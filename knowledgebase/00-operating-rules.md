# 00 — Operating Rules

**Michael Parks Design — Claude Code knowledgebase.**

Verified against Bricks 2.3.4 / ACSS 3.3.6 / WordPress 6.9.x. V1 baseline: 2026-05-24.

---

## What this knowledgebase is

This is a knowledgebase for how the MPD stack works and how Mike works with it. It exists so that a new build starts hot — stack up, ACSS configured, tokens known, wireframes in hand — with no training tax. You should not have to be walked through the golden rule or told to go read the gotchas. It is already here.

The knowledgebase is six files:

- **00-operating-rules.md** (this file) — how to behave: the memory model, the session lifecycle, the read and write protocols, the two core principles, surface selection, the auth requirement.
- **01-stack-conventions.md** — how Mike builds: BEM/ACSS structure, the styling-layers priority order, semantic HTML and ARIA, the variable reference, the inherited-project audit.
- **02-build-pipeline.md** — the core capability: token-aware wireframe in, built Bricks page out. Front half (wireframe intake) and back half (WP-CLI build), plus the verified schema library.
- **03-stack-gotchas.md** — non-obvious **build stack** behaviors discovered the hard way (Bricks, ACSS, ACF, WS Form, Rank Math). A lookup catalog, consulted by symptom.
- **04-hosting-cutover.md** — non-obvious **hosting layer** behaviors: the box, the CDN, the caches, and the cutover itself (RunCloud, RunCache, Cloudflare, ShortPixel, Perfmatters). Same catalog contract as `03`.
- **99-project-context.template.md** — the template for the per-project working log.

Two layers. **Bedrock** is `00`, `01`, `02` — settled knowledge, changes slowly, by deliberate decision. **Accumulators** are `03` and `04` — provisional knowledge, each entry a real incident with a fix, grows every project.

**Why `03` and `04` are separate:** `03` is how the build stack behaves while you build; `04` is the layer underneath and after. Different domains, consulted at different moments. Keeping ops out of `03` keeps `03` fast to scan by symptom, which is its whole job.

The knowledgebase serves new builds, through go-live. It is not for the pre-AI fleet. Its lifecycle is Local → Staging → **cutover**, and the cutover is where its involvement ends.

**Scope note (2026-07-15).** This originally read "not for live-site maintenance… go-live ends its involvement." The TAB harvest stretched that line honestly: a cutover *is* the end of the lifecycle and belongs here, and the post-launch performance lessons (RUCSS on a lean site, PSI lab noise, a cache plugin's broken Redis flush silently reverting content writes) were bought at real cost and will be paid for again on the next launch if they aren't written down. They live in `04`. What remains out of scope: ongoing maintenance of a delivered site — that's the project's own docs, not portable stack knowledge.

**This repo is PUBLIC.** Nothing in the knowledgebase may carry credentials, server IPs, zone IDs, or client-confidential business context. Document **mechanisms, not coordinates** — "the RunCloud panel owns the crontab" travels; an IP address does not. This matters most at harvest time, because auto-memory (the natural source) is full of coordinates, and `04` is the file most likely to attract them. Coordinates belong in auto-memory anyway: per the memory model below, they're per-machine state that goes stale. Real incidents are fine as provenance — scrub the identifying particulars (say "a $95/mo hosting order", not the client's username and order id). Scan the staged diff before committing.

---

## The memory model — where any given fact lives

Three layers hold knowledge, each with a different scope and lifespan. Knowing which layer a fact belongs in is the difference between knowledge that survives and knowledge that evaporates.

| Layer | Lives in | What goes here | Travels? |
|---|---|---|---|
| **Auto-memory** | Claude Code's per-project memory | Fast-changing state: current build status, in-flight decisions, this week's client blockers, ephemeral todos | No — per-machine, per-path |
| **Project files** | The project repo — `CLAUDE.md` and the project's `project-context.md` | Project canon: stack versions, file layout, decisions and their reasoning, completed and upcoming work, the "why and how we got here" narrative | Yes — travels with the repo |
| **The knowledgebase** | `claude-config/knowledgebase/` — these six files | Portable stack knowledge: how the stack works, how Mike builds, gotchas, the build pipeline, the hosting layer. True on every project, every machine | Yes — the master in `claude-config` |
| **Session context** | The running CC conversation | Everything read this session — the knowledgebase itself, once read, lives HERE, not in any persistent layer | No — and it leaks mid-session (see below) |

The test: if a fact would still be true and useful when the repo is cloned on a different server six months from now, it belongs in a project file or the knowledgebase. If it is about *right now* — what is mid-build, what the client is blocking on this week — auto-memory is the right home.

The relationship between the layers: `CLAUDE.md` is "what is" for a project. `project-context.md` is "why and how we got here" for that project. The knowledgebase is everything that is true *across* projects. A discovery during a build is triaged into one of these — a project-specific finding goes in `project-context.md`; a stack-wide gotcha goes in the project's copy of `03` and is harvested to the knowledgebase master at go-live; fast-changing state stays in auto-memory and is never committed.

**Anti-pattern:** committing fast-changing state to a repo. Build status, today's blockers, "mid-refactor on X" — these create noisy history and go stale. They stay in auto-memory.

### The fourth layer leaks — context is not a subscription

The knowledgebase on disk does nothing. It only governs behavior while it sits in session context, and session context is the one layer that degrades *during* a session. Two mechanisms:

**Compaction.** Long sessions auto-compact. The summary keeps "there is a golden rule" and loses the operational content — the discovery procedure, the schema library, the typed-settings distinction. From then on CC guesses with the confidence of something that remembers reading the rule. Compaction is invisible unless you treat it as a tripwire: **after any compaction event, re-run the read protocol before the next write.** No exceptions. A degraded session that keeps writing is more expensive than the re-read.

**Dilution.** Even without compaction, an instruction read 100k tokens ago under an hour of tool output gets soft adherence. This is why the hard-rules block exists at the top of the project `CLAUDE.md` (see the `99` template) — CLAUDE.md content holds attention in a way a file read at minute one does not. The knowledgebase is the reference layer; the hard-rules block is the enforcement layer.

**When a session goes sideways, `/clear` and re-prime.** Do not push a degraded session to finish the template. Re-priming costs twenty seconds; fighting a session that has lost the canon costs the afternoon.

> **(2026-07-18.)** First staging session of a new build on real content: mistakes left and right, golden rule not followed, the simplest template taking forever — with the knowledgebase current on the server and the project CLAUDE.md present and correct. On disk was never the question. Local sessions never hit the ceiling because local blobs are skeletons; staging blobs are tens of thousands of tokens each, and the golden rule's own readbacks are what flooded the context that held it.

---

## Read protocol

At the start of any build session:

1. **Read fully:** `00`, `01`, `02`. This is the day-one canon. Read it before touching the build.
2. **Lookup tier:** `03` and `04` are catalogs. Do not read them cover to cover. `03` opens with an **Index** of every entry title — scan that first, then grep the file for the exact title you need. Never read `03` whole: at ~24,000 words it triggers the compaction described above, evicting the very canon that sent you to it. Consult `03` when a build symptom matches — a write that silently no-ops, a style that won't override, an element that won't render. Consult `04` when the symptom is infrastructural — a stale response, a 404 that shouldn't be, a cert or DNS question, anything during a cutover. The schema library inside `02` is also lookup tier: read the front-half and back-half method fully, consult individual schemas as needed.
3. **Check the project phase.** Open the project's `project-context.md` and read the Current Status / phase field. Local vs Staging tells you how close to launch the project is. It is informational, not a hard mode switch — it tells you how freely you can experiment. A project in Local is fine for discovery work; a project in Staging is closer to launch, so weigh changes accordingly.

4. **Re-entry.** This protocol is not start-of-session only. It re-runs after any compaction event and after any mid-session `/clear` — a compacted or cleared session is a new session wearing the old one's scrollback. Steps 1–3 in full, no abbreviating on the grounds that "we read it earlier." Earlier is gone; that is what compaction means.

The point of reading `00`/`01`/`02` fully is that there is no separate training step. The canon is the training.

---

## Session lifecycle — the opener and the closer

A session has a beginning and an end ritual. The opener primes; the closer preserves. Together they are the loop that makes each session start hotter than the last. Skipping either breaks the loop.

### The opener

**On the first session of a brand-new project** — no project memory exists yet, and CC is responsible for *building* it during this session:

> This is the first session on a new project. No memory exists yet. Read the knowledgebase, then help scaffold this project's `CLAUDE.md` and `project-context.md` from the `99` template before we start building.

**Before you copy the master at kickoff, check whether a live project is sitting unharvested.** A project's `03`/`04` copy is a **snapshot, not a subscription** — taken once, never updated again. If a sibling has already launched and its learning is still below the master's seam, that knowledge is missing from this project's copy *permanently*, even after the sibling gets harvested next week.

```bash
git -C ~/claude-config pull    # the master moves; a stale local copy is its own trap
# then: does either accumulator's PROJECT SECTION hold entries from an already-live project?
# and: does `git log --oneline` show a sibling appending straight to the master?
```

If it does, **harvest that project first, then copy.** Go-live is the trigger (see the harvest below), so a live project's entries are already overdue — they are not "pending", they are late.

> **(2026-07-15.)** MMHN — a WooCommerce build — was an hour from inheriting a `03` with **zero** WooCommerce entries. Every Woo entry sat in VMG Client Portal's project section, unharvested, though VMG was live and therefore already past its trigger. Copying first would have frozen that gap into the one project that most needed it. Harvest cadence runs late by default (TAB launched 06-27, harvested 07-15), so "is anything overdue?" belongs at every kickoff, not in the edge cases. The same check surfaced that VMG had been appending straight to the master rather than keeping its own copy — a deviation from the write protocol worth catching early.

**On every session after the first:**

> Check memory first. Read the knowledgebase, then `CLAUDE.md` and this project's `project-context.md` before we start.

Either way, CC primes fully — knowledgebase plus project files — before touching the build. The roughly twenty seconds it costs pays for itself immediately. The fresh-vs-returning distinction matters: on session one CC should not waste effort looking for memory that does not exist, and it should know it is on the hook for creating the project files this session.

### The closer

The closer is not optional. It is the mechanism that moves a discovery out of the volatile session and into a file that survives. Without it, anything learned mid-session evaporates when the context window fills, and the next session starts cold. At roughly 80% of the context window, or when wrapping up:

> Before we close: what is worth preserving from this session that is not already captured? Distill anything new into the right file.

Then triage explicitly, per the memory model and the write protocol below:

- New build-stack gotchas → the project's copy of `03`, below the seam.
- New hosting/cutover/performance gotchas → the project's copy of `04`, below the seam.
- New verified schemas → the project's copy of `02`, the schema library.
- New project decisions or completed milestones → `CLAUDE.md`.
- New project-specific findings → `project-context.md`.
- Fast-changing in-flight state → auto-memory, not a committed file.

The closer is where the workflow compounds. The write protocol below says *what* earns a place and *where* it goes; the closer is *when* it happens.

The closer is also the exit ramp. At 80% the session is not just due for preservation — it is close to compaction, and compaction evicts the canon along with the discoveries. Once the closer has run and the files are written, do not keep building: `/clear`, then run the opener. Closer → clear → opener is one motion. A session that preserves its learning and then keeps working in a full context has saved the discoveries and sacrificed the golden rule.

### When the closer never ran — reconstructing a dead session

Sometimes a session ends without its closer: it is killed, the box runs out of memory, the connection drops. The next session opens onto work that was done but not written down, and the honest starting question is not "what should I do now" but **"what actually happened, and how much of it was saved?"**

That question is answerable. Four independent sources, none of which depend on the dead session having cooperated:

- **The prior transcript.** Sessions are journalled to `~/.claude/projects/<project-slug>/<session-id>.jsonl`, newest by mtime. It survives the kill, and it is the only source that records *intent* — what was asked, what was concluded, and what the session was in the middle of when it stopped.
- **Filesystem mtimes.** Plugin and theme directories, config files, and the docs themselves timestamp what was touched and when. `stat -c '%y %n'` across a directory reconstructs the order of operations even when nothing logged it.
- **The error log.** In-place update windows leave a distinctive signature — missing `vendor/autoload.php`, "class not found", `filemtime()` stat failures — bracketing the exact minutes a directory was being rewritten.
- **The artifacts themselves.** Read the file the session was supposed to write and check its mtime against the transcript's last entry. A doc written *before* the kill is intact work, not lost work.

**Cross-check them before concluding anything.** They disambiguate each other: a fatal in the log plus a matching directory mtime is a routine update window; the same fatal with no mtime nearby is a live problem. And check whether the dead session was mid-correction — a session that dies is disproportionately likely to have been in the middle of fixing something, because whatever killed it was often the same pressure that produced the error.

**Then verify the saved work rather than trusting it.** A file written before the kill is present, which is not the same as correct.

> **(NLTA, 2026-08-05.)** An update session was killed by memory pressure. The owner's assumption was that nothing had been written. In fact the project `CLAUDE.md` had been saved seven minutes before the kill and was complete and accurate — but the transcript showed the session's final act was an unfinished self-correction, which was the one thing genuinely lost. Reconstructing from mtimes and the error log also surfaced a whole earlier batch of work the session had never documented. The assumption was wrong in both directions at once: more had been saved than expected, and more had been done than was saved.

---

## Write protocol

When you discover something during a build, capture it. The knowledgebase only ratchets forward if discoveries survive the project they happened in.

**The bar.** An entry earns a place when it took more than ~15 minutes to figure out and is likely to bite again. A one-off typo fix does not qualify. A non-obvious stack behavior with a reproducible cause does.

**Which file.** Decide by the nature of what you learned:

- A **build-stack gotcha** — a non-obvious behavior, a silent failure, a thing that bit you in Bricks/ACSS/ACF/WS Form/Rank Math — goes in `03`. It has a symptom and an incident.
- A **hosting/cutover/performance gotcha** — the box, the CDN, the caches, the migration — goes in `04`. Same format, same bar. If you're unsure which: ask whether the fix is in the build or in the infrastructure.
- A **verified schema** — a Bricks element or setting shape discovered via the golden rule — goes in the schema library in `02`. It is reference, not an incident.
- A **convention** — a settled "this is how we build it" decision — goes in `01`. Conventions are rare mid-build; most of the time a discovery is a gotcha or a schema. A convention usually arrives by promotion (see harvest below), not by mid-build capture.

**Where it goes.** New entries append to **the project's copy** of the file, never to the master in `claude-config`. Each project runs on its own copy, inherited at kickoff. The master changes only at harvest — with one exception, for corrections: see "The correction exception" below.

**`03` and `04` both have a seam.** Each is split by a structural marker into the established section ("we know" — everything inherited at kickoff) and the project section ("we learned" — empty at kickoff, fills during the build). Append new gotchas below the seam, in the project section. Do not edit entries above the seam during a build.

**If a discovery contradicts an entry above the seam, do not silently edit the entry** — append yours below the seam and flag the conflict explicitly for the harvest. Two entries that disagree are a decision for Mike to make once, not a fight for each session to re-litigate. (TAB did exactly this with the `--skip-themes` finding, and the flag is why it was reconciled correctly rather than merged as a phantom second mechanism.)

**Entry format for `03` / `04`** — match the existing entries exactly:

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

Go-live is the moment the master knowledgebase **grows**. (It is not the only moment it *changes* — a false entry is corrected on sight; see the next section.)

When a project goes live, its learning is done. Everything it was going to discover, it has discovered. At that point:

1. The project's `03` and `04` project-section entries (everything below the seam) get reviewed by Mike.
2. Validated entries fold into the master `03` / `04` — promoted above the seam into the established section, provenance kept.
3. New verified schemas captured during the build fold into the master `02` schema library.
4. Anything that turned out to be wrong, project-specific, or already covered gets dropped, not merged.
5. Where a project entry **extends** an existing master entry rather than standing alone, fold it into that entry and keep both provenances — one mechanism, one entry. Two entries describing the same mechanism from different angles is how a catalog rots.
6. Conflicts flagged during the build get **resolved** here, not carried. Where a conflict is empirically testable, test it — the answer is worth more than either claim.
7. **Regenerate `03`'s index** — the `awk` command lives in its own Index section. A harvest that adds entries without re-running it leaves the lookup surface silently incomplete, and an incomplete index is worse than none: it reads as authoritative while missing exactly the newest entries. Do the same for `04` if it has grown an index by then.

This is a reviewed file edit in the `claude-config` repo. It is the same discipline as the existing CLAUDE.md → Notion sync — edit markdown, review the diff, commit. There is no slash command. The review is the point: the master is what every future project inherits, so nothing reaches it unexamined.

After harvest, the project is done with the knowledgebase. Whatever happens to the site afterward is out of scope.

---

## The correction exception — a false entry is fixed on sight

The harvest rule says the master changes only at go-live. That rule exists to keep **unreviewed** knowledge out of the master. It is not a reason to leave knowledge that is **demonstrably wrong** sitting inside it.

So there is one standing exception. **When an entry is proven false mid-build, correct it in the master immediately** — test, propose the rewrite, review, commit. Do not park the correction below a project seam and wait months for a ritual.

The cost of the alternative is measured, not hypothetical. `02`'s three-filter dynamic-tag example shipped without its `is_string()` guard and fatals on Bricks 2.3.x. TAB hit it and flagged the fix on 2026-05-28; TAB's harvest ran **seven weeks later**. Every project scaffolded in that window inherited a live bug from the file that is supposed to be canon — and the flag was sitting there the whole time. **Flagging is not fixing.**

The discipline is the harvest's, minus the wait:

- **Test before you rewrite.** Rule 6 applies with *more* force here, not less: a refutation is only worth acting on if it is empirical. A Local box settles most schema questions in minutes, and the answer is worth more than either claim.
- **Rewrite, don't extend, when the mechanism is wrong.** Rule 5 says one mechanism, one entry. An entry whose **Why** is false cannot be repaired by appending a correction underneath it — the reader hits the wrong explanation first and stops there.
- **Keep the original provenance.** The incident was real even when the diagnosis wasn't. Record both in `First seen`: what was observed, what actually caused it, and who found which.
- **Mike still reviews the diff.** Unchanged, and non-negotiable. The exception is about *timing*, not about skipping review.
- **Re-sync the project's own copy afterward** — otherwise the project that found the bug keeps carrying it above its own seam. (Snapshot, not subscription.)

**This applies to corrections only.** New knowledge still accumulates below the seam and waits for the harvest. That queue is not bureaucracy: it exists so entries get reviewed *as a batch, against each other*, which is where duplicates, folds and conflicts surface. A false entry has nothing to gain from that wait — it is already known to be wrong, and every day it stays costs a project.

> **(2026-07-15 — first applied.)** VMG's typed-settings entry was harvested carrying an explicit untested-conflict flag against `02`'s schema library. MMHN tested it the same day on a Local box: the entry was wrong on all three of its claims, and it was steering every future project away from a library that was correct. Rewritten at source in `dca8db1` rather than carried to MMHN's go-live, with VMG's provenance kept and MMHN's copy re-synced. The conflict flag is what made this possible — it survived the harvest instead of being smoothed over, which is exactly what the write protocol's "flag the conflict explicitly" clause is for.

---

## Surface selection

There are three surfaces for build work. They are complementary, not interchangeable. Pick by task.

| Task | Surface |
|---|---|
| Turning a token-aware wireframe into a built Bricks page | **WP-CLI build** — the pipeline in `02`. This is the primary path. |
| Discovering an unknown Bricks schema; building or extending a template with Bricks UI controls available | **Bricks builder** — visual, authoritative. Build one example, save, read it back. |
| Bulk operations: batch class creation, global class CSS population, custom query types, dynamic tags, CPT scaffolding, ACF fields | **WP-CLI direct DB edit** — the back half of `02`. |
| Configuring ACSS to a brand: palette, type scale, radius, buttons, focus, tokens | **WP-CLI** — `Database_Settings::save_settings($merged, true)` after `wp_set_current_user(1)`; convention in `01`, procedure in `02`. The **colour palette is the exception** — its shade ladder is dashboard-JS-derived, so prompt for the palette in the ACSS dashboard once (or a PHP derivation helper). Never browser-automate the ACSS dashboard for the scriptable settings. |

When a WP-CLI edit stalls — Bricks stripping writes, a specificity war, a schema you don't have — the fix is almost always to drop into the builder, discover the schema there, and resume via WP-CLI. The builder is the source of truth for shapes; WP-CLI is the mechanism for replicating them at scale.

The paste-into-Bricks-UI step from the older wireframe workflow is the thing the pipeline is built to eliminate. Default to the WP-CLI build path.

---

## Evidence discipline — a silent result is not a clean result

A command that returns nothing has two readings, and they are opposites:

1. **It ran and found nothing.** The absence is the finding.
2. **It never ran.** The absence is an artifact of the failure.

Reading 2 looks exactly like reading 1 in the scrollback, and it is the more dangerous of the two, because it converts *no evidence* into *evidence of none* — and that conclusion then gets written into a file that outlives the session. `grep` finding no matches and `grep` being killed by the OOM reaper both print nothing and both exit without fuss.

**The rule: before an empty result becomes a claim, prove the command completed.** Cheap ways, in rough order of preference:

- **Check the exit status**, not just the output. A killed process exits 137 (SIGKILL) or 143 (SIGTERM); `grep` finding nothing exits 1. These are distinguishable.
- **Make the command say something even when it finds nothing** — count lines, echo the input size, print a sentinel. A run that prints `0` proves it ran; a run that prints nothing proves nothing.
- **Test the harness on a case you know matches.** If the positive control comes back empty too, the command is broken, not the corpus.

This is the same failure shape as the `wp eval` fatal in `03` (a PHP fatal inside `eval`'d code swallows the output and the writes don't land) — different cause, identical trap. When you catch yourself about to write "there are zero X in the whole Y", that sentence is load-bearing and needs an exit status behind it.

The corollary is about tone, not tooling: **when a prior conclusion turns out to rest on a command that failed, the conclusion is unverified, not merely unlucky.** It may still be true — re-run and find out — but it does not get to keep its confidence in the meantime.

> **(NLTA, 2026-08-05 — first recorded.)** A session searching an error log for one plugin's fatals had its `grep` killed by memory pressure. The empty output was read as clean and the finding "zero references in the whole log" was written into the project `CLAUDE.md`. The session noticed the kill and started to re-run the search — and was itself killed before finishing. The re-run, done the next session, found 23 matching lines. Every one turned out to predate the window in question, so the conclusion held and nothing shipped wrong; the point is that it held **by luck**, and for a day the file carried a claim whose evidence had never existed.

---

## The two core principles

Two rules govern almost everything in a build. They are easy to confuse because both say "don't just do the obvious thing" — but they apply at different moments. Keep them distinct.

**The golden rule governs discovery — how to learn a shape.** The typed-settings rule governs expression — how to apply a value. One is "I don't know the schema." The other is "I know what I want; how do I apply it."

### The golden rule — never guess a Bricks schema

**Never guess Bricks schemas. Replicate a shape that has been saved through the builder — either by building a minimal example yourself, or by reading back one that already exists in the project.**

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

This knowledgebase is built as plain markdown so it works now. The intended end state is a Skill that Claude Code auto-loads when the work is Bricks/WordPress/ACSS — no manual pointing, no command. When that promotion happens, this file's content largely becomes `SKILL.md` and the other five become supporting files. The file structure is designed so that wrap is mechanical. Until then, this knowledgebase is loaded by being referenced from each project's CLAUDE.md or pointed at directly at session start.
