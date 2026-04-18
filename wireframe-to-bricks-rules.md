# Wireframe → Bricks Builder Rules

**Michael Parks Design — project-agnostic instruction set for Claude.ai**

Version 1.1 — Generated April 2026, patched April 2026

---

## 1. Prime Directive

This document defines the rules Claude follows when converting HTML/CSS wireframes into paste-ready Bricks Builder sections. It is project-agnostic. Per-project context (ACSS token map, sections to skip, client-specific vocabulary) lives in the project's Notion record, not in this document.

**Claude reads this document at conversation start and follows it through the full wireframe conversion. These rules are not suggestions — they are constraints.**

The output of this workflow is paste-ready HTML/CSS for Bricks 2.3+ that:
- Imports cleanly into the Bricks UI with minimal Custom CSS overflow
- Uses ACSS tokens rather than hardcoded values
- Follows BEM naming conventions on all custom blocks
- Is semantically correct with proper ARIA attributes
- Is ready for ACF dynamic data wiring after paste

Claude does not produce a finished build. Claude produces the structural foundation that the developer then completes in Bricks.

---

## 2. Required Project Context

Before generating any output, Claude requires the following from the project's Notion record:

1. **ACSS Token Map** — child page titled "ACSS Token Map — {project name}" containing the full ACSS custom property set for this project's install
2. **Sections to skip** — which sections are pre-built (header, footer, reusable components)
3. **Bricks version** — confirm 2.3 or later
4. **Plugin path and name** — for CSS handoff reference (not used during wireframe conversion itself)
5. **Semantic vocabulary overrides** — any project-specific deviations from the defaults in Section 7

**Claude reads from Notion. Claude does not write to Notion during this workflow.** Updates to the token map or project record happen outside this workflow, via Claude Code → local file → manual mirror to Notion.

If any required context is missing at conversation start, Claude asks for it before proceeding. Claude does not assume defaults for the token map — if the token map is not available, the workflow halts.

---

## 3. Workflow Phases

The workflow runs in three phases. Claude does not skip phases or combine them.

**Phase 1: Analysis.** Claude reads the wireframe and the ACSS token map. Claude produces the analysis report (Section 4). Claude does not generate section output in this phase.

**Phase 2: Approval gate.** The developer reviews the analysis report, confirms the section inventory, approves the token remap, and specifies the order of section output. Claude waits for explicit approval before proceeding.

**Phase 3: Per-section output.** Claude produces one section file per request, following the format in Section 9. Claude does not produce multiple sections in one response. Claude does not move to the next section until the developer approves the current one.

---

## 4. Analysis Report Template

Claude's Phase 1 output is a report with exactly these sections, in this order:

### 4.1 Section Inventory
A numbered list of every top-level section in the wireframe with a short purpose description. Example:
1. Hero — service intro with headline, subhead, primary CTA
2. Capabilities grid — 6-item numbered feature grid
3. Featured projects — 3-card portfolio showcase
4. Testimonial — single pull quote

### 4.2 Sections Flagged for Skip
A list of sections Claude will not produce output for, with the reason. Example:
- Site header (pre-built as Bricks template)
- Site footer (pre-built as Bricks template)
- Testimonial (existing reusable component on service pages)

### 4.3 Token Remap Table
A three-column table: wireframe token → ACSS token → match quality (exact / close / manual). Example:

| Wireframe Token | ACSS Token | Match |
| --- | --- | --- |
| `--cognac` | `--primary` | exact |
| `--cognac-light` | `--primary-hover` | close (+0.05 luminance delta) |
| `--warm-mid` | `--base-light` | manual (no direct equivalent) |

For any match rated "manual," Claude notes the delta and recommends whether to snap to ACSS or preserve the wireframe value. Default recommendation: snap to ACSS unless the delta is visually significant.

### 4.4 Bricks Compatibility Flags
Per-section list of CSS features that will not map to Bricks UI controls and will land in Custom CSS after paste. Example:
- Section 1: clamp() on hero heading (expected — lands in element Custom CSS)
- Section 2: CSS counter for numbering (expected — verify counter-reset lands on grid)
- Section 3: ::before decorative divider (WILL DROP on import — rebuild as Bricks absolute element)

### 4.5 Semantic and ARIA Refactoring Required
List of semantic HTML corrections and ARIA additions needed per section. Example:
- Section 1: hero uses `<div>` wrapper — refactor to `<section>` with `aria-labelledby`
- Section 2: capability items are `<div>` — refactor to `<ul>`/`<li>`
- Section 3: testimonial is bare `<p>` — refactor to `<figure>` + `<blockquote>` + `<figcaption>` + `<cite>`

### 4.6 Proposed Section Output Order
Claude recommends the order in which to produce sections, simplest first, so pattern mistakes surface cheaply before being repeated. The developer confirms or reorders.

**Claude stops after the report and waits for approval. No section output until Phase 2 completes.**

---

## 5. CSS Authoring Rules for Bricks Import

These rules govern how Claude writes CSS so that Bricks 2.3's HTML/CSS paste importer maps as much as possible to UI controls, leaving minimum fallout in Custom CSS.

### 5.1 Prefer Mappable Syntax
- Use `grid-template-columns: 1fr 1fr` rather than `grid-template-columns: 60fr 40fr`. The former maps to the Bricks grid UI; the latter lands in Custom CSS.
- Use `padding: 2rem` rather than `padding-block: 2rem` or `padding-inline: 2rem`. Longhand physical properties map cleaner than logical properties.
- Use explicit pixel or rem values for things that will map to UI panels (widths, heights, gaps). Variables are fine, but `calc()` expressions often drop to Custom CSS.

### 5.2 Avoid Features That Drop on Import
- No `@supports`, `@container`, or `:has()` — all drop to Custom CSS and cannot be edited via UI.
- No `::before` / `::after` for structural content. They drop on import. If a decorative element is required, output a note for the developer to rebuild it as a Bricks absolute-positioned element after paste.
- No CSS variables defined at `:root`. Use ACSS variables directly. If a section genuinely needs a scoped variable, define it on the block's BEM root class, not on `:root`.

### 5.3 Keep Specificity Flat
- BEM means flat specificity. Do not nest selectors like `.block .element .sub-element`. Each element gets its own BEM class.
- Do not use `!important` anywhere.
- Keep `:hover`, `:focus`, `:focus-within` states in separate rule blocks from the base rule. Combined rules sometimes import as Custom CSS instead of as states.

### 5.4 Media Queries
- Use only these breakpoints, matching Bricks defaults:
  - 1366px (laptop)
  - 1024px (tablet landscape)
  - 991px (tablet portrait)
  - 767px (mobile landscape)
  - 478px (mobile portrait)
- Write `max-width` queries for downward cascading (mobile-last approach) unless the wireframe is mobile-first, in which case use `min-width` consistently. Do not mix directions in one section.

### 5.5 Token Usage
- Every color value is an ACSS variable. No hex, no rgb(), no named colors in CSS output.
- Every spacing value is an ACSS variable or a `calc()` expression referencing ACSS variables. No magic numbers.
- Typography uses `--heading-font-family`, `--text-font-family`, and the ACSS type scale variables (`--h1` through `--h6`, `--text-xxl` through `--text-xs`). No hardcoded `font-family` or `font-size`.
- Per [Michael Parks Design convention]: prefer ACSS variables inside custom BEM classes over utility classes. Utility classes are acceptable but variables-in-BEM is the default.

---

## 6. BEM and ACSS Structural Conventions

### 6.1 Section Structure
Every section follows this nesting:

```
<section class="block-name"> (Bricks SECTION element)
  └── <div class="container">  (ACSS global class — handles max-width and centering)
        └── BEM elements (.block-name__header, .block-name__grid, .block-name__item)
```

The `__inner` wrapper is never used. The ACSS `.container` class replaces it entirely.

Padding is stripped from BEM container elements. Section-level padding uses ACSS section spacing utilities (`.section-padding--s/m/l/xl`) applied in Bricks after paste. Do not write section padding into the BEM CSS.

### 6.2 BEM Naming
- **Block** = descriptive section purpose. Examples: `service-hero`, `capabilities`, `featured-projects`, `testimonial`. Name is project-meaningful, not generic.
- **Element** = double underscore. `.service-hero__col`, `.capabilities__item`, `.featured-projects__card`.
- **Modifier** = double dash. `.service-hero__col--content`, `.capabilities__item--featured`.

ACSS global classes (`.container`, `.eyebrow`, `.btn`, `.btn--primary`) are used as-is. Do not wrap them in BEM. Do not create custom versions of them.

Buttons use ACSS button classes. Do not write custom button CSS.

### 6.3 Clickable Card Pattern

For cards where the entire primary content (image + title) should be clickable, wrap that content in a visible `<a>` element rather than using a stretched-link pseudo. Secondary links (category tags, meta actions) live as siblings of the anchor, inside the same list item.

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

This pattern avoids `::after` pseudo-elements (which drop on import per Section 5.2), avoids nested anchors, and lets inner links navigate independently without specificity battles against the primary click surface.

**Alternative — ACSS `.clickable-parent` utility** — is available when the card has *no* inner links (purely primary content). Apply `.clickable-parent` to the list item. Requires:

- A direct-child `<a>` inside the card (not wrapped in a heading — direct descendant)
- A positioning context: `.card.clickable-parent { position: relative; }` (compound selector needed to beat ACSS's `.clickable-parent:not(a) { position: static }` at equal specificity)
- Direct-child anchor must NOT use `position: absolute` for its visual-hiding technique, and must NOT apply `clip-path` — both break the stretched pseudo

Given these constraints, prefer the visible-anchor pattern above for most cases.

### 6.4 Focus Parent Pattern
When clickable-parent is used, add a focus state to the parent, not the link:

```css
.card:focus-within :focus {
  outline: none;
  box-shadow: none;
}
.card:focus-within {
  box-shadow: 0 0 0 var(--focus-width) var(--focus-color);
}
```

---

## 7. Semantic HTML and ARIA Requirements

### 7.1 Semantic Element Rules

| Element | Usage |
| --- | --- |
| `<section>` | Every top-level page section. Always has `aria-labelledby` pointing to its heading's id. |
| `<header>` | Section intro block (eyebrow + heading + intro copy). Not the site header. |
| `<article>` | Self-contained content units — portfolio cards, blog posts, team members. |
| `<aside>` | Supplementary content — callouts, related links, conditional fill cards. |
| `<ul>` / `<li>` | Any repeating list of items — capabilities grid, service lists, feature sets. |
| `<ol>` | Numbered sequential steps — process sections, how-it-works patterns. |
| `<figure>` + `<blockquote>` + `<figcaption>` + `<cite>` | Testimonials and pull quotes. Never a bare `<p>`. |

Claude applies these by default. Per-project vocabulary overrides come from the Notion project record.

### 7.2 ARIA Requirements

- Every `<section>` has `aria-labelledby` referencing its H2's `id`.
- `.eyebrow` `<p>` has `aria-hidden="true"` (decorative, redundant with the heading it precedes).
- Decorative SVGs have `aria-hidden="true"` on their wrapper.
- Decorative dividers, quote marks, background numbers have `aria-hidden="true"`.
- Star ratings have `aria-label="5 out of 5 stars"` — never rely on Unicode characters alone.
- Full-card links (link wrapping the heading of a card) have a descriptive `aria-label`.
- Background image `<div>`s have `role="img"` and `aria-label`.
- Form inputs always have an associated `<label>`. Visually hidden is acceptable. Placeholder-only labeling is not.
- Navigation elements have `aria-label="Main navigation"` (or equivalent scope label).
- Active navigation links have `aria-current="page"`.

---

## 8. Don't-Produce List

Claude does not produce any of the following, regardless of wireframe content or developer request:

- `::before` or `::after` pseudo-elements for structural content (decorative-only is acceptable with a rebuild note)
- CSS custom properties defined at `:root`
- `!important` declarations
- Inline `<style>` attributes on elements
- `<script>` tags or JavaScript (Bricks Interactions handle dynamic behavior)
- `<div class="container">` with custom container CSS (use the ACSS `.container` global class)
- Media queries at non-standard breakpoints (stick to Bricks defaults in Section 5.4)
- Google Fonts `<link>` tags (ACSS and the theme handle fonts)
- Hardcoded color hex codes, rgb() values, or named colors
- Hardcoded pixel values for spacing (use ACSS spacing variables)
- Nested selectors beyond single BEM depth
- JavaScript-driven animation or transition logic
- References to wireframe framework classes (Tailwind utility classes, Bootstrap classes, etc.) — output uses BEM + ACSS only

If the wireframe contains any of these, Claude refactors them during Phase 3 output and notes the refactor in the section file's header comment.

---

## 9. Per-Section Output Format

Each section Claude produces is one file with this exact structure:

### 9.1 Header Comment Block

```html
<!--
  Section: [block-name]
  Purpose: [one-line description]
  
  BEM Structure:
    .block-name
      .block-name__header
      .block-name__grid
        .block-name__item
  
  ACSS Tokens Used:
    Colors: --primary, --base, --neutral
    Spacing: --space-m, --section-space-l
    Type: --h2, --text-l, --heading-font-family
  
  Bricks Notes:
    - [any import quirks to watch for, e.g. "counter-reset lands on .block-name__grid"]
    - [any ::before rebuilds required post-paste]
  
  Responsive Breakpoints:
    - 991px (tablet portrait): [what changes]
    - 767px (mobile landscape): [what changes]
  
  ACF Fields:
    - field_name_1 → .block-name__heading (text)
    - field_name_2 → .block-name__intro (textarea)
    - field_repeater_1 → .block-name__grid (repeater)
      - sub_field_1 → .block-name__item-title
      - sub_field_2 → .block-name__item-description
-->
```

### 9.2 Style Block

```html
<style>
/* Block base */
.block-name { ... }

/* Elements */
.block-name__header { ... }
.block-name__grid { ... }
.block-name__item { ... }

/* Modifiers */
.block-name__item--featured { ... }

/* States */
.block-name__item:hover { ... }
.block-name__item:focus-within { ... }

/* Responsive */
@media (max-width: 991px) { ... }
@media (max-width: 767px) { ... }
</style>
```

### 9.3 HTML Markup

```html
<section class="block-name" aria-labelledby="block-name-heading">
  <div class="container">
    <header class="block-name__header">
      <p class="eyebrow" aria-hidden="true">Eyebrow text</p>
      <h2 id="block-name-heading">Section heading</h2>
      <p class="block-name__intro">Intro copy</p>
    </header>
    
    <!-- ACF: field_repeater_1 → Bricks Query Loop -->
    <ul class="block-name__grid">
      <li class="block-name__item">
        <!-- ACF: sub_field_1 -->
        <h3 class="block-name__item-title">Item title</h3>
        <!-- ACF: sub_field_2 -->
        <p class="block-name__item-description">Item description</p>
      </li>
    </ul>
  </div>
</section>
```

### 9.4 File Delivery
Claude produces the section as a single code block the developer can copy in full. No preamble, no trailing commentary beyond the section file itself. If Bricks-specific post-paste notes are required, they go in the header comment block, not as conversational text.

---

## 10. Handoff Notes

After Claude completes all approved sections, the developer takes over:

1. Paste each section file into Bricks via the HTML/CSS paste feature (one section at a time)
2. Review each imported structure, sign any Code elements containing SVGs or external resources
3. Apply ACSS utility classes (section spacing, background colors, button classes) in the Bricks UI
4. Wire ACF dynamic data to the fields annotated in each section's header comment
5. Insert any WS Form shortcodes or Bricks form elements where wireframes have form placeholders
6. Rebuild any `::before` decorative elements flagged in the analysis report as Bricks absolute elements

This phase is not Claude.ai's work. It is hands-on-keyboard Bricks work.

**CSS handoff to plugin happens at pre-launch, not during the wireframe conversion.** The pre-launch sweep is a separate Claude Code operation — see `bricks-css-sweep.md`.

---

## 11. Changelog

**v1.1 — April 2026 (patched)**
- Replaced Section 6.3 Clickable Parent Pattern with the visible-anchor Clickable Card Pattern. The original `::after` approach contradicted Section 5.2's prohibition on structural pseudo-elements. Patch sourced from the Claude Code × Bricks Direct Edit Playbook, captured during the ext-mem project.
- ACSS `.clickable-parent` utility retained as an alternative with documented constraints (direct-child anchor, compound-selector positioning context, no `position: absolute` or `clip-path` on the anchor).

**v1.0 — April 2026**
- Initial version, extracted from AHML project learnings and the first iteration of the workflow doc
- Establishes Notion as the project context source
- Codifies CSS authoring rules for Bricks 2.3 import compatibility
- Formalizes the analysis → approval → per-section output phase structure

*Future versions: document new Bricks import quirks as they surface, add project-specific vocabulary patterns that prove universal, refine the don't-produce list based on recurring Claude mistakes.*
