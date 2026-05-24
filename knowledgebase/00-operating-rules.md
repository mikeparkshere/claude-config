# 00 — Operating Rules

**Michael Parks Design — Claude Code knowledgebase.**

Verified against Bricks 2.3.4 / ACSS 3.3.6 / WordPress 6.9.x. V1 baseline: 2026-05-24.

---

## What this knowledgebase is

This is a knowledgebase for how the MPD stack works and how Mike works with it. It exists so that a new build starts hot — stack up, ACSS configured, tokens known, wireframes in hand — with no training tax. You should not have to be walked through the golden rule or told to go read the gotchas. It is already here.

The knowledgebase is five files:

- **00-operating-rules.md** (this file) — how to behave: the memory model, the session lifecycle, the read and write protocols, the two core principles, surface selection, the auth requirement.
- **01-stack-conventions.md** — how Mike builds: BEM/ACSS structure, the styling-layers priority order, semantic HTML and ARIA, the variable reference.
- **02-build-pipeline.md** — the core capability: token-aware wireframe in, built Bricks page out. Front half (wireframe intake) and back half (WP-CLI build), plus the verified schema library.
- **03-stack-gotchas.md** — non-obvious stack behaviors discovered the hard way. A lookup catalog, consulted by symptom.
- **99-project-context.template.md** — the template for the per-project working log.

Two layers. **Bedrock** is `00`, `01`, `02` — settled knowledge, changes slowly, by deliberate decision. **Accumulator** is `03` — provisional knowledge, each entry a real incident with a fix, grows every project.

The knowledgebase serves new builds. It is not for the pre-AI fleet and not for live-site maintenance. Its lifecycle is Local → Staging → go-live, and go-live ends its involvement with a project.

---

## The memory model — where any given fact lives

Three layers hold knowledge, each with a different scope and lifespan. Knowing which layer a fact belongs in is the difference between knowledge that survives and knowledge that evaporates.

| Layer | Lives in | What goes here | Travels? |
|---|---|---|---|
| **Auto-memory** | Claude Code's per-project memory | Fast-changing state: current build status, in-flight decisions, this week's client blockers, ephemeral todos | No — per-machine, per-path |
| **Project files** | The project repo — `CLAUDE.md` and the project's `project-context.md` | Project canon: stack versions, file layout, decisions and their reasoning, completed and upcoming work, the "why and how we got here" narrative | Yes — travels with the repo |
| **The knowledgebase** | `claude-config/knowledgebase/` — these five files | Portable stack knowledge: how the stack works, how Mike builds, gotchas, the build pipeline. True on every project, every machine | Yes — the master in `claude-config` |

The test: if a fact would still be true and useful when the repo is cloned on a different server six months from now, it belongs in a project file or the knowledgebase. If it is about *right now* — what is mid-build, what the client is blocking on this week — auto-memory is the right home.

The relationship between the layers: `CLAUDE.md` is "what is" for a project. `project-context.md` is "why and how we got here" for that project. The knowledgebase is everything that is true *across* projects. A discovery during a build is triaged into one of these — a project-specific finding goes in `project-context.md`; a stack-wide gotcha goes in the project's copy of `03` and is harvested to the knowledgebase master at go-live; fast-changing state stays in auto-memory and is never committed.

**Anti-pattern:** committing fast-changing state to a repo. Build status, today's blockers, "mid-refactor on X" — these create noisy history and go stale. They stay in auto-memory.

---

## Read protocol

At the start of any build session:

1. **Read fully:** `00`, `01`, `02`. This is the day-one canon. Read it before touching the build.
2. **Lookup tier:** `03` is a catalog. Do not read it cover to cover. Consult it when a symptom matches — a write that silently no-ops, a style that won't override, an element that won't render. The schema library inside `02` is also lookup tier: read the front-half and back-half method fully, consult individual schemas as needed.
3. **Check the project phase.** Open the project's `project-context.md` and read the Current Status / phase field. Local vs Staging tells you how close to launch the project is. It is informational, not a hard mode switch — it tells you how freely you can experiment. A project in Local is fine for discovery work; a project in Staging is closer to launch, so weigh changes accordingly.

The point of reading `00`/`01`/`02` fully is that there is no separate training step. The canon is the training.

---

## Session lifecycle — the opener and the closer

A session has a beginning and an end ritual. The opener primes; the closer preserves. Together they are the loop that makes each session start hotter than the last. Skipping either breaks the loop.

### The opener

**On the first session of a brand-new project** — no project memory exists yet, and CC is responsible for *building* it during this session:

> This is the first session on a new project. No memory exists yet. Read the knowledgebase, then help scaffold this project's `CLAUDE.md` and `project-context.md` from the `99` template before we start building.

**On every session after the first:**

> Check memory first. Read the knowledgebase, then `CLAUDE.md` and this project's `project-context.md` before we start.

Either way, CC primes fully — knowledgebase plus project files — before touching the build. The roughly twenty seconds it costs pays for itself immediately. The fresh-vs-returning distinction matters: on session one CC should not waste effort looking for memory that does not exist, and it should know it is on the hook for creating the project files this session.

### The closer

The closer is not optional. It is the mechanism that moves a discovery out of the volatile session and into a file that survives. Without it, anything learned mid-session evaporates when the context window fills, and the next session starts cold. At roughly 80% of the context window, or when wrapping up:

> Before we close: what is worth preserving from this session that is not already captured? Distill anything new into the right file.

Then triage explicitly, per the memory model and the write protocol below:

- New stack gotchas → the project's copy of `03`, below the seam.
- New verified schemas → the project's copy of `02`, the schema library.
- New project decisions or completed milestones → `CLAUDE.md`.
- New project-specific findings → `project-context.md`.
- Fast-changing in-flight state → auto-memory, not a committed file.

The closer is where the workflow compounds. The write protocol below says *what* earns a place and *where* it goes; the closer is *when* it happens.

---

## Write protocol

When you discover something during a build, capture it. The knowledgebase only ratchets forward if discoveries survive the project they happened in.

**The bar.** An entry earns a place when it took more than ~15 minutes to figure out and is likely to bite again. A one-off typo fix does not qualify. A non-obvious stack behavior with a reproducible cause does.

**Which file.** Decide by the nature of what you learned:

- A **gotcha** — a non-obvious behavior, a silent failure, a thing that bit you — goes in `03`. It has a symptom and an incident.
- A **verified schema** — a Bricks element or setting shape discovered via the golden rule — goes in the schema library in `02`. It is reference, not an incident.
- A **convention** — a settled "this is how we build it" decision — goes in `01`. Conventions are rare mid-build; most of the time a discovery is a gotcha or a schema. A convention usually arrives by promotion (see harvest below), not by mid-build capture.

**Where it goes.** New entries append to **the project's copy** of the file, never to the master in `claude-config`. Each project runs on its own copy, inherited at kickoff. The master changes only at harvest.

**`03` has a seam.** The gotchas file is split by a structural marker into the established section ("we know" — everything inherited at kickoff) and the project section ("we learned" — empty at kickoff, fills during the build). Append new gotchas below the seam, in the project section. Do not edit entries above the seam during a build.

**Entry format for `03`** — match the existing entries exactly:

```
### [Short pattern title — what you'd search to find this]
**Symptom / When:** [the observable failure]
**Why:** [the underlying mechanism, briefly]
**Fix:** [the actual fix, copy-pasteable where possible]
**First seen:** [project], [YYYY-MM-DD] — [the concrete incident]
```

For entries with no concrete incident — inherited knowledge that predates the knowledgebase — provenance is `V1 baseline, 2026-05-24`. From inception onward, every new entry carries a real project and date.

---

## The go-live harvest

Go-live is the one moment the master knowledgebase changes.

When a project goes live, its learning is done. Everything it was going to discover, it has discovered. At that point:

1. The project's `03` project-section entries (everything below the seam) get reviewed by Mike.
2. Validated entries fold into the master `03` — promoted above the seam into the established section, provenance kept.
3. New verified schemas captured during the build fold into the master `02` schema library.
4. Anything that turned out to be wrong, project-specific, or already covered gets dropped, not merged.

This is a reviewed file edit in the `claude-config` repo. It is the same discipline as the existing CLAUDE.md → Notion sync — edit markdown, review the diff, commit. There is no slash command. The review is the point: the master is what every future project inherits, so nothing reaches it unexamined.

After harvest, the project is done with the knowledgebase. Whatever happens to the site afterward is out of scope.

---

## Surface selection

There are three surfaces for build work. They are complementary, not interchangeable. Pick by task.

| Task | Surface |
|---|---|
| Turning a token-aware wireframe into a built Bricks page | **WP-CLI build** — the pipeline in `02`. This is the primary path. |
| Discovering an unknown Bricks schema; building or extending a template with Bricks UI controls available | **Bricks builder** — visual, authoritative. Build one example, save, read it back. |
| Bulk operations: batch class creation, global class CSS population, custom query types, dynamic tags, CPT scaffolding, ACF fields | **WP-CLI direct DB edit** — the back half of `02`. |

When a WP-CLI edit stalls — Bricks stripping writes, a specificity war, a schema you don't have — the fix is almost always to drop into the builder, discover the schema there, and resume via WP-CLI. The builder is the source of truth for shapes; WP-CLI is the mechanism for replicating them at scale.

The paste-into-Bricks-UI step from the older wireframe workflow is the thing the pipeline is built to eliminate. Default to the WP-CLI build path.

---

## The two core principles

Two rules govern almost everything in a build. They are easy to confuse because both say "don't just do the obvious thing" — but they apply at different moments. Keep them distinct.

**The golden rule governs discovery — how to learn a shape.** The typed-settings rule governs expression — how to apply a value. One is "I don't know the schema." The other is "I know what I want; how do I apply it."

### The golden rule — never guess a Bricks schema

**Never guess Bricks internal schemas. Build one example of the thing you need in the builder, save, read it back, then replicate that shape programmatically.**

This is the single most reliable principle for direct-DB work. Bricks runs a JS-side tree validator when the builder loads a template — unknown setting keys and malformed values get silently dropped, and the cleaned tree is re-saved on the next builder write. Every schema guess that is not builder-output-verified gets stripped. Every schema discovered this way sticks.

The discovery workflow:

1. Open the target template in the Bricks builder.
2. Add the minimum version of the element you need — one Query Loop with only Query Type set, one element with one border, etc.
3. Save the template.
4. Read it back: `wp post meta get <id> _bricks_page_content_2 --format=json`.
5. Use the returned shape as the schema template.

Full mechanics, the verified schema library, and the failure modes are in `02`.

### The typed-settings rule — typed controls before CSS, always

**Before writing any CSS, check whether Bricks has a typed control for it. If it does, use the control.**

This is the most-violated rule in the knowledgebase. The instinct is to reach straight for `_cssCustom` — it is fast and it gives more control. That instinct is wrong here. `_cssCustom` is less maintainable the moment a junior dev or the client takes over the site: inline CSS is invisible in the Bricks UI, accumulates as cruft, and is not editable by anyone working through the panels. A typed setting is visible, editable, and survives a handoff. The rule is not about what is easiest to write — it is about what is maintainable to inherit.

Priority order — always in this order:

1. **Typed Bricks setting via the UI control.** If the value can be typed into a Bricks panel — padding, margin, gap, border, background, typography, grid — it goes there. Check first, every time.
2. **`_cssCustom` on the specific element or class** — only when no typed control can express the value. Scoped to that use case.
3. **Child theme custom CSS** — when the need is genuinely global and site-wide, not element-specific.

Full convention and the styling-layers reasoning are in `01`. The principle is named here because CC breaks it constantly — it belongs in the read-first rules, not buried.

---

## Authentication requirement

**The first line of any WP-CLI script that writes Bricks template meta is `wp_set_current_user(1)`.**

Bricks hooks `update_post_metadata` and returns false — blocking the write — when the builder capability check fails. WP-CLI runs as user 0 by default, so the check fails and the write silently no-ops: no error, no log entry, the script reports success, nothing lands.

```php
wp_set_current_user( 1 );  // or any user ID with builder access
```

This gates the three per-post keys: `_bricks_page_content_2`, `_bricks_page_header_2`, `_bricks_page_footer_2`. Writes to the `bricks_global_classes` option are **not** gated this way — that option can be updated without setting the current user. Knowing the difference is useful when isolating a failure mode.

Full incident and mechanism: `03`, "update_post_meta silently fails on `_bricks_page_*_2` keys from WP-CLI."

---

## Phase 2 note

This knowledgebase is built as plain markdown so it works now. The intended end state is a Skill that Claude Code auto-loads when the work is Bricks/WordPress/ACSS — no manual pointing, no command. When that promotion happens, this file's content largely becomes `SKILL.md` and the other four become supporting files. The file structure is designed so that wrap is mechanical. Until then, this knowledgebase is loaded by being referenced from each project's CLAUDE.md or pointed at directly at session start.
