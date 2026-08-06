# Global preferences

## Working style
Treat me as a competent senior developer (15+ years WordPress, frontend, SEO).
I'm self-taught on sysadmin/infrastructure — be confident there, don't push verification work back on me.
Default to doing the task rather than asking permission for routine choices.
Skip generic caveats ("be sure to test", "make a backup first"). I know.
Flag genuine risk; otherwise proceed.

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
2026-07-30: `~/.runcloud/token` (bare JWT, mode 600) on 8 of 9 servers — the fleet reality — and
`~/.runcloud-token` (the `export RUNCLOUD_API_TOKEN=` form) on mpd2026 only, provisioned 2026-07-28.
A survey of that one box was mistaken for doctrine and written here in `fd68161`; it was wrong for
eight of nine servers. **Convergence is deferred, not decided** — pick a direction in a session
dedicated to it, not mid-rollout. Per-box table + full rationale: `server-provisioning.md` step 8
(**private repo** `mikeparkshere/server-provisioning` — it is a fleet map, so it is not in this public repo),
which is authoritative for the layout; keep this section in step with it.

Read it, don't source it — and never echo the value:
```bash
T=$(tr -d '\n' < ~/.runcloud/token)                            # 8 boxes
set -a; . ~/.runcloud-token; set +a; T="$RUNCLOUD_API_TOKEN"   # mpd2026 (export form)
```
Reading into `$T` fails loudly on a missing file; `source` fails **silently** and leaves whatever was
already in the environment.
⚠️ **Never trust a pre-exported `$RUNCLOUD_API_TOKEN`.** jbm003 sources `~/.runcloud-api.env` from
`~/.bashrc` line 122 — *below* the interactive bail on line 6 — so it populates the variable with a
**stale** value in **interactive shells only**. A non-interactive probe returns unset, so the poisoning
is invisible to `ssh jbm003 'echo $RUNCLOUD_API_TOKEN'` and live in every CC session. That is a
jbm003-local `.bashrc` line, not a fleet convention. Always resolve from the file on disk.
`~/.runcloud-api.env` is a **dead credential** — re-verified 2026-08-04 on jbm003: a valid `export`
file, mode 600, but its token returns **401**. It is not the live credential anywhere; the only reason
it still matters is the `.bashrc` poisoning above. Leaving it in place is the open item.
**A single 401 is not proof of expiry.** A valid and a stale token are both ~235-char JWTs, so length
distinguishes nothing. Before asking Michael to rotate: `sha256sum` each copy present on the box and
`curl` each against the API. A 401 from a stale environment copy while the file on disk is valid is the
expected failure here, not an exception — that is exactly the jbm003 result on 2026-08-04 (disk token
`200`, env-file token `401`). Normal state, not a fault.
Whether tokens are workspace-scoped or per-server is **unresolved** — the earlier "workspace-level,
reaches every server" claim is unverified; don't rely on it. Treat blast radius as unknown: mode 600,
`scp` from the Mac only, never pasted through a chat session, never committed.
Base: `https://manage.runcloud.io/api/v3`
Auth: `Authorization: Bearer $T` (the token you resolved from a file above — not the exported var)
Use the API first for any RunCloud task. SSH only for files or things the API doesn't expose.

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
CLAUDE.md is the source of truth and the flow to Notion is **one-way**, **on request** — after a closer, or every few sessions. Notion is a published view for reading and sharing; never edit a mirrored page directly, since an edit there is lost at the next write.
**"Push" and "sync" mean the same thing: make Notion match reality.** Both write *two* surfaces — the **`CLAUDE.md` child page** (verbatim copy of the file) **and** the **project page** (curated human layer + Projects/Clients DB properties). Writing only one is how the project page silently rotted from April to July while the technical copy looked current. The older claude.ai-vs-Claude-Code split between the two verbs is retired, **and that retirement binds claude.ai sessions too** — it is not a Claude Code-only rule. The statement both surfaces read is the Notion page *"CLAUDE.md ↔ Notion Sync — Workflow Reference"*; this file and that page must agree, and if they ever disagree, say so rather than picking one silently.
**Always use surgical edits, never whole-page replacement.** The mirrors carry curator-only blocks (e.g. the **User-Level References** table) that exist nowhere on disk; a replace wipes them. Target cell-level / line-level strings, batch the safe edits, isolate the risky ones, and re-fetch afterward to verify.
Writes to **Clients/Projects DB records** (as opposed to doc pages) get confirmed with Michael first — those are CRM data, not documentation.
Slash commands in claude-config repo: /wordpress-audit, /wordpress-runcloud.
