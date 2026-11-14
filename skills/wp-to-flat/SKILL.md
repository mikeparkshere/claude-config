---
name: wp-to-flat
description: "Use when porting a live WordPress brochure site (roughly 15 pages or fewer, no blog, no ecommerce, no member logins) to a hand-built flat PHP site on RunCloud: harvesting its content, media and SEO, reducing builder output (Bricks today) to readable page notes, planning redirects, rebuilding against flatsite.md, and proving SEO parity before cutover. A ground-up rebuild that treats WordPress as a content source only. NOT for maintaining an existing flat site (flatsite.md), WordPress builds (mpd-bricks-stack), or sites that need a CMS."
---

# WordPress → flat site

A ground-up rebuild. WordPress is a **source of content, media, SEO data and brand**, nothing more: none of its markup,
class names or paths reach the new build. The finished site follows **`flatsite.md`** (structure, security, staging,
launch). This skill covers getting from WordPress to there without losing content, rankings or links.

## Scope check first

Brochure sites, about 15 pages or fewer, minimal forms. **Stop at Gate 1** if the harvest finds published blog posts,
ecommerce, member or course areas, or anything that needs a CMS. The inventory's verdict line and FLAGS say so.
→ `reference/scope.md`

## Phases and gates

Stop at each gate and wait for Michael.

1. **Harvest** (read-only): `scripts/harvest.sh --path <wp root> --out <dir> --base-url https://<live host> --screens`
2. **Reduce**: `node scripts/reduce.js <dir>`. Read `reduced/inventory.md` and `reduced/pages/`; **never** `raw/`.
3. **Gate 1, inventory.** Fill `MIGRATION.md` (`templates/MIGRATION.md`) from the inventory: candidate verdict, page
   list, redirect plan (`node scripts/url-map.js <dir>`), form plan, every FLAG answered.
4. **Design system.** Brand tokens from the brand guide, or proposed from the live site's computed styles.
5. **Gate 2, tokens.** Michael approves the token block.
6. **First page.** The home page, complete: partials, components, motion, responsive, on the staging host.
7. **Gate 3, first page.** Nothing else starts until Michael approves it.
8. **Remaining pages, forms, redirects, sitemap, robots.** Fill `components.json` as you build, not after.
9. **QA:** `scripts/scrub-lint.sh <site root> --harvest <dir>` and
   `node scripts/parity-check.js <dir> --new https://<staging host> --staging`.
10. **Gate 4, pre-cutover.** Zero lint hits, zero parity FAILs, every parity REVIEW answered.
11. **Cutover** per `flatsite.md` → Launch, Path A (domain move). Rerun parity against production.
    → `reference/cutover.md`
12. **Skill retro.** Propose edits to this skill from the port's decision log and anything redone; add a dated,
    client-neutral entry to `reference/lessons.md`. Commit only what Michael approves.

## Hard rules

- **Never read raw builder data into context.** Bricks stores pages in `_bricks_page_content_2`; those blobs evict
  everything else. Scripts write raw data to `raw/`; Claude reads `reduced/` only.
- **Harvest is read-only.** No writes, plugin changes or option updates on the live WordPress site.
- **Rendered pages and screenshots are visual reference only.** Never copy markup from them.
- **The scrub lint must pass before Gate 4.** A failing lint is a blocker, not a note.
- **Keep the harvest out of git and out of this folder.** It holds client content. `harvest.sh` refuses a path inside
  the skill.
- **No client names or project details in skill files.**

## Reference

| File | When |
| --- | --- |
| `reference/scope.md` | Is this site a candidate? |
| `reference/harvest.md` | Running harvest and reduce; what came out; adding a builder reducer |
| `reference/architecture.md` | Build decisions on top of `flatsite.md`: data, components, motion, images, fonts |
| `reference/forms.md` | Porting a form |
| `reference/redirects.md` | The redirect plan and its defaults |
| `reference/scrub-list.md` | The lint, its layers and allowlist |
| `reference/seo-parity.md` | The parity check and reading its report |
| `reference/reversibility.md` | Keeping a path back to WordPress |
| `reference/cutover.md` | WordPress-specific cutover and decommission steps |
| `reference/fixture.md` | The permanent test fixture: what it is, restoring one |
| `reference/lessons.md` | Dated lessons from each port's retro |
