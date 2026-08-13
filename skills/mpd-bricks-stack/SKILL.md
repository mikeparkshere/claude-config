---
name: mpd-bricks-stack
description: "Use when building or modifying a Michael Parks Design WordPress site on Bricks Builder + Automatic.css (ACSS): writing Bricks element trees or global classes via WP-CLI, discovering Bricks schemas, applying ACSS tokens, BEM structure and styling-layer order, registering ACF fields in PHP, wiring WS Form or Rank Math, or diagnosing non-obvious Bricks/ACSS/ACF behavior. NOT for generic WordPress work with no Bricks or ACSS involvement, static HTML sites, or server administration."
---

# MPD Bricks stack

The canonical knowledgebase for how this stack works and how Mike builds with it. It exists so a build starts hot, with no training tax.

**This file is the loader and the enforcement layer. The canon lives in `~/claude-config/knowledgebase/`.** Do not restate its content here; read the files.

---

## Hard rules

These six govern almost everything. Full versions are in the files below.

1. **Golden rule: never guess a Bricks schema.** Not in `02`'s library means discover it by reading back builder-saved output. A guessed key persists in the DB and silently strips on the next builder load.
2. **Typed settings before CSS, and styled elements get a class.** Check whether Bricks has a typed control before writing any CSS. Whatever the layer, it attaches to a Global Class — never to the element, which emits `#brxe-<id>` at `(1,0,0)` and poisons every later override. Discovery and expression are different rules — see `00`; both rules in full in `01`.
3. **Readbacks are narrow.** `jq`-extract the shape you need. Whole blobs go through a subagent. A full `_bricks_page_content_2` read on staging can run tens of thousands of tokens and evict this skill.
4. **`wp_set_current_user(1)` first.** Any WP-CLI script writing `_bricks_page_*_2` meta. Without it the write silently no-ops and reports success.
5. **After any compaction: stop.** Re-run the read protocol before the next write. A compacted session is a new session wearing the old one's scrollback.
6. **Doc/build mismatches get flagged, not fixed.** Audits and alignment passes never migrate built state between homes. Pinned custom tokens have exactly one home — ACSS Global CSS (`01`) — and a doc naming another home is a doc bug. Any authorized move of load-bearing state verifies the new home on the rendered front end before the old copy is deleted (`00`).

---

## Read protocol

**Read fully before touching a build:**

- `~/claude-config/knowledgebase/00-operating-rules.md` — memory model, session lifecycle, read/write protocols, the two core principles, surface selection.
- `~/claude-config/knowledgebase/01-stack-conventions.md` — BEM/ACSS structure, styling-layer order, semantic HTML and ARIA, the variable reference.
- `~/claude-config/knowledgebase/02-build-pipeline.md` — wireframe intake, the WP-CLI build, the verified schema library.

**Consult by symptom, never cover to cover:**

- `~/claude-config/knowledgebase/03-stack-gotchas.md` — build-stack gotchas. **Start at its Index**, then grep for the exact entry title. ~193KB; reading it whole triggers the compaction that evicts this skill.
- `~/claude-config/knowledgebase/04-hosting-cutover.md` — hosting, cutover, cache and performance. Same contract.

**Scaffolding a new project:** `~/claude-config/knowledgebase/99-project-context.template.md`.

Six files. If a project's own docs list fewer, that list is stale — `04` is the one usually missing.

---

## Project copies supersede nothing

A project may carry its own copies of these files under `<webapp>/docs/`. Those are **snapshots taken at kickoff, not subscriptions.** They do not receive corrections made at master.

- For **conventions, pipeline and schemas** (`01`, `02`), the master in `~/claude-config/knowledgebase/` wins. Read it, not the project copy.
- For **gotchas** (`03`, `04`), the project copy holds entries below its seam that the master does not have yet. Read both; the project copy is additive, never authoritative on anything above the seam.
- **Archived playbooks are not canon.** Anything in `~/claude-config/archive/` — including copies sitting in a project's `docs/` — is superseded. `wireframe-to-bricks-rules.md` in particular prescribes 1366px and 1024px breakpoints that do not exist on a default Bricks install and emit zero CSS silently.

---

## Before starting a new project

Check whether a sibling is sitting unharvested before copying the master:

```bash
git -C ~/claude-config pull --ff-only
```

Then check whether any live project still holds entries below its `03`/`04` seam. If one does, harvest it first — copying before that freezes the gap into the new project permanently. Procedure in `00`.
