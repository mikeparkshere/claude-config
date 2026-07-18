# 02 — Build Pipeline

**Token-aware wireframe in, built Bricks page out, Mike reviews.**

Verified against Bricks 2.3.4 / ACSS 3.3.6. V1 baseline: 2026-05-24.

---

## The capability

This is one capability, not two documents. A wireframe — Claude.ai-generated, Figma, or design-supplied, constructed with the project's ACSS tokens known — comes in. Claude Code analyzes it, builds the page programmatically via WP-CLI, and hands it to Mike for review and customization.

The **front half** is wireframe intake: analysis, what good input looks like, the approval gate, section-by-section discipline. The **back half** is the WP-CLI build: the golden rule, the build sequence, the verified schema library, the failure modes.

The older workflow had a paste-into-Bricks-UI step between the two halves. The pipeline is built to eliminate that step. Claude Code builds the page directly.

Claude Code does not produce a finished design. It produces a correctly-structured, correctly-wired build that Mike then reviews and customizes. The wireframe is a structural specification, not a finished design.

---

# FRONT HALF — Wireframe intake

## Required project context

Before any build, these must be in hand. If any is missing, ask for it — do not assume defaults.

1. **ACSS token map** — the full ACSS custom property set for this project's install. The wireframe should already be built against these. Extract it once per project from the live ACSS stylesheet (`wp-content/uploads/automatic-css/automatic-variables.css` and the `@supports` clamp block in `automatic.css` — see the gotcha in `03` about which file holds the rendered values).
2. **Sections to skip** — which sections are pre-built: site header, site footer, reusable components that already exist on other pages.
3. **Bricks version** — confirm 2.3.4 or later.
4. **Plugin path and name** — the core functionality plugin, for CSS handoff reference.
5. **Semantic vocabulary overrides** — any project-specific deviations from the semantic defaults in `01`.

## ACSS brand-configuration (before token extraction)

Required-context item 1 (the ACSS token map) assumes ACSS is **already** configured to the brand. That configuration is its own procedure — WP-CLI-first, per the convention in `01`:

1. **Palette (the one interactive step).** Prompt the user to enter the brand palette in the ACSS dashboard (`?acssOpenDashboard=1`) — base colours + which slots to enable (governance-minimal). This is the only dashboard step; the shade derivation is JS-side (`03` → ACSS). Or run a PHP derivation helper.
2. **Everything else by WP-CLI** — one `wp eval-file`: `wp_set_current_user(1)`, read the option, merge type sizes / per-level Font Size Overrides (mobile=min, desktop=max = brand ranges) / scales / radius / button typography / fonts / line-heights / focus, then `save_settings($merged, true)`.
3. **Tokens** — pinned customs (`--cream`, a `--display` step above `--h1`, focus overrides) into Global SCSS. This delivers **inline** after `automatic.css` (`03` → ACSS), so `:root` overrides win the cascade.
4. **Verify on the rendered front end, not the on-disk files** — `curl` and grep the inline `automaticcss-core-inline-css` block + compiled `automatic.css` for palette hexes, per-level clamp sizes, button vars, and that custom overrides win.
5. **Then extract the token map** (below) — now that ACSS is brand-true.

## What good input looks like

A wireframe is good input when:

- It is constructed with the project's ACSS tokens known — colors, spacing, type all reference values that map to ACSS, not arbitrary design-tool output.
- It expresses structure and hierarchy clearly. Layout and nesting are what the pipeline imports; finished styling is not.
- It is one page or one coherent set of sections, not a whole site at once.

If the wireframe uses hardcoded values, framework utility classes (Tailwind, Bootstrap), or design-tool cruft, that is fine — the analysis pass catches it and the build refactors it. But a token-aware wireframe makes the remap step short and the build clean.

## The analysis pass

Claude Code reads the wireframe and the ACSS token map, and produces an analysis report before building anything. The report has these sections, in order:

**Section inventory.** Every top-level section with a one-line purpose.

**Sections flagged for skip.** Sections that will not be built, with the reason — header, footer, existing reusable components.

**Token remap table.** Three columns: wireframe token → ACSS token → match quality (exact / close / manual). For any "manual" match, note the delta and recommend snap-to-ACSS or preserve. Default recommendation: snap to ACSS unless the delta is visually significant.

**Bricks compatibility flags.** Per-section, what will not map cleanly and why — `clamp()` on a heading, a CSS counter, a decorative pseudo-element. Flag what is expected fallout vs what needs a rebuild.

**Semantic and ARIA refactoring needed.** Per-section, the semantic corrections and ARIA additions required — a `<div>` that should be a `<section>`, items that should be a `<ul>`, a bare `<p>` testimonial that should be a `<figure>`. The rules are in `01`.

**Proposed section output order.** Simplest first, so a pattern mistake surfaces cheaply before it is repeated.

## The approval gate

After the analysis report, Claude Code stops. Mike reviews the report, confirms the section inventory, approves the token remap, and sets the build order. Claude Code does not build until the gate clears.

## Section-by-section discipline and the authorization gradient

Section-by-section is the **default**, and it exists for a reason beyond caution: it is how CC picks up Mike's preferences and conventions. The cheap, simple sections built first are where the cadence gets established — pattern mistakes surface on a low-stakes section before they would be repeated across the page.

The gradient:

- **Default — one section at a time.** Build the section, verify it, present it, move to the next only after Mike approves. Order is simplest-first, set in the analysis report. This is the trust-building mechanism; do not skip it.
- **Complex sections — pause for input.** Some sections are intricate enough that CC should expect to stop and ask Mike for direction mid-section rather than guessing. A complex layout, an unusual interaction, an ambiguous wireframe region — surface the question, do not push through on assumption.
- **Whole-page build — only on explicit authorization.** Once CC has demonstrably grasped the workflow on a given project — the cadence is proven on the first sections — Mike may explicitly authorize building the rest of the page in one pass. CC never self-promotes to whole-page mode. That authorization is Mike's to give, per project, and only after the cadence is shown.

This mirrors the established pattern from prior projects: the first one or two sections get full propose-and-review; later sections execute with a judgment-call summary once trust on the cadence is established.

---

# BACK HALF — WP-CLI build

## The golden rule

**Never guess Bricks internal schemas. Build one example in the builder, save, read it back, replicate the shape.**

Bricks runs a JS-side tree validator when the builder loads a template. Unknown setting keys and malformed values are silently dropped, and the cleaned tree is re-saved on the next builder write. A guessed schema may work on first render and then vanish the next time the builder opens. A builder-verified schema sticks.

Discovery workflow:

1. Open the target template in the Bricks builder.
2. Add the minimum version of the element you need — one Query Loop with only Query Type set, one element with a single border.
3. Save the template.
4. Read it back: `wp post meta get <id> _bricks_page_content_2 --format=json`.
5. Use the returned shape as the schema template.

When a schema is not in the library below, this is how you get it. An incomplete schema library is expected — the golden rule is the method for filling it.

### Discovery cost — the golden rule can evict itself

A readback on a content-bearing page is not free: one `_bricks_page_content_2` blob on staging can run tens of thousands of tokens. Enough of them and the session compacts, evicting the knowledgebase — including this rule. The discovery method must not destroy the context that enforces it. Two disciplines:

**Extract narrowly. Never read a whole blob to learn one shape.**

```bash
# one element type, first match only
wp post meta get <id> _bricks_page_content_2 --format=json \
  | jq '[.[] | select(.name=="section")][0]'

# just the settings keys an element uses (schema discovery without the payload)
wp post meta get <id> _bricks_page_content_2 --format=json \
  | jq '[.[] | select(.name=="heading")][0].settings | keys'

# tree shape only — id/name/parent, no settings at all
wp post meta get <id> _bricks_page_content_2 --format=json \
  | jq 'map({id, name, parent})'
```

**Route bulk readbacks through a subagent.** When a full-blob read is genuinely needed — auditing a whole template, harvesting multiple schemas at go-live — dispatch it as a Task so the JSON burns the subagent's context and only the extracted shapes return to the main session. The main session's context is the scarce resource; spend the subagent's instead.


## Authentication

The first line of any WP-CLI script that writes Bricks template meta:

```php
wp_set_current_user( 1 );  // or any user ID with builder access
```

Bricks hooks `update_post_metadata` for `_bricks_page_content_2`, `_bricks_page_header_2`, and `_bricks_page_footer_2`, and blocks the write when the builder capability check fails. WP-CLI runs as user 0 — the check fails, the write silently no-ops, the script reports success, nothing lands.

Writes to the `bricks_global_classes` option are not gated this way.

## CSS authoring rules for clean output

When writing CSS into the build — element `_cssCustom`, global class CSS, or the wireframe's own styling for refactor — these rules keep the output clean and as much as possible expressed as typed Bricks settings rather than raw CSS.

**Prefer mappable syntax.**
- `grid-template-columns: 1fr 1fr`, not `60fr 40fr`. The former maps to the Bricks grid UI; the latter lands in custom CSS.
- Longhand physical properties (`padding`) map cleaner than logical (`padding-block`).
- `calc()` expressions often drop to custom CSS — use them deliberately, not by default.

**Avoid features that drop on import.**
- No `@supports`, `@container`, `:has()` — all drop to custom CSS, uneditable via UI.
- No `::before` / `::after` for structural content. They drop. A decorative pseudo gets rebuilt as a Bricks absolute-positioned element.
- No CSS variables at `:root`. Use ACSS variables directly. A genuinely section-scoped variable goes on the block's BEM root class, not `:root`.

**Keep specificity flat.**
- BEM means flat specificity. No nesting like `.block .element .sub`. Each element gets its own BEM class.
- No `!important` as a default tool. Where the layered-cascade gotchas in `03` require winning a specificity fight, use the doubled-class trick first.
- Keep `:hover` / `:focus` / `:focus-within` in separate rule blocks from the base rule.

**Breakpoints.** Use only the registered Bricks breakpoints. Default set on a standard install is `desktop / tablet_portrait (991) / mobile_landscape (767) / mobile_portrait (478)`. There is no `tablet_landscape` on the default set — keys for unregistered breakpoints save to the DB and emit zero CSS silently. Confirm the registered set before relying on a breakpoint:

```bash
wp eval "echo wp_json_encode(\Bricks\Breakpoints::\$breakpoints);"
```

**Token usage.** Every color is an ACSS variable — no hex, no rgb(), no named colors. Every spacing value is an ACSS variable or a `calc()` referencing them — no magic numbers. Typography uses the ACSS type scale and the heading/text font-family variables. The full variable reference is in `01`.

## The build sequence

For each approved section:

1. **Construct the element tree.** Build the section's Bricks element array — SECTION > CONTAINER > BEM elements per the structure in `01`. Each element is `{ id, name, parent, settings, ... }`. Element IDs must be 6-character alphanumeric, never all-numeric (see `03`).

2. **Create global classes.** Project BEM classes get registered in `bricks_global_classes`. Use the verified global class shape below. `settings` must be `array()`, never `new stdClass()` — a stdClass there crashes Bricks site-wide.

3. **Write the element tree.** `update_post_meta` on `_bricks_page_content_2` (or header/footer key), after `wp_set_current_user(1)`.

4. **Apply values as typed settings first — CSS is the exception, not the default.** Before writing any CSS, check whether Bricks has a typed control for the value. Layout, spacing, gap, borders, backgrounds, typography, grid all have typed schemas — use them. This is the typed-settings rule from `00`, and it is the most-violated rule in the knowledgebase: the instinct to reach straight for `_cssCustom` is fast but produces inline CSS that is invisible in the UI and unmaintainable for a junior dev or client handoff. `_cssCustom` is only for what a typed schema genuinely cannot express; global needs go to the child theme. Full order in `01`.

5. **Regenerate per-post CSS.** A DB-side write does not regenerate the per-post CSS files. Run the regen explicitly (see below).

6. **Verify against rendered HTML.** Do not trust the script's own success output. Curl the page, grep for the elements and the expected CSS, confirm element counts against the DB.

## Post-build steps

**CSS regeneration.** Bricks pre-builds per-post CSS at `wp-content/uploads/bricks/css/post-{ID}.min.css`. DB-side writes do not refresh these, and a render request does not reliably trigger regeneration — the on-demand path has capability checks that fail silently from CLI. Regenerate explicitly:

```php
// /tmp/bricks-regen-css.php
wp_set_current_user( 1 ); // builder cap check blocks CLI writes without this
\Bricks\Assets_Files::regenerate_css_files();
$files = glob( WP_CONTENT_DIR . '/uploads/bricks/css/post-*.min.css' );
echo "post-*.min.css count: " . count( $files ) . "\n";
if ( empty( $files ) ) exit( 1 ); // fail loudly if regen produced nothing
```

Run with `wp eval-file`. The correct method is `Assets_Files::regenerate_css_files()` — not the `Assets::generate_*` methods, which are for inline-render mode and do not write files. Full incident: `03`.

**Verification.** Curl the page and grep the inline CSS for the class to confirm a typed setting actually emitted:

```bash
curl -sk https://site.local/path/ | grep -oE '\.my-class[^{]*\{[^}]*\}'
```

If a setting persisted in the DB but emitted no CSS, the schema shape is wrong — see the failure modes below and the `03` entries on typed `_border` and breakpoint-suffix placement.

## Failure modes

**Silent strip on next builder load.** A write reports success, readback confirms the element, but after the builder opens, the element reverts or disappears. Cause: the JS-side tree validator dropped an unknown key. Fix: golden rule — any schema not builder-verified gets stripped.

**Global class crashes the site.** Site returns a critical error on every request after a `bricks_global_classes` write; error log shows `Cannot use object of type stdClass as array` in `bricks/includes/interactions.php`. Cause: `'settings' => new stdClass()` instead of `array()`. WordPress will not bootstrap, so WP-CLI fails — recovery is direct MySQL. Prevention: `'settings' => array()` always, even as a placeholder.

**Typed setting persists but emits no CSS.** The setting saves to the DB, readback looks correct, but the rendered page has no corresponding CSS. Cause: wrong schema shape — Bricks' emitter walks specific keys and ignores anything else, without stripping it. Most common with `_border` (flat shape required, not per-side nested) and breakpoint suffixes (must be on the outer key, not nested inside a typed dict). See `03`.

**Dynamic tags not parsed in arbitrary query fields.** `post__not_in: ["{post_id}"]` does not work — Bricks does not resolve dynamic tags inside that array. Use the dedicated `exclude_current_post: true`. General rule: where Bricks has a native key for a behavior, use that key; do not reach the same behavior via raw WordPress query keys.

---

# VERIFIED SCHEMA LIBRARY

Lookup tier. Each schema below was discovered via the golden rule and is verified. Treat anything not here as needing fresh discovery. New schemas append here at go-live harvest.

## Query Loop — Posts

```json
{
  "hasLoop": true,
  "query": {
    "objectType": "post",
    "post_type": ["video"],
    "exclude_current_post": true
  }
}
```

- `post_type` is an array, not a string.
- Exclude current post: `exclude_current_post: true`. Never `post__not_in: ["{post_id}"]` — the dynamic tag does not parse there and the invalid shape may get the whole query rejected on builder load.
- Bricks fills `posts_per_page`, `orderby`, `order` from main-query defaults if omitted.

## Query Loop — Custom (registered via filter)

```json
{
  "hasLoop": true,
  "query": {
    "objectType": "your_custom_type"
  }
}
```

Custom types register via two filters:

```php
add_filter( 'bricks/setup/control_options', function( $opts ) {
    $opts['queryTypes']['your_custom_type'] = __( 'Your Label', 'textdomain' );
    return $opts;
});

add_filter( 'bricks/query/run', function( $results, $query ) {
    if ( $query->object_type !== 'your_custom_type' ) return $results;
    return array( /* iterable of objects */ );
}, 10, 2 );
```

## Image with dynamic src

```json
{
  "image": {
    "useDynamicData": "{acf_field_name}"
  },
  "altText": "{post_title}"
}
```

- `useDynamicData` is inside `image`; `altText` is at the top level of settings.
- Confirmed path for ACF URL fields.

## Link (on container with tag=a, or on a button)

```json
{
  "link": {
    "type": "meta",
    "useDynamicData": "{post_url}"
  }
}
```

- The Link control on Container/Block elements appears only when the HTML tag is `a`. Change the tag first.
- For non-dynamic external URLs: `"type": "external", "url": "..."`.

## Block HTML tag options (native, no `customTag` needed)

`div`, `section`, `a`, `article`, `nav`, `ol`, `ul`, `li`, `aside`, `address`, `figure`, `custom`.

For anything else (`dl`, `dt`, `dd`): `"tag": "custom"` plus `"customTag": "dl"`.

## Bricks Conditions (hide-when-empty etc.)

```json
{
  "_conditions": [
    [
      {
        "id": "random6char",
        "key": "dynamic_data",
        "dynamic_data": "{acf_field}",
        "compare": "empty_not"
      }
    ]
  ]
}
```

Nested array shape: the outer array is AND groups, inner arrays are OR rules within a group. For a simple "hide if field empty": one outer array, one inner rule.

## Global Class shape

```json
{
  "id": "6-char-alphanumeric",
  "name": "class-name",
  "settings": {
    "_cssCustom": "/* CSS goes here */"
  },
  "modified": 1776526083000,
  "user_id": 1
}
```

- `settings` must be `array()` — never `new stdClass()`. A stdClass crashes Bricks' interactions loader site-wide; recovery requires direct MySQL.
- Custom CSS in `_cssCustom` uses literal class selectors (`.my-class { ... }`), not `%root%`. Bricks substitutes `%root%` to literal at UI save time; the stored value is always literal.

## Typed `_border` (flat shape — verified)

```php
'_border' => [
    'width'  => [ 'top' => '1', 'right' => '0', 'bottom' => '1', 'left' => '0' ],
    'style'  => 'solid',
    'color'  => [ 'id' => 'acss_import_base-light', 'name' => 'base-light', 'raw' => 'var(--base-light)' ],
    'radius' => [ 'top' => '2', 'right' => '2', 'bottom' => '2', 'left' => '2' ],
],
```

Width is a per-side object, style is a scalar, color is a single object, radius is a per-side object. Per-side nested shapes (`_border.bottom.{width,style,color}`) persist in the DB without error but emit zero border CSS. For "bottom border only," set `width.bottom = '1'`, other sides `'0'`. Full incident: `03`.

## SVG element

```json
{
  "source": "file",
  "file": { "id": "<attachment_id>" },
  "link": { "type": "...", "url": "...", "newTab": "...", "rel": "..." }
}
```

Color via typed `_typography.color` if the SVG uses `fill="currentColor"`, or via typed `_fill` / `_stroke` for a direct override. The SVG attachment must use `fill="currentColor"` for CSS color inheritance to work — otherwise it renders with a hardcoded fill.

## Custom dynamic tags (three-filter pattern)

```php
add_filter( 'bricks/dynamic_tags_list', function( $tags ) {
    $tags[] = array(
        'name'  => '{my_tag}',
        'label' => 'My Tag',
        'group' => 'My Group',
    );
    return $tags;
});

add_filter( 'bricks/dynamic_data/render_tag', function( $tag, $post, $context = 'text' ) {
    // REQUIRED on Bricks 2.3.x: $tag can arrive as a parsed-tag ARRAY, not the
    // '{my_tag}' string. Without this guard the site fatals on first render
    // (trim(): Argument #1 must be of type string, array given). Returning $tag
    // unchanged lets Bricks' native parsed-tag handler take over.
    if ( ! is_string( $tag ) ) return $tag;

    if ( $tag !== '{my_tag}' ) return $tag;
    return 'resolved value';
}, 10, 3 );

// Required separately for when the tag appears inside a larger content string
// AND for any tag used in an element _conditions (conditions resolve via
// render_content, never render_tag).
add_filter( 'bricks/dynamic_data/render_content', function( $content, $post, $context = 'text' ) {
    if ( ! is_string( $content ) || false === strpos( $content, '{my_tag}' ) ) return $content;
    return str_replace( '{my_tag}', 'resolved value', $content );
}, 10, 3 );
```

- `render_tag` handles a standalone tag; `render_content` handles a tag embedded in a string (e.g. `tel:{my_tag}` — the prefix makes it a content string, not a standalone tag). A tag used in both contexts needs both filters.
- **Element `_conditions` resolve via `render_content`, not `render_tag`** — a gate tag registered only on `render_tag` passes through as a literal, and a literal is non-empty, so `empty_not` is always true and the gate never fires. Register both. Boolean gate tags should return `'1'` or `''`.
- **When you refactor to a shared prefix guard, make it cover every prefix the tag map exposes** — a new tag with a different prefix silently fails the guard. Both failure modes are in `03`.
- Inside the render filter, `\Bricks\Query::get_loop_object()` returns the current loop item if called during a loop iteration.
- ACF field names in Bricks dynamic tags use an underscore, not a colon: `{acf_video_duration}`, not `{acf:video_duration}`.
- As the tag count grows, refactor from one-tag-per-filter-body to a single map array that all three filters iterate.

## Typed visual settings (verified — TAB, 2026-05-28 / 2026-05-31)

Read back from builder-saved pages via the golden rule. These four were the library's long-standing gaps (`_typography`, `_padding`/`_margin`, `_background`, `_gridTemplateColumns`) — all now captured.

**Layout**
- `_display`: `"grid"` | `"flex"` · `_direction`: `"row"` | `"column"` (**layout elements only** — `_flexDirection` elsewhere) · `_alignItems`: `"center"` | `"start"` | `"stretch"` · `_justifyContent` · `_flexWrap`: `"wrap"`
- Bricks `block` / `container` **default to flex column**, so `_direction:"column"` + `_rowGap` works *without* setting `_display`. (A class that only does this is redundant with ACSS `.gap--N` — see `03`.)
- `_columnGap` / `_rowGap`: token or value — `"var(--space-m)"`, `"0.6rem"`. **Layout elements only** (`section`/`container`/`block`/`div`) — on any other element the gap key is `_gap`, and `_direction` is `_flexDirection`. Wrong key for the element type = silent no-emit; see `03`.
- `_gridTemplateColumns`: a **string** — `"90px 1fr auto"` or `"var(--grid-3)"`. Responsive via breakpoint suffix on the **outer** key: `"_gridTemplateColumns:tablet_portrait": "var(--grid-1)"`
- `_width` / `_height`: `"100%"`, `"90px"`, `"var(--width-xl)"` · `_widthMax`: `"560px"` (note the `'100%'` special case in `03`)
- `_overflow`: `"hidden"` · `_aspectRatio`: a **scalar string** — `"4/3"`, `"1"` (not an object)
- Absolute positioning: `_position: "relative"|"absolute"`, `_top`/`_right`/`_bottom`/`_left`: `".8rem"`, `_zIndex: "2"`

**Spacing**
```php
'_padding' => [ 'top' => 'var(--space-m)', 'bottom' => 'var(--space-m)' ],  // partial keys allowed
'_margin'  => [ 'left' => 'auto', 'right' => 'auto' ],                      // 'auto' for centering
```

**Typography**
```php
'_typography' => [
    'font-family'    => 'custom_font_169',   // named families MUST use custom_font_<id> — see 03
    'font-size'      => 'var(--h4)',         // or '0.85rem'
    'font-weight'    => '600',
    'letter-spacing' => '0.1em',
    'text-transform' => 'uppercase',
    'text-align'     => 'center',
    'line-height'    => '1.3',
    'color'          => [ 'raw' => 'var(--token)' ],   // {raw} alone works; fuller {id,name,raw} also valid
],
'_typography:tablet_portrait' => [ 'font-size' => '3rem' ],   // breakpoint on the OUTER key
```

**Background**
```php
'_background' => [ 'color' => [ 'raw' => 'var(--token)' ] ],   // {raw}-only works even for non-ACSS child-theme tokens
```

**Two-colour border.** Typed `_border` is single-colour. For e.g. a gold top plus a light all-round: set 1px all sides via typed `_border`, then add `_cssCustom: ".class{ border-top:3px solid var(--secondary); }"` (literal selector).

## Element envelope (verified — VMG, 2026-06-06)

The wrapper every element is written in. The traps in each field have their own `03` entries; this is the shape.

```php
[
  'id'       => 'abc123',   // 6-char alphanumeric, never all-numeric — see 03
  'name'     => 'block',
  'parent'   => 'xyz789',
  'children' => [ 'def456' ],   // MUST be populated — parent alone renders empty shells (03)
  'label'    => 'Optional builder label',
  'settings' => [
      '_cssGlobalClasses' => [ 'clsID1', 'clsID2' ],  // class IDs, NOT names — see 03
      '_cssClasses'       => 'raw-class other',        // string — dynamic-data-parsed
      '_cssId'            => 'my-hook',                // id attr; the stable PHP-filter hook (03)
      '_attributes'       => [ [ 'id' => 'a1', 'name' => 'href', 'value' => '/?add-to-cart={post_id}' ] ],
      'tag'               => 'custom',
      'customTag'         => 'article',
  ],
]
```

- Both `_cssClasses` (the raw string) and `_attributes[].value` are **dynamic-data-parsed** — `value: "/?add-to-cart={post_id}"` resolves per loop item, which is how you build per-item links with no PHP. See `03` for the loop-aware-tag delimiter caveat.
- Non-native tags: `tag: 'custom'` + `customTag: '…'` (see Block HTML tag options above for what's native).

## Photo-in-wrapper (the standard for any contained image)

Two elements — a wrapper (block) carrying the box, an image inside carrying object-fit. **Do not collapse this into an image-as-figure for aspect-ratio'd photos** — the image element's `_aspectRatio` dual-routes to the inner img and the figure collapses (`03`).

```php
// wrapper class (block; add tag:"figure" if it needs a figcaption)
[ '_aspectRatio' => '3/4', '_overflow' => 'hidden', '_background' => [...], '_border' => [ 'radius' => [...] ] ]
// image class
[ '_width' => '100%', '_height' => '100%', '_objectFit' => 'cover' ]
// image element settings
[ 'image' => [ 'useDynamicData' => '{featured_image}', 'size' => 'medium' ] ]
```
Circle (author photo): wrapper `_aspectRatio: "1"` + `_border.radius` = `var(--radius-circle)`.

## Query Loop — ACF field (relational rendering, no custom code)

Any ACF **Relationship** or **Post Object** field on the current post is auto-exposed as a query loop:

```json
{ "hasLoop": true, "query": { "objectType": "acf_post_related_service" } }
```

- Post Object → 1 iteration; Relationship → N. The loop item is the **related post**, so post-context tags (`{post_title}`, `{acf_<field>}`, `{post_terms_*}`) resolve per-item. **LINK tags do not** — see `03`.
- **Repeater** loops work too (`objectType: acf_<repeater>`), but subfield tags are namespaced: `{acf_<repeater>_<subfield>}`. The bare subfield tag prints literally.
- **`gallery` and `image` fields are NOT loopable** — they aren't `CONTEXT_LOOP`. Use a custom query type. See `03`.

## Hide-when-empty (two verified mechanisms)

- **Loop on the section element** — when the query returns 0, the section + subtree render 0 times → entirely absent. Cleanest whole-section hide.
- **`_conditions` on the section** — the Conditions schema above, `compare: "empty_not"`. Note conditions read an ACF `true_false` as raw `"1"`/`""` (not "True"/"False"), and custom gate tags need `render_content` registered. Both in `03`.

## Archive template conditions

```php
// CPT archive  (/projects/)
[ 'main'=>'archiveType', 'archiveType'=>['postType'], 'archivePostTypes'=>['project'] ]
// Taxonomy archive  (/projects/category/{term}/)
[ 'main'=>'archiveType', 'archiveType'=>['term'], 'archiveTerms'=>['project_category::all'] ]
// Specific page (scores 8 — beats a `main:any` default)
[ 'id'=>'cnd001', 'main'=>'ids', 'ids'=>[ $page_id ] ]
```
The keys are `archivePostTypes` / `archiveTerms` — **not** `postType` / `taxonomy`, which are silently ignored and match every archive (`03`). One template can hold both conditions. The built-in `post` type needs the `page_for_posts` workaround instead (`03`).

## post-content element

Renders `the_content()` with **no element settings** — just a Global Class. Style rendered descendants via that class's `_cssCustom` (`.class h2`, `.class blockquote`, …) since they have no typed control; add `scroll-margin-top` for in-content anchor jumps.

## Button (dynamic CTA)

```json
{ "text": "{acf_cta_text}", "style": "btn--primary",
  "link": { "type": "meta", "useDynamicData": "{acf_cta_url}" } }
```
`type` MUST be `"meta"` for dynamic hrefs — `"external"` is literal-`url`-only and emits **no href at all** (`03`).

## BricksExtras ProSlider

Element type is `xproslider`. Each slide block (typically `block` with `tag: "li"`) MUST carry the identity classes, or the SSR markup lacks them until Splide's JS initialises — slides flash unstyled, and screen readers that don't wait for JS miss the carousel semantics:

```php
'_hidden' => [ '_cssClasses' => 'x-slider_slide splide__slide' ]
```

**Control value types are inconsistent — read an existing slider before writing one:**

| Setting | Type |
|---|---|
| `pagination` | **boolean** (`true`/`false`) |
| `arrows` | **string** (`"true"`/`"false"`) — NOT boolean |
| `autoplay`, `pauseOnHover` | boolean |
| `interval`, `speed` | numeric strings |

`arrows: true` (boolean) is silently dropped. `listTag: 'ul'` + a slide with `tag:'li'` gives proper list semantics.

## Schemas not yet captured

- `_border` image/gradient backgrounds (`_background` beyond a flat color).
- Bricks native form element settings.

An incomplete library is expected. When you need one, discover it via the golden rule and append it here for the harvest.

---

## CSS handoff

All section CSS belongs in the core functionality plugin or the child theme `style.css`, per the styling-layers order in `01` — not left as per-page Bricks custom CSS. Bricks inline CSS is not cached, not minified, and duplicated across every page that uses it. The handoff happens at pre-launch as a separate sweep, not during the build.
