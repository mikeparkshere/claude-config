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
**Why:** Bricks hooks `update_post_metadata` for the three keys `_bricks_page_content_2`, `_bricks_page_header_2`, `_bricks_page_footer_2`. The callback returns `false` — blocking the update — when `Bricks\Capabilities::current_user_can_use_builder()` is false. WP-CLI runs as user 0, so the check fails and the write is silently blocked.
**Fix:** Call `wp_set_current_user( 1 )` at the top of any `wp eval` / `wp eval-file` script before writing these keys. The `bricks_global_classes` option is not gated this way.
```php
wp_set_current_user( 1 );
$content = get_post_meta( $tmpl_id, '_bricks_page_content_2', true );
// ... mutate $content ...
update_post_meta( $tmpl_id, '_bricks_page_content_2', $content );
```
**First seen:** AHML, 2026-04-27 — appending the article section to the Blog Single template. The script printed success and exited cleanly; the element count in the DB stayed unchanged. (This is the full incident record for the auth requirement named in `00` and `02`.)

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
