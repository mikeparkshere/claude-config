# Global preferences

## Working style
Treat me as a competent senior developer (15+ years WordPress, frontend, SEO).
I'm self-taught on sysadmin/infrastructure — be confident there, don't push verification work back on me.
Default to doing the task rather than asking permission for routine choices.
Skip generic caveats ("be sure to test", "make a backup first"). I know.
Flag genuine risk; otherwise proceed.

## Language and voice
US English everywhere: chat, code comments, commit messages, docs, KB files, and client copy.
Register is plain-spoken Midwestern (Ohio): friendly, direct, practical, no airs. Say what it does and
what it costs. Understatement over hype. The regional flavor is tone, not slang or folksy gimmicks.
- Spelling: -ize/-yze (organize, optimize, analyze), -or (color, behavior), -er (center, meter),
  -se nouns (license, defense), single l (canceled, traveled, labeled, modeling), gray, program,
  catalog, aluminum, check (payment), tire, curb.
- Words: toward, among, while, learned, spelled (not towards, amongst, whilst, learnt, spelt).
  Fall, apartment, parking lot, cell phone, ZIP code, period (punctuation), "on the weekend."
- Collective nouns take singular verbs: "the team is," "the client has."
- Punctuation and formats: double quotes, with periods and commas inside the closing quote.
  Dates as October 3, 2026 or 10/03/2026. US units and currency (miles, °F, lb, $).
- Leave alone: proper nouns, product names, quoted text, code identifiers and third-party API params,
  and copy from a client who writes in British English (match the client).
- When editing existing text, fix British spellings in what you touch. In client-approved copy,
  flag British usage to Michael rather than rewriting it silently.

## Scope — which of the below applies where
This file has two layers, and they travel differently.
**Stack layer** (Stack, and the build conventions in `mpd-bricks-stack`) — portable. Applies to any
project on this stack, whoever hosts it.
**Ops layer** (RunCloud API, Cron, `wordpress-runcloud`) — **specific to the RunCloud-managed
DigitalOcean fleet.** On a bare, client-owned, or otherwise non-RunCloud box these are *inapplicable*,
not conventions to be satisfied — there is no panel, no RunCloud API, no fleet cron pattern to match.
The Cron section in particular describes `jbm001`'s workload, not a rule for every WordPress install.
⚠️ Added 2026-08-04 after the ops layer was read as universal on a bare dedicated box (Nametank /
ded3732) and a cron mandate was invented that never existed. When a house convention seems to apply
to an unfamiliar environment, establish which layer it belongs to first.

## Stack
WordPress, Bricks Builder, ACSS v3, ACF Pro (registered via PHP, never UI), 
WS Forms Pro, RankMath Pro, Perfmatters. Custom plugins, no logic in functions.php.
Servers: DigitalOcean managed via RunCloud — **the default, not the only case**; client-owned and bare
boxes happen, and the Ops layer above does not follow onto them. Dev via VSCode Remote-SSH.

## RunCloud API
**There is no single fleet convention. Resolve the path per box; do not assume.** As surveyed
2026-07-30: `~/.runcloud/token` (bare JWT, mode 600) on 8 of 9 servers — the fleet reality (9 of 10 since mmhn26, 2026-10-01) — and
`~/.runcloud-token` (the `export RUNCLOUD_API_TOKEN=` form) on mpd2026 only, provisioned 2026-07-28.
A survey of that one box was mistaken for doctrine and written here in `fd68161`; it was wrong for
eight of nine servers. **Convergence is deferred, not decided** — pick a direction in a session
dedicated to it, not mid-rollout. Per-box table + full rationale: `server-provisioning.md` step 8
(**private repo** `mikeparkshere/server-provisioning` — it is a fleet map, so it is not in this public repo),
which is authoritative for the layout; keep this section in step with it.
**The token lives only in its token file — never in `~/.env`** or any other multi-secret env file (Michael,
2026-10-01). One copy per box means one place to rotate and one place to check; a second copy in `.env`
is how a box ends up with a live token in one file and a dead one in another. Rotations go straight to
the token file (scp from the Mac), not through `.env`. Removed from `~/.env` on jbm003 and mmhn26 the same day, after confirming nothing
read it from there.

Read it, don't source it — and never echo the value:
```bash
T=$(tr -d '\n' < ~/.runcloud/token)                            # 9 boxes
set -a; . ~/.runcloud-token; set +a; T="$RUNCLOUD_API_TOKEN"   # mpd2026 (export form)
```
Reading into `$T` fails loudly on a missing file; `source` fails **silently** and leaves whatever was
already in the environment.
⚠️ **Never trust a pre-exported `$RUNCLOUD_API_TOKEN`.** Until 2026-10-01 jbm003 sourced `~/.runcloud-api.env`
from `~/.bashrc` *below* the interactive bail, so every interactive shell — and every CC session —
carried a **stale** token, invisible to a non-interactive `ssh jbm003 'echo $RUNCLOUD_API_TOKEN'`.
✅ **Resolved 2026-10-01:** the `.bashrc` line and `~/.runcloud-api.env` (a dead credential, 401) are both
deleted; fresh shells now get no variable at all. A session started *before* the removal still inherits
the old value until it exits — another reason to always resolve from the file on disk.
**A single 401 is not proof of expiry.** A valid and a stale token are both ~235-char JWTs, so length
distinguishes nothing. Before asking Michael to rotate: `sha256sum` each copy present on the box and
`curl` each against the API. A 401 from a stale environment copy while the file on disk is valid is the
expected failure here, not an exception — that is exactly the jbm003 result on 2026-08-04 (disk token
`200`, env-file token `401`). Normal state, not a fault.
**But tokens do expire, and then the 401 is real.** 2026-08-11: a box's `~/.runcloud/token` returned 401,
and it was the *only* copy present — the two alternate paths absent, `~/.env` carrying none — so no
stale-copy explanation was available. Rotated at manage.runcloud.io → Settings → API Key; the
replacement verified `200`. The rule above is unchanged: enumerate every copy *first*. It is the
**absence** of a second copy that makes a single 401 conclusive, not the 401 itself.
**Token scope is workspace-level — resolved 2026-08-11.** A token read from one box's
`~/.runcloud/token` returned **every server in the workspace**, not just its own. The earlier
"workspace-level, reaches every server" claim was right and is now verified rather than assumed.
**Blast radius is fleet-wide** — one leaked token is every server, not one box: mode 600, `scp` from the
Mac only, never pasted through a chat session, never committed.
Base: `https://manage.runcloud.io/api/v3`
Auth: `Authorization: Bearer $T` (the token you resolved from a file above — not the exported var)
Use the API first for any RunCloud task. SSH only for files or things the API doesn't expose.
**The API cannot set nginx config** — probed 2026-08-11, nine plausible paths (`/settings/nginx`,
`/nginx`, `/nginxconfig`, `/nginx-config`, `/settings/nginx-config`, `/customconfig`, `/custom-config`,
`/rewrite`, `/rewrites`) all 404, and `/webapps/{w}/settings` answers `OPTIONS` with `GET,HEAD` — it is
read-only despite a body that lists `disableFunctions`, `memoryLimit`, `openBasedir` and the security
toggles as if they were writable.
✅ **But READ it — that body carries the PHP-FPM process-manager config, and it is the fastest way to
diagnose 502/504s on any box.** `GET /webapps/{w}/settings` returns `processManager`,
`processManagerStartServers`, `processManagerMinSpareServers`, `processManagerMaxSpareServers`,
**`processManagerMaxChildren`**, `processManagerMaxRequests` and `memoryLimit`. The pool conf itself
lives in `/etc/php8*rc/fpm.d/`, which is **root-only and unreadable as `runcloud`** — so without this
endpoint FPM tuning looks un-inspectable from a normal session. It isn't.
**RunCloud's stock default is `dynamic` / maxChildren 5 / start 1 / minSpare 1 / maxSpare 1 on every
webapp**, and it is easy to never notice it has not been tuned. Found 2026-08-11: a box where every
webapp still carried that default, ~64 MB measured per worker, and the **five-worker ceiling** was the
best explanation for months of intermittent 504s across several sites — while the box had most of its
RAM free the entire time. **Worker starvation reads exactly like RAM starvation and takes the opposite
fix; check `maxChildren` before ever recommending a resize.** `maxSpare: 1` compounds it — FPM keeps one
idle worker, so every burst starts cold. Note the failure tracks *concurrency*, not request volume: a
tight burst exhausts a 5-worker pool while many times that number spread over hours does nothing, so a
volume-vs-errors correlation will look like noise and can wrongly exonerate the pool.
Confirm with FPM's own `server reached pm.max_children setting` log line (root-only). Changing the
values *afterwards*: `PATCH /servers/{id}/webapps/{w}/settings/fpmnginx` is the documented writable editor
(verified for `openBasedir`, MMHN 2026-07-21; **untested** for the `processManager*` fields), so for those the
panel stays the safe route — webapp → Settings; the form mirrors these field names one-to-one.
✅ **But set them at creation through the API** (verified 2026-10-01, MMHN): `POST /servers/{id}/webapps/custom`
accepts the same `processManager*` / `memoryLimit` / `maxExecutionTime` / `timezone` fields and applies them, so
a new webapp never has to ship on the 5-worker default. Read back `/settings` to confirm.
⛔ **But never use `/webapps/custom` for a WordPress site** (found 2026-10-02, MMHN). It creates a webapp of
type `custom`, and RunCloud's WordPress toolkit is keyed on type: `GET …/webapps/{w}/runcache` → 403 *"Web
Application need to be set to WordPress to use this function"*, so **no RunCache, no page cache**. The webapp
resource is `GET,HEAD,DELETE` only, so the type can't be changed by API afterwards; the only fixes are a RunCloud
support ticket or recreating the webapp. Create WordPress sites with `/webapps/wordpress`. Whether that endpoint
also accepts the `processManager*` fields is **unverified**: try them, read back `/settings`, and if they didn't
apply, set FPM in the panel before the site takes traffic. Check `"type"` with a GET the day a webapp is created.
⚠️ **New server: set the CLI PHP too** — RunCloud starts `php` (what WP-CLI runs) on the *lowest* installed
version regardless of the webapp's FPM version. `PATCH /servers/{id}/php/cli` `{"phpVersion":"php84rc"}`, then
verify `wp eval 'echo PHP_VERSION;'` (mmhn26 ran WP-CLI on 8.1 until caught, 2026-10-01).
⚠️ **firewalld can be silently down after a reboot — check it, don't assume it** (found 2026-10-07). jbm003
ran ~145 days with firewalld `inactive` after a May reboot; agents before **2.20.0** never check it. Read it as
`runcloud` with `systemctl is-active firewalld` (no root). Useful read-only endpoints for fleet sweeps:
`GET /servers/{id}/logs` (activity log — the firewalld alert and the `Auto Healing restarted the Firewall service`
line both land here), `GET /servers/{id}/hardwareinfo` (`uptime`), `GET /servers/{id}` (`agentVersion`).
`PUT /servers/{id}/security/firewalls` re-deploys the rule set but does **not** start a stopped firewalld.
2.20's auto-heal (30 min) is real but not guaranteed — it never fired on jbm003. SSH "Connection refused" from an
outside IP on these boxes is usually **fail2ban** (sshd jail, iptables REJECT, 10 h ban), not the firewall.
Full record: `server-provisioning.md` (private repo).
⚠️ **Before booking panel time for a server-level directive, check the webapp's `stack`.** A `hybrid`
webapp is nginx → Apache → FPM and **honors `.htaccess`**, so Apache directives work with no root, no
sudo and no panel — every webapp checked so far is hybrid, WordPress and static alike. This is easy to
miss because the boxes read as nginx-only. Prove it in a scratch dir before relying on it (drop a
`<FilesMatch>` deny, curl the file, expect 200 → 403), put the block **after `# END WordPress`** on WP
sites since WP overwrites its own markers, and verify with `?cb=$RANDOM` — `x-runcloud-cache` serves
pre-change copies and will show a 200 over a rule that is already working, while Cloudflare reports
`DYNAMIC` and looks innocent. A security rule "verified" without a cache-buster can be recorded as fixed
over a still-live exposure. A belief that `.htaccess` was inert here had already cost one project a PHP
redirect layer it never needed.
⚠️ **But only for requests that reach Apache.** nginx serves common static extensions itself and never
reads `.htaccess` for them: `.html`, `.txt`, `.css`, `.js`, images and **`.zip`** (MMHN 2026-10-08: a
`Require all denied` blocked a `.log` 403 while the `.zip` beside it stayed 200). So `.htaccess` cannot
protect backup archives or static docs. Use file mode (`600`) or a panel NGINX rule
(`location ^~ /path/ { return 404; }`). Full matrix: KB `04`, the hybrid static-files entry.

## Cron (server jbm001, all 11 webapps)
WP-Cron disabled site-side (`DISABLE_WP_CRON=true` in every wp-config.php). Driven by the
`runcloud` **user crontab** (hand-rolled, not RunCloud-managed — deliberate: identical reliability,
keeps fine stagger control + inline comments). Frequency tiered to workload (retier 2026-06-30):
- **1 min** — app-lwv, app-mbc25 (live WooCommerce / Action Scheduler; lwv also Mailster).
- **5 min** — app-ahml (production), app-fmed (heaviest event load).
- **10 min** — bbbrave, fmnb25, holdingspace, mediof, morristow, expert-t, app-highland (brochure).

Command form: `/usr/local/bin/wp cron event run --due-now --quiet --path=<webapproot>` (no `cd`).
Once a tier runs out of distinct minutes, a site shares one with a `sleep <n>;` sub-offset ahead of the
`wp` call — app-highland is the first to do this (digit 3, `sleep 30`). Keep that form when adding more.
Stderr → `~/cron-logs/<app>.log` (empty on success; growth = a real recurring error).
Crontab backup: `~/cron-handrolled.bak`. Restore: `crontab ~/cron-handrolled.bak`.
⚠️ **Refresh the backup in the same edit that changes the crontab.** On 2026-08-04 it was found 5 weeks
stale and missing app-highland entirely — restoring from it would have silently killed cron on a live
production site (WP-Cron is disabled site-side, so nothing would have picked up the slack). Verify with
`diff <(crontab -l) ~/cron-handrolled.bak`.
New webapp → add `DISABLE_WP_CRON` + a crontab line in the matching tier.
**parkshere2022 / 238586 uses a different mechanism, deliberately (decided 2026-09-10):** RunCloud-managed cron
jobs by API (`/etc/cron.d/runcloud-<user>`, `php<ver>rc … wp-cron.php`, `*/10` staggered by minute digit, Woo sites
`*/5`), not a hand-rolled crontab — two system users, panel visibility and RAM headroom all favor it. Adopt the
jbm001 *ideas* there, not the file: stderr → `~/cron-logs/<app>.log` (Clemente jobs 200747/200748 set the pattern; Punch 187333 retrofitted 2026-10-06 by PATCH, which needs the full job body;
the other 13 still discard stderr — retrofit is an open item) and tier only where a queue actually lags.

## Project conventions
Three-tier classification: Tier 1 active builds, Tier 2 live Claude-assisted, Tier 3 legacy.
CLAUDE.md is the source of truth and the flow to Notion is **one-way**, **on request** — after a closer, or every few sessions. Notion is a published view for reading and sharing; never edit a mirrored page directly, since an edit there is lost at the next write.
**"Push" and "sync" mean the same thing: make Notion match reality.** Both write *two* surfaces — the **`CLAUDE.md` child page** (verbatim copy of the file) **and** the **project page** (curated human layer + Projects/Clients DB properties). Writing only one is how the project page silently rotted from April to July while the technical copy looked current. The older claude.ai-vs-Claude-Code split between the two verbs is retired, **and that retirement binds claude.ai sessions too** — it is not a Claude Code-only rule. The statement both surfaces read is the Notion page *"CLAUDE.md ↔ Notion Sync — Workflow Reference"*; this file and that page must agree, and if they ever disagree, say so rather than picking one silently.
**Always use surgical edits, never whole-page replacement.** The mirrors carry curator-only blocks (e.g. the **User-Level References** table) that exist nowhere on disk; a replace wipes them. Target cell-level / line-level strings, batch the safe edits, isolate the risky ones, and re-fetch afterward to verify.
Writes to **Clients/Projects DB records** (as opposed to doc pages) get confirmed with Michael first — those are CRM data, not documentation.
Slash commands in claude-config repo: /wordpress-audit, /wordpress-runcloud.
