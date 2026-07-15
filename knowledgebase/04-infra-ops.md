# 04 — Infrastructure & Ops

**Michael Parks Design — Claude Code knowledgebase.**

RunCloud / DigitalOcean hosting knowledge. Added 2026-07-15.

---

## How this file works — scope

`00`–`03` are **build** knowledge: they serve a project from kickoff to go-live, and go-live ends their involvement. This file is the exception. It holds **server and hosting** knowledge — how RunCloud actually behaves, where things live, why a verified change doesn't show up. It is true across every project and it applies *during* a build, *at* deploy, and *after* go-live.

It exists because this knowledge kept getting rediscovered. It has no seam and no harvest ritual: entries land here directly, because there is no per-project copy of a server.

**What does NOT go here: coordinates.** No server IPs, no zone IDs, no credentials, no account-specific inventory. This repo is public, and those are per-machine state that goes stale — per `00`'s memory model, they belong in auto-memory. This file documents **mechanisms**, which stay true. When you need a coordinate, look it up live (`/servers` API listing, `curl -s ifconfig.me`, the secrets file).

Entry format matches `03`: symptom, why, fix, provenance.

---

## RunCloud API

Base `https://manage.runcloud.io/api/v3`, bearer token (path in `global-CLAUDE.md`). **Use the API first for any RunCloud task; SSH only for files or what the API doesn't expose.**

### A webapp must always have ≥1 domain binding
**Symptom / When:** `DELETE /servers/{s}/webapps/{w}/domains/{d}` returns `403 "Web Application must have at least one domain name."` when removing the last domain.
**Fix:** POST a placeholder first (`placeholder-foo.invalid`, or an `app-name.<ip>.nip.io`), then DELETE the real one.
**First seen:** 2026-07-14.

### The domain endpoint has no PATCH/PUT — only GET/HEAD/DELETE
**Symptom / When:** You need to change `www` / `redirection` on a primary domain and find no update endpoint.
**Why:** The endpoint genuinely supports only GET/HEAD/DELETE. There is no in-place edit.
**Fix:** The three-call dance — add placeholder → delete the misconfigured primary → re-POST the real domain with `{"name":"…","www":true,"redirection":"non-www"}` → delete the placeholder. The recreated domain reports `type: "alias"` rather than `"primary"`; nginx serves it correctly and RunCloud doesn't appear to treat `type` semantically.
**First seen:** 2026-07-14.

### SSL POST requires BOTH `method` AND `provider`
**Symptom / When:** `POST /servers/{s}/webapps/{w}/ssl` with only `method` returns `422 "The provider field is required"` — non-obvious, since `method` already names the provider.
**Fix:** Set both to `"letsencrypt"`. The environment field is `"live"` (camelCase, **not** `"production"`):
```json
{"method":"letsencrypt","provider":"letsencrypt","enableHttp":true,
 "enableHsts":false,"environment":"live","authorizationMethod":"http-01"}
```
LE issuance is fast — `validUntil` populates within ~20s once DNS is ready.
**First seen:** 2026-07-14.

### LE HTTP-01 needs DNS pointing at the new origin FIRST
**Symptom / When:** Issuing LE before the DNS flip on a Cloudflare-proxied migration — CF routes the ACME challenge to the OLD origin and validation fails.
**Fix:** Flip DNS, then issue. The accepted trade-off is a brief CF 525/526 window during issuance (1–3 min); this is preferred over a CF Origin Cert for consistency across sites.
**First seen:** 2026-07-14.

### Webapp `user` on create takes a numeric `system_user_id`, not a username
**Symptom / When:** `"user": "runcloud"` on webapp create returns `422 "Invalid system user id."`
**Fix:** Pull the numeric ID by listing existing webapps on the destination server — they share a `server_user_id`.
**First seen:** 2026-07-14.

### Deleting a webapp cascades to its database
**Symptom / When:** `DELETE /servers/{s}/webapps/{w}` removes the webapp files **and** the associated MariaDB database — the DB 404s immediately, no separate DELETE needed.
**Fix:** Know this before deleting. DB *users* may persist — check `/databaseusers` if cleanup matters.
**First seen:** 2026-07-14, verified across two migrations.

### PHP runtime settings can only be set at webapp CREATE
**Symptom / When:** You want to change `memory_limit` / `post_max_size` / `upload_max_filesize` / `processManager` / `openBasedir` / `disableFunctions` / timezone via the API and find nothing that works.
**Why:** `/webapps/{w}/settings`, `/settings/fpm`, `/settings/runtime`, `/settings/security` are all **GET-only** (verified via OPTIONS). `PATCH /settings/php` exists but changes only the PHP **version** — passing runtime values returns "The selected PHP version is already active."
**Fix:** Set them in the create payload, or change them in the RunCloud UI afterward. There is no API path post-create.
**First seen:** 2026-07-14.

---

## Cron

### The RunCloud panel OWNS the user crontab — never `crontab -e`
**Symptom / When:** A hand-edited crontab entry reverts, or a fix "doesn't stick."
**Why:** RunCloud stores cron jobs in its own DB and **rewrites the user crontab from it**. Anything added by hand is transient.
**Fix:** Always fix cron via the API or panel. A PATCH syncs to the live crontab within seconds.
**First seen:** 2026-07-14.

### Cron POST/PATCH takes SPLIT schedule fields, not a `time` string
**Symptom / When:** You try to send `"time": "*/5 * * * *"` because that's what GET returns.
**Why:** GET returns a single `"time"` string, but write endpoints take the fields separately — an asymmetry that reads like a bug.
**Fix:** Send `minute`, `hour`, `dayOfMonth`, `month`, `dayOfWeek` as separate fields. `POST /servers/{s}/cronjobs`; `PATCH /servers/{s}/cronjobs/{id}` **also** requires `label`, `username`, and `command`.
**First seen:** 2026-07-14.

### WP pseudo-cron is not good enough for billing — use real cron
**Symptom / When:** A site with subscription renewals depends on visitor traffic to fire `wp-cron.php`. Low-traffic sites miss renewals.
**Fix:** A RunCloud cron job (`*/5 * * * *`, `php<ver>rc wp-cron.php`) plus `DISABLE_WP_CRON = true` in `wp-config.php`. Reset the FPM opcache after the wp-config edit so running workers pick it up (see caching below). Verify: due-event backlog drains to 0 and the expected scheduled action row still exists.
**First seen:** VMG, 2026-07-14 — renewal billing had no guaranteed heartbeat.

---

## Caching — why a verified change doesn't show over HTTP

### Three independent cache layers, each cleared differently
**Symptom / When:** A change confirmed under WP-CLI (`wp eval`, `wp option get`) is invisible over HTTP. Edited PHP, a changed option, a new filter — all "not taking."
**Why:** Three layers, ordered app-outward. There is **no passwordless sudo** on these boxes, so php-fpm cannot be reloaded — hence the out-of-band tricks.

**1. FPM OPcache.** The FPM pool runs `opcache.validate_timestamps=0` — each PHP file compiles once and mtime is never re-checked.
- *Trap:* `wp eval 'ini_get("opcache.validate_timestamps")'` reads the **CLI** php.ini (where it's ON) — misleading. The FPM pool ini is the one that matters.
- *Trap:* `wp eval 'opcache_reset();'` resets only the **CLI** opcache — a separate process.
- **Fix:** run `opcache_reset()` *inside an FPM request* — drop a one-off `<?php opcache_reset();` in the web root, curl it, delete it.

**2. RunCloud native FastCGI cache.** Full-page cache; responses carry `x-runcloud-cache: HIT|MISS|BYPASS`. Caches more than you'd expect — including `robots.txt` and even `.php` responses (a deleted throwaway script can keep returning 200 from cache).
- **Fix:** `wp runcloud-hub purgeall` (registered WP-CLI command, simplest) or `wp eval 'RunCloud_Hub::purge_cache_all();'`. `?cb=<random>` forces a one-off BYPASS.

**3. Cloudflare edge.** Sites run CF Full proxy; static-ish paths cache (observed `robots.txt` at 4h). Purge needs the **Cache:Purge** permission — a DNS-scoped token returns code 10000 "Authentication error" even for `purge_everything`. Purge in the dashboard, widen the token, or wait the TTL.

**Verify at origin, bypassing CF:** `curl -sS -k -H "Host: <domain>" https://127.0.0.1/<path>?cb=$RANDOM`
**First seen:** VMG, 2026-06-08 — a live `robots_txt` filter stayed invisible through all three.

---

## Logs

### Hybrid-stack PHP logs live in the apache2 dir — there is no app-local logs dir
**Symptom / When:** You look for `webapps/<app>/logs/` and it doesn't exist. Docs referencing one are wrong.
**Why:** On RunCloud **hybrid** apps (nginx → Apache → FPM), PHP `error_log()` output is captured by `proxy_fcgi`.
**Fix:**
- PHP errors → `/home/runcloud/logs/apache2/app-<name>_error.log` (as `AH01071: Got error 'PHP message: …'` lines)
- nginx front end → `/home/runcloud/logs/nginx/app-<name>_{access,error}.log`
**First seen:** 2026-07-14.

---

## Cross-server work

### `runcloud@<other-server>` is pre-keyed between servers in the account
**Symptom / When:** You're about to set up a GitHub mirror, SFTP-via-local, or a RunCloud Git Pull integration just to move files between two servers.
**Why:** The `runcloud` user already has SSH key auth configured between RunCloud-managed servers in the same account (`~/.ssh/` holds the keypair and the relevant `authorized_keys`).
**Fix:** Just `rsync -az source/ runcloud@<dest>:dest/`. No key dance. Destination addresses come from the `/servers` API listing; the current host's own is `curl -s ifconfig.me`.
**First seen:** 2026-07-14, on a cross-server migration.

---

## Secrets

### Shared API secrets live in `~/.env`, outside every web root
**Why it matters:** Webapp roots (`/home/runcloud/webapps/app-*/`) are **served directly** — a secret there is a public URL away from disclosure. `~/.env` sits in the home dir, chmod 600, outside all of them.
**Fix / convention:**
- Load with `set -a; . ~/.env; set +a`
- Add new shared creds (Cloudflare, Mailgun, etc.) **here**, rather than scattering them per-project.
- The RunCloud API token lives separately — see `global-CLAUDE.md`.
- **Inject values without echoing them to the transcript** — `read -rs` into a `sed` replace.
- Token scopes are deliberately narrow, so a call can fail on permissions while the token still verifies as active. Check the scope before assuming the API is broken.
**First seen:** 2026-06-07.
