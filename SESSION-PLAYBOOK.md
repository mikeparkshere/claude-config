# Claude Code Session Playbook

The rhythm that made the last session work. Re-read at the start of any new project.

---

## The three-layer memory model

| Layer | Lives in | What goes here | Portable? |
|---|---|---|---|
| **Auto-memory** | `~/.claude/projects/<encoded-path>/memory/` | Fast-changing state: current build status, in-flight decisions, client blockers, ephemeral todos | ❌ Per-machine, per-path |
| **CLAUDE.md** | Project root, committed | Project canon: stack, file layout, decisions, conventions, completed/upcoming work | ✅ Travels with the repo |
| **`/docs/`** | Project root, committed | Portable knowhow: stack gotchas, debugging patterns, project context that any future Claude (any machine) needs | ✅ Travels with the repo |

**Rule of thumb:** if a fact would still be true and useful when you clone the repo on the RunCloud server six months from now, it belongs in CLAUDE.md or `/docs/`. If it's about *right now* — what you're mid-build on, what the client is blocking on this week — auto-memory is fine.

---

## Session opening (every session, every project)

Type this — exact words don't matter, the cue does:

> Check memory first. Read CLAUDE.md and the files in /docs/ before we start.

Why it works: forces a full context load instead of relying on the MEMORY.md index snippet. ~20 second prime; pays for itself immediately.

If the project is new (no docs yet), instead say:

> This is a fresh project. Help me scaffold CLAUDE.md and /docs/stack-gotchas.md before we start coding.

---

## The session rhythm

Three modes, in this order, repeating:

### 1. Iterate fast
Make changes, see results, move on. Don't over-document while flow is good. CLAUDE.md and `/docs/` updates wait until something earns its place.

### 2. Instrument when blocked
The moment something is mysterious — element rendering blank, click being intercepted, CSS not winning the cascade — *stop guessing and instrument*.

Patterns that worked last session:
- **Diagnostic JS via code element**: drop `console.log` + `MutationObserver` into a Bricks code element, ask for paste-back, iterate
- **Doubled selectors** (`.foo.foo`) when Bricks inline CSS won't yield
- **Re-sign code elements** with `wp_hash($code)` after any DB edit
- **`_cssId` over Bricks internal IDs** for PHP filter targeting

The instrument-first habit is what lets you iterate fast without burning hours on guesswork. Build the habit, not just the tools.

### 3. Save learnings when a gotcha bites
When you hit something that:
- Took more than 15 minutes to figure out
- Is non-obvious (you wouldn't guess it from the docs)
- Is likely to bite again on a different project

…it earns a spot in `/docs/stack-gotchas.md`. **Capture it with the concrete incident as provenance** — "we hit this when X" — not abstract advice. Future-you needs anchors, not principles.

---

## Session closing (when you're 80% through your context window or wrapping up)

Ask:

> Before we close, what's worth preserving from this session that isn't already in CLAUDE.md or /docs/? Distill anything new into the right file.

Then specifically prompt for:
- New gotchas (→ `/docs/stack-gotchas.md`)
- New project decisions or completed milestones (→ `CLAUDE.md`)
- New auto-memory entries for in-flight state

The closing session is where the workflow compounds. Skip it and the next session starts cold.

---

## Portability checklist (when starting a new project on a different machine/server)

1. Repo cloned ✅
2. `CLAUDE.md` present in root ✅
3. `/docs/` folder with `stack-gotchas.md` + `project-context.md` ✅
4. First message in Claude Code: "Check memory first. Read CLAUDE.md and the files in /docs/ before we start."
5. Optional: sync `~/.claude/CLAUDE.md` (user-level preferences) via dotfiles repo if you want personal collaboration style to travel too. Most people just re-establish over 2–3 sessions on a new machine.

---

## Anti-patterns (things that *don't* travel well)

- **Auto-memory as primary documentation**. It's machine-pinned to an encoded path of your CWD. Rename the folder, move to a different mount, switch machines — gone.
- **Committing fast-changing state to the repo**. Build status, today's blockers, "we're mid-refactor on X" — these create noisy git history and go stale fast. Leave them in auto-memory.
- **Abstract gotcha entries**. "Watch out for cascade specificity" is useless. "When Bricks injects inline CSS via Global Class, doubled-selector `.foo.foo` in the external sheet beats it — see the homepage notice background bug 2026-04-24" is useful.
- **Skipping session close**. The 5-minute distillation at the end is what makes the *next* session start hot.
