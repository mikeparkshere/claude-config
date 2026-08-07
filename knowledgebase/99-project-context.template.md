# Project Context — {PROJECT NAME}

> Historical decisions, brief pivots, and key findings for this specific project. Read alongside CLAUDE.md at session start.
>
> CLAUDE.md is "what is" — this file is "why and how we got here."

---

## How to use this file

This is the per-project working log. CC instantiates a copy of it at the start of each project and fills it in as the build proceeds. It is not part of the knowledgebase bedrock and it does not fold into the master at go-live — it stays a project artifact.

Replace `{PROJECT NAME}` and every `{placeholder}` below. Delete this "How to use" block once the file is instantiated. Mark unfilled sections `Not yet captured` rather than deleting them — an explicit gap is a signal.

**At instantiation, also copy the hard-rules block below verbatim to the TOP of this project's `CLAUDE.md`.** It stays in CLAUDE.md, not here — CLAUDE.md content holds attention across a long session in a way the knowledgebase, read once at minute one, does not. Reference layer vs. enforcement layer (see `00`, the fourth layer).

```markdown
## Hard rules (enforcement layer — full versions live in the knowledgebase)

0. **PHASE: {DEV | STAGING | LIVE}{ | +TXN}.** Read this back at session start
   before touching anything (`00`, read protocol step 3). DEV: reproducible,
   sprint. STAGING: irreplaceable client state exists and the client may be
   looking — stop before the database, plugin/theme deletion, or wp-config.
   LIVE: start in plan mode, agree the work, then execute. No readable phase
   means LIVE.
1. **Golden rule:** never guess a Bricks schema. Not in the `02` library =
   discover via readback of builder-saved output. A guessed key persists in
   the DB and silently strips on next builder load.
2. **Typed settings:** know the mechanism before applying a value. Discovery
   and expression are different rules (`00`).
3. **Readbacks are narrow:** `jq`-extract the shape you need; whole blobs go
   through a subagent (`02`, discovery cost).
4. **After any compaction:** stop. Re-run the read protocol before the next
   write (`00`).
5. **Session degrading:** `/clear` and re-prime. Do not push through.
```

---

## Current Status

**Phase: {DEV | STAGING | LIVE}{+TXN}.** {One paragraph: where the project is — install location, URL, what is built, what is live.}

Per the read protocol in `00`, the phase is a **contract, not a note**, and the canonical copy is hard rule 0 at the top of this project's `CLAUDE.md`. This entry is the narrative version; if the two disagree, rule 0 wins and the disagreement gets reported, not silently reconciled. The enforcement layer is `.claude/settings.json` in the project root, installed by `bin/set-phase.sh`.

DEV ends at whichever comes first: real data loading in, or the first client review. Both are the same event — something arrives that the build pipeline cannot regenerate. STAGING runs from there to cutover, which is most of the project's life. `+TXN` marks a site taking payments; it is a permanent property, not a phase, and it composes with all three.

**Stack versions:** WordPress {x}, Bricks {x}, ACSS {x}, {core plugin} v{x}, {child theme} v{x}.

**Environments:**
- Local: {URL} — {install root}
- Staging/DEV: {URL} — {install root}
- Production (target): {domain, or "not confirmed"}

**Built so far:** {pages / templates complete}.

**Outstanding before launch:** {what remains — pages, content, client assets, config gaps, SEO/performance pass, schema, QA, cutover}.

---

## Brief & Pivots

### Original brief
{The client's original ask, target outcomes, success criteria, agreed scope at kickoff. Capture this early — it anchors every later decision.}

### Decision — {short title}
**Direction:** {what was decided.}
**Why:** {the reasoning. Decisions without reasoning get re-litigated.}

*(Repeat per significant decision. Include locked items — palette, typography, voice, messaging — and note the lock date so they are not reopened without a client conversation.)*

---

## Key Findings

> Cross-project stack gotchas live in the knowledgebase `03-stack-gotchas.md`. This section is for project-specific findings — things true of THIS build, not the stack in general. A genuine stack gotcha discovered here goes in this project's copy of `03`, below the seam, not here.

### {Finding title} — {YYYY-MM-DD}
{What was discovered or done, and what it means for the rest of the build.}

*(Repeat per finding, newest-relevant on top. These are the "how we got here" narrative entries.)*

---

## Content / Data Strategy

**Page set:** {the pages, and whether any CPTs are involved.}

**Editor strategy:** {per the convention in `01` — Gutenberg for `post`, Classic Editor for Bricks-owned CPTs. A brochure site with no CPTs mostly does not need one.}

**ACF field groups:** {planned groups — options page for company/brand/contact/social, page-specific groups. All registered programmatically in the core plugin per `01`.}

**Forms:** {WS Forms usage, submission destination, activation status.}

**Cross-linking:** {editorial vs automatic Bricks queries.}

**Schema:** {what JSON-LD ships at launch and where it lives.}

**Photography:** {asset status — placeholder vs client-supplied. Do not ship visuals as final without confirming source.}

**Voice / copy:** {locked copy, voice rules, anything off-limits.}

---

## Client / Stakeholder Notes

- **Primary contact(s):** {name(s), role.}
- **Public-facing email(s):** {general / leads.}
- **Phone:** {number.}
- **Preferred prospect contact channel:** {phone / form / booking.}
- **Response promise:** {turnaround, or "not yet captured".}
- **Approval pattern:** {how the client signs off, or "not yet captured".}
- **Known sensitivities:** {brand-trust commitments, voice constraints, anything that has caused friction. Capture these — they prevent expensive mistakes.}

---

## Open Questions

- [ ] {Unresolved item — scope, asset, decision, client confirmation.}
- [x] ~~{Resolved item}~~ — Resolved {YYYY-MM-DD}. {One line on the resolution.}

*(Track both. Resolved items kept with strikethrough are a paper trail.)*

---

## Reference Links

- **Staging:** {URL}
- **Production (target):** {URL — note if not yet live / not confirmed}
- **Webapp root:** {path}
- **Core plugin:** {path}
- **Child theme:** {path}
- **Wireframes:** {location}
- **Logs:** {RunCloud paths — PHP/Apache and Nginx}
- **DB backups:** {location / naming convention}
- **ACSS token map:** {location of the per-project extracted token map}
- **Notion CLAUDE.md:** {Notion URL — a **published view, not canonical**. This project's `CLAUDE.md` is the source of truth; CC pushes it to Notion on request, one-way. Never edit the Notion copy — an edit there is silently lost at the next sync.}
- **Brand assets:** {location}
- **Social:** {handles}

---

*Companion to CLAUDE.md. Updated as decisions are made and findings emerge.*
*Last updated: {YYYY-MM-DD} — {what changed}.*
