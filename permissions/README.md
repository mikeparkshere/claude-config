# Phase gate — permission tiers

Three project phases, one file each. The doctrine lives in `knowledgebase/00-operating-rules.md`
(read protocol step 3, and "Changing phase mid-session"). This directory is the enforcement layer.

## How the layers stack

Claude Code project settings can only **tighten**, never loosen:

- `defaultMode: "auto"` is ignored from `.claude/settings.json` and `.claude/settings.local.json`
  (v2.1.142+), and the whole `autoMode` block is not read from project settings at all (v2.1.207+).
  A repo cannot grant itself auto mode. By design.
- `defaultMode: "plan"` and `"acceptEdits"` **do** work from project settings and override the
  user-level default.
- `ask` and `deny` rules merge across every scope, and deny/ask beat allow regardless of origin.

So: **user settings are the sprint, project settings are the brake.**

| Layer | File | Carries |
|---|---|---|
| Baseline | `~/.claude/settings.json` | `defaultMode: auto`, `autoMode.environment`, never-ever denies |
| Gate | `<project>/.claude/settings.json` | one of the tier files below |

These tier files are pure restriction (`ask` + `deny` + `defaultMode`), so they apply without a
workspace trust dialog. Only `allow` rules and `additionalDirectories` require trust.

## The tiers

| Phase | File | Effect |
|---|---|---|
| `DEV` | *(none)* | Inherits auto. Sprints. `set-phase.sh` removes any stale file. |
| `STAGING` | `staging.settings.json` | Auto mode, `ask` on twelve destructive WP-CLI forms. |
| `STAGING+TXN` | `staging-txn.settings.json` | As STAGING, plus `deny` on Woo, Action Scheduler and manual cron runs. A staging site pointed at a live gateway can email real customers and fire real webhooks; the scheduler is the thing that does it. |
| `LIVE` | `live.settings.json` | Starts in **plan mode**. Broad `ask`, `deny` on the three irreversible DB commands. |
| `LIVE+TXN` | `live-txn.settings.json` | As LIVE, plus `deny` on options, users, posts, Woo and Action Scheduler. |

Unmarked project → LIVE. Fail closed.

## Choices worth knowing

- **`wp db export` and `wp db query` are deliberately not in STAGING's ask list**, and export is
  absent from LIVE's. Export is the safe one; prompting on it trains you to click through.
- **`Bash(wp db *)` is never used as a single rule** for the same reason. Enumerated forms only.
- **No `curl` argument rules.** Bash patterns that try to constrain arguments are fragile: options
  before the URL, protocol swaps, redirects and variables all slip past. Panel-API restrictions
  belong in `autoMode.soft_deny` prose, where the classifier reads intent rather than string shape.
- **LIVE uses plan mode rather than a slow mode.** One gate per task, not one per action. The plan
  approval prompt offers "Yes, and use auto mode", so the work runs at full speed once agreed.
- **STAGING's brake is light on purpose.** By the phase definition it covers most of a project's
  life; a gate you want to disable is not a gate.

## Baseline

`user.settings.json.example` is a starting point for `~/.claude/settings.json`. Fill the
`REPLACE:` prose lines with real infrastructure. **Do not commit the filled version to this repo —
it is public.** The completed file names hosts, org, and buckets, which makes it a fleet map;
it belongs in the private `server-provisioning` repo alongside the per-box token table.

Keep `"$defaults"` at the head of every `autoMode` array. Each array *replaces* the built-in list
unless `$defaults` is present, so a `soft_deny` without it silently discards the built-in blocks
including `curl | bash` and force-push. Verify with `claude auto-mode config` after any edit.

## Exit codes

`set-phase.sh` in report mode exits **4** when it finds drift, so it works as a pre-flight
check rather than something you have to grep stdout for:

```bash
# every marked project on a box, drift only
find /home/runcloud/webapps -maxdepth 2 -name CLAUDE.md -printf '%h\n' | while read -r d; do
  ~/claude-config/bin/set-phase.sh --dir "$d" >/dev/null 2>&1 || echo "DRIFT: $d"
done
```

`0` clean · `1` missing file or bad template · `2` bad arguments · `3` demotion refused ·
`4` drift.

## Backing it out

Nothing here touches a site, database, content, or server config. The gate only restricts what
Claude Code does without asking, so a bad outcome is unwanted prompts, not damage.

```bash
# before you start, in claude-config:
git tag pre-phase-gate

# to back out later:
git -C ~/claude-config revert --no-edit pre-phase-gate..HEAD
# then on each box: git -C ~/claude-config pull

# remove the gate from every project on a box:
find /home/runcloud/webapps -maxdepth 2 -name CLAUDE.md -printf '%h\n' \
  | xargs -I{} ~/claude-config/bin/set-phase.sh --uninstall --dir {}

# user-level baseline (not tracked here — back it up before you write it):
cp ~/.claude/settings.json ~/.claude/settings.json.pre-gate
```

Hard rule 0 in a project `CLAUDE.md` is inert without the rest. Leave it or delete the line.
