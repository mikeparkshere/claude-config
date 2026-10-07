# mpd-core tokens — {{project-slug}}

> Copy to the site repo as `TOKENS.md`. The values live in the site's `values.php`; **this file records why.** Fill the source column as you set each value, and get the token set approved before the first build (it's a checkpoint, the same as an ACSS token map).

Status: {{draft | approved by Mike on <date>}} · mpd-core {{version}}

**Inputs:**
- The brand guide: {{path}}
- Anything carried from a sibling (name the sibling only in the gitignored notes, never here if the repo ships)

**Source key:** **brand** = from the brand guide · **framework** = mpd-core default, unchanged · **derived** = computed by a rule recorded here · **Mike** = decided by Mike on the date given.

## Naming rules (framework)

1. **A name says what the token is for**, not what its value is. `--color-surface`, not `--gray-900`, in anything a component touches.
2. **Keep an ACSS name when it already passes rule 1:** `--space-*`, `--section-space-*`, `--text-*`, `--h1`–`--h6`, `--content-width`, `--gutter`, `--grid-*`, `--focus-*`, `--radius-*`. Habits carry over.
3. **Color has two tiers.** The **palette** holds raw values and is never used in components. **Purpose** tokens reference the palette and are what components use. A context (a light section, print) re-points purpose tokens; components don't change.
4. **Palette steps are measured, not chosen:** step = (1 − OKLCH L) × 1000, rounded to 10 (`gray-780`, `blue-470`). A new color slots in by measurement.
5. **Prefixes group the picker:** `--color-*` for purpose colors, `--font-*` for families, hue names for the palette.
6. **The purpose names are the framework.** Add one when a real purpose appears (`--color-overlay` for a modal scrim, with its percentage recorded here); don't rename the set per site.

## Decisions

1. **Root font size: 100% (1rem = 16px).** Locked across the fleet.
2. **Fluid range:** {{360}} → {{1366}}px (`meta` in the values file). The framework default is 360 → 1366, Mike's standard; change it only if the brand guide says so.
3. **Type scale:** {{brand sizes are the desktop maximum; mobile minimums approved by Mike / …}}. Every heading level is strictly smaller than the one above it at every width; check the minimum of each level against the maximum of the next one down.
4. **Heading ladder:** `--h1`–`--h6` defined individually, plus `--display` above `--h1` if the brand has one.
5. **Palette:** brand values only, no generated shades.
6. **Radius:** {{square / the framework scale / …}}. `--radius` is what components use by default.
7. **Fonts:** {{custom font titles as registered in Bricks, or the system stack}}.

## Layout, spacing, grid

Framework defaults (Mike's standard ACSS output, 360–1366, 100% root). Record only what changed.

| token | source | note |
|---|---|---|
| `--content-width` | framework | 1366px |
| `--reading-width` | {{}} | prose measure |
| `--gutter`, `--space-*`, `--section-space-*` | framework | `--space-xs` is static on purpose: ACSS's never scaled |
| `--grid-1` … `--grid-6`, `--grid-1-2`, `--grid-2-3` | framework | add a weighted grid here when a layout needs one, and log it for the framework |

## Typography

| token | min → max px | source |
|---|---|---|
| `--display` | {{}} | {{}} |
| `--h1` … `--h6` | {{}} | {{}} |
| `--text-xxl` … `--text-xs` | {{}} | {{}} |
| `--line-height-body` / `--line-height-heading` | {{1.6 / 1.2}} | {{}} |
| `--font-sans` / `--font-mono` | — | for CSS in BEM `_cssCustom`; typed Bricks font controls use the registered custom font (`03`) |

Weights and letter-spacing live in the values file's `theme_style` block (typed Theme Style controls, not tokens): {{body 400, headings 600, buttons 500}}.

## Color — palette tier

| token | value | brand name / role |
|---|---|---|
| {{`--hue-step`}} | {{#hex}} | {{}} |

## Color — purpose tier

Contrast is measured, not assumed. Text roles need **4.5:1** on every surface they sit on (`--color-bg`, `--color-surface`, `--color-surface-raised`); `--color-border-input` and `--color-focus` need **3:1** (WCAG 1.4.11). Record the worst case.

| token | → palette | worst-case contrast | use |
|---|---|---|---|
| `--color-bg` / `-surface` / `-surface-raised` / `-surface-hover` | {{}} | — | page, cards, menus and fields, row hover |
| `--color-border` / `-border-strong` | {{}} | decorative | hairlines, dividers |
| `--color-border-input` | {{}} | {{≥ 3:1}} | form control edges |
| `--color-text` | {{}} | {{}} | body and headings |
| `--color-text-secondary` | {{}} | {{}} | supporting text, placeholders |
| `--color-text-muted` | {{}} | {{≥ 4.5:1 everywhere}} | dates, metadata |
| `--color-text-on-accent` | {{}} | {{}} | text on accent fills |
| `--color-accent` / `-hover` / `-active` / `-tint` | {{}} | {{}} | buttons, selected states |
| `--color-link` / `-link-hover` | `var(--color-accent*)` | {{}} | links |
| `--color-focus` | {{}} | {{≥ 3:1}} | focus ring |
| `--color-success` / `-warning` / `-danger` / `-info` / `-neutral`, each with `-tint` | {{}} | {{fg on its tint}} | status roles. Map a site's own lifecycle onto these in BEM classes, not in new tokens |

## ACSS → mpd-core (habit map)

| ACSS | mpd-core |
|---|---|
| `--space-*`, `--section-space-*`, `--gutter`, `--content-gap`, `--grid-gap`, `--text-*`, `--h1`–`--h6`, `--content-width*`, `--width-m/l/xl`, `--grid-*`, `--focus-*`, `--radius-*` | same |
| `--radius-circle` (50vw, really a pill) | `--radius-pill` |
| `--radius-50` | `--radius-circle` (a true 50%) |
| `--text-font-family` / `--heading-font-family` | `--font-sans` |
| `--primary` / `--primary-hover` | `--color-accent` / `--color-accent-hover` |
| `--base` as page / surface | `--color-bg` / `--color-surface` / `--color-surface-raised` |
| `--base-light`, borders | `--color-border` / `--color-border-strong` / `--color-border-input` |
| text colors | `--color-text` / `-secondary` / `-muted` |
| `--link-color` | `--color-link` |
| `--success` `--warning` `--danger` `--info` | `--color-success` … plus `-tint` |
| `--*-trans-N` | a purpose token when a real use appears |
| `--secondary`, `--accent` (ACSS roles) | none: name the purpose instead |

## Counts

{{n}} tokens: palette {{n}}, purpose {{n}}, everything else {{n}} (`apply-tokens.php` prints the totals on every run, `DRY=1` included).
