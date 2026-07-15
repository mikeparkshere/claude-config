# 03 — Stack Gotchas

**Non-obvious behaviors of the stack, discovered the hard way. The carry-forward accumulator.**

Verified against Bricks 2.3.4 / ACSS 3.3.6. V1 baseline: 2026-05-24.

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
**Fix:** Call `wp_set_current_user( 1 )` at the top of any `wp eval` / `wp eval-file` script before writing these keys (user 1 = admin with `full_access` + code execution, so `security_check_elements_before_save()` returns your elements as-is). The `bricks_global_classes` option is not gated this way.
```php
wp_set_current_user( 1 );
$content = get_post_meta( $tmpl_id, '_bricks_page_content_2', true );
// ... mutate $content ...
update_post_meta( $tmpl_id, '_bricks_page_content_2', $content );
```
**Alternative fix — run the whole script as a builder-capable user:** `wp eval-file script.php --user=<admin-login>`. Equivalent to the `wp_set_current_user()` call and cleaner for a script that writes these keys throughout. **Either way, check the return value** — `update_post_meta` returning `false` is the *only* signal you get.
**First seen:** AHML, 2026-04-27 — appending the article section to the Blog Single template. The script printed success and exited cleanly; the element count in the DB stayed unchanged. (This is the full incident record for the auth requirement named in `00` and `02`.) Confirmed still current on Bricks 2.3+ — NLTA, 2026-07-06, injecting a native breadcrumbs element into single templates; first run reported success and saved nothing.

### Bricks Element Manager — a disabled element renders as NOTHING, even when it's in the template data
**Symptom / When:** An element is confirmed present in `_bricks_page_content_2` but produces zero frontend output — no wrapper div, no error, nothing. Looks exactly like a failed write, so you go re-debug the write path (see the entry above) and find the data is fine.
**Why:** Bricks 2.x's Element Manager (`bricks_element_manager` option) lets you disable unused elements for performance, and a disabled element is **skipped entirely at render**. Installs that have been through a performance pass can have 20+ elements disabled — so an element you never used before is exactly the one likely to be off.
**Fix:** Re-enable the one you need, then opcache reset + cache purge:
```php
$m = get_option( 'bricks_element_manager', [] );
unset( $m['breadcrumbs'] );
update_option( 'bricks_element_manager', $m );
```
**Check this BEFORE debugging a write** when a programmatically-injected element doesn't render — it's a one-line check and it's the cheaper hypothesis.
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
**First seen:** AHML, 2026-04-29 — CSS sweep deleted `btn--outline` as orphan; it kept rendering. No actual breakage.

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
**First seen:** V1 baseline, 2026-05-24.

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

### ACSS settings live in `wp_options.automatic_css_settings` (serialized) — regenerate after a DB edit
After editing ACSS settings via `wp eval` or the DB directly, open the ACSS Dashboard and click Save to trigger stylesheet regeneration. Without that step the CSS file is not rebuilt.

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
**First seen:** KSCBS, 2026-05-10 — a button padding override with a selector identical to ACSS's had no effect.

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
- **Pair with the Perfmatters entry below** — Delay JS *and* Defer JS each independently kill the media modal on any page with an `acf_form()` uploader.
**First seen:** NLTA, 2026-07-06 — a gated front-end profile submission form.

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

### Enabling a Rank Math module via the `rank_math_modules` option does NOT create its DB tables
**Symptom / When:** You enable an RM module programmatically by appending its slug to `rank_math_modules` (e.g. `redirections`). The module reports active, but using it fails — `\RankMath\Redirections\DB::add()` returns `0`; log shows `Table 'wp_rank_math_redirections' doesn't exist`.
**Why:** Rank Math creates a module's tables in its module-activation path (the UI toggle / `Installer`), not when the option value changes. Writing the option directly skips table creation.
**Fix:** Call the installer for the active module set after toggling the option:
```php
\RankMath\Installer::create_tables( (array) get_option( 'rank_math_modules', array() ) );
```
`create_tables()` is public/static and idempotent (dbDelta). Note `\RankMath\Redirections\Cache::purge()` requires an argument — don't call it bare; a plain `wp cache flush` is enough.
**First seen:** AHML, 2026-06-02 — enabled Redirections by editing the option; the `/areas/ → home` redirect insert silently no-op'd until `Installer::create_tables()` ran.

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

## Perfmatters

### Perfmatters — a list option written as a STRING via CLI = sitewide 500s, with the full page body
**Symptom / When:** Pages return HTTP 500 but render the **complete** page body (right down to `</html>`). Only *some* pages 500 — those where Remove Unused CSS needs to regenerate — so pages with a warm RUCSS cache still return 200 and the breakage **spreads gradually** as caches expire. Nothing in the nginx error log.
**Why:** Perfmatters processes the page in an **output-buffer callback at `shutdown`** (`Buffer::process` → `CSS::optimize`). Its list-type options (`rucss_excluded_selectors`, `rucss_excluded_stylesheets`, …) are **arrays** — the settings UI explodes textarea input into arrays on save. Setting one to a plain string via `wp option patch` / `wp eval` makes `array_merge($defaults, $string)` throw a TypeError *inside the ob callback*: the buffered HTML has already been generated (so the full page ships) but headers haven't been sent (so PHP sets 500). **Wrong status + right body = the fatal-in-ob-callback signature.**
**Fix:** Re-save the option as an array, then opcache reset + cache purge:
```php
$o = get_option( 'perfmatters_options' );
$o['assets']['rucss_excluded_selectors'] = [ '.brx-a11y-hidden' ];
update_option( 'perfmatters_options', $o );
```
**Diagnosis playbook — full body + 500 (generalizes to ANY ob-callback fatal):**
1. Hook the `status_header` filter — if it never fires, the 500 isn't being set via WP.
2. Log `http_response_code()` at hooks through `shutdown` (priority `PHP_INT_MIN` and `PHP_INT_MAX`) — if the `PHP_INT_MAX` checkpoint never logs, a fatal occurred during `shutdown`.
3. `ini_set('log_errors','1'); ini_set('error_log', WP_CONTENT_DIR.'/trace.log');` in a temporary mu-plugin gated by a query arg — captures the fatal FPM otherwise swallows.
4. Remember opcache (`validate_timestamps=0`) — reset it before each retest, or you're testing stale code. See `04`.
**General rule:** when writing any plugin option from CLI, **read it first and preserve its shape.** A string where an array is expected is a silent, delayed, sitewide outage.
**First seen:** NLTA, 2026-07-06 — `rucss_excluded_selectors` saved as a bare string; every single-post page 500'd for ~35 min (Googlebot included) while cached pages masked it.

### Perfmatters — Delay JS *and* Defer JS EACH independently kill the WP media modal
**Symptom / When:** On any front-end page with a WP uploader (an `acf_form()` image/gallery field), "Add Image" buttons and drag-drop do nothing. Console shows two distinct failure modes: `media-views` crashing on a missing `wp.i18n`, and `acf is not defined` / `tinymce is not defined` from inline scripts.
**Why:** Two separate ordering breaks in one dependency chain — **fixing one is not enough:**
1. **Delay JS** with `/wp-includes/js/dist/` in its inclusions holds `wp-i18n`/`wp-hooks` until user interaction, but deferred `media-views.js` needs `wp.i18n` at DOM-ready → throws, and `wp.media` never initializes.
2. **Defer JS** defers the external `acf-input.min.js` / `editor.min.js` / TinyMCE files while their **inline companion scripts** (`acf.data = …`, `*-js-after` bootstraps) still run immediately → `acf`/`tinymce` undefined; the modal later dies in `acf.getMimeType` because `acf.data`'s mime map never loaded.
**Diagnosis:** grep the page HTML for `pmdelayedscript` vs `defer` on `dist/i18n`, `acf-input`, `media-views`.
**Fix — disable both, per page, from the site plugin (keeps the optimizations sitewide):**
```php
$off = fn( $on ) => is_page( 'submit-form-slug' ) ? false : $on;
add_filter( 'perfmatters_delay_js', $off );
add_filter( 'perfmatters_defer_js', $off );
```
**First seen:** NLTA, 2026-07-06 — a front-end submission form's Add Image was dead; the same config already carried a `ws-form` delay exclusion for the same failure class.

## Mailster + Mailgun

### Mailster/Mailgun — `mailgun_track` overrides the Mailgun dashboard toggle and breaks SSL on the tracking subdomain
**Symptom / When:** Recipients report an SSL/cert error when clicking links in a campaign. The broken URL is a Mailgun click-tracking redirect (`https://email.mg.<domain>/c/…`) — **even though the Mailgun dashboard shows Click/Open tracking "Off."**
**Why:** Mailster's Mailgun add-on stores a `mailgun_track` option (empty, `opens`, `clicks`, or `opens,clicks`). When set, Mailster passes `o:tracking-opens=yes` / `o:tracking-clicks=yes` **per message** — and the per-message API flags **override the domain-level dashboard toggle**. Mailgun then rewrites links through the tracking hostname; if HTTPS was never enabled for it (which it wasn't, *because the dashboard toggle is off*), there's no LE cert → cert errors for every recipient. Compounding: Mailster also has its own tracking that rewrites through the WordPress domain, so links get **double-wrapped** — Mailster wraps first, Mailgun wraps that, and the broken HTTPS host ends up outermost.
**Diagnosis:** `wp option get mailster_options --format=json` → inspect `mailgun_track`.
**Fix (default — keep Mailster-side tracking only):** `wp option patch update mailster_options mailgun_track ""`. Mailster's own tracking rides the working WordPress domain and is what feeds its campaign reports anyway.
**Only if the client actually wants Mailgun analytics:** enable HTTPS on the tracking domain in the Mailgun dashboard; confirm the `email.mg` CNAME is **DNS-only / grey cloud** in Cloudflare (Mailgun cannot provision LE certs through CF's proxy); wait up to ~24h for the cert; then turn off Mailster's own tracking to avoid double-counting.
**First seen:** NLTA, 2026-04-29 — client reported an SSL error clicking a campaign link.

### Mailster — custom dynamic tags use `{tag:option}` syntax and resolve at SEND time, not in the editor
**Symptom / When:** Building an auto-populating email block (a live roster, a product list). The custom-tag API and its argument syntax aren't obvious, and the tag renders as literal text in the drag-and-drop editor — which reads as "broken."
**Why:** Register with `mailster_add_tag( 'name', $callback )` on the `mailster_add_tag` action. The matching regex (`placeholder.class.php`) allows only `[a-z0-9-_]` in the tag name and parses **one** colon argument — the form is `{name:option}` (or `{name:option|fallback}`), **not** HTML-attribute syntax like `{name foo="bar"}`. Callback signature: `( $option, $fallback, $campaign_id, $subscriber_id )`, returning an HTML string. Tags resolve at **send / preview / test-send only** — the editor shows the raw shell by design.
**Fix / pattern:** One parameterized tag, many uses — pack a mode plus an optional limit into the single option (`{roster:female}`, `{roster:los-angeles,9}`) and branch inside the callback.
**To send a test programmatically** (mirrors `ajax.class.php::send_test()`): `sanitize_content($html,null)` → `mailster('placeholder',$c)` (`set_campaign`/`add_defaults`/`add_custom`) → `get_content()` → `helper->prepare_content()` → `inline_css()` → `strip_structure_html()` (**this** is what strips the editor-only `<module>/<single>/<multi>/<buttons>` tags) → `apply_filters('mailster_campaign_content', …)` → `mailster('mail')->send()`.
**First seen:** NLTA, 2026-06-16 — a dynamic roster tag for a custom campaign template.

## ShortPixel

### ShortPixel — WebP/AVIF images don't render in Outlook desktop, and there's no JPEG to fall back to
**Symptom / When:** Images are blank or broken boxes in Outlook desktop (Windows) email, but fine in Gmail and Apple Mail. Site media is served as `.webp` / `.avif`.
**Why:** Outlook desktop uses the Word rendering engine, which can't display WebP/AVIF. Worse, ShortPixel's CDN delivery (`spcdn.shortpixel.ai/spio/…,to_auto,s_webp:avif/…`) serves WebP **even to clients that never send `Accept: image/webp`** when the origin file is itself `.webp` + `to_auto` — there is no JPEG fallback. ShortPixel only optimizes *toward* next-gen formats; `to_jpg` against a webp origin just 307-redirects back to the webp. **So no JPEG exists anywhere to link to.**
**Fix:** Transcode to JPEG server-side and serve it directly:
1. GD (`imagecreatefromwebp()` → flatten onto white → `imagejpeg()`), cached under `uploads/<prefix>-email/`, regenerated only when the source is newer. (Check `function_exists('imagecreatefromwebp')` — Imagick was absent on this box; GD had WebP read support.)
2. **Exclude that cache path from ShortPixel** so it can't re-optimize and flip it back: append to `wpSPIO()->settings()->excludePatterns` → `['type'=>'path','value'=>'<prefix>-email','apply'=>'all','validated'=>true]`.
3. Safety net: filter the campaign content late (priority 999) to strip any `spcdn.shortpixel.ai/spio/<dir>/` wrapper off the JPEG URLs, so a CDN rewrite can't undo the fix.
**Verify:** `curl -s -o /dev/null -D - -H "Accept: image/png,image/*;q=0.8" "<url>" | grep -i content-type` — must return `image/jpeg`, not `image/webp`.
**First seen:** NLTA, 2026-06-16 — roster images in an email campaign broke in Outlook.

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

## Diagnostic patterns

### Diagnostic JS via a Bricks code element
**When to use:** A click intercepted by something invisible, an element misbehaving, a mystery state.
**Pattern:** Add a temporary `<script>` in a Bricks code element; drop `console.log`s and a `MutationObserver` on the suspect node; reload, perform the action, read the output; iterate. Strip the script after diagnosis — do not leave console noise in production.
**First seen:** V1 baseline, 2026-05-24.

---

# === PROJECT SECTION — "we learned" ===

*Empty at kickoff. New gotchas discovered during this project's build append below, in the entry format above. At go-live these are reviewed and the validated ones fold into the established section of the master.*

## VMG Client Portal

*Discovered during the VMG Client Portal build (2026-06 → 2026-07). Awaiting go-live harvest review; validated entries fold up into ESTABLISHED.*

### Bricks — register utility global-class "anchors" from a plugin so they show in the picker
**Symptom / When:** A child-theme utility class (`.display`, `.eyebrow`) works on the front end but doesn't appear in the Bricks class picker, so it can't be assigned in the builder.
**Why:** The picker lists entries in the `bricks_global_classes` option, not whatever exists in CSS. The class needs an entry there (CSS stays in the theme).
**Fix:** Seed empty "anchor" entries idempotently from the functionality plugin (on `admin_init`, hash-guarded so it writes once). Per the verified Global Class shape: `settings` MUST be `array()`; IDs 6-char alphanumeric, never all-numeric. `bricks_global_classes` writes are **not** cap-gated (no `wp_set_current_user` needed). Anchors carry empty settings → emit no CSS; the theme `style.css` supplies the actual rules. Keeps the plugin (anchors, version-controlled) and theme (CSS) split clean.
**First seen:** VMG, 2026-06-05 — `vmg-portal`'s `bricks-global-classes.php` seeder for `.display`/`.eyebrow`/etc.

### Bricks — typed settings written via WP-CLI emit for some control types, silently not for others
**Symptom / When:** Converting global-class `_cssCustom` to typed settings by a direct `bricks_global_classes` option write + `Assets_Files::regenerate_css_files()`. Some typed keys produce CSS; others persist in the option but emit nothing (no error, readback looks fine).
**Why:** The option→CSS generator handles control types unevenly on this path. Verified Bricks 2.3.6. **Emit:** `_padding`/`_margin` (type `spacing`), `_typography`, `_border` (incl. `radius` with `var()`), `_background`, `_display` (`select`), `_alignItems`/`_justifyContent`. **Do NOT emit from a CLI write:** `_gap`/`_width`/`_widthMax`/`_heightMin` (type `number`+`units`) and `_flexDirection` (type `direction`) — the builder's own save owns that generation.
**Fix:** Type everything in the emit set via CLI. For `number+units`/`direction`, either set them in the builder, or keep them as token-based `_cssCustom` (no magic numbers). Verify every typed write against the rendered page — silent no-emit is the failure mode.
**First seen:** VMG, 2026-06-06 — Contact page typed conversion; gap/flex-direction/max-width dropped silently.

### Bricks — empty `text-basic` renders nothing; use `block`/`div` for decorative empties
**Symptom / When:** A `text-basic` with `text:''` (a CSS-only dot, accent bar, counter holder) produces no DOM output.
**Why:** `text-basic` skips render on empty text; `block`/`div` render their wrapper regardless.
**Fix:** Decorative empty element → `block` with `tag:'custom'`+`customTag:'span'` (or div), not `text-basic`.
**First seen:** VMG, 2026-06-06 — Contact status dot vanished as a `text-basic`.

### Bricks — `html` element is the ungated raw-markup injector; global-class CSS only emits for in-use classes
**Symptom / When:** Need to inject static HTML (form stub, embed); and classes referenced only inside that HTML get no CSS.
**Why:** The `html` element (`settings.html`) just `echo`s markup — no capability gate (the `code` element gates execution). Bricks emits a global class's CSS **only if a real Bricks element uses that class**; classes living only inside raw-HTML strings are never collected.
**Fix:** Use `html` for raw markup. For CSS targeting raw-HTML-only classes, bundle those rules into the `_cssCustom` of a class that IS on a real element (the wrapping card/panel) so they emit.
**First seen:** VMG, 2026-06-06 — Contact form stub; field CSS rode on `.contact__form-panel`.

### Bricks — `{woo_product_price}` outputs price_html, rendered as HTML in a Basic Text element
**Symptom / When:** Want a Woo (subscription) price in a card, styled (large amount, small interval).
**Why / Fix:** `{woo_product_price}` returns Woo's `price_html` and renders as HTML (not escaped) in a `text-basic`. Structure: amount in `.woocommerce-Price-amount` (nested `.woocommerce-Price-currencySymbol`), subscription suffix in `.subscription-details`. Style by targeting those Woo classes from the card's price class; mirror the markup in mockups so the CSS transfers.
**First seen:** VMG, 2026-06-06 — Hosting Plans price.

### Convention — prefer native Bricks elements (Divider/Icon/SVG) over DIY div+CSS
**Symptom / When:** Tempted to build a hairline as a `block` + `_cssCustom` `height:1px;background`, or an icon as a CSS-masked pseudo.
**Why:** Native elements are UI-editable, self-documenting, and survive handoff — same reasoning as typed-settings over `_cssCustom`. A custom div for a line is invisible/unmanageable in the builder.
**Fix:** Native **Divider** for rules, **Icon**/**SVG** elements for glyphs. Reserve DIY+CSS for genuinely non-native needs.
**First seen:** VMG, 2026-06-06 — Mike replaced DIY `.plan-card__divider` divs with native Divider elements.
*(Harvest note: this is a **convention**, not a gotcha — per `00`'s write protocol it belongs in `01` once confirmed. Left here pending a deliberate promotion decision at VMG's true go-live; it is a sibling of the typed-settings rule.)*

### Bricks — flex/grid + gap that arranges BEM children belongs on the Container, not the single-child Section
**Symptom / When:** You put `display:flex; flex-direction:column; gap` (or grid+gap) on the **Section** to stack its content, but the gap has no effect and the children sit flush.
**Why:** In Section > Container > BEM, the Section's only child is the Container. `gap` only spaces an element's *direct children* — gap on the Section spaces `[the Container]` (one item → no effect). The intro/grid/status that need spacing are children of the **Container**, so the flex/grid+gap must live there. (`justify-content`/`align-items` for *centering* the single Container, by contrast, do belong on the Section.)
**Fix:** Put the content-stacking layout (flex/grid + gap) on the Container — give it a BEM class to hold it. Keep the Section as the full-bleed stage (background, min-height). Cleanest: move the whole stage (min-height + flex column + gap + centering) onto the Container and leave the Section as a thin background wrapper.
**First seen:** VMG, 2026-06-06 — Home split landing; `gap` on `.home` (section) was orphaned; moved the layout to `.home__container` (Mike classed the container for exactly this).

### ACSS/Bricks — `.btn--primary` visual CSS is NOT global on the frontend; it's generated per Bricks Button element
**Symptom / When:** A plain `<a class="btn btn--primary">` in a PHP-rendered template (dashboard, Woo override) renders unstyled — mono font only, no violet fill/padding/border — while the same classes look right on Bricks-built pages.
**Why:** On the frontend the `.btn--primary`/`.btn--outline` color/padding/border CSS is emitted per-element by the Bricks Button element at render. It is NOT in any globally-enqueued stylesheet (only `automatic-gutenberg.css`, block-editor-only, plus the child theme's mono-font-only `.btn` rule). No Bricks Button on the page = no button CSS.
**Fix:** For PHP/non-Bricks surfaces, define the button CSS yourself, scoped so it can't collide with Bricks' per-element CSS on builder pages (e.g. `.woocommerce .btn--primary{background:var(--primary);color:var(--white);…}`). Consume `--primary`/`--white`/`--primary-hover` to match the ACSS button.
**First seen:** VMG, 2026-06-07 — dashboard CTAs rendered as bare links until portal `.btn` CSS was added under `.woocommerce`.

### Bricks — the WooCommerce integration sheet loads AFTER the child theme and restyles My Account
**Symptom / When:** A custom-themed My Account nav (or other Woo surface) renders with Bricks' default look — light nav background, `line-height:60px` block links, no custom styling — even though the child-theme CSS targets the right classes and is enqueued.
**Why:** Bricks enqueues `themes/bricks/assets/css/integrations/woocommerce-layer.min.css` AFTER the child-theme `style.css`, with rules like `.woocommerce-account .woocommerce-MyAccount-navigation a{display:block;line-height:60px;padding:0 30px}` (0,2,1) and nav `background-color`/`min-width:25%`. These beat single-class child rules on both specificity and source order.
**Fix:** When you're fully theming the surface, opt OUT rather than fight per-rule: drop the conventional hook class (here `woocommerce-MyAccount-navigation` from the `<nav>` in the navigation.php override; keep your own `.acct__nav`). Per-`<li>` `--{endpoint}` classes from `wc_get_account_menu_item_classes()` are unaffected. Opting out also disables Bricks' account-nav JS that would double with a custom toggle.
**Process note (carry-forward):** ACSS-section, `.btn`, and this nav finding ALL appear only with the full stylesheet cascade loaded. When verifying a PHP-rendered Woo/portal surface by headless screenshot, replicate the page's ENTIRE stylesheet set in source order (Advanced Themer → automatic.css → frontend-light-layer → child style.css → woocommerce-layer → content-default → theme-style-* → post-* → automatic-bricks). A tokens+ACSS+style.css subset gives false confidence — pull the real list from the rendered page's `<link>`s (auth-cookie curl for gated pages).
**First seen:** VMG, 2026-06-07 — dashboard account nav rendered unstyled on the real page; a partial-CSS preview had shown it correct.

### Bricks/HTML — `<details>` content can't be force-shown on desktop (Chrome `::details-content` content-visibility); don't use it for responsive disclosure
**Symptom / When:** A `<details>`/`<summary>` used as a responsive menu (collapsed on mobile, "always open" on desktop via CSS) renders the content HIDDEN on desktop — the CSS override to keep it open has no effect, so a desktop sidebar collapses to nothing.
**Why:** Recent Chrome hides closed-`<details>` content via a `::details-content` pseudo with `content-visibility:hidden`, not a simple `display:none` on the child. Overriding the child's `display` doesn't un-hide it; `::details-content` support is too new/uneven to rely on.
**Fix:** For a disclosure that must be open at one breakpoint and collapsible at another, use a checkbox-controlled pattern (`input + label + list`, `:checked ~ list{display}`) or a `<button aria-expanded>` + JS toggling a class — both give full author control of `display`. Reserve `<details>` for collapse-everywhere cases.
**First seen:** VMG, 2026-06-07 — account-nav disclosure; the desktop sidebar collapsed to nothing until switched to a checkbox toggle.

### Bricks — woo-layer paints `.woocommerce-order-details table tfoot` bricks-bg-light (a white box on dark)
**Symptom / When:** The order-details table (thank-you, view-order) shows a near-white block behind the Subtotal/Total rows, even after theming the cells transparent.
**Why:** `integrations/woocommerce-layer.min.css` sets `.woocommerce-order-details table tfoot { background-color: var(--bricks-bg-light) }` (#f5f6f7) on the `<tfoot>` ELEMENT (not the cells), and loads AFTER the child theme. Transparent `td`/`th` let the tfoot bg show through. Its selector is (0,1,2) and out-sources style.css.
**Fix:** Beat it with the doubled-class trick (no `!important`): `.woocommerce-table--order-details.woocommerce-table--order-details tfoot { background: transparent; }` — 2 classes (0,2,1) outranks Bricks' 1-class (0,1,2). (Same family as the woo-nav and ghost-border layered-cascade fights.)
**First seen:** VMG, 2026-06-07 — thank-you / view-order order-details tfoot.

### Bricks — header/footer TEMPLATE content lives in `_bricks_page_header_2` / `_bricks_page_footer_2`, not `_bricks_page_content_2`
**Symptom / When:** You write a built element tree to a header or footer template (`bricks_template`, `_bricks_template_type` = header/footer) via `_bricks_page_content_2`, readback confirms the elements, regen succeeds — but the template renders NOTHING on the front end (the `<header>`/`<footer>` landmark is absent or empty).
**Why:** Bricks keys template content by template TYPE. A page/single template uses `_bricks_page_content_2`; a header template uses `_bricks_page_header_2`; a footer template uses `_bricks_page_footer_2`. Writing the tree to `_content_2` on a footer template stores valid data on a key the footer renderer never reads — so it's a silent no-render (distinct from the cap-gated silent no-WRITE; here the write lands, on the wrong key).
**Fix:** Match the key to the template type. Confirm with `wp post meta get <id> _bricks_template_type` first, then write to the matching `_bricks_page_{content|header|footer}_2`. Cross-check by reading an existing working template of the same type (e.g. the header) — its content key tells you which one the renderer reads.
**First seen:** VMG, 2026-06-07 — footer template 34 rendered empty until the tree moved from `_bricks_page_content_2` to `_bricks_page_footer_2`.

### Bricks — build a whole template section as a native element tree, NOT one `html` element (golden rule, even with no builder in front of you)
**Symptom / When:** Tempted to ship a footer/header/section by dropping the entire markup into a single Bricks `html` element (`settings.html`) with all styling in the child theme. It renders fine on first load — but the whole section is invisible and uneditable in the Bricks UI (no element tree, no typed panels, no global classes), which is the exact handoff failure the pipeline exists to prevent. It also skips the golden rule entirely.
**Why:** The `html` element is a raw-markup injector for genuinely non-native bits (embeds, form stubs) — not a substitute for the `SECTION > CONTAINER > BEM` element tree. The reflex to use it (or to hand-author markup + `style.css`) usually comes from "I can't open the builder from CLI, so I can't discover schemas." But the golden rule's discovery source doesn't have to be a fresh builder session: any EXISTING builder-made template is a verified example. The site header, and any already-built page, hold the exact verified shapes (`section`/`container`/`block`/`svg`/`text-basic`, typed `_typography`/`_padding`/`_border`/`fill`, the ACSS color-object `{raw:'var(--x)'}`, query-loop, link) — read them back and replicate.
**Fix:** Harvest schemas from existing builder output: `wp post meta get <header_id> _bricks_page_header_2 --format=json` and a built page's `_bricks_page_content_2`; plus the global classes those elements reference (`bricks_global_classes`). Build the section's element tree from those shapes via WP-CLI. Reserve `html` for true raw-markup needs only. (Companion: a footer Legal column became a Query Loop over the `vmg_legal` CPT — auto-syncing — instead of a hardcoded list, once built as real elements.)
**First seen:** VMG, 2026-06-07 — footer first shipped as one `html` blob + `style.css`; rebuilt as a 44-element native tree (typed settings on 24 global classes, logo lockup replicated element-for-element from the header, Legal as a `vmg_legal` query loop).

### ACSS — configure the palette/settings programmatically via `Database_Settings::save_settings()`
**Symptom / When:** You need to set the ACSS palette or any Dashboard setting from WP-CLI (no UI access) and have the stylesheets regenerate. Hand-editing the `automatic_css_settings` option (2000+ keys) directly is error-prone and does NOT rebuild the CSS.
**Why:** ACSS stores settings in the `automatic_css_settings` option and regenerates via `CSS_Engine::generate_all_css_files()`. `Database_Settings::save_settings()` is the exact path the Dashboard "Save" uses — it validates, persists, and regenerates in one call.
**Fix:**
```php
wp_set_current_user( 1 ); // save_settings requires the manage_options cap
$ds   = \Automatic_CSS\Model\Database_Settings::get_instance();
$vals = $ds->get_vars();                 // current FULL settings array
$vals['color-primary'] = '#842abf';      // hex = source of truth for parent --primary (+ --primary-h/s/l)
// Derived shades are stored independently — to fully recolor, rewrite each shade's H and S
// (keep the -l lightness targets): primary-{hover,ultra-light,light,semi-light,medium,semi-dark,dark,ultra-dark}-h / -s
$vals['vp-max']      = 1280;             // -> --content-width (px ÷ root = rem)
$vals['base-radius'] = '6px';            // -> --radius
$ds->save_settings( $vals, true );        // true = validate + regenerate all CSS files
```
Back up first: `wp option get automatic_css_settings --format=json > backup.json`. The contextual/dark-scheme vars (`--body-bg-color`, `--text-color`, `--h1`, `--space-*`) are better overridden in a child-theme `:root` bridge that loads after `automatic.css` — `automatic-bricks.css` loads last but only *consumes* vars (no `:root` blocks), so the bridge wins the cascade.
**First seen:** VMG, 2026-06-05 — configured the violet/obsidian/ink palette + content-width + radius entirely from CLI.

### ACSS — configure a DARK-FIRST site via a child-theme `:root` bridge (it wins the cascade)
**Symptom / When:** ACSS is light-first (`--body-bg-color` = white, `--text-color`/`--neutral` = near-black). A dark-first brand needs the default surface dark + light text, plus VMG type/spacing on top of the palette.
**Why:** All ACSS variables are defined in `automatic.css` (handle loads early). `automatic-bricks.css` loads **last** but contains **no `:root` blocks** — it only *consumes* vars (`background: var(--body-bg-color, …)`). So a child-theme `:root` block loading after `automatic.css` overrides the contextual/type vars and nothing later re-defines them. Verified by checking every `.css` in `<head>` for who *defines* (not uses) `--body-bg-color`/`--h1`/`--primary`.
**Fix:** Two layers — (1) set the 3 palette colors (`color-primary/base/neutral`) in ACSS settings (see the `save_settings` entry); (2) a child-theme `:root` bridge for the rest:
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
**First seen:** VMG, 2026-06-05 — dark-first violet/obsidian portal on ACSS 3.3.6.

### ACSS — `:where(section…)` makes any hand-rendered `<section>` flex-column-centred; `section > div` forces its children to column
**Symptom / When:** A plugin/PHP-rendered card built as `<section class="card">` (with `<div>` children) renders with content horizontally centred and spread vertically, and direct-child rows you set `display:flex` come out stacked as columns — though your CSS never says so. Shows only on real pages (ACSS loaded), not in a stripped mockup.
**Why:** ACSS ships `section:where(:not(.bricks-shape-divider)){display:flex;flex-direction:column;align-items:center;gap:…}` and `section > div:where(…){display:flex;flex-direction:column;align-items:flex-start;gap:…}`. Intended for Bricks sections, but they match ANY top-level `<section>` and its direct `<div>` children. They use `:where()` (specificity 0,0,1 / 0,0,1) so they're trivially overridden — but ONLY for properties you explicitly declare; relying on element defaults (no `display`/`flex-direction`) lets ACSS win.
**Fix:** On hand-authored sections, declare the layout explicitly: `.card{display:block}` and `flex-direction:row` on every direct-child flex row (`.card__head`, etc.). Don't rely on element defaults inside a `<section>` on an ACSS site.
**First seen:** VMG, 2026-06-07 — My Account dashboard cards (`<section class="card">`) rendered centred + stacked; the login card too.

## WooCommerce

### WooCommerce Subscriptions — create subscription products from WP-CLI
**Symptom / When:** Need to create recurring/subscription products programmatically (seeding plans, migrations). A plain `WC_Product_Simple` has no recurring price.
**Why:** Woo Subscriptions registers a `subscription` product type + `WC_Product_Subscription` class; the recurring terms live in `_subscription_*` meta. Setting the product-type term alone isn't enough — use the class so the data store wires it.
**Fix (run via `wp eval-file`, idempotent by SKU):**
```php
$p = new WC_Product_Subscription();
$p->set_name('Basic Hosting'); $p->set_status('publish');
$p->set_regular_price('24.95'); $p->set_virtual(true); $p->set_sku('vmg-basic');
$p->update_meta_data('_subscription_price','24.95');
$p->update_meta_data('_subscription_period','month');        // day|week|month|year
$p->update_meta_data('_subscription_period_interval','1');
$p->update_meta_data('_subscription_length','0');            // 0 = until cancelled
$p->update_meta_data('_subscription_sign_up_fee','0');
$p->update_meta_data('_subscription_trial_length','0');
$id = $p->save();
```
`get_price_html()` then renders "$24.95 / month". Guard re-runs with `wc_get_product_id_by_sku()`. Card content (tagline, feature bullets) is cleaner as an ACF group on `product` than Woo attributes.
**First seen:** VMG, 2026-06-05 — seeded 2 hosting plans from the live site's data.

### WooCommerce — Cart/Checkout pages default to BLOCKS, which bypass classic template overrides
**Symptom / When:** You add `woocommerce/checkout/form-checkout.php` (or `cart/cart.php`) overrides + CSS, but the live page renders the default block UI and ignores your template entirely — and a curl shows a React skeleton (`is-loading`, no billing fields).
**Why:** Modern WooCommerce seeds the Cart and Checkout pages with the **block** markup (`<!-- wp:woocommerce/checkout -->`, `<!-- wp:woocommerce/cart -->`), not the classic `[woocommerce_checkout]` / `[woocommerce_cart]` shortcodes. The blocks are React-hydrated and DO NOT use the classic PHP templates, so theme template overrides never run.
**Fix:** For a classic, template-override-themed checkout (the MPD/VMG approach), replace the page content with the shortcode: `wp post update <id> --post_content='<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->'` (and `[woocommerce_cart]`). Then the classic templates + foundation CSS apply. Verify with `wp post get <id> --field=post_content` — if you see `wp:woocommerce/checkout`, it's still the block.
**First seen:** VMG, 2026-06-07 — checkout override produced nothing live; page 17 held the checkout block, not the shortcode.

### WooCommerce Subscriptions — manual gateways (COD) are hidden on subscription carts until "Accept Manual Renewals" is on
**Symptom / When:** Checkout for a subscription product shows "Sorry, it seems there are no available payment methods which support subscriptions," even though a gateway (e.g. COD) is enabled and works for simple products.
**Why:** WCS only offers gateways that support automatic recurring payments for a subscription purchase — UNLESS manual renewals are accepted, which lets manual gateways (COD, BACS, cheque) qualify. Stripe etc. support automatic and always show; COD does not.
**Fix:** `update_option('woocommerce_subscriptions_accept_manual_renewals','yes')` (WooCommerce → Settings → Subscriptions → "Accept Manual Renewals"). For Local testing with COD this is required to reach the place-order step. Revisit when the real automatic gateway (Stripe) is added — you may turn manual renewals back off.
**First seen:** VMG, 2026-06-07 — COD enabled for checkout testing but absent from a subscription checkout until manual renewals were accepted.

### Verifying gated/cart-dependent Woo pages — the WC cart session can't be held over curl; render via do_shortcode
**Symptom / When:** Curling `/checkout/` or `/cart/` (even with a valid auth cookie) renders an empty page — no form, no items — while the product is genuinely in the customer's cart.
**Why:** Checkout/cart render against the **WC cart session**, which a plain curl chain doesn't reliably carry (the session cookie + persistent-cart merge don't survive the way a browser session does). My Account pages curl fine because they don't depend on the cart. (Also watch malformed Netscape cookie-jar lines silently dropping the auth cookie → requests fall back to logged-out.)
**Fix:** To verify cart/checkout theming headlessly, load the cart server-side and capture the template output directly, then screenshot it under the full CSS cascade:
```php
wp_set_current_user($uid); wc_load_cart(); WC()->cart->get_cart_from_session();
if ( WC()->cart->is_empty() ) { WC()->cart->add_to_cart( $product_id ); WC()->cart->calculate_totals(); }
file_put_contents('/tmp/checkout.html', do_shortcode('[woocommerce_checkout]'));
```
A real browser with a real cart renders identically — the empty curl is a harness limitation, not a site bug.
**First seen:** VMG, 2026-06-07 — checkout verified via do_shortcode (full themed form) after curl kept showing empty. (Same limitation hits order-pay + order-received — verify with `wc_get_template('checkout/form-pay.php'|'checkout/thankyou.php', array('order'=>...))`.)

### WooCommerce — "Coming Soon" (Launch Your Store) gates STORE pages to non-managers; looks like a broken page
**Symptom / When:** Cart, Checkout, Pay-for-Order, Thank-you (and Shop) render a generic "Great things are on the horizon" page for logged-in customers (and a ~475 KB page weight), while My Account renders normally. Your template overrides appear to do nothing.
**Why:** WooCommerce's Launch-Your-Store "Coming soon" mode (`woocommerce_coming_soon=yes`, `woocommerce_store_pages_only=yes`) shows a placeholder on STORE pages to anyone who can't manage the store. Only admins/shop-managers bypass it — so cart/checkout/etc. must be reviewed while logged in as an admin, and the test CUSTOMER sees the placeholder.
**Fix:** For verification, view store pages as an admin (or temporarily `wp option update woocommerce_coming_soon no`, then restore). It's a deliberate build-time gate; **disable it at launch** (in the launch-cleanup list). Not a theming bug.
**First seen:** VMG, 2026-06-07 — order-pay/thank-you "rendered empty" via the customer cookie; it was the coming-soon placeholder.

### WooCommerce — the cart's sparse 6-column table needs `table-layout: fixed`, not auto
**Symptom / When:** The classic cart row misaligns — the empty `product-remove` / `product-quantity` columns balloon (e.g. remove = 434px) while `product-name` collapses to its content, so the × and thumbnail float in a wide gap.
**Why:** With `table-layout: auto`, the browser distributes the table's free width across columns by its own heuristic; on a sparse cart (virtual product → empty quantity, single qty) it dumps the slack into the wrong columns and ignores `width` hints on the cells.
**Fix:** `table.cart { table-layout: fixed }` + explicit widths on remove/thumbnail/price/quantity/subtotal so `product-name` (no width) takes the remainder. Tighten cell `padding-inline` and set the subscription price cells to `--f-small` so "$X / month" stays one line. (The mobile stacked layout — Woo `shop_table_responsive` — is unaffected since cells become `display:block`.)
**First seen:** VMG, 2026-06-07 — cart row alignment; probed cell widths to find auto-layout was the cause.

### WooCommerce Subscriptions — the staging-site lock silently SKIPS all automatic renewals after a Local→production migration
**Symptom / When:** On a freshly-migrated production site (e.g. Duplicator restore from a Local build), automatic subscription renewals never charge. The renewal order is created but left unpaid with the note *"Payment processing skipped - renewal order created on staging site under staging site lock. Live site is at http://<old-local-url>"*; the subscription goes on-hold; **the gateway is never called** (no PaymentIntent in Stripe). Looks like a Stripe/card failure — it isn't.
**Why:** WCS stores the "real" site URL in option `wc_subscriptions_siteurl`, encoded with a `_[wc_subscriptions_siteurl]_` marker so search-replace tools can't rewrite it on migration. When the live URL differs from that lock, `WCS_Staging::is_duplicate_site()` returns true and WCS disables automatic payments — a deliberate guard against a clone double-charging customers. After a migration the lock still points at the old (Local) URL, so production is treated as the clone.
**Fix:** On production after migration, mark it live so WCS re-locks to the production URL. Admin: the "This is a live site / allow automatic payments" notice button. CLI (mirrors that button exactly):
```php
wp eval 'WCS_Staging::set_duplicate_site_url_lock();'
wp eval 'var_dump( WCS_Staging::is_duplicate_site() );'   // expect false
```
Add to the deploy checklist for ANY Local→server migration carrying subscriptions. Verify renewals actually charge by firing `do_action("woocommerce_scheduled_subscription_payment", <sub_id>)` on a test subscription and confirming a captured charge (HPOS: use `wcs_get_subscription()` / `wc_get_order()`, not `wp post meta`).
**First seen:** VMG, 2026-06-07 — first off-session renewal test silently skipped under the lock (sub forced on-hold, no Stripe call); renewals charged cleanly once the lock was reset to vmgdma.com.

### WooCommerce — Woo overrides `wp_mail_from`, so transactional mail can fail DMARC even when plugin mail passes
**Symptom / When:** Mailgun/SMTP logs show DMARC failures (and rejections) for WooCommerce emails — new-account, order/invoice, password reset, subscription notices — while your own plugin's `wp_mail()` sends pass cleanly. SPF/DKIM/MX all validate in Mailgun; the records aren't the problem.
**Why:** WooCommerce sends its emails From its OWN setting, `woocommerce_email_from_address` (default = the WP admin email), **ignoring any `wp_mail_from` filter** a functionality plugin sets. If that admin address is on a different domain than the one the mail is authenticated as (e.g. Mailgun signs `d=mailer.example.com` / envelope on the sending subdomain, but Woo's From is `admin@somewhere-else.com`), neither SPF nor DKIM aligns with the From domain → DMARC fails. If that other domain publishes `p=reject`, receivers bounce the mail outright.
**Fix:** Set Woo's From to an address on the authenticated sending domain (org-domain match = relaxed DMARC alignment), matching whatever `wp_mail_from` uses:
```bash
wp option update woocommerce_email_from_address 'noreply@example.com'
wp option update woocommerce_email_from_name 'Site Name'
```
Don't assume a plugin's `wp_mail_from` covers WooCommerce — it's a separate From source. Verify with a mail-tester.com send routed through `wp eval 'wp_mail(...)'` and confirm `dkim=pass / spf=pass / dmarc=pass`.
**First seen:** VMG, 2026-06-07 — Woo mail went out From the admin's `michaelparks.me` (which has `p=reject`) over Mailgun's `mailer.vmgdma.com` auth → rejected; fixed by pointing Woo's From at `noreply@vmgdma.com`. Verified 10/10 on mail-tester.

### WooCommerce — brand transactional emails via SETTINGS, not template overrides (esp. with `email_improvements` ON)
**Symptom / When:** You need WooCommerce emails on-brand. Tempting to copy `email-header.php`/`email-styles.php` into the theme — don't: `email-styles.php` is version-sensitive and an outdated override silently breaks email layout.
**Why / how it works (Woo 10.x):** Check `FeaturesUtil::feature_is_enabled('email_improvements')` first — it's ON by default on fresh 10.x and changes the model: the header band background = the **body** color (so the header is LIGHT, not the base color — your header logo must read on white/light), links **auto-follow the base color**, and it exposes extra native settings: `woocommerce_email_footer_text_color`, `_header_alignment`, `_header_image_width`, `_font_family` (curated email-safe list — Space Grotesk/web fonts can't load in email, so body falls back to a system face). The whole palette flows from `woocommerce_email_base_color`. CTAs render as **violet text-links**, not filled buttons.
**Fix / levers:** Set the options (`base_color` → brand accent, `background_color`/`body_background_color`/`text_color`/`footer_text_color`, `header_alignment`, `header_image_width`, `header_image`) — that alone themes everything; no override needed. **Email CSS can't use `var()`** — if you must add polish use literal hexes via the `woocommerce_email_styles` filter (appends after Woo's CSS) or `woocommerce_email_content_type`. **Header image MUST be a raster (PNG/JPG) — SVG is stripped by virtually every email client.** No `var()`, inlined table CSS only.
**Multipart gotcha:** to add a plaintext part (clears SpamAssassin `MIME_HTML_ONLY`), set each email's `email_type` to `multipart` in its `woocommerce_{id}_settings` option (what the admin UI toggles; loop `WC()->mailer()->get_emails()` to do it in bulk). **But `WC_Email::get_email_type()` silently returns `'plain'` if `DOMDocument` is missing** — setting multipart without ext-dom would downgrade emails to plain text and DROP the HTML/branding. Verify `class_exists('DOMDocument')` first.
**Verify without a client:** render with `EmailPreview` (`\Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview` → `set_email_type('WC_Email_...')->render()`), send a real branded email via `wp_mail` (plugin From filters + Mailgun SMTP), confirm acceptance via the **Mailgun events API** (`/v3/{domain}/events?recipient=`), and score auth/spam on mail-tester. **CLI caveat:** `is_ssl()` is false under WP-CLI, so emails rendered/sent via CLI emit some `http://` asset URLs (e.g. the product placeholder) → harmless 301s that DON'T occur on real web-triggered sends (which are https). And the EmailPreview uses fabricated line items, so a `placeholder.webp` / `example.com` link in a CLI/preview test is preview-only — real product emails use the real images (verify the products actually have featured images).
**First seen:** VMG, 2026-06-08 — themed all transactional email via settings only (base `#842ABF`, light theme, centered PNG logomark @180px, From "VMG Client Portal", text-link CTAs); replaced a broken `logomark.svg` header; flipped 41 emails to multipart (DOMDocument confirmed). mail-tester: DKIM valid + SPF pass, SpamAssassin -1.1 (clear of the -5 spam threshold).

### WooCommerce — admin-created customers get WP's PLAIN email, not the branded WC welcome
**Symptom / When:** You onboard a client from wp-admin → Users → Add New (role Customer, "send notification" checked) and they receive WordPress's generic plain-text "set your password" email — not the branded WooCommerce customer_new_account email that checkout buyers get.
**Why:** WooCommerce's branded `customer_new_account` email fires on `woocommerce_created_customer`, which is dispatched only by `wc_create_new_customer()` — used by checkout, My Account registration, and `wp wc customer create`. The wp-admin "Add New User" screen uses `wp_insert_user()` directly, so WC never fires; WP's own new-user notification sends instead.
**Fix (bridge in a plugin):** hook `user_register`; if `is_admin()` (and not ajax/REST — those other paths aren't is_admin, so no double-send) and the new user has the `customer` role and the core `send_user_notification` checkbox was set, (a) suppress WP's plain email and (b) send WC's branded one with a set-password link:
```php
// suppress WP's plain email for this user
add_filter( 'wp_new_user_notification_email', fn( $e ) => array_merge( $e, array( 'to' => '' ) ), 99 );
// send the branded WC welcome WITH a set-password link (3rd arg = $password_generated)
WC()->mailer()->get_emails()['WC_Email_Customer_New_Account']->trigger( $user_id, '', true );
```
`trigger( $id, '', true )` makes the email include the "set your password" link, which points at the **themed My Account reset page** (`/my-account/lost-password/?action=newaccount&key=…&login=…`), not raw wp-login. Emptying `to` is the cleanest core-safe way to cancel WP's email (no clean "skip" filter exists). CLI `wp wc customer create` needs none of this — it already fires the branded email.
**Account model (who needs an account):** subscriptions **force** account creation at checkout (WCS, no guest subs) — so custom service subscriptions REQUIRE the admin-invite path. One-off **invoices don't need an account** if billed as a **guest order** (paid via the secure order-pay link, no login); but an order **assigned to a registered customer requires that customer to log in to pay** (`woocommerce_order_received_verify_known_shoppers` defaults true, gates both order-received and order-pay). Sequencing trap: assigning a first invoice to a brand-new customer means they must set their password (welcome email) before they can log in to pay it.
**First seen:** VMG, 2026-06-08 — `vmg-portal/inc/accounts.php`; verified E2E (created a customer → branded set-password welcome → Mailgun delivered → user deleted). Password-at-checkout flow set via `woocommerce_registration_generate_password=no`; public registration left off.

### WooCommerce Subscriptions — admin-created ("manual") orders NEVER spawn a subscription, even for subscription products
**Symptom / When:** A client pays a manually-created order containing a subscription product (branded invoice email → order-pay link → card charged, card saved) — but no subscription appears anywhere: nothing in WooCommerce → Subscriptions, nothing scheduled in Action Scheduler, and billing silently stops after that one payment. No error, no admin notice, the gateway shows a clean one-time charge.
**Why:** WCS creates subscriptions inside the **checkout pipeline only**. Orders created in wp-admin (`created_via=admin` in `wc_order_operational_data`) or programmatically never pass through checkout, so no `shop_subscription` is spawned — the product's `_subscription_*` meta is inert in a manual order. Paying the order tokenizes the customer's card (`_stripe_customer_id`/`_stripe_source_id` land on the ORDER) but attaches it to nothing recurring.
**Fix:** Never start from "create order" for recurring work. Two correct flows:
- **Existing customer:** create the SUBSCRIPTION first (WooCommerce → Subscriptions → Add subscription: pick customer, add product line item, adjust price if bespoke) → subscription actions → **Create pending parent order** → send that order's payment link / "Email invoice". On payment the card is force-saved (WCS requires it for subscription orders), the sub auto-activates, renewal schedules itself.
- **New client:** hidden duplicate product at their price + checkout link `https://<site>/checkout/?add-to-cart=<id>` — checkout creates account + subscription + card token in one pass (WCS forces registration even with guest checkout on).
- **Retroactive repair** (if the mistake already happened): `wcs_create_subscription()` with `order_id` = the paid order, `add_product()`, copy addresses from parent, `set_payment_method('stripe')` + copy `_stripe_customer_id`/`_stripe_source_id` meta from the parent order, `set_requires_manual_renewal(false)`, `update_dates(['next_payment'=>…])`, `update_status('active')`. Then VERIFY a pending `woocommerce_scheduled_subscription_payment` row exists in `*_actionscheduler_actions` for the new sub id — the repair is not done until that row exists.
**First seen:** VMG, 2026-07-14 — a $95/mo hosting order was admin-created and paid; the customer had no subscription and month 2 would never have charged. Caught ~3 weeks after the fact, during unrelated cron hardening. Retro-created the subscription against the paid order; verified the scheduled-payment row.

## WS Form

### WS Form — theme an entire form by remapping its `--wsf-*` root vars; the skin sets them at ZERO specificity
**Symptom / When:** You need a WS Form (1.11.x) to match a design system. Writing selector overrides (`.wsf-field { … }`) is a losing game — hundreds of selectors, brittle across updates.
**Why / how it actually works:** WS Form themes everything through `--wsf-*` CSS custom properties. Each **Style (skin)** compiles to `uploads/ws-form/css/public/public.style.{id}.css`, which defines the vars scoped to `:where([data-wsf-style-id="N"])` — **specificity 0,0,0**. Critically, the whole component layer *derives* from a small **root tier of ~10 semantic colours** (`--wsf-form-color-base`, `-base-contrast`, `-primary`, `-accent`, `-neutral`, `-secondary`, `-success`/`-info`/`-warning`/`-danger`) via `var(--wsf-form-color-*)` references and `color-mix()` light/dark ramps (`--wsf-form-color-primary-dark-20: color-mix(in oklab, var(--wsf-form-color-primary), #000 20%)`).
**Fix / pattern:** A child-theme block scoped to `.wsf-form` (specificity 0,1,0) beats the `:where()` skin regardless of load order. Override the **root tier** → your tokens and every derived var recomputes automatically (the skin's `color-mix()` re-evaluates against your value, since `var()` resolves to the element's computed value). Only hand-map component literals where one root var can't serve two roles — on a DARK theme, field *text* (`--wsf-field-color` → bright ink) must differ from field *border* (`--wsf-field-border-color` → subtle ink), but both default to `var(--wsf-form-color-base)`. Useful specifics: `--wsf-field-box-shadow-width-focus: 0` kills the focus ring (border-only focus); the submit button renders as `.wsf-button-primary` so it draws the `--wsf-field-button-primary-*` tier (bg = primary, text = base-contrast) — no need to touch the neutral button tier; button typography is fully var-driven (`--wsf-field-button-font-family/-weight/-letter-spacing/-text-transform`). Note `--wsf-form-color-base-contrast` is the "light text on a coloured fill" role (button text → near-white), NOT a literal contrast of base. Keep it a **bridge** (map to tokens), edit the tokens to reshade.
**First seen:** VMG, 2026-06-08 — `bricks-child-theme/assets/css/ws-form.css` maps the root tier + field literals to the VMG tokens (fields matched to the existing Woo/account input treatment, submit to `.btn--primary`). ~25 var declarations theme the whole form. Fields are JS-injected at runtime, so the form can't be verified over curl — confirm visually (see also the Simply-Static "WS Form = empty `<form>` shell at export" note).

## WP-CLI

### WP-CLI — inline `wp eval` fatals silently on `"{$arr[barekey]}"` in PHP 8
**Symptom / When:** A multi-line inline `wp eval '...'` appears to produce no/truncated output and its writes don't land; the PHP log later shows a fatal "in eval()'d code on line N".
**Why:** In *complex* interpolation `"{$c[h]}"`, the bareword `h` is parsed as a constant (unlike *simple* `"$c[h]"` where it's a string key). PHP 8 throws on undefined constants, fataling the whole eval mid-run — the partial stdout just looks "cut off".
**Fix:** Quote the key (`"{$c['h']}"`), or — for anything non-trivial — write a `.php` file and run `wp eval-file script.php > out.txt 2>&1` so output and fatals are captured. Default to `eval-file` for multi-step DB work.
**First seen:** VMG, 2026-06-05 — the ACSS config script silently no-opped via inline eval; identical logic worked as `eval-file`.

### WP-CLI — `is_ssl()` is false, so WooCommerce reports gateways "unavailable" and strips the Payment Methods nav
**Symptom / When:** From `wp eval`, a correctly-configured **live** payment gateway reads as unavailable — `$gateway->is_available()` returns `false`, `WC()->payment_gateways()->get_available_payment_gateways()` omits it, and `wc_get_account_menu_items()` drops the `payment-methods` endpoint — even though the live front end charges fine. Looks like a broken gateway/menu; it isn't.
**Why:** WP-CLI has no HTTP request, so `$_SERVER['HTTPS']` is unset and `is_ssl()` returns `false`. WC Stripe (and other gateways) gate **live-mode** availability on `is_ssl()`; an unavailable tokenization gateway in turn makes WooCommerce remove the `payment-methods` account menu item (it only shows when a gateway supporting saved methods is available). Pure CLI-context artifact — nothing wrong with the config.
**Fix:** Simulate HTTPS before introspecting, then re-init gateways — or verify on the real front end (auth-cookie curl / browser). Don't trust CLI gateway-availability or account-menu output at face value.
```php
$_SERVER['HTTPS'] = 'on';
WC()->payment_gateways()->init();
$g = WC()->payment_gateways()->payment_gateways()['stripe'];
var_dump( $g->is_available() );          // now true
print_r( wc_get_account_menu_items() );   // now includes payment-methods
```
**First seen:** VMG, 2026-06-07 — after the Stripe live flip, `is_available()` and the Payment Methods nav both read as missing in `wp eval`; both flipped to present once HTTPS was simulated. (Confirmed the origin terminates real HTTPS — RunCloud LE cert, CF Full mode — so `is_ssl()` is genuinely true on real requests.)

