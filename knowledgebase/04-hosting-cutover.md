# 04 — Hosting, Cutover & Performance

**How the hosting layer behaves, and what bites during a go-live. The ops accumulator.**

Established 2026-07-15 at the TAB harvest. Verified against RunCloud (hybrid Nginx+Apache, PHP 8.4) / RunCache 0.1.0 / Cloudflare / ShortPixel / Perfmatters.

---

## How this file works

Same contract as `03`: **inherited, not authored**. A lookup catalog, consulted by symptom — not read cover to cover. Same seam, same entry format, same bar (>~15 minutes to figure out, likely to bite again), same go-live harvest.

**Why this file exists separately from `03`.** `03` is the build stack — how Bricks, ACSS, ACF and WS Form behave while you build. This file is the layer underneath and after: the box, the CDN, the caches, the cutover itself. They're different domains and you consult them at different moments. Mixing them made `03` slower to scan by symptom, which is `03`'s whole job.

**Scope note.** `00` originally scoped the knowledgebase to *"Local → Staging → go-live"* and explicitly not to live-site maintenance. This file deliberately extends past that line: a cutover IS the end of the lifecycle, and the post-launch performance entries were bought at real cost on a real launch. The scope grew; that's an honest expansion, recorded here rather than left implicit. See `00`.

**Entry format** — match it exactly:

```
### [Short pattern title — what you would search to find this]
**Symptom / When:** [the observable failure]
**Why:** [the underlying mechanism, briefly]
**Fix:** [the actual fix, copy-pasteable where possible]
**First seen:** [project], [YYYY-MM-DD] — [the concrete incident]
```

---

# === ESTABLISHED — "we know" ===

## RunCloud

### RunCloud hybrid (Nginx+Apache) serves static files by the file's "other"-read bit — `.htaccess` does NOT protect them
**Symptom / When:** Project docs left in the public web root (`docs/`, `assets/`, `notes/`, a stray `.html`/`.png`/`.csv`) stay reachable at HTTP 200 even with a per-dir `Require all denied` `.htaccess` and a root `<FilesMatch>` deny. Confusingly the protection is **partial** — most files 403, but a few static ones leak, and **not by extension** (one `.png` leaks while sibling `.png`s 403). Reads like a random misconfiguration.
**Why:** On RunCloud's **hybrid** stack Nginx is the front and serves static assets **directly**, never consulting Apache — so `.htaccess` (an Apache mechanism) is bypassed for anything Nginx serves itself. The access decision for those files falls to the filesystem **"other" (world) read bit**: mode `644` (`o+r`) → served; mode `640`/`600` (`o-r`) → 403. Files that happen to be proxied to Apache get 403 from `.htaccess` — which is exactly why the leak looks random. **The discriminator is the permission bit, not the extension and not the `.htaccess`.**
**Fix:** Make protection mechanism-independent with filesystem perms:
```bash
chmod -R o-rwx docs assets notes && chmod o-rwx CLAUDE.md
```
Owner (the PHP-FPM/Apache user) keeps full access, so the site and any Remote-SSH session are unaffected. Verify by curling each file — a `200` means it's still leaking. Note `wp-config.php` returning 200 with a **0-byte body** is fine (PHP executed, no source emitted); `.htaccess` is 403 because nginx-rc blocks dotfiles natively regardless of perms.
**Also:** the per-webapp Nginx vhost (`/etc/nginx-rc/conf.d/*.conf`) is root-owned and unreadable to the webapp user, and the RunCloud API does not expose it — editing Nginx is not an available lever without panel or root access. Perms need none of them.
**First seen:** TAB, 2026-06-27 — a cutover left project docs in the web root; 3 of ~100 files (two `.html` + one `.png`, all `644`) leaked at 200 while `600` favicons were already 403. `chmod -R o-rwx` flipped all to 403 with zero site impact, and superseded a planned "needs an Nginx deny rule via the panel" workaround.

### RunCloud Let's Encrypt via API — `environment` is `live` (not `production`), and issuance takes minutes (poll ≥15)
**Symptom / When:** `POST /servers/{id}/webapps/{wid}/ssl` to issue LE. (1) `"environment":"production"` → 422 "selected environment is invalid." (2) After a valid request, `validUntil` stays `null` and 443 stays closed for several minutes — which reads as failure.
**Why:** RunCloud's LE env enum is `live`/`staging`. And issuance (HTTP-01 validation + nginx deploy) is an **async background job** that commonly takes ~8–10 minutes, not seconds — a 5-minute poll times out before the cert lands.
**Fix:**
```json
{"provider":"letsencrypt","authorizationMethod":"http-01","environment":"live","enableHttp":true,"enableHsts":false}
```
Required: `provider`/`enableHttp`/`enableHsts`, plus `authorizationMethod` and `environment` for LE. **Poll `validUntil` for ≥15 minutes before assuming failure.** The webapp-level cert is a SAN over ALL attached domains, so every attached hostname must resolve to the box for HTTP-01 — or use `dns-01`.
**First seen:** TAB, 2026-06-27 — guessed `production` (422), then a 5-minute poll timed out and triggered a **premature DNS rollback and an ~8-minute https blip**. The cert had actually issued fine ~9 minutes in. The rollback was the outage, not the cert.
**Timing variance — do not shorten the poll.** A VMG issuance on 2026-07-14 saw `validUntil` populate in ~20s with DNS already resolving to the box. So the job is *sometimes* fast, which is a trap: one fast run teaches you to poll for 30s, and the next cutover takes 9 minutes and you roll back a cert that was going to land. **≥15 minutes regardless.** (A `method` field is sometimes passed alongside `provider` — it is not required; `provider` alone is what the API validates on.)

### RunCloud API — a webapp must always have ≥1 domain, so the last DELETE 403s
**Symptom / When:** `DELETE /servers/{s}/webapps/{w}/domains/{d}` returns `403 "Web Application must have at least one domain name."` — removing the domain you're trying to replace is exactly what you can't do.
**Fix:** POST a placeholder first (`placeholder-foo.invalid`, or `app-name.<ip>.nip.io`), then DELETE the real one, then clean up the placeholder.
**First seen:** 2026-07-14, on a migration.

### RunCloud API — the domain endpoint has NO PATCH/PUT; changing `www`/`redirection` needs a placeholder dance
**Symptom / When:** You need to fix `www` or `redirection` on a primary domain and find no update endpoint. The endpoint supports only GET/HEAD/DELETE — there is no in-place edit.
**Fix:** Three calls: add placeholder → delete the misconfigured primary → re-POST the real domain with `{"name":"…","www":true,"redirection":"non-www"}` → delete the placeholder. The recreated domain reports `type: "alias"` rather than `"primary"`; nginx serves it correctly and RunCloud doesn't appear to treat `type` semantically.
**First seen:** 2026-07-14.

### RunCloud API — PHP runtime settings can ONLY be set at webapp CREATE
**Symptom / When:** You want to change `memory_limit` / `post_max_size` / `upload_max_filesize` / `processManager` / `openBasedir` / `disableFunctions` / timezone via the API, and nothing works.
**Why:** `/webapps/{w}/settings`, `/settings/fpm`, `/settings/runtime`, `/settings/security` are all **GET-only** (verified via OPTIONS). `PATCH /settings/php` exists but changes only the PHP **version** — passing runtime values there returns the misleading "The selected PHP version is already active."
**Fix:** Set them in the create payload, or change them in the RunCloud UI. There is no API path post-create. Plan the create payload accordingly — retrofitting means the UI.
⚠️ **Reads must be server-scoped**: `GET /servers/{s}/webapps/{w}/settings`. The bare `/webapps/{w}/settings` form returns `{"message":"Not found"}` — which reads like the endpoint doesn't exist at all. (Same body is the FPM-tuning diagnostic: `processManagerMaxChildren` et al.)
**First seen:** 2026-07-14; path scoping 2026-08-11.

### RunCloud API — `user` on create takes a numeric `system_user_id`; deleting a webapp CASCADES to its database
**Symptom / When:** Two unrelated create/delete traps. (1) `"user": "runcloud"` on webapp create → `422 "Invalid system user id."` (2) `DELETE /servers/{s}/webapps/{w}` silently takes the associated MariaDB database with it — the DB 404s immediately after.
**Fix:** (1) Pull the numeric ID by listing existing webapps on the destination server — they share a `server_user_id`. (2) Know the cascade before deleting; no separate DB DELETE is needed, and there's no confirmation that it's about to happen. DB *users* may persist — check `/databaseusers` if cleanup matters.
**First seen:** 2026-07-14, verified across two migrations.

### RunCloud API — webapp CREATE is a typed endpoint (`/webapps/custom`), not the collection route
**Symptom / When:** `POST /servers/{s}/webapps` with a fully valid payload → "The POST method is not supported for this route. Supported methods: GET, HEAD."
**Why:** Creation is typed per app kind: `POST /servers/{s}/webapps/custom` and `…/webapps/wordpress` both exist (OPTIONS answers `GET,HEAD,POST,DELETE`). The collection route is list-only.
**Fix:** POST the create payload to `/webapps/custom`. The FPM process-manager fields (`processManager`, `processManagerMaxChildren`, `processManagerStartServers`, …) belong in this payload — creation is the only API moment they can ever be set (see the runtime-settings entry above). Verified: values apply exactly, including below-stock ones, and `ps`-visible worker counts match. Response carries `pullKey1/2`; the numeric suffix of `pullKey1` is the creation timestamp, handy for fleet-convention DB names.
**First seen:** 2026-08-11.

### RunCloud API — DB user grant is `POST /databases/{id}/grant` with `{"id": <dbUserId>}`
**Symptom / When:** DB and DB user create fine via API, but attaching the user fails: `/attachuser`, `/users`, `/attach` all exist and answer GET — and reject POST.
**Why:** GET-only routes here are decoys, not near-misses. The real route is `POST /servers/{s}/databases/{dbId}/grant`, and its body field is just `id` (the database-user id) — the descriptive guess `{"databaseUserId": …}` returns "The id field is required."
**Fix:** `POST /databases {"name"}` → `POST /databaseusers {"username","password","verifyPassword"}` → `POST /databases/{dbId}/grant {"id": <userId>}`. Probe unknown routes with OPTIONS before guessing payloads.
**First seen:** 2026-08-11.

### RunCloud — a new webapp ships an `index.html` that SHADOWS your `index.php`
**Symptom / When:** WordPress installed cleanly (wp-cli happy, `wp-login.php` reachable) but the front page serves RunCloud's "Welcome to RunCloud" placeholder.
**Why:** Webapp creation drops a default `index.html` in the web root and the server prefers it over `index.php`. `wp core download` doesn't remove it, and both pages return 200, so status-code checks pass.
**Fix:** Delete `index.html` after installing, then verify the rendered `<title>` over HTTP with a cache-buster — never the status code alone.
**First seen:** 2026-08-11.

### RunCloud hybrid — a staging basic-auth gate in `.htaccess`, and the two exemptions it MUST carry
**Symptom / When:** A basic-auth-gated staging vhost; weeks later the LE cert fails to renew, and/or WP's pseudo-cron never fires — both silently.
**Why:** Two *background* requests traverse the public edge and eat the 401 like any stranger: the ACME http-01 challenge (`/.well-known/acme-challenge/…`) at renewal time (~day 60), and WordPress's pseudo-cron loopback to `/wp-cron.php` — it resolves the site's public URL, so it leaves the box (through the CDN if proxied) and returns as an anonymous request.
**Fix:** htpasswd ships at `/RunCloud/Packages/apache2-rc/bin/htpasswd` (`apache2-rc`, **not** `httpd-rc`; use `-B -i` for bcrypt read from stdin). Gate with exemptions:
```apache
SetEnvIf Request_URI "^/\.well-known/acme-challenge/" auth_exempt
SetEnvIf Request_URI "^/wp-cron\.php$" auth_exempt
AuthType Basic
AuthName "Staging"
AuthUserFile /home/<user>/.htpasswd-<app>
<RequireAny>
  Require env auth_exempt
  Require valid-user
</RequireAny>
```
Verify all four states with cache-busters: bare → 401, creds → 200, an ACME probe path → **404** (the origin answering; a 401 means the exemption failed), `/wp-cron.php` → 200. The same two exemptions apply to ANY edge gate — Cloudflare Access needs them as Bypass policies.
**First seen:** 2026-08-11.

### RunCloud hybrid — PHP `error_log()` lands in the apache2 dir; there is NO app-local logs dir
**Symptom / When:** You go looking for `webapps/<app>/logs/` and it doesn't exist. Docs (including older project notes) that reference one are simply wrong, so you conclude logging is broken.
**Why:** On **hybrid** apps (nginx → Apache → FPM), PHP `error_log()` output is captured by `proxy_fcgi` and lands in Apache's log — the same hybrid architecture behind the static-file entry above.
**Fix:**
- PHP errors → `/home/runcloud/logs/apache2/app-<name>_error.log` (as `AH01071: Got error 'PHP message: …'` lines)
- nginx front end → `/home/runcloud/logs/nginx/app-<name>_{access,error}.log`
**First seen:** 2026-07-14.

### Cross-server — `runcloud@<other-box>` is already key-authed; just rsync
**Symptom / When:** You're about to set up a GitHub mirror, SFTP-via-local, or a RunCloud Git Pull integration purely to move files between two servers in the same account.
**Why:** The `runcloud` user already has SSH key auth configured between RunCloud-managed servers in the account (`~/.ssh/` holds the keypair and the relevant `authorized_keys`).
**Fix:** `rsync -az source/ runcloud@<dest>:dest/`. No key dance. Destination addresses come from the `/servers` API listing; the current box's own is `curl -s ifconfig.me`.
**First seen:** 2026-07-14, on a cross-server migration.

## Cron

### The RunCloud panel OWNS the user crontab — `crontab -e` edits are transient
**Symptom / When:** A hand-edited crontab entry reverts, or a cron fix "doesn't stick."
**Why:** RunCloud stores cron jobs in its own DB and **rewrites the user crontab from it**. Anything added by hand is living on borrowed time.
**Fix:** Always fix cron via the API or the panel — never `crontab -e`. A PATCH syncs to the live crontab within seconds.
**First seen:** 2026-07-14.

### RunCloud cron POST/PATCH takes SPLIT schedule fields, not the `time` string GET returns
**Symptom / When:** You send `"time": "*/5 * * * *"` — because that's exactly what GET returned — and it fails.
**Why:** An asymmetry that reads like a bug: GET returns a single `"time"` string, but the write endpoints take the fields separately.
**Fix:** Send `minute`, `hour`, `dayOfMonth`, `month`, `dayOfWeek` as separate fields. `POST /servers/{s}/cronjobs`; `PATCH /servers/{s}/cronjobs/{id}` **also** requires `label`, `username`, and `command`.
**First seen:** 2026-07-14.

### WP pseudo-cron is not good enough for billing — give renewals a real heartbeat
**Symptom / When:** A site with subscription renewals relies on visitor traffic to fire `wp-cron.php`. On a low-traffic site, scheduled payments fire late or not at all — and nothing alerts you.
**Fix:** A RunCloud cron job (`*/5 * * * *`, `php<ver>rc wp-cron.php`) plus `DISABLE_WP_CRON = true` in `wp-config.php`. **Reset the FPM opcache after the wp-config edit** so running workers pick it up. Verify: the due-event backlog drains to 0 and the expected scheduled action row still exists.
**First seen:** VMG, 2026-07-14 — renewal billing on a live subscription had no guaranteed heartbeat.

## Cloudflare

### A DNS record flipped to proxied needs Universal SSL **active** first — and a zone-scoped token can't read cert status
**Symptom / When:** You flip apex/www to proxied (orange) and visitors get a cert error. Or you want to confirm the edge cert is ready but `GET /zones/{id}/ssl/certificate_packs` returns 9109 "Unauthorized."
**Why:** Cloudflare's Universal SSL edge cert provisions **asynchronously** after a zone goes active (minutes to hours). Proxying before it's active serves no valid edge cert. A token scoped to DNS + ZoneSettings + ZoneRead lacks the SSL/cert read scope, so you cannot check status via API — you need the dashboard.
**Fix:** Confirm Universal SSL is **Active** (dashboard → SSL/TLS → Edge Certificates) before flipping to proxied, or canary on `www` first. **Correct sequence with origin-cert-via-LE:** point apex **grey** → issue LE at the origin → confirm origin https → *only then* flip to orange + SSL Full (strict). Verify the proxied result with `--resolve` to a CF anycast IP (`server: cloudflare` + `cf-ray` present).
**First seen:** TAB, 2026-06-27 — held the orange flip until Universal SSL was dashboard-confirmed; the scoped token couldn't read cert_packs.

### Cloudflare injects a managed `robots.txt` that blocks every AI crawler — default-ON for new zones
**Symptom / When:** Post-migration, `robots.txt` contains a `# BEGIN Cloudflare Managed content` block with `Content-Signal: search=yes,ai-train=no,use=reference` and a flat `Disallow: /` for `ClaudeBot`, `GPTBot`, `Google-Extended`, `Applebot-Extended`, `CCBot`, `Bytespider`, `Amazonbot` and `meta-externalagent` — none of which anyone configured. It is prepended **above** the SEO plugin's own rules.
**Why:** It is a Cloudflare zone-level default on newly created zones, injected at the edge. Nothing in WordPress produces it and nothing in WordPress can remove it. **Ordinary search is unaffected** — `Googlebot` is a separate agent and stays allowed, and `Google-Extended` governs only AI training/grounding — so this is not an SEO regression. It matters when the site deliberately publishes structured data (`FAQPage`, `Service`, `LocalBusiness`) *for* answer engines, which is now the main reason to publish FAQ schema at all since FAQ rich results were restricted to government/health sites in 2023.
**Fix:** Confirm it is edge-side, not origin-side, with a byte diff — this takes one command and rules out the whole WordPress layer:
```bash
curl -s --resolve dom:443:<ORIGIN_IP>     https://dom/robots.txt | wc -c   # e.g. 133
curl -s --resolve dom:443:<CF_ANYCAST_IP> https://dom/robots.txt | wc -c   # e.g. 1969
```
Then disable it in the **Cloudflare dashboard** (it has shipped under both *Security → Settings* and *AI Crawl Control*; the label moves). ⚠️ **A DNS+ZoneSettings-scoped token cannot reach the control** — `/bot_management`, `/managed_headers` and `/rulesets` all return **403**, while `/managed_robots_txt`, `/ai_crawl_control` and `/content_signals` return **400 "could not route"** (they do not exist under those names), and it is absent from all 56 zone settings. Do not burn time probing for an endpoint; it is a dashboard toggle or a broader token. Verify after: the edge byte count should drop to match origin exactly, with no `BEGIN Cloudflare Managed content` marker.
**First seen:** Highland, 2026-08-04 — found during the post-cutover verification sweep. Directly contradicted the documented reason for publishing `FAQPage` on that build. Disabled; edge went 1969 → 133 bytes, byte-identical to origin.


## Cutover

### Verifying a cutover *from ANY box that resolved the domain before the flip* — its DNS cache still points at the OLD origin
**Symptom / When:** Post-cutover, `curl https://thedomain/` returns the OLD site (stale content, old headers), while real users and `dig @8.8.8.8` show the new origin. Leads to hours chasing a phantom "stale cache" (FastCGI? Cloudflare?) that does not exist. **This fires from any box that resolved the domain before the flip — not only the migrated origin box.** That includes the staging/admin box you are running the cutover *from*, which is not the new origin at all.
**Why:** During cutover the box queried the apex while it still pointed at the old IP, and cached that (systemd-resolved / the record's TTL). Bare `curl` from that box keeps hitting the old origin until the cache expires. **Nothing to do with any page cache** — which is exactly why it burns so much time. ⚠️ Do not read this as "I'm not on the origin box, so bare curl is safe here" — the mechanism is the resolver, and it is indifferent to which box you are on.
**Fix:** Never trust bare `curl` from the box during a cutover.
```bash
curl --resolve domain:443:<NEW_ORIGIN_IP> https://domain/   # the new origin directly
curl --resolve domain:443:<CF_ANYCAST_IP> https://domain/   # the public/edge path
getent hosts domain                                          # what the box actually resolves
```
`resolvectl flush-caches` clears it, but `--resolve` is the reliable habit — use it for **every** cutover verification regardless of which box you are on. Note the inverse also matters post-launch: once the cache clears, bare curl becomes trustworthy again — re-verify rather than carrying the workaround forever.
**First seen:** TAB, 2026-06-27 — a long false-alarm "RunCloud/Cloudflare is serving a stale homepage" investigation; the box's resolver simply still pointed the apex at the old box. **Highland, 2026-08-04** — same trap from a *third* box: immediately after the flip, a bare `curl` from jbm003 (staging, neither old nor new origin) returned `server: Squarespace` with the departing host's July LE cert while the edge was already serving the new site correctly. Believing it would have triggered a rollback of a cutover that had already succeeded. Folded at the Highland harvest with the framing broadened from "the origin box" to "any box".

### Verify a served static asset via its `?ver=` URL — the BARE file URL is served from a stale static cache
**Symptom / When:** You edit the child theme `style.css`, `curl` the plain file URL to verify, and get the OLD content — even though the on-disk file is correct and the page itself shows the new styling.
**Why:** Nginx/RunCloud static-file caching serves a cached copy for the bare URL. WordPress enqueues the stylesheet with `?ver=filemtime(...)`, so the *page* always requests a fresh build when the file changes — but a bare `curl …/style.css` can hit the stale cache and mislead verification.
**Fix:** Verify against the exact URL the page references (grep the page for `style.css?ver=…` and curl that), or append a cache-buster (`?cb=$RANDOM`). Trust the on-disk file and the versioned URL, not the bare URL.
**First seen:** TAB, 2026-06-25 — chased a "CSS not applying" ghost for several steps; the versioned URL had served the correct CSS all along.

### Migrating a site that carries WooCommerce Subscriptions — reset the staging-site lock or renewals silently stop
**Symptom / When:** Post-migration, automatic subscription renewals are created but never charged; the gateway is never called.
**Why / Fix:** WCS's duplicate-site guard still points at the pre-migration URL, so production is treated as a clone. Full mechanism, the CLI fix and the verification step: `03` → **WooCommerce** → "the staging-site lock silently SKIPS all automatic renewals after a Local→production migration". **Belongs on the deploy checklist for any migration carrying subscriptions.**
**First seen:** VMG, 2026-06-07 — cross-referenced here at the 2026-07-15 harvest because the trigger is the cutover, while the mechanism is WCS's.

### `google-site-verification` TXT records must be carried into the new DNS zone — and they may not be Search Console at all
**Symptom / When:** Planning a platform migration (Squarespace/Wix/Shopify → WordPress) where DNS moves to a new provider. The apex carries one or more `google-site-verification=…` TXT records, often several, and often nobody at the client remembers creating them. The temptation is to drop them and "start fresh".
**Why:** Two separate things get conflated. (1) `google-site-verification` is **not Search Console-exclusive** — the same record type backs Google Workspace, Google Ads, Merchant Center, and can back a Business Profile. A client who "doesn't know what Search Console is" may still have a record that is load-bearing for their email or their GBP. (2) The *history* argument for keeping them is **false**: Google collects Search Console data whether or not anyone has verified, and verifying a property surfaces up to **16 months** of backfilled performance data. Un-verifying revokes a *person's access*; it does not delete Google's data.
**Fix:** Copy every existing TXT record into the new zone verbatim at cutover — three strings cost nothing, and a blind deletion during the riskiest hour of a migration is expensive to diagnose. Then verify your **own** new `sc-domain:` Domain property (covers http/https and www/non-www in one), add the client as an owner so it is their asset, and only then identify the legacy records from *inside* Search Console (Settings → Users and permissions) before pruning. A DNS record alone tells you nothing about what it belongs to or who holds it.
```bash
dig +short TXT example.com | grep google-site-verification
```
**First seen:** Highland, 2026-08-04 — three verification records on the apex, client unfamiliar with Search Console, and no way to attribute them from DNS. An earlier note in this project's docs claimed dropping them would destroy the site's search history; that was wrong and was corrected on the spot.


### A crawl only finds what is still linked — platform sitemaps under-report, and legacy URLs need analytics
**Symptom / When:** Building the 301 map for a migration. You pull the old site's `sitemap.xml`, get a handful of URLs, and assume that is the site. Post-launch, 404s appear for pages nobody knew existed.
**Why:** Hosted-platform sitemaps list current, published, navigation-reachable pages. They omit orphaned pages, old campaign landing pages, and anything unlinked but still indexed — precisely the URLs that carry inbound links and still receive traffic. A crawl inherits the same blind spot: it can only follow links that exist today.
**Fix:** Three sources, not one. (1) The platform sitemap. (2) A depth-limited crawl from the homepage to catch what the sitemap omits. (3) **Search Console / analytics top-pages export**, which is the only source that surfaces URLs nothing links to any more. Do (3) *before* DNS moves, while access still exists. Also check which paths the new CMS already canonicalises — WordPress 301s a missing trailing slash natively, so `/about` → `/about/` needs no rule, and writing one only masks a future slug change.
**First seen:** Highland, 2026-08-04 — the Squarespace `sitemap.xml` listed 4 URLs; a crawl found 6; only 3 needed rules, because WordPress already handled the slash-only differences. Analytics-sourced legacy URLs were flagged as the remaining gap.


### The departing host's HSTS header dictates your cutover order — check it BEFORE planning one
**Symptom / When:** Planning a platform migration where the new origin has no TLS certificate yet. The obvious order is: flip DNS → run Let's Encrypt HTTP-01 → done, accepting "a few minutes of 404s". In practice returning visitors get a **full-page certificate interstitial** for the whole window, and there is no http fallback to soften it.
**Why:** Three facts compound. (1) **LE HTTP-01 cannot validate until DNS already points at the new box**, so the cert can never be pre-issued on that method — DNS genuinely must move first. (2) A RunCloud webapp with no SSL has **no HTTPS vhost**, so every `https://` request falls through to the catch-all (which answers **200** with "Website Unavailable" under a mismatched cert — see the RunCloud entry above). (3) The departing host is very likely sending **HSTS**: Squarespace sends `max-age=15552000` (180 days), so every browser that visited in the last six months is pinned to HTTPS-only and **will refuse to fall back to http**. Every indexed URL is an https URL too. So the "soft" window is actually a hard failure for exactly the people most likely to visit.
**Fix:** Check first, then choose the order:
```bash
curl -sI https://the-old-domain.com/ | grep -i strict-transport-security
```
If HSTS is present (assume it is), go **proxy-first** and the gap disappears entirely:
1. Zone SSL → **Flexible** *first*, while records are still grey — it is a no-op until something proxies, and setting it after you proxy means a window where CF speaks HTTPS to a certless origin and serves the catch-all.
2. Flip the A record **and enable the proxy in the same change**. Visitors ride the CDN's own edge cert, which satisfies HSTS; the CDN reaches the origin over plain HTTP, which works from the start.
3. Issue LE at the origin — **the ACME challenge still validates through the proxy**, provided `always_use_https` is OFF so the http challenge is not redirected.
4. Origin cert live and verified → zone SSL → **Full (strict)** → *then* `siteurl`/`home` → `https://`. Flipping those while still on Flexible is the classic redirect loop.
5. `always_use_https` → on, so the http→https hop terminates at the edge.
**Reduce the apex to ONE record before flipping**, then PATCH that record in place — an atomic switch, rather than deleting three records and creating a fourth while resolvers round-robin across a mixed old/new set. The old host keeps serving on its remaining IP throughout.
**Set both records to TTL 60 well ahead of the cutover.** It makes propagation ~1 minute and, more importantly, makes rollback ~1 minute — which is what actually bounds your worst case.
**First seen:** Highland, 2026-08-04 — Squarespace → RunCloud/jbm001. Proxy-first was chosen after finding the 180-day HSTS; the cert then issued in **30 seconds**, but the decision was correct regardless, because the downside was a cert interstitial and not a 404. Zero user-visible interruption; verified at every step.


## RunCache (RunCloud Hub successor)

### RunCache flushes rewrite rules on activation → every CPT page 404s
**Symptom / When:** After activating/installing RunCache (or possibly a later cache op), **every custom-post-type URL 404s** — through the CDN AND at origin — while regular pages still 200. `wp rewrite list --format=count` shows a truncated set (e.g. **95**, missing the CPT rules) although `permalink_structure` is correct and the posts exist.
**Why:** The plugin calls `flush_rewrite_rules()` on a request where the core plugin's CPTs aren't registered yet, regenerating `rewrite_rules` WITHOUT them. **The SEO spine silently goes down** — and a cache plugin is the last thing you'd suspect.
**Fix:** `wp rewrite flush` (restores the full set — e.g. 95→194).
> **Standing rule: after ANY RunCache settings or cache operation, spot-check that a CPT URL returns 200.** The rewrite count is the canary.
**First seen:** TAB, 2026-06-28 — RunCache activation between sessions 404'd every service/location/project page; caught during routine cache verification, not by monitoring.

### RunCache native page-cache exclusions are enforced at NGINX and synced via the RunCloud API — editing the WP option alone doesn't reach nginx
**Symptom / When:** You add a URL/cookie exclusion but the page still serves cached (`x-runcache-status: HIT`); or you can't find where the rules live (there's no `/page-cache` endpoint on the RunCloud v3 API for a hybrid webapp).
**Why:** `runcloud-native` caching is FastCGI/nginx-level (config `runcache-nginx.conf`). Rules compile into nginx and are pushed via the RunCloud API using `rcapi_key/secret` stored in the `runcache_runcloud_settings` option. A plain `update_option` never regenerates nginx.
**Fix:** Edit via the plugin API, then push:
```php
( new \RunCache\Core\Rules() )->save_settings( $s );  // exclude_url_mch = regex array, e.g. '/request-a-quote.*'
```
```bash
wp runcache-native send-settings   # pushes to nginx — without this, nothing changes
```
Defaults already exclude `/wp-admin/`, `/login.*`, `wp-json`, `feed`, `sitemap`, cart/checkout/account + bypass the logged-in cookie. **Verify origin-direct** (`curl --resolve …:<origin-ip>`): form/auth pages should return `x-runcache-status: BYPASS`.
**Why it matters:** cached HTML on a form page serves **stale form nonces** — the form silently fails for real users.
**First seen:** TAB, 2026-06-28 — excluding the quote/thank-you/contact/auth pages.

### RunCache v0.1.0 — BOTH the object-cache flush and `wp cache flush` are broken (`array_merge()` error), and stale reads will silently clobber your work
**Symptom / When:** Three escalating faces of one bug. (1) `wp cache flush` prints `[RunCache][error] Redis flush error: array_merge(): Argument #2 must be of type array, string given`. (2) After a bulk `wp search-replace` on posts, `get_post()` / rendered pages read **stale** while `wp db query` shows the DB is correct. (3) Worst: a later `wp_update_post()` on a search-replaced post **writes the stale object back**, silently reverting the fix.
**Why:** RunCache 0.1.0's Redis dropin has a broken group/full flush. Per-key get/set/delete work fine, so normal site operation is unaffected — which is why it hides. But `wp search-replace` writes **direct SQL** and its cache invalidation no-ops, so Redis keeps the OLD post object. `get_post()` then returns that stale object, and `wp_update_post()` persists it over your SQL fix. **Reads diverge by path:** `wp db query`/`LOCATE()` hit MySQL (fresh); `get_post`/`get_posts`/page renders hit Redis (stale).
**Fix:**
```bash
# force-flush Redis BEFORE any get_post-based edit or verification
PW=$(grep -oP "RUNCACHE_REDIS_PASSWORD',\s*'\K[^']+" wp-config.php)
redis-cli -a "$PW" -n 0 FLUSHDB     # single-app box — all keys are runcache*
wp runcache purge
# then re-check a CPT URL returns 200 (rewrite canary)
```
**Verify content writes with `wp db query` + `LOCATE()` (MySQL-direct), NOT `get_post` or rendered HTML**, until caches are flushed. Sequence edits so any `wp_update_post` on a post runs *before* a search-replace on that same post — or flush between them. For page-cache clears use `wp runcache purge`, not `wp cache flush`.
**First seen:** TAB, 2026-06-28 (the flush error, thought benign) → **TAB, 2026-07-15** (the real cost): an internal-link cleanup where search-replace fixed a URL, then a later `eval-file` did `get_post` → `wp_update_post` and restored the broken one. `LOCATE()` proved only that post was dirty. Re-running the replace + `FLUSHDB` (10,632 stale keys) + `runcache purge` resolved it.

### RunCache Redis object cache: config lives in `provider.redis` → `RUNCACHE_REDIS_*` consts; the legacy `RCWP_*` fallback won't pick up a plain host+password
**Symptom / When:** `wp runcache enable-object-cache` reports success but no dropin installs, no `RUNCACHE_REDIS_*` consts appear, Redis stays at 0 keys and `wp_using_ext_object_cache()` is none — even with `RCWP_REDIS_HOST`/`RCWP_REDIS_PASSWORD` in wp-config.
**Why:** `enable` uses the existing (empty) `provider.redis`. The `RCWP_*` fallback needs BOTH `RCWP_REDIS_PASSWORD` **and** `RCWP_REDIS_DOMAIN`, and treats the password as a `[username,password]` array — a plain string plus a missing DOMAIN makes it bail silently.
**Fix:** Set the provider explicitly:
```php
( new \RunCache\Core\ObjectCache() )->save_settings([
  'enable' => true, 'object_cache_type' => 'redis',
  'provider' => [ 'redis' => [ 'host'=>'127.0.0.1','port'=>'6379','password'=>$pw,'key'=>'runcache:tab:' ] ],
]);
```
Writes the consts and installs `object-cache.php`. Redis password = `/etc/redis/redis.conf` `requirepass` (sudo). Verify `wp_using_ext_object_cache()` and a climbing `dbsize`.
**⚠️ Before a second app shares Redis:** set a `maxmemory` cap and an eviction policy — a default install is uncapped `noeviction`.
**First seen:** TAB, 2026-06-28 — bare `enable-object-cache` no-op'd until `provider.redis` was set.

## LiteSpeed Cache / OpenLiteSpeed

*New section at the MBC harvest, 2026-08-25. OpenLiteSpeed boxes run LiteSpeed Cache rather than RunCache, and its optimisation features fail in ways that look like application bugs. All three entries below cost real debugging time before the cache was suspected.*

### LSCache ESI never works on OpenLiteSpeed — every `<esi:include>` ships raw to the browser
**Symptom / When:** Any ESI-dependent LSCache feature breaks, each wearing a different disguise. Observed: cart Update / remove-item forms silently no-op with no error anywhere (the nonce in the form input was an unresolved ESI tag); a ~32px empty strip above the header **for logged-in users only** (LSCache replaces the admin bar with an ESI block, so WP's `html{margin-top:32px}` bump CSS renders with no bar under it — reads convincingly as a body/root layout bug); a raw `wp_rest` nonce placeholder leaking into guest-cacheable HTML.
**Why:** ESI is a LiteSpeed **Enterprise** feature. OpenLiteSpeed does not process it *at all* — not in attributes, not in tag position. The LSCache plugin does not gate the setting on OLS, so it looks configurable, happily emits placeholders, and sends `X-LiteSpeed-Cache-Control: …,esi=on` that the server simply ignores. The `/?lsesi=…` endpoints work when requested directly, which misleads: it is the server-side *substitution* that never happens.
**Fix:** Turn it off. Nothing on OLS can use it, and the features that appear to need it don't — a mini-cart updates via WC's cart-fragments JS, which never involved ESI.
```bash
wp litespeed-option set esi false
wp litespeed-purge all
```
**Verification:** `curl -s <url> | grep -c 'esi:include'` on a served page; nonzero means leaking. **Check logged-in as well** — the admin-bar ESI only appears there. Gotcha inside the gotcha: `wp option get litespeed.conf.esi` prints **empty** for `false`, so use `wp eval 'var_export(get_option("litespeed.conf.esi","MISSING"));'` to tell "off" from "absent".
**First seen:** MBC, 2026-05-22 — a cart breakage cost ~90 minutes and was symptom-patched by editing the `esi-nonce` list, which fixed that one case by accident of scope and left a false mechanism on record ("OLS doesn't substitute ESI inside attributes, only element content" — it does neither). Root cause found 2026-08-07 while chasing the admin-bar gap; ESI disabled outright and the fleet swept.

### LSCache UCSS strips state-only selectors — anything that only renders after a user action
**Symptom / When:** On cacheable pages, a notice or state that appears only in response to a click renders **unstyled** — no background, no padding. The same markup on an uncached page (cart, checkout, account) is fine, which makes it look like a template problem rather than a cache one.
**Why:** UCSS crawls each cacheable page at regeneration time, walks the rendered DOM, and prunes every selector it cannot find. Selectors that exist only in a post-interaction state — success/error notices, `:hover`, `:focus`, markup injected after an action — are never present during a steady-state crawl, so they are pruned as "unused". Pages excluded from caching never get pruned, hence the split behaviour.
**Fix:** Allowlist the selector roots. Patterns are fine and cost nothing at runtime — the list is applied once at regeneration:
```bash
wp litespeed-option set optm-ucss_whitelist '.woocommerce*
.brxe-woocommerce*'
wp litespeed-purge all
```
**Design around it too:** where a JS-applied state can be expressed as an inline style rather than a class, do that — an inline `style.display` cannot be pruned, a `.is-hidden` class can. Any new component with hover/focus styling or a JS-toggled class needs its own allowlist entry; this is a recurring tax, not a one-time fix.
**Verify UCSS is even running before trusting the allowlist.** On one site `optm-ucss` was `1` while `wp-content/litespeed/ucss/` did not exist and zero UCSS files had ever been generated (CCSS generated normally, ~20 URLs sat queued) — so the allowlist was protecting nothing and the real CSS delivery was per-file minification. `ls wp-content/litespeed/ucss/ | wc -l` answers it in one command.
**First seen:** MBC, 2026-05-22 — a WooCommerce add-to-cart notice rendered stripped on `/shop/` but correct on `/cart/`.

### `optm-js_defer` defers **inline** scripts too — it rewrites them into base64 `data:` URIs
**Symptom / When:** An inline script whose entire purpose is to run *before paint* runs after it instead. Every pre-paint pattern breaks the same way: a no-flash theme switch, a "hide what this user already dismissed" guard, a layout-shift preventer. The user sees the element render and then vanish — a visible flash plus a CLS regression on a site that may otherwise measure 0. **The PHP source looks entirely correct**, which is what makes it expensive: the damage exists only in the served HTML.
**Why:** LiteSpeed's "Load JS Deferred" is not limited to external `<script src>`. It rewrites inline blocks into `<script src="data:text/javascript;base64,…" defer>`. `defer` means "after HTML parsing", so a script deliberately placed immediately after the element it manipulates no longer runs before that element paints.
**Fix:** Opt the script out. Add `data-cfasync="false"` alongside on any site on — or heading to — Cloudflare, since Rocket Loader does the same thing.
```html
<script data-no-optimize="1" data-cfasync="false"> /* stays inline and synchronous */ </script>
```
**Verification — never trust the source, curl the rendered page.** Decode to confirm *which* script you have; a page carries several and `grep -o … | head -1` will hand you the wrong one (the loadCSS polyfill is usually first):
```bash
curl -s "https://example.com/?cb=$RANDOM" | grep -c 'src="data:text/javascript;base64,'
```
Sibling to the Perfmatters entry below — Delay JS and Defer JS each independently break things. Two different plugins, same class of failure: **a JS optimisation silently relocating correct code.**
**First seen:** MBC, 2026-08-25 — a dismissable homepage announcement bar shipped with its dismissal script deferred, so anyone who had dismissed it would have watched it render and disappear on every subsequent visit. Caught only because the served HTML was checked rather than the PHP.

## Performance

### ShortPixel on Bricks behind Cloudflare: use `deliverWebp=1` (Global `<picture>`) and leave SPIO's own CDN off
**Symptom / When:** WebP/AVIF generate but aren't delivered to Bricks-rendered images, or images get mis-cached at the CF edge.
**Why:** Three interacting facts. The `.htaccess`/Accept-header delivery mode relies on `Vary`, which Cloudflare mis-caches. Bricks renders images **outside `the_content`**, so the WP-hooks mode misses them entirely. And SPIO's own CDN (`useCDN`) rewrites image URLs off the CF zone — redundant, and it loses edge caching + WAF.
**Fix:** `spio_settings`: `deliverWebp=1` (Altered/**Global** output-buffer → rewrites the whole page to `<picture>`), `createWebp/createAvif=1`, `useCDN=false`. Verify: the page emits `<picture>` with `image/avif` + `image/webp` sources, and image URLs stay on-domain (CF edge-caches them, MISS→HIT).
**First seen:** TAB, 2026-06-28 — Global picture-rewrite was the only mode that both caught Bricks images and stayed CF-safe.

### Perfmatters RUCSS (Remove Unused CSS, async) regresses LCP on an already-lean Bricks site
**Symptom / When:** Enabling "Remove Unused CSS" (async) makes mobile LCP **worse** on most templates, even though TTFB and CLS are fine.
**Why:** Async inlines the used-CSS then loads full stylesheets asynchronously. On a lean site (small render-blocking CSS, fast TTFB) the async full-CSS lands **after** first paint and pushes the LCP element's final styling later — you're paying async's cost without having the problem it solves. It also **strips classes injected at runtime by JS**.
**Fix:** On a lean Bricks+ACSS site RUCSS usually isn't worth it — measure per template and revert if net-negative. Keep `minify_css/js` regardless (safe win). If you do keep RUCSS, exclude: **any CSS whose classes are added at runtime** (scroll-reveal/animation stylesheets — otherwise elements stay permanently hidden), form CSS, and the conversion pages.
**First seen:** TAB, 2026-06-28 — RUCSS regressed LCP on 3 of 4 templates (1.8→3.4s, 2.9→4.8s); reverted, kept minify.

### PSI lab (mobile) is unreliable under load — confirm with desktop + objective metrics + an A/B before "fixing" a regression
**Symptom / When:** PSI mobile shows a large perf/LCP regression (88→69, LCP 5–6s) with "Something went wrong" errors and big run-to-run swings — while objective signals say the site is fast (TTFB ~40ms, LCP images 0ms, all assets 200, CLS ~0).
**Why:** PSI's 4× mobile CPU throttle plus transient infra issues produce inflated, noisy LCP that resource timing doesn't corroborate. Easy to misdiagnose and then "fix" something that was never broken — the fix is the risk here, not the symptom.
**Fix:** Cross-check with **desktop** PSI (low throttle) + objective metrics, and run an **A/B** (toggle the suspected cause) before acting. Desktop green + A/B doesn't move LCP ⇒ lab noise → trust **CrUX field data** (GSC, ~28 days).
**First seen:** TAB, 2026-06-28 — a post-cache mobile read tanked to ~70/LCP 5–6s; desktop was 96/99 (LCP <1.5s) and an animations on/off A/B left LCP unchanged, confirming a lab artifact.

### Perfmatters — a list option written as a STRING via CLI = sitewide 500s, with the full page body
**Symptom / When:** Pages return HTTP 500 but render the **complete** page body (right down to `</html>`). Only *some* pages 500 — those where Remove Unused CSS needs to regenerate — so pages with a warm RUCSS cache still return 200 and the breakage **spreads gradually** as caches expire. Nothing in the nginx error log.
**Why:** Perfmatters processes the page in an **output-buffer callback at `shutdown`** (`Buffer::process` → `CSS::optimize`). Its list-type options (`rucss_excluded_selectors`, `rucss_excluded_stylesheets`, …) are **arrays** — the settings UI explodes textarea input into arrays on save. Setting one to a plain string via `wp option patch` / `wp eval` makes `array_merge($defaults, $string)` throw a TypeError *inside the ob callback*: the buffered HTML has already been generated (so the full page ships) but headers haven't been sent (so PHP sets 500). **Wrong status + right body = the fatal-in-ob-callback signature.**
**Fix:** Re-save the option as an array, then opcache reset + cache purge:
```php
$o = get_option( 'perfmatters_options' );
$o['assets']['rucss_excluded_selectors'] = [ '.brx-a11y-hidden' ];
update_option( 'perfmatters_options', $o );
```
**Diagnosis playbook — full body + 500 (generalizes to ANY ob-callback fatal):**
1. Hook the `status_header` filter — if it never fires, the 500 isn't being set via WP.
2. Log `http_response_code()` at hooks through `shutdown` (priority `PHP_INT_MIN` and `PHP_INT_MAX`) — if the `PHP_INT_MAX` checkpoint never logs, a fatal occurred during `shutdown`.
3. `ini_set('log_errors','1'); ini_set('error_log', WP_CONTENT_DIR.'/trace.log');` in a temporary mu-plugin gated by a query arg — captures the fatal FPM otherwise swallows.
4. Remember opcache (`validate_timestamps=0`) — reset it before each retest, or you're testing stale code. See `04`.
**General rule:** when writing any plugin option from CLI, **read it first and preserve its shape.** A string where an array is expected is a silent, delayed, sitewide outage.
**First seen:** NLTA, 2026-07-06 — `rucss_excluded_selectors` saved as a bare string; every single-post page 500'd for ~35 min (Googlebot included) while cached pages masked it.

### Perfmatters — Delay JS *and* Defer JS EACH independently kill the WP media modal
**Symptom / When:** On any front-end page with a WP uploader (an `acf_form()` image/gallery field), "Add Image" buttons and drag-drop do nothing. Console shows two distinct failure modes: `media-views` crashing on a missing `wp.i18n`, and `acf is not defined` / `tinymce is not defined` from inline scripts.
**Why:** Two separate ordering breaks in one dependency chain — **fixing one is not enough:**
1. **Delay JS** with `/wp-includes/js/dist/` in its inclusions holds `wp-i18n`/`wp-hooks` until user interaction, but deferred `media-views.js` needs `wp.i18n` at DOM-ready → throws, and `wp.media` never initializes.
2. **Defer JS** defers the external `acf-input.min.js` / `editor.min.js` / TinyMCE files while their **inline companion scripts** (`acf.data = …`, `*-js-after` bootstraps) still run immediately → `acf`/`tinymce` undefined; the modal later dies in `acf.getMimeType` because `acf.data`'s mime map never loaded.
**Diagnosis:** grep the page HTML for `pmdelayedscript` vs `defer` on `dist/i18n`, `acf-input`, `media-views`.
**Fix — disable both, per page, from the site plugin (keeps the optimizations sitewide):**
```php
$off = fn( $on ) => is_page( 'submit-form-slug' ) ? false : $on;
add_filter( 'perfmatters_delay_js', $off );
add_filter( 'perfmatters_defer_js', $off );
```
**First seen:** NLTA, 2026-07-06 — a front-end submission form's Add Image was dead; the same config already carried a `ws-form` delay exclusion for the same failure class.

### ShortPixel — WebP/AVIF images don't render in Outlook desktop, and there's no JPEG to fall back to
**Symptom / When:** Images are blank or broken boxes in Outlook desktop (Windows) email, but fine in Gmail and Apple Mail. Site media is served as `.webp` / `.avif`.
**Why:** Outlook desktop uses the Word rendering engine, which can't display WebP/AVIF. Worse, ShortPixel's CDN delivery (`spcdn.shortpixel.ai/spio/…,to_auto,s_webp:avif/…`) serves WebP **even to clients that never send `Accept: image/webp`** when the origin file is itself `.webp` + `to_auto` — there is no JPEG fallback. ShortPixel only optimizes *toward* next-gen formats; `to_jpg` against a webp origin just 307-redirects back to the webp. **So no JPEG exists anywhere to link to.**
**Fix:** Transcode to JPEG server-side and serve it directly:
1. GD (`imagecreatefromwebp()` → flatten onto white → `imagejpeg()`), cached under `uploads/<prefix>-email/`, regenerated only when the source is newer. (Check `function_exists('imagecreatefromwebp')` — Imagick was absent on this box; GD had WebP read support.)
2. **Exclude that cache path from ShortPixel** so it can't re-optimize and flip it back: append to `wpSPIO()->settings()->excludePatterns` → `['type'=>'path','value'=>'<prefix>-email','apply'=>'all','validated'=>true]`.
3. Safety net: filter the campaign content late (priority 999) to strip any `spcdn.shortpixel.ai/spio/<dir>/` wrapper off the JPEG URLs, so a CDN rewrite can't undo the fix.
**Verify:** `curl -s -o /dev/null -D - -H "Accept: image/png,image/*;q=0.8" "<url>" | grep -i content-type` — must return `image/jpeg`, not `image/webp`.
**First seen:** NLTA, 2026-06-16 — roster images in an email campaign broke in Outlook.
**See also** the ShortPixel entry at the top of this section — leaving SPIO's own CDN off (in favour of `deliverWebp=1`) sidesteps the no-JPEG-fallback problem at source.

## Email delivery

### Mailster/Mailgun — `mailgun_track` overrides the Mailgun DASHBOARD toggle and breaks SSL on the tracking subdomain
**Symptom / When:** Recipients hit an SSL/cert error clicking links in a campaign. The broken URL is a Mailgun click-tracking redirect (`https://email.mg.<domain>/c/…`) — **even though the Mailgun dashboard shows Click/Open tracking "Off."**
**Why:** Mailster's Mailgun add-on stores a `mailgun_track` option (empty, `opens`, `clicks`, `opens,clicks`). When set, Mailster passes `o:tracking-opens=yes` / `o:tracking-clicks=yes` **per message** — and the per-message API flags **override the domain-level dashboard toggle**. Mailgun then rewrites links through the tracking hostname; HTTPS was never enabled for it *because the dashboard toggle is off*, so there's no LE cert → a cert error for every recipient. Compounding: Mailster also rewrites through the WordPress domain, so links get **double-wrapped** and the broken HTTPS host ends up outermost.
**Diagnosis:** `wp option get mailster_options --format=json` → inspect `mailgun_track`.
**Fix (default — keep Mailster-side tracking only):** `wp option patch update mailster_options mailgun_track ""`. Mailster's own tracking rides the working WordPress domain and is what feeds its campaign reports anyway.
**Only if the client actually wants Mailgun analytics:** enable HTTPS on the tracking domain in the Mailgun dashboard; confirm the `email.mg` CNAME is **DNS-only / grey cloud** in Cloudflare (Mailgun cannot provision LE certs through CF's proxy — same class as the Cloudflare entry above); wait up to ~24h for the cert; then disable Mailster's own tracking to avoid double-counting.
**First seen:** NLTA, 2026-04-29 — client reported an SSL error clicking a campaign link.

## Secrets

### Shared API secrets live in `~/.env`, OUTSIDE every web root
**Symptom / When:** You need somewhere to put Cloudflare/Mailgun/etc. credentials on the box, and the obvious spot — next to the project — is the dangerous one.
**Why:** Webapp roots (`/home/runcloud/webapps/app-*/`) are **served directly**, and on the hybrid stack a world-readable file there is one URL away from disclosure regardless of `.htaccess` — see the static-file entry at the top of this file. `~/.env` sits in the home dir, chmod 600, outside all of them.
**Fix / convention:**
- Load with `set -a; . ~/.env; set +a`
- Add new shared creds **there**, rather than scattering them per-project.
- **Inject values without echoing them to the transcript** — `read -rs` into a `sed` replace.
- Token scopes are deliberately narrow, so a call can fail on permissions while the token still verifies as active. Check the scope before concluding the API is broken.
**First seen:** 2026-06-07.

---

# === PROJECT SECTION — "we learned" ===

*Empty at kickoff. New hosting/cutover/performance gotchas discovered during this project append below, in the entry format above. At go-live these are reviewed and the validated ones fold into the established section of the master.*
