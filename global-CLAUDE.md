# Global preferences

## Working style
Treat me as a competent senior developer (15+ years WordPress, frontend, SEO).
I'm self-taught on sysadmin/infrastructure — be confident there, don't push verification work back on me.
Default to doing the task rather than asking permission for routine choices.
Skip generic caveats ("be sure to test", "make a backup first"). I know.
Flag genuine risk; otherwise proceed.

## Stack
WordPress, Bricks Builder, ACSS v3, ACF Pro (registered via PHP, never UI), 
WS Forms Pro, RankMath Pro, Perfmatters. Custom plugins, no logic in functions.php.
Servers: DigitalOcean managed via RunCloud. Dev via VSCode Remote-SSH.

## RunCloud API
Token: `~/.runcloud/token` (mode 600) — a **bare JWT**: no `export`, no trailing newline. `cat` it into a
var; do **not** try to source it: `TOK=$(cat ~/.runcloud/token)`. Never echo the value.
Workspace-level token (scope = every server in the workspace, not just the local box).
Base: `https://manage.runcloud.io/api/v3`
Auth: `Authorization: Bearer $TOK`
Use the API first for any RunCloud task. SSH only for files or things the API doesn't expose.
⚠️ Corrected 2026-08-04 — the old path was wrong and cost a session real time. Two dead credential
files remain and both fail: `~/.runcloud-token` (gone entirely) and `~/.runcloud-api.env` (a valid
`export` file, but its token now returns **401**). Neither is the live credential.

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

## Project conventions
Three-tier classification: Tier 1 active builds, Tier 2 live Claude-assisted, Tier 3 legacy.
CLAUDE.md is the source of truth. CC pushes it to Notion **on request** — after a closer, or every few sessions — and the sync is **one-way**. Notion is a published view for reading and sharing; never edit it directly, since an edit there is lost at the next push.
Slash commands in claude-config repo: /wordpress-audit, /wordpress-runcloud.