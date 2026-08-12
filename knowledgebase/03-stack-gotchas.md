# 03 — Stack Gotchas

**Non-obvious behaviors of the stack, discovered the hard way. The carry-forward accumulator.**

V1 baseline: 2026-05-24, verified against Bricks 2.3.4 / ACSS 3.3.6.
**TAB harvest 2026-07-15** — entries marked `TAB` were found on Bricks 2.3.6→2.3.8 / ACSS 3.3.6 / ACF Pro 6.8.4 / WS Form Pro 1.11.x / WP 6.9–7.0.1, and hold there. Where an older entry was extended rather than duplicated, its `First seen` carries both provenances.

Hosting, cutover and post-launch performance gotchas live in **`04-hosting-cutover.md`** — this file is the build stack.

---

## How this file works — the accumulation contract

This file is **inherited, not authored**. Each project copies it from the `claude-config` master at kickoff. It is a lookup catalog — consult it by symptom when something fails in a non-obvious way, not cover to cover.

**The seam.** This file has two parts, divided by the `=== PROJECT SECTION ===` marker below.

- **Above the seam — ESTABLISHED ("we know").** Everything inherited at kickoff. Validated across prior projects. Do not edit these entries during a build.
- **Below the seam — PROJECT ("we learned").** Empty in a fresh project copy. New discoveries from the current build append here.

**The bar.** An entry earns a place when it took more than ~15 minutes to figure out and is likely to bite again. Not a one-off typo — a non-obvious stack behavior with a reproducible cause.

**Entry format** — match it exactly:

```
### [Short pattern title — what you would search to find this]
**Symptom / When:** [the observable failure]
**Why:** [the underlying mechanism, briefly]
**Fix:** [the actual fix, copy-pasteable where possible]
**First seen:** [project], [YYYY-MM-DD] — [the concrete incident]
```

Provenance: entries inherited from before the knowledgebase was formalized carry `V1 baseline, 2026-05-24`. Every entry added from inception onward carries a real project and date.

**At go-live**, the project-section entries are reviewed by Mike; validated ones fold into the master above the seam. See the harvest in `00`.

Some facts referenced here have their full canonical home in bedrock — the `wp_set_current_user(1)` requirement and the golden rule are stated in `00` and `02`. Where an entry below is the *full incident record* for one of those, that is noted; bedrock names the rule, this file carries the war story.

---

## Index

170 entries. Every title is written as what you would search for, so this list is the lookup surface: scan it, find the entry, then grep the file for that exact title.

**Do not read this file cover to cover.** At ~24,000 words it will evict the knowledgebase that sent you here — see `00`, the fourth layer. The index exists so that instruction is followable rather than aspirational.

Regenerate after every harvest, from the repo root:

```bash
awk '/^# === PROJECT/{exit} /^## /{s=$0; sub(/^## /,"",s); skip=(s ~ /^How this file works/ || s ~ /^Index/); if(!skip) print "\n**" s "**\n"} /^### /{if(!skip){t=$0; sub(/^### /,"",t); print "- " t}}' knowledgebase/03-stack-gotchas.md
```

Group labels below are bold rather than headings on purpose — `###` here would collide with the entry headings the generator reads.

**WordPress + Bricks Builder**

- Doubled-class selectors beat Bricks inline CSS cascade
- Bricks element IDs must be 6-char alphanumeric, never all-numeric
- `_cssId` is the stable hook for PHP filters, not Bricks' internal element id
- Re-sign Bricks code elements after any DB-side edit
- Bricks code elements store CSS in `settings.cssCode`, not `settings.code`
- `update_post_meta` silently fails on `_bricks_page_*_2` keys from WP-CLI
- Bricks Element Manager — a disabled element renders as NOTHING, even when it's in the template data
- Bricks Query Filter elements need a manual reindex AND a cron tick after programmatic creation
- Bricks term dynamic data tag is `{term_url}`, not `{term_link}`
- Bricks filter-radio / filter-checkbox default to vertical column — set `displayMode: 'button'` for horizontal pills
- Bricks archive template `archiveType: postType / post` never matches for built-in `post`
- Bricks filter active state — color must be set on `.brx-option-text`, not the `<label>`
- Bricks per-post CSS cache hides DB-side global-class edits until regen
- `rm -f post-*.min.css` does NOT auto-regenerate — frontend silently degrades
- Bricks button utility classes (`btn--outline`, `btn--primary`) are Bricks-injected, not user-defined
- Bricks `bricks/allowed_html_tags` filter for custom elements
- Bricks emits skip-links automatically via the `bricks_body` action
- Bricks — typed `_border` setting uses a flat width/style/color shape, not per-side nested objects
- Bricks — typed-setting breakpoint suffixes go on the OUTER key, not as a sibling inside a nested dict
- Bricks — `_widthMax: '100%'` is special-cased to suppress horizontal scrollbars
- Bricks — author rules with equal specificity lose to `@layer bricks` framework rules
- Bricks — `*{border-color}` plus a default 1px border on native inputs = "ghost" borders on every form
- Bricks — where CSS actually lives: global-class CSS is INLINE, element-typed CSS is in `post-{id}.min.css`
- Bricks emits a Global Class's CSS ONLY when an element on that page references it
- Bricks file-mode CSS never externalizes global-class CSS — architecture, not a CLI limitation
- Bricks `_typography.font-family` quotes the value — use `custom_font_<id>`, not a CSS string
- Bricks image element with `tag=figure` collapses to content size — `:where(.brxe-image).tag` forces `width:auto; height:fit-content`
- Bricks `_aspectRatio` dual-routes to the inner img — it can't drive the figure's box when the image element IS the figure
- Wrapper-block-as-figure inherits UA `figure { margin: 1em 40px }` — Bricks zeros it only for `figure.brxe-image`
- Bricks "invalid post type" on Edit with Bricks = stale rewrite rules (not a Bricks setting)
- Bricks 2.3.x `bricks/dynamic_data/render_tag` passes `$tag` as a parsed-tag ARRAY, not always a string
- A custom dynamic tag used in an element `_conditions` resolves via `render_content`, NOT `render_tag` — register BOTH
- Bricks `logoHeight` (and any number-unit control) silently rejects `clamp()`
- Bricks button/link with a dynamic-data href needs `link.type: "meta"` — `"external"` emits NO href at all
- Native `{post_url}` resolves to the queried object (not the loop item) in LINK contexts inside a custom-query loop
- Bricks custom query returning bare IDs (`'fields'=>'ids'`) does NOT establish per-item post context
- Bricks custom-query `posts_per_page` arrives at `settings['query']['posts_per_page']`, not `settings['posts_per_page']`
- Bricks archive template conditions use `archivePostTypes` / `archiveTerms` — NOT `postType` / `taxonomy`
- Bricks scores competing header/footer/template conditions — a specific post-ID condition (8) beats `main: any`
- Bricks per-page footer/header disable key is `footerDisabled` / `headerDisabled`
- Writing Bricks content to a page in `wordpress` editor mode renders nothing — flip `_bricks_editor_mode`
- Bricks element tree written via WP-CLI needs populated `children` arrays — `parent` alone renders empty shells
- Bricks `_cssGlobalClasses` must reference class IDs, not names — name refs persist but emit no class attribute
- Bricks image element with a dynamic source needs the tag to return an ARRAY `[$id]` in image context
- Bricks image element: the stored `id`/`url` is a BUILDER PREVIEW, not a frontend fallback
- Bricks DOES resolve dynamic data inside custom `_attributes` — and a loop-aware tag resolves per-item there (use a delimiter, not JSON)
- Bricks `_conditions` on an ACF `true_false` read the RAW value — `empty` / `empty_not` work
- ACF `true_false` rendered into a Bricks custom `_attributes` value outputs `"True"` / `"False"`, not `"1"` / `"0"`
- Markup generated by a custom dynamic tag CANNOT be styled by Bricks global classes — its CSS must live in the child theme
- Editing `bricks_global_classes` via WP-CLI while a Bricks builder tab is open gets silently reverted
- Deleting a global class in the Bricks Style Manager leaves DANGLING refs in element `_cssGlobalClasses`
- Bricks ships a default `blockquote` style (4px left border + Georgia) that hits any `customTag: "blockquote"`
- `display:flex` breaks an inline comma-separated `{post_terms_*}` list — it eats the space after the comma
- Bricks auto-adds `aria-current="page"` to a link whose href EXACTLY matches the current URL — drive active states from it
- Bricks form `fromName` / `fromEmail` / `emailTo` / `emailSubject` are read ONLY by the Email action
- Bricks — seed utility global-class "anchors" from a plugin so child-theme classes show in the picker
- Bricks — flex/gap control KEYS depend on the ELEMENT TYPE: `_direction`/`_columnGap`/`_rowGap` on layout elements, `_flexDirection`/`_gap` on everything else
- Bricks — an empty `text-basic` renders nothing; use `block`/`div` for decorative empties
- Bricks — the `html` element is the ungated raw-markup injector; reserve it for genuinely non-native markup
- Bricks — flex/grid + gap that arranges BEM children belongs on the Container, not the single-child Section
- Bricks — header/footer TEMPLATE content lives in `_bricks_page_header_2` / `_bricks_page_footer_2`, not `_bricks_page_content_2`
- Bricks has built-in sanitized SVG upload — don't add SVG handling to the core plugin; and `get_allowed_mime_types()` reads false under WP-CLI
- Test custom Bricks dynamic tags with `bricks_render_dynamic_data()`, NOT manual `apply_filters()`
- A WP-CLI write to `_bricks_page_content_2` can silently not persist — verify by DB read-back; and global-class CSS is inline, not in `post-*.min.css`
- Advanced Themer "Remove style controls" tweak silently kills ALL typed-setting CSS site-wide — only `_cssCustom` survives
- Typed flex `_columnGap`/`_rowGap`/`_direction` silently don't emit when a global class is on a `text-basic` element — only on layout elements (div/block/container)
- Bricks builder open during a WP-CLI meta write → saving from that tab silently clobbers the DB edit
- Frontend-only JS widget (Leaflet map etc.) renders as a blank box inside the Bricks builder canvas — looks clobbered, isn't
- Bricks Code element written headlessly renders nothing until you regenerate its code signature
- A mockup's "reuse these classes" note goes stale after a builder rework — reconcile against live `bricks_global_classes` BEFORE building
- Grid container: `_alignItems` is ignored, `_alignItemsGrid` is the operative key (unset emits `align-items: initial`)
- `Assets_Files::regenerate_css_files()` prints "Error: Control type number is not defined!" and still succeeds
- Bricks Form: `redirectAdminUrl` silently overrides the custom redirect AND mangles it into a literal path
- Bricks custom login page ignores `?redirect_to=` whenever a redirect action is configured
- A Bricks template imported from another site carries that site's numeric IDs — fonts, ACF fields, and form settings all break silently
- Dynamic tags do NOT re-resolve inside the value a custom dynamic tag returns
- Custom Bricks query type over a non-post source: post-context tags silently resolve to nothing

**BricksExtras**

- BricksExtras element `name` strips hyphens from the file basename
- BricksExtras elements are gated behind a per-element admin enable flag
- BricksExtras Offcanvas Nestable — `clickTrigger` is the master selector for burger-toggle behavior
- ProSlider list semantics (BricksExtras)
- BricksExtras ProAccordion has a hardcoded `:where()` gray header background
- BricksExtras ProAccordion emits no `aria-expanded` / `aria-controls` in SSR markup
- BricksExtras element-level styling doesn't emit via CLI CSS regen — "Control type X is not defined!"
- Sticky header + offcanvas inside the header template — the panel "drops"/slides with the header
- BE Pro Slider Gallery default lazy-load ships placeholder `src` — kills hero/LCP images
- BricksExtras before/after renders nothing server-side — verify headless with Playwright, and beware the screenshot-timing false alarm

**Frontend Toolkit (animations)**

- Frontend Toolkit must skip the builder iframe, not just the builder main frame
- Frontend Toolkit `staggerObserver` does not pick up AJAX-injected children inside an already-fired stagger parent
- Frontend Toolkit — never put `.anim-*` on a content wrapper taller than the viewport

**ACSS**

- ACSS settings — write via `Database_Settings::save_settings()`, never direct option writes
- ACSS heading sizes (`h1-max` / `h1-min`) don't reach `--h1` — override in the child theme
- ACSS `option-<slot>-clr` toggles gate whether a color slot compiles at all
- A column "stack" class (`_direction:column` + `_rowGap`) is byte-equivalent to the ACSS `.gap--N` utility
- `_inner` is dead — the ACSS Container replaces it
- ACSS — "Remove Deactivated Classes" toggle is the master ACSS→Bricks sync switch (misnamed)
- ACSS — `clamp()` values in an `@supports` block override the rem fallbacks in `:root`
- ACSS — spacing/text scale stops at `xs`; using `2xs` silently falls back to an invalid var
- ACSS — `automatic-bricks.css` enqueues AFTER the child theme; override ACSS tokens via `:root`, not selectors
- ACSS — button bg-context wrappers override variant classes via specificity
- ACSS — "light"/"dark" variants of a NEAR-BLACK base resolve to LIGHT colors
- ACSS — changing a base color hex in the Dashboard can wipe variation overrides on that family
- ACSS — `:where(section…)` makes any hand-rendered `<section>` flex-column-centred; `section > div` forces its children to column
- ACSS palette shades are dashboard-derived — a WP-CLI base-colour write leaves the ramp stale
- ACSS custom CSS / Global SCSS is delivered INLINE (after automatic.css), not as a linked file
- ACSS v3 settings UI is a shadow-DOM front-end overlay — a11y-tree automation can't reach it
- ACSS type: per-level Font Size Override hits a non-geometric brand scale exactly
- ACSS is fully configurable headless via `Database_Settings::save_settings()` — but `API::update_settings()` is broken in 3.3.6
- ACSS color shade ramps are stored per-shade, NOT recomputed from the master — must rewrite the ramp partials
- ACSS → Bricks palette sync only hooks under `is_admin()` — CLI saves regenerate CSS but never update the color picker

**ACF**

- ACF Pro — `default_value` seeds the form only, not `get_field()` reads
- ACF — `true_false` opt-out fields: legacy posts have NO meta row, so `value='1'` excludes them
- ACF — `acf/prepare_field`: `$field['name']` is the PREFIXED input name; match on `_name`
- ACF — `acf_form()` front-end survival kit
- ACF field removal — `get_field()` stops working but the raw post meta survives
- ACF `url` field type rejects relative paths and query strings
- Bricks ACF query loop: `objectType: acf_<field>`; repeater subfields are `{acf_<repeater>_<subfield>}`
- ACF `gallery` / `image` fields are NOT loopable in Bricks — including repeater image subfields

**WordPress core — CPTs, rewrites, canonical**

- A CPT named `author` collides with WP's built-in `?author=` query var — single URLs 404
- A term archive whose slug matches a CPT single 301s to that single (`redirect_canonical` collision)
- Favicon: WP native Site Icon handles raster but not SVG; programmatic set skips the `site_icon-*` sizes

**Rank Math + Bricks**

- Rank Math `%excerpt%` description template produces junk on Bricks-built Pages
- The `rank_math_modules` option has two traps — slugs are legacy, and writing it does NOT create the module's DB tables
- Bricks `bricks_template` CPT is publicly indexable AND in the Rank Math sitemap by default
- Rank Math — SEO scores are computed CLIENT-SIDE; the DB value goes stale and NULL ≠ unoptimized
- Rank Math — `og:type` defaults to `article` on EVERY non-homepage page, including archives
- Disabling Rank Math's Schema (Rich Snippets) module removes ALL its JSON-LD — including the default @graph
- Rank Math routes a CPT *named* `author` through its built-in Author sitemap provider
- Rank Math's per-post-type sitemap toggle is all-or-nothing — turning a CPT off removes its ARCHIVE too
- `blog_public = 0` on staging masks per-page noindex — you cannot verify indexing config until you lift it

**Mailster**

- Mailster — custom dynamic tags use `{tag:option}` syntax and resolve at SEND time, not in the editor

**WooCommerce**

- Bricks — `{woo_product_price}` outputs `price_html` and renders as HTML in a Basic Text element
- Bricks — the WooCommerce integration sheet loads AFTER the child theme and restyles Woo surfaces
- WooCommerce — Cart/Checkout pages default to BLOCKS, which bypass classic template overrides
- WooCommerce — "Coming Soon" (Launch Your Store) gates STORE pages to non-managers; looks like a broken page
- Verifying gated/cart-dependent Woo pages — the WC cart session can't be held over curl; render via `do_shortcode`
- WooCommerce — the cart's sparse 6-column table needs `table-layout: fixed`, not auto
- WooCommerce — Woo overrides `wp_mail_from`, so transactional mail can fail DMARC even when plugin mail passes
- WooCommerce — brand transactional emails via SETTINGS, not template overrides (esp. with `email_improvements` ON)
- WooCommerce — admin-created customers get WP's PLAIN email, not the branded WC welcome
- WooCommerce Subscriptions — create subscription products from WP-CLI
- WooCommerce Subscriptions — manual gateways (COD) are hidden on subscription carts until "Accept Manual Renewals" is on
- WooCommerce Subscriptions — the staging-site lock silently SKIPS all automatic renewals after a Local→production migration
- WooCommerce Subscriptions — admin-created ("manual") orders NEVER spawn a subscription, even for subscription products

**WS Form**

- WS Form's PHP API needs `wp_set_current_user(1)` from WP-CLI — reads included
- WS Form `WS_Form_Field::db_create()` needs an EMPTY label or the field gets ZERO meta
- WS Form — choices live in `data_grid_checkbox`/`data_grid_radio`; the export JSON is the importable artifact
- WS Form — skin it by overriding root `--wsf-form-*` vars, never by writing rules against `.wsf-field` / `.wsf-label`
- WS Form — per-field-type CSS loads AFTER the theme; the native checkbox IS the styled box (sibling of the label, no wrap mode)
- WS Form CAPTCHA (Turnstile/reCAPTCHA) keys live in the global `ws_form` option, not the field meta
- WS Form Pro implements Turnstile natively — and Cloudflare "Turnstile Spin" is redundant on any plugin stack
- A client-rendered form cannot be verified with `curl` — check the CAPTCHA provider's own call count instead

**Roles & Capabilities**

- Bricks builder access is admin-only by default — custom roles get NO builder access unless explicitly granted
- Walling Rank Math to admins — deny `rank_math_*` caps; the editor role ships with the metabox cap
- `current_user_can('assign_terms')` is a false negative — check the taxonomy's MAPPED capability
- A CPT registered `capability_type => 'post'` makes the "blog" caps load-bearing — do not drop them on a site with no blog

**CSS general**

- Author `a { ... }` rules silently lose to the UA stylesheet's `a:link`
- Stacking-context bugs from `transform: translateY(-50%)`
- Stretched-link cards break three silent ways — specificity, `clip-path`, absolute positioning
- `<details>` content can't be force-shown on desktop (Chrome `::details-content` content-visibility)

**Fonts**

- Variable-font prep for Bricks: TTF→WOFF2 (keep axes), subset, and the opsz-instance trap
- Bricks custom fonts — `bricks_font_faces` meta schema + italic key encoding (also a `02` schema-library harvest candidate)

**WP-CLI**

- WP-CLI — inline `wp eval` fatals silently on `"{$arr[barekey]}"` in PHP 8
- WP-CLI — `is_ssl()` is false, so WooCommerce reports gateways "unavailable" and strips the Payment Methods nav
- Local: `wp db query` fails on the mysql socket; `wp eval`/`wp option get` work
- `wp media import` of SVG fails as CLI user 0 — sideload as an admin user
- `wp eval-file` dies silently (exit 0, zero output) on some script files — run via `wp eval 'include ...'`
- WP-CLI `--prompt` ECHOES the resolved command line, secret included, to stdout — use `wp eval` + file read for secrets
- The WP-CLI upgrader skin ECHOES premium package URLs, licence key included — update licensed themes/plugins with `--quiet` + version readback

**Diagnostic patterns**

- Diagnostic JS via a Bricks code element
- A copy sweep over Bricks content misses `_attributes` values — aria-labels and alt text are copy too

---

# === ESTABLISHED — "we know" ===

## WordPress + Bricks Builder

### Doubled-class selectors beat Bricks inline CSS cascade
**Symptom / When:** A child-theme stylesheet rule with equal specificity to a Bricks Global Class rule does not win.
**Why:** Bricks injects Global Class CSS inline in the page `<head>`, which by source order beats the external child-theme stylesheet. Doubling the class (`.foo.foo`) bumps specificity above the inline Global Class without `!important`.
**Fix:** `.foo.foo { ... }` in the child-theme sheet.
**First seen:** V1 baseline, 2026-05-24. (Distinct from the layered-`@layer bricks` case below — different cascade mechanic, same trick.)

### Bricks element IDs must be 6-char alphanumeric, never all-numeric
**Symptom:** `{query_results_count_filter:<id>}` and similar dynamic-data tags render as empty strings.
**Fix:** Open the element in the builder, change the ID to a mix of letters and numbers (`vidlst`, not `593395`). When constructing element trees programmatically, generate 6-char alphanumeric IDs.
**First seen:** V1 baseline, 2026-05-24.

### `_cssId` is the stable hook for PHP filters, not Bricks' internal element id
**Why:** Bricks' internal element id changes on duplicate. The HTML `id` attribute (`_cssId` in builder data) is user-controlled and survives duplication.
**Fix:**
```php
add_filter( 'bricks/query/run', function( $results, $query_obj ) {
    if ( $query_obj->settings['_cssId'] !== 'memfav' ) return $results;
    // ... custom query logic
}, 10, 2 );
```
**First seen:** V1 baseline, 2026-05-24.

### Re-sign Bricks code elements after any DB-side edit
**Symptom:** A code element renders blank with no error.
**Why:** Bricks signs every code element with `wp_hash($code)` and silently refuses to execute if the signature does not match the stored hash. Direct DB edits invalidate the signature.
**Fix:** Re-sign via `wp eval` after DB edits, or open and save in the builder, which re-signs automatically.
**First seen:** V1 baseline, 2026-05-24.

### Bricks code elements store CSS in `settings.cssCode`, not `settings.code`
**Symptom:** A script scanning `_bricks_page_content_2` for "empty" code elements flags elements as empty even though they emit CSS to the page. Removing them silently breaks responsive layouts, counter patterns, accordion open-states.
**Why:** The Bricks `code` element has two content fields — `code` (HTML/PHP) and `cssCode` (CSS only). The wireframe-to-Bricks paste converter puts unmappable CSS into `cssCode` and leaves `code` empty. A naïve check on `code` returns empty even though `cssCode` carries working CSS. The element renders as `<div class="brxe-code"><style>...</style></div>` on the front end but looks empty in the structure tree.
**Fix:** When inventorying or removing code elements, check BOTH fields:
```php
$code    = trim( $el['settings']['code'] ?? '' );
$cssCode = trim( $el['settings']['cssCode'] ?? '' );
if ( $code === '' && $cssCode === '' ) {
    // Truly empty — safe to remove
}
```
To migrate `cssCode` to a central stylesheet: dump it, copy to the right `style.css` section, then remove the husk. The comment `/* Extracted from pasted content that could not be mapped... */` at the top of the CSS is the wireframe-import signature — if you see it, the element is loaded with `cssCode`.
**First seen:** AHML, 2026-05-12 — a "remove empty husks" batch deleted 17 code elements; 16 carried real CSS in `cssCode` (~9.6 KB). Caught when Process page step numbers disappeared.

### `update_post_meta` silently fails on `_bricks_page_*_2` keys from WP-CLI
**Symptom:** `update_post_meta($post_id, '_bricks_page_content_2', $array)` returns `false`. No PHP warning, no log entry. `get_post_meta` afterward shows the meta unchanged. The script reports success; nothing lands.
**Why:** Bricks guards these three keys (`_bricks_page_content_2`, `_bricks_page_header_2`, `_bricks_page_footer_2`) two ways: an `update_post_metadata` filter (`Bricks\Ajax::update_bricks_postmeta`) and a `sanitize_post_meta__bricks_page_content_2` callback (`Bricks\Ajax::sanitize_bricks_postmeta` → `Helpers::security_check_elements_before_save()`). The sanitize path returns your new elements untouched **only** when the current user passes both `Builder_Permissions::user_can_modify_element_count()` **and** `Capabilities::current_user_can_execute_code()`; otherwise it hands back the *existing* stored elements (or runs non-code elements through `wp_filter_post_kses`). WP-CLI runs as user 0, so the check fails, the old array is returned unchanged, and `update_post_meta` sees no diff → returns `false`, with no warning and nothing logged. (An in-builder request carries a valid `bricks-nonce-builder`, which short-circuits the check — that path is unavailable from CLI, so an admin user context is the substitute.)

**Fix — three routes around the same guards.** Both guards are registered **by the Bricks theme** (`Bricks\Ajax`), which is why (b) works and why (c) sidesteps them entirely:

**(a) Set the user — the canonical route.** First line of any `wp eval` / `wp eval-file` script. User 1 = admin with `full_access` + code execution, so `security_check_elements_before_save()` returns your elements as-is:
```php
wp_set_current_user( 1 );
$content = get_post_meta( $tmpl_id, '_bricks_page_content_2', true );
// ... mutate $content ...
update_post_meta( $tmpl_id, '_bricks_page_content_2', $content );
```
Or run the whole script as that user straight from the CLI — `wp eval-file script.php --user=<admin-login>` — equivalent to the `wp_set_current_user()` call and cleaner when a script writes these keys throughout. **Whichever route: check the return value.** `false` from `update_post_meta` is the only signal you get. (Confirmed still current on Bricks 2.3+ — NLTA, 2026-07-06.)

**(b) Unload the theme — `wp --skip-themes eval`.** The theme never loads, so neither guard is registered:
```bash
wp --skip-themes eval '$els[] = [...]; update_post_meta( 188, "_bricks_page_content_2", $els );'
```

**(c) Bypass the meta API — direct `$wpdb`.** For when you must write inside a theme-loaded request (e.g. a larger eval that also needs `update_field`/`get_field` in the same pass):
```php
$wpdb->update(
    $wpdb->postmeta,
    [ 'meta_value' => maybe_serialize( $els ) ],   // no wp_slash: you're skipping the API's internal wp_unslash
    [ 'post_id' => $id, 'meta_key' => '_bricks_page_content_2' ]
);
wp_cache_flush(); // drop the stale meta cache for the row
```

**⚠️ `--skip-themes` and CSS regen are mutually exclusive in one `wp` invocation.** `\Bricks\Assets_Files::regenerate_css_files()` is defined in the same theme `--skip-themes` unloads — skip it and regen fatals (`Class "Bricks\Assets_Files" not found`); keep the theme and the meta write no-ops. **Split them:** write meta under `--skip-themes`, then regen in a *separate* `wp eval` with themes loaded + `wp_set_current_user(1)`. (Or use route (c) and regen normally.)

**Symptom triage — this entry covers a write that FAILS or no-ops. A write that lands and then reverts *minutes later* is a different bug** — see "Editing `bricks_global_classes` while a Bricks builder tab is open gets silently reverted."

The `bricks_global_classes` option is not gated this way (but see the builder-clobber entry — it has its own hazard).

**First seen:** AHML, 2026-04-27 — appending the article section to the Blog Single template. The script printed success and exited cleanly; the element count in the DB stayed unchanged. (This is the full incident record for the auth requirement named in `00` and `02`.)
**Mechanism deepened:** AHML, 2026-07-01 — traced to the `sanitize_post_meta__bricks_page_content_2` → `security_check_elements_before_save()` path (the sanitize callback hands back the *existing* array, so `update_post_meta` sees no diff — which is why it returns `false` rather than erroring).
**Extended:** TAB, 2026-04-25 / 2026-06-09 / 2026-06-24 — the `--skip-themes` and `$wpdb` routes, and the regen mutual-exclusion, each found independently before the shared mechanism was understood. TAB also logged a "write returns true, then silently reverts" signature (2026-04-25) that **neither guard explains** — it is almost certainly the builder-clobber bug, which wasn't characterised until 2026-06-22.

### Bricks Element Manager — a disabled element renders as NOTHING, even when it's in the template data
**Symptom / When:** An element is confirmed present in `_bricks_page_content_2` but produces zero frontend output — no wrapper div, no error, nothing.
**Why:** Bricks 2.x's Element Manager (`bricks_element_manager` option) lets you disable unused elements for performance, and a disabled element is **skipped entirely at render**. An install that's been through a performance pass can have 20+ disabled — so an element you've never used on that site before is exactly the one likely to be off.
**Fix:** Re-enable the one you need, then opcache reset + cache purge:
```php
$m = get_option( 'bricks_element_manager', [] );
unset( $m['breadcrumbs'] );
update_option( 'bricks_element_manager', $m );
```
**Symptom triage:** this looks *identical* to the write-guard entry above — data present, nothing rendered. **Check the Element Manager first**: it's a one-line read, versus debugging a write path that turns out to be fine.
**First seen:** NLTA, 2026-07-06 — an injected breadcrumbs element didn't render; `breadcrumbs` was among ~23 disabled elements on that install.

### Bricks Query Filter elements need a manual reindex AND a cron tick after programmatic creation
**Symptom:** A `filter-radio` / `filter-checkbox` / `filter-select` element added via `update_post_meta` renders, but the filter's `<ul>` is empty — no options. `Bricks\Query_Filters::reindex()` returns `true`, but `wp_bricks_filters_index` stays empty.
**Why:** Bricks maintains three filter tables: `wp_bricks_filters_element`, `wp_bricks_filters_index_job` (queued jobs), `wp_bricks_filters_index` (the lookup table). `reindex()` from WP-CLI populates the job queue, but the jobs are processed by the `bricks_indexer` cron event — they sit unprocessed until cron ticks.
**Fix:** After `reindex()`, force the cron event:
```php
wp_set_current_user( 1 );
// ... add filter element ...
( new \Bricks\Query_Filters() )->reindex();
// Then from shell (cron runs in its own request):
//   wp cron event run bricks_indexer
```
Or `wp cron event run --all`. Confirm with `SELECT COUNT(*) FROM wp_bricks_filters_index` (> 0).
**First seen:** AHML, 2026-04-27 — Blog Archive filter facet. Element registered, `reindex()` returned true, index table stayed at 0 rows.

### Bricks term dynamic data tag is `{term_url}`, not `{term_link}`
**Symptom:** A term-context dynamic tag renders as the literal string — `<a href="{term_link}">…</a>`. `{term_name}` resolves; the link href does not. No error.
**Why:** Bricks registers `term_url` for a term's archive URL, not `term_link`. Easy to assume it matches the `post_link` pattern; it does not. When a tag is not recognized, Bricks outputs it verbatim.
**Fix:** Use `{term_url}`.
```php
'link' => [ 'type' => 'meta', 'useDynamicData' => '{term_url}' ],
```
**First seen:** AHML, 2026-04-27 — Blog Archive filter pills via a term query loop.

### Bricks filter-radio / filter-checkbox default to vertical column — set `displayMode: 'button'` for horizontal pills
**Symptom:** A filter element renders, options populate, but items stack vertically — even with `display: flex; flex-wrap: wrap` on the container.
**Why:** Bricks ships baseline CSS keyed off the `data-mode` attribute on the `<ul>`. Default `data-mode="default"` styles as `flex-direction: column`. `data-mode="button"` gets horizontal flex-wrap, the option gap variable, and auto-hides the underlying inputs. The selector `.brxe-filter-radio[data-mode=default]` is more specific than a single class, so a plain class override loses.
**Fix:** Set `displayMode: 'button'` in element settings. The rendered `<ul>` then gets `data-mode="button"` and Bricks supplies horizontal styling. Pill CSS targets the `<label>`.
```php
$el['settings']['displayMode'] = 'button';
```
**First seen:** AHML, 2026-04-27 — Blog Archive category filter.

### Bricks archive template `archiveType: postType / post` never matches for built-in `post`
**Symptom:** A Bricks `archive` template with `archiveType` conditions never fires on the WP blog index. Header/footer render; template content is missing.
**Why:** Bricks' archiveType matcher checks `is_post_type_archive()`. The built-in `post` type has no CPT archive — its surface is `is_home()`, which Bricks treats as `content_type='content'`, not `archive`. When `is_home()`, Bricks resolves the page to `get_option('page_for_posts')` and matches templates against THAT page.
**Fix:** Set a "Blog" page as the Posts page in Settings → Reading, then condition the archive template on that page ID:
```php
'templateConditions' => [
    [ 'id' => 'cnd001', 'main' => 'ids', 'ids' => [ <page_for_posts_id> ] ],
],
```
Template type can stay `archive` — Bricks treats type as a UI hint; matching is by condition.
**First seen:** AHML, 2026-04-27 — Blog Archive hero never rendered on `/blog/`.

### Bricks filter active state — color must be set on `.brx-option-text`, not the `<label>`
**Symptom:** A filter facet's active pill has low-contrast text. Setting `color` on the wrapper `<label>` does nothing.
**Why:** Bricks renders each option as `<label> > <input> + <span.brx-input-indicator> + <span class="brx-option-text bricks-button">`. The visible text is the inner `<span>`, which carries `.bricks-button` — and `.bricks-button` has its own `color` rules that win because the span is a child of the label.
**Fix:** Target the inner span. The active span gets `brx-option-active`:
```css
.blog-filter__radio .brx-option-text.brx-option-active { color: var(--base); }
```
Keep the label rule for background and border-color. Same pattern for `filter-checkbox`.
**First seen:** AHML, 2026-04-30 — Blog Archive filter, active pill rendered cognac-on-cognac.

### Bricks per-post CSS cache hides DB-side global-class edits until regen
**Symptom:** You edit `bricks_global_classes` via the DB. `wp cache flush` runs fine, but rendered pages still reference the old classes.
**Why:** Bricks pre-builds per-post CSS at `wp-content/uploads/bricks/css/post-{ID}.min.css`, containing the inlined Global Class CSS as it was at generation time. Bricks reads these on render until they are stale-flagged or the post is re-saved. Object cache flush does not touch them.
**Fix:** Delete the cached files so Bricks regenerates:
```bash
rm -f wp-content/uploads/bricks/css/post-*.min.css
```
The system files in that directory (`color-palettes.min.css`, etc.) are safe to leave. But deleting is not enough on its own — see the next entry.
**First seen:** AHML, 2026-04-29 — CSS sweep deleted 62 orphan global classes; smoke test still showed the deleted names because per-post CSS was stale.

### `rm -f post-*.min.css` does NOT auto-regenerate — frontend silently degrades
**Symptom:** After deleting `post-*.min.css`, the front end loads but layout breaks subtly. Most diagnostic: a Bricks offcanvas mobile nav renders inline as document content on desktop. No error; pages still 200.
**Why:** With `cssLoading = 'file'`, Bricks expects per-post files to provide structural rules (`.brxe-offcanvas{visibility:hidden}`, transform rules). When the file is missing, those rules go missing and elements render in unstyled flow. A render request does NOT reliably trigger regeneration — the on-demand path has cap checks that fail silently.
**Fix:** Always run the explicit regen and verify file count:
```php
wp_set_current_user( 1 );
\Bricks\Assets_Files::regenerate_css_files();
$files = glob( WP_CONTENT_DIR . '/uploads/bricks/css/post-*.min.css' );
echo "post-*.min.css count: " . count( $files ) . "\n";
if ( empty( $files ) ) exit( 1 );
```
The correct method is `Assets_Files::regenerate_css_files()` — not the `Assets::generate_*` methods, which are for inline-render mode and do not write files.
**First seen:** AHML, 2026-04-30 — a CSS sweep ran `rm` and assumed visit-driven regen. Site ran ~12h with all per-post CSS missing.

### Bricks button utility classes (`btn--outline`, `btn--primary`) are Bricks-injected, not user-defined
**Symptom:** During a Global Class cleanup you mark `btn--outline` as orphan (no element references it) and delete it — but it still renders on the page.
**Why:** The Bricks Button element's Style/Color/Size settings emit hardcoded class names (`btn--outline`, `btn--primary`, `btn--m`) into the rendered class list, independent of user-defined Global Classes. The CSS is generated at runtime from the button's settings. A Global Class of the same name can be a true orphan AND the class still renders.
**Fix:** During cleanup, treat `btn--*` as Bricks-managed. Safe to delete the Global Class entry; rendering is unaffected. Verify by inspecting rendered HTML.
**Corollary — PHP-rendered surfaces get NO button CSS at all.** Because the CSS is generated per Bricks Button element at render, a plain `<a class="btn btn--primary">` in a PHP template (a dashboard, a Woo template override) renders unstyled — mono font only, no fill/padding/border — while the same classes look right on Bricks-built pages. It is in no globally-enqueued stylesheet (only `automatic-gutenberg.css`, block-editor-only, plus the child theme's mono-font `.btn` rule). No Bricks Button on the page = no button CSS.
**Fix (non-Bricks surfaces):** define the button CSS yourself, scoped so it cannot collide with Bricks' per-element CSS on builder pages (e.g. `.woocommerce .btn--primary{background:var(--primary);color:var(--white);…}`). Consume `--primary`/`--white`/`--primary-hover` to match the ACSS button.
**First seen:** AHML, 2026-04-29 — CSS sweep deleted `btn--outline` as orphan; it kept rendering. No actual breakage. · **Extended:** VMG, 2026-06-07 — portal dashboard CTAs rendered as bare links until `.btn` CSS was added under `.woocommerce`.

### Bricks `bricks/allowed_html_tags` filter for custom elements
**Symptom:** Builder warning "tag X not allowed" on a code element using e.g. `<button>` — render is correct but the builder validation is noisy.
**Fix:**
```php
add_filter( 'bricks/allowed_html_tags', function( $tags ) {
    $tags[] = 'button';
    return $tags;
});
```
**First seen:** V1 baseline, 2026-05-24.

### Bricks emits skip-links automatically via the `bricks_body` action
**What it does:** Bricks core emits two skip-links via `bricks_body` at frontend bootstrap, before the header template renders: `<a class="skip-link" href="#brx-content">` and a footer-skip variant. It also emits `<main id="brx-content">`.
**Why it matters:** When building a header template, do not author your own skip-link — you will get two anchors with the same target, confusing assistive tech.
**What you DO provide:** CSS to style `.skip-link` visually-hidden-until-focused. Bricks does not ship that rule — the anchor renders visible by default. Add a child-theme `.skip-link` rule (hide via `transform: translateY(-150%)`, restore on `:focus`).
**First seen:** KSCBS, 2026-05-17 — header v2 rebuild initially authored a duplicate skip-link.

### Bricks — typed `_border` setting uses a flat width/style/color shape, not per-side nested objects
**Symptom:** A `_border` setting written with a per-side nested shape (`_border.bottom.{width,style,color}`) persists in `bricks_global_classes` without error, but the rendered page contains no border CSS at all.
**Why:** Bricks' `_border` schema is flat — width is a per-side object, style is a scalar, color is a single object, radius is a per-side object. Bricks' emitter walks `_border.width` / `.style` / `.color` / `.radius` explicitly; anything else is ignored and not even stripped on next builder load, so a readback looks correct while the output is empty.
**Fix:** Use the flat shape (it is in the `02` schema library). For "bottom border only," set `width.bottom = '1'`, other sides `'0'`. Confirm by curl + grep of the inline CSS. A correct declaration looks like `border-top: 1px solid var(--base-light); border-right: 0 solid var(--base-light); ...` — if there are no `border-*` declarations, the shape is wrong.
**First seen:** KSCBS, 2026-05-17 — About page built programmatically; three classes had `_border` in nested shape, all rendered with no border.

### Bricks — typed-setting breakpoint suffixes go on the OUTER key, not as a sibling inside a nested dict
**Symptom:** A responsive typed setting written as a sibling key inside a dict (`'font-size:tablet_portrait' => '3rem'` inside `_typography`) saves and persists, but the rendered CSS emits it as a literal malformed property — `font-size:tablet_portrait: 3rem;` — which the browser drops. The override never fires.
**Why:** Bricks' emitter honors the breakpoint suffix only on the OUTER typed-setting key — `_typography:tablet_portrait` wraps the rules in a media query. A suffix on a nested property is treated as a literal key name.
**Fix:** Each breakpoint variant is a separate top-level key with its own typed dict:
```php
'_typography' => [ 'font-size' => '4rem', 'color' => [ ... ] ],
'_typography:tablet_portrait' => [ 'font-size' => '3rem' ],
```
**Related:** `_gridColumn:breakpoint` does not emit CSS at all — use `_cssCustom` with an explicit media query for responsive grid placement. **Related:** keys for unregistered breakpoints save to the DB and emit zero CSS silently. Confirm the registered set with `wp eval "echo wp_json_encode(\Bricks\Breakpoints::\$breakpoints);"` before relying on a breakpoint.
**First seen:** KSCBS, 2026-05-17 — About page mobile review; a `font-size` breakpoint override emitted as invalid CSS, and a `_gridColumn` breakpoint key emitted nothing.

### Bricks — `_widthMax: '100%'` is special-cased to suppress horizontal scrollbars
**Symptom:** A wrapper with `_widthMax: '720px'` appears to overflow the viewport at narrow breakpoints, even though `max-width` should only constrain.
**Why:** Bricks' converter treats `_widthMax` set to exactly `'100%'` or `'100vw'` as a scrollbar-suppression directive, distinct from any other value. Any other value (`720px`) goes through the standard `max-width` path and does not get that treatment.
**Fix:** When a fixed-px `_widthMax` overflows at small viewports, override at the breakpoint with the literal `'100%'`:
```python
'_widthMax': '720px',
'_widthMax:mobile_portrait': '100%',
```
`'auto'` / `'unset'` / omitting the key do not trigger the same path — it has to be exactly `'100%'` or `'100vw'`.
**First seen:** KSCBS, 2026-05-07 — `home-cta__inner` overflowed below 478px.

### Bricks — author rules with equal specificity lose to `@layer bricks` framework rules
**Symptom / When:** An author CSS rule with equal specificity to a Bricks framework rule (e.g. `.bricks-button`), loading source-order after `frontend-light-layer.min.css`, should win per CSS Cascade Level 5 (unlayered beats layered) — but does not. The framework property keeps winning. Confirmed for `display` and `border-color`.
**Why:** Bricks declares its framework rules inside `@layer bricks { ... }`. Per spec, unlayered author rules should beat layered ones unconditionally — empirically this does not hold for at least these properties. Whether it is a Chromium quirk or a Bricks surfacing detail is not pinned down; the repro is reliable.
**Fix:** Bump author-rule specificity with the doubled-class trick — no `!important` needed:
```css
@media (max-width: 599px) {
  .header-bot__cta.header-bot__cta { display: none; }  /* (0,2,0) beats (0,1,0) */
}
```
Diagnostic: if the rule appears in the rendered CSS but the behavior does not change, this is the candidate. Skip the spec rabbit hole, apply doubled-class.
**First seen:** KSCBS, 2026-05-17 — a hide-below-600 rule on `.header-bot__cta` rendered into the page but did not hide the button. (Distinct from the inline-CSS doubled-class entry at the top — different mechanic, same fix.)

### Bricks — `*{border-color}` plus a default 1px border on native inputs = "ghost" borders on every form
**Symptom / When:** A form built with any plugin emitting native `<input>`/`<select>`/`<textarea>` renders with a 1px light-grey border on every field, even when the form plugin sets border-width to 0. Author CSS to strip it at equal specificity does not fully take.
**Why:** Two rules inside `frontend-light-layer.min.css`, both in `@layer bricks`: `* { border-color: var(--bricks-border-color) }` (`#dddedf`), and `.input, input:not([type=submit]), select, textarea { border-style: solid; border-width: 1px }`. Per the layered-cascade entry above, equal-specificity author rules lose the color portion.
**Fix:** For any project that does not want default form borders, write a defensive child-theme rule that strips the border with `!important`, then opt back in where wanted:
```css
.my-form, .my-form *, .my-form input, .my-form select, .my-form textarea {
  border: 0 !important;
}
```
Pair with a brand-aligned input fill so borderless fields stay visible.
**Investigation-order lesson:** When tracing a default style on a Bricks site, start with `frontend-light-layer.min.css` — grep for `*{...}`, `[class*=brxe-]{...}`, `.input{...}`. Do not chase third-party plugin internals first.
**First seen:** KSCBS, 2026-05-17 — WS Forms contact form; inputs rendered with a 1px `#dddedf` border. Initial investigation wrongly focused on WS Forms internals.

### Bricks — where CSS actually lives: global-class CSS is INLINE, element-typed CSS is in `post-{id}.min.css`
**Symptom / When:** Two mirror-image false alarms. (1) You grep `post-{id}.min.css` for a Global Class selector after a DB write + regen and find nothing — even though the class clearly renders styled. (2) You add typed settings to a *specific element* (`_border`/`_objectFit`/`_aspectRatio`), regen, then `curl | grep '\.brxe-<id>'` the page and find no CSS — looks like the typed settings didn't emit.
**Why:** Bricks splits CSS by scope. **Global Class** CSS is emitted inline in the document head (`<style id="bricks-frontend-inline-css">`). **Element-id-scoped** CSS (typed settings on an individual element) is written to the per-post file `wp-content/uploads/bricks/css/post-{ID}.min.css` and loaded via `<link>`. The per-post file carries only element-base + element-id CSS, never the global classes.
**This holds in `cssLoading='file'` mode too** — file mode does not move global classes into a file, because no such file target exists. Source-verified 2.3.10; see "Bricks file-mode CSS never externalizes global-class CSS."
**Fix:** Verify each in its own home.
```bash
# Global class → rendered page (this is the canonical 02 recipe)
curl -sk <url> | grep -oE '\.my-class[^{]*\{[^}]*\}'
# Element typed settings → the per-post file
grep -r '<element-id>' wp-content/uploads/bricks/css/
```
Element typed CSS uses `#brxe-<id>` ID selectors, and for images the dual-route `#brxe-<id>:not(.tag), #brxe-<id> img{…}`. Useful corollary: Bricks' own image default is `:where(.brxe-image) img{height:100%;width:100%}` at **zero specificity**, so an ID-scoped typed `_aspectRatio` on a figure-image wins and renders.
**First seen:** TAB, 2026-05-29 — Single Service hero; a bronze CTA rule was absent from `post-15486.min.css` but present in the page's inline head CSS. **And** TAB, 2026-06-09 — overview image typed settings; `.brxe-psomd0` rules were in `post-15582.min.css`, not inline.

### Bricks emits a Global Class's CSS ONLY when an element on that page references it
**Symptom / When:** You need to know whether grepping a rendered page for `.my-class{…}` is a valid check, or whether Bricks dumps every global class onto every page (which would make grep useless — false positives everywhere).
**Why:** Bricks walks the page's element tree and emits CSS only for classes actually referenced via `_cssGlobalClasses`. Unused classes emit nothing. **Verified empirically** (Bricks 2.3.8): on a page whose tree doesn't reference them, the `.bio-hero*` classes appear **zero** times — no CSS, no markup — while classes the page does use emit their rules inline. The same classes emit normally on the page that uses them.
**Fix:** Grepping the rendered page for a global class's CSS is a **valid** verification — it is the canonical `02` recipe and it does not produce false positives. **Corollary with teeth:** class names that appear only inside a *custom dynamic tag's returned HTML string* are never "seen" as used, so Bricks emits no CSS for them — see "Markup generated by a custom dynamic tag cannot be styled by Bricks global classes."
**First seen:** TAB, 2026-06-25 (the tag-markup incident) — **confirmed by direct test at the 2026-07-15 harvest**, which also retired a contradictory note in TAB's project playbook claiming Bricks emits unused-class CSS. It does not. · **Independently corroborated:** VMG, 2026-06-06 — classes referenced only inside an `html` element's raw-markup string got no CSS, for the same reason (fix: bundle those rules into the `_cssCustom` of a class that IS on a real element — see the `html` element entry).

### Bricks file-mode CSS never externalizes global-class CSS — architecture, not a CLI limitation
**Applies to:** Bricks **2.2+** (fleet baseline). Source-verified on 2.3.10. See the pre-2.2 note at the end.
**Symptom / When:** `cssLoading='file'` is set and `\Bricks\Assets_Files::regenerate_css_files()` (or `wp bricks regenerate_assets`) has run. Per-post, variables, colour-palette and theme-style files all generate — yet every BEM global class still renders **inline**, and `style-manager.min.css` sits at **0 bytes**. Reads as "file mode half-applied."
**Why:** It is not half-applied; there is **no file target for global classes in any mode**. `Assets::generate_global_classes()` (`includes/assets.php`) writes to `Assets::$inline_css['global_classes']`, and is called only from the frontend inline path (`frontend.php`, `assets.php`), builder AJAX, the block editor and query loops — **never from `includes/assets/files.php`**, which contains zero references to global classes. `generate_post_css_file()` writes only `Assets::$inline_css[$content_type]` to disk, so the `global_classes` bucket cannot reach a file by any path. Per-post files therefore carry **element defaults (`.brxe-*`), keyframes and page settings only**. Verified empirically: a Header template file of 7.5 KB contained **0** BEM selectors while its `.header`, `.header__container` rules sat inline.
**Fix:** Nothing to fix — **leave `cssLoading='file'`**. It still externalizes real weight (element defaults, page settings, theme styles, colour palettes, global variables, global custom CSS, global elements). Reverting to UNSET/hybrid does **not** shrink the inline block; it *grows* it, by pulling the element defaults back inline too. Do not spend a builder session chasing this.
**The empty `style-manager.min.css` is a separate, real, fixable fault.** `Assets::enqueue_style_manager_css()` enqueues on `file_exists()` with **no size check**, so a 0-byte file costs a render-blocking request on every page. It holds colour variables, utility classes and scale-property global variables — **not** global classes, so no builder save will ever make it hold them. On **ACSS-driven sites it is permanently empty**: `Ajax::generate_style_manager_css_file()` skips colours with no `light` key ("old format"), and ACSS-generated palettes carry `raw` only (measured: 0 of 207 colours had `light`; 0 of 4 global variables had `scale`; the generator returns `true` and writes 0 bytes). **Fix: delete the file.** The deletion sticks — regen's cleanup filter *excludes* that filename from deletion but never creates it. It reappears populated only if a `light`-format palette is added and saved in the builder, which is correct.
**Option name:** the setting lives in **`bricks_global_settings`**, not `bricks_settings` (which does not exist and returns `false` — a `?? 'default'` against it silently fabricates a plausible answer). Read it the reliable way: `wp eval '\Bricks\Database::get_setting("cssLoading", "UNSET");'`.
**Pre-2.2 installs:** `style-manager.min.css` is `@since 2.2`, so the empty-file half of this entry cannot occur below that — `generate_color_palette_css_file()` is the deprecated shim that now just calls the Style Manager generator. The global-classes-are-inline architecture was not re-verified below 2.2; if a sub-2.2 install surfaces, confirm against its own source before applying the "nothing to fix" verdict.
**First seen:** TAB, 2026-06-26 — pre-launch CSS-handoff sweep; CLI switch left global classes inline, reverted to baseline, deferred to cutover. · **Corrected:** MMHN, 2026-07-15 — this entry cited `bricks_settings`; the real option is `bricks_global_settings`. (Noted in passing: a fresh Local install can ship `cssLoading='file'` already set.) · **Substantially rewritten:** VMG, 2026-08-01 — the old entry's prescribed fix (re-save global classes in the builder so `style-manager.min.css` populates) does not work and cannot: that file never holds global classes. Read against 2.3.10 source rather than inferred from symptoms; "no net win" also overstated, since file mode does move the element-default bulk out of the HTML.

### Bricks `_typography.font-family` quotes the value — use `custom_font_<id>`, not a CSS string
**Symptom / When:** You set a Global Class font-family to `"Jost, sans-serif"` (or `"var(--heading-font-family)"`) and the rendered CSS comes out `font-family: "Jost, sans-serif";` — quoted, treated as one literal name. The browser silently falls back to the default sans.
**Why:** Bricks emits `_typography.font-family` as a quoted CSS string for compatibility with custom font names containing spaces. It does not parse the value as a CSS expression — `var(...)`, comma stacks and `inherit` all get wrapped and broken.
**Fix:** Reference the Bricks **custom font post ID**. Bricks stores each Custom Font as a `bricks_fonts` CPT; setting `_typography.font-family` to `custom_font_<id>` emits the bare font name plus the matching `@font-face`.
```bash
wp post list --post_type=bricks_fonts --fields=ID,post_title
```
System stacks work as literals (`"sans-serif"` is a valid CSS keyword). Named families always go through `custom_font_<id>`.
**First seen:** TAB, 2026-04-25 — Global Class typography assignments; both `var(--heading-font-family)` and `"Jost, sans-serif"` produced quoted output. `custom_font_169` resolved it.

### Bricks image element with `tag=figure` collapses to content size — `:where(.brxe-image).tag` forces `width:auto; height:fit-content`
**Symptom / When:** An image-as-figure (image element, `tag: "figure"`) built for a fill-the-container treatment — absolute-inset-0 hero with `_objectFit: cover` — renders at its natural intrinsic dimensions and gets clipped instead of scaled-and-cropped. Toggling `_objectPosition` in the UI has no visible effect; the lever feels dead.
**Why:** `frontend.min.css` ships `:where(.brxe-image).tag { display:inline-block; height:fit-content; position:relative; width:auto }`. The `:where()` zeros `.brxe-image`'s specificity but `.tag` carries (0,1,0). With `position:absolute; inset:0` AND `height:fit-content`, height comes from content, not the inset values — `bottom:0` is ignored for sizing. The inner img's `height:100%` then resolves circularly against the figure's intrinsic size, so `object-fit:cover` has no aspect mismatch to crop and `object-position` has nothing to position.
**Fix:** Set `_width: "100%"` and `_height: "100%"` on the figure's Global Class. Same specificity (0,1,0), but the page-inline CSS comes after `frontend.min.css` in source order, so it wins.
**Diagnostic recipe:** when an image hero "looks fine but `object-position` does nothing," inspect the rendered figure — computed height matching the img's intrinsic height (not the section's) is this bug.
**First seen:** TAB, 2026-05-02 — Our Process hero; surfaced by noticing the object-position lever was inert.

### Bricks `_aspectRatio` dual-routes to the inner img — it can't drive the figure's box when the image element IS the figure
**Symptom / When:** You consolidate a photo block to image-as-figure (image element, `tag: "figure"`, one class carrying wrapper+img settings) with `_aspectRatio: "3/4"`. The figure collapses to the image's intrinsic dimensions; aspect-ratio appears to do nothing.
**Why:** Bricks' image element redefines `_aspectRatio` with the dual selector `&:not(.tag), img` (`includes/elements/image.php` ~L136). When the class sits on the figure (which has `.tag`), `:not(.tag)` skips it — the rule only matches the inner `img`, which already has `width:100%; height:100%` from Bricks' defaults. Per CSS spec explicit width AND height override `aspect-ratio`. So the ratio lands nowhere useful.
**Fix — the image/figure decision tree:**
- **(a) Layout-sized image** (absolute-inset-0 hero): image element with `tag:"figure"` — one element. Layout drives the box; no aspect-ratio needed.
- **(b) Aspect-ratio'd photo, no caption:** outer `<div>`/block for layout + image element for semantics — two elements. The **wrapper** carries `_aspectRatio` (it's a block, so no dual-route); the image carries `_width:100%; _height:100%; _objectFit:cover`.
- **(c) Captioned photo:** outer block with `tag:"figure"` + plain image + `<figcaption>` — the figcaption MUST be inside its figure.
**First seen:** TAB, 2026-05-02 — photo-block sweep; `partner__photo` consolidated to image-as-figure rendered a collapsed figure. Reverted to wrapper-as-figure, which is correct for aspect-ratio'd photos.

### Wrapper-block-as-figure inherits UA `figure { margin: 1em 40px }` — Bricks zeros it only for `figure.brxe-image`
**Symptom / When:** A captioned photo built as a block with `tag:"figure"` (case (c) above) renders with a visible gutter — ~16px top/bottom, ~40px left/right — inside its container. The wrapper class sets no `_margin`, the parent has no padding, the image fills the figure. The gutter persists.
**Why:** Bricks' `frontend.min.css` ships a UA-mimic `figure { margin: 1em 40px }` and zeros it back out only for the image-as-figure case: `figure.brxe-image { margin: 0 }`. A wrapper-block-as-figure renders `<figure class="brxe-block …">` — no `.brxe-image`, so the zeroing selector misses and the UA-mimic margin survives.
**Fix:** One defensive child-theme rule mirroring Bricks' own pattern for the block variant:
```css
figure.brxe-block { margin: 0; }
```
(0,1,1) beats bare `figure` (0,0,1) without `!important`, and it covers every future wrapper-as-figure so the pattern doesn't re-bite per element.
**Diagnostic recipe:** unexplained white-space around a Bricks figure with nothing in the class settings to explain it → check whether it renders `.brxe-image` or `.brxe-block`.
**First seen:** TAB, 2026-05-03 — About Story section; `story__photo` showed ~40px gutter each side while sibling image-as-figure photos rendered flush.

### Bricks "invalid post type" on Edit with Bricks = stale rewrite rules (not a Bricks setting)
**Symptom / When:** Clicking *Edit with Bricks* redirects to `wp-admin/edit.php?post_type=&bricks_notice=error_post_type`. Note `post_type=` is **empty** — that's the giveaway. It misdirects you toward `bricks_global_settings.postTypes` or CPT registration.
**Why:** Bricks' `template_redirect` handler (`includes/builder.php` ~L1137-1152) calls `get_post_type()` on the parsed main query. If the permalink doesn't resolve — the URL 404s because rewrite rules are stale — no post is queried, `get_post_type()` returns empty, the supported-types check fails, redirect fires. `bricks_template` is hardcoded as supported, so when *templates* hit this it is never a postTypes setting, always rewrites.
**Verify:**
```bash
wp eval 'echo url_to_postid("https://site.tld/template/header/");'   # 0 if broken
curl -sI https://site.tld/template/header/ | head -1                 # 404 if broken
```
**Fix:** `wp rewrite flush`.
**Foot-gun:** a core plugin that flushes only on `register_activation_hook` won't re-flush when you edit CPT registration, add a CPT, or change a rewrite slug. Flush manually after any such edit.
**First seen:** TAB, 2026-04-27 — Header template returned 404 on the front end; Edit with Bricks redirected with empty `post_type=`.

### Bricks 2.3.x `bricks/dynamic_data/render_tag` passes `$tag` as a parsed-tag ARRAY, not always a string
**Symptom / When:** Custom dynamic tags registered via the three-filter pattern fatal on the first frontend request — typically rendering the header:
```
PHP Fatal error: Uncaught TypeError: trim(): Argument #1 ($string) must be of type string, array given
```
**Why:** The three-filter pattern assumes `$tag` is a string like `'{my_tag}'`. In Bricks 2.3.x that is not always true — Bricks pre-parses the tag during element-setting rendering and passes a parsed-tag array (keys `tag`, `filters`, …). `trim($tag, '{}')` blows up. `render_content` still receives a string; only `render_tag` sees the parsed form.
**Fix:** Type-guard the top of every `render_tag` implementation. Returning `$tag` unchanged lets Bricks' native parsed-tag handler take over:
```php
public static function render_tag( $tag, $post, $context = 'text' ) {
    if ( ! is_string( $tag ) ) {
        return $tag;   // Bricks 2.3.x parsed-tag array — leave for native handling
    }
    $bare = trim( $tag, '{}' );
    if ( ! isset( self::tag_map()[ $bare ] ) ) return $tag;
    return self::resolve( $bare, $post );
}
```
The `02` schema-library example carries this guard for exactly this reason.
**First seen:** TAB, 2026-05-28 — six author-module dynamic tags all faulted on first frontend request. The `is_string()` guard resolved it with no other change.

### A custom dynamic tag used in an element `_conditions` resolves via `render_content`, NOT `render_tag` — register BOTH
**Symptom / When:** A boolean gate tag works in a text element but a section's hide-when-empty `_conditions` (`compare: empty_not`) never fires — the section always renders. Older tags from the same class still work in text.
**Why:** Bricks evaluates conditions in `Conditions::check()` by resolving the `dynamic_data` value through `render_dynamic_data()`, which runs the **`render_content`** filter — not `render_tag`. A tag hooked only to `render_tag` returns unresolved. Worse, the usual perf prefix-guard in `render_content` (`if ( false === strpos( $content, '{tab_post_' ) ) return $content;`) silently drops any tag with a *different* prefix — it passes through as a **literal string**, and a literal is non-empty, so `empty_not` is always true and the gate stays open forever.
**Fix:** Register gate tags on **both** filters, and make the `render_content` guard cover **every** prefix the class's `tag_map()` exposes:
```php
if ( ! is_string( $content )
    || ( false === strpos( $content, '{tab_post_' ) && false === strpos( $content, '{tab_author_' ) ) ) {
    return $content;
}
```
Boolean gate tags should return `'1'` or `''` so `empty_not`/`empty` compare cleanly. Conditions resolve against `$instance->post_id` — on a content template for a single, that's the queried object.
**First seen:** TAB, 2026-06-10 — Bio: Single gated sections rendered for every author; the guard only passed `{tab_post_`. **Hit again independently:** TAB, 2026-06-25 — Location Single section gating never fired until `render_content` was added. Same gotcha, two builds, five months apart — hence its place here.

### Bricks `logoHeight` (and any number-unit control) silently rejects `clamp()`
**Symptom / When:** Setting the Logo element's `logoHeight` to `clamp(36px, 4vw, 48px)` saves, the builder shows the value, but no `height` rule renders. No console error, no validation warning.
**Why:** `logoHeight` is a `number-unit` control that parses `<number><unit>` and silently drops anything else. `clamp()`, `min()`, `max()`, `calc()` and `var()` all fail validation and emit nothing.
**Fix:** Write the rule in the child theme against Bricks' inner `<img>`, and leave the Bricks setting empty so it doesn't fight:
```css
.header__logo .bricks-site-logo { height: clamp(36px, 4vw, 48px); width: auto; }
```
Applies to any Bricks number-unit setting where you want a fluid expression.
**First seen:** TAB, 2026-04-25 — header logo responsive sizing.

### Bricks button/link with a dynamic-data href needs `link.type: "meta"` — `"external"` emits NO href at all
**Symptom / When:** A button / text-link / block-with-`tag:a` has `link = { "type": "external", "useDynamicData": "{some_tag}" }`. It renders as an `<a>` with **no `href` attribute**. No error. CTA buttons look fine and go nowhere; `tel:`/`mailto:` links are dead.
**Why:** Bricks' link control treats `type: "external"` as "use the literal `url` field" and ignores `useDynamicData`. Dynamic-data links are a different type (`meta`). The combination is internally inconsistent, so Bricks emits the element with no resolved href rather than erroring.
**Fix:** `link.type = "meta"` whenever the href comes from `useDynamicData`. `"external"` is for a literal `url` string only.
```php
'link' => [ 'type' => 'meta', 'useDynamicData' => '{acf_cta_url}' ]
```
The Bricks UI sets `meta` automatically when you pick Dynamic Data, so this only bites WP-CLI-authored links copied from a wrong reference. **When it bites, it bites in bulk** — audit every dynamic link on the site, not just the one you noticed.
**First seen:** TAB, 2026-05-28 — a hrefless CTA copied from another page's button; the scan found the same dead pattern on four more CTAs and their `tel:` links, all silently hrefless.

### Native `{post_url}` resolves to the queried object (not the loop item) in LINK contexts inside a custom-query loop
**Symptom / When:** A loop driven by a **custom query type** renders cards correctly — `{post_title}`, `{post_date}`, `{post_terms_*}` all resolve per item — but the card's *link* (`link.useDynamicData: "{post_url}"`) resolves to the current/queried post for every card. All hrefs identical, often with a stray `aria-current="page"`. Moving the link deeper doesn't help.
**Why:** Bricks resolves **text** tags through its loop object (`\Bricks\Query::get_loop_object()`), which IS set during iteration of a custom array result. But the native `{post_url}` provider used for **link** resolution reads the global `$post` / queried object, which Bricks does not `setup_postdata()` for custom-query array results. Native (`objectType: post`) loops don't hit this because Bricks sets the global post for them.
**Fix:** Add a loop-aware URL tag in the core plugin and use it for the link:
```php
if ( class_exists( '\Bricks\Query' ) ) {
    $obj = \Bricks\Query::get_loop_object();
    if ( $obj instanceof WP_Post ) return $obj->ID;   // per-item
}
// fallbacks: $post / numeric / get_queried_object_id()
// then: case 'my_post_url': return get_permalink( $post_id );
```
Because the tag reads the loop object explicitly — the same mechanism that makes text per-item — it resolves correctly per card.
**First seen:** TAB, 2026-05-28 — related-posts cards; titles were per-item but all three links pointed at the current post.

### Bricks custom query returning bare IDs (`'fields'=>'ids'`) does NOT establish per-item post context
**Symptom / When:** A custom query loop renders the right number of cards, but per-item `_conditions` never switch and native dynamic tags (`{post_url}`, `{acf_*}`) all resolve to the **queried post**. Critically this only *looks* wrong when the template's own queried object is the **same post type** as the loop items (a related-projects loop on a Single Project) — because then "wrong" reads as plausible. The identical pattern on a different-type template works, which masks the cause.
**Why:** When `bricks/query/run` returns **WP_Post objects**, Bricks runs `setup_postdata()` per iteration, so the global `$post` IS the loop item and both `_conditions` ACF resolution and native tags pick it up. When the callback returns **bare integer IDs**, Bricks does not set up post context — everything relying on the global post falls back to the main queried post. (A *loop-aware custom tag* reading `get_loop_object()` still works with IDs — which is why an attachment-ID gallery loop is fine — but nothing else does.)
**Fix:** For post-card loops, return **WP_Post objects**; drop `'fields' => 'ids'`. Reserve bare-ID returns for loops consumed *only* by `get_loop_object()`-aware custom tags.
**First seen:** TAB, 2026-06-08 — related-projects card routing; the enabled/disabled overlay condition never flipped and the `<a>` pointed at the page's own project. The same overlay on a Single Service (objects) worked.

### Bricks custom-query `posts_per_page` arrives at `settings['query']['posts_per_page']`, not `settings['posts_per_page']`
**Symptom / When:** You set the loop element's Query → posts-per-page, but your `bricks/query/run` handler ignores it and uses its own default count.
**Why:** Bricks nests the query controls under the element's `settings['query']` array. A handler reading the flat top-level key finds nothing and silently falls back.
**Fix:**
```php
$ppp = $s['query']['posts_per_page'] ?? ( $s['posts_per_page'] ?? null );
```
**First seen:** TAB, 2026-06-25 — a reviews loop capped at the handler default (4) instead of the loop's 3. Latent for weeks because the default coincidentally matched.

### Bricks archive template conditions use `archivePostTypes` / `archiveTerms` — NOT `postType` / `taxonomy`
**Symptom / When:** An `archive` template conditioned `archiveType:[postType]` with a `postType:[x]` key renders on **every** CPT archive, not just `x`. Two such templates → the lower post-ID one hijacks the other's archive.
**Why:** `Bricks\Templates::render_data()` reads `$condition['archivePostTypes']` for the postType branch and `$condition['archiveTerms']` (format `taxonomy::termid` or `taxonomy::all`) for the term branch. A `postType` / `taxonomy` key is silently ignored → `empty($condition['archivePostTypes'])` is true → the condition matches ALL post-type archives.
**Fix:** Use the real keys. One template can hold both conditions to cover `/things/` + `/things/category/{term}/`:
```php
[ 'main'=>'archiveType', 'archiveType'=>['postType'], 'archivePostTypes'=>['project'] ]
[ 'main'=>'archiveType', 'archiveType'=>['term'],     'archiveTerms'=>['project_category::all'] ]
```
**Related:** a real CPT archive (`has_archive: true`) matches `archiveType: postType` directly — unlike the built-in `post` type, which needs the `page_for_posts` workaround (see the AHML entry above). Creating the template may need a `wp rewrite flush` to register the archive route.
**First seen:** TAB, 2026-05-31 — a Projects Archive built from the Services Archive inherited the broken `postType` key; both then matched every CPT archive. Fixed retroactively on both.

### Bricks scores competing header/footer/template conditions — a specific post-ID condition (8) beats `main: any`
**Symptom / When:** A second header template conditioned to one page (`main: ids`) doesn't appear to take over from the site-wide default (`main: any`) — the page renders an empty `<header id="brx-header">` shell, or the wrong header.
**Why:** Two things. (1) Bricks **scores** matching templates (`templates.php` ~L895-927): a specific post ID scores **8** and beats "any" — so the specific header *does* win; you don't need to exclude the default. (2) But `Database::set_active_templates()` caches its result and Bricks caches per-post header CSS, so a page rendered *before* the condition was written shows a stale (often empty) header until a page-settings write + CSS regen forces re-resolution.
**Fix:** Assign via `_bricks_template_settings` → `templateConditions` (`main: ids`), then regen CSS and re-render. Verify programmatically:
```php
\Bricks\Database::set_active_templates( $page_id );
echo \Bricks\Database::$active_templates['header'];  // should print the specific template ID
```
**First seen:** TAB, 2026-05-30 — a per-page Simple header rendered as an empty shell; resolved after the page-settings write + regen.

### Bricks per-page footer/header disable key is `footerDisabled` / `headerDisabled`
**Symptom / When:** Writing `_bricks_page_settings` with `templateFooterDisabled` (or `disableFooter`) to suppress the footer on one page has no effect.
**Why:** `Database::is_template_disabled( $type )` builds the key as `"{$type}Disabled"`. The only keys honored are `headerDisabled` and `footerDisabled`. Any other key name is silently ignored.
**Fix:**
```php
update_post_meta( $page_id, '_bricks_page_settings', [ 'footerDisabled' => true ] );
```
Then regen CSS. Confirm by render — the `<footer>` markup should be absent.
**First seen:** TAB, 2026-05-30 — footer kept rendering under a guessed `templateFooterDisabled` key.

### Writing Bricks content to a page in `wordpress` editor mode renders nothing — flip `_bricks_editor_mode`
**Symptom / When:** You write an element tree to `_bricks_page_content_2` on a freshly-created WP page; the front end renders the default WP template, no Bricks.
**Why:** Bricks only renders `_bricks_page_content_2` when the page is in Bricks editor mode. A page created in WP admin defaults to `wordpress`; writing Bricks content alone doesn't switch it.
**Fix:** Set `_bricks_editor_mode = bricks` alongside the content write (+ `wp_set_current_user(1)` + CSS regen). Verify by curl: the body should carry `brxe-*` elements.
**First seen:** TAB, 2026-05-31 — Thank-you page created in WP admin; setting the mode + writing the 59-element tree rendered it.

### Bricks element tree written via WP-CLI needs populated `children` arrays — `parent` alone renders empty shells
**Symptom / When:** A programmatically-built `_bricks_page_content_2` renders all TOP-LEVEL sections but each is an empty shell — no headings, grids or cards. Element count is correct; nesting via `parent` is correct.
**Why:** Bricks' frontend renderer walks the `children` array on each element, not `parent`. If every element ships `'children'=>[]`, only depth-0 elements render; their descendants are never emitted.
**Fix:** After building the flat element list, derive `children` from `parent` before writing — group ids by parent in document order, then set each element's `children`. Builder-saved trees always have populated `children` (`children:a:3`), which is the golden-rule tell.
**First seen:** TAB, 2026-05-31 — 42 elements wrote; 3 sections rendered as empty shells until children were rebuilt.

### Bricks `_cssGlobalClasses` must reference class IDs, not names — name refs persist but emit no class attribute
**Symptom / When:** A built tree renders every element with correct nesting and working loops, but **unstyled** — the global-class names never appear in `class="…"`, so no CSS hooks and no layout. Readback of the meta looks fine (the names are right there).
**Why:** `_cssGlobalClasses` is an array of class **IDs** (the 6-char `id` of each `bricks_global_classes` entry, e.g. `svhsec`), not names (`service-hero`). Bricks resolves each ref against the registry by ID at render; an unknown ID is silently skipped.
**Fix:** Reference IDs; look up first:
```php
$byname = array_column( get_option('bricks_global_classes'), 'id', 'name' );
$id = $byname['service-hero'];   // 'svhsec'
```
**The trap that hides it:** when a class's name and id happen to be identical, referencing by name works — masking the bug until you hit one where name ≠ id.
**First seen:** TAB, 2026-05-31 — Single Project built referencing classes by name; all 7 sections rendered with 0 class hits until a name→id repair pass. A sibling template was unaffected because it referenced IDs directly.

### Bricks image element with a dynamic source needs the tag to return an ARRAY `[$id]` in image context
**Symptom / When:** An image element whose `image.useDynamicData` points at a *custom* dynamic tag renders nothing — or renders attachment ID 1 / a wrong image. The tag resolves fine in text contexts.
**Why:** `image.php::get_normalized_image_settings()` reads `$images[0]`: `if ( is_numeric( $images[0] ) ) $image['id'] = $images[0]`. Native image tags return an **array** `[ $id ]`. A custom tag returning the bare string `"15585"` makes `$images[0]` the **first character** `"1"` → attachment 1, or nothing.
**Fix:** Make custom image-source tags context-aware. Return `[]` (not `''`) on the empty path in image context:
```php
return $context === 'image' ? [ (int) $id ] : (string) $id;
```
**First seen:** TAB, 2026-06-08 — a gallery loop rendered empty `<li>`s until the tag returned `[$attachment_id]` for image context.

### Bricks image element: the stored `id`/`url` is a BUILDER PREVIEW, not a frontend fallback
**Symptom / When:** An image element bound to a dynamic source that *also* still carries a static `id`+`url` (the image you picked in the builder) renders **nothing** when the dynamic source is empty for that post. You expected the picked image as a fallback; it shows in the builder canvas, which misleads.
**Why:** When `useDynamicData` is set, Bricks resolves from the dynamic value at render and ignores the stored `id`/`url` — those are canvas preview only. Empty dynamic value → no image markup at all.
**Fix:** Don't treat the stored id as a fallback. Either keep the dynamic source always populated, or use a tag with its own fallback / a conditional second element gated on the source being empty. **Often this is the behavior you want:** an imageless section is usually a better "no photo yet" state than the wrong placeholder photo on every item.
**First seen:** TAB, 2026-06-09 — hero images rewired to `{featured_image}` with an old placeholder still stored on the element; posts without a featured image render no hero `<img>`, confirming preview-only.

### Bricks DOES resolve dynamic data inside custom `_attributes` — and a loop-aware tag resolves per-item there (use a delimiter, not JSON)
**Symptom / When:** You need a per-loop-item value on a `data-*` / `aria-*` attribute and don't know whether Bricks resolves a `{tag}` in an attribute value, or whether it's per-item in a loop.
**Why / what's true:** `base.php::get_custom_attributes()` runs every attribute value through `bricks_render_dynamic_data()` — so tags **do** resolve in attribute values, including compound strings (`"View the {post_title}"`). A **loop-aware** custom tag resolves **per-item** there regardless of the `$this->post_id` passed, because it reads the loop object directly. **Caveat:** the value is emitted raw into `key="value"`, so a value containing `"` (e.g. JSON) **breaks the attribute**.
**Fix:** Use a quote-free delimiter-joined string for list payloads and split in JS — `data-gallery="{my_gallery_urls}"` where the tag joins on `|~|`, then `attr.split('|~|')`.
**First seen:** TAB, 2026-06-08 — archive cards carrying their gallery URLs for a shared lightbox whose images aren't in the DOM.

### Bricks `_conditions` on an ACF `true_false` read the RAW value — `empty` / `empty_not` work
**Symptom / When:** You gate two conditional variants (an `<a>` vs a `<button>` per loop item) on an ACF boolean. The text render `{acf_<bool>}` shows **"True" / "False"** — both non-empty — which suggests `empty`/`empty_not` can't distinguish them, tempting a custom `1`/`""` tag or a fragile `== True` compare.
**Why:** The condition engine resolves `dynamic_data: {acf_<bool>}` to the field's **raw** value — `"1"` for true, **`""` for false** — NOT the formatted string `render_content` emits for text.
**Fix:** Gate directly — enabled variant `compare:'empty_not'`, disabled variant `compare:'empty'`. No custom tag needed. **The text render is a red herring; verify on a real toggled item.**
**First seen:** TAB, 2026-06-08 — per-item card routing; nearly added a custom tag before confirming empty/empty_not already switch correctly.

### ACF `true_false` rendered into a Bricks custom `_attributes` value outputs `"True"` / `"False"`, not `"1"` / `"0"`
**Symptom / When:** A loop card sets `data-x = {acf_<bool>}` to drive an attribute-selector style (`[data-x="1"]{…}`). The style never applies, even where the field is on.
**Why:** Bricks renders the tag's **display value** into the attribute — for ACF true_false that's `"True"`/`"False"` (capitalized). **Contrast the entry above:** `_conditions` read the RAW value, attributes get the DISPLAY value. Same field, same tag, two different values depending on context.
**Fix:** Match the rendered text (`[data-x="True"]`), or emit a deterministic value via a custom tag returning `"1"`/`""`. Always confirm the live value first:
```bash
curl -s <url> | grep -oE 'data-x="[^"]*"'
```
**First seen:** TAB, 2026-06-24 — a `[data-featured="1"]` accent never applied; the live attribute was `"True"`.

### Markup generated by a custom dynamic tag CANNOT be styled by Bricks global classes — its CSS must live in the child theme
**Symptom / When:** A custom tag returns an HTML string with class names; you register matching global classes with typed settings; the markup renders completely unstyled.
**Why:** Bricks only emits a global class's CSS on a page when an actual **element** in that page's tree references the class via `_cssGlobalClasses` (see the emission entry above). Classes that exist only inside a tag's returned string are never seen as used, so no CSS is emitted — the global class is dead weight.
**Fix:** Put CSS for tag-generated markup in the **child theme** (always loaded), and delete the would-be global classes. This is the correct styling-layer home for code-generated components anyway (`01`). Specificity tricks don't help — the rule simply isn't emitted.
**First seen:** TAB, 2026-06-25 — a composed-HTML directory tag; 3 global classes emitted nothing → moved to the child theme.

### Editing `bricks_global_classes` via WP-CLI while a Bricks builder tab is open gets silently reverted
**Symptom / When:** You write Global Class settings via `wp eval`, verify the **raw DB + rendered CSS** both show the change, then minutes later the option has reverted — sometimes **partially** (one class sticks, others revert), which reads like a race. `update_option` returned `true`; the immediate re-read was correct; the revert happens later with **no CLI write in between**.
**Why:** An open builder tab holds the entire Global Classes collection in browser memory. On heartbeat autosave (or any manual save) it writes the **whole** option back from that stale copy, clobbering out-of-band CLI edits. Advanced Themer's class manager makes the builder especially write-happy. The reverse also bites: a CLI write can clobber unsaved builder work.
**Fix:** Treat `bricks_global_classes` as **single-writer**. Close every builder tab for the site before editing via CLI; after writing, re-read after **~90s** (one heartbeat window) to confirm it stuck — don't trust the immediate read. If coordination isn't possible, make the change in the builder UI instead. Per-post `_bricks_page_content_2` isn't affected the same way *unless that page is open in the builder*.
**PHP-side trap in the same area:** mutating the classes array through nested references (`foreach($gc as &$c){ $s =& $c['settings']; … }`) can leave the saved value unchanged. Index by position instead: `$gc[$i]['settings'][...] = …`.
**First seen:** TAB, 2026-06-22 — typed-setting edits on four shared classes verified live in raw DB + render, then reverted (3 of 4) within ~2 min while a builder tab was open. Held permanently once the builder was closed.

### Deleting a global class in the Bricks Style Manager leaves DANGLING refs in element `_cssGlobalClasses`
**Symptom / When:** After pruning classes, elements still list the deleted id (e.g. `["aheyb1","mhawiz"]` where `aheyb1` no longer exists). Renders nothing, but clutters the tree and shows up as phantom "co-classes" in usage audits.
**Why:** The Style Manager removes the class from the option but does NOT scrub references from every element across every post. The id just becomes a no-op.
**Fix:** When auditing/consolidating, scrub dead refs — build the set of valid class ids, then for each live (non-revision) `_bricks_page_*_2`, drop any `_cssGlobalClasses` entry not in the set. Iterate posts and `update_metadata_by_mid()` per dirty row, under `--skip-themes` + `wp_set_current_user(1)`. A *deleted* id is absent from the option entirely — distinguish from intentional empty-settings hooks, which are still present.
**First seen:** TAB, 2026-06-25 — class-consolidation sweep; elements carried dead ids after Style Manager deletes.

### Bricks ships a default `blockquote` style (4px left border + Georgia) that hits any `customTag: "blockquote"`
**Symptom / When:** A testimonial built as a text element with `tag:"custom"`, `customTag:"blockquote"` renders with an unwanted 4px solid left border (and a Georgia serif face at 1.3em if not otherwise overridden), though its own class sets none of that.
**Why:** `frontend-light-layer.min.css` includes a bare-element rule: `blockquote{border-left-style:solid;border-left-width:4px;font-family:georgia,…;font-size:1.3em;margin:15px 0;padding:0 0 0 30px}`. ACSS adds nothing here — it's purely the Bricks framework default. A class setting font/margin/padding but not `border` leaves the border intact.
**Fix:** Typed `_border` reset on the class (typed-settings-first, no `_cssCustom` needed):
```php
'_border' => [ 'width' => ['top'=>'0','right'=>'0','bottom'=>'0','left'=>'0'], 'style' => 'solid' ]
```
A single class (0,1,0) beats bare `blockquote` (0,0,1), and an unlayered inline Global Class beats `@layer bricks` — so no doubled-class trick needed here (unlike the equal-specificity `@layer` cases above). Zero the 30px `padding-left` too if you don't want the indent.
**First seen:** TAB, 2026-05-30 — testimonial blockquotes rendered with a stray left border.

### `display:flex` breaks an inline comma-separated `{post_terms_*}` list — it eats the space after the comma
**Symptom / When:** A term list rendered via `{post_terms_<tax>}` (which outputs `<a>…</a>, <a>…</a>` with real `, ` separators) is put in a `display:flex; gap:…` container to look like chips. The commas render with **no space after them** and the stray `, ` text-nodes float as their own flex items.
**Why:** Flex turns **every** child — including the `, ` text-nodes between anchors — into an anonymous flex item, and collapses their leading/trailing whitespace. The space that is literally in the markup disappears. Flex is the wrong layout for an inline comma-joined string; it only suits true chips with no separators.
**Fix:** Use `display:block` (or inline) so the `, ` flows naturally and wraps between items. Keep multi-word names whole with `white-space:nowrap` on the links (no typed control — legit `_cssCustom`); the list still wraps *between* terms. Only reach for flex+gap if you also strip the separator to render genuine chips.
**First seen:** TAB, 2026-06-09 — a Categories row rendered "Decks & Porches,Landscaping".

### Bricks auto-adds `aria-current="page"` to a link whose href EXACTLY matches the current URL — drive active states from it
**Symptom / When:** You need an active/current state on a nav or filter set. Instinct is a hardcoded active class (wrong on every other page), per-link `_conditions` (they hide/show — they can't toggle a class), or JS (flash + extra code).
**Why:** Bricks already emits `aria-current="page"` on any link whose resolved href equals the current request URL — **exact match only, NOT ancestors** (verified: on `/projects/category/decks-porches/` the Decks chip gets it; the `/projects/` "All" chip does not). The current item is already flagged in server-rendered markup.
**Fix:** Style from the attribute — `.chip[aria-current="page"]{ …active… }` (`aria-current` has no typed control, so this is legit `_cssCustom` on the base class). Remove any hardcoded active class and any static `aria-current`. Server-rendered (no flash), DRY (one rule covers every link including a "show all" root link, which lights up only when that root is current), and the a11y signal is correct for free.
**First seen:** TAB, 2026-06-09 — filter chips; an "All work" chip hardcoded active showed active on category pages too.

### Bricks form `fromName` / `fromEmail` / `emailTo` / `emailSubject` are read ONLY by the Email action
**Symptom / When:** A Bricks form (e.g. on a custom login / lost-password / reset-password page) carries leftover placeholder email settings — a bogus `fromName`, a stale subject. It looks like the user-facing auth emails will send with that bogus From name.
**Why:** `fromName` and friends are read in exactly one place — `includes/integrations/form/actions/email.php`, the **Send Email** action. The `login` / `lost-password` / `reset-password` actions trigger **WordPress core mail** (`retrieve_password()` etc.), whose From name/address come from WP core / `wp_mail_from_name` / an SMTP plugin / Bricks' own `userActivationLinkEmailFromName` — never the form field. If the form's `actions` array has no `email`, those fields are inert dead config.
**Fix:** Confirm the form's `actions` first; if there's no `email` action, the field changes nothing. To actually brand auth emails, set Bricks' `userActivationLinkEmailFromName`, filter `wp_mail_from_name`, or use an SMTP plugin. Scrub the leftover only for a clean audit — zero behavior change.
**First seen:** TAB, 2026-06-11 — a blueprint's leftover `fromName` on three auth forms; confirmed inert in Bricks source rather than chased.

### Bricks — seed utility global-class "anchors" from a plugin so child-theme classes show in the picker
**Symptom / When:** A child-theme utility class (`.display`, `.eyebrow`) works on the front end but doesn't appear in the Bricks class picker, so it can't be assigned to an element in the builder.
**Why:** The picker lists entries in the `bricks_global_classes` option, not whatever exists in CSS. The class needs an entry there; the CSS can stay in the theme.
**Fix:** Seed empty "anchor" entries idempotently from the functionality plugin (on `admin_init`, hash-guarded so it writes once). Per the verified Global Class shape: `settings` MUST be `array()`; IDs 6-char alphanumeric, never all-numeric. `bricks_global_classes` writes are **not** cap-gated (no `wp_set_current_user` needed — unlike the `_bricks_page_*_2` keys). Anchors carry empty settings → they emit no CSS; the theme `style.css` supplies the actual rules. Keeps the plugin (anchors, version-controlled) / theme (CSS) split clean.
**First seen:** VMG, 2026-06-05 — a `bricks-global-classes.php` seeder for `.display`/`.eyebrow` and siblings.

### Bricks — flex/gap control KEYS depend on the ELEMENT TYPE: `_direction`/`_columnGap`/`_rowGap` on layout elements, `_flexDirection`/`_gap` on everything else
**Symptom / When:** You write typed flex/gap settings and some silently emit nothing — no error, readback confirms the key persisted, but the rendered CSS has no `gap` / `flex-direction`. Setting `_display: flex` doesn't help. Reads exactly like "Bricks drops certain control types on CLI writes" — it isn't.
**Why:** Bricks registers **two different control sets for the same CSS properties**, split by element type. `elements/base.php` wraps its `_flexDirection` (L918) and `_gap` (L991) definitions in `if ( ! $this->is_layout_element() ) {`. `is_layout_element()` (base.php L4559) returns true for **`section`, `container`, `block`, `div`** — filterable via `bricks/is_layout_element`. Layout elements instead get `elements/container.php`'s `_direction` (L359), `_columnGap` (L412), `_rowGap` (L424). So the key for `flex-direction` is `_direction` on a block and `_flexDirection` on a heading. The wrong key for that element type is just an unknown key: it persists in the DB and never emits — the ordinary silent-strip failure wearing a disguise. **This is the golden rule one layer deeper: the schema depends on the element, not only on Bricks.**
**Fix:** Pick the key set by element type.

| Property | `section` / `container` / `block` / `div` | every other element |
|---|---|---|
| `flex-direction` | `_direction` | `_flexDirection` |
| gap | `_columnGap` / `_rowGap` | `_gap` |

`_width`, `_widthMax`, `_heightMin`, `_padding`, `_margin`, `_typography`, `_border`, `_background`, `_display`, `_alignItems`, `_justifyContent` are element-type agnostic and emit everywhere. When a typed setting doesn't emit, check that element's own control registration before blaming the write path:
```bash
grep -rn "'_yourKey'" wp-content/themes/bricks/includes/elements/
```
**There is NO write-path difference.** A global class written via the `bricks_global_classes` option and an element tree written into `_bricks_page_content_2` emit **byte-identical** declarations from the same settings matrix. They land in different *homes* — global-class CSS inline, element CSS in `post-{id}.min.css` (see "where CSS actually lives") — but the emitter behaves the same. `02`'s verified schema library is correct on both paths.
**First seen:** VMG, 2026-06-06 — a Contact page typed conversion where gap / flex-direction / max-width "dropped silently"; diagnosed at the time as a CLI-write / control-type limitation, and recorded that way at the 2026-07-15 harvest with an explicit ⚠️ untested-conflict flag against `02`. · **Cause found and the entry rewritten:** MMHN, 2026-07-15 — probe on Bricks 2.3.9, both write paths, one page: a `block` emitted `column-gap:44px; row-gap:55px; flex-direction:column` from `_columnGap`/`_rowGap`/`_direction` and **nothing** from `_gap`/`_flexDirection`; a `heading` emitted `gap:66px; flex-direction:row` from `_gap`/`_flexDirection` and **nothing** from `_columnGap`/`_direction`. A perfect mirror. `_width: 111px`, `_widthMax: 222px`, `_heightMin: 333px` all emitted from CLI on both paths, refuting the "number+units doesn't emit" theory (`_gap` is also number+units and works — on the right element). VMG's original incident was a conversion of **layout-element** classes using **non-layout** key names, which explains gap and flex-direction exactly. The residual "max-width dropped" is unexplained but was most likely `_maxWidth`, the pre-migration key (see the silent-strip startup audit in `01`).

### Bricks — an empty `text-basic` renders nothing; use `block`/`div` for decorative empties
**Symptom / When:** A `text-basic` with `text:''` (a CSS-only dot, accent bar, counter holder) produces no DOM output at all.
**Why:** `text-basic` skips render on empty text; `block`/`div` render their wrapper regardless.
**Fix:** Decorative empty element → `block` with `tag:'custom'` + `customTag:'span'` (or a div), never `text-basic`.
**First seen:** VMG, 2026-06-06 — a Contact status dot vanished as a `text-basic`.

### Bricks — the `html` element is the ungated raw-markup injector; reserve it for genuinely non-native markup
**Symptom / When:** You need to inject static HTML (a form stub, an embed) — and/or you're tempted to ship a whole footer/section as one `html` element with all styling in the child theme.
**Why:** The `html` element (`settings.html`) just `echo`s markup with no capability gate (the `code` element gates execution). That makes it the tool for raw markup — but a whole section built this way is invisible and uneditable in the Bricks UI (no element tree, no typed panels, no global classes), which is the exact handoff failure the pipeline exists to prevent. Separately, classes referenced **only** inside a raw-HTML string are never collected as "in use", so Bricks emits no CSS for them (see the global-class-emit entry).
**Fix:** Use `html` for true raw-markup needs only. For CSS targeting raw-HTML-only classes, bundle those rules into the `_cssCustom` of a class that IS on a real element (the wrapping card/panel) so they emit. For a whole section, build the native `SECTION > CONTAINER > BEM` element tree instead — and note the golden rule's discovery source does **not** require a fresh builder session: any existing builder-made template on the site is a verified example. Read one back (`wp post meta get <header_id> _bricks_page_header_2 --format=json`, plus the `bricks_global_classes` those elements reference) and replicate the shapes.
**First seen:** VMG, 2026-06-06 — a Contact form stub, where field CSS had to ride on `.contact__form-panel`. · VMG, 2026-06-07 — a footer first shipped as one `html` blob + `style.css`; rebuilt as a 44-element native tree (typed settings on 24 global classes, logo lockup replicated element-for-element from the header, Legal column converted to a CPT query loop).

### Bricks — flex/grid + gap that arranges BEM children belongs on the Container, not the single-child Section
**Symptom / When:** You put `display:flex; flex-direction:column; gap` (or grid+gap) on the **Section** to stack its content, but the gap has no effect and the children sit flush.
**Why:** In `SECTION > CONTAINER > BEM`, the Section's only child is the Container. `gap` spaces an element's *direct children* — gap on the Section spaces `[the Container]`, one item, so no effect. The intro/grid/status that need spacing are children of the **Container**, so the flex/grid + gap must live there. (`justify-content`/`align-items` for *centering* the single Container, by contrast, do belong on the Section.)
**Fix:** Put the content-stacking layout (flex/grid + gap) on the Container — give it a BEM class to hold it. Keep the Section as the full-bleed stage (background, min-height). Cleanest: move the whole stage (min-height + flex column + gap + centering) onto the Container and leave the Section a thin background wrapper.
**First seen:** VMG, 2026-06-06 — a split landing section; `gap` on the section class was orphaned until the layout moved to the container class.

### Bricks — header/footer TEMPLATE content lives in `_bricks_page_header_2` / `_bricks_page_footer_2`, not `_bricks_page_content_2`
**Symptom / When:** You write a built element tree to a header or footer template (`bricks_template` with `_bricks_template_type` = header/footer) via `_bricks_page_content_2`; readback confirms the elements and regen succeeds — but the template renders NOTHING on the front end (the `<header>`/`<footer>` landmark is absent or empty).
**Why:** Bricks keys template content by template TYPE. A page/single template uses `_bricks_page_content_2`; a header uses `_bricks_page_header_2`; a footer uses `_bricks_page_footer_2`. Writing the tree to `_content_2` on a footer template stores valid data on a key the footer renderer never reads — a silent no-RENDER. Distinct from the cap-gated silent no-WRITE (`update_post_meta` entry): here the write lands, just on the wrong key.
**Fix:** Match the key to the template type. Confirm with `wp post meta get <id> _bricks_template_type` first, then write to the matching `_bricks_page_{content|header|footer}_2`. Cross-check by reading back an existing working template of the same type — its content key tells you which one the renderer reads.
**First seen:** VMG, 2026-06-07 — a footer template rendered empty until the tree moved from `_bricks_page_content_2` to `_bricks_page_footer_2`.

### Bricks has built-in sanitized SVG upload — don't add SVG handling to the core plugin; and `get_allowed_mime_types()` reads false under WP-CLI
**Symptom / When:** Deciding whether the core plugin needs SVG upload support. A `wp eval` check — `in_array('image/svg+xml', get_allowed_mime_types())` — returns false, suggesting WP blocks SVG and you need to add `upload_mimes` + a sanitizer. Meanwhile SVGs are already uploading fine in the admin.
**Why:** Two things. (1) Bricks ships SVG upload (`themes/bricks/includes/svg.php`): hooks `upload_mimes` to enable SVG, `wp_handle_upload_prefilter` → sanitizes with `darylldoyle/svg-sanitizer` (the "enshrined" library) on by default, and gates on `Bricks\Capabilities::current_user_can_upload_svg()` (admins yes; per-role assignable under Bricks → Settings → Builder Access). (2) That `upload_mimes` filter only adds SVG when the capability check passes — and **WP-CLI runs as user 0**, so the cap is false and the filter no-ops. The mime/cap state under bare `wp eval` is therefore a false negative.
**Fix:** Don't add SVG mime/sanitization to the core plugin — it would duplicate Bricks' filters (double `upload_mimes`, double sanitize). Rely on Bricks; just assign the SVG-upload capability to the roles that need it. When checking cap/mime state from CLI, `wp_set_current_user(1)` first or the check lies:
```php
wp eval 'wp_set_current_user(1); var_export( array_key_exists("svg", get_allowed_mime_types()) );'  // true
```
**First seen:** Highland, 2026-06-14 — concluded "WP blocks SVG, add a sanitizer to highland-core"; the block was a user-0 CLI artifact and Bricks already handles SVG safely. (General rule: verify any capability/mime/`current_user_can` check from CLI with the current user set.)
**Also bites `wp media import`:** importing an SVG from the CLI fails with `Sorry, you are not allowed to upload this file type` — same root cause (user-0 → SVG mime not enabled). Fix: pass `--user=1` on the command, e.g. `wp media import logo.svg --title="…" --user=1 --porcelain`. First seen Highland, 2026-07-01 (about-page CTA logo import).


### Test custom Bricks dynamic tags with `bricks_render_dynamic_data()`, NOT manual `apply_filters()`
**Symptom / When:** Verifying a custom dynamic tag (registered via `bricks/dynamic_tags_list` + `render_tag` + `render_content`). Calling `apply_filters('bricks/dynamic_data/render_tag', '{my_tag}', ...)` by hand returns the value wrapped in extra braces (`{resolved}`), and an unknown tag comes back double-braced (`{{x}}`) — looks broken.
**Why:** Bricks' own `Bricks\Integrations\Dynamic_Data\Providers::get_tag_value` (and `::render`) are hooked at the **same priority 10** as your callback. Your plugin's filter registers first (plugins load before the theme), so Bricks' provider runs *after* yours on the already-resolved string and re-wraps it. That chain isn't how Bricks resolves tags at runtime, so the manual test is misleading.
**Fix:** Test through Bricks' canonical API, which mirrors real rendering:
```php
wp eval 'echo bricks_render_dynamic_data("{highland_phone_link}");'            // tel:3303383577
wp eval 'echo bricks_render_dynamic_data("Call {highland_phone} now", get_the_ID(), "text");'
```
Standalone tags, embedded tags, link context, and unknown-tag passthrough all render correctly this way. (Building the three filters off one shared map array — per `02` — keeps add-a-tag to a one-row change.)
**First seen:** Highland, 2026-06-14 — registering `{highland_phone}` / `{highland_phone_link}`; manual `apply_filters` showed `{tel:...}` and `{{unknown}}`, but `bricks_render_dynamic_data()` confirmed clean output.


### A WP-CLI write to `_bricks_page_content_2` can silently not persist — verify by DB read-back; and global-class CSS is inline, not in `post-*.min.css`
**Symptom / When:** Building page content section-by-section via `update_post_meta('_bricks_page_content_2', …)` after `wp_set_current_user(1)`. A build script reported its in-memory element count (155) and "success", but the new section was absent from the rendered page; a fresh `get_post_meta` showed the OLD count (142) — the write never landed. Re-running the identical script persisted it. Separately, grepping `post-{ID}.min.css` for the new global classes finds nothing (file is 0 bytes).
**Why:** Two independent things. (1) The non-persist couldn't be pinned to a definitive cause — `update_post_meta` returned without the value landing once, then worked identically on re-run (a transient cache/timing artifact; the gated-capability requirement from the established `update_post_meta`-silently-fails entry was already satisfied). (2) This install delivers global-class CSS **inline in `<head>`**, not via per-post files — so `post-{ID}.min.css` is legitimately 0 bytes for a page built entirely from global classes; that file is the wrong place to verify.
**Fix:** Never trust a build script's own echoed count. After every gated-meta write, re-read from the DB in a SEPARATE `wp eval` (fresh process) and assert the count/ids — if short, re-run. Verify emitted CSS by grepping the RENDERED page HTML (`curl`), not the post CSS file.
**First seen:** Highland, 2026-06-16 — homepage hero section's first write was lost; caught because the section didn't render, re-ran, confirmed 155 in DB.


### Advanced Themer "Remove style controls" tweak silently kills ALL typed-setting CSS site-wide — only `_cssCustom` survives
**Symptom / When:** Global-class (and element) CSS built from **typed** Bricks settings (`_background`, `_padding`, `_typography`, `_border`, `_gridTemplateColumns`, …) stops rendering everywhere — builder canvas AND front-end. Raw `_cssCustom` classes still render. No PHP error, no fatal, nothing in the log. Data is fully intact (`bricks_global_classes` settings all present and valid). Toggling AT off restores everything; AT on re-breaks it. Looks at first like an AT *version* regression — it is **not** (reproduces on every AT version; a plugin rollback + browser hard-refresh does nothing because the trigger is a DB setting, not the binary).
**Why:** AT's builder tweak **"Remove style controls"** (`AT__Builder::disable_style_controls`, gated by `remove-style-controls` being present in the `bricks-advanced-themer__brxc_builder_default_custom_settings` array). It hooks `bricks/elements/{element}/controls` at priority 999 and **unsets every control that has a `css` property** (keeping a small excluded set: `_cssCustom`, `_cssClasses`, `_cssId`, `_attributes`, …). The footgun: that filter is registered on `init`, so it runs on the **front-end**, not just the builder UI — and Bricks' CSS generator builds CSS from typed settings *by walking those same control definitions' `css` property*. Strip the controls → Bricks has no setting→CSS mapping → the entire typed pass emits nothing. `_cssCustom` is raw and excluded, so it's the only thing left. The feature's intent is a strict utility/class-only authoring workflow (hide native style controls so designers must use global classes) — fundamentally at odds with our **typed-first** methodology.
**Fix:** Remove `remove-style-controls` from the setting (keeps AT 100% functional otherwise):
```php
wp eval '$o="bricks-advanced-themer__brxc_builder_default_custom_settings"; $v=get_option($o); $v=array_values(array_filter((array)$v,fn($x)=>$x!=="remove-style-controls")); update_option($o,$v);'
```
UI equivalent: Bricks → AT settings → Builder Tweaks → uncheck **"Remove style controls."** Then regenerate CSS (`\Bricks\Assets_Files::regenerate_css_files()` after `wp_set_current_user(1)`) and hard-refresh the builder to clear the iframe's cached assets. **Diagnostic tell:** if typed CSS is gone but `_cssCustom` rules survive, suspect a control-stripping filter (AT or otherwise) before anything else — bisect AT by clearing its settings rows (`bricks-advanced-themer__*`) with the plugin still active; if that restores rendering, it's a setting, not the code.
**First seen:** Highland, 2026-06-20 — Header (#38) + Footer (#50) and homepage typed CSS vanished site-wide after an AT update; user had rolled back 3.3.15→3.3.13 with no effect. Bisected to this one setting; removing `remove-style-controls` restored 106 global-class rules with AT active.


### Typed flex `_columnGap`/`_rowGap`/`_direction` silently don't emit when a global class is on a `text-basic` element — only on layout elements (div/block/container)
**Symptom / When:** A global class sets `_display: flex` + `_alignItems` + `_columnGap` (string value) and the flex + alignment emit, but the **gap (and `_direction`) never appear in the rendered CSS**. The same class shape emits the gap correctly elsewhere. Looks like a per-class emit bug or a caching artifact (it is neither — reproduces in inline AND file mode, with only one class entry, correctly referenced).
**Why:** Bricks resolves a global class's typed setting→CSS mapping against the **controls of the element type the class is applied to**. The `text-basic` (text) element does NOT register the flex layout sub-controls (`_direction`, `_columnGap`, `_rowGap`, `_flexWrap`), so those settings have no `css` mapping to emit through and are dropped. The controls a text element DOES have — `_display`, `_alignItems`, `_margin`, `_typography` — still emit, which is what makes it look selective. A `div`/`block`/`container` element carries the full layout group, so the identical class shape emits the gap there. **Confirmed:** `.eyebrow` (on `text-basic<p>`) dropped `_columnGap`; `.footer__social` (identical shape, on a `div`) emitted it.
**Fix:** Three options when a flex **text** element needs a gap between flex items (e.g. an eyebrow `<p>` with a `::before` rule + its text):
1. **(used here)** Define the gap in CSS alongside whatever it spaces — e.g. `margin-right`/`margin-inline-end` on the `::before` in the child theme (the pseudo-element is non-typeable anyway, so this is the legitimate typed-first exception, not a punt).
2. Restructure the element to a `block` with `tag:"custom"` + `customTag:"p"` — a layout element rendered as `<p>`, which keeps `<p>` semantics and lets `_columnGap` emit typed. Invasive if the class is already used widely.
3. Wrap the line + text in a layout child and gap that.
Don't burn time hunting a "broken" typed gap on a text element — check the **applied element's type first**. `text-basic` = no flex gap/direction controls.
**First seen:** Highland, 2026-06-20 — `.eyebrow` (text-basic `<p>`) wouldn't emit a typed `_columnGap` between its `::before` line and text; spent multiple passes ruling out value/cache/version/`_direction` before confirming it was the text-vs-div element type. Gap moved to `.eyebrow::before { margin-right }` in the child theme.


### Bricks builder open during a WP-CLI meta write → saving from that tab silently clobbers the DB edit
**Symptom / When:** You edit `_bricks_page_content_2` (or header/footer) headlessly while a Bricks builder tab for that same post is open in a browser. The DB write succeeds and the front-end renders correctly, but the builder still shows the *old* tree — and the moment anyone clicks Save in that stale tab, your headless changes vanish.
**Why:** The builder loads the element tree into JS memory on open and treats that in-memory copy as the source of truth. Every Save POSTs the **entire** in-memory tree back over the meta key — it does not diff against, merge with, or re-read the DB first. So a builder session that opened *before* your CLI write is carrying a pre-edit snapshot, and its next Save overwrites the DB with that snapshot. The DB write itself isn't lost on contact — it's lost on the next builder Save. (Verify intactness with a `wp post meta get … --format=json` read-back, not by looking at the builder.)
**Fix:** Sequence the surfaces, never overlap them on the same post. Before a WP-CLI build/edit, close or hard-reload any open builder tab for that post. After a headless edit, a builder that needs to reopen must be **reloaded** so it pulls the fresh DB tree — only then is Save safe. If a clobber already happened, re-run the (idempotent) headless build; it takes seconds. This is the write-side twin of the golden rule's read-side validator: the builder is authoritative on save, so don't let a stale one save over you.
**First seen:** Highland, 2026-06-30 — integrated the Leaflet service-area map into homepage #18 via WP-CLI while a builder tab was open; the tab showed the old placeholder and looked "clobbered." DB read-back confirmed the map elements (canvas/caption) were actually intact — the real risk was a Save from the stale tab, avoided by closing all builders.


### Frontend-only JS widget (Leaflet map etc.) renders as a blank box inside the Bricks builder canvas — looks clobbered, isn't
**Symptom / When:** A component whose visuals are produced by a front-end JS library (Leaflet map, a slider, anything mounted by an enqueued script) shows as an empty styled `<div>` in the Bricks **builder** canvas, while rendering perfectly on the live page. Easy to misread as a failed/clobbered edit.
**Why:** The builder canvas is its own iframe/context and only loads builder-registered assets. A script enqueued for the front end (here, conditionally via `wp_enqueue_scripts` on `is_front_page()`/`is_page()`) is **not** loaded in the builder, so the mount element stays an empty container. The element, its classes, and its typed settings are all present and correct in the tree — only the JS-painted result is absent.
**Why it's fine:** Nothing to fix. The element box (background/radius/aspect/size from its Bricks/ACSS classes) still shows in the builder so you can position it; the widget itself only needs to be correct on the front end, which is where it's verified (curl/grep for the markup + enqueue, then a real browser to confirm the library initialised). If a builder-canvas preview is ever genuinely wanted, Bricks' `bricks/builder/…` asset hooks can enqueue the script there too — usually not worth it.
**First seen:** Highland, 2026-06-30 — the Leaflet service-area map (init script `highland-core/assets/js/service-area-map.js`, enqueued front-end only) showed as a blank steel box in the builder; verified the map renders on the live homepage (tiles + 9 markers + radius) and confirmed the builder blank is expected, not data loss. See the companion entry above on builder-open clobber risk.


### Bricks Code element written headlessly renders nothing until you regenerate its code signature
**Symptom / When:** You create a Bricks `code` element with `'executeCode' => true` via WP-CLI / direct DB write. DB read-back confirms the element and its code, the page returns 200, but the element renders **nothing** on the front end (no output, no `brxe-code` wrapper, no error) — as if the element isn't there.
**Why:** Bricks (1.9.7+) signs executable code with an HMAC (`settings.signature`) to stop DB-injected code from running as PHP — a security control against exactly this write path. `Code::render()` calls `Helpers::sanitize_element_php_code( $post_id, $element_id, $code, $signature )`; with no/invalid signature it returns empty and renders nothing. The signature is keyed to the post id + element id + code, so it can only be produced by Bricks itself, not hand-written.
**Fix:** After writing the code element, sign all code instances as an admin with the code-execution cap:
```php
wp_set_current_user( 1 );
\Bricks\Admin::crawl_and_update_code_signatures(); // signs every code element in the DB
\Bricks\Assets_Files::regenerate_css_files();
```
Verify the element now carries `settings.signature` (32 chars) on read-back and the code renders. Requires `executeCodeEnabled` on (Bricks → Settings → Custom code) and the user to hold the cap. If `BRICKS_LOCK_CODE_SIGNATURES` is defined, signing is blocked by design — don't use the Code element for headless writes there; use a Shortcode element backed by a plugin shortcode instead. Re-sign after any later headless edit to the code (the signature is content-bound).
**First seen:** Highland, 2026-06-30 — contact page (#21) form built as a static stub in a Code element (`executeCode`+`noRoot`); the `<form>` rendered nothing until `crawl_and_update_code_signatures()` signed it. Belongs with the auth gotcha (`wp_set_current_user(1)`) as a headless-write prerequisite.


### A mockup's "reuse these classes" note goes stale after a builder rework — reconcile against live `bricks_global_classes` BEFORE building
**Symptom / When:** Building a new page from a token-pure mockup whose header comment asserts it reuses existing global classes ("keep class contracts identical so the typed global classes carry over"). You plan to reuse `hh-card` / `hh-cta-band` / `hh-trust` / `hh-section-head` as named — but those classes don't exist in the install, or exist under different names, so a literal reuse would attach non-existent class ids (unstyled output) or silently rebuild components that already exist under another name (drift + duplication).
**Why:** Mockups are authored against an earlier snapshot of the build. Between mockup and build, the person doing the hands-on Bricks pass renames/refactors the shared components (on Highland, Mike's homepage rework turned `hh-cta-band`→`dark-cta-band`, `hh-trust`→`trust-strip`, `hh-section-head`→`mpd-section__header`, `hh-card`→`home-services__card`, and moved page blocks to page-scoped `home-*`). The mockup's class names and its "these carry over" note are frozen at authoring time and quietly diverge from live state.
**Fix:** Before building, dump the live classes and reconcile the mockup's assumed names against them — never trust the mockup's reuse note:
```bash
wp option get bricks_global_classes --format=json | python3 -c "import json,sys;[print(c['name']) for c in sorted(json.load(sys.stdin),key=lambda x:x['name'])]"
```
Also read what the sibling built page actually references (`_bricks_page_content_2` → `_cssGlobalClasses` → resolve ids to names) to learn the *current* naming. Then: reuse the live shared abstractions (here `mpd-*`, `dark-cta-band`, `trust-strip`, `eyebrow`, `display`, buttons) under their real names, and build page blocks page-scoped to the new page — do not resurrect the mockup's stale block names.
**First seen:** Highland, 2026-07-01 — about-page mockup asserted `hh-*` reuse; recon showed all those shared components had been renamed in the homepage rework, so the build reused `dark-cta-band`/`mpd-section__header`/`trust-strip__item` and used page-scoped `about-*` blocks instead. (Reconciling class contracts against live state is the front-half "token/skip remap" step of `02` extended to class names.)


### Grid container: `_alignItems` is ignored, `_alignItemsGrid` is the operative key (unset emits `align-items: initial`)
**Symptom / When:** Grid items won't stretch to equal height (or an `_alignItems` value on a grid class does nothing). Rendered CSS shows `align-items: initial` — a value never present in any setting.
**Why:** Bricks splits the control per display mode: `_alignItems` is the flex control and doesn't emit when `_display: grid`; the grid control is `_alignItemsGrid`, and when it's unset Bricks emits `align-items: initial` for grid containers (`initial` → `normal` → stretch, so the default behaves correctly — but the emitted `initial` misleads an audit into thinking a value is set).
**Fix:** On grid classes, set/unset `_alignItemsGrid` (unset = stretch). Audit tip: a class carrying both keys (e.g. `_alignItems: start` + `_alignItemsGrid: center` on `home-diff__container`) is really centered — the flex key is dead weight under grid.
**First seen:** Highland, 2026-07-03 — `.home-services__secondary` items wouldn't equal-height; class had `_alignItemsGrid: center`; removal restored stretch.


### `Assets_Files::regenerate_css_files()` prints "Error: Control type number is not defined!" and still succeeds
**Symptom / When:** Running the standard per-post CSS regen from WP-CLI on a site with BricksExtras (or any plugin registering custom Bricks controls). WP-CLI prints `Error: Control type number is not defined!` — which looks like the regen died.
**Why:** The regen walks every element's control definitions to emit CSS. Some third-party control types are only registered inside the builder context, so outside it the lookup misses and Bricks emits a notice. It does not halt the run — the remaining files still generate.
**Fix:** Do not trust the message either way. Assert on the output, which is why the canonical regen script ends with a file count:
```php
$f = glob( WP_CONTENT_DIR . '/uploads/bricks/css/post-*.min.css' );
echo "post-*.min.css count: " . count( $f ) . "\n";   // plus: check the target post's mtime
```
Non-zero count + a fresh `post-<ID>.min.css` mtime = the regen worked. Then verify the actual rule landed in the rendered page.
**First seen:** Highland, 2026-07-22 — regen after the Services media-tier conversion. Printed the error, wrote 11 files, page rendered correctly.


### Bricks Form: `redirectAdminUrl` silently overrides the custom redirect AND mangles it into a literal path
**Symptom / When:** A Bricks Form with the `redirect` action and a dynamic URL (e.g. `redirect = {site_login}`) sends the user to a nonsense URL after a successful submit — `https://site.com/wp-admin/{site_login}` — with the tag unresolved and wrapped under `/wp-admin/`. Most visible on a custom Reset Password page, where the post-reset redirect is the last step of a flow nobody tests until launch.
**Why:** In `themes/bricks/includes/integrations/form/actions/redirect.php` the two settings are **not** mutually exclusive and are evaluated in order. `redirect` is rendered first (`$redirect_to = $form->render_data( $settings['redirect'] )`), then the admin branch **overwrites it** and re-wraps the **raw, unrendered** string: `$redirect_to = isset($settings['redirect']) ? admin_url( $settings['redirect'] ) : admin_url();`. So the dynamic tag never resolves and the value becomes a path under wp-admin. In the Bricks UI the two controls sit in the same "Redirect" group with nothing indicating one nullifies the other.
**Fix:** Pick one. To use a custom/dynamic redirect, the `redirectAdminUrl` key must be **UNSET, not set to `false`** — the code tests `isset()`, so a stored `false` still triggers the admin branch:
```php
$s = $el['settings'];
unset( $s['redirectAdminUrl'] );   // NOT $s['redirectAdminUrl'] = false;
```
Verify by replicating the logic rather than trusting the panel:
```php
$r = isset($s['redirect']) ? bricks_render_dynamic_data($s['redirect']) : false;
if ( isset($s['redirectAdminUrl']) ) $r = isset($s['redirect']) ? admin_url($s['redirect']) : admin_url();
echo $r; // what the user will actually get
```
**First seen:** Highland, 2026-08-04 — imported Reset Password page had both set; every successful password reset landed on `/wp-admin/{site_login}`. Login page on the same build was correct precisely *because* it had `redirectAdminUrl` with **no** `redirect` value, which is the only combination where the admin branch behaves as labelled.


### Bricks custom login page ignores `?redirect_to=` whenever a redirect action is configured
**Symptom / When:** A user is bounced to the custom login page from a protected URL (`/login/?redirect_to=…`), logs in, and lands on the dashboard instead of where they were going. Looks like the parameter is being dropped.
**Why:** `form/actions/login.php` only honours the `redirect_to` form field when the redirect action is **absent**, or when neither `redirect` nor `redirectAdminUrl` is set:
```php
if ( ! in_array( 'redirect', $form_settings['actions'], true ) ||
     ( ! isset( $form_settings['redirect'] ) && ! isset( $form_settings['redirectAdminUrl'] ) ) ) { … }
```
A form configured to always land on wp-admin therefore ignores deep links by design.
**Fix:** Accept it for an admin-only door (arguably desirable — every login goes to one known place). If deep-link-after-login is wanted, remove the redirect action from the form and let `redirect_to` drive, or filter `bricks/auth/custom_redirect_url`. Decide deliberately; don't discover it from a client.
**Also here — a stale docblock to ignore:** `auth-redirects.php::modify_reset_password_email()` documents "only occurs if … the WordPress auth URL behavior is not set to default". **The implementation has no such check** — it rewrites the reset email to the custom page whenever that page is set and published. Verify behaviour, not the comment:
```php
apply_filters( 'retrieve_password_message', $native_msg, 'KEY', 'user', null ); // print the link it produces
```
**First seen:** Highland, 2026-08-04 — auditing the Bricks custom auth pages before launch.


### A Bricks template imported from another site carries that site's numeric IDs — fonts, ACF fields, and form settings all break silently
**Symptom / When:** Pages imported from another build (a wireframe kit, a sibling project, a marketplace template) render structurally fine but: text uses the wrong typeface with **`font-family:"some-image-filename"`** in the emitted CSS; an `alt` or heading prints a literal `{acf_some_field}`; form notification settings name a domain you've never heard of.
**Why:** Bricks stores several references as **numeric post IDs or bare field names that are only meaningful on the origin site**. `_typography.font-family` is `custom_font_<post_id>` — on the new site that ID is whatever post happens to occupy it, commonly a media attachment, and Bricks dutifully emits its title as a font name. `{acf_*}` tags reference field *names* that may not exist locally. Form `emailTo`/`fromName`/Mailchimp strings come along too. None of it errors; it just renders wrong.
**Fix:** After ANY cross-site template import, sweep for foreign references before styling:
```bash
# font ids used, resolved against what they actually are locally
wp eval 'global $wpdb; $r=$wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE \"_bricks_page_%\"");
$j=$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name=\"bricks_global_classes\"");
preg_match_all("/custom_font_(\d+)/", implode("",$r).$j, $m);
foreach(array_unique($m[1]) as $id){ $p=get_post($id); printf("custom_font_%-5s -> %s\n",$id,$p?$p->post_type."/".$p->post_title:"MISSING"); }'
# this install's real fonts (note the post type is bricks_fonts, plural)
wp eval 'foreach(get_posts(["post_type"=>"bricks_fonts","numberposts"=>-1]) as $f) printf("%d %s\n",$f->ID,$f->post_title);'
```
Then confirm on the rendered page — `curl … | grep -oE 'font-family:[^;}]*' | sort -u` should list only real families. Also grep the rendered HTML for `{acf_` to catch unresolved tags, and read the form settings for the origin site's email config.
**First seen:** Highland, 2026-08-04 — three auth pages imported as wireframe bones emitted `font-family:"highland-home-kit-before_001"` (font id 154 was a *kitchen photo* locally) and `alt="{acf_business_info_company_name}"`, plus `fromName: acss.brixies.co` on every form.


### Dynamic tags do NOT re-resolve inside the value a custom dynamic tag returns
**Symptom / When:** A custom tag returns client-entered text (an ACF field, a repeater row). If that text itself contains another dynamic tag, the tag prints literally on the page — and, worse, gets published verbatim into JSON-LD or a meta description.
**Why:** Bricks resolves tags in the element's saved settings. Your callback's **return value** is output, not re-parsed — there is no second pass. Anything nested arrives as literal text.
**Fix:** Treat client-supplied text as a leaf. Do not put tags in it, and say so in the ACF field instructions so nobody tries later. If a value genuinely must be composed, resolve it inside the callback:
```php
function prefix_loop_answer() {
    $row = prefix_current_row();
    return $row ? bricks_render_dynamic_data( $row['answer'] ) : ''; // explicit second pass
}
```
Weigh that carefully where the same string feeds structured data — a half-resolved tag in `acceptedAnswer` is worse than a hardcoded value.
**First seen:** Highland, 2026-08-04 — a client-managed FAQ repeater feeds both the on-page block and `FAQPage` schema. Wiring the site-wide response promise (`{highland_response_time}`) into an answer would have published `{highland_response_time}` into `acceptedAnswer`; the literal string was kept deliberately and the constraint documented.


### Custom Bricks query type over a non-post source: post-context tags silently resolve to nothing
**Symptom / When:** A custom query type iterating arrays (an ACF **options-page** repeater, an API result) loops the right number of times, but `{post_title}`, `{acf_*}` and friends output empty inside the loop.
**Why:** Those tags read the global post context. `bricks/query/loop_object` only establishes it when the loop object is a `WP_Post` and you call `setup_postdata()`. Plain arrays have no post context, and ACF's own repeater loop exposure (`acf_<repeater>`) applies to a repeater **on the current post** — an options-page repeater is not that.
**Fix:** Register loop-aware custom tags that read the loop object directly, and have them fail quietly outside a loop:
```php
function prefix_current_row() {
    if ( ! class_exists( '\Bricks\Query' ) ) return null;
    $o = \Bricks\Query::get_loop_object();
    return is_array( $o ) && isset( $o['question'] ) ? $o : null;
}
function prefix_loop_question() { $r = prefix_current_row(); return $r ? $r['question'] : ''; }
```
Register on `render_tag` **and** `render_content` (conditions resolve via the latter). The loop element itself must be a layout element — `hasLoop` is ignored on heading/text.
**First seen:** Highland, 2026-08-04 — `highland_faqs` query type over a Site Options repeater so the client owns the FAQ; rows are arrays, so `{acf_*}` was never going to resolve.


## BricksExtras

### BricksExtras element `name` strips hyphens from the file basename
**Symptom:** A BricksExtras element written with `name: 'x-pro-accordion'` (the file basename) silently does not render; children orphan.
**Why:** The BricksExtras loader does `str_replace('-', '', $element['file_name'])` for the registered name. So `x-pro-accordion.php` registers as `xproaccordion` — all hyphens stripped.
**Fix:** Use the hyphen-stripped name: `name: 'xproaccordion'`. Verify with `grep "public \$name" wp-content/plugins/bricksextras/components/classes/<file>.php`.
**First seen:** KSCBS, 2026-05-10 — Contact page FAQ accordion did not render despite correct child elements.

### BricksExtras elements are gated behind a per-element admin enable flag
**Symptom:** A BricksExtras element (with the correct name) still does not render. No errors. The class is not even loaded.
**Why:** The loader skips registration when the `bricksextras_<key>` option is `0` (the default). The option must be `1` for the element class to register.
**Fix:** Flip the toggle:
```bash
wp option update bricksextras_pro_accordion 1
wp cache flush
```
Audit all disabled elements: `wp option list --search="bricksextras_*" --format=csv | grep ",0$"`. Worth checking all in-use Pro elements at project start.
**First seen:** KSCBS, 2026-05-10 — Pro Accordion did not render until `bricksextras_pro_accordion` was flipped.

### BricksExtras Offcanvas Nestable — `clickTrigger` is the master selector for burger-toggle behavior
**Symptom:** A Burger Trigger plus Offcanvas Nestable renders, BricksExtras JS enqueues, but clicking the burger does nothing. The burger never gets runtime `aria-controls` / `aria-expanded`.
**Why:** The Bricks UI shows `.brxe-xburgertrigger` as a placeholder in the "Selector (Trigger)" field — it reads like a default but is not one. The PHP render falls back to empty string if `clickTrigger` is not explicitly set. The offcanvas JS uses `clickTrigger` for click registration, syncBurgers, and autoAriaControl — all three branches bail when it is empty.
**Fix:** Explicitly set the offcanvas's `clickTrigger` to `.brxe-xburgertrigger` (or a custom class on the trigger). BricksExtras UI placeholders are visual hints, not runtime defaults.
**First seen:** KSCBS, 2026-05-17 — header v2 rebuild; burger rendered but did nothing.

### ProSlider list semantics (BricksExtras)
**Context:** Splide's default markup wraps slides in `<div>`; for archive grids and screen-reader semantics you want `<ul><li>` with an `<article>` inside.
**Fix:** Set Splide config `listTag: 'ul'` and add an extra Slide block with `tag: 'li'`.
**First seen:** V1 baseline, 2026-05-24. (Full ProSlider schema — slide markers + the boolean-vs-string control types — is in the `02` schema library.)

### BricksExtras ProAccordion has a hardcoded `:where()` gray header background
**Symptom / When:** You apply a card class with `_background: white` to a ProAccordion **item**. The card area renders white, but the accordion HEADER row stays gray (`#EFEFEF`) — reads as "background not taking".
**Why:** `proaccordion.css` ships `:where(.x-accordion_header){ background-color:#EFEFEF }`. The `:where()` has zero specificity so it doesn't override your class — but your class is on the accordion **item** and never reaches the inner header element. With no explicit background on the header itself, the BricksExtras default wins by default.
**Fix:** Apply a Global Class to the **accordion-header element** (the `<div role="button">` inside the item) with an explicit `_background.color`. Any class-level declaration beats `:where()`; no `!important` needed.
**First seen:** TAB, 2026-04-26 — FAQ section; a `card-gold-*` treatment looked gray-on-white until the header got its own class.

### BricksExtras ProAccordion emits no `aria-expanded` / `aria-controls` in SSR markup
**Symptom / When:** ProAccordion renders each header as `<div role="button" tabindex="0">` but the HTML never contains `aria-expanded` or `aria-controls`. It's clickable, but screen readers announce no expand/collapse state and can't navigate button→panel. There is no setting for it.
**Why:** BricksExtras toggles `aria-expanded` via JS on click but ships no initial-state ARIA in SSR; `aria-controls` (the structural button↔panel link) appears never to be set. Both are required by the W3C ARIA APG accordion pattern and by WCAG 2.1 AA — so this is a real AA gap on any project using it.
**Fix (in order of preference):** (1) file a feature request with BricksExtras for SSR ARIA; (2) inject via child-theme JS — walk `.x-accordion_header` on init, generate id pairs, set `aria-controls` on the header and `id` on the panel, toggle `aria-expanded` on click; (3) replace with native `<details>`/`<summary>` (full a11y by default, loses the BricksExtras features).
Triage first: click an item and inspect. If `aria-expanded` updates, only SSR is stale (less urgent). If it doesn't update at all, severe.
**First seen:** TAB, 2026-04-26 — FAQ a11y audit: 6 items, 0 with `aria-expanded`, 0 with `aria-controls`. Shipped fix was the child-theme JS route.

### BricksExtras element-level styling doesn't emit via CLI CSS regen — "Control type X is not defined!"
**Symptom / When:** After writing a BricksExtras element (e.g. `xoffcanvasnestable`) headlessly and running `\Bricks\Assets_Files::regenerate_css_files()` via WP-CLI, the regen prints `Error: Control type number is not defined!` and the BE element's *element-level* styling settings (`offcanvas_width`, `backdrop_color`, …) emit **zero CSS**. Global-class CSS on the same elements emits fine; the element markup and its JS config render fine.
**Why:** Bricks' CSS generator resolves each setting against the element's registered control definitions to know how to map it to CSS. BricksExtras registers its custom control types only in builder context — under CLI the control type lookup fails, and the generator skips those settings (loudly, but non-fatally). Builder saves regenerate with all control types present, which is why the same settings work on installs where the element was authored in the UI.
**Fix:** Keep the typed BE settings in place (correct, builder-visible, they emit on the first builder save of the template). For anything that must render before that first save — or that the typed control can't express (responsive `min()`) — carry it in `_cssCustom` on a related global class, targeting BE's stable structural classes, e.g. `.brxe-xoffcanvasnestable .x-offcanvas_inner { width: min(400px, 88vw); padding: 0; }`. Check BE's base stylesheet (`bricksextras/components/assets/css/`) first — its defaults may already cover you (backdrop is `rgba(0,0,0,.5)` out of the box, ≡ `--black-trans-50`).
**First seen:** Highland, 2026-07-02 — header rebuild #38; BE Pro OffCanvas `offcanvas_width`/`backdrop_color` skipped by CLI regen. Width/padding moved to `_cssCustom` on `mpd-header-offcanvas__inner`; backdrop left to BE's default.


### Sticky header + offcanvas inside the header template — the panel "drops"/slides with the header
**Symptom / When:** An offcanvas element (BricksExtras Pro OffCanvas, or anything `position: fixed`) lives inside the header template, and native `headerSticky` + `headerStickyOnScroll` are enabled. Opening the offcanvas (or just scrolling) makes the main header bar visibly drop/slide, and the offcanvas panel positions against the header instead of the viewport.
**Why:** `headerStickyOnScroll` animates `#brx-header` with a `transform`. A transformed ancestor becomes the containing block for `position: fixed` descendants — the offcanvas panel (and its backdrop) stop being viewport-fixed and ride the header's transform instead. Same family as the typed-`position:sticky` containing-block trap, from the other direction.
**Fix:** Either (a) drop sticky from the header template (`headerSticky`/`headerStickyOnScroll` out of `_bricks_template_settings`) — chosen on Highland; or (b) keep sticky but move the offcanvas out of the header template (BE's OffCanvas supports a template source: `offcanvas_template`) so no transformed ancestor sits above the fixed panel. Plain `headerSticky` without `OnScroll` avoids the *slide* but the trap remains latent for any transform Bricks applies.
**First seen:** Highland, 2026-07-02 — header #38 rebuild; BE offcanvas at header-template root; drop confirmed fixed by removing sticky.


### BE Pro Slider Gallery default lazy-load ships placeholder `src` — kills hero/LCP images
**Symptom / When:** A Pro Slider Gallery renders every slide `<img>` with an inline SVG data-URI as `src`/`srcset` (real URL only in `data-splide-lazy`), so images appear only after Splide's JS initializes. Harmless below the fold; on a hero it makes the LCP element a 0×0 SVG and defers the real paint to post-JS.
**Why:** `lazyLoadSupport` defaults to `'splide'`, which swaps in the placeholder + `loading="eager"` and hands loading to Splide at init. The `fetchpriority="high"` WP adds targets the data-URI, not the real image.
**Fix:** For above-the-fold sliders set `lazyLoadSupport: 'none'` (real `src`, eager, in initial HTML) + `maybeSRCSET: 'enable'` (srcset default is DISABLED — without it you ship the full-size original). Keep the `'splide'` default for below-fold sliders.
**First seen:** Highland, 2026-07-03 — homepage hero slider; first slide was an SVG placeholder until JS ran.


### BricksExtras before/after renders nothing server-side — verify headless with Playwright, and beware the screenshot-timing false alarm
**Symptom / When:** Confirming a BE `xbeforeafterimage` (or any BE widget) actually works after a CLI build. `curl | grep` proves only that the markup shipped — BE builds the clip/handle in JS, so a broken widget and a working one look identical in the HTML. Worse: a full-section Playwright screenshot can show **the same image on both sides of the handle**, which reads as "the before image is wired wrong."
**Why:** Two separate things. (1) BE's SSR output is just two stacked blocks; the clip-path, handle and labels only exist after Splide/BE JS initialises, which is why the widget is also blank in the Bricks builder iframe. (2) The before layer is `position:absolute; clip-path: polygon(0 0, 50% 0, 50% 100%, 0 100%)` **over** the after layer — if the screenshot fires before that layer's image has painted, you photograph the after layer showing through, at full width. The DOM is correct the whole time; only the pixels lie.
**Fix:** Assert on the DOM, not the picture — then screenshot to confirm composition:
```js
// per block: which image, and is it the clipped (before) layer?
[...ba.querySelectorAll('.x-before-after-image_block')].map(blk => ({
  src:  blk.querySelector('img').currentSrc.split('/').pop(),
  clip: getComputedStyle(blk).clipPath,   // before => polygon(...), after => 'none'
  pos:  getComputedStyle(blk).position,   // before => absolute, after => static
}))
```
Screenshot the **block element** (`locator.screenshot()`), or `scrollIntoViewIfNeeded()` + wait ~2s before shooting the section — never `page.screenshot({clip})` straight after load. Playwright installs clean on a RunCloud box with no root: `npm i playwright && npx playwright install chromium`.
**First seen:** Highland, 2026-07-22 — promoting three Services sections to media tier. The kitchen section's first screenshot showed a finished kitchen on both sides of the handle; the pairing was in fact correct (before=154, after=168) and a block-level screenshot proved it immediately. Nearly "fixed" a bug that did not exist.


## Frontend Toolkit (animations)

### Frontend Toolkit must skip the builder iframe, not just the builder main frame
**Symptom:** After a Bricks upgrade, the builder canvas renders blank — the element tree populates in the sidebar but the canvas shows only the header and Page Title section. Front end is fine.
**Why:** A child-theme enqueue guard using `bricks_is_builder_main()` returns true only in the parent builder admin frame — false inside the builder iframe where the canvas renders. So `animations.css`/`animations.js` keep loading inside the iframe. The animation CSS sets `.anim-*` elements to `opacity: 0` until an IntersectionObserver fires; in Bricks 2.3.4 the iframe's observer no longer fires reliably, so the canvas looks blank though the HTML is fully rendered.
**Fix:** Use `bricks_is_builder()` — covers both main and iframe contexts. One-word change in the child theme `functions.php` guard. Reset OPcache after (FPM workers cache compiled `functions.php`).
**Diagnostic giveaway:** headers and Page Title render but the page body does not — those elements have no `.anim-*` classes; everything else does.
**Cross-project rule:** any project using the Frontend Toolkit must guard with `bricks_is_builder()`. Bake it into the child-theme scaffold.
**First seen:** AHML, 2026-05-12 — after Bricks 2.2.x → 2.3.4.

### Frontend Toolkit `staggerObserver` does not pick up AJAX-injected children inside an already-fired stagger parent
**Symptom:** Stagger cascades animate on initial load. After a Bricks AJAX event — filter, pagination, query-loop refresh — newly-injected children inside a stagger parent stay at `opacity: 0` forever. Solo `.anim-fade-up` elements work fine across AJAX.
**Why:** The stagger observer is `once: true` and unobserves its parent after first intersection; the `observed` WeakSet keeps the persistent parent marked. After AJAX, `observeAll()` re-runs but the `elementObserver` path skips children of stagger parents and the `staggerObserver` path skips already-observed parents — so new children are picked up by neither.
**Fix:** In `observeAll()`, when a stagger parent is already observed, add a catch-up pass applying `.anim-visible` to all current matching children:
```js
document.querySelectorAll(STAGGER_SELECTORS).forEach(function (el) {
    if (observed.has(el)) {
        el.querySelectorAll(ANIM_SELECTORS).forEach(function (child) {
            child.classList.add('anim-visible');
        });
        return;
    }
    staggerObserver.observe(el);
    observed.add(el);
});
```
The CSS `nth-child` rules still drive per-child `transition-delay`, so the cascade is preserved. Alternatively, on AJAX-rendered lists, make each card a standalone `.anim-fade-up` rather than a stagger child.
**First seen:** AHML, 2026-04-30 — Blog Archive post grid.

### Frontend Toolkit — never put `.anim-*` on a content wrapper taller than the viewport
**Symptom / When:** On page load a long content block (single blog post body, area community section) renders at `opacity: 0` and only appears once the user scrolls a little. Small hero elements animate in normally.
**Why:** The toolkit's IntersectionObserver uses `threshold: 0.1` — 10% of the target must be in view to reveal it. A wrapper holding an entire tall article can't show 10% of itself while the hero occupies the top of the viewport, so it never crosses the threshold on load and stays hidden until a scroll nudges it past 10%. An element taller than ~`viewport / 0.1` (≈10× the viewport) can never cross it at all. Because `.anim-*` sets `opacity: 0` up front, a broken reveal = invisible primary content (SEO/UX risk).
**Fix:** Don't animate tall content containers. Animate the small, above-the-fold pieces (eyebrow, title, meta, featured image, CTA) and leave the body wrapper static. Strip the `anim-*` token from the wrapper that grows with content (it may be a stagger child — removing its class just makes it always-visible while its siblings keep staggering):
```php
wp_set_current_user( 1 );
// drop the anim-* class from the reading-column / body wrapper's _cssClasses, then:
update_post_meta( $tmpl_id, '_bricks_page_content_2', $content );
\Bricks\Assets_Files::regenerate_css_files();
```
**First seen:** AHML, 2026-07-01 — Blog Single (tmpl 595) `article__inner` reading column and Area Single (tmpl 474) community-body wrapper both went invisible-until-scroll; removed `.anim-fade-up` from the body wrappers, kept hero + CTA animations.

## ACSS

### ACSS settings — write via `Database_Settings::save_settings()`, never direct option writes
**Symptom / When:** You set values via `wp option update` / `wp option patch` (e.g. `primary-medium-h`) and they never compile into the CSS — even after a dashboard Save. Or the dashboard's React form keeps showing stale values after a hard refresh, and a save stomps your WP-CLI changes.
**Why:** Two mechanisms stacked. (1) ACSS only persists keys defined in its UI schema (`UI::get_all_settings()`). The flat shade keys (`primary-medium-h`, `primary-light-h`, hover/comp) are **artifacts** the engine computes from the schema's `color-<slot>` hex input — they're not in the allowed-variables list and get dropped silently on save. (2) The dashboard's React form caches values in client state; a save can stomp WP-CLI changes if the form was loaded before your update.
**Fix — three rules:**
```bash
wp --user=1 eval '
$values = get_option("automatic_css_settings");
$values["color-primary"]   = "#8B6F47";   // canonical schema input key: a hex string
$values["color-secondary"] = "#BF9B30";
$db = \Automatic_CSS\Model\Database_Settings::get_instance();
$db->save_settings($values, true);        // true = regenerate all 8 CSS files
'
```
1. Use schema **input** keys (`color-primary`, `color-base`) — single hex strings — not the computed shade artifacts.
2. Call `Database_Settings::save_settings($values, true)` — equivalent to a dashboard Save, but reliable.
3. Run as **user 1** (`wp --user=1 eval`) — the save method requires `manage_options` and throws `Insufficient_Permissions` otherwise.

Discover valid schema keys with `(new \Automatic_CSS\Model\Config\UI())->get_all_settings()`. Close the ACSS dashboard tab before CLI edits (same single-writer hazard as the Bricks builder).
**Full-recolor mechanics.** The `color-<slot>` hex is the source of truth for the parent var (`--primary`) and its `-h`/`-s`/`-l` partials — but the derived shades are stored **independently**. To genuinely recolor a family, rewrite each shade's `-h` and `-s` while keeping its `-l` lightness target: `primary-{hover,ultra-light,light,semi-light,medium,semi-dark,dark,ultra-dark}-h` / `-s`. Non-color settings ride the same call: `vp-max` → `--content-width` (px ÷ root = rem), `base-radius` → `--radius`. Back up first: `wp option get automatic_css_settings --format=json > backup.json`. Contextual/dark-scheme vars (`--body-bg-color`, `--text-color`, `--h1`, `--space-*`) are better handled by a child-theme `:root` bridge than by settings — see the `automatic-bricks.css` entry.
**First seen:** V1 baseline, 2026-05-24 (the "regenerate after a DB edit" rule). **Extended:** TAB, 2026-04-25 — color-slot population silently dropped by direct flat-key writes; dashboard saves repeatedly reverted CLI changes via stale React state. · **Extended:** VMG, 2026-06-05 — configured a full palette + content-width + radius entirely from CLI.

### ACSS heading sizes (`h1-max` / `h1-min`) don't reach `--h1` — override in the child theme
**Symptom / When:** You set `h1-max`/`h2-max` in the option or dashboard. The values land in the DB and even appear in the rendered CSS as `--h1-max`, but the actual `--h1` falls back to a modular-scale default.
**Why:** ACSS 3.3.6's SCSS defines a `$heading-fallbacks` map with three modes (`calc`, `calc-max`, `clamp`); only `calc-max` and `clamp` consume `$h1-max`. The active mode is selected by something **not exposed in `UI::get_all_settings()`** — there is no `heading-mode` key in the schema. Depending on config the output can resolve to the `text-xxl-pure` static defaults rather than your `h1-max`. Every *other* heading property — line-height, letter-spacing, font-weight, color, font-family — compiles through correctly. Only the **size** is affected.
**Fix:** Don't fight it, especially for an irregular type ladder. Override `--h1`…`--h6` directly in the child theme and leave every other heading property to ACSS:
```css
:root { --h1: 3.5rem; --h2: 2.5rem; --h3: 1.75rem; --h4: 1.375rem; --h5: 1.125rem; --h6: 1rem; }
```
⚠️ A fixed rem override loses ACSS's fluid scaling — write a `clamp()` if you want fluid (see the `@supports` entry above).
**Also missing from the ACSS schema:** no global `body-line-height` / `text-line-height`; `h4-line-height` is absent even though h1/h2/h3/h5/h6 have it. Set both in the child theme.
**First seen:** TAB, 2026-04-25 — an irregular brand ladder (56→40→28→22→18→16) fitting no modular ratio surfaced the behavior.

### ACSS `option-<slot>-clr` toggles gate whether a color slot compiles at all
**Symptom / When:** You set `color-secondary` (or tertiary/action/accent) via the dashboard or `save_settings()`, but `--secondary-*` and its whole shade ramp never appear in `automatic-variables.css`. The hex lands in the option; the slot emits nothing.
**Why:** Each color slot has an on/off toggle — `option-primary-clr`, `option-secondary-clr`, etc. ACSS only compiles a slot's variables when the toggle is `'on'`. **Blueprints frequently ship with several slots OFF**, so this bites on any project started from one.
**Fix:**
```bash
wp --user=1 eval '
$values = get_option("automatic_css_settings");
$values["option-secondary-clr"] = "on";
(\Automatic_CSS\Model\Database_Settings::get_instance())->save_settings($values, true);
'
# audit all toggles:
wp eval '$s=get_option("automatic_css_settings"); foreach($s as $k=>$v) if(preg_match("/^option-.*-clr$/",$k)) echo "$k = $v\n";'
```
Worth auditing at ACSS configuration time on every project — the symptom is silent.
**First seen:** TAB, 2026-04-25 — a brand color didn't compile because the blueprint had `option-secondary-clr: 'off'`.

### A column "stack" class (`_direction:column` + `_rowGap`) is byte-equivalent to the ACSS `.gap--N` utility
**Symptom / When:** Several near-identical wrapper classes only set flex-column + a row-gap, and you want to consolidate.
**Why:** Bricks `.brxe-block` AND `.brxe-container` already default to `display:flex; flex-direction:column`. ACSS `.gap--N` = `gap: var(--space-N)`, and on a single-column flex `gap` ≡ `row-gap`. So the stack class's `_direction:column` is redundant and its `_rowGap` is exactly what `.gap--N` provides.
**Fix:** Re-point the elements to the ACSS gap class (id `acss_import_gap--N`) and delete the bespoke class — zero new classes, no visual change. Only when the element is a block/container (column default) AND the gap maps to a space token. **NB** the ACSS space scale is fluid and large (`--space-xs` ≈ 1.9rem), so a hardcoded `1.5rem` gap has NO token equivalent — keep those as typed element settings rather than snapping to a utility.
**First seen:** TAB, 2026-06-25 — class-consolidation sweep; 17 column-stack classes re-pointed to `gap--{xs,m,l,xl}`.

### `_inner` is dead — the ACSS Container replaces it
Layout pattern is SECTION > CONTAINER (ACSS class) > BEM elements. Do not add `__inner` BEM elements; the ACSS Container handles max-width and centering. Padding is stripped from BEM containers; section spacing utilities handle it. (Full convention in `01`.)

### ACSS — "Remove Deactivated Classes" toggle is the master ACSS→Bricks sync switch (misnamed)
**Symptom / When:** Right-clicking a color field in the Bricks builder shows only the "Default" palette — no ACSS-named palettes — even though ACSS is configured and the variables render fine on the front end. `wp option get bricks_color_palette` returns `[]`.
**Why:** Despite the label, ACSS's "Remove Deactivated Classes" toggle is the gate for the entire ACSS → Bricks DB sync on save. With it off, `Bricks::after_save_settings()` bails before importing palettes, so `bricks_color_palette` stays empty.
**Fix:** WP Admin → Automatic.css → Options → Bricks Enhancements → toggle "Remove Deactivated Classes" ON, then Save the ACSS Dashboard. Verify `bricks_color_palette` now has `acss_import_*` entries.
**Default state:** ships `on` on fresh ACSS installs. Older or hand-disabled installs leave it `off`. Check it at ACSS configuration time on every project — the symptom is silent.
**First seen:** KSCBS, 2026-05-06 — Bricks color picker showed only "Default" swatches.

### ACSS — `clamp()` values in an `@supports` block override the rem fallbacks in `:root`
**Symptom / When:** Auditing ACSS sizes from `automatic-variables.css` shows fixed rem values (`--h2: 2.28rem` ≈ 36px). A child-theme override planned on that basis does not match what renders.
**Why:** ACSS ships two declarations per typography variable. The rem value in `automatic-variables.css` is a fallback. The real value is a `clamp()` redeclaration inside an `@supports (font-size: clamp(...))` block in `automatic.css` (lines ≈ 5099+). Every modern browser supports `clamp()`, so the `@supports` block always wins.
**Fix:** Audit the actual values from `automatic.css` lines 5099+. When overriding a heading/text size from the child theme, write a new `clamp()` to preserve fluid scaling — a fixed rem override loses fluid behavior. Retune the `Xvw + Yrem` slope when changing a max.
**First seen:** KSCBS, 2026-05-05 — overriding `--h1`/`--h2`; the real gap to brand spec was 2px, not the 6px the rem fallback implied.

### ACSS — spacing/text scale stops at `xs`; using `2xs` silently falls back to an invalid var
**Symptom / When:** A typed setting or `_cssCustom` rule using `var(--space-2xs)` or `var(--text-2xs)` saves cleanly and the var reference is in the rendered CSS, but the element renders at default sizing.
**Why:** ACSS emits only `--space-xs/s/m/l/xl/xxl` and `--text-xs/s/m/l/xl/xxl`. There is no `2xs`. `var(--space-2xs)` with no fallback is invalid; the browser drops the property silently.
**Fix:** For tighter sizing than `xs`, use literal rems, or provide a fallback: `var(--space-2xs, 0.5rem)`.
**First seen:** KSCBS, 2026-05-07 — mobile_portrait padding on `home-cta__btn` used `var(--space-2xs)`.

### ACSS — `automatic-bricks.css` enqueues AFTER the child theme; override ACSS tokens via `:root`, not selectors
**Symptom / When:** An equal-specificity child-theme override for a rule in `automatic-bricks.css` matches but has zero effect; the ACSS rule wins despite identical specificity.
**Why:** ACSS enqueues `automatic-bricks.css` after the child-theme stylesheet, so equal-specificity child rules lose on source order.
**Fix:** Override the ACSS token, not the rule. ACSS button/spacing/type tokens (`--btn-padding-block`, etc.) are defined at `:root` in `automatic.css` (load order earlier than the child theme). A `:root` override in the child theme wins for the variable, and ACSS's own consuming rule resolves to the new value:
```css
:root { --btn-padding-block: 0.6em 0.4em; }
```
Before fighting any `automatic-bricks.css` rule with a selector, check whether it consumes a token defined in `automatic.css` — if so, override the token.
**Why the bridge always wins (the deeper mechanism).** `automatic-bricks.css` loads **last** but contains **no `:root` blocks at all** — it only *consumes* vars (`background: var(--body-bg-color, …)`). Every ACSS variable is *defined* in `automatic.css`, which loads early. So a child-theme `:root` block loading after `automatic.css` overrides the token and **nothing later redefines it**. Verify by checking every `.css` in `<head>` for who *defines* (not uses) the var.
**Application — a DARK-FIRST site on light-first ACSS.** Two layers: (1) set the three palette colors (`color-primary`/`-base`/`-neutral`) via ACSS settings (see the `save_settings` entry); (2) bridge the rest in the child theme:
```css
:root {
  --body-bg-color: var(--obsidian); --body-color: var(--ink-100);
  --text-color: var(--ink-100); --heading-color: var(--ink-100);
  --heading-font-weight: 500; --heading-font-family: var(--font-display);
  --link-color: var(--sapphire-light);
  --h1: var(--f-h1); --h2: var(--f-h2); --text-m: var(--f-body); /* type scale */
}
```
Spacing piggybacks the same mechanic: a brand `--space-*` defined in a token file loading after `automatic.css` overrides ACSS's for the shared steps — no settings edit needed. Don't use `.bg--dark` for brand sections (its bg is `--neutral-dark`); use the raw surface tokens.
**First seen:** KSCBS, 2026-05-10 — a button padding override with a selector identical to ACSS's had no effect. · **Extended:** VMG, 2026-06-05 — dark-first portal on ACSS 3.3.6; the `:root` bridge carried the whole contextual layer.

### ACSS — button bg-context wrappers override variant classes via specificity
**Symptom / When:** A button set to `btn--base` (or any `btn--*` variant) inside a `.bg--dark` / `.bg--light` section ignores the variant — it renders the configured bg-context color regardless.
**Why:** ACSS's "BG Color Buttons" system emits `.bg--dark [class*="btn--"]` at (0,3,0), beating variant rules at (0,2,0). `[class*="btn--"]` matches every variant, so the variant override is silently overridden. Separately, the stock `.btn--base` rule is broken outside a `.bg--*` context (off-white text on off-white bg). ACSS ships no `.bg--primary` wrapper — green-section CTAs need a project-defined system.
**Fix:** Either change the bg-context button variables in ACSS Dashboard → Buttons, or override the wrapper at equal-or-higher specificity in the child theme — the child sheet enqueues after `automatic.css`, so `(0,3,0)` matching the ACSS wrapper wins. Encode the brand rule once project-wide rather than fighting it per button.
**First seen:** KSCBS, 2026-05-07 — hero buttons set to `btn--base` rendered green-on-charcoal, failing the dark-section contrast rule.

### ACSS — "light"/"dark" variants of a NEAR-BLACK base resolve to LIGHT colors
**Symptom / When:** You build a dark-theme surface on `var(--base-light)` expecting "slightly lighter than the near-black base" and get a light grey-lavender. White text on it is unreadable.
**Why:** ACSS variant lightness values are absolute-ish scale positions, **not relative offsets** from the base. For a base around `#08090D` (L≈4%), `--base-light` lands in genuinely light territory. There is no generated "base but 7% lighter" variable — the mental model of `-light` meaning "a bit lighter than what I set" is simply wrong at the dark end of the scale.
**Fix:** Derive dark surfaces from the base **hue** with explicit lightness — ACSS exposes the HSL components:
```css
--surface:      hsl( var(--base-h), var(--base-s), 11% );
--surface-deep: hsl( var(--base-h), var(--base-s),  7% );
```
Still brand-tracked (hue and saturation follow the palette), and guaranteed dark.
**First seen:** NLTA, 2026-07-06 — form inputs on a dark page rendered lavender with white text on top.

### ACSS — changing a base color hex in the Dashboard can wipe variation overrides on that family
**Symptom / When:** A manual variation override on a color (e.g. a customized `--base-light`) does not survive a change to the parent base color hex.
**Why:** Not fully pinned down — appears that reconfiguring a parent color regenerates the family and discards manual variation overrides.
**Fix:** Any time you change a base color in the ACSS Dashboard, re-verify variation overrides on that family in the same session.
**First seen:** KSCBS, 2026-05-10 — a Warm Silver override on `--base-light` did not survive a `--base` hex change. (Provisional — if confirmed across more cases, promote to a firmer entry.)

### ACSS — `:where(section…)` makes any hand-rendered `<section>` flex-column-centred; `section > div` forces its children to column
**Symptom / When:** A plugin/PHP-rendered card built as `<section class="card">` (with `<div>` children) renders with content horizontally centred and spread vertically, and direct-child rows you set `display:flex` come out stacked as columns — though your CSS never says so. Shows only on real pages (ACSS loaded), not in a stripped mockup.
**Why:** ACSS ships `section:where(:not(.bricks-shape-divider)){display:flex;flex-direction:column;align-items:center;gap:…}` and `section > div:where(…){display:flex;flex-direction:column;align-items:flex-start;gap:…}`. Intended for Bricks sections, they match ANY top-level `<section>` and its direct `<div>` children. They use `:where()` (specificity 0,0,1) so they're trivially overridden — but ONLY for properties you explicitly declare; relying on element defaults (no `display`/`flex-direction`) lets ACSS win.
**Fix:** On hand-authored sections, declare the layout explicitly: `.card{display:block}` and `flex-direction:row` on every direct-child flex row. Don't rely on element defaults inside a `<section>` on an ACSS site.
**First seen:** VMG, 2026-06-07 — My Account dashboard cards (`<section class="card">`) rendered centred and stacked; the login card too.

### ACSS palette shades are dashboard-derived — a WP-CLI base-colour write leaves the ramp stale
**Symptom / When:** Scripting the ACSS palette via `save_settings`: you write `color-primary` (or `color-accent`), regenerate, and `--primary` updates but `--primary-light/-dark/-hover/…` stay on the OLD colour.
**Why:** The shade ladder (`-light/-dark/-hover/-trans` + the `-h/-s/-l` partials, ~2,113 keys) is computed by the dashboard's JS and **stored in the option**; the SCSS compiler reads those stored keys — it does NOT recompute shades from the base hex. A base-only write updates the base and nothing else. (Everything non-colour — type, radius, buttons, scales, focus — has no derivation and scripts cleanly.)
**Fix:** For the palette, prompt the user to set it in the dashboard once (it runs the derivation), then script the rest by WP-CLI — the established pattern. Or replicate the derivation in PHP: keep base H/S, set each shade's L to its fixed step (ultra-light 95 / light 85 / semi-light 65 / semi-dark 35 / dark 25 / ultra-dark 10; hover a smaller L bump — confirm), write base + all `-h/-s/-l`, then `save_settings`. Convention + procedure: `01`, `02`.
**First seen:** MMHN, 2026-07-16 — `color-accent=#112233` changed `--accent` but left `--accent-light/-dark` gold; confirmed the SCSS reads the stored shade keys.

### ACSS custom CSS / Global SCSS is delivered INLINE (after automatic.css), not as a linked file
**Symptom / When:** Custom vars/rules added in the ACSS Global SCSS are in the on-disk `automatic-custom-css.css`, but the front-end `<head>` doesn't link that file and the linked `automatic.css` still shows the framework default (e.g. `--focus-width:2px`, no `--cream`). Looks like the custom CSS isn't loading / an override was lost.
**Why:** With `cssLoading=file`, the front end enqueues `automatic.css` and the Global SCSS is added **inline** via `wp_add_inline_style` on the ACSS core handle — printed in `<style id="automaticcss-core-inline-css">` immediately AFTER the `automatic.css` `<link>`. The standalone `automatic-custom-css.css` is a build artifact, not what loads; and because the inline block follows `automatic.css`, `:root` overrides in Global SCSS win the cascade.
**Fix:** Verify custom CSS on the **rendered page**, not the disk files (`curl -sk <url> | grep -A2 automaticcss-core-inline-css`). Overriding an ACSS framework variable in Global SCSS is a valid, load-order-safe technique.
**First seen:** MMHN, 2026-07-16 — nearly reported `--focus-width:3px` working off the disk files; the page confirmed it only via the inline block.

### ACSS v3 settings UI is a shadow-DOM front-end overlay — a11y-tree automation can't reach it
**Symptom / When:** Automating the ACSS dashboard, `read_page`/`form_input` return only the WP admin bar; none of the dashboard inputs/toggles/dropdowns are reachable by element ref.
**Why:** As of v3 the settings UI is a real-time dashboard on the front end (`?acssOpenDashboard=1`, or SHIFT+CMD+O in the builder), rendered in a shadow DOM.
**Fix:** Prefer WP-CLI (`01`/`02`) and avoid the dashboard for scriptable settings. If you must drive it (palette only), use screenshot + coordinate clicks; it's a single-expand accordion whose expanded header stays at its compact row, so expand→set→collapse in one batch keeps fields on-screen.
**First seen:** MMHN, 2026-07-16.

### ACSS type: per-level Font Size Override hits a non-geometric brand scale exactly
**Symptom / When:** A brand type scale isn't geometric (H1 46–56, H2 34–48, H3 22–26 — H2:H3 ≠ H1:H2), so ACSS's base-size + single ratio can't land every level.
**Why:** ACSS Typography has a per-level tab (H1…H6, and XXL…XS for text) with a **Font Size Override (mobile / desktop px)** on top of the global base+scale. The mobile/desktop pair is the fluid-clamp min/max — i.e. the brand's range.
**Fix:** Set the brand ranges as per-level overrides (mobile=min, desktop=max); leave base+scale for the unspecified levels. Scriptable via `save_settings`. Pin a floor with a per-level override where a scale step would dip below it (e.g. `text-s`=14/14 for a 14px floor).
**First seen:** MMHN, 2026-07-16.

### ACSS is fully configurable headless via `Database_Settings::save_settings()` — but `API::update_settings()` is broken in 3.3.6
**Symptom / When:** Configuring ACSS (colors, fonts, scales) from WP-CLI. `\Automatic_CSS\API::update_settings( $vars )` — the documented entry point — fatals: `Call to undefined method Automatic_CSS\Model\Database_Settings::save_vars()` (API.php:86). Version skew: the installed `API.php` calls a method that no longer exists.
**Why:** The real save method is `Database_Settings::save_settings( $values, $trigger_css_generation = true )`. It validates `$values` against the allowed-variable list from the config UI and falls back to each var's **default** for any allowed var absent from input — so passing only your deltas resets everything else to defaults.
**Fix:** Merge over the full current settings, then save. `save_settings()` regenerates the CSS files directly (no dashboard "Save" needed — supersedes the older `03` note about clicking Save).
```php
wp_set_current_user(1); // needs manage_options (CAPABILITY check)
$db = \Automatic_CSS\Model\Database_Settings::get_instance();
$vars = array_merge( $db->get_vars(), $my_changes ); // FULL set, not just deltas
$db->save_settings( $vars, true );
```
**First seen:** Highland, 2026-06-14 — configuring ACSS to the brand. `API::update_settings()` fatalled (clean fail, no DB write); switched to `save_settings()` with a full merge.


### ACSS color shade ramps are stored per-shade, NOT recomputed from the master — must rewrite the ramp partials
**Symptom / When:** After setting a master color (`color-primary`) via DB + regenerate, the base `--primary` is correct but the whole shade ramp (`--primary-dark`, `-light`, `-hover`, …) stays the OLD color. The dashboard's color picker recomputes ramps in JS; the PHP save path does not.
**Why:** Each shade's hue/sat/lightness is stored as ~60 option keys per family (`{color}-{mod}-{h,s,l}` plus `-alt` scheme variants). The SCSS compiler emits composites (`--primary-dark`) **from these stored partials** (composites are not themselves option keys). Change only the master and the partials are stale. `API::update_settings()` propagates saturation only (`-s`), not hue/lightness — and even that uses a stale modifier list (`medium` vs this build's `semi-light`/`semi-dark`).
**Fix:** Rewrite the partials, then regenerate. Model validated against the blueprint baseline — every shade inherits master **H** and **S**; **L** is a fixed curve independent of master:
```
ultra-light 95 · light 85 · semi-light 65 · semi-dark 35 · dark 25 · ultra-dark 10 · medium 50
comp = master L · hover = master L × 1.15      (-alt variants mirror the non-alt values)
```
Derive master H/S/L with `new \Automatic_CSS\Helpers\Color( $hex )` (`->h/->s/->l`) for exact consistency. Iterate only existing `{color}-{mod}-{h|s|l}(-alt)$` keys so nothing non-allowed is introduced. Related: each non-core color also needs its `option-{name}-clr` toggle = `'on'` or it emits no CSS at all (secondary/danger/success/warning ship OFF; only primary/base/neutral on by default).
**First seen:** Highland, 2026-06-14 — primary went red but ramp stayed teal (hue 193); rewrote 270 ramp keys across 5 families.


### ACSS → Bricks palette sync only hooks under `is_admin()` — CLI saves regenerate CSS but never update the color picker
**Symptom / When:** After a headless `save_settings()`, the front-end CSS is correct (all `--color` vars emit) but the Bricks color picker still shows the OLD palette — newly added/enabled colors are absent. Repeated CLI saves never fix it.
**Why:** `Bricks::__construct()` registers `add_action('automaticcss_settings_after_save', 'after_save_settings')` **inside an `if ( is_admin() )`** guard (Bricks.php:92). WP-CLI is not admin context, so the listener is never attached — the CSS regen runs (called directly in `save_settings`) but the palette sync (only via that hook) does not. The picker reflects whatever the last *browser* dashboard save wrote.
**Fix:** Construct the platform (its ctor needs the DB-settings instance) and call the sync directly:
```php
wp_set_current_user(1);
$db = \Automatic_CSS\Model\Database_Settings::get_instance();
( new \Automatic_CSS\Framework\Platforms\Bricks( $db ) )->after_save_settings();
```
The sync is additive (never removes) and gated by `option-remove-deactivated-classes-from-globals` (the misnamed master sync switch — see established entry). It pulls `get_color_palettes()` with `pro_active_only=true`, so a color appears only if its `option-{name}-clr` is on. Semantic colors (danger/success/warning) additionally route through the `option-status-colors` path.
**First seen:** Highland, 2026-06-14 — all brand colors emitted in CSS but the picker stuck on the blueprint's primary/base/neutral; forcing `after_save_settings()` synced all 9 families (345 entries).


## ACF

### ACF Pro — `default_value` seeds the form only, not `get_field()` reads
**Symptom / When:** An ACF field defined with `'default_value' => 'foo'` — a helper using `get_field()` returns null/empty before the options page has been saved.
**Why:** ACF treats `default_value` as a form pre-fill, not a runtime fallback. Until the options page is saved once, the underlying option does not exist and `get_field()` returns null.
**Fix:** Either save the options page once in admin after registering defaults (cheapest), or guard reads in helper functions with a hardcoded fallback. For projects where the options page is guaranteed saved before launch, the admin-save approach is cleaner — make it a launch-checklist gate: "Site Options must be saved once in admin before launch."
**First seen:** KSCBS, 2026-05-05 — core plugin scaffold; field defaults seeded but `kscbs_get_company_name()` returned empty.

### ACF — `true_false` opt-out fields: legacy posts have NO meta row, so `value='1'` excludes them
**Symptom / When:** You add a "default ON" `true_false` field as a suppression lever (include everything; toggle off to exclude), then a `meta_query` for `value => '1'` returns **nothing** for existing posts.
**Why:** ACF only writes the meta row when a post is saved *after* the field exists. Pre-existing posts have **no row at all** — so `value='1'` doesn't match them, and neither does `!= '0'`. (Same root cause as the `default_value` entry above: ACF defaults are a form pre-fill, not data.)
**Fix:** Treat "missing" as included — match NOT EXISTS **OR** explicit `'1'`, and set `default_value => 1` so newly-saved posts store it. Only an explicit `'0'` then suppresses:
```php
'meta_query' => [ 'relation' => 'OR',
  [ 'key' => 'include_in_email', 'compare' => 'NOT EXISTS' ],
  [ 'key' => 'include_in_email', 'value' => '1' ],
],
```
**First seen:** NLTA, 2026-06-16 — an "include in email" suppression toggle on an existing CPT; every legacy post silently fell out of the query.

### ACF — `acf/prepare_field`: `$field['name']` is the PREFIXED input name; match on `_name`
**Symptom / When:** A `prepare_field` filter that looks up `$field['name']` in a map silently matches nothing. No error — the filter just never fires its branch.
**Why:** By prepare time ACF has rewritten `name` to the **form input name** (`acf[field_abc123]`). The original field name lives in `$field['_name']`.
**Fix:** `$name = $field['_name'] ?? $field['name'];` before any name-keyed logic.
**First seen:** NLTA, 2026-07-06 — a per-field placeholder swap on a front-end form matched nothing.

### ACF — `acf_form()` front-end survival kit
**When:** Building a front-end authoring form with `acf_form()` (reuses the field schema you already register in PHP — a strong alternative to rebuilding the whole field set in a form plugin and maintaining a mapping forever).
**The non-obvious parts:**
- **The `fields` param accepts `'_post_title'`** mixed in with field keys — full control of cross-group field order. Tradeoff: the form is **curated**, so fields added to the admin groups later do **not** auto-appear. (Group-based `field_groups` rendering auto-inherits but can't interleave.) Pick per project and document the choice.
- **Route `_post_title` yourself anyway** in `acf/save_post` (title + `sanitize_title()` slug) — belt and braces, and you get clean permalinks at pending stage.
- **Taxonomy fields as `select`/`multi_select` ride select2 + admin-ajax** — fragile on the front end. `field_type => 'radio'` / `'checkbox'` render native inputs (inside `.categorychecklist-holder`), keep `save_terms`, and CSS-grid into columns.
- **Section headings can't be pure CSS** — ACF fields float at inline %-widths, so a `::before` on a 33%-wide field can't span the row. Inject `<h3>` client-side keyed on the stable `div[data-key="field_…"]` selector.
- **ACF's form CSS is the wp-admin light theme** — on a dark site every input and surface needs explicit overrides, including select2, `.acf-switch`, gallery chrome, and `accent-color` for radios/checkboxes.
- **A hidden `#acf-hidden-wp-editor` with TinyMCE in the markup is normal** — it ships with the uploader/media modal, not a rendered WYSIWYG. Don't chase it.
- **Location-rule-driven "conditional" groups don't switch on the front end** — location rules resolve at render. Mimic with a server-side selector (`?type=x` → a per-type field list) and set the driving term in `acf/save_post` from a **whitelisted** hidden input.
- **Check `04` before debugging a dead uploader** — Perfmatters' Delay JS *and* Defer JS each independently kill the WP media modal on any page with an `acf_form()` uploader. Fixing one is not enough, and it presents as an ACF bug rather than an optimisation one.
**First seen:** NLTA, 2026-07-06 — a gated front-end profile submission form.

### ACF field removal — `get_field()` stops working but the raw post meta survives
**Symptom / When:** You refactor a field out of a field group (removing a repeater, replacing it with a relationship). `get_field("old_field", $post_id)` returns null even though the data is visibly still in the database.
**Why:** ACF reads values via the local field-group definitions. Remove the field from `acf_add_local_field_group()` and ACF no longer knows its structure (key, type, sub_fields), so `get_field()` can't reconstruct the value. The raw meta keys remain — ACF wrote them and they persist until explicitly deleted. **The data isn't lost; the reader is gone.** For a repeater the keys look like:
```
page_faqs            => "6"          (row count)
page_faqs_0_question => "Do you…"
_page_faqs           => "field_xxx"  (field key reference)
```
**Fix — migrate via `get_post_meta`, then clean up:**
```php
$count = (int) get_post_meta( $pid, 'page_faqs', true );
for ( $i = 0; $i < $count; $i++ ) {
    $q = get_post_meta( $pid, "page_faqs_{$i}_question", true );
    $a = get_post_meta( $pid, "page_faqs_{$i}_answer", true );
    if ( $q && $a ) $rows[] = [ 'question' => $q, 'answer' => $a ];
}
// …migrate $rows to the new structure, then delete both the value and the `_`-prefixed key per row
```
**Order matters:** read the raw data BEFORE removing the field from the PHP if you can — it's far easier. If the field is already gone, this is the recovery path.
**First seen:** TAB, 2026-04-26 — refactoring a page-level FAQ repeater to a FAQ CPT + relationship; the repeater was removed from the field group first, so the 6 rows had to be read raw.

### ACF `url` field type rejects relative paths and query strings
**Symptom / When:** A field declared `'type' => 'url'` throws "Value must be a valid URL" and blocks save when you enter an internal link like `/request-a-quote/` or `/request-a-quote/?service=decks`.
**Why:** ACF's `url` type validates against a full RFC URL (scheme + host). Relative paths, site-root paths and bare query strings all fail — the type is for absolute external URLs only.
**Fix:** Use `'type' => 'text'` for internal links. The Bricks tag `{acf_<field>}` resolves a text field identically in a link `useDynamicData` binding, so no template change is needed. (Don't reach for the ACF `link` type as a workaround — it returns an **array**, which breaks a string `{acf_<field>}` tag.)
**First seen:** TAB, 2026-05-30 — a CTA field holding `/request-a-quote/?service=slug` blocked save under `type: url`.

### Bricks ACF query loop: `objectType: acf_<field>`; repeater subfields are `{acf_<repeater>_<subfield>}`
**Symptom / When:** A Query Loop set to an ACF **repeater** renders the right number of rows but every subfield tag (`{acf_feature_title}`) prints **literally**. Relationship loops resolve fine; repeater subfields silently don't.
**Why:** Bricks' ACF provider namespaces repeater subfield tags by the parent repeater (`provider-acf.php` ~L115: `'acf_' . $parent_field['name'] . '_' . $field['name']`). The bare subfield tag isn't in the loop's tag map, so it falls through to literal text.
**Fix:**
- Loop element: `hasLoop = true`, `query.objectType = "acf_<field_name>"`.
- **Repeater** (loop item = a row): `{acf_<repeater>_<subfield>}` → `{acf_service_features_feature_title}`.
- **Relationship** (loop item = the related *post*): use post-context tags — `{post_title}`, `{post_url}`, and the related post's own `{acf_<field>}`.
**First seen:** TAB, 2026-05-29 — repeater loops rendered correct row counts with literal subfield tags; grepping `provider-acf.php` gave the namespaced format.

### ACF `gallery` / `image` fields are NOT loopable in Bricks — including repeater image subfields
**Symptom / When:** Two faces of one bug. (1) A loop set to `objectType: acf_<gallery_field>` renders the loop shell (`data-start=0 data-end=0`) but **zero items**, though the field has images. (2) A repeater loop renders the right row count and text subfields resolve, but an **image** subfield bound to a Bricks image element renders an empty `<img>`.
**Why:** Bricks builds its loop-tag registry from a field-type→context map (`provider-acf.php` ~L1088). `gallery` and `image` map to `[CONTEXT_TEXT, CONTEXT_IMAGE]` — **not `CONTEXT_LOOP`**. Only `relationship`, `post_object`, `repeater`, `flexible_content` and `group` get `CONTEXT_LOOP`. A gallery field is never registered as a loop tag, so the query matches nothing; a repeater's image subfield can't be pulled per-row.
**The golden-rule trap:** the provider's loop *handlers* (`set_loop_query`/`set_loop_object`) both have `case 'gallery'`, which reads like support. **The registration is the source of truth, not the render handlers.** Verify the registered loop tags (dump the provider's `loop_tags` via reflection); don't trust a switch statement.
**Fix:** Expose the images as a **custom query type** returning plain attachment IDs, plus a loop-aware image tag returning `[ $id ]` in image context (see the image-context array entry above):
```php
// query run: return get_field('<gallery>', get_the_ID(), false)   // raw attachment IDs
// tag:       return $context === 'image' ? [ $att_id ] : (string) $att_id;
//            $att_id from \Bricks\Query::get_loop_object()
```
**First seen:** TAB, 2026-06-08 — a gallery loop returned 0 items. **Same mechanism again:** TAB, 2026-06-10 — a repeater's logo-image subfield looped (chips rendered) but every `<img>` was empty.

## WordPress core — CPTs, rewrites, canonical

### A CPT named `author` collides with WP's built-in `?author=` query var — single URLs 404
**Symptom / When:** You register a CPT named `author` with rewrite slug `authors` (plural, to dodge the obvious `/author/` user-archive collision). The archive `/authors/` resolves. Single URLs `/authors/{slug}/` **404**. `url_to_postid()` returns 0. The post exists, rules are flushed, the meta is fine.
**Why:** `register_post_type()` auto-generates rules routing `/{slug}/{post}/` to `?{cpt-name}={post}` — using the CPT's internal name as the query var. When the name is `author` it routes to `?author={slug}`, which is **already** a registered WP query var bound to the built-in user-archive lookup (`User_Query` against `user_nicename`). WP's user lookup runs, finds no match, and 404s before ever querying your post type.
**Diagnostic:**
```bash
wp eval '
$rules = $GLOBALS["wp_rewrite"]->wp_rewrite_rules();
foreach ($rules as $m => $q) if (preg_match("/^authors\//", $m)) echo "  $m => $q\n";
'
# ?author=$matches[1] instead of ?your_cpt=$matches[1] → this collision
```
**Fix:** Pass an explicit non-colliding `query_var`, then `wp rewrite flush`:
```php
register_post_type( 'author', [
    'query_var' => 'tab_author',
    'rewrite'   => [ 'slug' => 'authors', 'with_front' => false ],
] );
```
**General rule:** check any CPT name against WP's reserved query vars (`p`, `name`, `page`, `paged`, `author`, `category`, `tag` — see `wp-includes/class-wp.php::public_query_vars`). Any overlap needs an explicit `query_var` override.
**First seen:** TAB, 2026-05-28 — an `author` CPT for bylines decoupled from `wp_users`; `/authors/` worked, `/authors/trace-baum/` 404'd.

### A term archive whose slug matches a CPT single 301s to that single (`redirect_canonical` collision)
**Symptom / When:** `/projects/category/decks-porches/` 301-redirects to `/services/decks-porches/`. The term query resolves fine server-side (`is_tax=1`, `is_404=false`, found>0), the redirects table is empty, the rewrite rule matches — yet the live request redirects.
**Why:** When taxonomy term slugs intentionally mirror post slugs elsewhere (a common, deliberate IA choice), WP core's `redirect_canonical()` sees the shared slug and "helpfully" redirects the term archive to the matching single.
**Fix:** Bail out of `redirect_canonical` for that taxonomy, in the core plugin:
```php
add_filter( 'redirect_canonical', fn( $r, $req ) => is_tax( 'project_category' ) ? false : $r, 10, 2 );
```
**Related:** if a nested-slug taxonomy (`projects/category`) 301s to a same-slug single, also check rewrite ordering — registering the **taxonomy before the post type** fixes a greedy CPT attachment rule sorting ahead of the taxonomy rule.
**First seen:** TAB, 2026-05-31 — category archives 301'd to service singles until the filter was added.

### Favicon: WP native Site Icon handles raster but not SVG; programmatic set skips the `site_icon-*` sizes
**Symptom / When:** Wiring a favicon on a Bricks build. Two snags: (1) WP native Site Icon never emits an SVG favicon (raster only), so a crisp/scalable SVG needs separate output; (2) setting `site_icon` programmatically (import attachment + `update_option('site_icon', $id)`) does NOT generate the `site_icon-32/180/192/270` intermediate sizes — `wp_generate_attachment_metadata()` only makes the default sizes, so every favicon link falls back to the full image.
**Why:** The `site_icon-*` sizes are registered only inside WP's admin crop flow (`WP_Site_Icon`), which doesn't run from WP-CLI / a plain attachment insert. And `wp_site_icon()` (hooked `wp_head` @ 99) only outputs `<link rel="icon">`/`apple-touch-icon`/`msapplication` for raster — no `type="image/svg+xml"`.
**Fix (hybrid, what Highland uses):**
- Raster set → WP native Site Icon from a 512² PNG. To get real sizes when setting it headless, register the sizes before regenerating:
  ```php
  add_filter('intermediate_image_sizes_advanced', function($s){ foreach([32,180,192,270,512] as $px){ $s["site_icon-$px"]=['width'=>$px,'height'=>$px,'crop'=>true]; } return $s; });
  wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, get_attached_file($id)));
  update_option('site_icon', $id);
  ```
- SVG favicon → emit it yourself in the core plugin (bundled asset, brand constant): `add_action('wp_head', fn() => printf('<link rel="icon" href="%s" type="image/svg+xml">', esc_url($url)), 2)`. Modern browsers prefer the SVG; Safari/iOS/Android use the native raster set. Add `<meta name="theme-color">` alongside. An SVG with `<style>@media (prefers-color-scheme: dark){...}</style>` gives a free dark-mode favicon.
**First seen:** Highland, 2026-06-14 — programmatic Site Icon generated only `thumbnail`; added the size filter + a plugin SVG-favicon module (`inc/favicon.php`).


## Rank Math + Bricks

### Rank Math `%excerpt%` description template produces junk on Bricks-built Pages
**Symptom / When:** Meta descriptions on Bricks-built **Pages** render as leftover/empty text — e.g. a static front page shows WordPress's default "This is an example page…" as its meta description.
**Why:** Bricks stores the page layout in `_bricks_page_content_2` and leaves WP-native `post_content` empty or stale. Rank Math's default `pt_page_description = %excerpt%` auto-generates the description from `post_content` → garbage. Separately, RM's Homepage Titles & Meta tab governs only a *latest-posts* front page; with a **static** front page it uses that page's own SEO meta.
**Fix:** Author `rank_math_description` (and `rank_math_title` for the front page) **manually per Bricks Page**:
```php
update_post_meta( $page_id, 'rank_math_description', 'Hand-written description.' );
update_post_meta( get_option('page_on_front'), 'rank_math_title', '%sitename% %sep% …' );
```
CPTs that keep real copy in `post_content` (body rendered via the Bricks Post Content element) are unaffected — `%excerpt%` works there. Rule: **Bricks Pages need manual descriptions; content-backed CPTs don't.**
**First seen:** AHML, 2026-06-02 — homepage rendered the WP sample-page text as its meta description during RM Titles & Meta setup.

### The `rank_math_modules` option has two traps — slugs are legacy, and writing it does NOT create the module's DB tables
**Symptom / When:** Managing Rank Math modules programmatically. Two distinct failures, same option, usually the same sitting. (1) You append a module slug (e.g. `redirections`) and the module reports active, but using it fails — `\RankMath\Redirections\DB::add()` returns `0`; log shows `Table 'wp_rank_math_redirections' doesn't exist`. (2) You *remove* `schema` to stop RM emitting its JSON-LD graph — matching the directory name in `includes/modules/schema/` — and RM keeps emitting it.
**Why:** (1) Rank Math creates a module's tables in its module-activation path (the UI toggle / `Installer`), not when the option value changes. Writing the option directly skips table creation. (2) The Schema module's **directory** is `schema`, but the value persisted in the option is the legacy slug **`rich-snippet`**. Directory name ≠ option value, and nothing surfaces the mismatch.
**Fix:** Read the option to learn the real slugs rather than assuming any — never infer a slug from the directory name:
```bash
wp option get rank_math_modules --format=json
```
Then call the installer for the active module set after any toggle:
```php
\RankMath\Installer::create_tables( (array) get_option( 'rank_math_modules', array() ) );
```
`create_tables()` is public/static and idempotent (dbDelta). Note `\RankMath\Redirections\Cache::purge()` requires an argument — don't call it bare; a plain `wp cache flush` is enough. Gate any "is RM emitting schema?" check on `in_array( 'rich-snippet', get_option('rank_math_modules', []), true )`.
**First seen:** AHML, 2026-06-02 — enabled Redirections by editing the option; the `/areas/ → home` redirect insert silently no-op'd until `Installer::create_tables()` ran. **Highland, 2026-08-04** — scoping RM to titles/meta/sitemap/redirects while the core plugin took over JSON-LD; removing `schema` did nothing because the stored slug is `rich-snippet`. Folded at the Highland harvest: one option, one entry.

### Bricks `bricks_template` CPT is publicly indexable AND in the Rank Math sitemap by default
**Symptom / When:** `/template/<name>/` URLs (header, footer, single/archive templates, error) return HTTP 200, render raw template scaffolding, are `index,follow`, and appear in `bricks_template-sitemap.xml`. Google can index your header/footer as standalone pages.
**Why:** Bricks registers `bricks_template` as `publicly_queryable` (needed for builder preview), and Rank Math defaults `pt_bricks_template_sitemap = on` with `pt_bricks_template_robots = index`. Nothing flags it.
**Fix:** In RM options set `pt_bricks_template_sitemap = off` and `pt_bricks_template_robots = ['noindex']` + `pt_bricks_template_custom_robots = 'on'`, then `\RankMath\Sitemap\Cache::invalidate_storage()`. Do **not** disable Bricks' `publicly_queryable` — that breaks builder preview. Check on every Bricks + RM project at SEO-config time.
**First seen:** AHML, 2026-06-02 — sitemap verification found 9 internal templates live at 200/index and listed in the sitemap.

### Rank Math — SEO scores are computed CLIENT-SIDE; the DB value goes stale and NULL ≠ unoptimized
**Symptom / When:** `rank_math_seo_score` postmeta is NULL or outdated even though title, description, and focus keyword are all set. An audit keying off the score column miscounts — both directions.
**Why:** Rank Math's content analysis runs as **JavaScript in the block editor** and only writes the score on an editor save. CLI or programmatic meta edits never touch it. The score is cosmetic; the meta itself is what ships to crawlers.
**Fix:** Judge optimization state by the **actual meta keys** — `rank_math_title`, `rank_math_description`, `rank_math_focus_keyword` — never by the score. To refresh the dashboard numbers, open and re-save the post in wp-admin.
**First seen:** NLTA, 2026-07-06 — an audit reported "1 post at score 25"; the real state was 2 posts missing focus keywords (one unnoticed) and 4 missing excerpts. Scores for CLI-fixed posts stayed stale afterward. *(Generalizes: any plugin metric computed in the editor is unreliable as an audit source — verify the underlying data.)*

### Rank Math — `og:type` defaults to `article` on EVERY non-homepage page, including archives
**Symptom / When:** `<meta property="og:type" content="article">` on a CPT archive or taxonomy archive, where it should be `website`. Share previews on Facebook/LinkedIn render collection pages as articles, with publish-date metadata that makes no sense.
**Why:** Rank Math's `Facebook::get_type()` (`includes/opengraph/class-facebook.php`) only branches on `is_front_page()`/`is_home()` → `website`, `is_author()` → `profile`, and `is_product()` → `product`. **Everything else** — post-type archives, taxonomy archives, search, date archives — falls through to `article`. There is no UI setting; the per-archive Open Graph fields don't expose `og:type`.
**Fix:** Hook the `rank_math/opengraph/type` filter:
```php
add_filter( 'rank_math/opengraph/type', function( $type ) {
    if ( is_post_type_archive() || is_tax() || is_category() || is_tag() ) {
        return 'website';
    }
    return $type;
});
```
**Verify:** `curl -s <url> | grep og:type` — singles should still be `article`, archives now `website`.
**First seen:** NLTA, 2026-04-29 — flagged in an SEO audit; fixed via a filter in the core plugin.

### Disabling Rank Math's Schema (Rich Snippets) module removes ALL its JSON-LD — including the default @graph
**Symptom / When:** You want a custom emitter (the core plugin) to own all structured data, and you expect to still need a `rank_math/json_ld` filter to strip RM's automatic @graph (WebSite / Organization / BreadcrumbList) even after turning off per-post-type rich snippets.
**Why:** RM's **entire** front-end JSON-LD pipeline is gated by the `rich-snippet` module. Disable it and RM emits **zero** `application/ld+json` — the baseline @graph goes with it. No filter needed.
**Fix:**
```php
\RankMath\Helper::update_modules( [ 'rich-snippet' => 'off' ] );
// verify: curl <url> | grep -c 'application/ld+json'  → only your own block
```
⚠️ **BreadcrumbList JSON-LD disappears too**, so a custom emitter must rebuild it. (The *visual* breadcrumb element is unaffected.) Set `pt_*_default_rich_snippet` → `off` for tidiness.
**First seen:** TAB, 2026-06-24 — RM kept for sitemap/redirects/404 while the core plugin owns the full @graph; disabling the module alone zeroed RM's output and the planned suppression filter proved unnecessary.

### Rank Math routes a CPT *named* `author` through its built-in Author sitemap provider
**Symptom / When:** A `rank_math/sitemap/entry` filter that excludes non-public CPT entries works for normal CPTs (`if ($type !== 'project') return $url;`) but the identical pattern for a CPT named `author` does nothing — gated entries leak into `author-sitemap.xml` as 404 URLs.
**Why:** RM has a dedicated built-in **Author (user-archive) sitemap provider**. A CPT literally named `author` is routed through it by slug collision, so the `$type` passed to the entry filter is NOT the plain post-type string your other checks rely on — the string compare misses every entry. (Pairs with the `?author=` query-var collision above: naming a CPT `author` collides with WP core *and* Rank Math, in two unrelated ways.)
**Fix:** Gate on the entry **object's** post_type, not `$type`:
```php
if ( is_object( $post ) && ( $post->post_type ?? '' ) === 'author'
     && ! get_field( 'author_public_archive', $post->ID ) ) return false;
```
Then `\RankMath\Sitemap\Cache::invalidate_storage()`.
**First seen:** TAB, 2026-06-27 — a post-launch crawl found a gated bio entry in `author-sitemap.xml` as a 404.

### Rank Math's per-post-type sitemap toggle is all-or-nothing — turning a CPT off removes its ARCHIVE too
**Symptom / When:** A CPT is archive-only (singles 301 to the archive, or are thin/noindex). The sitemap advertises every single URL, all of which redirect. Setting `pt_<type>_sitemap = off` fixes that but also removes the `/archive/` entry you *do* want indexed — the one page that matters.
**Why:** RM builds one sitemap per post type and includes the post-type archive link as an entry inside it. The option governs the whole sitemap, so there is no setting that keeps the archive while dropping the members.
**Fix:** Leave the toggle **on** and filter entries individually. Keeps working for posts added later, with no further config:
```php
add_filter( 'rank_math/sitemap/entry', function ( $url, $type, $object ) {
    if ( empty( $url['loc'] ) ) return $url;
    $archive = get_post_type_archive_link( 'project' );
    if ( ! $archive ) return $url;
    $loc = untrailingslashit( $url['loc'] );
    if ( untrailingslashit( $archive ) === $loc ) return $url;                   // keep the archive
    if ( 0 === strpos( $loc, untrailingslashit( $archive ) . '/' ) ) return false; // drop its singles
    return $url;
}, 10, 3 );
```
Verify every advertised URL actually returns 200 — a sitemap full of 301s is the symptom to look for:
```bash
for u in $(curl -s https://site/project-sitemap.xml | grep -oE '<loc>[^<]+' | sed 's/<loc>//'); do
  printf "%s %s\n" "$(curl -so /dev/null -w '%{http_code}' "$u")" "$u"; done
```
**First seen:** Highland, 2026-08-04 — `project` is archive-only; the sitemap listed 12 single project URLs that every one of them 301'd to `/projects/`.
*(Related, above the seam: "Bricks `bricks_template` CPT is publicly indexable AND in the Rank Math sitemap by default" — that one IS solved by the plain toggle, because templates have no archive worth keeping.)*


### `blog_public = 0` on staging masks per-page noindex — you cannot verify indexing config until you lift it
**Symptom / When:** Every page on staging reports `noindex`, so a page that is *supposed* to be noindexed at launch (login, thank-you, templates) looks correctly configured — and a page that is **missing** its per-page setting looks identical. The mistake surfaces after go-live.
**Why:** With "Discourage search engines" on, Rank Math emits `noindex` site-wide, overriding the per-page signal in the output. The rendered `<meta name="robots">` therefore proves nothing about post-launch behaviour.
**Fix:** Lift the flag in a bounded window, sample the pages, and restore in the same command so it cannot be left on:
```bash
wp option update blog_public 1 --quiet
for p in /login/ /about/ /; do printf "%-16s %s\n" "$p" \
  "$(curl -s "https://site$p" | grep -oP '(?<=name="robots" content=")[^"]*' | head -1)"; done
wp option update blog_public 0 --quiet
```
Expect the protected pages to hold `noindex` from their own `rank_math_robots` meta while public pages return to `index, follow`. Do this before cutover, not after.
**First seen:** Highland, 2026-08-04 — confirming three admin auth pages were noindexed. Staging showed `noindex` on all 10 pages, which would equally have been true if the per-page meta were absent.


## Mailster

### Mailster — custom dynamic tags use `{tag:option}` syntax and resolve at SEND time, not in the editor
**Symptom / When:** Building an auto-populating email block (a live roster, a product list). The custom-tag API and its argument syntax aren't obvious, and the tag renders as literal text in the drag-and-drop editor — which reads as "broken."
**Why:** Register with `mailster_add_tag( 'name', $callback )` on the `mailster_add_tag` action. The matching regex (`placeholder.class.php`) allows only `[a-z0-9-_]` in the tag name and parses **one** colon argument — the form is `{name:option}` (or `{name:option|fallback}`), **not** HTML-attribute syntax like `{name foo="bar"}`. Callback signature: `( $option, $fallback, $campaign_id, $subscriber_id )`, returning an HTML string. Tags resolve at **send / preview / test-send only** — the editor shows the raw shell by design.
**Fix / pattern:** One parameterized tag, many uses — pack a mode plus an optional limit into the single option (`{roster:female}`, `{roster:los-angeles,9}`) and branch inside the callback.
**To send a test programmatically** (mirrors `ajax.class.php::send_test()`): `sanitize_content($html,null)` → `mailster('placeholder',$c)` (`set_campaign`/`add_defaults`/`add_custom`) → `get_content()` → `helper->prepare_content()` → `inline_css()` → `strip_structure_html()` (**this** is what strips the editor-only `<module>/<single>/<multi>/<buttons>` tags) → `apply_filters('mailster_campaign_content', …)` → `mailster('mail')->send()`.
**First seen:** NLTA, 2026-06-16 — a dynamic roster tag for a custom campaign template.

## WooCommerce

*New section at the VMG harvest, 2026-07-15. Woo-domain entries live here — including Bricks/Woo interop — because the symptom is always "my Woo surface is wrong", and this catalog is consulted by symptom.*

### Bricks — `{woo_product_price}` outputs `price_html` and renders as HTML in a Basic Text element
**Symptom / When:** You want a Woo price in a card, styled (large amount, small interval).
**Why / Fix:** `{woo_product_price}` returns Woo's `price_html` and renders as HTML (not escaped) in a `text-basic`. Structure: amount in `.woocommerce-Price-amount` (nested `.woocommerce-Price-currencySymbol`), subscription suffix in `.subscription-details`. Style by targeting those Woo classes from the card's price class; mirror the markup in mockups so the CSS transfers.
**First seen:** VMG, 2026-06-06 — a hosting-plan card price.

### Bricks — the WooCommerce integration sheet loads AFTER the child theme and restyles Woo surfaces
**Symptom / When:** A custom-themed My Account nav (or other Woo surface) renders with Bricks' default look — light nav background, `line-height:60px` block links, no custom styling — even though the child-theme CSS targets the right classes and is enqueued. Same family: the order-details table shows a near-white block behind the Subtotal/Total rows even after theming the cells transparent.
**Why:** Bricks enqueues `themes/bricks/assets/css/integrations/woocommerce-layer.min.css` AFTER the child-theme `style.css`, with rules like `.woocommerce-account .woocommerce-MyAccount-navigation a{display:block;line-height:60px;padding:0 30px}` (0,2,1) and nav `background-color`/`min-width:25%`. These beat single-class child rules on both specificity and source order. The tfoot case is the same sheet: `.woocommerce-order-details table tfoot { background-color: var(--bricks-bg-light) }` (#f5f6f7) is set on the `<tfoot>` ELEMENT, not the cells — so transparent `td`/`th` let it show through.
**Fix — two routes:**
- **Opt out** when you're fully theming a surface: drop the conventional hook class (e.g. `woocommerce-MyAccount-navigation` from the `<nav>` in the `navigation.php` override; keep your own `.acct__nav`). Per-`<li>` `--{endpoint}` classes from `wc_get_account_menu_item_classes()` are unaffected. Opting out also disables Bricks' account-nav JS that would double with a custom toggle.
- **Out-specify** for a one-off rule, with the doubled-class trick (no `!important`): `.woocommerce-table--order-details.woocommerce-table--order-details tfoot { background: transparent; }` — two classes (0,2,1) beats Bricks' (0,1,2). Same family as the `@layer bricks` and ghost-border cascade fights.
**Process note (carry-forward):** this finding, the ACSS `:where(section…)` one, and the `.btn--primary` one ALL appear only with the full stylesheet cascade loaded. When verifying a PHP-rendered Woo/portal surface by headless screenshot, replicate the page's ENTIRE stylesheet set in source order (Advanced Themer → automatic.css → frontend-light-layer → child style.css → woocommerce-layer → content-default → theme-style-* → post-* → automatic-bricks). A tokens+ACSS+style.css subset gives false confidence — pull the real list from the rendered page's `<link>`s (auth-cookie curl for gated pages).
**First seen:** VMG, 2026-06-07 — a dashboard account nav rendered unstyled on the real page after a partial-CSS preview had shown it correct; the order-details tfoot the same day.

### WooCommerce — Cart/Checkout pages default to BLOCKS, which bypass classic template overrides
**Symptom / When:** You add `woocommerce/checkout/form-checkout.php` (or `cart/cart.php`) overrides + CSS, but the live page renders the default block UI and ignores your template entirely — and a curl shows a React skeleton (`is-loading`, no billing fields).
**Why:** Modern WooCommerce seeds the Cart and Checkout pages with **block** markup (`<!-- wp:woocommerce/checkout -->`, `<!-- wp:woocommerce/cart -->`), not the classic `[woocommerce_checkout]` / `[woocommerce_cart]` shortcodes. The blocks are React-hydrated and do NOT use the classic PHP templates, so theme template overrides never run. **The same fact cuts the other way:** on a Blocks checkout the classic `woocommerce_checkout_*` PHP hooks never fire either — customisation goes through Store API extension points and Blocks filters.
**Fix — decide which checkout you're building, then make the page match:**
- **Classic** (template-override theming, classic hooks): replace the page content with the shortcode — `wp post update <id> --post_content='<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'` (and `[woocommerce_cart]`). Classic templates + foundation CSS then apply.
- **Blocks** (express pay at the top, Store API, `theme.json` styling): leave the block markup and theme through `theme.json` + block filters. Do NOT add classic template overrides — they are dead code that will mislead the next person, and no classic hook will fire.
Verify which you have: `wp post get <id> --field=post_content` — `wp:woocommerce/checkout` = block, `[woocommerce_checkout]` = classic.
**First seen:** VMG, 2026-06-07 — a checkout template override produced nothing live; the page held the block, not the shortcode. That project went classic. *(The fork is real — a project may deliberately choose Blocks for express pay + Store API, in which case this entry is the reason its classic hooks never fire.)*

### WooCommerce — "Coming Soon" (Launch Your Store) gates STORE pages to non-managers; looks like a broken page
**Symptom / When:** Cart, Checkout, Pay-for-Order, Thank-you (and Shop) render a generic "Great things are on the horizon" page for logged-in customers (and a ~475 KB page weight), while My Account renders normally. Your template overrides appear to do nothing.
**Why:** WooCommerce's Launch-Your-Store "Coming soon" mode (`woocommerce_coming_soon=yes`, `woocommerce_store_pages_only=yes`) shows a placeholder on STORE pages to anyone who can't manage the store. Only admins/shop-managers bypass it — so cart/checkout must be reviewed while logged in as an admin, and the test CUSTOMER sees the placeholder.
**Fix:** For verification, view store pages as an admin (or temporarily `wp option update woocommerce_coming_soon no`, then restore). It's a deliberate build-time gate — **disable it at launch** (put it on the launch-cleanup list). Not a theming bug.
**First seen:** VMG, 2026-06-07 — order-pay/thank-you "rendered empty" via a customer cookie; it was the coming-soon placeholder.

### Verifying gated/cart-dependent Woo pages — the WC cart session can't be held over curl; render via `do_shortcode`
**Symptom / When:** Curling `/checkout/` or `/cart/` (even with a valid auth cookie) renders an empty page — no form, no items — while the product is genuinely in the customer's cart.
**Why:** Checkout/cart render against the **WC cart session**, which a plain curl chain doesn't reliably carry (the session cookie + persistent-cart merge don't survive the way a browser session does). My Account pages curl fine because they don't depend on the cart. (Also watch malformed Netscape cookie-jar lines silently dropping the auth cookie → requests fall back to logged-out.)
**Fix:** Load the cart server-side and capture the template output directly, then screenshot it under the full CSS cascade:
```php
wp_set_current_user($uid); wc_load_cart(); WC()->cart->get_cart_from_session();
if ( WC()->cart->is_empty() ) { WC()->cart->add_to_cart( $product_id ); WC()->cart->calculate_totals(); }
file_put_contents('/tmp/checkout.html', do_shortcode('[woocommerce_checkout]'));
```
A real browser with a real cart renders identically — the empty curl is a harness limitation, not a site bug. Same limitation hits order-pay + order-received: verify with `wc_get_template('checkout/form-pay.php'|'checkout/thankyou.php', array('order'=>…))`.
**First seen:** VMG, 2026-06-07 — checkout verified via `do_shortcode` (full themed form) after curl kept showing empty.

### WooCommerce — the cart's sparse 6-column table needs `table-layout: fixed`, not auto
**Symptom / When:** The classic cart row misaligns — the empty `product-remove` / `product-quantity` columns balloon (e.g. remove = 434px) while `product-name` collapses to its content, so the × and thumbnail float in a wide gap.
**Why:** With `table-layout: auto`, the browser distributes the table's free width across columns by its own heuristic; on a sparse cart (virtual product → empty quantity, single qty) it dumps the slack into the wrong columns and ignores `width` hints on the cells.
**Fix:** `table.cart { table-layout: fixed }` + explicit widths on remove/thumbnail/price/quantity/subtotal so `product-name` (no width) takes the remainder. Tighten cell `padding-inline`. (The mobile stacked layout — Woo `shop_table_responsive` — is unaffected, since cells become `display:block`.)
**First seen:** VMG, 2026-06-07 — cart row alignment; probing cell widths found auto-layout was the cause.

### WooCommerce — Woo overrides `wp_mail_from`, so transactional mail can fail DMARC even when plugin mail passes
**Symptom / When:** Mailgun/SMTP logs show DMARC failures (and rejections) for WooCommerce emails — new-account, order/invoice, password reset — while your own plugin's `wp_mail()` sends pass cleanly. SPF/DKIM/MX all validate; the records aren't the problem.
**Why:** WooCommerce sends its emails From its OWN setting, `woocommerce_email_from_address` (default = the WP admin email), **ignoring any `wp_mail_from` filter** a functionality plugin sets. If that admin address is on a different domain than the one the mail is authenticated as (Mailgun signs `d=mailer.example.com` / envelope on the sending subdomain, but Woo's From is `admin@somewhere-else.com`), neither SPF nor DKIM aligns with the From domain → DMARC fails. If that other domain publishes `p=reject`, receivers bounce the mail outright.
**Fix:** Set Woo's From to an address on the authenticated sending domain (org-domain match = relaxed DMARC alignment), matching whatever `wp_mail_from` uses:
```bash
wp option update woocommerce_email_from_address 'noreply@example.com'
wp option update woocommerce_email_from_name 'Site Name'
```
Never assume a plugin's `wp_mail_from` covers WooCommerce — it's a separate From source. Verify with a mail-tester.com send routed through `wp eval 'wp_mail(...)'` and confirm `dkim=pass / spf=pass / dmarc=pass`.
**First seen:** VMG, 2026-06-07 — Woo mail went out From the admin's own unrelated domain, which publishes `p=reject`, while Mailgun authenticated the client's sending subdomain → rejected outright. Fixed by pointing Woo's From at an address on the authenticated domain; verified 10/10 on mail-tester.

### WooCommerce — brand transactional emails via SETTINGS, not template overrides (esp. with `email_improvements` ON)
**Symptom / When:** You need WooCommerce emails on-brand. Tempting to copy `email-header.php`/`email-styles.php` into the theme — don't: `email-styles.php` is version-sensitive and an outdated override silently breaks email layout.
**Why / how it works (Woo 10.x):** Check `FeaturesUtil::feature_is_enabled('email_improvements')` first — it's ON by default on fresh 10.x and changes the model: the header band background = the **body** color (so the header is LIGHT, not the base color — your header logo must read on white/light), links **auto-follow the base color**, and it exposes extra native settings: `woocommerce_email_footer_text_color`, `_header_alignment`, `_header_image_width`, `_font_family` (a curated email-safe list — web fonts can't load in email, so body falls back to a system face). The whole palette flows from `woocommerce_email_base_color`. CTAs render as text-links, not filled buttons.
**Fix / levers:** Set the options (`base_color` → brand accent, `background_color`/`body_background_color`/`text_color`/`footer_text_color`, `header_alignment`, `header_image_width`, `header_image`) — that alone themes everything; no override needed. **Email CSS can't use `var()`** — for polish, use literal hexes via the `woocommerce_email_styles` filter (appends after Woo's CSS) or `woocommerce_email_content_type`. **The header image MUST be a raster (PNG/JPG) — SVG is stripped by virtually every email client.**
**Multipart gotcha:** to add a plaintext part (clears SpamAssassin `MIME_HTML_ONLY`), set each email's `email_type` to `multipart` in its `woocommerce_{id}_settings` option (loop `WC()->mailer()->get_emails()` to do it in bulk). **But `WC_Email::get_email_type()` silently returns `'plain'` if `DOMDocument` is missing** — setting multipart without ext-dom would downgrade emails to plain text and DROP the HTML/branding. Verify `class_exists('DOMDocument')` first.
**Verify without a client:** render with `EmailPreview` (`\Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview` → `set_email_type('WC_Email_...')->render()`), send a real branded email via `wp_mail`, confirm acceptance via the Mailgun events API (`/v3/{domain}/events?recipient=`), and score auth/spam on mail-tester. **CLI caveat:** `is_ssl()` is false under WP-CLI, so emails rendered/sent via CLI emit some `http://` asset URLs → harmless 301s that don't occur on real web-triggered sends. EmailPreview uses fabricated line items, so a placeholder image / `example.com` link in a CLI test is preview-only.
**First seen:** VMG, 2026-06-08 — all transactional email themed via settings only (light theme, centered PNG logomark @180px, text-link CTAs); a broken SVG header replaced; 41 emails flipped to multipart (DOMDocument confirmed). mail-tester: DKIM valid + SPF pass, SpamAssassin -1.1.

### WooCommerce — admin-created customers get WP's PLAIN email, not the branded WC welcome
**Symptom / When:** You onboard a client from wp-admin → Users → Add New (role Customer, "send notification" checked) and they receive WordPress's generic plain-text "set your password" email — not the branded WooCommerce `customer_new_account` email that checkout buyers get.
**Why:** WooCommerce's branded `customer_new_account` email fires on `woocommerce_created_customer`, dispatched only by `wc_create_new_customer()` — used by checkout, My Account registration, and `wp wc customer create`. The wp-admin "Add New User" screen uses `wp_insert_user()` directly, so WC never fires and WP's own new-user notification sends instead.
**Fix (bridge in a plugin):** hook `user_register`; if `is_admin()` (and not ajax/REST — those paths aren't `is_admin`, so no double-send) and the new user has the `customer` role and the core `send_user_notification` checkbox was set, (a) suppress WP's plain email and (b) send WC's branded one with a set-password link:
```php
// suppress WP's plain email for this user
add_filter( 'wp_new_user_notification_email', fn( $e ) => array_merge( $e, array( 'to' => '' ) ), 99 );
// send the branded WC welcome WITH a set-password link (3rd arg = $password_generated)
WC()->mailer()->get_emails()['WC_Email_Customer_New_Account']->trigger( $user_id, '', true );
```
`trigger( $id, '', true )` makes the email include the "set your password" link, pointing at the **themed My Account reset page** (`/my-account/lost-password/?action=newaccount&key=…&login=…`), not raw wp-login. Emptying `to` is the cleanest core-safe way to cancel WP's email (no clean "skip" filter exists). CLI `wp wc customer create` needs none of this — it already fires the branded email.
**Account model (who needs an account):** subscriptions **force** account creation at checkout (WCS, no guest subs) — so custom service subscriptions REQUIRE the admin-invite path. One-off **invoices don't need an account** if billed as a **guest order** (paid via the secure order-pay link, no login); but an order **assigned to a registered customer requires that customer to log in to pay** (`woocommerce_order_received_verify_known_shoppers` defaults true, gating both order-received and order-pay). Sequencing trap: assigning a first invoice to a brand-new customer means they must set their password before they can log in to pay it.
**First seen:** VMG, 2026-06-08 — verified end-to-end (created a customer → branded set-password welcome → delivered → user deleted). Password-at-checkout set via `woocommerce_registration_generate_password=no`; public registration left off.

### WooCommerce Subscriptions — create subscription products from WP-CLI
**Symptom / When:** Need to create recurring/subscription products programmatically (seeding plans, migrations). A plain `WC_Product_Simple` has no recurring price.
**Why:** Woo Subscriptions registers a `subscription` product type + `WC_Product_Subscription` class; the recurring terms live in `_subscription_*` meta. Setting the product-type term alone isn't enough — use the class so the data store wires it.
**Fix (run via `wp eval-file`, idempotent by SKU):**
```php
$p = new WC_Product_Subscription();
$p->set_name('Basic Hosting'); $p->set_status('publish');
$p->set_regular_price('24.95'); $p->set_virtual(true); $p->set_sku('basic-plan');
$p->update_meta_data('_subscription_price','24.95');
$p->update_meta_data('_subscription_period','month');        // day|week|month|year
$p->update_meta_data('_subscription_period_interval','1');
$p->update_meta_data('_subscription_length','0');            // 0 = until cancelled
$p->update_meta_data('_subscription_sign_up_fee','0');
$p->update_meta_data('_subscription_trial_length','0');
$id = $p->save();
```
`get_price_html()` then renders "$24.95 / month". Guard re-runs with `wc_get_product_id_by_sku()`. Card content (tagline, feature bullets) is cleaner as an ACF group on `product` than as Woo attributes.
**First seen:** VMG, 2026-06-05 — seeded hosting plans from the live site's data.

### WooCommerce Subscriptions — manual gateways (COD) are hidden on subscription carts until "Accept Manual Renewals" is on
**Symptom / When:** Checkout for a subscription product shows "Sorry, it seems there are no available payment methods which support subscriptions," even though a gateway (e.g. COD) is enabled and works for simple products.
**Why:** WCS only offers gateways that support automatic recurring payments for a subscription purchase — UNLESS manual renewals are accepted, which lets manual gateways (COD, BACS, cheque) qualify. Stripe etc. support automatic and always show; COD does not.
**Fix:** `update_option('woocommerce_subscriptions_accept_manual_renewals','yes')` (WooCommerce → Settings → Subscriptions → "Accept Manual Renewals"). For Local testing with COD this is required to reach the place-order step. Revisit when the real automatic gateway is added — you may turn manual renewals back off.
**First seen:** VMG, 2026-06-07 — COD enabled for checkout testing but absent from a subscription checkout until manual renewals were accepted.

### WooCommerce Subscriptions — the staging-site lock silently SKIPS all automatic renewals after a Local→production migration
**Symptom / When:** On a freshly-migrated production site (e.g. a Duplicator restore from a Local build), automatic subscription renewals never charge. The renewal order is created but left unpaid with the note *"Payment processing skipped - renewal order created on staging site under staging site lock. Live site is at http://<old-local-url>"*; the subscription goes on-hold; **the gateway is never called** (no PaymentIntent). Looks like a card failure — it isn't.
**Why:** WCS stores the "real" site URL in option `wc_subscriptions_siteurl`, encoded with a `_[wc_subscriptions_siteurl]_` marker so search-replace tools can't rewrite it on migration. When the live URL differs from that lock, `WCS_Staging::is_duplicate_site()` returns true and WCS disables automatic payments — a deliberate guard against a clone double-charging customers. After a migration the lock still points at the old (Local) URL, so production is treated as the clone.
**Fix:** On production after migration, mark it live so WCS re-locks to the production URL. Admin: the "This is a live site / allow automatic payments" notice button. CLI (mirrors that button exactly):
```php
wp eval 'WCS_Staging::set_duplicate_site_url_lock();'
wp eval 'var_dump( WCS_Staging::is_duplicate_site() );'   // expect false
```
**Add this to the deploy checklist for ANY Local→server migration carrying subscriptions** (cross-referenced from `04`'s Cutover section). Verify renewals actually charge by firing `do_action("woocommerce_scheduled_subscription_payment", <sub_id>)` on a test subscription and confirming a captured charge (HPOS: use `wcs_get_subscription()` / `wc_get_order()`, not `wp post meta`).
**First seen:** VMG, 2026-06-07 — the first off-session renewal test silently skipped under the lock (sub forced on-hold, no gateway call); renewals charged cleanly once the lock was reset to the production URL.

### WooCommerce Subscriptions — admin-created ("manual") orders NEVER spawn a subscription, even for subscription products
**Symptom / When:** A client pays a manually-created order containing a subscription product (branded invoice email → order-pay link → card charged, card saved) — but no subscription appears anywhere: nothing in WooCommerce → Subscriptions, nothing scheduled in Action Scheduler, and billing silently stops after that one payment. No error, no admin notice; the gateway shows a clean one-time charge.
**Why:** WCS creates subscriptions inside the **checkout pipeline only**. Orders created in wp-admin (`created_via=admin` in `wc_order_operational_data`) or programmatically never pass through checkout, so no `shop_subscription` is spawned — the product's `_subscription_*` meta is inert in a manual order. Paying the order tokenizes the card (`_stripe_customer_id`/`_stripe_source_id` land on the ORDER) but attaches it to nothing recurring.
**Fix:** Never start from "create order" for recurring work. Two correct flows:
- **Existing customer:** create the SUBSCRIPTION first (WooCommerce → Subscriptions → Add subscription: pick customer, add product line item, adjust price if bespoke) → subscription actions → **Create pending parent order** → send that order's payment link / "Email invoice". On payment the card is force-saved (WCS requires it for subscription orders), the sub auto-activates, and the renewal schedules itself.
- **New client:** a hidden duplicate product at their price + a checkout link `https://<site>/checkout/?add-to-cart=<id>` — checkout creates account + subscription + card token in one pass (WCS forces registration even with guest checkout on).
- **Retroactive repair** (if it already happened): `wcs_create_subscription()` with `order_id` = the paid order, `add_product()`, copy addresses from the parent, `set_payment_method('stripe')` + copy `_stripe_customer_id`/`_stripe_source_id` meta from the parent order, `set_requires_manual_renewal(false)`, `update_dates(['next_payment'=>…])`, `update_status('active')`. Then VERIFY a pending `woocommerce_scheduled_subscription_payment` row exists in `*_actionscheduler_actions` for the new sub id — the repair is not done until that row exists.
**First seen:** VMG, 2026-07-14 — a $95/mo hosting order was admin-created and paid; the customer had no subscription and month 2 would never have charged. Caught ~3 weeks after the fact during unrelated cron hardening. Retro-created the subscription against the paid order; verified the scheduled-payment row.

## WS Form

### WS Form's PHP API needs `wp_set_current_user(1)` from WP-CLI — reads included
**Symptom / When:** Any WS Form API call from `wp eval-file` throws `Uncaught Exception: Insufficient user capabilities (read_form)` → "critical error on this website".
**Why:** WS Form gates its API on capabilities (`read_form`/`create_form`/…) via `WS_Form_Common::user_must()`. WP-CLI runs as user 0, which has none. Same class of gotcha as the Bricks meta-write block — and note it gates **reads**, not just writes.
**Fix:** `wp_set_current_user(1);` as the first line of any script touching the WS Form API.
**First seen:** TAB, 2026-06-26 — a `db_read()` in a tracking-field build fataled the site.

### WS Form `WS_Form_Field::db_create()` needs an EMPTY label or the field gets ZERO meta
**Symptom / When:** Building a field via the PHP API (`$f->type='checkbox'; $f->label='X'; $f->db_create();`) produces a field row with **no meta at all** (0 rows in `wsf_field_meta`) and a warning `Undefined variable $field_type_config`. The field renders broken — no choices, no required, no width.
**Why:** In `db_create()` the per-type config lookup and the subsequent `build_meta_data()` only run **inside the `if ($this->label === '')` block**. Presetting a non-empty label skips loading the config, so `build_meta_data` runs against an undefined config and writes nothing.
**Fix:** Leave `label` empty on `db_create()` so WS Form loads the type config and auto-builds full default meta. Set the label afterward on the read-back object, then `db_update_from_object()`:
```php
$f = new WS_Form_Field(); $f->form_id = $fid; $f->section_id = $sid; $f->type = 'checkbox';
$f->db_create();   // label '' → full meta auto-built
// then set label + overrides on the form object and db_update_from_object()
```
**First seen:** TAB, 2026-05-30 — a first build pass set labels pre-create; choice fields came back with 0 meta. Re-created with empty labels → 37/34 meta keys including the choice grids.

### WS Form — choices live in `data_grid_checkbox`/`data_grid_radio`; the export JSON is the importable artifact
**Symptom / When:** You need to set choice options programmatically and want a reusable form artifact, but WS Form has **no WP-CLI command** and no per-type default-meta accessor.
**Why / shape:** Each choice field stores options in a `data_grid_<type>` meta key: `{rows_per_page, group_index, default:[…checked values], columns:[{id,label}], groups:[{id,label,rows:[{id,data:[label,value]}]}]}`. Default-checked options go in the grid's top-level `default` array (by value). Field width = `meta.breakpoint_size_75` (12-col). Form-level actions live in `form.meta.action` as a data-grid of `[ActionLabel, json-encoded {id,meta,events}]` rows — **JSON-string-encoded inside the data cell**, so `json_decode` → mutate → `wp_json_encode` back. (URLs inside are JSON-escaped: `\/path\/`.)
**Fix / workflow:** `db_create` → sections → fields (empty label) → `db_read` → set labels/meta/`data_grid_*`/`meta.action` → `db_update_from_object` → `db_publish`. All of it needs `wp_set_current_user(1)`. Then `db_read(true,true)` + `wp_json_encode` → save as `*.wsf.json`, which is WS Form's native import format for other environments.
**⚠️ Verify in a real browser, NOT curl** — WS Form renders fields **client-side** (JS hydrates a `wsf-form-canvas` skeleton), so curl shows the canvas and section labels but few or no `<input>`s. That is expected, not a failure.
**First seen:** TAB, 2026-05-30 — a quote form built entirely via the API and exported as a re-importable artifact; the curl output nearly read as a broken build.

### WS Form — skin it by overriding root `--wsf-form-*` vars, never by writing rules against `.wsf-field` / `.wsf-label`
**Symptom / When:** Hand-authored rules (`.wsf-form .wsf-field {…}`, `.wsf-checkbox label`, `[data-checkbox-style=button]`, `.wsf-label-required`) either lose to WS Form's own skin or match nothing at all. Several of those names **don't exist** — `.wsf-checkbox`/`.wsf-radio` wrappers, `data-checkbox-style` and `.wsf-label-required` are invented (real: choices render in `.wsf-fieldset`; the required marker is `.wsf-required-wrapper`).
**Why:** WS Form's **Styler** (form carries `data-wsf-style-id="N"`) builds its entire skin — 800+ component vars — by deriving them from a small set of root theme vars (`--wsf-form-color-base|base-contrast|primary|secondary|accent|neutral|danger`, `--wsf-form-font-*`), auto-generating shades via `color-mix()`. Crucially the roots are declared at **`:where([data-wsf-style-id="N"])` — ZERO specificity**, deliberately, so author overrides win. Writing component-level rules fights a system designed to be driven from the roots.
**How the derivation works (why remapping the root tier is enough).** The component layer *derives* from ~10 semantic roots (`--wsf-form-color-base`, `-base-contrast`, `-primary`, `-accent`, `-neutral`, `-secondary`, `-success`/`-info`/`-warning`/`-danger`) via `var()` references and `color-mix()` ramps (`--wsf-form-color-primary-dark-20: color-mix(in oklab, var(--wsf-form-color-primary), #000 20%)`). Override a root and every derived var recomputes automatically — `color-mix()` re-evaluates against your value, because `var()` resolves to the element's computed value. Each skin compiles to `uploads/ws-form/css/public/public.style.{id}.css`.
**Fix:** Remap the **root** vars to your design tokens on a plain `.wsf-form { }` block — beats `:where()` trivially, applies to all forms, keeps WS Form's tested layout and a11y intact. Override targeted component vars only where a flat recolor isn't enough (label/legend font-family, field border/radius, focus). Find the real names from the served page:
```bash
curl -s <url> | grep -oE '\-\-wsf-[a-z0-9-]+\s*:'
```
**Specifics worth knowing.** `--wsf-form-color-base-contrast` is the "light text on a coloured fill" role (button text → near-white) — **not** a literal contrast of base. On a DARK theme, field *text* (`--wsf-field-color`) must be remapped separately from field *border* (`--wsf-field-border-color`): both default to `var(--wsf-form-color-base)` but need different values. `--wsf-field-box-shadow-width-focus: 0` gives border-only focus. The submit button renders as `.wsf-button-primary`, drawing the `--wsf-field-button-primary-*` tier (bg = primary, text = base-contrast) — no need to touch the neutral tier. Button typography is fully var-driven (`--wsf-field-button-font-family`/`-weight`/`-letter-spacing`/`-text-transform`). Keep the block a **bridge** (roots → your tokens) and reshade by editing the tokens. Fields are JS-injected at runtime, so a form can't be verified over curl — confirm visually.
Choice fields render as chips only when the field meta `checkbox_style`/`radio_style` = `button`; submit picks the primary family via `class_field_button_type=primary`.
**First seen:** TAB, 2026-05-31 — a ~130-line hand-written `.wsf-*` skin with dead selectors, fighting the Styler. Replaced by a ~25-declaration root remap that branded inputs/labels/legends/help/submit/chips in one pass.

### WS Form — per-field-type CSS loads AFTER the theme; the native checkbox IS the styled box (sibling of the label, no wrap mode)
**Symptom / When:** Child-theme CSS overriding a WS Form field rule doesn't take at equal specificity. And trying to put the choice `<input>` *inside* its `<label>`, or drawing your own checkbox box, fights WS Form.
**Why:** (1) WS Form enqueues per-field-type stylesheets (`ws-form-public-checkbox.css`, …) **after** the theme, so on equal specificity it wins on source order. (2) WS Form **styles the native input itself**: `input[type=checkbox].wsf-field { appearance:none; … }` IS the visible box, `:checked::after` is the checkmark, driven by `--wsf-field-checkbox-*` vars. The input is `position:absolute` and is a **sibling rendered before** `label.wsf-label`. There is **no input-inside-label wrap mode** — don't try to nest it.
**Fix:** Beat the source-order win with the doubled-class trick (`.scope.scope [data-row-checkbox] > input.wsf-field + label.wsf-label {…}`). Don't hand-draw a box with `::before` — brand WS Form's native one via the vars. For a clickable card option: keep `label.wsf-label` as the full-width card (override its `margin-left:0`, add left padding), set the row `position:relative`, and absolutely-position the input inside the card's gutter. Add a scoping class per field via the `class_field_wrapper` meta.
**First seen:** TAB, 2026-05-31 — matching choice fields to a wireframe (card grid + pills); needed doubled-class to beat WS Form's checkbox CSS, and a hand-drawn `::before` box turned out to be redundant.

### WS Form CAPTCHA (Turnstile/reCAPTCHA) keys live in the global `ws_form` option, not the field meta
**Symptom / When:** You add a Turnstile field but its meta `turnstile_site_key`/`turnstile_secret_key` read empty — looks unconfigured, yet the live form works.
**Why:** WS Form (1.11.x) stores CAPTCHA keys **globally** in the `ws_form` settings option; the per-field meta is only an override and is normally blank. Server-side validation is automatic when a Turnstile field is present and keys are set.
**Fix:** Read from `get_option('ws_form')` (regex `turnstile_(site|secret)_key`), not the field. Real Turnstile keys start `0x4AAA…` (test keys are `1x000…`). **Validate a secret without solving a challenge:**
```bash
curl -s https://challenges.cloudflare.com/turnstile/v0/siteverify -d 'secret=<key>&response=dummy'
# error-codes:["invalid-input-response"] = secret recognised (good)
# error-codes:["invalid-input-secret"]   = bad key
```
**First seen:** TAB, 2026-06-27 — verifying a Turnstile setup; field meta was empty and the dummy-token trick confirmed the secret without a browser.

### WS Form Pro implements Turnstile natively — and Cloudflare "Turnstile Spin" is redundant on any plugin stack
**Symptom / When:** Adding Cloudflare Turnstile to a form. The Cloudflare dashboard prompts you to "set up siteverify with Spin", which reads like a required step, and Spin will happily generate backend code for you.
**Why:** Two things are being conflated. **`siteverify` is not new and not optional** — `https://challenges.cloudflare.com/turnstile/v0/siteverify` is the server-side validation call that every Turnstile integration must make, and it is what the *secret* key is for (the client widget alone protects nothing). **WS Form Pro already makes that call**, via `WS_FORM_TURNSTILE_ENDPOINT` in `ws-form.php` — so on this stack it is already done. **Spin** (new in 2026, docs updated 2026-07-23 — postdates common model knowledge cutoffs, so it will not be in an assistant's memory) is a *setup convenience* that creates the widget, issues the keys, and **injects a siteverify call into your backend**, via dashboard / `wrangler turnstile widget create` / a prompt pasted into an AI coding agent. Cloudflare's own docs state it is not required and deploys nothing on your behalf.
**Fix:** On any site whose form plugin handles CAPTCHA validation (WS Form, Fluent Forms, Gravity), **do not run Spin.** Its output lands as either dead code in the site plugin or a second validation path competing with the plugin's own. Create the widget in the dashboard, put the keys where the plugin expects them, and stop. In WS Form the global keys live in the **`ws_form` option** as `turnstile_site_key` / `turnstile_secret_key`; the `turnstile` **field type** declares `required_setting_global_meta_key`, so a field added to any form inherits the global keys automatically.
**⚠️ Add every hostname to the widget's domain list** — production *and* staging. Turnstile validates against that list, so a production-only widget makes staging fail in a way indistinguishable from a broken configuration.
**First seen:** Highland, 2026-08-04 — the dashboard prompted for Spin after the widget was created manually; the plugin source showed siteverify was already wired.


### A client-rendered form cannot be verified with `curl` — check the CAPTCHA provider's own call count instead
**Symptom / When:** Verifying a CAPTCHA integration by fetching the page and grepping for the widget markup or the provider's script. You get zero hits for `challenges.cloudflare.com` and conclude the integration is broken — or you get the markup, conclude it works, and ship a form that silently rejects every submission.
**Why:** WS Form builds its fields **client-side** from a JSON definition embedded in the page. What `curl` returns is that definition, not the rendered DOM; the provider's script is injected at runtime. **Zero `challenges.cloudflare.com` in page source is the expected result and proves nothing in either direction.** The inverse is worse: a widget that renders is no evidence at all that the token validates server-side, and that is the half that actually gates the submission.
**Fix:** Verify at the provider, not the page. **Submit the form for real, then check the widget's siteverify call count in the Cloudflare Turnstile dashboard** — a recorded call proves the token reached Cloudflare *and* validated server-side. Zero calls after a submission means the integration is not live regardless of what the page looks like. Then confirm the notification actually arrived, which closes the path end to end. This is cheaper and more conclusive than a headless browser, which only ever re-checks the client half.
**Also — position the CAPTCHA field ABOVE the submit button.** WS Form sorts by `wp_wsf_field.sort_index` and will happily place it after submit; the challenge then renders below the button and invites a click before it is solved, turning a lead into a validation error. Fix it by dragging in the WS Form UI, **not** by patching `sort_index` in the database — WS Form DB edits do not take effect until the form is republished.
**First seen:** Highland, 2026-08-04 — grepping the live pages returned `challenges.cloudflare.com: 0` on both forms and briefly read as a broken integration; the forms are client-rendered. The same inspection found the turnstile field sorted *after* submit on both forms.


## Roles & Capabilities

### Bricks builder access is admin-only by default — custom roles get NO builder access unless explicitly granted
**Symptom / When:** Creating a custom client role (e.g. "Business Manager") and wondering whether they'll see "Edit with Bricks" — or wanting to be sure a content role can't open the builder.
**Why:** `\Bricks\Capabilities::current_user_can_use_builder()` allows the builder only for administrators or roles holding `bricks_full_access` / `bricks_edit_content` (granted via Bricks → Settings → Builder Access, stored in `bricks_capabilities_permissions`). A fresh role has neither, so it gets no builder access.
**Fix:** To DENY: just don't grant those caps (the default). To GRANT: add the role via Bricks → Settings → Builder Access. Do NOT try to gate via `edit_posts` — Bricks ignores it for builder access. Verify: `wp_set_current_user($id); \Bricks\Capabilities::current_user_can_use_builder();` should be `false`.
**First seen:** AHML, 2026-06-02 — Business Manager role (`inc/roles.php`); confirmed builder denied end-to-end.

### Walling Rank Math to admins — deny `rank_math_*` caps; the editor role ships with the metabox cap
**Symptom / When:** You want Rank Math hidden from non-admin editors/clients (they use an ACF picker instead). The admin menu is already gone for them, but the post-editor SEO metabox still shows for Editors.
**Why:** RM gates its UI on `rank_math_*` caps (`current_user_can('rank_math_onpage_general')`). The editor role is granted `rank_math_onpage_general` by default, so editors see the metabox/columns/analysis. (The top-level menu is separately gated on `manage_options`.)
**Fix:** Deny all `rank_math_*` caps to non-admins at runtime — version-resilient, no role/DB mutation:
```php
add_filter( 'user_has_cap', function ( $allcaps, $caps ) {
    if ( ! empty( $allcaps['manage_options'] ) ) return $allcaps; // admins keep RM
    foreach ( (array) $caps as $c ) {
        if ( is_string( $c ) && strpos( $c, 'rank_math_' ) === 0 ) $allcaps[ $c ] = false;
    }
    return $allcaps;
}, 10, 2 );
```
**First seen:** AHML, 2026-06-02 — `inc/rank-math-admin-wall.php`; verified a throwaway editor had all `rank_math_*` denied while `edit_posts` stayed intact.

### `current_user_can('assign_terms')` is a false negative — check the taxonomy's MAPPED capability
**Symptom / When:** Auditing a custom role. `current_user_can('assign_terms')` returns false, so you conclude the role cannot categorise posts and start granting caps it does not need — commonly `manage_categories`, which also hands over term **deletion**.
**Why:** `assign_terms` is a meta capability name, not a granted one. Each taxonomy maps it to a real cap — by default `assign_terms => 'edit_posts'` while manage/edit/delete map to `manage_categories`. Nobody literally holds a cap called `assign_terms`.
**Fix:** Resolve through the taxonomy object:
```php
$tax = get_taxonomy( 'service_type' );
current_user_can( $tax->cap->assign_terms );  // 'edit_posts' → true for a content role
current_user_can( $tax->cap->manage_terms );  // 'manage_categories' → should be false
```
**First seen:** Highland, 2026-08-04 — verifying the Business Manager role; the false negative nearly justified granting `manage_categories` over a locked term set that drives both page anchors and `Service` schema.


### A CPT registered `capability_type => 'post'` makes the "blog" caps load-bearing — do not drop them on a site with no blog
**Symptom / When:** Building a scoped client role on a brochure site. The guidance (including our own Business Manager playbook) says to drop `edit_posts`/`publish_posts`/etc. when the site has no blog. You do — and the client can now edit nothing, because their actual content lives in a CPT.
**Why:** A CPT registered with `capability_type => 'post'` (the default) does not get its own capability set; it *maps onto the post caps*. So `edit_posts` grants the CPT, not a blog. The presence or absence of a blog is irrelevant to whether those caps are needed.
**Fix:** Check before pruning, and keep the caps if any CPT maps onto them — then hide the empty Posts menu instead of removing the capability:
```bash
wp eval 'foreach (["project"] as $t){ $o=get_post_type_object($t);
  printf("%s capability_type=%s\n", $t, is_array($o->capability_type)?implode("/",$o->capability_type):$o->capability_type); }'
```
```php
add_action( 'admin_menu', function () {
    if ( ! prefix_is_business_manager() ) return;
    remove_menu_page( 'edit.php' );          // empty Posts menu
    remove_menu_page( 'edit-comments.php' );
}, 9999 );
```
**⚠️ HARVEST ACTION — this amends `~/claude-config/business-manager-role-playbook.md`.** Its "Stack assumptions" and "Customization checklist per project" both say to drop blog caps when there is no blog, with no CPT caveat. That instruction is wrong for any brochure site whose content is a CPT — which is most of them. Amend the playbook at harvest; flagged here rather than edited at master mid-build.
**First seen:** Highland, 2026-08-04 — building the Business Manager role. Highland has 0 posts and no posts page, but `project` is `capability_type => 'post'`, so following the checklist literally would have shipped a client login with no editable content.


## CSS general

### Author `a { ... }` rules silently lose to the UA stylesheet's `a:link`
**Symptom / When:** A child-theme rule like `a { text-decoration: none }` has no effect — links keep the UA underline / default color.
**Why:** Browser UA stylesheets define link styling on `a:link` (specificity (0,1,1)), not bare `a` ((0,0,1)). The author rule loses on specificity regardless of source order.
**Fix:** Match the UA specificity with a pseudo-class:
```css
a:any-link { text-decoration: none; }
a:any-link:hover { text-decoration: underline; }
```
`:any-link` matches `:link` and `:visited`. Same applies to `color`. Worth standing this pattern up in every Bricks/ACSS child theme — neither Bricks nor ACSS sets a generic `a` text-decoration rule, so the UA wins by default.
**First seen:** KSCBS, 2026-05-10 — site-wide link policy; `a { text-decoration: none }` had zero effect.

### Stacking-context bugs from `transform: translateY(-50%)`
**Symptom:** A child's `z-index` will not lift it above sibling sections, even with high values.
**Why:** `transform` (any value) creates a new local stacking context. The child can only stack within its parent's context.
**Fix:** Drop the transform; use `top: 50%` plus flex/grid centering on the parent.
**First seen:** V1 baseline, 2026-05-24.

### Stretched-link cards break three silent ways — specificity, `clip-path`, absolute positioning
**Symptom / When:** A clickable-card pattern (ACSS `.clickable-parent`, or a hand-rolled stretched `::after`) renders correctly but the card is not clickable — or only a 1×1 pixel of it is.
**Why:** Three independent mechanisms, all silent:
1. **Specificity** — ACSS ships `.clickable-parent:not(a) { position: static }` at `(0,1,1)`, which beats a plain `.card { position: relative }` at `(0,1,0)`. With no positioning context the stretched pseudo has nothing to stretch to.
2. **`clip-path` on the anchor** — a visually-hidden anchor using `clip-path: inset(50%)` clips its own pseudo-elements. The `::after` renders into an invisible box regardless of how it is positioned.
3. **`position: absolute` on the anchor** — makes the anchor its own containing block, so the absolute `::after` sizes against the 1×1 anchor rather than the card.
**Fix:** Prefer the visible-anchor pattern — wrap the primary content in a real `<a>`, keep secondary links as siblings of it. No pseudo-element, no utility, no specificity fight. Where `.clickable-parent` is genuinely wanted (card has no inner links), use the compound selector `.card.clickable-parent { position: relative }` to reach `(0,2,0)`, and keep both `clip-path` and `position: absolute` off the anchor. Convention and markup live in `01` (clickable card and focus patterns); this entry is the incident record behind that rule.
**First seen:** ext-mem video site build, 2026-04 — three separate stretched-link failures in one session. Harvested from `archive/claude-code-bricks-playbook.md` §5.3 at archive time, 2026-07-24.

### `<details>` content can't be force-shown on desktop (Chrome `::details-content` content-visibility)
**Symptom / When:** A `<details>`/`<summary>` used as a responsive menu (collapsed on mobile, "always open" on desktop via CSS) renders the content HIDDEN on desktop — the CSS override to keep it open has no effect, so a desktop sidebar collapses to nothing.
**Why:** Recent Chrome hides closed-`<details>` content via a `::details-content` pseudo with `content-visibility:hidden`, not a simple `display:none` on the child. Overriding the child's `display` doesn't un-hide it, and `::details-content` support is too new/uneven to rely on.
**Fix:** For a disclosure that must be open at one breakpoint and collapsible at another, use a checkbox-controlled pattern (`input + label + list`, `:checked ~ list{display}`) or a `<button aria-expanded>` + JS toggling a class — both give full author control of `display`. Reserve `<details>` for collapse-everywhere cases.
**First seen:** VMG, 2026-06-07 — an account-nav disclosure; the desktop sidebar collapsed to nothing until it was switched to a checkbox toggle.


## Fonts

### Variable-font prep for Bricks: TTF→WOFF2 (keep axes), subset, and the opsz-instance trap
**Symptom / When:** Google Fonts downloads are TTF; Bricks Custom Fonts wants WOFF2. And a "variable" font added to Bricks may quietly be a single optical-size **instance** (wght axis only, no `opsz`), so `font-optical-sizing:auto` does nothing at display sizes.
**Why:** WOFF2 is just a compression container — `fontTools` (`f.flavor='woff2'; f.save()`) converts TTF→WOFF2 losslessly, keeping all axes (wght, opsz, ital). Full-glyph variable files are big (~210–240KB); latin-subset via `pyftsubset --unicodes=<latin range> --flavor=woff2` (keeps axes), and optionally clamp wght to used weights (`fonttools varLib.instancer wght=400:600`) to shrink (~100KB). Bricks stores faces in `bricks_font_faces` post meta (weight/style → attachment id); **variable weights point multiple weights at ONE file** (400 & 600 → same attachment), italic is a **separate file**. A file whose internal family name is e.g. "Newsreader 16pt" is the 16pt instance — no opsz.
**Fix:** Verify axes with fontTools (`'fvar' in TTFont(f)` → list `f['fvar'].axes`), don't trust the filename. For optical sizing, download the full variable font WITH the opsz axis. `@font-face` maps discrete `font-weight:400/600` to the same variable file — true weights only if the file is really variable (else "600" renders 400 outlines). `font-optical-sizing` defaults to `auto`; don't set it `none`.
**First seen:** MMHN, 2026-07-16 — first-added Newsreader files were the 16pt instance (no opsz); re-converted the full opsz+wght TTFs to latin-subset WOFF2.

### Bricks custom fonts — `bricks_font_faces` meta schema + italic key encoding (also a `02` schema-library harvest candidate)
**When:** Registering or auditing Bricks native Custom Fonts programmatically, or verifying every weight/style in a brand's type spec is actually installed.
**Why / schema:** Each custom font is a `bricks_fonts` post; its faces live in post meta `bricks_font_faces`, keyed by weight → an array of variant files. Normal weights use an INTEGER key (`700`); **italics use a STRING key `"<weight>italic"`** (e.g. `"800italic"`). Each value is `[ 0 => ['woff2' => <attachment_id>] ]` (woff/ttf keys also accepted). Discovered by reading back UI-created faces (golden rule).
```php
get_post_meta( $font_id, 'bricks_font_faces', true ) === [
  500         => [ 0 => [ 'woff2' => 14 ] ],   // normal weight  → upright file
  800         => [ 0 => [ 'woff2' => 14 ] ],
  '500italic' => [ 0 => [ 'woff2' => 16 ] ],   // italic = "<weight>italic" string key → italic file
  '800italic' => [ 0 => [ 'woff2' => 16 ] ],
];
```
**Things that bite:**
- A single **variable** woff2 can back multiple discrete weight faces — Bricks emits one `@font-face` per declared weight and the browser pins the variable `wght` axis to each, so e.g. 500/600/700/800/900 all → the same variable file render at distinct weights. But any weight you DON'T declare falls back to the nearest declared one (declare only 700/800/900 and a request for 600 renders at 700 — too heavy). Declare every weight the brand uses.
- **Italics need a separate file with an italic axis.** A typical upright variable font (Montserrat, Figtree) has NO italic axis (`fvar` carries `wght` only), so italics must be a distinct `-Italic` variable woff2 mapped to the `"<weight>italic"` keys. You cannot get real italics from the upright file (the browser would synthesize an oblique).
- Editing `bricks_font_faces` directly is NOT gated like the `_bricks_page_*_2` keys — a plain `update_post_meta` sticks (replicate the exact shape per the golden rule).
- **Verify a woff2 (variable? axes? italic?)** with fonttools in a throwaway venv — macOS system Python is PEP 668 externally-managed, so `pip install` is blocked: `python3 -m venv /tmp/fc && /tmp/fc/bin/pip install fonttools brotli` (brotli is required to open woff2), then check `"fvar" in TTFont(f)` + its axes and `OS/2.fsSelection & 0x01` (italic bit).
**First seen:** Highland, 2026-06-13 — verifying the Montserrat/Figtree install. Only 700/800/900 (Montserrat) + 400 (Figtree) were declared, all pointing to a single upright variable file, no italics. Confirmed the italic key encoding and the separate-italic-file requirement by reading back faces the client added through the Bricks UI.


## WP-CLI

### WP-CLI — inline `wp eval` fatals silently on `"{$arr[barekey]}"` in PHP 8
**Symptom / When:** A multi-line inline `wp eval '...'` appears to produce no/truncated output and its writes don't land; the PHP log later shows a fatal "in eval()'d code on line N".
**Why:** In *complex* interpolation `"{$c[h]}"` the bareword `h` is parsed as a constant (unlike *simple* `"$c[h]"`, where it's a string key). PHP 8 throws on undefined constants, fataling the whole eval mid-run — the partial stdout just looks "cut off".
**Fix:** Quote the key (`"{$c['h']}"`), or — for anything non-trivial — write a `.php` file and run `wp eval-file script.php > out.txt 2>&1` so output and fatals are captured. Default to `eval-file` for multi-step DB work.
**First seen:** VMG, 2026-06-05 — an ACSS config script silently no-opped via inline eval; identical logic worked as `eval-file`.

### WP-CLI — `is_ssl()` is false, so WooCommerce reports gateways "unavailable" and strips the Payment Methods nav
**Symptom / When:** From `wp eval`, a correctly-configured **live** payment gateway reads as unavailable — `$gateway->is_available()` returns `false`, `get_available_payment_gateways()` omits it, and `wc_get_account_menu_items()` drops the `payment-methods` endpoint — even though the live front end charges fine. Looks like a broken gateway/menu; it isn't.
**Why:** WP-CLI has no HTTP request, so `$_SERVER['HTTPS']` is unset and `is_ssl()` returns `false`. WC Stripe (and other gateways) gate **live-mode** availability on `is_ssl()`; an unavailable tokenization gateway in turn makes WooCommerce remove the `payment-methods` account menu item (it only shows when a gateway supporting saved methods is available). A pure CLI-context artifact — nothing is wrong with the config.
**Fix:** Simulate HTTPS before introspecting, then re-init gateways — or verify on the real front end (auth-cookie curl / browser). Don't trust CLI gateway-availability or account-menu output at face value.
```php
$_SERVER['HTTPS'] = 'on';
WC()->payment_gateways()->init();
$g = WC()->payment_gateways()->payment_gateways()['stripe'];
var_dump( $g->is_available() );          // now true
print_r( wc_get_account_menu_items() );   // now includes payment-methods
```
**First seen:** VMG, 2026-06-07 — after a live gateway flip, `is_available()` and the Payment Methods nav both read as missing in `wp eval`; both flipped to present once HTTPS was simulated. (The origin terminated real HTTPS, so `is_ssl()` was genuinely true on real requests.)


### Local: `wp db query` fails on the mysql socket; `wp eval`/`wp option get` work
**Symptom / When:** `wp db query "…"` errors `Can't connect to local MySQL server through socket '/tmp/mysql.sock'`, while `wp option get`, `wp eval`, `wp post list` in the same shell work.
**Why:** `wp db query` shells out to the `mysql` client, which needs the live socket path; Local's bundled MySQL uses its own socket and is only up while the site runs (and not at `/tmp/mysql.sock`). PHP-based WP-CLI commands connect via `DB_HOST`/mysqli and are unaffected. (A fully stopped Local site fails all DB access — no `mysqld`.)
**Fix:** Use PHP-path WP-CLI (`wp eval`, `wp eval-file`, `wp option get/update`, `wp post *`) for DB work on Local; avoid `wp db query`/`wp db cli`. If everything DB-related fails, start the Local site first.
**First seen:** MMHN, 2026-07-16.

### `wp media import` of SVG fails as CLI user 0 — sideload as an admin user
**Symptom / When:** `wp media import icon.svg` errors "Sorry, you are not allowed to upload this file type" even though SVG uploads work fine in wp-admin and Bricks' SVG support is enabled.
**Why:** Bricks registers the SVG mime via an `upload_mimes` filter gated by `current_user_can_upload_svg()`. WP-CLI runs as user 0, the capability check fails, and the mime never registers — same silent-capability family as the `_bricks_page_*_2` write gate.
**Fix:** Sideload inside `wp eval` with the user set first:
```php
wp_set_current_user( 1 );
require_once ABSPATH . 'wp-admin/includes/{image,file,media}.php';
$aid = media_handle_sideload( [ 'name' => 'icon.svg', 'tmp_name' => $tmp ], 0, 'icon' );
```
Bricks' sanitizer still runs on the upload. To add the icon to a custom set, append `{id: 'icon_*', name, url, setId, attachment_id}` to the `bricks_custom_icons` option (set registry = `bricks_icon_sets`).
**First seen:** Highland, 2026-07-11 — adding a Remix `star-line` icon to the MPD set for the Google-review CTA.


### `wp eval-file` dies silently (exit 0, zero output) on some script files — run via `wp eval 'include ...'`
**Symptom / When:** `wp eval-file build-x.php` returns exit 0 with no output at all — not even a first-line `WP_CLI::log()` — while `php -l` passes and small probe files in the same directory run fine. Nothing lands in the DB.
**Why:** RunCloud's bundled wp-cli phar `EvalFile_Command` strips the opening tag and `eval()`s the source. Some file contents trip a silent bailout in that eval path (mechanism unconfirmed — the failing file had a large HTML nowdoc with UTF-8 en dashes and a ~300-line element-tree array; a sibling script of similar size/shape ran fine). Truncated versions of the same file DO report parse errors, so the eval path can error loudly — this failure mode is specifically silent.
**Fix:** Run the identical file as `wp eval 'include "/path/to/build-x.php";'` — `include` compiles it as a normal PHP file and it executes correctly. Cheap habit: if an eval-file script produces no output where output is expected, don't debug the script first — rerun via include.
**First seen:** Highland, 2026-07-11 — the Request-an-Estimate page build script; identical file ran perfectly via include on the first try.

### WP-CLI — `--prompt` ECHOES the resolved command line, secret included, to stdout
**Symptom / When:** Feeding a password via `--prompt=admin_password < passfile` precisely to keep it off the command line — and wp-cli prints the fully resolved command (`--admin_password='…'` and all) to stdout, straight into logs and transcripts.
**Why:** `--prompt` is an interactive convenience, not a secrecy feature; it reprints the command it assembled before running it.
**Fix:** Keep secrets inside PHP where argv and stdout never see them: `wp eval 'wp_set_password(trim(file_get_contents("<mode-600 path>")), <user_id>);'`. Same pattern for any secret-bearing op — read from a restricted file inside the eval'd code. If a secret does get echoed, rotate it immediately; the echoed value is already in scrollback.
**First seen:** 2026-08-11.

### The WP-CLI upgrader skin ECHOES premium package URLs, licence key included — update licensed themes/plugins with `--quiet` + version readback
**Symptom / When:** `wp theme update bricks` (or any premium theme/plugin whose updater authenticates the download via the package URL) prints `Downloading update from https://…?license_key=<key>&version=…` to stdout. The licence key lands in terminal scrollback, CI logs, and AI-session transcripts — on every box the update runs on.
**Why:** WP core's `WP_Upgrader` emits a `downloading_package` feedback line carrying the **full package URL**, and WP-CLI's `UpgraderSkin` routes feedback through `WP_CLI::log()`, which prints by default. Bricks builds that URL with the licence key as a query arg (`my.bricksbuilder.io/api/commerce/download/get_theme?license_key=…`); other premium updaters may do the same — check before assuming one doesn't. Same failure family as the `--prompt` echo above: WP-CLI treating a secret-bearing string as ordinary progress output.
**Fix:** Update with `--quiet` and confirm by version readback instead of by output:
```bash
wp theme update bricks --quiet
wp theme list --name=bricks --fields=version,update_version   # readback = the confirmation
```
`--quiet` suppresses only informational log lines — real errors still reach stderr and the exit code, so failures stay loud. **Verified empirically** (positive control: a default `wp theme install` printed the package URL; the identical run under `--quiet` produced 0 bytes of output while the install landed, proven by readback). Where a rollout script should keep its progress output, redact rather than suppress: `wp theme update bricks 2>&1 | sed -E 's/license_key=[^&[:space:]]+/license_key=REDACTED/g'`. If a key has already been echoed, regenerate it at the vendor portal (my.bricksbuilder.io for Bricks) — the echoed copy is already in scrollback.
**First seen:** Nametank/ext-mem, 2026-08-12 — the first-in-fleet Bricks 2.3.11 update echoed the licence key to the session transcript. Promoted straight to master (Michael's direction) ahead of the fleet-wide 2.3.11 rollout, since the project deliberately carries no `03` copy and has no harvest to wait for.


## Diagnostic patterns

### Diagnostic JS via a Bricks code element
**When to use:** A click intercepted by something invisible, an element misbehaving, a mystery state.
**Pattern:** Add a temporary `<script>` in a Bricks code element; drop `console.log`s and a `MutationObserver` on the suspect node; reload, perform the action, read the output; iterate. Strip the script after diagnosis — do not leave console noise in production.
**First seen:** V1 baseline, 2026-05-24.

### A copy sweep over Bricks content misses `_attributes` values — aria-labels and alt text are copy too
**Symptom / When:** Find-and-replacing a term across page content (a rename, or removing something for privacy/legal reasons). The script reports every replacement made, read-back confirms the write, and the term is **still in the blob**.
**Why:** Bricks scatters human-readable strings across several settings keys, and a sweep written against `settings.text` sees only one of them. The others: `settings._attributes[].value` (aria-label, title, any hand-set attribute), `settings.altText`, image `caption`, `settings.link.title`, and `_cssId` where an id was named after the thing being renamed. `_attributes` is the usual culprit because ARIA labels are written once at build time and never looked at again.
**Fix:** Sweep by walking every string in the element, not by reading known keys — then assert the term is gone from the whole serialized blob rather than from the strings you happened to check:
```python
def walk(o, path):
    if isinstance(o, dict):  [walk(v, f'{path}.{k}') for k, v in o.items()]
    elif isinstance(o, list): [walk(v, f'{path}[{i}]') for i, v in enumerate(o)]
    elif isinstance(o, str) and TERM in o: print(path, repr(o))
```
```php
$blob = wp_json_encode( get_post_meta( $id, '_bricks_page_content_2', true ) );
printf( "'%s' still present: %s\n", $term, strpos( $blob, $term ) !== false ? 'YES' : 'no' );
```
**First seen:** Highland, 2026-07-22 — removing a township name from the site for client location privacy. The text sweep reported 1/1 hits on all four pages and passed; two pages still carried the name inside map-canvas `aria-label` attributes. A whole-blob assertion caught it; a per-key one would have shipped it.


---

# === PROJECT SECTION — "we learned" ===

*Empty at kickoff. New gotchas discovered during this project's build append below, in the entry format above. At go-live these are reviewed and the validated ones fold into the established section of the master.*
