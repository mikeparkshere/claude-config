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

Keys are configured per environment (established during the April 2026 8-environment rollout), always via the dashboard route above. `authorized_keys` is never hand-edited.

## 2. GitHub authentication

Two separate things. This checklist historically covered only the second, which is a gap: a box can pass `gh auth status` and still fail `git pull`, and `git pull` is step 0 of every slash command.

**2a. The server's own SSH key, registered on your GitHub account.** This is what `git clone` and `git pull` use.

```bash
ls ~/.ssh/id_ed25519.pub || ssh-keygen -t ed25519 -C "{alias}"
cat ~/.ssh/id_ed25519.pub    # add at GitHub → Settings → SSH and GPG keys
```

Verify:

```bash
ssh -T git@github.com
```

Success looks like `Hi mikeparkshere! You've successfully authenticated, but GitHub does not provide shell access.` and a **non-zero exit** — GitHub grants no shell, so the greeting is the success signal, not the exit code. Being greeted by *account* name confirms an account-level key rather than a per-repo deploy key.

**2b. The `gh` CLI.** Separate, and **not** required for git. CC needs it for autonomous repo work (creating repos, PRs). Required on every box — any server may eventually host a new project.

```bash
gh auth login    # device-code flow
gh auth status   # verify
```

Confirmed independent 2026-07-24: devmpdesign authenticates to GitHub over SSH and fetches cleanly with **no `gh` installed at all**. Never read `gh auth status` as evidence that git will work, or the reverse.

## 3. Clone the repo

```bash
git clone git@github.com:mikeparkshere/claude-config.git ~/claude-config
```

Home directory of the `runcloud` user, always — every playbook and slash command assumes `~/claude-config` resolves.

## 4. Install Claude Code

Native installer. No Node.js prerequisite — the binary is self-contained — and it auto-updates in the background.

```bash
curl -fsSL https://claude.ai/install.sh | bash
claude --version
claude doctor      # reports install type and auto-update status
```

The binary lands at `~/.local/bin/claude`, symlinked to `~/.local/share/claude/versions/<version>`. If `claude` is not found afterward, `~/.local/bin` is missing from PATH — check with a **login** shell, since a non-login shell won't have it.

npm global installs are the legacy path (Anthropic deprecated them January 2026) and are not used here — `/usr/lib/node_modules` was empty on every box checked. The system `node` version is irrelevant to Claude Code itself; it matters only for skill scripts that shell out to it.

**On version skew:** auto-update converges the fleet without anyone managing it — jbm003 and devmpdesign both sat at 2.1.218 on 2026-07-24. Still note the version when a box misbehaves; "same CC version as the Mac?" remains an early question (learned 2026-07-18, the jbm003 staging investigation).

## 5. Wire the global CLAUDE.md

`global-CLAUDE.md` in this repo is the master. It must become `~/.claude/CLAUDE.md` on the server — **as a symlink, not a copy**, so `git pull` updates it without a second sync step:

```bash
mkdir -p ~/.claude
ln -sf ~/claude-config/global-CLAUDE.md ~/.claude/CLAUDE.md
```

Confirmed symlink, not a copy, on jbm003 and devmpdesign (2026-07-24). A copy would be the same trap as a stale clone.

## 6. Wire the slash commands

The playbooks at the repo root are the slash commands. CC reads them from `~/.claude/commands/`:

```bash
mkdir -p ~/.claude/commands
ln -sf ~/claude-config/wordpress-runcloud.md ~/.claude/commands/wordpress-runcloud.md
ln -sf ~/claude-config/wordpress-audit.md ~/.claude/commands/wordpress-audit.md
```

Symlinks are the confirmed fleet convention — the April 2026 rollout converted all local command copies to symlinks pointing at the repo, precisely so `git pull` is the only sync step.

Confirmed 2026-07-24: the runcloud set only. `flatsite.md`, `bricks-css-sweep.md`, `wordpress-local.md` and `business-manager-role-playbook.md` are wired on no server. `wordpress-local.md` is Mac-only by design (LocalWP paths). The other three are candidates that have simply never been deployed — wire them deliberately if you want them, rather than assuming they are already live.

## 7. Vendored WP skills

Four skills were selected from an external WordPress agent skills repo (reviewed in claude.ai for workflow relevance): wp-performance, wp-plugin-development, wp-rest-api, wp-wpcli-and-ops.

**Vendored into this repo 2026-07-18** and deployed to jbm003 the same day. **Not yet on every box** — devmpdesign had no `~/.claude/skills/` directory at all when checked on 2026-07-24, so treat this step as outstanding on any server you have not verified. Check with `ls -la ~/.claude/skills/` before assuming.

The skills are already vendored into `skills/` in this repo. To deploy on a box, symlink them:

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
ssh -T git@github.com                        # greets by account name (non-zero exit is normal)
git -C ~/claude-config pull                  # "Already up to date"
ls -la ~/.claude/CLAUDE.md                   # symlink → ~/claude-config/global-CLAUDE.md
ls -la ~/.claude/commands/                   # the wired playbooks, no broken links
ls -la ~/.claude/skills/                     # the four vendored skills
gh auth status                               # authenticated
claude --version                             # native install, auto-updated
cat ~/.runcloud/token | head -c 8            # token present (first chars only)
find ~/.claude -xtype l                      # prints nothing if no symlink is dangling
```

Then the real test: start CC, run `/wordpress-runcloud`, and watch step 0 pull cleanly. If the slash command loads and the webapp listing appears, the box is fleet-standard. First project on the box follows the kickoff protocol in `00` — unharvested-sibling check before the snapshot copy, as always.
