# mpd-core

A lightweight framework for **native Bricks** sites: the subset of ACSS v3 conventions Mike actually uses, built on Bricks' own Style Manager and Theme Style, with no Automatic.css. Stack rules: `stacks/bricks-native.md`. History: `CHANGELOG.md`.

**Version 0.2.0.** Verified on Bricks 2.4.2.

## What's here

| Path | What it is | Ships to the site? |
|---|---|---|
| `plugin/mpd-core/` | The plugin: framework defaults Theme Style can't express, the clickable-parent / focus-parent patterns, the WS Form bridge. Consumes tokens, defines none. | **Yes**: copy to `wp-content/plugins/mpd-core/`, unchanged |
| `tokens/values.example.php` | Neutral placeholder values. | **Yes**: copy to the site repo as `values.php` and fill it |
| `tokens/apply-tokens.php` | Values → Bricks Style Manager (palette, purpose colors, every other token). | No, run from here |
| `tokens/apply-theme-style.php` | Values → the site-wide Theme Style (fonts, weights, line heights, framework defaults). | No, run from here |
| `tokens/export.php` | Style Manager → JSON, the committed record of what's live. | No, run from here; the JSON goes in the site repo |
| `setup/seed-pattern-classes.php` | Registers the pattern anchors as pickable, name-only global classes. | No, run once |
| `kitchen-sink/build.php` | A private page proving every token and pattern on the install. | No, run from here |
| `tools/readback.php` | Reads a builder-saved tree to disk and prints a summary plus rule flags. The golden rule's readback, safe for context. | No |
| `tools/drift-check.php` | Dry-runs a page-build script against the live DB and lists the builder edits it would revert. | No |
| `tools/audit/` | The usage audit that scoped the framework (ACSS usage across Local sites). Re-run to re-scope. | No |
| `TOKENS.template.md` | Where a site records why each value is what it is. | **Yes**: as `TOKENS.md` |

Everything that writes output takes a required path (`OUT`, `OUTDIR`). This repo is public: never point one at a folder inside it.

## Starting a site

From the site's WordPress root, with Bricks 2.4+ active and **no ACSS installed**. `F` is this folder.

```bash
F=~/claude-config/frameworks/mpd-core

# 1. Plugin
cp -R $F/plugin/mpd-core wp-content/plugins/ && wp plugin activate mpd-core

# 2. Values: copy, then fill from the brand guide (palette, purpose mapping, type scale, fonts).
#    Register any custom fonts in Bricks first (`02` → bricks_font_faces); reference them by title.
mkdir -p mpd-core && cp $F/tokens/values.example.php mpd-core/values.php
cp $F/TOKENS.template.md mpd-core/TOKENS.md

# 3. Tokens, then the CSS regen in a SEPARATE request (`03`: a same-request regen writes the old palette)
VALUES=mpd-core/values.php wp eval-file $F/tokens/apply-tokens.php            # DRY=1 to preview
wp eval 'wp_set_current_user(1); \Bricks\Assets_Files::regenerate_css_files();'

# 4. Theme Style, then pattern classes (close every builder tab first: classes are single-writer)
VALUES=mpd-core/values.php wp eval-file $F/tokens/apply-theme-style.php
wp eval-file $F/setup/seed-pattern-classes.php

# 5. Prove it
VALUES=mpd-core/values.php wp eval-file $F/kitchen-sink/build.php
#    open /mpd-core-kitchen-sink/ logged in: check it at 360px, a midpoint, and 1366px

# 6. Record what's live, and commit it with values.php and TOKENS.md
OUT=mpd-core/style-manager-export.json wp eval-file $F/tokens/export.php
```

Also set these Bricks settings, which the scripts don't touch: **Disable class chaining = ON**; "Output global class CSS in Class Manager order" stays **OFF** (no utilities compete with BEM classes); CSS loading = external files.

**Verify on the rendered page, not in the database:** `getComputedStyle(document.documentElement).getPropertyValue('--color-accent')` resolves, a container inside a section measures `--content-width`, and the body font is the one you set. Measure narrow widths in a same-origin iframe; Chrome won't size a window below ~500px (`03`).

## Changing a token later

Edit `values.php`, re-run steps 3 and 6, commit. Never hand-edit the Style Manager without re-exporting. If the builder was open, reload it without saving.

## Updating the plugin on a site

Copy `plugin/mpd-core/` over the site's copy and bump nothing by hand: the version lives in the plugin header and `MPD_CORE_VERSION`. Read `CHANGELOG.md` first. A new token in a release means re-running step 3 with the site's values file, after adding the token there.

## Changing the framework

Framework changes happen here, never in a site's copy. Log a gap on the site (`gaps.md`: what was missing, the workaround, the proposal), then fold it into this package with a CHANGELOG entry and a version bump. Nothing site-specific lands here: no brand values, names or content.
