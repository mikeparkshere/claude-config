# Claude Code × Bricks — Direct Edit Playbook

**Michael Parks Design — how to safely drive Bricks through Claude Code (WP-CLI, direct DB mutations) when the paste-to-Bricks workflow isn't the right tool.**

Version 1.0 — Captured April 2026 from the ext-mem video site build session. Companion to `wireframe-to-bricks-rules.md` (Claude.ai paste workflow) and `wireframe-to-bricks-system-design.md` (workflow reasoning).

---

## 1. When to use which surface

| Task | Surface |
|---|---|
| Generating new element trees from wireframes | **Claude.ai clipboard paste** (`wireframe-to-bricks-rules.md`) |
| Building or extending a template with Bricks UI controls available | **Bricks builder** (visual, authoritative) |
| Bulk ops: batch class creation, global class CSS population, custom query types, dynamic tags, CPT scaffolding, ACF fields, admin columns | **Claude Code direct DB edit** (this doc) |

The surfaces are complementary, not substitutes. When direct-DB edits stall (Bricks stripping your writes, specificity wars, missing schema knowledge), the fix is usually to drop down to the builder for schema discovery, then resume via Claude Code.

---

## 2. The Golden Rule

**Never guess Bricks internal schemas. Build ONE example of the thing you need in the builder, save, read it back, then replicate that shape programmatically.**

This is the single most reliable principle for direct-DB work. It's also the core insight of [`wpgaurav/bricks-skills`](https://github.com/wpgaurav/bricks-skills) — the repo teaches clipboard-paste JSON generation but the method applies identically here: use the builder's own output as the authoritative shape, not docs or guesses.

Discovery workflow:
1. Open the target template in the Bricks builder
2. Add the minimum version of the element you need (e.g., one Query Loop block with only Query Type set)
3. Save the template
4. Read it back via `wp post meta get <id> _bricks_page_content_2 --format=json`
5. Use the returned shape as the schema template

Every schema guess in this session that wasn't discovered this way got silently rejected by Bricks' JS-side validator on the next builder load. Every schema discovered this way stuck.

---

## 3. Authentication — non-negotiable for post meta writes

Bricks hooks `update_post_metadata` and **returns false when `current_user_can_use_builder()` fails**. WP-CLI runs with no current user by default, so direct `update_post_meta()` on `_bricks_page_content_2` (or header/footer equivalents) silently no-ops — no error, no warning, your write just doesn't land.

Fix: first line of any WP-CLI script that writes Bricks template meta:

```php
wp_set_current_user( 1 );  // or any user ID with builder access
```

`bricks_global_classes` option writes are **not** gated this way — you can update that option without `wp_set_current_user()`. Useful to know when isolating failure modes.

---

## 4. Authoritative schemas learned

Each of these was discovered via the Golden Rule above. Treat them as verified; treat anything not here as needing fresh discovery.

### 4.1 Query Loop — Posts

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

- `post_type` is an **array**, not a string
- Exclude current post: use `exclude_current_post: true`. **Never** `post__not_in: ["{post_id}"]` — dynamic tag doesn't parse inside that array, and the invalid shape may cause Bricks to reject the whole query on builder load
- Bricks fills in `posts_per_page`, `orderby`, `order` from main-query defaults if omitted

### 4.2 Query Loop — Custom (registered via filter)

```json
{
  "hasLoop": true,
  "query": {
    "objectType": "your_custom_type"
  }
}
```

Custom types register via `bricks/setup/control_options`:

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

### 4.3 Image with dynamic src

```json
{
  "image": {
    "useDynamicData": "{acf_field_name}"
  },
  "altText": "{post_title}"
}
```

- `useDynamicData` is inside `image`, but `altText` is at the top level of settings
- Confirmed path for ACF URL fields via `get_field()` under the hood

### 4.4 Link (on container tag=a, or on button)

```json
{
  "link": {
    "type": "meta",
    "useDynamicData": "{post_url}"
  }
}
```

- The Link control on Container/Block elements **only appears when HTML tag is `a`**. Change tag first, Link appears.
- For non-dynamic external URLs: `"type": "external", "url": "..."`

### 4.5 Block HTML tag options (native, no `customTag` needed)

`div`, `section`, `a`, `article`, `nav`, `ol`, `ul`, `li`, `aside`, `address`, `figure`, `custom`

For anything not in this list (e.g., `dl`, `dt`, `dd`), use `"tag": "custom"` plus `"customTag": "dl"`.

### 4.6 Bricks Conditions (hide-when-empty et al.)

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

Nested array shape: outer array = AND groups, inner arrays = OR rules within each AND group. For simple "hide if field empty", one outer array with one inner rule.

### 4.7 Global Class shape

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

**`settings` must be `array()` — never `new stdClass()`.** A stdClass here crashes Bricks' interactions loader (`includes/interactions.php:512`, "Cannot use object of type stdClass as array") on every subsequent request. Fatal site-wide, requires direct-MySQL recovery to fix.

Custom CSS stored in `_cssCustom` uses literal class selectors (`.my-class { ... }`), not `%root%`. Bricks substitutes `%root%` → literal at UI save time, but the stored value is always literal.

### 4.8 Custom dynamic tags

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
    if ( $tag !== '{my_tag}' ) return $tag;
    return 'resolved value';
}, 10, 3 );

// Needed separately for when the tag appears inside larger content strings
add_filter( 'bricks/dynamic_data/render_content', function( $content, $post, $context = 'text' ) {
    if ( false === strpos( $content, '{my_tag}' ) ) return $content;
    return str_replace( '{my_tag}', 'resolved value', $content );
}, 10, 3 );
```

Inside the render filter, `\Bricks\Query::get_loop_object()` returns the current loop item if called during a loop iteration.

### 4.9 ACF field name convention in Bricks dynamic tags

Bricks dynamic tags use **underscore**, not colon: `{acf_video_duration}`, not `{acf:video_duration}`. This is a common trip-up when writing from paste-workflow habits.

---

## 4b. Schemas verified on Highland (Bricks 2.3.6, 2026-06-14 — footer build)

Mined by reading back the live Header template, then used to build the Footer. All survived render; confirm survival on next builder open.

### Element-array structure (per element in `_bricks_page_{content,header,footer}_2`)
```php
[ 'id' => '6char', 'name' => 'section', 'parent' => 0 /* or parent id */,
  'children' => [ 'childid', ... ], 'settings' => [ ... ], 'label' => 'Human label' ]
```
Top-level elements have `parent => 0`. `children` is an ordered array of child ids; **both** the child's `parent` and the parent's `children` must be set. IDs are 6-char alphanumeric, never all-numeric.

### Element types confirmed in this install
`section` (settings `tag:"div"`), `container` (the ACSS centered/max-width wrapper — just `name:"container"`, no flag needed), `block` (generic; default div, or `tag:"nav"|"ul"|...`, or `tag:"custom"` + `customTag:"footer"`), `div` (used for links: `tag:"a"` + `link`), `text-basic` (`tag` + `text`, supports dynamic tags in `text`), `svg`.

### svg element — two sources
```php
// custom icon set (e.g. the "MPD" set: bricks_icon_sets / bricks_custom_icons options)
'settings' => [ 'source' => 'iconSet', 'iconSet' => [
    'library' => 'custom_set_468irzn12', // = "custom_" . setId
    'svg' => [ 'id' => 34, 'icon_id' => 'icon_6pdd2o24s', 'url' => '.../device-mobile-light.svg' ] ] ]
// arbitrary media SVG attachment (used for the logo)
'settings' => [ 'source' => 'file', 'file' => [ 'id' => 45, 'url' => '.../logo.svg' ] ]
```
Both render the SVG **inline** (`<svg class="brxe-svg" …>`), not as a `<img>`/file URL — so grep rendered output for `brxe-svg`, not the filename.

### Dynamic tags DO resolve inside link URLs (refines §5.4)
The §5.4 caution is about raw *query* fields. The **Link control's URL field resolves dynamic tags**: `'link' => ['type' => 'external', 'url' => '{highland_phone_link}']` rendered `href="tel:3303383577"`; same for `{highland_email_link}` (mailto) and `{acf_facebook_url}`. Confirmed.

### Typed global-class settings shapes (the maintainable alt to `_cssCustom`)
Read back from the header's global classes. Value shapes:
```php
'_background' => [ 'color' => [ 'id' => 'acss_import_secondary', 'raw' => 'var(--secondary)' ] ],
'_padding'    => [ 'top' => 'var(--space-m)', 'right' => '0', 'bottom' => '8', 'left' => '0' ], // px = bare number
'_margin'     => [ /* same shape as _padding */ ],
'_typography' => [ 'font-size' => 'var(--text-s)', 'font-weight' => '600', 'font-style' => 'normal',
                   'letter-spacing' => '...', 'text-transform' => 'uppercase', 'line-height' => '...',
                   'color' => [ 'id' => 'acss_import_base', 'raw' => 'var(--base)' ] ], // map keyed by CSS prop
'_display' => 'flex', '_direction' => 'row', '_justifyContent' => 'space-between', '_alignItems' => 'center',
'_gap' => 'var(--space-m)', '_columnGap' => 'var(--space-xs)', '_rowGap' => '...',  // gap requires _display flex
'_widthMax' => '34ch',  // number+unit string
'_border' => [ 'width' => ['left'=>'1'], 'style' => 'solid', 'color' => ['raw'=>'var(--neutral-trans-10)'] ],
```
**Breakpoint variants are key-suffixed:** `'_justifyContent:mobile_landscape' => 'center'`, `'_display:tablet_portrait' => 'none'`, `'_typography:hover' => [...]`.

**Typed is the default — `_cssCustom` is the exception (do NOT default to it).** Earlier in this build I wrongly reasoned "I can't verify typed survives the builder, so use `_cssCustom`." That's the most-violated-rule trap (`00`/`01`). The correct method when a typed shape is unknown is the **golden rule via read-back of EMITTED CSS**: set the typed key on a class → `\Bricks\Assets_Files::regenerate_css_files()` → curl the page and grep the rendered rule. If it emits the property, the shape is right (and a real control key survives the builder). This is fully doable headlessly — no excuse to default to `_cssCustom`.

**More verified typed shapes (confirmed by read-back, footer build):**
```php
// grid (string values + breakpoint suffix all emit)
'_display' => 'grid', '_gridTemplateColumns' => 'var(--grid-3)', '_gridGap' => 'var(--space-l)',
'_gridTemplateColumns:tablet_portrait' => '1fr', '_gridGap:tablet_portrait' => 'var(--space-xl)',
'_rowGap' => 'var(--space-s)', '_columnGap' => '0.6em',   // gap keys: STRING values, take var() — both emit
'width' => '180px',  // svg/img element direct key (also height, fill:{id,raw}) — from a builder-made class
'_typography' => [ 'font-size'=>'var(--text-m)', 'line-height'=>'1.55', 'letter-spacing'=>'0.16em',
                   'text-transform'=>'uppercase', 'font-family'=>'Montserrat', 'color'=>['raw'=>'var(--x)'] ],
```
Typed global-class settings emit as **Bricks-generated** CSS (`.cls {background-color: …; padding-top: …}`), not a `_cssCustom` blob — that's the win: visible/editable in the panel.

**Gotchas found:**
- `_gap` did **not** emit (neither a `'var(--space-s)'` string nor a `{number,unit}` object). Use `_rowGap` / `_columnGap` (string values) instead — those emit and take `var()`.
- The typography **font-family control quotes its value**: `'font-family'=>'var(--heading-font-family)'` emits `font-family:"var(--heading-font-family)"` (broken — quoted var). Pass the **registered font NAME** instead (`'font-family'=>'Montserrat'`) → `font-family:"Montserrat"` (valid, matches the Bricks custom font).
- `text-decoration` has **no** typed control (absent from the typography control) — this is a genuine `_cssCustom` case (e.g. underlined links).
- Buttons: don't hand-build (typed or `_cssCustom`) — attach the ACSS button global classes (`acss_import_btn--primary` + literal `btn` via `_cssClasses`) per `01`. The base `.btn` selector isn't locatable in the front-end ACSS CSS, so confirm the rendered button in the builder (live preview is the right surface for it).
- Renaming a global class in the builder DOES update its `_cssCustom` literal selector (`.site-footer` → `.footer`) — not stale after rename.
- Structural-only wrappers correctly have `settings => []` (empty is a valid signal; no class CSS needed).

### Header/Footer template creation
```php
$tid = wp_insert_post([ 'post_type'=>'bricks_template', 'post_title'=>'Footer', 'post_status'=>'publish' ]);
update_post_meta($tid, '_bricks_template_type', 'footer');           // or 'header'
update_post_meta($tid, '_bricks_editor_mode', 'bricks');
update_post_meta($tid, '_bricks_template_settings',
    [ 'templateConditions' => [ [ 'id'=>'6char', 'main'=>'any' ] ] ]); // 'any' = apply site-wide
update_post_meta($tid, '_bricks_page_footer_2', $elements);          // gated key → wp_set_current_user(1) first
```
ACSS button classes import as global classes `acss_import_btn--primary` / `--outline` / `--m` etc. (the base `.btn` selector wasn't locatable in the front-end ACSS CSS headlessly). `_cssClasses` (string) is the literal extra-class field if needed alongside `_cssGlobalClasses` (ids).

---

## 4c. Schemas verified on Highland homepage build (Bricks 2.3.6, 2026-06-16)

Full 8-section homepage (#18) built direct via WP-CLI from a token-pure ACSS mockup. Every shape below verified by read-back of EMITTED CSS / rendered HTML (golden rule), typed-first.

### Element types + tags
- `text-basic` native `tag` options: `div, p, span, figcaption, address, figure, custom` (+ `customTag` when `tag:'custom'`). So `blockquote`/`cite`/`h3` → `tag:'custom'` + `customTag:'blockquote'`. Verified `<blockquote>`, `<figcaption>`, `<figure>` render correctly.
- `block`/`div` both take `tag` (incl. `figure`, `li`, `article`, `a`) and `tag:'custom'`+`customTag`. Convention (Mike): `div` for `li` and semantic wrappers; `block` for `ul`/`figure`.
- The `heading` element **preserves inline HTML** in `text` — `'… my <span class="accent">full</span> focus.'` renders the span intact. This is how accent words work; the `.accent` style itself lives in the **child theme** (Bricks only emits global-class CSS for classes attached to an element, not for classes that ride inside raw text).
- `_cssId => 'hero-title'` renders `id="hero-title"` — use it for `aria-labelledby` targets.

### Verified typed setting shapes (all emit)
- `_attributes => [ ['id'=>'6char','name'=>'aria-label','value'=>'…'] ]` → renders the literal HTML attribute. **WORKS HEADLESS** — corrects the 4b footer note that ARIA attrs "need the builder UI". Use for `aria-label` / `aria-labelledby` / `aria-hidden` / `role`.
- `_flexWrap => 'wrap'`; `_direction => 'column'` (flex-direction); `_overflow => 'hidden'`; `_position => 'relative'|'absolute'` + offset keys `_top/_right/_bottom/_left` (bare `'0'` = `0px`).
- `_width` / `_height => '42px'` (decorative bars); `_flexGrow => '1'` (equal-height card → push CTA to bottom); `_widthMax => '46ch'|'18ch'|'34rem'`, breakpoint-suffixable (`_widthMax:tablet_portrait => 'none'|'34rem'`).
- `_border` radius-only: `['radius'=>['top'=>'var(--radius-l)', …]]`. Single-side: `['width'=>['left'=>'3'],'style'=>'solid','color'=>[…]]` → `border-left:3px solid …`. Full four-side emits the `border:` shorthand.
- Grid: `_display:'grid'` + `_gridTemplateColumns:'var(--grid-3-2)'` (+ `:tablet_portrait`/`:mobile_portrait` suffixes) + `_rowGap`/`_columnGap` (string `var()` values; `_gap` still doesn't emit). Bricks injects a harmless `align-items:initial` before your `_alignItems` — yours wins (later in the rule).

### Lean on ACSS utilities BEFORE `_cssCustom` (Mike preference, reinforced)
Order: typed control → **ACSS utility class** → `_cssCustom` → child theme. Attach via `_cssGlobalClasses` using the import id:
- list reset → `acss_import_list--none` (NOT `_cssCustom{list-style:none}`)
- aspect ratio → `acss_import_aspect--4-3` / `--1-1` (NOT `_cssCustom{aspect-ratio}`)

### ACSS buttons DO style headless (corrects 4b / 03 note)
ACSS keys base button styling off `[class*="btn--"]:not(…)` and `a[class*="btn--"]{display:inline-flex}` — there is **no** base `.btn` rule. So attaching `acss_import_btn--primary` (or `--outline` + a color like `--white`) to an `<a>` (`div tag=a`) fully styles it with no builder step; the literal `btn` via `_cssClasses` is redundant (harmless). The footer build's "base `.btn` not locatable → confirm in builder" was from targeting `.btn` instead of `btn--*`. Builder pass is now just hover/contrast eyeballing — e.g. a ghost-on-light wants `btn--outline`+`btn--secondary` for a steel outline, not bare `btn--outline` (which renders red).

### Global-class CSS delivery
This install loads global-class CSS **INLINE in `<head>`** — so `post-{ID}.min.css` is often 0 bytes even for a page full of global classes. Verify emitted CSS by grepping the **rendered page HTML**, not the post CSS file. (See the matching `03` gotcha.)

---

## 4d. Schemas verified on Highland contact page build (Bricks 2.3.6, 2026-06-30)

### `code` element (raw-HTML / shortcode slot)
```php
'name' => 'code',
'settings' => [
  'executeCode' => true,   // run the code (else it shows as escaped text); needs Bricks > Settings > Code execution ON
  'noRoot'      => true,    // render without the .brxe-code wrapper div — outputs the raw markup directly
  'code'        => "<form class=\"…\">…</form>",  // raw HTML (and/or PHP) string
  // 'signature' => …       // DO NOT hand-write — Bricks generates it; see below
],
```
- Used as the interim form stub (mockup `<form>` markup) — swap for a `shortcode` element (`[ws_form id=X]`) when the real form exists. Field CSS for raw HTML inside a code element can't be typed Bricks settings → child theme (the token-correct target WS Forms inherits).
- **CRITICAL — code signature.** An `executeCode` element written headlessly renders **nothing** until signed. Bricks HMAC-signs executable code (`settings.signature`) to stop DB-injected code from running; an unsigned/invalid element returns empty from `Code::render()`. After writing, sign it as an admin with the code cap:
  ```php
  wp_set_current_user( 1 );
  \Bricks\Admin::crawl_and_update_code_signatures(); // signs every code element in the DB
  \Bricks\Assets_Files::regenerate_css_files();
  ```
  Read-back should show `settings.signature` (32 chars). Re-sign after any later headless edit to the code (signature is content-bound). Full incident in `03`.

### `svg` element — color + size (reconfirms 4b)
Color a `fill="currentColor"`/`stroke="currentColor"` icon via typed `_typography.color`; size via the svg-direct `width`/`height` keys (NOT `_width`/`_height`):
```php
'settings' => [
  'source' => 'iconSet', 'iconSet' => [ 'library' => 'custom_set_468irzn12', 'svg' => [ 'id'=>34, 'icon_id'=>'icon_…', 'url'=>'….svg' ] ],
  'width' => '20px', 'height' => '20px',
  '_typography' => [ 'color' => [ 'id'=>'acss_import_primary', 'raw'=>'var(--primary)' ] ],
],
```

### `text` only renders on `text-basic`/`heading` — NOT `div`/`block` (reconfirmed, bit again)
A `div`/`block` with a `text` setting renders the wrapper (and any `::before`) but drops the text. For a list item that is text + a dot, the `<li>` is a `div tag=li` (carries flex/`_columnGap` so the `::before` dot spaces) **with a `text-basic` child** holding the label. Putting `text` on the `div` itself = dots with no labels.

---

## 4e. Schemas verified on Highland about page build (Bricks 2.3.6, 2026-07-01)

### Breakpoint-suffixed `_border` / `_padding` emit headless (responsive side-switch)
The hero credentials rail switches from a left border to a top border when it stacks — done entirely with typed, breakpoint-suffixed border/padding keys, verified in the rendered CSS:
```php
'_border'  => [ 'width' => ['left'=>'1'], 'style'=>'solid', 'color'=>['id'=>'acss_import_neutral-light','raw'=>'var(--neutral-light)'] ],
'_padding' => [ 'left' => 'var(--space-l)' ],
'_border:mobile_landscape'  => [ 'width' => ['top'=>'1','left'=>'0'], 'style'=>'solid', 'color'=>[...] ], // → @media(max-width:767px) border-top:1px; border-left:0
'_padding:mobile_landscape' => [ 'left' => '0', 'top' => 'var(--space-l)' ],
```
Confirms breakpoint suffixes work on the object-valued `_border`/`_padding` keys (not just scalars). NOTE: when Mike then edits the same border via the builder UI, it re-emits as the **logical** border keys (`_borderWidthLogical` / `_borderStyle` / `_borderColor` / `_borderRadiusLogical`) — the builder's native border shape. Both the flat `_border` and the logical set emit; either is fine to author.

### SVG element from a media file — native shape has no `source` key
Builder-written SVG-from-media (e.g. Mike's value-card logo-mark icon) is just:
```php
'name' => 'svg',
'settings' => [ 'file' => [ 'id' => 93, 'filename' => 'HHM-logo-mark.svg', 'url' => '…/HHM-logo-mark.svg' ] ],
```
No `'source' => 'file'` key. (My footer/contact note used `'source'=>'file'` + `file:{id,url}` — that also works, but the builder's own output omits `source` and includes `filename`.) Size via the svg-direct `width`/`height` keys on a global class (`about-value__icon` = `{width:'3rem',height:'3rem'}`), NOT `_width`/`_height`.

### Split section head (eyebrow+title left / lede right)
`mpd-section__header` is already a flex `row` / `space-between` / `align-items:flex-end`. Put `mpd-section__heading-wrapper` (eyebrow + `display` h2) and the lede as **siblings** under it → two columns. Use **`mpd-section__lede`** (margin-free) for the right-column lede, NOT `mpd-lede` (its `margin-bottom` misaligns under `flex-end`). Add a page-scoped bottom-margin class (`about-section-head`) on the header so it still spaces off the grid/list below (the homepage's `gsh02`/`gsi01` spacing ids are orphaned — no definition, emit nothing).

## 5. Failure modes (time sinks we hit today)

### 5.1 Silent strip on next builder load

**Symptom:** PHP write reports success, readback via WP-CLI confirms the new element, but after the user opens the builder the element reverts or disappears.

**Cause:** Bricks' JS-side tree validator runs when the builder loads the template. Unknown settings keys or malformed values get silently dropped; the cleaned tree is re-saved when the builder next writes (auto-save or explicit).

**Fix:** Use the Golden Rule. Any schema guess that's not builder-output-verified will get stripped.

### 5.2 Global Class crashes site

**Symptom:** Site returns "Critical error" on every request after writing to `bricks_global_classes`. Error log shows `Cannot use object of type stdClass as array` in `bricks/includes/interactions.php`.

**Cause:** `'settings' => new stdClass()` instead of `'settings' => array()`.

**Recovery:** WordPress won't bootstrap, so WP-CLI fails. Fix directly via MySQL PDO:

```bash
mysql --socket="$SOCK" -uroot -proot local  # or your local's MySQL socket
```

Read `wp_options.option_value` for `bricks_global_classes`, `unserialize()` it in a standalone PHP script, surgically remove or fix the stdClass entries, `serialize()` and write back.

**Prevention:** `'settings' => array()` always. Even when setting the field later and initially you just need a placeholder.

### 5.3 Stretched-link patterns break subtly

The clickable-card pattern (ACSS `.clickable-parent` or custom `::after`) has at least three ways to silently break:

1. **Specificity**: ACSS's `.clickable-parent:not(a)` at `(0,1,1)` beats a plain `.card { position: relative }` at `(0,1,0)`. Use compound `.card.clickable-parent { position: relative }` to climb to `(0,2,0)`.

2. **clip-path on the anchor**: a visually-hidden anchor using `clip-path: inset(50%)` clips its own pseudo-elements. The stretched `::after` renders into an invisible box regardless of its absolute positioning.

3. **position: absolute on the anchor**: makes the anchor its own containing block. The absolute `::after` then positions relative to the 1×1 anchor, not the card.

**Better pattern in practice:** skip stretched-link entirely when the card has inner links (e.g., taxonomy tags). Structure as:

```html
<li class="card">
  <a href="{post_url}" class="card__link">
    <!-- poster, title, anything that should share the primary click target -->
  </a>
  <p class="card__meta">
    <a href="...">inner link</a> · <a href="...">inner link</a>
  </p>
</li>
```

Visible anchor wraps primary clickable content. Secondary links as siblings. No pseudo-elements, no ACSS utility, no specificity wars. Valid HTML, cleanly pasteable.

### 5.4 Bricks doesn't parse dynamic tags in arbitrary query fields

Writing `post__not_in: ["{post_id}"]` doesn't work — Bricks doesn't resolve dynamic tags inside that array. Use the dedicated boolean `exclude_current_post: true` and Bricks injects the post ID at render time.

General rule: where Bricks has a native UI control for a setting, that setting has its own schema key. Don't try to reach the same behavior via WordPress-level keys (`post__not_in`, `meta_query` with dynamic strings) — use the Bricks key first.

---

## 6. ACSS specificity cheat sheet

Common selector specificity values when overriding ACSS utilities:

| Selector | Specificity |
|---|---|
| `.card` | `(0,1,0)` |
| `.card.clickable-parent` | `(0,2,0)` |
| `.card:hover` | `(0,2,0)` |
| `.clickable-parent:not(a)` | `(0,1,1)` — `:not(a)` adopts the specificity of `a` which is `(0,0,1)` |
| `.clickable-parent:not(a) a` | `(0,1,2)` |
| `.card .card__meta a` | `(0,2,1)` |

When ACSS utility rules override your custom-class rules, climb specificity via compound-class selectors rather than `!important`. `!important` trumps specificity but kills debuggability.

---

## 7. Patch: `wireframe-to-bricks-rules.md` Section 6.3

The current Section 6.3 ("Clickable Parent Pattern") contradicts Section 5.2 ("Avoid Features That Drop on Import") — it prescribes a structural `::after` for the stretched-link mechanism while Section 5.2 forbids structural pseudo-elements. It also doesn't address the specificity or `clip-path` gotchas.

**Proposed replacement for Section 6.3:**

> ### 6.3 Clickable Card Pattern
>
> For cards where the entire primary content (image + title) should be clickable, wrap that content in a visible `<a>` element rather than using a stretched-link pseudo. Secondary links (category tags, meta actions) live as siblings of the anchor, inside the same list item.
>
> ```html
> <li class="card">
>   <a href="{url}" class="card__link">
>     <div class="card__media">
>       <img src="…" alt="…">
>     </div>
>     <h3 class="card__title">Title</h3>
>   </a>
>   <p class="card__meta">
>     <a href="…">Category</a> · <a href="…">Tag</a>
>   </p>
> </li>
> ```
>
> This pattern avoids `::after` pseudo-elements (which drop on import per Section 5.2), avoids nested anchors, and lets inner links navigate independently without specificity battles against the primary click surface.
>
> **Alternative — ACSS `.clickable-parent` utility** — is available when the card has *no* inner links (purely primary content). Apply `.clickable-parent` to the list item. Requires:
>
> - A direct-child `<a>` inside the card (not wrapped in a heading — direct descendant)
> - A positioning context: `.card.clickable-parent { position: relative; }` (compound selector needed to beat ACSS's `.clickable-parent:not(a) { position: static }` at equal specificity)
> - Direct-child anchor must NOT use `position: absolute` for its visual-hiding technique, and must NOT apply `clip-path` — both break the stretched pseudo
>
> Given these constraints, prefer the visible-anchor pattern above for most cases.

---

## 8. Related resources

- **`wpgaurav/bricks-skills`** ([repo](https://github.com/wpgaurav/bricks-skills)) — markdown skill files for Claude.ai and Claude Code covering Bricks JSON structure (`bricksCopiedElements` clipboard format), element shapes, breakpoint conventions, hooks. Intended workflow: AI generates Bricks JSON → user right-click-pastes into Structure panel. Complementary to this doc — bricks-skills covers the clipboard-paste generation path, this doc covers the direct-DB-edit path. Both rely on the same Golden Rule.
- **Bricks theme source** — when schemas aren't in your own install to read back, `bricks/includes/elements/` and `bricks/includes/query.php` are the authoritative reference.
- **ACSS compiled stylesheet** — `wp-content/uploads/automatic-css/automatic-bricks.css` — grep here to understand what utility classes exist and their exact selectors/specificity before writing overrides.

---

## 9. Changelog

**v1.0 — April 2026**

- Captured from the ext-mem video site build session: Query Loop schema discovery, stretched-link failure modes, `stdClass` site-crash recovery, ACSS specificity overrides, custom query/tag registration patterns
- Proposes reconciliation patch for `wireframe-to-bricks-rules.md` Section 6.3
- References `wpgaurav/bricks-skills` as the complementary clipboard-paste resource

*Future versions: document new Bricks versions' schema changes, expand the schema library as more element types are used, add element-level visual-settings schema (`_typography`, `_padding`, etc.) once they're discovered via the Golden Rule.*

---

## 4f. Schemas verified on Highland header rebuild (Bricks 2.3.8 / BricksExtras 1.6.9, 2026-07-02)

Source: KSCBS header template export (builder-saved) + BE plugin source read + emitted-CSS verification. All survived Mike's builder open/save.

### BricksExtras Pro OffCanvas (`xoffcanvasnestable`)
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
  'builderHidden'        => true,
],
```
- Renders: `.x-offcanvas_backdrop` + `.x-offcanvas_inner` (role=dialog, `inert` when closed, `data-lenis-prevent`). Config lands in `data-x-offcanvas` JSON — verify flags there via curl.
- BE base CSS defaults: inner 300px wide, 30px padding, white bg, backdrop rgba(0,0,0,.5). Zero the padding via your own inner class and own the spacing.
- Control keys are authoritative from `bricksextras/components/classes/x-offcanvas-nestable.php` — read source, don't guess.

### BricksExtras Burger Trigger (`xburgertrigger`)
```php
'name' => 'xburgertrigger', 'settings' => [ 'aria_label' => 'Open main menu' ],
```
Renders a real `<button>`; aria-expanded/controls wired at runtime by BE JS (curl won't show them).

### BE element-level styling + CLI regen — see the `03` entry
`Assets_Files::regenerate_css_files()` under WP-CLI errors `Control type number is not defined!` and skips BE element settings CSS (width/backdrop). Global-class CSS is unaffected. Emits correctly on first builder save; bridge with `_cssCustom` on a related class targeting `.brxe-xoffcanvasnestable .x-offcanvas_inner`.

### nav-menu (classic) typed class settings — full working shape
`menuAlignment` (row|column), `menuGap`, `menuMargin`, `menuTypography` (+ `:hover`) — emits as `.cls .bricks-nav-menu > li > a { … }`. No typed control for the WP active state: `.cls .current-menu-item > a, .cls a[aria-current="page"]` is a legit `_cssCustom` case (like text-decoration).

### Typography `font-size` accepts raw `clamp()` headlessly
`'_typography' => ['font-size' => 'clamp(2.33rem, 1.69rem + 2.65vw, 3.95rem)']` on a global class emits verbatim (`.display` redo). Fluid utility sizes don't need `_cssCustom`.

### Native sticky vs offcanvas-in-header
`headerSticky`+`headerStickyOnScroll` transform `#brx-header` → traps `position:fixed` descendants (offcanvas panel rides the header). Removed sticky on Highland; alternative is BE `offcanvas_template` outside the header. See `03`.

## 4g. BricksExtras Pro Slider — verified schemas (Bricks 2.3.8 / BE 1.6.9, 2026-07-03)

Source: BE plugin source read (`components/classes/x-pro-slider*.php`) → headless build → **survived Mike's builder open/edit/save** (homepage hero, post 18). Splide-based.

### Pro Slider (`xproslider`) — gallery-mode fade rotator
```php
'name' => 'xproslider', // nestable; in gallery mode its one child is a Pro Slider Gallery
'settings' => [
  'type'           => 'fade',       // select: loop | slide | fade (default slide)
  'rewind'         => true,         // checkbox; REQUIRED for fade+autoplay to cycle back
  'autoplayscroll' => 'autoplay',   // select: autoplay | autoscroll | none
  'interval'       => '4000',       // number control; builder saves as STRING (int works headlessly too)
  'pauseOnHover'   => true,         // checkbox → render checks isset()
  'pauseOnFocus'   => true,
  'arrows'         => 'false',      // select: string 'true'/'false' (default 'true' = icons ON)
  'pagination'     => true,         // checkbox; render checks isset() — control 'default: true' does NOT apply headlessly, set it
  'galleryMode'    => true,         // checkbox; child gallery element renders the splide__list
],
```
- `perPage` forced to 1 when `type=fade`; omit. Defaults omitted = Splide defaults (speed 400, keyboard focused, drag on).
- Config lands in `data-x-slider` JSON (`rawConfig`) — verify flags via curl.
- Nestable non-gallery mode: each child is a `block` with hidden `_cssClasses: 'x-slider_slide splide__slide'` (from `get_nestable_item()`); slides only iterate real children.
- Element-level styling (arrow/pagination controls) hits the known BE CLI-regen gap (`03`) — bridge on a global class if it must render pre-builder-save.

### Pro Slider Gallery (`xproslidergallery`) — child of gallery-mode slider
```php
'name' => 'xproslidergallery',
'settings' => [
  'items' => [                       // Bricks image-gallery shape; builder-canonical per-image keys: id/full/url only
    'images' => [ ['id'=>73,'full'=>'<full-url>','url'=>'<sized-url>'], /* … */ ],
    'size'   => 'medium',            // top-level size drives rendered size
  ],
  'objectFit'       => 'cover',      // typed, emits `img { object-fit }` (element-level → regen gap applies)
  'lazyLoadSupport' => 'none',       // select: none | splide (default) | bricks — see LCP gotcha in `03`
  'maybeSRCSET'     => 'enable',     // select: disable (default) | enable
],
```
- Renders as the `ul.splide__list` itself (`x-slider-gallery`), one `li.splide__slide` per image — the source's "place outside the Pro Slider" intro text is a copy-paste error; it goes INSIDE.
- Captions off unless `caption: true` (pulls attachment captions). `link` default none.

## 4h. Schemas verified on Highland projects archive build (Bricks 2.3.8 / BE 1.6.9, 2026-07-11)

Source: BE plugin source read (`components/classes/x-before-after-image.php`) → headless build → **survived Michael's builder open/edit/save 2026-07-11** (template #138 — he added handle icons; nothing stripped). Same discovery route as the §4g Pro Slider.

`iconLeft` / `iconRight` (slider handle chevrons) take the standard custom-icon-set shape: `['library' => 'custom_set_468irzn12', 'svg' => ['id' => 32, 'icon_id' => 'icon_dctwf7tqx', 'url' => '…/arrow-left-s-line.svg']]` — builder-verified (Michael's edit), renders inline SVGs with `fill="currentColor"`.

### BricksExtras Before/After Image (`xbeforeafterimage`) — nestable
```php
'name' => 'xbeforeafterimage',
'settings' => [
  'maybeLabels' => true,   // checkbox → renders Before/After label divs (defaults 'Before'/'After'; beforeText/afterText override)
  // 'start' => 50 (number, % start position), 'direction' => 'horizontal'|'vertical' — defaults fine, omit
],
// EXACTLY two children, each a `block` with the hidden BE class; each holds one `image`:
'children' => [ /* block×2 */ ],
// block:  'settings' => [ '_hidden' => ['_cssClasses' => 'x-before-after-image_block'] ]
// image:  'settings' => [ 'image' => ['useDynamicData' => '{acf_before_image}', 'size' => 'medium'],
//                         'caption' => 'none', '_hidden' => ['_cssClasses' => 'x-before-after-image_image'] ]
```
- **Child order is semantic:** child 1 = *before* (absolutely positioned, clip-path from left), child 2 = *after* (static, defines the element's flow height). BE CSS keys off `:nth-of-type()`.
- Works inside a query loop with dynamic images (`{featured_image}` / ACF image fields) — verified, 8 loop iterations.
- **Labels ship unpositioned** (`position:absolute` + translucent white bg only) — the label position/typography controls are element-level BE CSS → the known CLI-regen gap. Bridge on your own global class (Highland: label pills + the height chain live in `project-card__media` `_cssCustom`).
- **Height chain for an aspect-pinned wrapper:** `.wrapper .brxe-xbeforeafterimage, .wrapper .x-before-after_container, .wrapper .x-before-after-image_block { height:100% }` + `img { width/height:100%; object-fit:cover }` (BE's own img CSS covers only its happy path).
- Front-end JS bails inside the builder iframe (`body > .brx-body.iframe` guard) — **blank/static in canvas is expected**, same as Leaflet.
- `\Bricks\Assets_Files::regenerate_css_files()` under CLI emits a benign `Error: Control type number is not defined!` when BE nestables are present (BE controls don't register headless). Files still regenerate — check the count, ignore the message.

### Archive template conditions (`_bricks_template_type` = 'archive')
```php
update_post_meta( $tid, '_bricks_template_settings', [
  'templateConditions' => [ [
    'id' => '6char', 'main' => 'archiveType',
    'archiveType' => ['postType'], 'archivePostTypes' => ['project'],
  ] ],
] );
```
Verified matching `is_post_type_archive('project')` (templates.php `archiveType` case). Content in `_bricks_page_content_2` as usual.

### Conditions + dynamic data inside a loop (reconfirms §4.6 in loop context)
`_conditions` with `key: 'dynamic_data'` on `{acf_*}` fields evaluate **per loop iteration** — used for the before/after ⇄ plain-image switch and hide-when-empty meta/description lines. Both branches verified (cleared a field, curled, restored).

### This install's `large` image size is 1920px
`wp media image-size`: `large` = 1920 (blueprint override; `medium` = `medium_large` = 768). Don't reach for `'size' => 'large'` on card-scale images — Michael's hero-slider precedent uses `medium`.

### Verified on Services build (2026-07-11)
- `'_width' => 'fit-content'` emits (`width: fit-content`) — keyword values work on the width control, not just number+unit.
- `text-link` element: `{ text, link: {type,url}, _cssGlobalClasses }` renders a bare `<a>text</a>`; with a `#anchor` URL Bricks adds `data-brx-anchor="true"` (native smooth scroll) automatically.
- Pills-in-a-row recipe (the `03` inline-row gotcha, applied): ul = flex + `_flexWrap:'wrap'` + gaps; li = `_width:'fit-content'`; link = `_display:'inline-block'`. All typed.
- Rich Text element (`name: 'text'`): `settings.text` takes a full HTML block (h2/p/ul/table/code all render intact) and **resolves dynamic tags inside it** — incl. `{highland_*}` inside `href` attributes. Right tool for long legal/prose pages; one element instead of dozens of text-basics. (Privacy build, 2026-07-11.)
- WS Form ships a native Bricks element: `'name' => 'ws-form-form'`, settings `{"form-id": "1"}` (string ID), wrapper `.brxe-ws-form-form` — prefer it over a shortcode element for WS Forms embeds. (Michael placed these via the builder, 2026-07-11.)

## 4i. Bricks native Form element — auth action settings (Bricks 2.3.10, 2026-08-04)

Fills a gap `02` explicitly lists as uncaptured ("Bricks native form element settings"). Read back from working login / lost-password / reset-password forms. **Harvest candidate for the master `02` schema library.**

The action set lives in **`actions` (plural array)** — not `action`. Field bindings are by **field id**, not name:

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

**Traps, all verified:**
- `redirect` + `redirectAdminUrl` are **not** mutually exclusive; the admin branch overwrites the rendered redirect and re-wraps the *raw* string. Full mechanism + fix in `docs/stack-gotchas.md`. To use a dynamic redirect, `redirectAdminUrl` must be **unset**, not `false` (the code tests `isset()`).
- The reset form needs **no** hidden key/login fields — Bricks renders `form-field-key` / `form-field-login` itself from `$_GET` whenever `resetPasswordNew` is set and `reset-password` is in `actions` (`elements/form.php` ~3771). Adding your own would be redundant.
- `?redirect_to=` is honoured **only** when no redirect action is configured (`form/actions/login.php`).
- Field ids survive a cross-site import intact; **font ids and `{acf_*}` tags do not** — see the import gotcha in `03`.
- Bricks renders an empty trailing `<li class="brx-query-trail">` after any query loop; it carries no item class, so item-level borders/styles do not apply to it.
