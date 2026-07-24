# Server provisioning — making a fresh box CC-ready

This is the layer *underneath* everything else in this repo. The knowledgebase, the slash commands, the session lifecycle — all of it assumes a server where Claude Code is installed, authenticated, and wired to this repo. That wiring has been done nine times from memory. This file makes it a checklist so server ten is boring.

Run these steps once per new server, in order. After the first session, none of this recurs — step 0 in the slash commands keeps the clone current forever.

**Scope:** this covers the CC layer only. It assumes RunCloud has already provisioned the box (stack, firewall, the `runcloud` user) and that provider-native snapshots are enabled. There is no fleet-standard offsite backup layer, and setting one up is not part of this checklist. Finishing these steps makes a box CC-ready, not backed up.

---

## 1. SSH access from the Mac

Ed25519 key, friendly alias. Add to `~/.ssh/config` **on the Mac**:

```
Host {alias}            # e.g. jbm004, vmg003 — match the fleet naming
    HostName {server-ip}
    User runcloud
    IdentityFile ~/.ssh/id_ed25519
```

The public key goes onto the server via the RunCloud dashboard (server → SSH keys) so it lands in the right `authorized_keys` without hand-editing.

Verify: `ssh {alias}` from the Mac drops you at a `runcloud@` prompt with no password.

Keys are configured per environment (established during the April 2026 8-environment rollout). {CONFIRM: deployed via RunCloud dashboard, or copied manually?}

## 2. GitHub authentication

The clone uses the SSH remote (`git@github.com:mikeparkshere/claude-config.git`), and CC needs `gh` for autonomous repo work. On the server:

```bash
gh auth login    # GitHub CLI — follow the device-code flow
gh auth status   # verify
```

{CONFIRM: does `gh auth login` handle git-over-SSH too (it can configure git credentials), or does each server's SSH key get added to the GitHub account separately? jbm003 pushes over SSH successfully, so one of these is true.}

## 3. Clone the repo

```bash
git clone git@github.com:mikeparkshere/claude-config.git ~/claude-config
```

Home directory of the `runcloud` user, always — every playbook and slash command assumes `~/claude-config` resolves.

## 4. Install Claude Code

```bash
{CONFIRM: exact install command used on the fleet — npm global? native installer? Node version prerequisite?}
claude --version
```

Record the version. Version skew across the fleet muddies debugging — when a server misbehaves, "same CC version as the Mac?" is an early question (learned 2026-07-18, the jbm003 staging investigation).

## 5. Wire the global CLAUDE.md

`global-CLAUDE.md` in this repo is the master. It must become `~/.claude/CLAUDE.md` on the server — **as a symlink, not a copy**, so `git pull` updates it without a second sync step:

```bash
mkdir -p ~/.claude
ln -sf ~/claude-config/global-CLAUDE.md ~/.claude/CLAUDE.md
```

{CONFIRM: symlink is the assumed convention here — if the existing fleet uses copies, either standardize on symlinks or document the copy-on-pull step, because a stale copy is the same trap as a stale clone.}

## 6. Wire the slash commands

The playbooks at the repo root are the slash commands. CC reads them from `~/.claude/commands/`:

```bash
mkdir -p ~/.claude/commands
ln -sf ~/claude-config/wordpress-runcloud.md ~/.claude/commands/wordpress-runcloud.md
ln -sf ~/claude-config/wordpress-audit.md ~/.claude/commands/wordpress-audit.md
```

Symlinks are the confirmed fleet convention — the April 2026 rollout converted all local command copies to symlinks pointing at the repo, precisely so `git pull` is the only sync step.

{CONFIRM: which playbooks are wired as commands on servers — all of them, or just the runcloud set? `wordpress-local.md` presumably stays Mac-only.}

## 7. Vendored WP skills — NOT YET DEPLOYED

Four skills were selected from an external WordPress agent skills repo (reviewed in claude.ai for workflow relevance): wp-performance, wp-plugin-development, wp-rest-api, wp-wpcli-and-ops.

**Status as of 2026-07-18: selected but never installed.** They exist on no server and not in this repo — the evaluation happened, the deployment didn't. Discovered while writing this checklist, which is the checklist doing its job.

When deploying, use the same pattern as everything else: vendor the four skill directories into `skills/` in this repo, then symlink on each box:

```bash
mkdir -p ~/.claude/skills
ln -sf ~/claude-config/skills/wp-performance ~/.claude/skills/wp-performance
ln -sf ~/claude-config/skills/wp-plugin-development ~/.claude/skills/wp-plugin-development
ln -sf ~/claude-config/skills/wp-rest-api ~/.claude/skills/wp-rest-api
ln -sf ~/claude-config/skills/wp-wpcli-and-ops ~/.claude/skills/wp-wpcli-and-ops
```

Vendoring (copying into this repo) beats cloning the upstream repo per-server: the upstream was already pruned 17→4, upstream drift is unwanted, and step 0 then keeps the skills current fleet-wide for free.

**Upstream: `https://github.com/WordPress/agent-skills`** (began under Automattic, now WordPress org). Vendored 2026-07-18 from upstream commit `c212346`; the repo had grown to 18 skills by then. `skills/VENDORED.md` records provenance.

## 8. RunCloud API token

The global CLAUDE.md tells CC to prefer the API over SSH for RunCloud tasks. That requires the token on the box:

```bash
mkdir -p ~/.runcloud
# place the token so that `cat ~/.runcloud/token` returns it
chmod 600 ~/.runcloud/token
```

Transfer the token by `scp` from the Mac — never paste it through a chat session or commit it anywhere in this repo.

## 9. Verification — the box is ready when all of these pass

```bash
ssh {alias}                                  # from Mac: no-password login
git -C ~/claude-config pull                  # "Already up to date"
ls -la ~/.claude/CLAUDE.md                   # symlink → ~/claude-config/global-CLAUDE.md
ls ~/.claude/commands/                       # the wired playbooks
gh auth status                               # authenticated
claude --version                             # matches the fleet
cat ~/.runcloud/token | head -c 8            # token present (first chars only)
```

Then the real test: start CC, run `/wordpress-runcloud`, and watch step 0 pull cleanly. If the slash command loads and the webapp listing appears, the box is fleet-standard. First project on the box follows the kickoff protocol in `00` — unharvested-sibling check before the snapshot copy, as always.
