# Stack: bricks-native

**Status: 0.3.0.** Proven on its first production site (pkjsupport, live 2026-10-07) and extracted from it, then on a first ACSS retrofit (MFH, live 2026-11-04), then a live retrofit fixed forward on production (ETS, 2026-11-10). The framework package lives at `frameworks/mpd-core/`.

A `bricks-native` site runs Bricks with **no Automatic.css installed**, ever: not deactivated, not "for reference." Tokens live in the Bricks Style Manager. A small framework plugin, **mpd-core**, carries the subset of ACSS v3 conventions in actual use (measured by an audit across five sites).

## How this file is used

`00`, read protocol step 4: a project whose `CLAUDE.md` declares `stack: bricks-native` loads this file, and every `[stack:acss]` rule in the knowledgebase is replaced by the table below. Untagged rules apply unchanged. A project `CLAUDE.md` may carry its own copy of the table as a per-project override; where they differ, the project wins, and the difference gets flagged rather than silently picked.

## Starting a site on this stack

Follow `frameworks/mpd-core/README.md`: install the plugin, copy `tokens/values.example.php` into the site repo and fill it from the brand, run the token, Theme Style and pattern-class scripts, build the kitchen sink, verify it, and commit the export. The site's own `TOKENS.md` (from `TOKENS.template.md`) records why each value is what it is.

Put these lines in the project `CLAUDE.md` overview, and keep them current:

```
stack: bricks-native
framework: mpd-core <version> (frameworks/mpd-core in claude-config)
```

## Replacements for `[stack:acss]` rules

| `[stack:acss]` rule | bricks-native replacement |
|---|---|
| Pinned brand tokens live in ACSS Global CSS | Tokens live in the Bricks Style Manager. The site's `values.php` is the input; the JSON export is the committed record of what's live |
| Irregular heading ladders → ACSS per-level Font Size Overrides | `--h1`–`--h6` defined individually in the values file |
| Bare `--variable` in a Bricks field expands to `var(--variable)` | **Always write `var(--variable)`.** Bare `--variable` fails silently |
| `@include clickable-parent` / `@include focus-parent` | The mpd-core pattern classes, `clickable-parent` and `focus-parent--shadow` / `--outline`, registered as name-only global classes and added alongside the element's BEM class |
| ACSS `.container` handles max-width and centering | Bricks Container element; width from the Theme Style **container group's `width`** = `var(--content-width)` |
| Theme Style variables resolve from ACSS | Same Theme Style values; variables resolve from the Style Manager |
| Child-theme CSS as the third styling surface | mpd-core plugin CSS, for framework-level rules only; component styles stay in BEM classes |
| ACSS configuration via `save_settings()` | `tokens/apply-tokens.php` and `apply-theme-style.php`, driven by the site's values file |
| ACSS token map required before a build | The site's `TOKENS.md` plus the Style Manager export |
| ACSS owns the WS Form layer (`option-forms`, `--f-*`) | WS Form keeps its own styler. mpd-core CSS carries a `.wsf-form` bridge that remaps the styler's root `--wsf-form-*` vars (plus a few field vars) to purpose tokens (`03` → "WS Form — skin it by overriding root `--wsf-form-*` vars") |

## Stack facts

- **Root font size is 100% (1rem = 16px).** Locked.
- **Framework defaults live in the Theme Style first** (typed-settings-first): root size, type, links, focus, container width, section padding, container/block **row-gap only**, rich-text contextual spacing, the primary button. The plugin CSS holds only what Theme Style can't express: `outline-offset`, the code-element font, the builder-list and `dd` resets, the sticky-footer page shell, the patterns and the WS Form bridge. It consumes tokens and never defines them.
- **No utility classes.** The audit showed almost no real use. Component styling lives in BEM classes that consume tokens.
- **Names say what a token is for.** ACSS names are kept where they already do (`--space-*`, `--section-space-*`, `--text-*`, `--h1`–`--h6`, `--content-width`, `--gutter`, `--grid-*`, `--focus-*`), so habits carry over. **Color is two-tier:** a raw palette (never used by components; steps named by measured lightness) and purpose tokens (`--color-bg`, `--color-surface*`, `--color-border*`, `--color-text*`, `--color-accent*`, `--color-link*`, `--color-focus`, and status roles with `-tint`) that components consume. Don't assume an ACSS color name resolves here.
- **Utilities ACSS shipped and mpd-core doesn't:** `.hidden-accessible` (use Bricks' `.screen-reader-text`), `-trans-N` colors (a purpose token gets added when a real use appears), the `-rgb` / `-h/-s/-l` partials, `.eyebrow`, `.btn--secondary`.

## Build rules that follow from the defaults

- Blocks and containers carry a `row-gap` default only, so **any wrapping row layout sets its own row-gap.**
- Container width comes from the Theme Style container group; `containerMaxWidth` only reaches root containers.
- Paragraph and heading margins are zeroed; flow spacing exists only inside rich text (`.brxe-text`, post content). Don't set contextual spacing's Fallback (`03`).
- **Per-instance values travel as data** (`01`): a custom property in a `style` attribute, consumed by one global class. Never element-bound CSS.
- **CLI and builder are single-writers on `bricks_global_classes`** (`03`): gate a CLI class write on `_edit_lock` < 150s, checked before the write.
- **If pages are built from scripts, dry-run each script against the live DB before re-running it** (`tools/drift-check.php`; `03`). People edit in the builder between runs.
- **Read trees back through `tools/readback.php`**, never into the session. It writes the tree to disk and prints the outline, every settings key, and rule flags (element-bound styling, bare `--var`, literal colors, missing BEM classes, ARIA).

## Retrofitting an ACSS site

First done on MFH, 2026-11-04: 16 trees and 495 elements in one working session. Measure before you commit to it: count the ACSS classes and variables actually used on published trees, excluding revisions, which can inflate the count tenfold.

1. **Take a baseline first:** HTML and screenshots of every URL, plus a database export. Run updates before the swap and compare, so update fallout doesn't get blamed on the port. No browser on the box is not a reason to skip screenshots: `04` → "Headless Chrome on a RunCloud box without root".
   **Read the values from the site's compiled ACSS, not its settings screen.** Fluid tokens are `calc(min + slope * ((100vw - 22.5rem) / 50))`, so the denominator gives the real range (ETS: 360–1160px, with `--content-width` 72.5rem, not the framework's 1366). Set `meta.fluid_min/max` and `--content-width` to match, or every fluid token shifts. Steps whose min exceeds their max (ACSS `--space-xs`, `--text-xs`, `--h6` on some installs) shrink as the viewport grows; pin them.
2. **Deactivate ACSS; don't run both.** `--space-*`, `--text-*` and `--h*` collide. On a site with an audience, do this behind a maintenance page that depends on neither stack (`04`).
3. **Set the root size explicitly.** A Theme Style `typographyHtml` of `var(--root-font-size)` now resolves from the Style Manager. Unresolved, Bricks can fall back to 62.5%, which shifts every rem.
4. **Map colors by role, not by name.** One ACSS name can be a fill, a border and text at once. Read each use's setting path. Rewrite CSS inside Code elements too, then re-sign them (`03`). **Walk everything that holds settings**, not just `_bricks_page_*_2`: `bricks_components[*].elements` (a placement is an empty section with a `cid`, `03`), `_bricks_page_settings` (e.g. `siteBackground`), and every class that survives the prune, not only those the trees reference.
5. **Recover class roles from the ID.** Refs to removed ACSS classes are `acss_import_<name>`, so `btn--primary` still tells you the element was a primary button: set `style: primary` before dropping it. Also strip leftover color `id` / `name` keys and `_mappedId` (`03`).
6. **Guard the write:** every `var()` in classes and trees must resolve to a token, or stop. Then read back from a fresh process; a real administrator is required for tree writes (`00`). After the swap, run the same check on the **rendered pages**: collect each page's inline styles and stylesheets (including Perfmatters' `data-pmdelayedstyle` links), and list every `var(--x)` used without a fallback that nothing defines. Expected noise: core's runtime `--wp--*` and builder-tool vars. Anything else is a missed reference.
7. **Clear the child theme:** `:root` type ladders (they load last and win) and any `* { border-color … !important }`, which beats every component border.
8. **Check what ACSS was quietly covering — and plan a parity layer for it.** ACSS styles Bricks elements **by element type** (section flex and `--container-gap`, both-axis gaps on containers/blocks/divs, the `[class*="btn--"]` button box, rich-text flow), which no DB scan sees, so a port that passes every structural check can still look wrong. Extract the rules from compiled ACSS before the swap, then restore them in a labeled child-theme parity layer with ACSS's selectors and specificity, and set the Theme Style's contextual spacing to ACSS's values. The layer is a **sanctioned exception** to this stack's no-child-theme-CSS rule: transitional, closed to new rules, and retired by the site's next design pass (ETS, Michael, 2026-11-10). Full rule list and the button-border trap: `03` → "Removing ACSS: it styles Bricks elements by element type". Also check Gutenberg post content, WS Form skinning, and outline buttons (`03` → "A Bricks outline button takes the Theme Style's primary button text color").
9. **Clear Perfmatters' used-CSS snapshots after every CSS write** if Remove Unused CSS is on: `wp perfmatters clear-used-css` (`04`). They outlive a Bricks regen.
10. **`seed-pattern-classes.php` reports "all present" when ACSS imported the same names** (`focus-parent--shadow` with id `acss_import_focus-parent--shadow`). That works, since mpd-core's CSS targets names, but those ids then survive the prune by name. Map imported templates' pattern-class ids onto the site's existing ids by name.
11. **Open every builder page after the swap.** The builder can peak near a stock 128M limit (`04`).

## Known limits (0.3.0)

- **A single-context dark site needs no mechanism** (MFH): purpose tokens simply take dark values. Placeholder values are light-first only.
- **Light/dark context switching is undesigned.** The purpose tier is built for it (a context re-points purpose tokens, components don't change), but no mechanism ships yet. The first site that needs a second context decides it.
- **No secondary button, no weighted grids beyond `--grid-1-2` / `--grid-2-3`, no `.eyebrow`.** Each needs a design call; see `frameworks/mpd-core/CHANGELOG.md`. ETS needed two more variants (ACSS `btn--primary-dark` / `-light`) and solved them with BEM modifier classes on the component.
- **Outline buttons aren't styled by the Theme Style.** The primary typography color applies to `.bricks-color-primary` too, so an outline primary gets on-accent text on a transparent fill (`03`). Until the framework ships an outline rule, style outlines on their class.
- **Purpose tokens a retrofit tends to need** beyond the framework set (ETS, 14 of them): an inverse context for a dark footer and photo hero (`color-bg-inverse`, `color-text-inverse`, `-inverse-muted`), a non-accent "action" fill for form submits, overlay and shadow colors, an accent border. Candidates for the framework once a second site needs them.
- **Verified on Bricks 2.4.2 and 2.4.5**, with WS Form Pro 1.12 and BricksExtras 1.7.4 (2.4.2), and WS Form Pro 1.12.18 and 1.12.19 (2.4.5). Re-check the Style Manager row shapes (`02`) on a newer Bricks before trusting a CLI token write.
