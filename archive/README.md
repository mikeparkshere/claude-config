# Archived

Files here are superseded and kept for history only.

- SESSION-PLAYBOOK.md, session-openers.md — absorbed into knowledgebase/00-operating-rules.md (memory model + session lifecycle sections), 2026-05-24.
- wireframe-to-bricks-rules.md — superseded by knowledgebase/02-build-pipeline.md (front half), 2026-07-24. This was the Claude.ai paste-into-Bricks workflow; `02` was built specifically to eliminate the paste step. **Do not follow §5.4** — it prescribes 1366px and 1024px breakpoints that do not exist on a default Bricks install, and `02` documents that unregistered breakpoint keys save to the DB and emit zero CSS silently. Its §6.3 also contradicted its own §5.2 and was patched externally by claude-code-bricks-playbook.md §7; the corrected pattern is in `01`.
- wireframe-to-bricks-workflow.docx — superseded by knowledgebase/02-build-pipeline.md, 2026-07-24. The .docx companion to wireframe-to-bricks-rules.md: same Claude.ai paste-into-Bricks workflow, same caveats.
- claude-code-bricks-playbook.md — superseded, 2026-07-24. Surface-selection table → `00`; golden rule → `00` and `02`; auth requirement → `00` and `02`; §4 schema library → `02`; §5 failure modes → `02`; §6 specificity cheat sheet → `01`. §5.3's stretched-link mechanisms were harvested into `03` (CSS general) at archive time rather than dropped — `01` carried the rules without the reasoning.
