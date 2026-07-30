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

Install is a user-local binary drop from the official release tarball. Check the current release at `https://github.com/cli/cli/releases`, then substitute it below:

```bash
cd /tmp
curl -fsSLO https://github.com/cli/cli/releases/download/v<VERSION>/gh_<VERSION>_linux_amd64.tar.gz
tar xzf gh_<VERSION>_linux_amd64.tar.gz
mkdir -p ~/.local/bin
cp gh_<VERSION>_linux_amd64/bin/gh ~/.local/bin/gh
chmod +x ~/.local/bin/gh
rm -rf /tmp/gh_<VERSION>_linux_amd64*
gh --version
```

Take the current release rather than matching another box — see the drift note below. Then authenticate:

```bash
gh auth login    # device-code flow; you complete it in a browser
gh auth status   # verify
```

**Two things about this install method.** It is **not** forced by a lack of sudo — the `runcloud` user has passwordless sudo (verified on devmpdesign, 2026-07-24), so GitHub's apt repo is available if ever wanted. The binary drop is simply what the fleet already runs. And unlike `claude`, a dropped binary **never auto-updates**, so `gh` versions drift (jbm003 on 2.93.0, devmpdesign on 2.96.0 as of 2026-07-24). That is accepted rather than a defect: `gh`'s repo and PR surface is stable across minor versions, and pinning a fleet-wide version that nothing maintains is false consistency. If drift ever does bite, the apt repo is the fix.

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

The binary lands at `~/.local/bin/claude`, symlinked to `~/.local/share/claude/versions/<version>`. If `claude` is not found afterward, `~/.local/bin` is missing from PATH.

**PATH in non-login shells.** Ubuntu puts the `~/.local/bin` line in `~/.profile`, which only login shells read. Two things then break: `ssh {alias} 'claude --version'` (a non-interactive remote command) and anything CC shells out to. `~/.bashrc` looks like the fix, but Ubuntu's stock `~/.bashrc` bails on line 6 with `case $- in *i*) ;; *) return;; esac`, so a line appended to the bottom never runs non-interactively. The line has to go **above** that bail:

```bash
grep -q 'claude-config PATH' ~/.bashrc || \
  sed -i '1i export PATH="$HOME/.local/bin:$PATH"  # claude-config PATH' ~/.bashrc
```

Verify from the Mac, not from a shell on the box — an interactive session is the one case that already worked:

```bash
ssh {alias} 'claude --version; gh --version'
```

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

The runcloud set above is the fleet floor — every box gets it. Beyond that the fleet has **roles, not just drift**. Some boxes carry `flatsite.md` plus Cloudflare / PageSpeed / Google credentials; the Bricks build boxes carry neither and do not need either. A box missing `flatsite.md` is not an incomplete box, it is a box that does not do flatsite work.

So: audit the floor, report the role layer. Never "fix" a role difference by wiring a playbook onto a box whose job doesn't call for it — that spreads credential surface for nothing.

`wordpress-local.md` is Mac-only by design (LocalWP paths). `bricks-css-sweep.md` and `business-manager-role-playbook.md` are wired on no server as of 2026-07-24 — candidates that have never been deployed, not gaps.

## 7. Skills

Five skills live in `skills/`. Four are vendored from an external WordPress agent skills repo (reviewed in claude.ai for workflow relevance): wp-performance, wp-plugin-development, wp-rest-api, wp-wpcli-and-ops. The fifth, `mpd-bricks-stack`, is first-party — authored here, not vendored, and outside the upstream-drift reasoning below.

**Vendored into this repo 2026-07-18** and deployed to jbm003 the same day. devmpdesign had no `~/.claude/skills/` directory when first checked on 2026-07-24 and was wired the same evening. Confirmed complete on jbm003, devmpdesign and mpd2026 as of 2026-07-30 — five links each. Still treat this step as outstanding on any server you have not verified: `mkdir -p` is required, and without it the `ln` below fails outright rather than degrading.

To deploy on a box, symlink all five:

```bash
mkdir -p ~/.claude/skills
for s in wp-performance wp-plugin-development wp-rest-api wp-wpcli-and-ops mpd-bricks-stack; do
  ln -sfn ~/claude-config/skills/"$s" ~/.claude/skills/"$s"
done
```

**`-n` is load-bearing.** These targets are directories. If `~/.claude/skills/wp-performance` already exists as a symlink to a directory, plain `ln -sf` dereferences it and creates the new link *inside* it — `~/claude-config/skills/wp-performance/wp-performance`, a self-referential link written into the repo working tree. It doesn't error, it doesn't dangle, and `find -xtype l` won't catch it because it resolves fine. It shows up as untracked repo content on the next `git status`. `ln -sfn` replaces the link instead of following it, which makes this block safe to re-run.

If a box has had `ln -sf` run twice, clean up with:

```bash
find ~/claude-config/skills -mindepth 2 -maxdepth 2 -type l -delete
```

Vendoring (copying into this repo) beats cloning the upstream repo per-server: the upstream was already pruned 17→4, upstream drift is unwanted, and step 0 then keeps the skills current fleet-wide for free.

**Upstream: `https://github.com/WordPress/agent-skills`** (began under Automattic, now WordPress org). Vendored 2026-07-18 from upstream commit `c212346`; the repo had grown to 18 skills by then. `skills/VENDORED.md` records provenance.

## 8. RunCloud API token

The global CLAUDE.md tells CC to prefer the API over SSH for RunCloud tasks. That requires the token on the box.

**There is no single fleet convention. Resolve the path per box; do not assume.** Surveyed across all nine servers 2026-07-30:

| layout | boxes |
|---|---|
| `~/.runcloud/token` — bare JWT, mode 600 | jbm001, jbm002, jbm003, parkshere2022, vlta0225, mpd2025_01, devmpdesign, client-spade (8) |
| `~/.runcloud-token` — `export RUNCLOUD_API_TOKEN=` line, mode 600 | mpd2026 (1) |

The bare-JWT layout is the fleet reality. The export form exists on one box, provisioned 2026-07-28. A survey of one box was mistaken for doctrine and written into `global-CLAUDE.md` (`fd68161`) and then into this file (`8ed2ac1`); both were wrong for eight of nine servers. **Convergence is deferred, not decided** — pick a direction in a session dedicated to it, not mid-rollout.

Read it, don't source it:

```bash
T=$(tr -d '\n' < ~/.runcloud/token)      # 8 boxes
# or, where the export form is present:
set -a; . ~/.runcloud-token; set +a; T="$RUNCLOUD_API_TOKEN"   # mpd2026
```

`cat` on the export file returns the assignment, not a bare token, and anything expecting a bare token silently sends the string `export`. Reading into `$T` fails loudly on a missing file; `source` fails **silently** and leaves whatever was already in the environment — see below.

⚠️ **Never trust a pre-exported `$RUNCLOUD_API_TOKEN`.** jbm003 sources `~/.runcloud-api.env` from `~/.bashrc` line 122, which populates the variable with a stale value in **interactive shells only**. A non-interactive probe returns unset, so the poisoning is invisible to `ssh box 'echo $RUNCLOUD_API_TOKEN'` and live in every CC session. `~/.runcloud-api.env` also exists on jbm001 (299 B, `export` form, not sourced anywhere) and on jbm003 (626 B, comment header) — two different files sharing a name. Always resolve from the file on disk.

**A single 401 is not proof of expiry.** Both a valid and a stale token are ~235-char JWTs, so length distinguishes nothing. Before asking Michael to rotate: `sha256sum` each copy present on the box, and `curl` each against the API. A 401 from a stale environment copy while the file on disk is valid is the expected failure here, not an exception.

At least three distinct token values are in play by size — 235 B (jbm group + parkshere2022), 227 B (vlta0225, mpd2025_01, devmpdesign), 236 B (client-spade). mpd2026's 264 B export file wraps a 235 B token, matching the jbm group. Whether these are workspace-scoped or per-server is unresolved; the earlier "workspace-level, reaches every server" claim is unverified and should not be relied on.

Transfer the token by `scp` from the Mac — never paste it through a chat session, never echo the value, and never commit it anywhere in this repo.

## 9. Verification — the box is ready when all of these pass

**The floor — must pass on every box:**

```bash
ssh {alias}                                  # from Mac: no-password login
ssh -T git@github.com                        # greets by account name (non-zero exit is normal)
git -C ~/claude-config pull --ff-only        # "Already up to date"
ls -la ~/.claude/CLAUDE.md                   # symlink → ~/claude-config/global-CLAUDE.md
ls -la ~/.claude/commands/                   # wordpress-runcloud.md, wordpress-audit.md
ls -la ~/.claude/skills/                     # all five skills, all symlinks
gh auth status                               # authenticated
claude --version                             # native install, auto-updated
ls -l ~/.runcloud/token ~/.runcloud-token 2>/dev/null   # exactly one should exist; mode 600
stat -c '%a %s %n' ~/.runcloud/token 2>/dev/null        # 600, ~227-236 bytes
ssh {alias} 'claude --version; gh --version' # from Mac: PATH survives a non-login shell
find ~/.claude -xtype l -not -path '*/debug/*'          # dangling links; prints nothing
find ~/claude-config/skills -mindepth 2 -maxdepth 2 -type l   # ln -sf nesting; prints nothing
```

`~/.claude/debug/latest` is excluded deliberately: it is a CC-internal symlink that dangles routinely between sessions on any box where `claude` has run, so an unfiltered `find` never comes back clean and the check becomes noise.

**The role layer — report, don't remediate:**

```bash
ls ~/.claude/commands/flatsite.md 2>/dev/null       # flatsite boxes only
ls ~/.cloudflare* ~/.pagespeed* ~/.google* 2>/dev/null   # credentialed boxes only
grep -n runcloud ~/.bashrc ~/.profile 2>/dev/null   # stale-token sourcing; jbm003 line 122
```

Absence here is a role fact, not a finding. Record which boxes are which; do not converge them.

Then the real test: start CC, run `/wordpress-runcloud`, and watch step 0 pull cleanly. If the slash command loads and the webapp listing appears, the box is fleet-standard. First project on the box follows the kickoff protocol in `00` — unharvested-sibling check before the snapshot copy, as always.
