# 01 — Stack Conventions

**How Mike builds. Settled ground — no incident attached, changes only by deliberate decision.**

Verified against Bricks 2.3.4 / ACSS 3.3.6. V1 baseline: 2026-05-24.

---

## The stack

WordPress + Bricks Builder 2.3.4+ + Automatic.css (ACSS) 3.3+ + ACF Pro + WS Forms Pro + RankMath Pro + Perfmatters, hosted on RunCloud / DigitalOcean. Claude Code via VSCode Remote-SSH is the build tool.

Code ownership is strict and non-negotiable:

- **All custom functionality** lives in the project's core functionality plugin — never in the theme or `functions.php`. ACF field groups are registered programmatically via `acf_add_local_field_group()` in the plugin, never UI-only. Custom post types, taxonomies, hooks, helper functions, Bricks dynamic tags — all plugin.
- **Presentation** lives in the child theme.
- **No WooCommerce customizations in the theme directly.**
- BEM naming throughout.

The reason for the plugin/theme split: field groups and functionality defined in PHP travel with the codebase between local, staging, and production. UI-only definitions do not, and recreating them by hand at cutover is error-prone.

---

## Styling layers — the priority order

This is the most-violated rule in the build, named as a core principle in `00` and stated here in full. Before writing any CSS for anything, check whether Bricks exposes a typed control for it. If it does, use the control.

**The order, always:**

1. **Typed Bricks setting, via the UI control.** Padding, margin, gap, border, background, typography, grid, alignment — if the value can be typed into a Bricks panel, it goes there. This is the first choice every time, not a fallback.
2. **`_cssCustom` on the specific element or class.** Only when no typed control can express the value — `justify-items: center`, a `max-height` on a figure+img, a narrow per-breakpoint override the typed schema cannot reach. Scoped to where it is used, for that use case.
3. **Child theme `style.css`.** When the need is genuinely global and site-wide — brand context systems, site-wide utilities, link policy. Not element-specific. Pinned custom brand tokens are the one exception at this layer: they live in ACSS Global CSS, not the child theme — see "Pinned custom tokens" below.

**Why the order matters.** `_cssCustom` is the fast instinct and it gives more control. It is also less maintainable the moment anyone else touches the site. Inline custom CSS is invisible in the Bricks UI — a junior dev or a client working through the panels cannot see it, cannot edit it, and will not know it exists. It accumulates as cruft that has to be swept later. A typed setting is visible, editable in the panel, and survives a handoff intact. The rule is about what is maintainable to inherit, not what is fastest to write.

**Styled elements get a class — always.** The layer order above says *what kind* of rule to write; this says *where it attaches*. **If an element has any CSS applied to it, that CSS goes on a Global Class, and the element gets that class.** Never leave styling bound to the element itself. This holds for typed settings and `_cssCustom` alike, and it holds even when the element is used exactly once — "it's a one-off" is not an exemption.

Why, mechanically: styling typed onto an element emits an **ID selector**, `#brxe-<id> {…}` at specificity `(1,0,0)` (see `03`). That is the worst of all worlds — it cannot be reused, it does not appear in the Style Manager, a client or another dev working through the panels will not find it, and it outranks every class rule anyone writes later, so the first override attempt fails for reasons that are invisible from the CSS. A Global Class emits at `(0,1,0)`: reusable, listed, overridable, and greppable.

The practical test when building: after typing a value into a Bricks panel, look at what is selected in the class bar. If it is the element and not a class, stop and make the class first. The corollary is that structural elements carrying **no** CSS need no class — a Section whose only class is ACSS `section--m`, a wrapper that exists purely to nest. Empty typed settings (`[]`) are the correct signal that nothing was overridden.

Templates in particular: give every styled element a template-specific class even when it is unstyled today, so future styling has a named hook instead of arriving as element-bound CSS. Where a shared class and a template-specific class both apply, list the shared one **first** and the specific one **second** — Bricks keys global-class CSS in first-encountered element-class order, so the later class wins at equal specificity.

**Component vs site-wide.** Component-scoped layout — grid, gap, padding, alignment, typography on a specific block — lives in that block's Bricks Global Class typed settings. Site-wide context systems — dark-section overrides, brand button systems, a `.display em` accent, a `.bg--primary` system — live in the child theme `style.css`. The split is: does this rule belong to one component, or to the whole site.

**Lean on the platform.** Beyond CSS specifically: use Bricks built-ins (Divider, Icon, SVG) over DIY substitutes; use ACSS scale tokens over magic rem values; use typed `_border` and `_background` schemas over `_cssCustom`; prefer Grid with named ACSS templates (`var(--grid-2-1)`) over flex split layouts. Prune typed settings to deliberate deviations only — an empty `[]` is a valid signal that nothing was overridden. Before creating a new utility class, check whether a project class already exists for it.

---

## Skinning third-party UI — check ACSS first, then bridge the token tier

Two steps, in this order. Getting them the wrong way round means rebuilding a system the framework already ships.

**1. Ask whether ACSS already owns this surface.** ACSS covers more third-party UI than its name suggests — most importantly it ships a **first-class WS Form layer**, gated by the `option-forms` setting: roughly 240 rules driven by a dedicated `--f-*` token namespace, made context-aware by the `.form--light` / `.form--dark` utility classes, covering inputs, labels, legends, help, placeholders, focus, the required marker, every button role, checkboxes, radios, selects, ranges, progress bars and tabs. Both context classes are registered as Bricks global classes by the ACSS import, so they are already in the picker.

Where ACSS covers it, **brand through `--f-*` and put the context class on the wrapper.** Do not remap the plugin's own root variables as well: ACSS's rules sit at `(0,2,0)` and above, a `.wsf-form` remap is `(0,1,0)`, and ACSS wins wherever the two touch the same property — so a parallel bridge is dead code that looks live. Starting point: `knowledgebase/assets/acss-form-brand.css`.

⚠️ **A form wrapper with neither context class gets none of it** and renders the plugin's stock skin. Every form needs one.

⚠️ **The context class describes the FORM, not the section behind it.** `.form--dark` means a dark form — dark fills, light text. A white-fielded form sitting on a navy band is still a *light* form and takes `.form--light`, with only the label/legend/help text — which render outside the field, on the dark surface — overridden. Choose by the class's own defaults, not by the background: pick the opposite and you spend the build overriding them back one token at a time, since every state (background, text, placeholder, border, and their hover twins) is an independent token that surfaces separately. That reads as the framework fighting you and is not.

**2. Only where ACSS does not cover it, bridge the plugin's token tier.** Find out whether the plugin exposes variables of its own before writing a single rule against its classes. Modern UI plugins increasingly do, and the ones that do usually declare their internals at **zero specificity on purpose** — `:where(...)` — precisely so author overrides win without a fight.

```bash
curl -s <url> | grep -oE '\-\-<prefix>-[a-z0-9-]+\s*:' | sort -u
# then read the compiled stylesheet to see WHICH selector declares them —
# that tells you the specificity you have to beat, which is often zero
```

Remap the roots in one block and let the plugin's own derivation repaint everything downstream. This is the same move as overriding an ACSS token rather than fighting an `automatic-bricks.css` rule (`03`): override what a rule *consumes*, not the rule. It is stronger here, because the component layer is usually generated — a ramp built with `color-mix()` off a handful of semantic roots, so changing a root recomputes every shade.

**Why component rules are the wrong layer either way**, even when they appear to work: plugin stylesheets frequently enqueue *after* the theme, so equal specificity loses on source order and every override has to escalate. Hand-written component skins also accumulate dead selectors against class names that were guessed and never existed.

**The two-context pattern.** Anything appearing on both light and dark sections needs a light default plus a dark override, opted into with a wrapper class — ACSS's `.form--light` / `.form--dark` where it applies, otherwise a **generic** class of your own, never a project BEM class. Scoping to one layout is what stops it being reusable. In the dark block, flip only what renders *outside* the control and pin the control's interior. Remapping a "base" colour wholesale is the classic error: base usually feeds the field text as well as the labels, so input text goes light on a light fill and vanishes.

**Do not assume a framework default is accessible.** ACSS's own form defaults include an input border that fails 3:1 on white, and a focus colour and required marker that are light-surface brand colours reused unchanged on dark. The artifact carries the checklist; run it every time, because the ratios are per-brand and only the list travels.

**Guard the semantic tier.** A governance-minimal palette (above) enables only the colour slots the brand uses, so `--danger` / `--success` / `--warning` / `--info` may not exist on a given project — and ACSS's own form defaults reference some of them. Confirm the slots a default depends on are enabled, or pin the value explicitly.

Mechanism, incident and the real class names: `03` → *"WS Form — skin it by overriding root `--wsf-form-*` vars"*, which remains correct for the no-ACSS-coverage case.

---

## Motion — the animation toolkit

Scroll reveal on this stack is the in-house toolkit, **not** Bricks' or Advanced Themer's animation systems: ~8 KB, dependency-free, and the same polish without the weight. Files and the full guide: `knowledgebase/assets/animations/`.

**Copy from the knowledgebase, never from another project.** The framework circulated for months in a pre-hardening state — its own review had listed the fixes and they were never folded back — so each project that copied a sibling inherited the same defects. The hardened build is the one in `assets/`.

**The builder guard is two-tier and the tiers are not interchangeable.** General child-theme CSS skips only the builder *panel* (`! bricks_is_builder_main()`), because brand tokens and signature devices need to render in the canvas. The animation layer skips *every* builder context, because its hidden state is `opacity: 0` and would blank the canvas. Collapsing them either way breaks something: tighten the first and the canvas loses its tokens; loosen the second and it goes blank.

**Anything whose resting state is invisible needs a fail-safe gate.** The hidden state is scoped under a class set by an inline `<head>` script, so no-JS means nothing is hidden. This generalises past animation: any technique that hides content and reveals it with script must fail open, or a script failure becomes invisible content for users and crawlers alike.

**Anything applied by JS needs an RUCSS exclusion.** Used-CSS tooling cannot see a class that only ever appears at runtime, strips the rule, and leaves the content hidden for good. Same failure as the gate, arriving from the other direction.

**Restraint is the brand.** One reveal per section; stagger for card grids; never on header, nav, sticky elements, footer utility rows, form fields or anything above the fold. Roll out template-by-template so a vocabulary mistake surfaces on one page.

---

## Layout structure

Every section follows one nesting pattern:

```
SECTION (Bricks Section element, BEM block class)
  └── CONTAINER (ACSS class — handles max-width and centering)
        └── BEM elements (.block__header, .block__grid, .block__item)
```

- The `__inner` wrapper is dead. The ACSS Container class replaces it entirely — it handles max-width and centering.
- Padding is stripped from BEM container elements. Section-level spacing is handled by ACSS section spacing — set it on the Section, do not write it into the BEM CSS.

**Bricks Theme Style requirements** — set per project:

- HTML font-size = `var(--root-font-size)`
- Container width = `var(--content-width)`
- Default heading tag = H2
- Disable class chaining = ON (Bricks performance setting; required)

---

## BEM naming

- **Block** — the descriptive section purpose. `service-hero`, `capabilities`, `featured-projects`, `testimonial`. Project-meaningful, never generic.
- **Element** — double underscore. `.service-hero__col`, `.capabilities__item`, `.featured-projects__card`.
- **Modifier** — double dash. `.service-hero__col--content`, `.capabilities__item--featured`.

ACSS global classes (`.container`, `.eyebrow`, button classes) are used as-is. Do not wrap them in BEM, do not create custom versions of them. Buttons use ACSS button classes — do not write custom button CSS.

Specificity stays flat. BEM means each element has its own class; do not nest selectors like `.block .element .sub`. No `!important` as a default tool — where a layered-cascade fight has to be won, the doubled-class trick (`.foo.foo`) is the first move (see `03`).

String expansion in Bricks: write `--variable` in a Bricks field and ACSS expands it to `var(--variable)`.

### Specificity cheat sheet

When an ACSS utility or a framework rule overrides your custom class, climb specificity with compound selectors rather than `!important` — `!important` trumps specificity but kills debuggability, and every later override has to escalate again.

| Selector | Specificity |
|---|---|
| `.card` | `(0,1,0)` |
| `.card.clickable-parent` | `(0,2,0)` |
| `.card:hover` | `(0,2,0)` |
| `.clickable-parent:not(a)` | `(0,1,1)` — `:not(a)` adopts `a`'s `(0,0,1)` |
| `.clickable-parent:not(a) a` | `(0,1,2)` |
| `.card .card__meta a` | `(0,2,1)` |

The doubled-class trick (`.foo.foo`) is the cheapest legal climb: `(0,2,0)` beats `(0,1,0)` with no new selector surface. It is the first move against Bricks' inline Global Class CSS, `@layer bricks` framework rules, and any stylesheet that enqueues after the child theme — all three cases are catalogued in `03`.

Before fighting any rule with a selector, check whether it consumes a token you could override instead (`03`, ACSS `automatic-bricks.css`). Overriding the token is almost always the smaller change.

---

## ACSS variable reference

The authoritative values live in the project's ACSS token map, extracted per project. This is the structural reference — the variable names and what they are for.

**Spacing.** Six-step scale: `--space-xs`, `--space-s`, `--space-m`, `--space-l`, `--space-xl`, `--space-xxl`. The scale stops at `xs` — there is no `2xs` or `3xs`; using one silently falls back to an invalid var (see `03`). Section spacing: `--section-space-xs` through `--section-space-xxl`. Utility classes `.padding--{size}`, `.margin--{size}`, `.gap--{size}`. Directional spacing = a custom class plus `var(--space-{size})`. Fine-tune with `calc(var(--space-l) / 1.1)`. Never use magic numbers. Inside custom BEM classes, prefer the variables over the utility classes.

**Typography.** Font sizes: `--h1` through `--h6`, and `--text-xxl` / `--text-xl` / `--text-l` / `--text-m` / `--text-s` / `--text-xs` (same `xs` floor — no `2xs`). Global heading variables: `--heading-font-family`, `--heading-color`, `--heading-line-height`, `--heading-font-weight`, `--heading-letter-spacing`, `--heading-text-transform`. Per-level overrides: `--h1-color`, `--h1-font-weight`, etc. Global text: `--text-font-family`, `--text-color`, `--text-line-height`, `--text-font-weight`. To resize a heading visually without changing its tag, set `font-size: var(--h4)` on an `h2`. No hardcoded `font-family` or `font-size`.

ACSS heading and text sizes are fluid by default — the rendered values come from `clamp()` declarations in an `@supports` block in `automatic.css`, not from the rem fallbacks in `automatic-variables.css`. When auditing or overriding sizes, read the right file and preserve fluid scaling (see `03`).

**Colors.** Utility classes `.text--{color}`, `.bg--{color}`, `.link--{color}`. Shades: `-ultra-light`, `-light`, `-mid`, `-dark`, `-ultra-dark`. Variables: `var(--{color})`, `var(--{color}-{shade})`. Semantic colors: `--warning`, `--info`, `--success`, `--danger`. Color partials for computed work: `--{color}-hex`, `-hsl`, `-h`, `-s`, `-l`, `-rgb`, `-r`, `-g`, `-b`. Local override pattern: `.my-card--alt { --base-dark: var(--secondary); }`. Every color value in output is an ACSS variable — no hex, no rgb(), no named colors.

ACSS ships `.bg--ultra-light` / `.bg--light` / `.bg--dark` / `.bg--ultra-dark` but no `.bg--primary` — a brand-color section system is project-defined. ACSS auto-derives intermediate color shades at full saturation, which can be off-brand; treat auto-derived intermediates as needing design review before use.

---

## ACSS configuration

Configure ACSS the way we build Bricks: **programmatically, verified against output** — not by hand in the dashboard. The mechanism is `Automatic_CSS\Model\Database_Settings::save_settings( $values, $trigger_css_generation = true )`:

- `wp_set_current_user(1)` first — `save_settings` runs a capability check (same class of gotcha as Bricks' gated `_bricks_page_*` writes).
- **Pass the full settings array**, merged from the current option — `save_settings` resets any *omitted* allow-listed variable to its default.
- One call saves **and** regenerates the CSS (ScssPhp compiler). No separate regen step.

Direct-value settings — type sizes and **per-level Font Size Overrides**, scales, radius, button typography, font families, line-heights, focus — are fully scriptable this way (seconds, not a UI session). **The colour palette is the one exception:** its shade ladder (`-light/-dark/-hover/-trans` + `-h/-s/-l` partials) is computed by the dashboard's JS and stored in the option; generation reads those stored keys, so writing only the base hex leaves the ramp on the old colour. Either prompt the user to enter the palette in the dashboard once (it runs the derivation) — the established default — or replicate the derivation in PHP (keep base H/S, set each shade's L to its fixed step). Reserve browser automation for the palette-only case; never for the scriptable settings. Full mechanism and the boundary incident: `03` → ACSS; the ordered procedure: `02`.

**Governance-minimal palette.** Enable only the ACSS colour slots the brand uses. Every enabled slot emits ~15 auto-derived, full-saturation intermediates into the Bricks/AT colour picker — off-brand, and an invitation to pick off-script. Represent one-off brand colours (a flat surface, an accessible-text variant) as **pinned custom tokens in ACSS Global CSS** (next section), not slots; keep sub-14px sizes (eyebrows) out of the general text scale for the same reason.

### Pinned custom tokens — one home, by law

**Pinned custom brand tokens live in a `:root {}` block in ACSS Global CSS (Settings → Global CSS in the dashboard; earlier versions labeled the same field "Global SCSS"). That is the only home. No alternatives.** Not child-theme `:root`. Not Bricks Global Variables. Not a per-project choice. A brand guide, project doc, or template that names any other home is a doc bug — fix the document, never the build (`00` → doc/reality mismatches).

Why this home:

- **Cascade position is deterministic.** ACSS delivers Global CSS inline after `automatic.css` (`03` → ACSS), so a pinned `:root` token always beats anything ACSS emits — including any future collision with emitted names (`--h1`–`--h6`, `--success`, `--danger`). Child-theme `style.css` wins only by enqueue order, which is not something to make load-bearing.
- **Builder canvas delivery is native.** No builder-guard tuning required, unlike child-theme CSS (see Motion — the two-tier guard).
- **One audit surface.** Slots, scales, and pinned tokens all read from one place.
- **The picker stays clean either way.** Tokens stay out of the Bricks colour picker because they are not registered palette slots — the home has nothing to do with it. The "keep it out of the picker" rationale is not an argument for the child theme.

The child theme keeps a pointer: a header comment in `style.css` naming ACSS Global CSS as the token home, so nobody greps the theme and concludes the tokens don't exist.

**Irregular heading ladders are not pinned tokens.** A brand ladder that fits no scale ratio (56→40→28→22→18→16) goes into ACSS per-level Font Size Overrides — ACSS then emits the brand values directly and no override exists anywhere. Never re-declare `--h1`–`--h6` in any CSS home to express a ladder.

**Grandfathered fleet.** Pre-convention sites carry pinned tokens — and on most of the fleet, full `--h1`–`--h6` ladder overrides — in child-theme `:root`. **These are correct as-built. Never migrate them during an audit, KB-alignment pass, or any doc-driven sweep** (`00` → audits never migrate). They work; the doc adapts to them, not the reverse. Opportunistic migration of a heading ladder to Font Size Overrides is fine when already in a site's ACSS settings for other work — same values in, override block deleted only after front-end verification. Known fleet-wide risk worth naming: if headings change size across a grandfathered site after an ACSS update, the child-theme ladder losing its enqueue-order win is the first suspect.

---

## Clickable card and focus patterns

For cards where the entire primary content (image plus title) should be clickable, wrap that content in a **visible `<a>`** rather than a stretched-link pseudo-element. Secondary links — category tags, meta actions — live as siblings of the anchor, inside the same list item.

```html
<li class="card">
  <a href="{url}" class="card__link">
    <div class="card__media">
      <img src="…" alt="…">
    </div>
    <h3 class="card__title">Title</h3>
  </a>
  <p class="card__meta">
    <a href="…">Category</a> · <a href="…">Tag</a>
  </p>
</li>
```

This avoids `::after` pseudo-elements (which drop on Bricks import), avoids nested anchors, and lets inner links navigate independently with no specificity fight against the primary click surface. This is the default pattern.

**Alternative — ACSS `.clickable-parent` utility** — available only when the card has no inner links (purely primary content). Apply `.clickable-parent` to the list item. It requires:

- A direct-child `<a>` inside the card (a direct descendant, not wrapped in a heading).
- A positioning context: `.card.clickable-parent { position: relative; }` — the compound selector is needed to beat ACSS's `.clickable-parent:not(a) { position: static }` at equal specificity.
- The direct-child anchor must not use `position: absolute` for its visual-hiding technique and must not apply `clip-path` — both break the stretched pseudo.

Given those constraints, prefer the visible-anchor pattern for most cases.

**Focus-parent.** When `.clickable-parent` is used, the focus state goes on the parent, not the link. Bricks BEM (on the parent element's CSS): `%root%:focus-within :focus { outline: none; box-shadow: none; }` and `%root%:focus-within { box-shadow: 0 0 0 var(--focus-width) var(--focus-color); }`. ACSS utility alternative: `.focus-parent--shadow` or `.focus-parent--outline` on the same `position: relative` parent. Tokens: `--focus-width`, `--focus-color`, `--focus-offset`. All child elements must be `position: static` or the clickable area breaks.

---

## Semantic HTML

| Element | Usage |
|---|---|
| `<section>` | Every top-level page section. Always has `aria-labelledby` pointing to its heading's id. |
| `<header>` | A section intro block — eyebrow, heading, intro copy. Not the site header. |
| `<article>` | Self-contained content units — portfolio cards, blog posts, team members. |
| `<aside>` | Supplementary content — callouts, related links, conditional fill cards. |
| `<ul>` / `<li>` | Any repeating list — capabilities grid, service lists, feature sets. |
| `<ol>` | Numbered sequential steps — process sections, how-it-works patterns. |
| `<figure>` + `<blockquote>` + `<figcaption>` + `<cite>` | Testimonials and pull quotes. Never a bare `<p>`. |

Project-specific vocabulary overrides come from the project record.

---

## ARIA requirements

- Every `<section>` has `aria-labelledby` referencing its H2's `id`.
- An `.eyebrow` `<p>` has `aria-hidden="true"` — decorative, redundant with the heading it precedes.
- Decorative SVGs have `aria-hidden="true"` on the wrapper.
- Decorative dividers, quote marks, background numbers have `aria-hidden="true"`.
- Star ratings have `aria-label="5 out of 5 stars"` — never rely on Unicode characters alone.
- A full-card link (a link wrapping a card's heading) has a descriptive `aria-label`.
- Background-image `<div>`s have `role="img"` and an `aria-label`.
- Form inputs always have an associated `<label>`. Visually hidden is acceptable; placeholder-only labeling is not.
- Navigation has `aria-label="Main navigation"` or an equivalent scope label.
- Active navigation links have `aria-current="page"`.

Accessibility is structural — encode it in the markup at build time, not via runtime ARIA injection.

---

## Inheriting a project — the silent-strip startup audit

Bricks' validator silently drops settings with unknown keys at write time, and **the dropped values stay in the DB** — Bricks doesn't clean them up. The class still carries the wrong-key data; it just never renders. This matters on handover: learning a schema lesson fixes future scaffolds, it does **not** retroactively fix classes written before the lesson landed. A project built by someone else (or by you, before a gotcha was known) can be carrying dormant settings that were meant to do something.

So on any project you weren't there for the build of, audit before you style:

```bash
wp eval '
$classes = get_option("bricks_global_classes", []);
$bad = ["_maxWidth","_minWidth","_maxHeight","_minHeight","_borderRadius","objectFit"];
foreach ($bad as $bk) {
    $hits = [];
    foreach ($classes as $c) {
        if (isset($c["settings"][$bk])) $hits[] = $c["name"] . " = " . json_encode($c["settings"][$bk]);
    }
    if ($hits) { echo "\n$bk → " . count($hits) . " classes:\n"; foreach ($hits as $h) echo "  - $h\n"; }
}
'
```

Correct keys: `_widthMax` / `_widthMin` / `_heightMax` / `_heightMin` (suffix, not prefix), `_border.radius` (nested, side keys map to corners), `_objectFit` (leading underscore). When hits are found, migrate in one sweep — read the value, write it to the correct key, unset the old one.

The migration is a pure win: settings that did nothing become settings that do something. The values were already in the DB, just dormant — no element-tree restructure, no DOM change. Expect the layout to *gain back* constraints it was always supposed to have (max-widths capping content, min-heights setting card floors, radii rounding corners). Spot-check afterward; if one restored constraint produces an unwanted shift, unset that class rather than reverting the sweep.

## Editor strategy

Decided per project, but the established pattern: Gutenberg for the built-in `post` type (blog), Classic Editor for any CPT where Bricks owns the layout — set via the `use_block_editor_for_post_type` filter. The reason: Bricks renders `post_content` through its Post Content element; Gutenberg block markup in `post_content` would leak through on Bricks-owned templates. Gutenberg stays only where its block authoring genuinely helps the client.

A brochure site with no CPTs and Bricks-built pages mostly does not need an editor strategy. Revisit if a project adds a blog or a CPT.
