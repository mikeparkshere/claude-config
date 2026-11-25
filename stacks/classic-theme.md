# Stack: classic-theme

**Status: 0.1.0, draft.** First site: ECT (redesign started 2026-11-25). Written before the first build, so every rule here is a decision, not yet a lesson. Revise it at that site's go-live harvest.

A `classic-theme` site runs **no page builder and no Automatic.css**: a hand-built classic PHP theme (template hierarchy + `template-parts/`, not a block theme) kept in git, plus the project's core plugin for all data and logic. Bricks, BricksExtras, Advanced Themer and ACSS are not installed. Layout changes and new pages go through Claude Code; site staff edit content only.

## How this file is used

`00`, read protocol step 4: a project whose `CLAUDE.md` declares `stack: classic-theme` loads this file. Two kinds of rule change:

1. **Every `[stack:acss]` rule** is replaced by the table below.
2. **Untagged rules that are about Bricks are suspended**, because there is no Bricks. The list is under "Untagged rules on this stack". Everything else untagged applies unchanged.

A project `CLAUDE.md` may carry its own overrides; where they differ from this file, the project wins and the difference gets flagged, not silently picked.

Put these lines in the project `CLAUDE.md` overview and keep them current:

```
stack: classic-theme
repo: <org>/<repo> (theme + core plugin)
```

## Untagged rules on this stack

| Rule | On classic-theme |
|---|---|
| BEM, no `__inner` | **Applies.** Every component is a BEM block in its own CSS file |
| Clickable-parent and focus-parent *patterns* | **Apply, as plain CSS** in the theme's patterns file. No global classes to register |
| Phase gates, authorization gradient, compaction safeguards, evidence discipline | **Apply** |
| Core-plugin rule (no logic in `functions.php`) | **Applies, sharpened:** the theme owns presentation only (templates, CSS, front-end JS, `theme.json`, enqueues, theme supports). CPTs, taxonomies, fields, schema data, cron and business rules live in the core plugin. A future redesign should need only a new theme |
| The golden rule (never guess a Bricks schema) | **Suspended**, no Bricks. Its intent carries over as: **never guess a data shape.** Read the field's PHP registration in the core plugin, or the term or meta as stored, before a template reads it |
| Typed-settings-first | **Suspended.** There are no builder controls. Values come from tokens in CSS; `theme.json` holds only what the post editor needs |
| Bricks write cautions (`_bricks_page_content_2`, `--user=2`, `_edit_lock`, `bricks_global_classes`, Used CSS, readback tooling) | **Suspended.** They come back only during a cutover off Bricks, while the old site is still live |

## Replacements for `[stack:acss]` rules

| `[stack:acss]` rule | classic-theme replacement |
|---|---|
| Pinned brand tokens live in ACSS Global CSS | Tokens are plain custom properties in the theme's `tokens.css`, pasted from the brand guide's token block. The brand guide is the source; `tokens.css` records it |
| Irregular heading ladders → ACSS per-level Font Size Overrides | `--h1`–`--h6` defined individually in `tokens.css` |
| Bare `--variable` in a Bricks field expands to `var(--variable)` | Not applicable. Plain CSS: always `var(--x)` |
| `@include clickable-parent` / `@include focus-parent` | The pattern classes in the theme's `patterns.css` |
| ACSS `.container` handles max-width and centering | Theme container classes reading `--container`, `--container-wide` and `--gutter` |
| Theme Style variables resolve from ACSS | No Theme Style. Base element styles live in the theme's `base.css` |
| Child-theme CSS as the third styling surface | No child theme. The theme is the only styling surface, in a fixed layer order: tokens → base (`mpd-core` conventions) → patterns → components |
| ACSS configuration via `save_settings()` | Edit `tokens.css` in git |
| ACSS token map required before a build | The brand guide's token block, pasted into `tokens.css` before the first component |
| ACSS owns the WS Form layer (`option-forms`, `--f-*`) | WS Form keeps its own styler. The theme carries a `.wsf-form` bridge that remaps WS Form's root `--wsf-form-*` vars to purpose tokens. Same approach as `frameworks/mpd-core`'s bridge, copied as CSS |

## Stack facts

- **Root font size is 100% (1rem = 16px).** Locked.
- **`mpd-core` here means its conventions, not its package.** `frameworks/mpd-core` is built on the Bricks Style Manager and Theme Style, so its plugin and scripts don't install on this stack. The naming carries over: `--space-*`, `--text-*`, `--h1`–`--h6`, `--gutter`, a raw palette plus purpose tokens that components consume. Its `.wsf-form` bridge can be copied as plain CSS.
- **No build step.** CSS is plain files using custom properties. JS is vanilla ES modules (`type="module"`, deferred). No jQuery, no bundler unless a project decides otherwise in writing.
- **`theme.json` locks the block editor and does nothing else.** Palette, font sizes and spacing come from the tokens, with custom colors and sizes turned off. Turn the block editor on only for the post types whose body is written in it; everything else stays classic.
- **Fonts are self-hosted WOFF2**, preloaded only for the faces above the fold, `font-display: swap`, with metric-matched fallbacks.

## Build rules

- **Fixtures, not client content.** The repo never holds client data: no records, photos, bios, form entries or dumps. The core plugin ships a seeder (`wp <prefix> seed`, with `--reset` that clears fixtures only). Local runs on fixtures only; production data reaches a dev machine only through a scrub script that swaps names, bios and media first.
- **A component reference page** (a private, noindexed route for logged-in admins) renders every component in every state. Check it before building anything new, and reuse what's there instead of making near-copies.
- **Change flow:** spec or mockup → build on a branch against fixtures → screenshots at 360px and 1440px before and after → staging review → merge → deploy.
- **Escape at output**, every time: `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` for rich text. Templates read data through core-plugin helpers, not raw `get_post_meta`, so the theme never learns field keys it doesn't need.
- **Server-render first.** Interactive features (filters, search overlays) enhance HTML that already works without JS. Filter-state URLs canonicalize to the clean path and are noindexed.
- **Verify on the rendered page, not the template.** Curl the URL with a cache-buster and check the output: markup, schema, `<title>`, canonical, robots.

## Cutover from a builder site

A builder site moves to this stack by a **theme deploy**, not a database migration. Content stays in the production database; only the presentation changes. Before cutover, every page whose copy lives only inside builder elements has to move to `post_content`, ACF fields or options, or it renders blank once the builder is off. Audit for empty `post_content` first. Rehearse the activation on a staging clone, then: back up, deploy the theme and core plugin, activate, deactivate the builder and ACSS, purge caches, flush rewrites, resubmit the sitemap.

## Known limits (0.1.0)

- **No deploy mechanism is settled.** The first site decides it (checkout + rsync, or a symlinked checkout). Record it here.
- **No shared theme starter yet.** The first site's theme becomes the starter once a second site needs one.
- **Dark mode and second contexts are undesigned.**
