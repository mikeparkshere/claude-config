# Stack: bricks-native

**Status: 0.2.0.** Proven on its first production site (pkjsupport, live 2026-10-07) and extracted from it. The framework package lives at `frameworks/mpd-core/`.

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

## Known limits (0.2.0)

- **Light/dark context switching is undesigned.** The purpose tier is built for it (a context re-points purpose tokens, components don't change), but no mechanism ships yet. The first site that needs a second context decides it.
- **No secondary button, no weighted grids beyond `--grid-1-2` / `--grid-2-3`, no `.eyebrow`.** Each needs a design call; see `frameworks/mpd-core/CHANGELOG.md`.
- **Verified on Bricks 2.4.2**, with WS Form Pro 1.12 and BricksExtras 1.7.4. Re-check the Style Manager row shapes (`02`) on a newer Bricks before trusting a CLI token write.
