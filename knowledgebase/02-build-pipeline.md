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
2. **Everything else by WP-CLI** — one `wp eval-file`: `wp_set_current_user(1)`, read the option, merge type sizes / per-level Font Size Overrides (mobile=min, desktop=max = brand ranges) / scales / radius / button typography / fonts / line-heights / focus, then `\Automatic_CSS\Model\Database_Settings::get_instance()->save_settings( $merged, true )` — an **instance** method via the Singleton trait, never a static call (see `01`).
3. **Tokens** — pinned customs (`--cream`, a `--display` step above `--h1`, focus overrides) into ACSS Global CSS (the dashboard field formerly labeled Global SCSS) — the only token home, by law (`01` → Pinned custom tokens). This delivers **inline** after `automatic.css` (`03` → ACSS), so `:root` overrides win the cascade. Irregular heading ladders are not tokens — they go into per-level Font Size Overrides in step 2, never as `--h1`–`--h6` re-declarations in any CSS home.
4. **Verify on the rendered front end, not the on-disk files** — `curl` and grep the inline `automaticcss-core-inline-css` block + compiled `automatic.css` for palette hexes, per-level clamp sizes, button vars, and that custom overrides win. Pinned tokens specifically: confirm every one resolves — `getComputedStyle(document.documentElement).getPropertyValue('--token')` in the console, or grep the inline block. Any move or re-home of tokens is not complete until this passes and only then is the old copy deleted (`00` → doc/reality mismatches).
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

**A sibling install is a legitimate golden-rule source.** When this install has no builder-saved example of a shape, read one from another project's builder-saved output — `bricks_global_classes` (an option, so no builder session needed) or a template's `_bricks_page_content_2`. It is builder-saved output either way, and cheaper than a discovery session. It is weaker only when the Bricks versions diverge, so compare versions first. Several WCDP schemas below were lifted this way from MMHN and then proven rendering on WCDP.

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

1. **Construct the element tree.** Build the section's Bricks element array — SECTION > CONTAINER > BEM elements per the structure in `01`. Each element is `{ id, name, parent, settings, ... }`. Element IDs must be alphanumeric and never all-numeric (see `03`); 6 characters is the house convention for new IDs, not a Bricks requirement — 5-character IDs pass the validator and survive a builder save (WCDP, 2026-08-18), though they have not been tested against `{query_results_count_filter:<id>}`. A script's ID guard validates only the IDs that script adds, never an inherited tree: an inherited page built on 5-character IDs is not corrupt, and a whole-tree guard aborts on it as if it were.

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

**Loop riding an archive's main query** (verified — WCDP, 2026-08-20, builder-saved readback from a sibling install, proven rendering):

```php
'hasLoop' => true,
'query'   => [ 'objectType' => 'post', 'post_type' => [ 'my_cpt' ],
               'is_archive_main_query' => true, 'posts_per_page' => 12 ],
```

`posts_per_page` merges into the main query, and so do `orderby` / `order` (Bricks' defaults when omitted), which clobbers a plugin's `pre_get_posts` ordering at the same priority — keep ordering in PHP at priority 20. See `03`.

**Scoping a native posts loop from PHP** (source-verified — WCDP, 2026-08-20, Bricks 2.3.10, `includes/query.php`):

```php
// apply_filters( 'bricks/posts/query_vars', $query_vars, $settings, $element_id, $element_name )
add_filter( 'bricks/posts/query_vars', function ( $vars, $settings, $element_id ) {
    if ( false === strpos( $settings['_cssClasses'] ?? '', 'prefix-upcoming-loop' ) ) return $vars;
    // merge meta_query / orderby here; posts_per_page flows through from the element
    return $vars;
}, 10, 3 );

// the loop element:
'query'       => [ 'objectType' => 'post', 'post_type' => [ 'my_cpt' ], 'posts_per_page' => 3 ],
'_cssClasses' => 'prefix-upcoming-loop',   // marker class = the hook key
```

- Key the hook on a **marker class in `_cssClasses`** — not the element id (changes on duplicate) and not `_cssId` (duplicated per loop iteration).
- Prefer this native-loop-plus-filter shape over a custom query type for post-card loops: native loops resolve per-item LINK-context tags correctly, custom types do not reliably `setup_postdata()` (`03`). Verified with distinct per-item hrefs; the shape survived a builder save unstripped.

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

**Static image** (verified — WCDP, 2026-08-20, builder-saved readback):

```php
'image'   => [ 'id' => 123, 'filename' => 'x.webp', 'full' => '<full-url>', 'size' => 'full', 'url' => '<sized-url>' ],
'altText' => '…',          // top level, as above
'loading' => 'eager',      // top-level scalar (elements/image.php controls['loading'])
```

Static images default to lazy **even as the LCP hero** — set `loading: 'eager'` on the above-the-fold image.

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

**Not Block-only.** `text-basic` registers the same `tag` + `customTag` pair (`includes/elements/text-basic.php`), so `'tag' => 'custom', 'customTag' => 'cite'` renders `<cite class="brxe-text-basic …">`, and `dt` / `dd` work the same way. With a block at `customTag: 'dl'` / `'blockquote'`, a full `<dl><div><dt>/<dd></div></dl>` or `<blockquote><p><cite>` builds with no custom PHP (verified — WCDP, 2026-08-18). Confirm the control exists on any other element before relying on it: `grep -n "customTag" wp-content/themes/bricks/includes/elements/<element>.php`.

**`<time>` with a machine-readable date** (builder-verified — WCDP, 2026-09-11):

```php
'tag' => 'custom', 'customTag' => 'time',
'_attributes' => [ [ 'id' => 'a1', 'name' => 'datetime', 'value' => '{acf_my_date:Y-m-d}' ] ],
```

The dynamic tag resolves inside the attribute; the bare ACF date modifiers (`:Y-m-d`, `:j`, `:M Y`) all render.

**A real `<button>`** (verified — WCDP, 2026-09-14) for a dismiss/toggle control that does not navigate:

```php
'tag' => 'custom', 'customTag' => 'button',
'_attributes' => [
    [ 'id' => 'a1', 'name' => 'type',       'value' => 'button' ],
    [ 'id' => 'a2', 'name' => 'aria-label', 'value' => 'Close announcements' ],
],
```

Renders `<button class="…" type="button" aria-label="…">`. `customTag` is sanitised against `Helpers::get_allowed_html_tags()`, which on Bricks 2.4.2 starts from `wp_kses_allowed_html('post')` — `button` is in that list natively, so no `bricks/allowed_html_tags` filter is needed for it. Confirm on the install: `wp eval 'echo implode(" ", \Bricks\Helpers::get_allowed_html_tags());'`. Do not use the Bricks Button element for a non-navigating control: it sets its tag to `a` as soon as `settings['link']` is non-empty.

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

Nested array shape: **the outer array is a list of condition SETS, and the logic BETWEEN sets is OR. Rules INSIDE a set are ANDed together.** For a simple "hide if field empty": one set, one rule.

```php
// OR — render if ANY network is set (one rule per set)
'_conditions' => [
    [ [ 'id'=>'cnd001', 'key'=>'dynamic_data', 'dynamic_data'=>'{tag_a}', 'compare'=>'empty_not' ] ],
    [ [ 'id'=>'cnd002', 'key'=>'dynamic_data', 'dynamic_data'=>'{tag_b}', 'compare'=>'empty_not' ] ],
],

// AND — render only if BOTH are set (both rules in one set)
'_conditions' => [
    [
        [ 'id'=>'cnd001', 'key'=>'dynamic_data', 'dynamic_data'=>'{tag_a}', 'compare'=>'empty_not' ],
        [ 'id'=>'cnd002', 'key'=>'dynamic_data', 'dynamic_data'=>'{tag_b}', 'compare'=>'empty_not' ],
    ],
],
```

Authoritative source: `bricks/includes/conditions.php` → `Conditions::check()` — "Loop over condition sets (logic between sets: OR)" wrapping "Loop over conditions inside a set (logic inside a set: AND)". A set short-circuits to false on its first failing rule.

**Correction, Clemente 2026-07-25.** This entry previously said the opposite ("outer array is AND groups, inner arrays are OR rules within a group"). Writing an intended OR as three rules in one set silently hid the element: two of the three dynamic tags were empty, the AND failed, and the element vanished with no error. The one-set-one-rule advice for the common hide-when-empty case was correct and unchanged by this — which is why the error survived so long. Verified against `conditions.php` and confirmed empirically on both branches (one network set → renders; all networks empty → hidden).

## Global Class shape

```json
{
  "id": "abc123",
  "name": "class-name",
  "settings": {
    "_cssCustom": "/* CSS goes here */"
  },
  "modified": 1776526083000,
  "user_id": 1
}
```

- `id`: alphanumeric, never all-numeric; 6 characters is the house convention.
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
- **Grid alignment uses separate keys.** On a `_display: "grid"` class, `_alignItems` / `_justifyContent` are the *flex* keys and emit `align-items: initial` — the value you wrote is replaced, not dropped. Use `_alignItemsGrid` / `_justifyItemsGrid` / `_justifyContentGrid` / `_alignContentGrid` (`includes/elements/container.php`, gated on `_display`). Verified — WCDP, 2026-09-13. Mechanism in `03`.
- `_gridTemplateColumns`: a **string** — `"90px 1fr auto"` or `"var(--grid-3)"`. Responsive via breakpoint suffix on the **outer** key: `"_gridTemplateColumns:tablet_portrait": "var(--grid-1)"`. **Layout elements only**, like the gap keys (`03`).
- `_gridAutoRows` / `_gridAutoColumns`: plain text, raw CSS (`"1fr"`) · `_gridAutoFlow`: select (`"row"` …). On the grid element/class; the UI gate on `_display = grid` is panel-only, the emitter just needs the class to set `_display: "grid"`. `_gridAutoRows: "1fr"` gives equal-height card rows with no measured min-height — **cancel it where the grid drops to one column** (`"_gridAutoRows:mobile_landscape": "auto"`) or every stacked card stretches to the tallest. Verified — WCDP, 2026-08-19.
- **Grid-item controls**, on the child (verified — WCDP, 2026-08-18; control definitions re-read on Bricks 2.4.2):
  - `_gridItemColumnSpan` → `grid-column`, `_gridItemRowSpan` → `grid-row`: plain **text** controls, so the value is raw CSS — `"span 2"`, `"1 / 3"`, `"auto"`, never `2`. Registered in `container.php`, so **layout elements only**.
  - `_gridItemJustifySelf` → `justify-self`: an `align-items`-type control (`"center"`, `"start"`, …), registered on layout elements and on every other element via `base.php`.
  - There is **no** `_gridItemAlignSelf` on Bricks 2.4.2 — don't write it.
  - All take a breakpoint suffix on the outer key. A column span **must** be reset where the grid drops to one column (`"_gridItemColumnSpan:mobile_landscape": "auto"`): `span 2` in a one-column grid creates an implicit second column and overflows. Verify the reset landed inside the `@media` block (`03`).
- `_display` takes a breakpoint suffix too: `"_display:tablet_portrait": "none"` emits `@media (max-width: 991px) { .class { display: none } }` — the typed desktop-only hide, no `_cssCustom` media query (verified — WCDP, 2026-08-18).
- `_flexGrow`: `"1"` (emits `flex-grow: 1`; defined in `base.php` and `container.php`) — survived a builder save (WCDP, 2026-09-11).
- `_width` / `_height`: `"100%"`, `"90px"`, `"var(--width-xl)"` · `_widthMax`: `"560px"` (note the `'100%'` special case in `03`). `_widthMax: "none"` emits `max-width: none` — needed for full-bleed negative-margin work, since Bricks caps every element at `max-width: 100%` (`03`; builder-verified — WCDP, 2026-09-11)
- `_overflow`: `"hidden"` · `_aspectRatio`: a **scalar string** — `"4/3"`, `"1"` (not an object)
- Absolute positioning: `_position: "relative"|"absolute"`, `_top`/`_right`/`_bottom`/`_left`: `".8rem"`, `_zIndex: "2"`

**Spacing**
```php
'_padding' => [ 'top' => 'var(--space-m)', 'bottom' => 'var(--space-m)' ],  // partial keys allowed
'_margin'  => [ 'left' => 'auto', 'right' => 'auto' ],                      // 'auto' for centering
'_padding:mobile_landscape' => [ 'top' => '4px', 'bottom' => '4px' ],    // partial keys hold at breakpoints too
```

Partial keys work at breakpoints because typed spacing emits **longhands** (`padding-top`, …), never the shorthand, so the base sides a breakpoint object omits are untouched. Restating unchanged sides is noise — prune to the deliberate deviation. Verified — WCDP, 2026-09-14 (base `padding-left` held across the boundary).

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

**Pseudo-state suffix and transition** (verified — WCDP, 2026-08-19, read back from builder-saved global classes on a sibling install):
```php
'_border:hover'     => [ 'color' => [ 'raw' => 'var(--primary)' ] ],  // partial: only the changed props
'_typography:hover' => [ 'color' => [ 'raw' => 'var(--token)' ] ],    // text-decoration etc. too
'_cssTransition'    => 'border-color .2s ease, transform .2s ease',   // plain string, raw CSS value
```
The `:hover` suffix rides the **outer** key exactly like a breakpoint suffix, and the value is a *partial* of the base control's shape. Emits `.class:hover { … }` at `(0,2,0)`. Element-level control keys take it too (`subMenuBackground:hover`, `buttonBorder:hover` on nav/pagination elements).

**Two-colour border.** Typed `_border` is single-colour. For e.g. a gold top plus a light all-round: set 1px all sides via typed `_border`, then add `_cssCustom: ".class{ border-top:3px solid var(--secondary); }"` (literal selector).

## Element envelope (verified — VMG, 2026-06-06)

The wrapper every element is written in. The traps in each field have their own `03` entries; this is the shape.

```php
[
  'id'       => 'abc123',   // alphanumeric, never all-numeric; 6 chars = house convention — see 03
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
{ "hasLoop": true, "query": { "objectType": "acf_<field_name>" } }
```

**`objectType` is exactly `acf_<field_name>` — no fixed infix.** Bricks registers the loop tag as literally `'acf_' . $field['name']` (`provider-acf.php`). A field named `performers` is `acf_performers`, full stop — do not pattern-match a longer field name (e.g. a project's own `post_related_service` field, which is `acf_post_related_service` only because that's its literal name) into a perceived `acf_post_related_<x>` convention. When in doubt, grep `provider-acf.php`'s tag-registration line rather than inferring from an example.

- Post Object → 1 iteration; Relationship → N. The loop item is the **related post**, so post-context tags (`{post_title}`, `{post_url}`, `{acf_<field>}`, `{post_terms_*}`) resolve per-item — **including LINK-type settings** (a `link: {type:'meta', useDynamicData:'{post_url}'}` control resolves correctly per item), provided the loop is anchored correctly (see the `hasLoop` placement note below). An earlier version of this entry said LINK tags do not resolve per-item; that was a misdiagnosis of the placement bug, not a real limitation — corrected th-members, 2026-08-30, see `03`.
- **`hasLoop` only works on `block` / `div` / `section`** (anything extending `Element_Container` — that's the only code path that actually dispatches a `\Bricks\Query` and repeats the element). Setting `hasLoop` on a leaf element (`text-link`, `heading`, `text-basic`, etc.) is silently accepted and persists in the DB, but does nothing — the element renders once, in the *parent* loop's context. Nest the tag-bearing leaf element one level inside a looped container instead; it inherits the correct context automatically. Full incident: `03`.
- **Repeater** loops work too (`objectType: acf_<repeater>`), including **options-page** repeaters (WCDP: five of them, builder-save verified, 2026-08-22), but subfield tags are namespaced: `{acf_<repeater>_<subfield>}`. The bare subfield tag prints literally.
- **On a repeater loop the loop object is a row array, not a `WP_Post`.** `\Bricks\Query::get_loop_object()` returns whatever the loop iterates; for `acf_<repeater>` that is the row's associative array keyed by sub-field name. A custom tag that assumes `instanceof \WP_Post` silently renders empty text there. Branch on the type (verified — WCDP, 2026-08-22):
  ```php
  $row = \Bricks\Query::get_loop_object();
  if ( is_array( $row ) && ! empty( $row['my_subfield'] ) ) { /* repeater row: read sub-fields by name */ }
  elseif ( $row instanceof \WP_Post )                     { /* post loop: use $row->ID */ }
  ```
  This is what makes computed-from-the-row tags possible (e.g. initials derived from a `name` sub-field, so the client never maintains a second field in step with the first).
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
The keys are `archivePostTypes` / `archiveTerms` — **not** `postType` / `taxonomy`, which are silently ignored and match every archive (`03`). One template can hold both conditions. The built-in `post` type needs the `page_for_posts` workaround instead (`03`). The archive template's `_bricks_template_type` is `'archive'`.

**CPT single template** (verified — WCDP, 2026-08-20, builder-saved readback from a sibling install): the type is **`'content'`, not `'single'`**:
```php
update_post_meta( $id, '_bricks_template_type', 'content' );
// _bricks_template_settings:
'templateConditions' => [ [ 'main' => 'postType', 'postType' => [ 'my_cpt' ] ] ]
```

**Error (404) template** (verified — WCDP, 2026-08-18, Bricks 2.3.10; from `includes/database.php` — `is_404()` → content type `error`, condition `main === 'error'` scores 8 — and proven by a live 404 render with full header/footer):
```php
update_post_meta( $id, '_bricks_template_type', 'error' );
update_post_meta( $id, '_bricks_editor_mode', 'bricks' );
update_post_meta( $id, '_bricks_template_settings', [
    'templateConditions' => [ [ 'id' => 'cnderr', 'main' => 'error' ] ],
] );
// tree goes in _bricks_page_content_2 — error renders as a content-type template
```
Same map: `is_search()` → type `'search'` with condition `main: 'search'`; archives → `'archive'`.

## post-content element

Renders `the_content()` with **no element settings** — just a Global Class. Style rendered descendants via that class's `_cssCustom` (`.class h2`, `.class blockquote`, …) since they have no typed control; add `scroll-margin-top` for in-content anchor jumps.

## Button (dynamic CTA)

```json
{ "text": "{acf_cta_text}", "style": "btn--primary",
  "link": { "type": "meta", "useDynamicData": "{acf_cta_url}" } }
```
`type` MUST be `"meta"` for dynamic hrefs — `"external"` is literal-`url`-only and emits **no href at all** (`03`).

- A button with **no `link` setting** renders as `<span class="bricks-button …">` — fully styled, no anchor, inert (verified — WCDP, 2026-08-20). Useful deliberately; also the tell when a button unexpectedly won't click — check for a missing or invalid `link` key.
- The reverse: once `link` is non-empty the element renders `<a>`, so it is the wrong element for a control that does not navigate — use a layout element with `customTag: 'button'` (Block HTML tag options above).
- Icon on a button: see Icon settings below.

## Icon settings — custom icon sets (verified — WCDP, 2026-08-18)

Custom icon sets are **`wp_options` state, not code**: Bricks has no filter to register one, and each icon row binds to an attachment ID on that install. Rebuild the set per install from the SVG files — `bin/bricks-icon-import.php` in this repo does it idempotently (`ICON_SET=<name> ICON_SRC=<dir|files> wp eval 'include ".../bin/bricks-icon-import.php";'`, `ICON_DRY=1` to preview). The silent-nothing failure when an attachment doesn't resolve is in `03`.

Option shapes (read from a working install):
```json
// bricks_icon_sets
[ { "id": "set_abc123xyz", "name": "MySet" } ]

// bricks_custom_icons — one entry per icon
[ { "id": "icon_def456uvw", "name": "arrow-right-line",
    "url": "https://<site>/wp-content/uploads/<yyyy>/<mm>/arrow-right-line.svg",
    "setId": "set_abc123xyz", "attachment_id": 53 } ]
```

Element setting — **identical on `button`, `icon` and `text-link`** (8 usages read back from a builder-saved page):
```php
'icon' => [
    'library' => 'custom_set_abc123xyz',       // "custom_" . <setId>
    'svg'     => [ 'id' => 59, 'icon_id' => 'icon_def456uvw', 'url' => '<url>' ],   // id = attachment, icon_id = the icon row
],
'iconPosition' => 'right',                     // button + text-link; absent on the icon element
```

- ⚠️ **The `svg` element's icon variant is NOT verified.** It is asserted elsewhere to use `iconSet` with `source: "iconSet"`, but no builder-saved example has been read back. Discover it before using it.
- Bake `fill="currentColor"` and `aria-hidden="true"` into the files at import: Bricks inlines the SVG verbatim, and a button's icon renders with no attributes at all, so there is no per-element place to set either. `render_svg()` replaces an existing `aria-hidden` rather than duplicating it.
- Bricks inlines the file contents, so the filename never appears in rendered HTML — don't count icons by grepping for it. Icons inside a query loop render once per iteration.
- The same `icon` shape feeds BricksExtras icon controls (`prevIcon` / `nextIcon` on the Pro Slider Control below).

## BricksExtras ProSlider

Element type is `xproslider`. Each slide block (typically `block` with `tag: "li"`) MUST carry the identity classes, or the SSR markup lacks them until Splide's JS initialises — slides flash unstyled, and screen readers that don't wait for JS miss the carousel semantics:

```php
'_hidden' => [ '_cssClasses' => 'x-slider_slide splide__slide' ]
```

**Control values are typed by control, and the wrong type fails OPEN — read the control definition before writing one** (`grep -n -A10 "controls\['<key>'\]" wp-content/plugins/bricksextras/components/classes/x-pro-slider.php`). The builder never produces an invalid value, so a builder-saved readback does not protect a CLI write here:

| Control type | Examples | How BE reads it | Write |
|---|---|---|---|
| checkbox | `pagination`, `pauseOnHover`, `pauseOnFocus` | `isset( $settings['x'] )` | **`true`, or omit the key.** `false` is *set*, so **`false` = ON** — `pagination => false` renders dots. |
| checkbox, value passed through | `rewind` | `isset()` then the value | `true`; `false` happens to work here, but omit it anyway — the checkbox rule is the safe default |
| select of strings | `arrows` (`'true'`/`'false'`), `keyboard` (`'false'`/`'focused'`/`'global'`), `autoplayscroll` (`'autoplay'`/`'autoscroll'`/`'none'`) | value passed through | **One of the literal option strings.** A boolean is not an option: `keyboard => true` was coerced to `'global'` and hijacked the arrow keys document-wide. |
| numeric strings | `interval`, `speed` | value | `'4000'` |

`arrows => true` (boolean) is silently dropped. There is no `autoplay` key on BE 1.7.4 (this table once listed one as a boolean) — autoplay is `autoplayscroll => 'autoplay'`. A builder save did not reintroduce omitted checkbox keys (BE 1.7.1), but re-read after one anyway. Same `isset()` mechanism as the Bricks Form `redirectAdminUrl` below; full entry in `03`. **Correction, WCDP 2026-09-14:** this table previously listed `pagination` and `pauseOnHover` as booleans; that shape rendered dots with `pagination => false`.

`arrows => 'false'` is correct but BE copies the raw string into its per-breakpoint config, where `"false"` is truthy, so Splide builds a `.splide__arrows` wrapper anyway. **Harmless** — BE's own CSS hides any `.splide__arrows:not(.x-splide__arrows)`, expecting arrows to come from `xproslidercontrol` (below). Don't write a rule to hide them.

`listTag: 'ul'` + a slide with `tag:'li'` gives proper list semantics.

**Slide padding — two traps, both on the slide:**
- BE's `proslider.css` ships `.x-slider_slide { padding: 4rem 1rem }` — right for card carousels, wrong for a ticker, logo strip or quote rotator (a single text line became a 173px band). Zero it with a typed `_padding` on your own slide class; typed won against BE's `(0,1,0)` with no escalation (WCDP, 2026-09-14).
- `slidePadding` is **not** Splide's `padding` option — it emits `#brxe-<id> .x-slider_slide { padding }` at ID specificity, crushing the card's own padding. Leave it unset when the slide *is* the card (WCDP, 2026-09-11; `03`).

**Query-loop slide** (builder-verified — WCDP, 2026-09-11, zero strips):
```php
[ 'name' => 'xproslider', 'settings' => [
    'perPage' => 3, 'perPage:tablet_portrait' => 2, 'perPage:mobile_landscape' => 1,   // breakpoint suffix on the outer key
    'perMove' => 1, 'gap' => '32px', 'arrows' => 'false', 'rewind' => true, 'listTag' => 'ul' ] ],   // arrows STRING; pagination omitted = none
[ 'name' => 'block', 'parent' => '<slider-id>', 'settings' => [
    'tag' => 'li', '_hidden' => [ '_cssClasses' => 'x-slider_slide splide__slide' ],
    'hasLoop' => true, 'query' => [ 'objectType' => 'post', 'post_type' => [ 'my_cpt' ], 'posts_per_page' => 6 ],
    '_cssClasses' => 'prefix-marker' ] ],
```
`_hidden._cssClasses` and the user `_cssClasses` both render (verified in the DOM).

## BricksExtras Pro Slider Control — nav arrow (verified — WCDP, 2026-09-11, BE source `x-pro-slider-control.php` + builder save)

```php
[ 'name' => 'xproslidercontrol', 'settings' => [
    'controlType' => 'navArrow', 'navType' => 'prev',   // 'next' + nextIcon / nextAriaLabel for the other
    'slider'      => 'section',                         // finds the slider in the same <section>
    'buttonType'  => 'icon',
    'prevIcon'    => [ 'library' => 'custom_set_abc123xyz', 'svg' => [ 'id' => 115, 'icon_id' => 'icon_def456uvw', 'url' => '<url>' ] ],
    'prevAriaLabel' => 'Previous items' ] ],
```

Renders `<div class="x-slider-control" data-x-slider-control='{…"type":"navArrow"}'><button class="x-slider-control_nav x-slider-control_nav--prev" type="button" disabled aria-label="…">`. The button ships `disabled` and BE's JS enables it — curl shows it disabled. Style through a global class on the root: the `navbutton*` controls are element-level BE CSS and hit the CLI-regen gap (`03`).

## Bricks native Form element — auth action settings (verified — Highland, 2026-08-04, Bricks 2.3.10)

Read back from working login / lost-password / reset-password forms. The action set lives in **`actions` (plural array)** — not `action`. Field bindings are by **field id**, not name:

```php
// LOGIN
'actions'          => [ 'login', 'redirect' ],
'loginName'        => 'e5b556',   // <- ids from settings.fields[].id
'loginPassword'    => 'ba4114',
'loginRemember'    => 'grxbiw',
'redirectAdminUrl' => true,       // present = redirect to wp-admin

// LOST PASSWORD
'actions'                   => [ 'lost-password' ],   // note the hyphen
'lostPasswordEmailUsername' => 'e5b556',

// RESET PASSWORD
'actions'          => [ 'reset-password', 'redirect' ],
'resetPasswordNew' => 'e5b556',
'redirect'         => '{site_login}',   // dynamic tags ARE rendered here
```

- `redirect` + `redirectAdminUrl` are **not** mutually exclusive — the admin branch overwrites the rendered redirect and re-wraps the *raw* string. To use a dynamic redirect, `redirectAdminUrl` must be **unset**, not `false` (the code tests `isset()`). Full mechanism in `03`.
- The reset form needs **no** hidden key/login fields — Bricks renders `form-field-key` / `form-field-login` itself from `$_GET` whenever `resetPasswordNew` is set and `reset-password` is in `actions`.
- `?redirect_to=` is honoured **only** when no redirect action is configured.

**Form styling keys and the password toggle** (verified — WCDP, 2026-09-13, Bricks 2.3.13; read back from builder-saved auth pages on a sibling install, rendered and function-tested on WCDP):

Class-level styling keys on the `form` element that emit — typed shapes, so they go on a global class like any other typed setting:
```php
'labelTypography'             => [ … ],   // typography shape, incl. text-transform
'fieldTypography'             => [ … ],
'placeholderTypography'       => [ … ],
'fieldBackgroundColor'        => [ 'raw' => 'var(--white)' ],
'fieldBorder'                 => [ … ],   // flat _border shape
'fieldPadding'                => [ 'top' => '…', 'right' => '…', 'bottom' => '…', 'left' => '…' ],
'fieldMargin'                 => [ … ],   // side object
'submitButtonWidth'           => '100',
'submitButtonBackgroundColor' => [ 'raw' => 'var(--primary)' ],
'submitButtonTypography'      => [ … ],
'submitButtonBorder'          => [ … ],
'submitButtonMargin'          => [ … ],
```
There is **no submit hover control** — the hover state is the one legitimate `_cssCustom` on a form class.

Password field with the eye toggle (an entry in `settings.fields[]`):
```php
[ 'type' => 'password', 'passwordToggle' => true,
  'passwordShowIcon' => [ 'library' => 'svg', 'svg' => [ 'id' => <att>, 'url' => '<url>', 'filename' => 'eye.svg' ] ],
  'passwordHideIcon' => [ 'library' => 'svg', 'svg' => [ … ] ] ]
```
Renders `.password-input-wrapper > button.password-toggle`. `rememberme` and `html` field types take `'width' => '50'` for a half-width pair. Importing the SVG attachments from WP-CLI needs `wp --user=1 media import` (`03`).

## BricksExtras Pro OffCanvas + Burger Trigger (verified — Highland, 2026-07-02, BE 1.6.9)

```php
'name' => 'xoffcanvasnestable', // one nestable child block = panel content
'settings' => [
  'direction'            => 'x-offcanvas_right',      // x-offcanvas_{left|right|top|bottom}
  'offcanvas_width'      => ['top'=>'400','right'=>'400','bottom'=>'400','left'=>'400'],
  'aria_label'           => 'Mobile menu',            // default "Offcanvas"; role default dialog
  'auto_aria_control'    => 'true',                   // writes aria-controls onto triggers
  'sync_burger_triggers' => 'true',                   // burger inside the panel = close button
  'clickTrigger'         => '.brxe-xburgertrigger',   // selector; matches ALL burgers (open + close)
  'backdrop_color'       => ['raw'=>'var(--black-trans-50)'],
  'backdrop_to_close'    => true,
  'esc_to_close'         => 'true',   // DEFAULTS OFF — set explicitly
  'trapFocus'            => 'true',   // exists (undocumented in BE docs); DEFAULTS OFF
  'preventScroll'        => 'true',   // DEFAULTS OFF; returnFocus defaults ON
  'reduce_motion'        => 'notransition', // fade | slide | notransition
],
// Burger: 'name' => 'xburgertrigger', 'settings' => [ 'aria_label' => 'Open main menu' ]
```

⚠️ **The three a11y flags default OFF.** Set `esc_to_close` / `trapFocus` / `preventScroll` explicitly on every build. Renders `.x-offcanvas_backdrop` + `.x-offcanvas_inner` (role=dialog, `inert` when closed); config lands in `data-x-offcanvas` JSON — verify via curl. BE base CSS gives the inner 300px width + 30px padding; zero it on your own class. Burger renders a real `<button>` with aria wired at runtime by JS (curl won't show it).

## BricksExtras Before/After Image (verified — Highland, 2026-07-11, BE 1.6.9)

```php
'name' => 'xbeforeafterimage',
'settings' => [ 'maybeLabels' => true ],  // 'start' => 50, 'direction' => horizontal|vertical — defaults fine
// EXACTLY two children, each a `block` with the hidden BE class; each holds one `image`:
// block:  '_hidden' => ['_cssClasses' => 'x-before-after-image_block']
// image:  'image' => ['useDynamicData' => '{acf_before_image}', 'size' => 'medium'],
//         'caption' => 'none', '_hidden' => ['_cssClasses' => 'x-before-after-image_image']
```

- **Child order is semantic:** child 1 = *before* (absolutely positioned, clip-path from left), child 2 = *after* (static, defines flow height). BE CSS keys off `:nth-of-type()`.
- Works inside a query loop with dynamic images — verified over 8 iterations.
- Labels ship unpositioned; their controls are element-level BE CSS → the CLI-regen gap (`03`). Bridge on your own global class.
- **Height chain for an aspect-pinned wrapper:** `.wrapper .brxe-xbeforeafterimage, .wrapper .x-before-after_container, .wrapper .x-before-after-image_block { height:100% }` + `img { width/height:100%; object-fit:cover }`.
- Front-end JS bails inside the builder iframe — **blank in canvas is expected**.

## BricksExtras Pro Slider Gallery (verified — Highland, 2026-07-03, BE 1.6.9)

Child of a `galleryMode` Pro Slider; renders the `ul.splide__list` itself (BE's "place outside the Pro Slider" doc text is a copy-paste error — it goes INSIDE).

```php
'name' => 'xproslidergallery',
'settings' => [
  'items' => [ 'images' => [ ['id'=>73,'full'=>'<full-url>','url'=>'<sized-url>'] ], 'size' => 'medium' ],
  'objectFit'       => 'cover',
  'lazyLoadSupport' => 'none',      // none | splide (default) | bricks
  'maybeSRCSET'     => 'enable',    // disable (default) | enable
],
```

⚠️ **Above the fold, `lazyLoadSupport` must be `'none'`** — the `splide` default ships a placeholder data-URI `src` and makes the LCP element a 0×0 SVG. And `maybeSRCSET` defaults to *disabled*, so without it you ship the full-size original. On the parent `xproslider`: `rewind => true` is REQUIRED for fade+autoplay to cycle back, and `arrows` is a **string** `'true'`/`'false'` while `pagination` is an `isset()` checkbox — `true` or omit, never `false` (see the ProSlider table above).

## BricksExtras Breadcrumbs (verified — WCDP, 2026-09-12, BE 1.7.1 source `x-breadcrumbs.php` + builder save)

⚠️ BE elements render nothing until switched on in BricksExtras → Elements (`03`). Confirm `isset( \Bricks\Elements::$elements['xbreadcrumbs'] )` before writing the tree.

```php
[ 'name' => 'xbreadcrumbs', 'settings' => [
    'ariaLabel'       => 'Breadcrumb',
    'maybeSchema'     => 'disable',   // DEFAULTS ON — emits BreadcrumbList microdata
    'maybeCPTarchive' => 'enable',    // DEFAULTS OFF — adds the CPT archive crumb
    'separator'       => '›',
] ]
```

- Every `maybe*` control is a select with **`'enable'` / `'disable'` strings**, not booleans.
- `maybeSchema` defaults ON and duplicates a plugin- or PHP-owned BreadcrumbList JSON-LD — disable it wherever schema lives in code. The current crumb keeps a stray `itemprop="name"` span even with schema off (no `itemscope`, so inert).
- `separator` emits `#brxe-<id> ol { --x-breadcrumb-separator: "›" }` — element-level CSS, so it needs the CSS regen.
- Renders `nav.brxe-xbreadcrumbs[aria-label] > ol.x-breadcrumbs_list > li.x-breadcrumbs_list-item`, `aria-current="page"` on the last `li`. Style through a global class on the root: `.my-crumbs a`, `.my-crumbs [aria-current="page"]`, `li:not(:first-child)::before` for the separator, `--x-breadcrumbs-gap` for spacing.

## Theme Style keys — root font-size and default heading tag (verified — WCDP, 2026-08-11)

`01`'s Theme Style requirements live in `bricks_theme_styles`, not `bricks_global_settings`, under keys that don't match the UI labels. There is no `defaultHeadingTag` setting under any name (`03`).

```php
$ts = get_option( 'bricks_theme_styles', [] );
$ts[ $k ]['settings']['typography']['typographyHtml'] = 'var(--root-font-size)';   // "HTML: font-size"
$ts[ $k ]['settings']['heading']['tag']               = 'h2';                      // unset = h3
update_option( 'bricks_theme_styles', $ts );
```

The control definitions are authoritative for both the group key and the control key: `includes/theme-styles/controls/typography.php` and `includes/theme-styles/controls/element-heading.php` each `return [ 'name' => …, 'controls' => … ]`. The heading element reads `$this->theme_styles['tag'] ?? 'h3'`, so an unset tag is h3, not h2. `htmlFontSize` in `includes/i18n.php` belongs to the Style Manager and is a red herring.

## Bricks custom fonts — `bricks_font_faces` meta shape (verified — Highland, 2026-06-13)

Each custom font is a **`bricks_fonts`** post (plural post type); faces live in post meta `bricks_font_faces`, keyed by weight. Normal weights use an INTEGER key; **italics use a STRING key `"<weight>italic"`**:

```php
get_post_meta( $font_id, 'bricks_font_faces', true ) === [
  500         => [ 0 => [ 'woff2' => 14 ] ],   // normal weight  → upright file
  '500italic' => [ 0 => [ 'woff2' => 16 ] ],   // italic → separate italic file
];
```

A single **variable** woff2 can back multiple discrete weight faces, but any weight you don't declare falls back to the nearest declared one — declare every weight the brand uses. Italics need a genuinely separate italic-axis file. Unlike `_bricks_page_*_2`, this key is **not** write-gated: a plain `update_post_meta` sticks.

## Schemas not yet captured

- `_border` image/gradient backgrounds (`_background` beyond a flat color).
- `_transform` / `_transform:hover` and `_boxShadow` / `_boxShadow:hover` — not found in any builder-saved class or tree on the fleet as of 2026-09. A hover lift + shadow currently lives in `_cssCustom`. (Nearby shape, proves nothing about the generic keys: `subMenuBoxShadow = { values: { offsetX, offsetY, blur }, color: { raw } }`.)
- The `svg` element's icon-set variant (see Icon settings).

An incomplete library is expected. When you need one, discover it via the golden rule and append it here for the harvest.

---

## CSS handoff

All section CSS belongs in the core functionality plugin or the child theme `style.css`, per the styling-layers order in `01` — not left as per-page Bricks custom CSS. Bricks inline CSS is not cached, not minified, and duplicated across every page that uses it. The handoff happens at pre-launch as a separate sweep, not during the build.

The sweep is implemented as `bricks-css-sweep.md` at the repo root: a per-page operation run at the Staging → Live boundary, once the page is visually locked and client-approved. It extracts page- and element-level custom CSS into the core plugin, then empties the Bricks fields. Do not run it during active development.
