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

315 entries. Every title is written as what you would search for, so this list is the lookup surface: scan it, find the entry, then grep the file for that exact title. Titles ending `[stack:acss]` apply only on the ACSS stack (`00`, read protocol step 4).

**Do not read this file cover to cover.** At ~35,000 words it will evict the knowledgebase that sent you here — see `00`, the fourth layer. The index exists so that instruction is followable rather than aspirational.

Regenerate after every harvest, from the repo root:

```bash
awk '/^# === PROJECT/{exit} /^## /{s=$0; sub(/^## /,"",s); skip=(s ~ /^How this file works/ || s ~ /^Index/); if(!skip) print "\n**" s "**\n"} /^### /{if(!skip){t=$0; sub(/^### /,"",t); print "- " t}}' knowledgebase/03-stack-gotchas.md
```

Group labels below are bold rather than headings on purpose — `###` here would collide with the entry headings the generator reads.

**WordPress + Bricks Builder**

- Doubled-class selectors beat Bricks inline CSS cascade
- Bricks element IDs must be alphanumeric, never all-numeric — 6 chars is the house convention; validate only the IDs a script adds
- `{query_results_count_filter}` with NO element id renders 0 when it sits outside the loop
- `_cssId` is the stable hook for PHP filters, not Bricks' internal element id
- Re-sign Bricks code elements after any DB-side edit
- Bricks code elements store CSS in `settings.cssCode`, not `settings.code`
- `update_post_meta` silently fails on `_bricks_page_*_2` keys from WP-CLI
- Bricks Element Manager — a disabled element renders as NOTHING, even when it's in the template data
- Bricks Query Filter elements need a manual reindex AND a cron tick after programmatic creation
- Sibling `:has()` drives an empty state across Bricks AJAX filter re-renders — no JS listener
- Every Bricks query loop appends an empty `brx-query-trail` child — a 6-item `<ul>` renders 7 `<li>`
- Bricks term dynamic data tag is `{term_url}`, not `{term_link}`
- Bricks filter-radio / filter-checkbox default to vertical column — set `displayMode: 'button'` for horizontal pills
- Bricks archive template `archiveType: postType / post` never matches for built-in `post`
- Bricks filter active state — color must be set on `.brx-option-text`, not the `<label>`
- Bricks taxonomy filter ordered by a meta key silently DROPS every term lacking that meta
- Bricks per-post CSS cache hides DB-side global-class edits until regen
- `rm -f post-*.min.css` does NOT auto-regenerate — frontend silently degrades
- Bricks button utility classes (`btn--outline`, `btn--primary`) are Bricks-injected, not user-defined
- Bricks `bricks/allowed_html_tags` filter for custom elements
- Bricks Button element's `tag` is free text — `custom` + `customTag` is invalid there, and the error message misleads
- Bricks `accordion-nested` emits `aria-controls=""` / `aria-labelledby=""` unless the content wrapper and heading carry `_cssId`
- Bricks `icon` element with a custom-set SVG — `iconSize` doesn't size it and `iconColor` fills it
- Bricks emits skip-links automatically via the `bricks_body` action
- Bricks — typed `_border` setting uses a flat width/style/color shape, not per-side nested objects
- Bricks — typed-setting breakpoint suffixes go on the OUTER key, not as a sibling inside a nested dict
- A modifier class without its own breakpoint suffixes overrides the base class's media queries at EVERY width
- Bricks — `_widthMax: '100%'` is special-cased to suppress horizontal scrollbars
- Bricks caps every element at `max-width: 100%` — a full-bleed via width or negative margins needs `_widthMax: 'none'`
- Bricks — author rules with equal specificity lose to `@layer bricks` framework rules
- Bricks — `*{border-color}` plus a default 1px border on native inputs = "ghost" borders on every form
- Bricks — where CSS actually lives: global-class CSS is INLINE, element-typed CSS is in `post-{id}.min.css`
- Bricks never rebuilds `style-manager.min.css` — on an upgraded site it is a fossil that outranks current tokens
- Bricks' palette option is `bricks_color_palette` — SINGULAR, and the wrong name fails differently in PHP than in WP-CLI
- Bricks emits a Global Class's CSS ONLY when an element on that page references it
- Bricks file-mode CSS never externalizes global-class CSS — architecture, not a CLI limitation
- Bricks `_typography.font-family` quotes the value — use `custom_font_<id>`, not a CSS string
- Bricks image element with `tag=figure` collapses to content size — `:where(.brxe-image).tag` forces `width:auto; height:fit-content`
- Bricks `_aspectRatio` dual-routes to the inner img — it can't drive the figure's box when the image element IS the figure
- Wrapper-block-as-figure inherits UA `figure { margin: 1em 40px }` — Bricks zeros it only for `figure.brxe-image`
- Bricks "invalid post type" on Edit with Bricks = stale rewrite rules (not a Bricks setting)
- Bricks 2.3.x `bricks/dynamic_data/render_tag` passes `$tag` as a parsed-tag ARRAY, not always a string
- A custom dynamic tag used in an element `_conditions` resolves via `render_content`, NOT `render_tag` — register BOTH
- Bricks `{post_excerpt}` auto-generates from content — an `empty_not` gate on it never fires
- Bricks `logoHeight` (and any number-unit control) silently rejects `clamp()`
- Bricks `logo` element: `logoText` is escaped into `alt` WITHOUT dynamic-data resolution
- Bricks button/link with a dynamic-data href needs `link.type: "meta"` — `"external"` emits NO href at all
- Native `{post_url}` resolves to the queried object (not the loop item) in LINK contexts inside a custom-query loop
- Bricks custom query returning bare IDs (`'fields'=>'ids'`) does NOT establish per-item post context
- Bricks custom-query `posts_per_page` arrives at `settings['query']['posts_per_page']`, not `settings['posts_per_page']`
- Bricks archive template conditions use `archivePostTypes` / `archiveTerms` — NOT `postType` / `taxonomy`
- Bricks `is_archive_main_query` merge clobbers a plugin's `pre_get_posts` ordering at priority 10
- Bricks scores competing header/footer/template conditions — a specific post-ID condition (8) beats `main: any`
- Bricks per-page footer/header disable key is `footerDisabled` / `headerDisabled`
- Bricks has no `defaultHeadingTag` setting — root font-size and the default heading tag live in the THEME STYLE, under keys you will not guess
- Writing Bricks content to a page in `wordpress` editor mode renders nothing — flip `_bricks_editor_mode`
- Bricks element tree written via WP-CLI needs populated `children` arrays — `parent` alone renders empty shells
- Bricks `_cssGlobalClasses` must reference class IDs, not names — name refs persist but emit no class attribute
- Bricks image element with a dynamic source needs the tag to return an ARRAY `[$id]` in image context
- Bricks image element: the stored `id`/`url` is a BUILDER PREVIEW, not a frontend fallback
- Bricks DOES resolve dynamic data inside custom `_attributes` — and a loop-aware tag resolves per-item there (use a delimiter, not JSON)
- Bricks `_conditions` on an ACF `true_false` read the RAW value — `empty` / `empty_not` work
- ACF `true_false` rendered into a Bricks custom `_attributes` value outputs `"True"` / `"False"`, not `"1"` / `"0"`
- Markup generated by a custom dynamic tag CANNOT be styled by Bricks global classes — its CSS must live in the child theme
- A markup-rendering setting on a global class silently does nothing — classes contribute CSS only
- Editing `bricks_global_classes` via WP-CLI while a Bricks builder tab is open gets silently reverted
- Editing a SHARED global class from a page where it's a secondary consumer silently restyles its primary home
- Deleting a global class in the Bricks Style Manager leaves DANGLING refs in element `_cssGlobalClasses`
- Bricks ships a default `blockquote` style (4px left border + Georgia) that hits any `customTag: "blockquote"`
- `display:flex` breaks an inline comma-separated `{post_terms_*}` list — it eats the space after the comma
- Bricks auto-adds `aria-current="page"` to a link whose href EXACTLY matches the current URL — drive active states from it
- Bricks form `fromName` / `fromEmail` / `emailTo` / `emailSubject` are read ONLY by the Email action
- Bricks — seed utility global-class "anchors" from a plugin so child-theme classes show in the picker
- Bricks — flex/gap control KEYS depend on the ELEMENT TYPE: `_direction`/`_columnGap`/`_rowGap` on layout elements, `_flexDirection`/`_gap` on everything else
- Bricks layout-element base display: `.brxe-block` is a wrapping, full-width, `flex-start` flex column; `.brxe-div` has NO display rule
- A Bricks element with a link is wrapped in an `<a>` — the WRAPPER is the flex item, not the element
- Bricks — an empty `text-basic` renders nothing; use `block`/`div` for decorative empties
- Bricks — the `html` element is the ungated raw-markup injector; reserve it for genuinely non-native markup
- Bricks — flex/grid + gap that arranges BEM children belongs on the Container, not the single-child Section
- Bricks — header/footer TEMPLATE content lives in `_bricks_page_header_2` / `_bricks_page_footer_2`, not `_bricks_page_content_2`
- Bricks has built-in sanitized SVG upload — don't add SVG handling to the core plugin; and `get_allowed_mime_types()` reads false under WP-CLI
- A Bricks custom icon set cannot ship in a plugin — it is `wp_options` + per-install attachment IDs, and a mismatch renders NOTHING
- `svg:not([width]){min-width:1em}` outranks a global class — icons silently clamp to 1em
- Test custom Bricks dynamic tags with `bricks_render_dynamic_data()`, NOT manual `apply_filters()`
- Bricks date formatting on an ACF tag — the bare `:PHP-format` works, `:format(...)` prints the tag on the page
- A WP-CLI write to `_bricks_page_content_2` can silently not persist — verify by DB read-back; and global-class CSS is inline, not in `post-*.min.css`
- Advanced Themer "Remove style controls" tweak silently kills ALL typed-setting CSS site-wide — only `_cssCustom` survives
- Typed flex `_columnGap`/`_rowGap`/`_direction` silently don't emit when a global class is on a `text-basic` element — only on layout elements (div/block/container)
- Bricks builder open during a WP-CLI meta write → saving from that tab silently clobbers the DB edit
- A Bricks builder save does not bump `post_modified` and creates no revision — prove a save from the access log
- Frontend-only JS widget (Leaflet map etc.) renders as a blank box inside the Bricks builder canvas — looks clobbered, isn't
- `bricksData` ships `googleMapInstances` / `leafletMapInstances` on EVERY page — config keys prove nothing about what a page uses
- protomaps-leaflet 5.x: the basemap style option is `flavor`, not `theme` — the wrong name renders blank tiles with no error
- CARTO basemaps (Positron / Voyager / Dark Matter) now require an API key — the failure is a watermark tile, not an error
- Bricks Code element written headlessly renders nothing until you regenerate its code signature
- A mockup's "reuse these classes" note goes stale after a builder rework — reconcile against live `bricks_global_classes` BEFORE building
- Grid container: `_alignItems` is ignored, `_alignItemsGrid` is the operative key (unset emits `align-items: initial`)
- A grid column span or `grid-auto-rows: 1fr` must be reset to `auto` at the single-column breakpoint
- `Assets_Files::regenerate_css_files()` prints "Error: Control type number is not defined!" and still succeeds
- Bricks Form: `redirectAdminUrl` silently overrides the custom redirect AND mangles it into a literal path
- Bricks custom login page ignores `?redirect_to=` whenever a redirect action is configured
- A cloned Bricks auth page ships a cleartext password input — audit `type`, not appearance
- A Bricks template imported from another site carries that site's numeric IDs — fonts, ACF fields, and form settings all break silently
- Dynamic tags do NOT re-resolve inside the value a custom dynamic tag returns
- Custom Bricks query type over a non-post source: post-context tags silently resolve to nothing
- Bricks maintenance mode serves a plain unbranded page unless a `content`-type template is wired to `maintenanceTemplate`
- `_cssId` on an element inside a query loop duplicates per iteration — any `aria-labelledby` pointing at it collapses to the first item
- A Bricks CPT template that renders `post_content` = per-page content with zero template risk
- Bricks dynamic tags do NOT parse inside raw `post_content` — use shortcodes, including `[bricks_template id="X"]` for real Bricks elements
- Bricks re-grants revision support to every Bricks-enabled post type at `init` 999 — a default-priority `remove_post_type_support()` silently loses
- Bricks `altText` — an empty string is indistinguishable from unset, so you cannot force `alt=""`
- A shared attachment's alt text can only be right for one post — set the image element's `altText` with dynamic data
- Bricks image-as-figure puts `brxe-<id>` on the `<figure>`, not the `<img>` — naive verification greps find nothing
- Bricks Color Manager: a color row is `{id, raw, light}` and `light` accepts `var(--…)` — a two-tier palette works
- Opening the Bricks Color Manager writes the 18-color Default palette into an empty `bricks_color_palette`
- Theme Style `containerMaxWidth` only reaches ROOT containers — a container inside a section stays at Bricks' 1100px
- Bricks' base `:where(p) { margin-block: 0 1.2em }` stacks with flex `row-gap` — use Theme Style contextual spacing, not CSS
- Bricks contextual spacing: setting "Fallback spacing" silently overrides the Heading spacing
- Enabling Bricks Query Filters from WP-CLI doesn't create their tables — the indexer then logs DB errors on every cron tick
- Advanced Themer option names changed between versions — `bricks-advanced-themer__brxc_*` (3.3.x) vs `bricks-advanced-themer_brxc_*` (3.5)
- Bricks nav-menu ships `.sub-menu { line-height: 60px }` and `li { margin-left: 30px }` — both survive a typed `menuGap`
- Bricks text elements don't run shortcodes — use the Shortcode element (Post Content does)
- Re-running a WP-CLI build script silently reverts every builder edit made since its last run — dry-run it against the live DB first
- Front-end-only CSS: the builder canvas strips `bricks-is-frontend`, and Bricks' component preview keeps it — scope page-shell rules to both facts
- Bricks image element has no `sizes` control — WordPress derives `sizes` from the chosen image size, so Full ships the original
- Bricks native `breadcrumbs` climbs the post CATEGORY on blog singles — "Home / Uncategorized / Title"

**BricksExtras**

- BricksExtras element `name` strips hyphens from the file basename
- BricksExtras elements are gated behind a per-element admin enable flag
- BricksExtras `xheadersearch` — typed layout settings emit nothing (empty `$css_selector`), and "live search" is the whole Bricks Query Filters stack
- BricksExtras nestable elements: read the default child tree from `get_nestable_item()` — it's builder-authoritative, so building it by CLI isn't a guess
- Bricks mega menu: there's no BricksExtras mega element — core `nav-menu` and BE Slide Menu render the same template, and an offcanvas `flex-wrap` throws it off-screen
- BricksExtras Offcanvas Nestable — `clickTrigger` is the master selector for burger-toggle behavior
- ProSlider list semantics (BricksExtras)
- BricksExtras ProAccordion has a hardcoded `:where()` gray header background
- BricksExtras ProAccordion emits no `aria-expanded` / `aria-controls` in SSR markup
- BricksExtras element-level styling doesn't emit via CLI CSS regen — "Control type X is not defined!"
- Sticky header + offcanvas inside the header template — the panel "drops"/slides with the header
- BE Pro Slider Gallery default lazy-load ships placeholder `src` — kills hero/LCP images
- BricksExtras control values are typed, and the wrong type fails OPEN — `false` can mean ON
- BricksExtras Media Player defaults `crossorigin="anonymous"` — a cross-origin source with no CORS headers never plays, silently
- A typed BricksExtras setting can land on a DESCENDANT — overriding the custom property on the element itself silently loses
- Bricks builder save 500s on ONE template — a stray BricksExtras `extrasCustomQueryCode` placeholder
- BricksExtras Pro Slider — `slidePadding` is CSS padding on every slide, NOT Splide's `padding` option
- BricksExtras Pro Slider — a hover-lift shadow is clipped by the track, and the clip window can't live on the track
- BricksExtras before/after renders nothing server-side — verify headless with Playwright, and beware the screenshot-timing false alarm

**Frontend Toolkit (animations)**

- Frontend Toolkit must skip the builder iframe, not just the builder main frame
- Frontend Toolkit `staggerObserver` does not pick up AJAX-injected children inside an already-fired stagger parent
- Frontend Toolkit — never put `.anim-*` on a content wrapper taller than the viewport

**ACSS**

- ACSS settings — write via `Database_Settings::save_settings()`, never direct option writes `[stack:acss]`
- ACSS per-level heading sizes (`h1-min` / `h1-max`) DO reach `--h1` — if they don't, the write path is wrong `[stack:acss]`
- ACSS `option-<slot>-clr` toggles gate whether a color slot compiles at all `[stack:acss]`
- A column "stack" class (`_direction:column` + `_rowGap`) is byte-equivalent to the ACSS `.gap--N` utility
- `_inner` is dead — the ACSS Container replaces it
- ACSS — "Remove Deactivated Classes" toggle is the master ACSS→Bricks sync switch (misnamed) `[stack:acss]`
- ACSS — `clamp()` values in an `@supports` block override the rem fallbacks in `:root` `[stack:acss]`
- ACSS — spacing/text scale stops at `xs`; using `2xs` silently falls back to an invalid var
- ACSS — `automatic-bricks.css` enqueues AFTER the child theme; override ACSS tokens via `:root`, not selectors `[stack:acss]`
- ACSS — button bg-context wrappers override variant classes via specificity `[stack:acss]`
- ACSS button variants: the Bricks picker, the Button Style dropdown and the compiled CSS are three independent lists — `btn--action` is never in the dropdown, and a picker class can emit zero CSS `[stack:acss]`
- ACSS `[class*="btn--"]` matches ANY class containing the substring — a BEM modifier like `header__btn--panel` picks up button skin `[stack:acss]`
- ACSS — "light"/"dark" variants of a NEAR-BLACK base resolve to LIGHT colors `[stack:acss]`
- ACSS — changing a base color hex in the Dashboard can wipe variation overrides on that family `[stack:acss]`
- ACSS — `:where(section…)` makes any hand-rendered `<section>` flex-column-centered; `section > div` forces its children to column `[stack:acss]`
- A fixed ACSS width token in a Container's `_widthMax` replaces its viewport-safe max-width — horizontal overflow on mobile `[stack:acss]`
- ACSS palette shades are dashboard-derived — a WP-CLI base-color write leaves the ramp stale `[stack:acss]`
- ACSS custom CSS / Global SCSS is delivered INLINE (after automatic.css), not as a linked file `[stack:acss]`
- ACSS v3 settings UI is a shadow-DOM front-end overlay — a11y-tree automation can't reach it `[stack:acss]`
- ACSS type: per-level Font Size Override hits a non-geometric brand scale exactly `[stack:acss]`
- ACSS is fully configurable headless via `Database_Settings::save_settings()` — but only under an admin context, or it silently GUTS `automatic-bricks.css` `[stack:acss]`
- ACSS color shade ramps are stored per-shade, NOT recomputed from the master — must rewrite the ramp partials `[stack:acss]`
- ACSS `-hover` shades are LIGHTER by default — white on `--action-hover` / `--accent-hover` fails AA while `--primary-hover` may not `[stack:acss]`
- ACSS `--{color}-rgb` partials are SPACE-separated — legacy `rgba(var(--x-rgb), a)` silently kills the whole declaration
- ACSS → Bricks palette sync only hooks under `is_admin()` — CLI saves regenerate CSS but never update the color picker `[stack:acss]`
- ACSS flow-spacing `[class*="woocommerce"] * + p > * + *` leaks into Woo form rows — and fires on every page `[stack:acss]`
- ACSS `option-forms` ships ~145 KB of `.form--light` / `.form--dark` rules — about 40% of `automatic.css`, used only if an element carries those classes `[stack:acss]`

**ACF**

- ACF Pro — `default_value` seeds the form only, not `get_field()` reads
- ACF — `true_false` opt-out fields: legacy posts have NO meta row, so `value='1'` excludes them
- ACF — `acf/prepare_field`: `$field['name']` is the PREFIXED input name; match on `_name`
- ACF hook-variation order: `acf/render_field/key=…` fires AFTER the field has rendered — arm string swaps on `prepare_field`
- ACF relationship field's `taxonomy` setting silently zeroes every search result
- An ACF field property computed at registration time silently breaks the options group it reads — compute it in `acf/prepare_field`
- ACF — `acf_form()` front-end survival kit
- ACF field removal — `get_field()` stops working but the raw post meta survives
- A field group rebuilt with the same names nested in `group` fields — `get_field()` returns the OLD top-level value
- ACF `group` fields are structural — reorganising into tabs must WRAP them, never replace them
- Writing ACF **group** subfield options directly needs FOUR rows, not two — miss the group-level pair and `get_field()` returns `null`
- Round-tripping an ACF GROUP through `get_field()` → `update_field()` double-applies subfield formatting
- Migrating a UI/JSON field group to PHP — `local` lies, the post types can't be trashed, and children orphan
- ACF `url` field type rejects relative paths and query strings
- An ACF repeater round-trip bakes `new_lines` formatting into storage — `get_field()` → `update_field()` is lossy
- Bricks ACF query loop: `objectType: acf_<field>`; repeater subfields are `{acf_<repeater>_<subfield>}`
- ACF `gallery` / `image` fields are NOT loopable in Bricks — a repeater's image SUBFIELD renders per row only with return format `id`
- An ACF options page sizes its labels at (0,3,2) — `.acf-field .acf-label label` only restyles the nested ones
- ACF left-placed tabs: `.acf-tab-wrap.-left` and `.acf-fields.-sidebar` are JS-applied — curl never shows them
- A submenu under an ACF options page must hook `admin_menu` above 99 — earlier, its link is a bare `/wp-admin/<slug>` that 404s
- Renaming an ACF options-page group subfield orphans its value unless both option rows move
- ACF `update_field()` by field KEY on an unregistered group silently writes the KEY as the meta key — verify the registration, not the file
- Bricks `{acf_<name>}` tags are keyed on the field NAME across every group — a new field named `phone` silently hijacks an options-page `{acf_phone}`

**WordPress core — CPTs, rewrites, canonical, mail**

- A CPT named `author` collides with WP's built-in `?author=` query var — single URLs 404
- `redirect_canonical` 301s requests you meant to serve — a term archive to a same-slug CPT single, and a custom rewrite endpoint to a trailing slash
- Favicon: WP native Site Icon handles raster but not SVG; programmatic set skips the `site_icon-*` sizes
- `remove_action()` on `admin_head` needs core's REAL priority AND must run after the admin bootstrap
- A `wp_mail_from` filter beats an explicit `From:` header — a form plugin's per-message From field is cosmetic
- `default_category` still references a term with 0 posts — repoint before deleting Uncategorized
- Updating a PARENT THEME on a live box throws a hard fatal at any request that lands inside the unpack window
- `class_exists( 'WooCommerce' )` at include time is ALWAYS false when your plugin's folder sorts before `woocommerce/` — the guarded file silently never loads
- `strip_shortcodes()` only strips REGISTERED shortcodes — an old builder's tags (`[vc_row]`, `[ld_*]`) survive migration and leak onto the front end
- Legacy root-slug redirect silently skipped — the slug resolves to an ATTACHMENT page, not a 404
- `set_quality()` is ignored when an image editor converts formats (WebP → JPEG)

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
- A Bricks accordion can carry FAQ **microdata** that survives the `faqSchema` toggle — "one JSON-LD block" does not prove single-source schema
- `wp_rank_math_internal_links` is stale, and a raw HTML link count is inflated by nav/footer chrome
- Rank Math: `wp plugin update` leaves `rank_math_version` behind the code, and a file rollback then puts it AHEAD — Pro re-runs its updater on every admin load
- SEOPress on a Bricks site — eight defaults to correct before it is trusted (free and PRO)

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
- Bricks WC template types take over the render — the `[woocommerce_*]` shortcode on the page is inert
- WooCommerce HPOS — `--with-sync` keeps writing legacy `wp_posts` rows until compat mode is explicitly dropped
- WooCommerce Shipping label printing needs `xmlrpc.php` reachable — a blanket block breaks it
- WooCommerce Subscriptions — create subscription products from WP-CLI
- WooCommerce Subscriptions — manual gateways (COD) are hidden on subscription carts until "Accept Manual Renewals" is on
- WooCommerce Subscriptions — the staging-site lock silently SKIPS all automatic renewals after a Local→production migration
- WooCommerce Subscriptions — admin-created ("manual") orders NEVER spawn a subscription, even for subscription products
- Bricks `#brx-content` is a FLEX ITEM of `<body>` — `max-width` + auto margins on it shrink-wrap wide native blocks (Woo Cart/Checkout) and overflow on mobile
- Bricks ships an UNLAYERED `.alignwide { width: 120%; margin-left: -10% }` — capping `max-width` on a Woo Cart Block isn't enough
- Adding `theme.json` to a Bricks site: keep it settings-only (no `styles`, `defaultPalette: false`) or it leaks base CSS into Gutenberg-rendered content
- Bricks `woocommerce-notice` element renders "PHP class does not exist" — it's registration-gated behind a site-wide setting, and you don't need it
- Woo hides the thank-you page's address block from everyone except the order's own customer — verifying as admin makes it look broken
- Woo password show/hide toggle on a Bricks site: no vertical anchor, and two competing eyes that can't be recolored
- WooCommerce Blocks analytics hooks — the authoritative list is Woo's own bundled GA script
- HPOS: `wp wc shop_order delete --force` only TRASHES — the row stays in `wc_orders`
- WooCommerce — "Refund amount" is read-only when taxes are enabled, even at zero rates
- WooCommerce Stripe Gateway 11.x manages its own webhook — and Stripe caps endpoints at 3 API versions per mode
- WooCommerce Stripe: the customer ID is a *user option* (`{prefix}_stripe_customer_id`), and saved cards re-sync from it

**WS Form**

- WS Form's PHP API needs `wp_set_current_user(1)` from WP-CLI — reads included
- WS Form `WS_Form_Field::db_create()` needs an EMPTY label or the field gets ZERO meta
- WS Form — choices live in `data_grid_checkbox`/`data_grid_radio`; the export JSON is the importable artifact
- WS Form — skin it by overriding root `--wsf-form-*` vars, never by writing rules against `.wsf-field` / `.wsf-label`
- WS Form — per-field-type CSS loads AFTER the theme; the native checkbox IS the styled box (sibling of the label, no wrap mode)
- WS Form `css_style: false` drops more than the skin — the section fieldset chrome and `.wsf-hidden-element` lose their CSS
- WS Form prints its entire compiled CSS inline on every page — while identical files sit unused in uploads
- WS Form inlines a ~95KB form-definition JSON blob per rendered form — no setting moves it, only placement does
- WS Form checkbox `required` is cosmetic — `checkbox_min` is the enforcement, and it renders `data-checkbox-min`
- WS Form CAPTCHA (Turnstile/reCAPTCHA) keys live in the global `ws_form` option, not the field meta
- WS Form Pro implements Turnstile natively — and Cloudflare "Turnstile Spin" is redundant on any plugin stack
- A client-rendered form cannot be verified with `curl` — check the CAPTCHA provider's own call count instead
- WS Form tracking meta is written whenever the form toggle is on — an empty value proves nothing
- A populated GA4 conversion number proves nothing — WS Form does NOT push to the dataLayer
- WS Form `.empty()`s the form on every client render — and an attribute "already done" guard survives it and lies
- Custom WS Form trackings silently OVERWRITE the plugin's built-ins if you reuse a key name
- WS Form submission retention hides in a JSON-inside-serialized-PHP blob — and `''` means OFF, not "unset"
- WS Form Google Sheets: a header cell is NOT a mapping — an unmapped column looks wired and silently writes blank
- Reading a WS Form–linked Google Sheet from the server: use the add-on's credential, not a service account
- WS Form REST submit from the CLI returns a bare 403 unless it carries `wsf_post_mode=submit`
- WS Form `wsf_pre_render` runs at render AND at submit — the hook for runtime options and a hidden recipient

**Fluent Forms**

- Fluent Forms `fluentform_submission_success` never bubbles from the `<form>` — an `e.target` listener reads an empty form id
- Global autoload-captcha Turnstile silently breaks *payment* forms only — single-use tokens expire before a slow fill completes

**Roles & Capabilities**

- Bricks builder access is admin-only by default — custom roles get NO builder access unless explicitly granted
- Walling Rank Math to admins — deny `rank_math_*` caps; the editor role ships with the metabox cap
- `current_user_can('assign_terms')` is a false negative — check the taxonomy's MAPPED capability
- A CPT registered `capability_type => 'post'` makes the "blog" caps load-bearing — do not drop them on a site with no blog
- SEOPress adds noindex/nofollow/redirection bulk actions to every CPT list — a scoped client role inherits them
- `bricks_template` is `capability_type => 'post'` — a client role with post caps reaches the template editor by URL
- `editable_roles` returning an empty array breaks Advanced Themer's ACF field — ACF warnings in admin for a restricted role

**CSS general**

- Author `a { ... }` rules silently lose to the UA stylesheet's `a:link`
- Stacking-context bugs from `transform: translateY(-50%)`
- Stretched-link cards break three silent ways — specificity, `clip-path`, absolute positioning
- `<details>` content can't be force-shown on desktop (Chrome `::details-content` content-visibility)
- A `*/` inside a CSS comment silently eats the NEXT rule
- CSS custom properties do not substitute inside `url("data:image/svg+xml,…")`

**Fonts**

- Variable-font prep for Bricks: TTF→WOFF2 (keep axes), subset, and the opsz-instance trap
- Bricks custom fonts — `bricks_font_faces` meta schema + italic key encoding (also a `02` schema-library harvest candidate)

**WP-CLI**

- WP-CLI — inline `wp eval` fatals silently on `"{$arr[barekey]}"` in PHP 8
- WP-CLI — `is_ssl()` is false, so WooCommerce reports gateways "unavailable" and strips the Payment Methods nav
- Local: `wp db query` fails on the mysql socket; `wp eval`/`wp option get` work
- `wp media import` of SVG fails as CLI user 0 — sideload as an admin user
- `wp eval-file` dies silently (exit 0, zero output) on some script files — run via `wp eval 'include ...'`
- `wp eval-file` and the `wp eval 'include …'` workaround do NOT run the file in global scope — `global $var` sees nothing
- WP-CLI in the wrong webapp: a cross-site class-id collision silently edits a sibling install
- WP-CLI — `--prompt` ECHOES the resolved command line, secret included, to stdout
- The WP-CLI upgrader skin ECHOES premium package URLs, licence key included — update licensed themes/plugins with `--quiet` + version readback
- Vendor-updater products are invisible to WP-CLI — `wp plugin/theme list` prints `none` whether or not an update exists
- An emergency plugin deactivation outlives the fix, and edge-cached HTML hides the outage
- `wp post term remove` takes a slug/name, not a term_id — and reports success either way
- `wp plugin list` reports "version higher than expected" for premium plugins with no wp.org slug — it is a permanent false positive, not a signal
- `get_page_by_path()` as an import existence-check silently matches ATTACHMENT slugs — imports skip real posts

**Analytics & search measurement**

- GSC's query / page / query×page views disagree — the query view silently drops ~37% of clicks
- GA4 silently restates closed historical windows — never put a decimal on a GA4 share
- A GSC "average position" is a window average — so a length-matched pair of windows is the only honest before/after
- `esc_js()` on a URL query string inside a `<script>` breaks it: `&` becomes `&amp;`, so a GTM environment never applies

**Diagnostic patterns**

- Diagnostic JS via a Bricks code element
- A copy sweep over Bricks content misses `_attributes` values — aria-labels and alt text are copy too
- Testing as a logged-in user from the CLI — mint auth cookies with `wp_generate_auth_cookie`
- Firing admin hooks under `wp eval` gives false negatives — verify admin screens over real HTTP
- Verify link presence against BOTH absolute and relative hrefs — Bricks emits relative
- Headless Chrome clamps the layout viewport to a ~500px minimum — a `--window-size=375` screenshot is a CROP of a 500px layout
- A regex delete across a PHP file can silently remove hundreds of lines — and `php -l` will not notice
- A hidden tab freezes transitions and `requestAnimationFrame` — a focus ring "missing" and a measurement script that hangs
- Diagnostic: stop reasoning about the cascade — dump the matched CSS rules via CDP

## WordPress + Bricks Builder

### Doubled-class selectors beat Bricks inline CSS cascade
**Symptom / When:** A child-theme stylesheet rule with equal specificity to a Bricks Global Class rule does not win.
**Why:** Bricks injects Global Class CSS inline in the page `<head>`, which by source order beats the external child-theme stylesheet. Doubling the class (`.foo.foo`) bumps specificity above the inline Global Class without `!important`.
**Fix:** `.foo.foo { ... }` in the child-theme sheet.
**First seen:** V1 baseline, 2026-05-24. (Distinct from the layered-`@layer bricks` case below — different cascade mechanic, same trick.)

### Bricks element IDs must be alphanumeric, never all-numeric — 6 chars is the house convention; validate only the IDs a script adds
**Symptom / When:** Either `{query_results_count_filter:<id>}` and similar dynamic-data tags render as empty strings, or a build script's ID guard aborts on an inherited page (`!! ABORT: bad id 'vhrsc'`) with nothing actually wrong.
**Why:** The real constraint is **alphanumeric and never all-numeric**. An all-numeric ID (`593395`) breaks tags that take an element ID. **Length isn't enforced by Bricks:** 5-character IDs pass the builder's JS-side tree validator and survive a builder save with nothing stripped. Six characters is a house convention for new work, because it leaves more room before collisions. It isn't a Bricks rule, so a guard that retro-validates a whole inherited tree against it reports corruption that isn't there.
**Fix:** Open the element in the builder and change an all-numeric ID to a mix of letters and numbers (`vidlst`, not `593395`). When constructing element trees programmatically, generate 6-char alphanumeric IDs, and validate **only the elements the script is adding**, never the inherited tree:
```php
foreach ( array_column( $new_elements, 'id' ) as $id ) {           // NOT array_column( $tree, 'id' )
    if ( ! preg_match( '/^[a-z0-9]{6}$/i', $id ) || ctype_digit( $id ) ) { /* abort */ }
}
```
⚠️ **Untested:** 5-char IDs have not been checked against `{query_results_count_filter:<id>}` (a two-minute local test). Until someone runs it, keep IDs you point a tag at at 6 characters.
**First seen:** V1 baseline, 2026-05-24 (all-numeric IDs blanking dynamic tags). · WCDP, 2026-08-18 — a page built with 5-character IDs throughout was opened and saved in the builder. Element count didn't change, and every at-risk key (`customTag`, breakpoint-suffixed grid keys, `:has()` `_cssCustom`, `style: btn--*`, icon settings) was intact. A later script's whole-tree 6-char guard then aborted on that page before writing anything. The abort was correct behaviour from an over-broad rule.

### `{query_results_count_filter}` with NO element id renders 0 when it sits outside the loop
**Symptom / When:** An archive header reads "0 Items" while the grid below renders a full page. The same tag works on a sibling template, and nothing errors.
**Why:** With an id (`{query_results_count_filter:abc123}`), Bricks resolves the count from that element's query history. With **no** id, it falls back to `Query::get_query_object( Query::is_any_looping() )`, the *current* query. A count in a page header sits outside every loop, so there is no current query and the count resolves to 0.
**Fix:** Always scope the tag to the loop element's id: `{query_results_count_filter:<loopElementId>}`. The bare form works only inside the loop it counts. **Verify it right:** the markup is `<span data-brx-qr-count="<id>">20</span> Items`, so grepping the page for the literal `20 Items` finds nothing whether the tag works or not. Grep the span.
**First seen:** THM, 2026-08-09 — the archive template used the bare tag while a taxonomy template built later from the same base used the scoped form. Both had valid 6-char ids, so the all-numeric entry above did not apply.

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

**⚠️ Revisions are the exception that route (a) does not cover.** WordPress denies `edit_post` on the `revision` post type for every user, so `current_user_can( 'edit_post', <revision_id> )` and `\Bricks\Capabilities::current_user_can_use_builder( <revision_id> )` are both `false` even as user 1. A sweep across templates "succeeds" on live posts and silently leaves the old value in their revisions, and the script's own log may still report a clean pass. Write revisions with route (c), using `wp_cache_delete( $rev_id, 'post_meta' )`, and confirm with a `LIKE` scan afterward rather than trusting the sweep. A stale reference in a revision is inert until someone restores it, so this matters when you're deleting the thing referenced.

**Symptom triage — this entry covers a write that FAILS or no-ops. A write that lands and then reverts *minutes later* is a different bug** — see "Editing `bricks_global_classes` while a Bricks builder tab is open gets silently reverted."

The `bricks_global_classes` option is not gated this way (but see the builder-clobber entry — it has its own hazard).

**First seen:** AHML, 2026-04-27 — appending the article section to the Blog Single template. The script printed success and exited cleanly; the element count in the DB stayed unchanged. (This is the full incident record for the auth requirement named in `00` and `02`.) · **Extended:** THM, 2026-08-09 — removing a custom font: 4 global classes repointed cleanly, while 8 template revisions reported success and kept the old font id. Caught by a post-change reference count.
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
**It bites again on every RE-run, and worse.** `reindex()` empties `wp_bricks_filters_index` immediately and only queues the rebuild. Until cron ticks, the filter resolves zero options, and Bricks renders a zero-option filter as **nothing at all**, absent from the DOM rather than an empty `<ul>`. An idempotent re-run of a script that changed only CSS then makes a working filter vanish, and it reads as though the element was deleted. Don't call `reindex()` on a re-run that touches only global-class styling, since that lives in an option and needs no reindex. When you do call it, make the script fail loudly:
```php
if ( 0 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bricks_filters_index" ) ) echo "*** index EMPTY: run wp cron event run bricks_indexer\n";
```
**First seen:** AHML, 2026-04-27 — Blog Archive filter facet. Element registered, `reindex()` returned true, index table stayed at 0 rows. · **Extended:** THT, 2026-09-16 — a gender filter disappeared after a re-run that added one CSS property.

### Sibling `:has()` drives an empty state across Bricks AJAX filter re-renders — no JS listener
**Symptom / When:** A "no results" block must show when a filtered loop returns nothing, and track every AJAX re-render of the grid, without wiring a listener to filter events.
**Why:** The loop's trail element (`.brx-query-trail`, `includes/elements/base.php`) carries `data-max-pages` in the re-rendered markup, so a pure-CSS sibling selector reacts to the AJAX swap for free.
**Fix:**
```css
.results-grid:has(.brx-query-trail[data-max-pages="0"]) ~ .results-empty { display: block; }
```
`:has()` falls to custom CSS on import (`02`, CSS authoring rules), so put it in the child theme or a class's `_cssCustom`, not a typed control.
**First seen:** THM (inherited from its clone source), 2026-08-09 — a browse template's empty state. Trail class and attribute re-verified on current Bricks at harvest, 2026-10-05.

### Every Bricks query loop appends an empty `brx-query-trail` child — a 6-item `<ul>` renders 7 `<li>`
**Symptom / When:** Counting rendered children to verify a loop (`grep -c '<li'`) returns one more than the query returned. Or a reviewer reading the page without CSS reports an extra, empty numbered item at the end of a list ("step 7 of 6").
**Why:** Bricks appends a trailing marker element carrying the loop's query state (`data-query-element-id`, `data-page`, `data-max-pages`). It backs Query Filters, load-more, infinite scroll and live search, and it's emitted on **every** query loop whether those features are used or not. It inherits the loop element's tag, so a loop on an `li` produces an extra `<li>`. `.brx-query-trail { display: none !important }` ships in Bricks' frontend CSS, so there's no layout or flex-gap impact and it's out of the accessibility tree. But any **CSS-ignoring** consumer (reader view, text extraction, AI crawlers, some audit tools) sees an empty list item, followed by whatever comes next on the page.
**Fix:** When verifying, count anchors or a content class, not the wrapper tag. To remove it from a loop that uses none of those features, return `false` from `bricks/render_query_loop_trail` (`$render, $element, $query`; `includes/elements/base.php`, since 1.11.1), keyed on `$element->id`. ⚠️ Removing it breaks Query Filters, load-more, infinite scroll and live search on that loop, and the `:has()` empty state above, which depends on the trail. Answer a schema question with the JSON-LD step count, not with "it's hidden".
**First seen:** MMHN, 2026-07-21 — six static `<li>` chips converted to one looping `<li>`; verification counted 7 `<li>` against 6 anchors. · MMHN, 2026-09-28 (Bricks 2.4.2) — a client's content reviewer flagged a recipe's step list as having a seventh step; the `<ol>` was closed correctly and the Recipe JSON-LD carried exactly six `HowToStep`s.

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
**Fix — preferred, in button mode: the typed pill controls.** With `displayMode: 'button'`, `filter-base.php` registers `buttonBackgroundColor`, `buttonBorder`, `buttonTypography`, `buttonActiveBackgroundColor`, `buttonActiveBorder`, `buttonActiveTypography` and `buttonOptionsGap`. They emit against `&[data-mode="button"] .bricks-button` and `.bricks-button.brx-option-active`, which is the inner span, so `buttonActiveTypography` sets the active color on the right node with no hand-written CSS (shapes in `02`). The controls are invisible in the builder until Mode is Button, which is why they look absent. **One gap:** `buttonBorder`'s CSS mapping is the single property `border-color`, so width, style and radius written into it persist and never emit. Add only the radius as custom CSS: `.my-filter[data-mode="button"] .bricks-button { border-radius: var(--radius-xl); }`.
**Fix — hand-written, outside button mode or on older Bricks:** target the inner span. The active span gets `brx-option-active`:
```css
.blog-filter__radio .brx-option-text.brx-option-active { color: var(--base); }
```
Keep the label rule for background and border-color. Same pattern for `filter-checkbox`.
**First seen:** AHML, 2026-04-30 — Blog Archive filter, active pill rendered cognac-on-cognac. · **Extended:** THT, 2026-09-16, Bricks 2.3.12 — the typed-control route, found by reading `filter-base.php` rather than the builder UI.

### Bricks taxonomy filter ordered by a meta key silently DROPS every term lacking that meta
**Symptom / When:** A taxonomy-bound filter is given a custom order with `filterTaxonomyOrderBy: meta_value_num` + `filterTaxonomyOrderMetaKey: <key>` (shape in `02`). The order works. Then a term added later, typically by the client in wp-admin, never appears in the filter. It exists, has posts, and shows on its own archive. Nothing errors.
**Why:** Bricks passes the key straight to `get_terms()` (`Query_Filters::set_data_source_from_taxonomy()` → `$args['meta_key']`). `WP_Term_Query` implements `meta_key` as a **join** on term meta, so a term with no row for that key is excluded, not sorted last. Bricks already sets `hide_empty => false`, so an *empty* term still shows. It's specifically the *unmetaed* term that vanishes, and nothing about the symptom points at ordering.
**Fix:** Guarantee every term carries the meta, including terms created after the build, by seeding it on `created_term` rather than a one-off backfill:
```php
add_action( 'created_term', function ( $term_id, $tt_id, $taxonomy ) {
    if ( ! in_array( $taxonomy, [ 'my-tax-a', 'my-tax-b' ], true ) || '' !== get_term_meta( $term_id, 'term_order', true ) ) return;
    $max = 0;
    foreach ( (array) get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids', 'exclude' => [ $term_id ] ] ) as $sid )
        $max = max( $max, (int) get_term_meta( $sid, 'term_order', true ) );
    update_term_meta( $term_id, 'term_order', $max + 10 );
}, 10, 3 );
```
Prove it with a probe term whose meta you delete, then run the query Bricks runs (`get_terms([... 'orderby'=>'meta_value_num', 'meta_key'=>'term_order'])`). Custom ordering is only worth this when the terms form a scale (Small · Average · Large sorts alphabetically wrong). Otherwise keep Bricks' alphabetical default.
**First seen:** THM, 2026-08-11 — size-scale terms. The drop was predicted while writing the ordering and verified with a probe term before the hook shipped.

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
**Fix (non-Bricks surfaces):** define the button CSS yourself, scoped so it cannot collide with Bricks' per-element CSS on builder pages (e.g. `.woocommerce .btn--primary{background:var(--primary);color:var(--white);…}`). `[stack:acss]` Consume `--primary`/`--white`/`--primary-hover` to match the ACSS button.
**Corollary — non-Button Bricks elements get no button CSS either.** A `text-link` (or any element other than a Bricks **Button**) carrying `btn--primary` via `_cssGlobalClasses` renders with the class in the DOM and no fill, padding or radius. `.btn--*` classes only **declare** custom properties (`--btn-background`, `--btn-text-color`, …); the consuming rule is emitted per Button element at render, and where ACSS compiled its button module into `automatic-gutenberg.css` there is no global consumer on the front end at all. Grepping the page finds `.btn--primary` defined, which makes it look like a cascade problem. Fix: use a real `button` element (`name: 'button'`) with the `style` setting, and brand it by overriding the **tokens** rather than writing button chrome — `.my-cta { --btn-background: var(--action); --btn-font-weight: 700; }`. `[stack:acss]` A token override also beats fighting ACSS's `[class*="btn--"]` rule at equal specificity, which is why a typed `_typography.font-weight` on the class silently loses. See also "ACSS button variants: the Bricks picker, the Button Style dropdown and the compiled CSS are three independent lists…". The inverse trap, where a non-button class *gains* button skin because its name contains `btn--`, is "ACSS `[class*="btn--"]` matches ANY class containing the substring…".
**First seen:** AHML, 2026-04-29 — CSS sweep deleted `btn--outline` as orphan; it kept rendering. No actual breakage. · **Extended:** VMG, 2026-06-07 — portal dashboard CTAs rendered as bare links until `.btn` CSS was added under `.woocommerce`. · **Extended:** WCDP, 2026-08-10 — both inherited header CTAs were `text-link` + `btn--secondary`/`btn--primary` and had been rendering as bare text links.

### Bricks `bricks/allowed_html_tags` filter for custom elements
**Symptom:** Builder warning "tag X not allowed" on a code element using e.g. `<button>` — render is correct but the builder validation is noisy.
**Fix:**
```php
add_filter( 'bricks/allowed_html_tags', function( $tags ) {
    $tags[] = 'button';
    return $tags;
});
```
**Current Bricks: `button` needs no filter.** On Bricks 2.4.2 (WP 7.1.2), `Helpers::get_allowed_html_tags()` starts from `array_keys( wp_kses_allowed_html( 'post' ) )`, and `button` is already in that list, so the example above is redundant there. A layout element with `tag: 'custom'` + `customTag: 'button'` emits a real `<button>` with no filter (schema in `02`, "Block HTML tag options"). Keep the filter for tags that really are outside kses `post`. Check the live list rather than trusting a copy: `wp eval 'echo implode(" ", \Bricks\Helpers::get_allowed_html_tags());'`
**First seen:** V1 baseline, 2026-05-24. · **Checked:** WCDP, 2026-09-30 — `button` present in core's list on Bricks 2.4.2 with no filter registered.

### Bricks Button element's `tag` is free text — `custom` + `customTag` is invalid there, and the error message misleads
**Symptom / When:** A Button element with `tag: 'custom'` shows the builder error *"HTML tag not allowed. Extend allowed tags through filter `bricks/allowed_html_tags`"*. Extending the filter does nothing.
**Why:** Layout and text elements expose `tag` as a select whose `custom` option pairs with a sibling `customTag`. The Button is the exception: its `tag` control is `type: 'text'` (placeholder `span`, shown only when `link` is empty, `includes/elements/button.php`), validated against `Helpers::get_allowed_html_tags()`. `'custom'` is a Bricks sentinel, not an HTML tag, so no filter can ever permit it. (The shape is in `02`.)
**Fix:** Set `tag` to the real tag name (`'button'`, `'a'`) and remove `customTag`. Sweep for offenders:
```php
foreach ( get_posts( [ 'post_type' => [ 'bricks_template', 'page' ], 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids' ] ) as $id ) {
    foreach ( (array) get_post_meta( $id, '_bricks_page_content_2', true ) as $el ) {
        if ( ( $el['name'] ?? '' ) === 'button' && ( $el['settings']['tag'] ?? '' ) === 'custom' ) printf( "%d %s\n", $id, $el['id'] );
    }
}
```
This does not apply to `<button>` markup inside a **code** element, which is the entry above.
**First seen:** THM (inherited from its clone source), 2026-08-09. Control type re-verified on current Bricks at harvest, 2026-10-05.

### Bricks `accordion-nested` emits `aria-controls=""` / `aria-labelledby=""` unless the content wrapper and heading carry `_cssId`
**Symptom / When:** A headless-built nestable accordion toggles fine, but every title wrapper has `aria-controls=""`, every content wrapper has `id="" role="region" aria-labelledby=""`, and the title heading gets `id=""`. Nothing is visibly wrong.
**Why:** `Accordion_Nested::enhance_accessibility()` walks the rendered HTML with DOMDocument and does `$wrapper->getAttribute('id') ?? 'accordion-content-' . $index`. DOMDocument returns an empty **string** for a missing attribute, never `null`, so the `??` fallback never runs and `''` is written back. Elements with element-level styling get `id="brxe-…"` and happen to work. Class-only elements, the correct build per `01`, hit the bug.
**Fix:** Give each content wrapper and each question heading an explicit, page-unique `_cssId` (`faq-a-00` / `faq-q-00`, never inside a query loop). The pass then wires real ids.
**First seen:** THT, 2026-09-06 — an FAQ accordion, caught by reading the rendered title wrapper.

### Bricks `icon` element with a custom-set SVG — `iconSize` doesn't size it and `iconColor` fills it
**Symptom / When:** An `icon` pointed at a custom icon set renders huge, growing to fill its flex row (439px in a 700px accordion title), and a stroke-only chevron or check renders as a solid filled shape.
**Why:** Bricks inlines the SVG as the element root (`<svg class="brxe-icon">`). `iconSize` emits only `font-size`, and an inlined SVG with no `width` attribute doesn't size from font-size. `iconColor` emits both `color` and `fill`, and a CSS `fill` beats the file's `fill="none"`, painting every path.
**Fix:** On the icon's class, set typed `_width` / `_height` (e.g. `1.25em`), keep `iconColor` for the `currentColor` stroke, and add `_cssCustom: '.my-icon { fill: none; flex: 0 0 auto; }'`, which has no typed control. Prefer the `svg` element (`source: 'iconSet'`) wherever the accordion's `isAccordionIcon` behavior isn't needed. It has real typed `height`/`width` and doesn't emit `fill`. (Related: "`svg:not([width]){min-width:1em}` outranks a global class…".)
**First seen:** THT, 2026-09-06 — FAQ accordion chevrons.

### Bricks emits skip-links automatically via the `bricks_body` action
**What it does:** Bricks core emits two skip-links via `bricks_body` at frontend bootstrap, before the header template renders: `<a class="skip-link" href="#brx-content">` and a footer-skip variant. It also emits `<main id="brx-content">`.
**Why it matters:** When building a header template, do not author your own skip-link — you will get two anchors with the same target, confusing assistive tech.
**What you DO provide:** CSS to style `.skip-link` visually-hidden-until-focused. Bricks does not ship that rule — the anchor renders visible by default. Add a child-theme `.skip-link` rule (hide via `transform: translateY(-150%)`, restore on `:focus`).
**First seen:** KSCBS, 2026-05-17 — header v2 rebuild initially authored a duplicate skip-link.

### Bricks — typed `_border` setting uses a flat width/style/color shape, not per-side nested objects
**Symptom:** A `_border` setting written with a per-side nested shape (`_border.bottom.{width,style,color}`) persists in `bricks_global_classes` without error, but the rendered page contains no border CSS at all.
**Why:** Bricks' `_border` schema is flat — width is a per-side object, style is a scalar, color is a single object, radius is a per-side object. Bricks' emitter walks `_border.width` / `.style` / `.color` / `.radius` explicitly; anything else is ignored and not even stripped on next builder load, so a readback looks correct while the output is empty.
**Fix:** Use the flat shape (it is in the `02` schema library). For "bottom border only," set `width.bottom = '1'`, other sides `'0'`. Confirm by curl + grep of the inline CSS. A correct declaration looks like `border-top: 1px solid var(--base-light); border-right: 0 solid var(--base-light); ...` — if there are no `border-*` declarations, the shape is wrong.
**The opposite signature — a shorthand parsed into the typed fields.** A paste through the HTML & CSS to Bricks converter, or an import, can decompose `border: 1px solid var(--neutral)` positionally and put the **color in `width`** (`"width":"var(--neutral)"`, `color` unset). Bricks then emits no `border-width` or `border-color`, CSS falls back to `medium` (~3px) and `currentColor`, and a heavier, text-colored border renders that looks deliberate. The same split breaks `var(--x, fallback)` on its internal comma, spreading one call across corners (`top: "var(--border-radius-m,"`, `right: "0.5rem)"`). Rewrite to the flat shape, and rebuild the radius from what the browser was actually rendering (the fallback), since the named token may never have existed. Audit an install:
```bash
wp eval '$g=get_option("bricks_global_classes",[]);
foreach($g as $c){ $b=$c["settings"]["_border"]??null; if(!$b) continue;
  $w=$b["width"]??null; $bad = (is_string($w) && strpos($w,"var(")!==false);
  foreach((array)($b["radius"]??[]) as $v) if (is_string($v) && (substr_count($v,"(")!=substr_count($v,")"))) $bad=true;
  if($bad) printf("%s (%s): %s\n",$c["name"],$c["id"],wp_json_encode($b)); }'
```
**First seen:** KSCBS, 2026-05-17 — About page built programmatically; three classes had `_border` in nested shape, all rendered with no border. · **Extended:** THM, 2026-08-09 — an inherited single-template aside class had rendered a 3px `currentColor` border on every page since the clone. Found while editing the box, not by looking for it.

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

### A modifier class without its own breakpoint suffixes overrides the base class's media queries at EVERY width
**Symptom / When:** A `card-grid--4` modifier (e.g. `_gridTemplateColumns: var(--grid-4)`) works on desktop, but at mobile widths the grid stays four columns and cards squeeze to ~90px. The base `card-grid`'s `tablet_portrait` / `mobile_landscape` overrides never apply. The HTML checks all pass; only a narrow screenshot shows it.
**Why:** Media queries add no specificity. The base class emits `.card-grid{…}` plus its `@media` overrides; the modifier emits a bare `.card-grid--4{…}` with no media query. At equal (0,1,0) specificity, source order decides. Bricks emits global-class CSS in first-encountered order, the modifier comes after the base, so the modifier's base-width rule beats the base class's media-scoped rules at **every** viewport, not just desktop.
**Fix:** Every responsive modifier carries its own breakpoint ladder, mirroring the base:
```php
'_gridTemplateColumns'                  => 'var(--grid-4)',
'_gridTemplateColumns:tablet_portrait'  => 'var(--grid-2)',
'_gridTemplateColumns:mobile_landscape' => 'var(--grid-1)',
```
This applies to any responsive typed setting a modifier overrides, not just grids.
**First seen:** WCDP, 2026-08-20 — the Home `card-grid--4` involvement grid rendered four ~90px columns at 390px; caught by screenshot. Hit again 2026-09-13 on a `split--even` modifier (`var(--grid-2)`), which never collapsed until it gained its own `:tablet_portrait` key.

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

### Bricks caps every element at `max-width: 100%` — a full-bleed via width or negative margins needs `_widthMax: 'none'`
**Symptom / When:** A block given `width: calc(100% + 2rem)`, or negative inline margins to bleed past its parent's padding, renders at exactly the parent's content width and shifted sideways. The far edge falls short and nothing errors.
**Why:** Bricks' base layer ships `[class*=brxe-]{max-width:100%}`, which hits every element. `width: calc(100% + …)` is clamped back to 100%, and a negative margin then only *moves* the box. Two different-looking symptoms have one cause. (Different mechanism from the `_widthMax: '100%'` converter special case above.)
**Fix:** Set the typed `_widthMax: 'none'` on the class (it emits `max-width: none`) alongside the width and margins:
```php
'_width'    => 'calc(100% + 2 * var(--space-l))',
'_widthMax' => 'none',
'_margin'   => [ 'top' => 'calc(-1 * var(--space-l))', 'left' => 'calc(-1 * var(--space-l))', 'right' => 'calc(-1 * var(--space-l))' ],
```
The same cap stacks with BricksExtras' own `.x-slider{width:100%}` on a slider root (see the Pro Slider clip-window entry).
**Verify:** measure, don't eyeball. Compare the element's `getBoundingClientRect()` left/right against its parent's border box.
**First seen:** WCDP, 2026-09-11 — twice in one afternoon: a Pro Slider clip window, then a card photo meant to bleed to the card edges. The second time it was recognised from measurement in under a minute. `_widthMax: 'none'` survived a builder save (readback verified).

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
⚠️ **OPEN CONFLICT (MMHN 2026-10-08, untested).** MMHN saw the spec behavior from the other side. BricksExtras ships its component CSS **unlayered**, and a default like `.x-offcanvas_inner { background: #fff }` beat every override made inside Bricks, at any specificity: the typed `offcanvas_color` control (which emits a correct `#brxe-<id> .x-offcanvas_inner` selector, inside `@layer bricks`) and element `_cssCustom` both lost. An unlayered child-theme rule ((0,2,0) over the plugin's (0,1,0)) or `!important` in `_cssCustom` won. That matches the spec (unlayered beats layered regardless of specificity) and the blockquote entry's "an unlayered inline Global Class beats `@layer bricks`", and contradicts this entry's claim that an unlayered author rule lost to `@layer bricks`. **Settle it:** on one page, pit an equal-specificity unlayered rule against a `@layer bricks` rule for `display` and `border-color`, in both source orders, and read the winner from CDP's matched-rule output ("Diagnostic: stop reasoning about the cascade"), confirming along the way that the "unlayered" rule really sits outside every layer. Practical either way: override a BricksExtras default in the child theme, not in Bricks typed settings or `_cssCustom`.
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
**The full set of compiled artifacts** in `wp-content/uploads/bricks/css/`: `color-palettes.min.css`, `global-variables.min.css` (emitted only when `bricks_global_variables` is non-empty), `theme-style-<name>.min.css`, `post-<id>.min.css`, and `style-manager.min.css`. A regen rebuilds every one of these **except `style-manager.min.css`** — see "Bricks never rebuilds `style-manager.min.css`". Verified SLVPR, 2026-09-21, Bricks 2.3.13.
**Fix:** Verify each in its own home.
```bash
# Global class → rendered page (this is the canonical 02 recipe)
curl -sk <url> | grep -oE '\.my-class[^{]*\{[^}]*\}'
# Element typed settings → the per-post file
grep -r '<element-id>' wp-content/uploads/bricks/css/
```
Element typed CSS uses `#brxe-<id>` ID selectors, and for images the dual-route `#brxe-<id>:not(.tag), #brxe-<id> img{…}`. Useful corollary: Bricks' own image default is `:where(.brxe-image) img{height:100%;width:100%}` at **zero specificity**, so an ID-scoped typed `_aspectRatio` on a figure-image wins and renders.
**First seen:** TAB, 2026-05-29 — Single Service hero; a bronze CTA rule was absent from `post-15486.min.css` but present in the page's inline head CSS. **And** TAB, 2026-06-09 — overview image typed settings; `.brxe-psomd0` rules were in `post-15582.min.css`, not inline.

### Bricks never rebuilds `style-manager.min.css` — on an upgraded site it is a fossil that outranks current tokens
**Symptom / When:** After emptying `bricks_global_variables` or editing the colour palette, tokens fail to resolve or resolve to dead values — `getComputedStyle` returns a `var(--retired-name)` pointer to a variable that exists nowhere in the build. Everything in the DB reads clean. The overriding declarations trace to a file in `wp-content/uploads/bricks/css/` whose mtime predates the change by weeks or months.
**Why:** Bricks compiles several artifacts into `wp-content/uploads/bricks/css/` — `color-palettes.min.css`, `global-variables.min.css`, `theme-style-<name>.min.css`, `post-<id>.min.css` and `style-manager.min.css`. `\Bricks\Assets_Files::regenerate_css_files()` and the admin "Regenerate CSS files" button rebuild all of them **except `style-manager.min.css`**, which only a builder session writes. A DB-side cleanup therefore leaves that one file enqueued, re-declaring retired variables at a cascade position that beats ACSS Global CSS. The DB reads clean; the fossil lives only on disk.
⚠️ **Version-scoped, and this is the part that matters.** On Bricks 2.3.13 `style-manager.min.css` is **empty** — the palette and global-variable output now lives in the two dedicated files, and those regenerate correctly. So this is a **carried-forward** hazard: it bites a site whose file was compiled by an older Bricks and survived the upgrade. On a fresh current build the file is inert, and grepping it returns nothing.
**Fix:** Do not grep one filename — the artifact that holds the fossil moves between Bricks versions. List the directory and find the file that **did not** move across a regen:
```bash
stat -f '%Sm %z %N' wp-content/uploads/bricks/css/*.min.css   # macOS
stat -c '%y %s %n' wp-content/uploads/bricks/css/*.min.css    # Linux
# run the regen, list again — anything whose mtime did not change is not managed by regen
```
Then confirm the palette option is clean (`bricks_color_palette` — singular, see its own entry), park a backup of the suspect file, and delete it. Bricks recreates it, empty, on the next builder load or save.
⚠️ **On `bricks-native` the file is a duplicate. The hazard is staleness, not deletion.** Where the Style Manager is the token home, `style-manager.min.css` is populated: it re-declares every `light`-format color, plus scale variables if any exist. Those same tokens are also emitted to `color-palettes.min.css` and `global-variables.min.css`, which the regen does rebuild. The file loads **after** them, so a stale copy overrides fresh values, and deleting it drops nothing. The detection above will flag it, since it doesn't move across a CLI regen, and that flag is correct. After a CLI palette write, refresh it with `\Bricks\Ajax::generate_style_manager_css_file()` (public static, no nonce, reads options fresh, returns false on a write failure), then run `regenerate_css_files()` in a **separate** request, or delete it.
⚠️ **Why the separate request:** the palette file is rebuilt from `Database::$global_data['colorPalette']`, which is loaded once per request (`files.php`, ~L724). A regen in the same `wp eval` as the `update_option` writes the **pre-update** palette, so the front end serves the old colors while the option reads new.
**First seen:** Nametank (ext-mem), 2026-08-13 — after the BRAND v1.1 reconciliation emptied `bricks_global_variables` and moved the pinned brand tokens to ACSS Global CSS, the tokens stopped resolving on the front end. Root cause was a `style-manager.min.css` compiled in April, before the cleanup, re-declaring the pinned tokens as `var(--ms-*)` pointers to the deleted globals. Deleting it fixed it, and the file has stayed empty since. Sessions in between debugged the *convention* — which token home was correct — instead of the stale compiled artifact.
**Mechanism corrected:** SLVPR, 2026-09-21 — verified on Bricks 2.3.13 by running `regenerate_css_files()` and diffing mtimes: `color-palettes`, `global-variables` and `theme-style-mpd` all rebuilt; `style-manager.min.css` did not. But it is 0 bytes on all three installs checked (SLVPR, the `stack-0626` template, and Nametank's own since its fix), so the palette/token output has moved and the original "it carries the tokens" mechanism no longer describes a current install. Reframed as a carried-forward hazard and the detection generalised, because grepping the named file on a modern site returns nothing and reads as "not my problem."
**Corrected:** pkjsupport, 2026-10-04 (Bricks 2.4.2). The Stage 0 `bricks-native` warning said the file was the live token output and that deleting it would drop the tokens. Tested false: all 27 of its declarations were also in `color-palettes.min.css`. With the file moved aside it was no longer enqueued, and all 107 Style Manager tokens still resolved by `getComputedStyle`. The same session found the same-request regen revert above, during a CLI palette write.

### Bricks' palette option is `bricks_color_palette` — SINGULAR, and the wrong name fails differently in PHP than in WP-CLI
**Symptom / When:** A read of the Bricks colour palette comes back empty, and the conclusion drawn is "this install has no palette" or "the palette got wiped." In PHP the read returns `false` and flattens silently to nothing in any `foreach` or `json_encode`; from WP-CLI the same wrong name errors instead.
**Why:** The option is registered singular — `bricks_color_palette`. `bricks_color_palettes` has never existed. WordPress's `get_option()` returns `false` for an unregistered key with no notice, so in PHP a typo is indistinguishable from a genuinely empty palette. WP-CLI is the loud path: `Error: Could not get 'bricks_color_palettes' option. Does it exist?`
**Fix:** Singular, always.
```bash
wp option get bricks_color_palette --format=json | jq '.[0].colors | length'
```
In PHP, separate absent from empty rather than trusting a falsy read:
```php
$raw = get_option( 'bricks_color_palette', null );
if ( null === $raw ) { /* option absent — you are reading the wrong key, not an empty palette */ }
```
This is generic `get_option()` behaviour rather than a Bricks quirk, and it is the `00` Evidence-discipline trap in option form: an empty result that means "I looked in the wrong place," not "there is nothing there."
**First seen:** Nametank (ext-mem), 2026-08-13 — surfaced while verifying the palette was clean during the `style-manager.min.css` investigation, where a wrong-key read would have falsely confirmed the palette as the culprit.
**Refined:** SLVPR, 2026-09-21 — the original wording ("the plural name reads empty silently") holds in PHP but not from WP-CLI, which errors loudly. Corrected because the silent branch is the one a CLI session never hits and the loud branch is the one it always will.

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
**The empty `style-manager.min.css` is a separate, real, fixable fault.** `Assets::enqueue_style_manager_css()` enqueues on `file_exists()` with **no size check**, so a 0-byte file costs a render-blocking request on every page. It holds color variables, utility classes and scale-property global variables — **not** global classes, so no builder save will ever make it hold them. `[stack:acss]` On **ACSS-driven sites it is permanently empty**: `Ajax::generate_style_manager_css_file()` skips colors with no `light` key ("old format"), and ACSS-generated palettes carry `raw` only (measured: 0 of 207 colors had `light`; 0 of 4 global variables had `scale`; the generator returns `true` and writes 0 bytes). **Fix: delete the file.** The deletion sticks — regen's cleanup filter *excludes* that filename from deletion but never creates it. It reappears populated only if a `light`-format palette is added and saved in the builder, which is correct. On `bricks-native` the file is populated, but only as a later-loading duplicate of `color-palettes.min.css` / `global-variables.min.css`. Deleting it drops no tokens; a stale copy is the real hazard (see "Bricks never rebuilds `style-manager.min.css`"). Verified: pkjsupport, 2026-10-04.
**Option name:** the setting lives in **`bricks_global_settings`**, not `bricks_settings` (which does not exist and returns `false` — a `?? 'default'` against it silently fabricates a plausible answer). Read it the reliable way: `wp eval '\Bricks\Database::get_setting("cssLoading", "UNSET");'`.
**Pre-2.2 installs:** `style-manager.min.css` is `@since 2.2`, so the empty-file half of this entry cannot occur below that — `generate_color_palette_css_file()` is the deprecated shim that now just calls the Style Manager generator. The global-classes-are-inline architecture was not re-verified below 2.2; if a sub-2.2 install surfaces, confirm against its own source before applying the "nothing to fix" verdict.
**First seen:** TAB, 2026-06-26 — pre-launch CSS-handoff sweep; CLI switch left global classes inline, reverted to baseline, deferred to cutover. · **Corrected:** MMHN, 2026-07-15 — this entry cited `bricks_settings`; the real option is `bricks_global_settings`. (Noted in passing: a fresh Local install can ship `cssLoading='file'` already set.) · **Substantially rewritten:** VMG, 2026-08-01 — the old entry's prescribed fix (re-save global classes in the builder so `style-manager.min.css` populates) does not work and cannot: that file never holds global classes. Read against 2.3.10 source rather than inferred from symptoms; "no net win" also overstated, since file mode does move the element-default bulk out of the HTML. · **Corrected:** pkjsupport, 2026-10-04 — the Stage 0 `bricks-native` aside ("deleting it would drop them") tested false; see the `style-manager.min.css` entry above.

### Bricks `_typography.font-family` quotes the value — use `custom_font_<id>`, not a CSS string
**Symptom / When:** You set a Global Class font-family to `"Jost, sans-serif"` (or `"var(--heading-font-family)"`) and the rendered CSS comes out `font-family: "Jost, sans-serif";` — quoted, treated as one literal name. The browser silently falls back to the default sans.
**Why:** Bricks emits `_typography.font-family` as a quoted CSS string for compatibility with custom font names containing spaces. It does not parse the value as a CSS expression — `var(...)`, comma stacks and `inherit` all get wrapped and broken.
**Fix:** Reference the Bricks **custom font post ID**. Bricks stores each Custom Font as a `bricks_fonts` CPT; setting `_typography.font-family` to `custom_font_<id>` emits the bare font name plus the matching `@font-face`.
```bash
wp post list --post_type=bricks_fonts --fields=ID,post_title
```
System stacks work as literals (`"sans-serif"` is a valid CSS keyword). Named families always go through `custom_font_<id>`.
**The fallback stack has its own key.** The emitter appends `fallback` verbatim after the resolved family (`assets.php`, ~L4044); it's the typed "Fallback" field in the font control. Without it, a slow font load renders the browser default:
```php
'_typography' => [ 'font-family' => 'custom_font_8', 'fallback' => 'system-ui, -apple-system, "Segoe UI", sans-serif' ],
// emits font-family: "Switzer", system-ui, …   — works inside Theme Style typographyBody / typographyHeadings too
```
**First seen:** TAB, 2026-04-25 — Global Class typography assignments; both `var(--heading-font-family)` and `"Jost, sans-serif"` produced quoted output. `custom_font_169` resolved it. · **Extended:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — the `fallback` key, verified in the Theme Style CSS file.

### Bricks image element with `tag=figure` collapses to content size — `:where(.brxe-image).tag` forces `width:auto; height:fit-content`
**Symptom / When:** An image-as-figure (image element, `tag: "figure"`) built for a fill-the-container treatment — absolute-inset-0 hero with `_objectFit: cover` — renders at its natural intrinsic dimensions and gets clipped instead of scaled-and-cropped. Toggling `_objectPosition` in the UI has no visible effect; the lever feels dead.
**Why:** `frontend.min.css` ships `:where(.brxe-image).tag { display:inline-block; height:fit-content; position:relative; width:auto }`. The `:where()` zeros `.brxe-image`'s specificity but `.tag` carries (0,1,0). With `position:absolute; inset:0` AND `height:fit-content`, height comes from content, not the inset values — `bottom:0` is ignored for sizing. The inner img's `height:100%` then resolves circularly against the figure's intrinsic size, so `object-fit:cover` has no aspect mismatch to crop and `object-position` has nothing to position.
**Fix:** Set `_width: "100%"` and `_height: "100%"` on the figure's Global Class. Same specificity (0,1,0), but the page-inline CSS comes after `frontend.min.css` in source order, so it wins.
**Diagnostic recipe:** when an image hero "looks fine but `object-position` does nothing," inspect the rendered figure — computed height matching the img's intrinsic height (not the section's) is this bug.
**The naming consequence — never call a design-system class `.tag`.** The `tag` class is not the user-facing HTML-tag setting. It is a structural marker that `includes/elements/image.php` stamps onto the outermost wrapper of any Image element that gets a wrapper (a link, a caption, an overlay or gradient, or an explicit `tag` setting) (`set_attribute( '_root', 'class', 'tag' )` and `set_attribute( 'link', 'class', 'tag' )`). Any bare `.tag` rule, a common name for badges, chips and status pills, leaks onto every linked image site-wide and shows up as unexplained inset on card images. Name those classes `.badge` / `.chip` / `.pill` rather than guarding `.tag` with `:not(.brxe-image)`. Audit: `curl -s <url> | grep -oE 'class="[^"]*\btag\b[^"]*"' | sort -u` should return only Bricks image wrappers.
**First seen:** TAB, 2026-05-02 — Our Process hero; surfaced by noticing the object-position lever was inert. · **Extended:** THM (inherited from its clone source), 2026-08-09 — the naming collision, and the reason that project's badge utility is `.badge`. Re-verified on current Bricks at harvest, 2026-10-05.

### Bricks `_aspectRatio` dual-routes to the inner img — it can't drive the figure's box when the image element IS the figure
**Symptom / When:** You consolidate a photo block to image-as-figure (image element, `tag: "figure"`, one class carrying wrapper+img settings) with `_aspectRatio: "3/4"`. The figure collapses to the image's intrinsic dimensions; aspect-ratio appears to do nothing.
**Why:** Bricks' image element redefines `_aspectRatio` with the dual selector `&:not(.tag), img` (`includes/elements/image.php` ~L136). When the class sits on the figure (which has `.tag`), `:not(.tag)` skips it — the rule only matches the inner `img`, which already has `width:100%; height:100%` from Bricks' defaults. Per CSS spec explicit width AND height override `aspect-ratio`. So the ratio lands nowhere useful.
**Fix — the image/figure decision tree:**
- **(a) Layout-sized image** (absolute-inset-0 hero): image element with `tag:"figure"` — one element. Layout drives the box; no aspect-ratio needed.
- **(b) Aspect-ratio'd photo, no caption:** outer `<div>`/block for layout + image element for semantics — two elements. The **wrapper** carries `_aspectRatio` (it's a block, so no dual-route); the image carries `_width:100%; _height:100%; _objectFit:cover`.
- **(c) Captioned photo:** outer block with `tag:"figure"` + plain image + `<figcaption>` — the figcaption MUST be inside its figure.
**`object-fit` may take the same route, and a class can miss it.** `_objectFit` on the image element emits through the element-id selector pair, not onto an arbitrary global class. A class carrying `{_width: '100%', _height: '100%', _objectFit: 'cover'}` on a bare (caption-less) image was seen to emit width and height but no `object-fit`, stretching a 16:9 source into its wrapper; `object-fit: cover` in the class's `_cssCustom` cropped it. ⚠️ **Re-test before relying on the mechanism:** the no-emit was verified with a grep (`'\.my-img{'`) that misses inline global-class CSS, which Bricks prints as `.my-img {` (see "Bricks — where CSS actually lives"). The stretched image was real; that the class CSS lacked `object-fit` was never proven.
**First seen:** TAB, 2026-05-02 — photo-block sweep; `partner__photo` consolidated to image-as-figure rendered a collapsed figure. Reverted to wrapper-as-figure, which is correct for aspect-ratio'd photos. · **Extended:** MMHN, 2026-07-18 — a recipe-single feature image: a 1920×1080 source stretched into a 480px panel.

### Wrapper-block-as-figure inherits UA `figure { margin: 1em 40px }` — Bricks zeros it only for `figure.brxe-image`
**Symptom / When:** A captioned photo built as a block with `tag:"figure"` (case (c) above) renders with a visible gutter — ~16px top/bottom, ~40px left/right — inside its container. The wrapper class sets no `_margin`, the parent has no padding, the image fills the figure. The gutter persists.
**Why:** Bricks' `frontend.min.css` ships a UA-mimic `figure { margin: 1em 40px }` and zeros it back out only for the image-as-figure case: `figure.brxe-image { margin: 0 }`. A wrapper-block-as-figure renders `<figure class="brxe-block …">` — no `.brxe-image`, so the zeroing selector misses and the UA-mimic margin survives.
**Fix:** One defensive child-theme rule mirroring Bricks' own pattern for the block variant:
```css
figure.brxe-block { margin: 0; }
```
(0,1,1) beats bare `figure` (0,0,1) without `!important`, and it covers every future wrapper-as-figure so the pattern doesn't re-bite per element.
**Diagnostic recipe:** unexplained white-space around a Bricks figure with nothing in the class settings to explain it → check whether it renders `.brxe-image` or `.brxe-block`.
**On current Bricks the rule ships in `frontend-light-layer.min.css`, inside `@layer bricks`, and the symptom can be mobile overflow.** A figure block whose width resolves to 100% also carries the 40px side margin, so it overflows its parent by however much the container padding falls short of 40px. The page scrolls sideways at 390 (`scrollWidth` ~19px over), while on desktop it reads as a harmless inset inside a grid column. A typed `_margin: 0` on each figure block's class also works ((0,1,0) beats bare `figure`), but it's easy to lose on an "empty by design" hook class. Theme Style contextual spacing (see "Bricks' base `:where(p)`…") may cover `figure` too. **Finding it:** the culprit reports `width: 100%`, so enumerate instead. Walk every element, skip anything with a `position: fixed` ancestor (off-canvas panels and mini-carts park off-screen right), and report elements whose `right` exceeds `clientWidth`.
**First seen:** TAB, 2026-05-03 — About Story section; `story__photo` showed ~40px gutter each side while sibling image-as-figure photos rendered flush. · **Extended:** MMHN, 2026-07-21 — three figure blocks on an About page; the mockup's `margin: 0` was lost in conversion, and a full-page screenshot came back 409px wide on a 390px viewport.

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

### Bricks `{post_excerpt}` auto-generates from content — an `empty_not` gate on it never fires
**Symptom / When:** An element gated `empty_not` on `{post_excerpt}` renders on every post, including posts with no excerpt written, where it shows a ~55-word auto-summary of the content. Placed above a post-content element, it reads as the content duplicated. The gate tests correctly on posts that DO have excerpts, so demo content with excerpts everywhere hides the failure completely.
**Why:** `{post_excerpt}` resolves through WordPress's excerpt pipeline, which falls back to generating an excerpt from `post_content` when none is written. The tag is non-empty for any post with body content, so `empty_not` against it is always true. The condition isn't broken. The premise "no custom excerpt → empty tag" is. (Different mechanism from the Rank Math `%excerpt%` entry.)
**Fix:** Gate on `has_excerpt()` through a custom tag returning `'1'` / `''`, registered on `render_content` as well as `render_tag` (conditions resolve via `render_content`). Keep `{post_excerpt}` only as the *displayed* value behind that gate.
```php
function prefix_has_excerpt_value( $post_id ) { return has_excerpt( $post_id ) ? '1' : ''; }
// element: _conditions → {prefix_has_excerpt} empty_not ; text → {post_excerpt}
```
For card decks that *want* a fallback, use a custom tag that returns `get_the_excerpt()` behind `has_excerpt()` and otherwise `wp_trim_words()` at the length the design wants. WP's 55-word fallback is rarely it.
**First seen:** WCDP, 2026-08-20 — the Event single's hero lead dumped a 55-word auto-excerpt above the full description as soon as the first excerpt-less event existed. It was invisible for two days because every seeded event carried a custom excerpt.

### Bricks `logoHeight` (and any number-unit control) silently rejects `clamp()`
**Symptom / When:** Setting the Logo element's `logoHeight` to `clamp(36px, 4vw, 48px)` saves, the builder shows the value, but no `height` rule renders. No console error, no validation warning.
**Why:** `logoHeight` is a `number-unit` control that parses `<number><unit>` and silently drops anything else. `clamp()`, `min()`, `max()`, `calc()` and `var()` all fail validation and emit nothing.
**Fix:** Write the rule in the child theme against Bricks' inner `<img>`, and leave the Bricks setting empty so it doesn't fight:
```css
.header__logo .bricks-site-logo { height: clamp(36px, 4vw, 48px); width: auto; }
```
Applies to any Bricks number-unit setting where you want a fluid expression.
**First seen:** TAB, 2026-04-25 — header logo responsive sizing.

### Bricks `logo` element: `logoText` is escaped into `alt` WITHOUT dynamic-data resolution
**Symptom / When:** A logo element with `logoText => '{acf_my_org_name}'` renders `alt="{acf_my_org_name}"` — the literal tag, read aloud by screen readers and indexed as-is.
**Why:** `Element_Logo::render()` does `$image_atts['alt'] = esc_attr( $settings['logoText'] )` on the image branch, with no `render_dynamic_data()` call. The tag is only resolved on the *text-logo* branch further down (`elseif ( ! empty( $settings['logoText'] ) )`), which never runs when an image resolved. So the same setting is dynamic in one branch and literal in the other.
**Fix:** Put a literal string in `logoText`. If the org name must come from options, resolve it in PHP when writing the tree rather than leaving a tag in the field. Audit it on any inherited header — it fails silently and only an accessibility pass or a page-source read catches it.
**First seen:** WCDP, 2026-08-10 — an inherited header carried an ACF tag in `logoText` for a field that did not exist on the new install either, so the alt would have been a literal tag for a value that was never coming.

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
**Class attributes too, even with `WP_Post` results.** The same split hits a dynamic tag in an element's `_cssClasses`, which is parsed in root-attribute context, where the global post can still be the page. A custom query type returning `WP_Post` objects (which "Bricks custom query returning bare IDs" below says get `setup_postdata()`) still resolved a class tag against the page: product cards lost Woo's `add_to_cart_button ajax_add_to_cart` classes, so Add to Cart did a full page load, while their text, href and label were right. Use the loop-object resolver in every tag filter, not just the ones you expect to run in loops, and verify an AJAX CTA by clicking; markup that lost its contract classes still looks correct.
**First seen:** TAB, 2026-05-28 — related-posts cards; titles were per-item but all three links pointed at the current post. · **Extended:** MMHN, 2026-07-20 — a homepage featured-products carousel (custom query type) reusing the shop archive's card.

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

### Bricks `is_archive_main_query` merge clobbers a plugin's `pre_get_posts` ordering at priority 10
**Symptom / When:** A CPT archive whose ordering is set in a plugin `pre_get_posts` renders in the wrong order. If the fallback orderby has ties (e.g. seeded posts sharing a `post_date`), the order **varies per request**, which reads as randomness rather than a deterministic clobber. The plugin hook verifiably runs, and a simulated `WP_Query` with the same args orders correctly. Only the real archive request is wrong.
**Why:** When a Bricks archive template contains a loop with `is_archive_main_query: true`, `Database::set_main_archive_query` (hooked to `pre_get_posts` at default priority 10 in `includes/database.php`) merges the loop element's **prepared query vars** into the main query with `$query->set()`. The prepared vars include Bricks' defaults for anything the loop element doesn't specify, notably `orderby: 'date'` / `order: 'DESC'`. Plugins register their hooks before the theme loads, so at equal priority the theme's callback runs last and overwrites what the plugin set. The plugin's `meta_query` survives (the loop had none to merge) but its `orderby` does not. That partial clobber is what makes it hard to spot.
**Fix:** Register the plugin's archive-query callback at priority **20** (anything after 10), so it runs after Bricks' merge and owns the final ordering:
```php
add_action( 'pre_get_posts', 'prefix_event_archive_query', 20 ); // after Bricks' merge at 10
```
Setting an explicit `orderby` on the loop element also works, but it splits query policy across two homes. Keep it in PHP. The same merge applies to `posts_per_page`, which is how the loop's own setting reaches the main query.
**The flip side — a loop NOT merged paginates against the wrong page size.** Without `is_archive_main_query: true`, the loop is a secondary query, and its `/page/N/` links are resolved by the **main** query, sized from Settings → Reading. WordPress 404s any `paged` beyond the main query's `max_num_pages` before Bricks renders anything. The links work for every loop page only when **loop `posts_per_page` ≥ main `posts_per_page`**. Larger is always safe, and smaller breaks once there are enough posts. The fix is the merge flag (native), or a loop page size at or above Reading's, or a `pre_get_posts` that sizes the main query for that archive. ⚠️ A "test pagination on a small dataset" tweak (loop 3 against Reading 10) manufactures exactly this 404 in code that is fine, and a production config with loop 24 / Reading 10 hides it until someone lowers the loop. Mirror the production *relationship* between the two values when testing.
**Verify:** dump the live request, not a simulation. A temporary mu-plugin printing `$wp_query->get('orderby')` and `$wp_query->request` on the real archive URL shows the clobber. The simulated-query check will pass and mislead.
**The loop is the other valid home for the policy, and on a Woo archive leaving it silent defeats WooCommerce.** The prepared vars Bricks merges default an unset loop `posts_per_page` to Settings → Reading (10) and an unset `orderby` to date DESC. On a product archive that makes `woocommerce_default_catalog_orderby`, the catalog-ordering dropdown and any `menu_order` curation inert; the tell is a shop sorted newest-first, i.e. reverse import order. Either route works: declare it on the loop (`'orderby' => 'menu_order', 'order' => 'ASC', 'posts_per_page' => 24`), or keep it in a priority-20 `pre_get_posts` as above. Only a priority-10 `pre_get_posts` loses.
**First seen:** WCDP, 2026-08-20 — the Events archive (upcoming only, ordered ascending by a start-date meta clause) rendered in a per-request-varying order. Same-second seed `post_date`s made `ORDER BY post_date DESC` non-deterministic. Priority 10 → 20 fixed it; verified stable across three loads with `ORDER BY CAST(meta_value AS SIGNED) ASC` in the live SQL. · **Extended:** THT, 2026-09-16 — every page-2 link on a CPT archive 404'd under a loop-3 / Reading-10 test config. Re-tested at Reading 2 / loop 3, where all pages resolved. · **Extended:** MMHN, 2026-07-18 / 2026-07-19 — a CPT archive paged at 10 despite a default-priority `pre_get_posts` setting 24; then a product archive ignored a full `menu_order` curation until the loop got `orderby: menu_order`.

### Bricks scores competing header/footer/template conditions — a specific post-ID condition (8) beats `main: any`
**Symptom / When:** A second header template conditioned to one page (`main: ids`) doesn't appear to take over from the site-wide default (`main: any`) — the page renders an empty `<header id="brx-header">` shell, or the wrong header.
**Why:** Two things. (1) Bricks **scores** matching templates (`templates.php` ~L895-927): a specific post ID scores **8** and beats "any" — so the specific header *does* win; you don't need to exclude the default. (2) But `Database::set_active_templates()` caches its result and Bricks caches per-post header CSS, so a page rendered *before* the condition was written shows a stale (often empty) header until a page-settings write + CSS regen forces re-resolution.
**Fix:** Assign via `_bricks_template_settings` → `templateConditions` (`main: ids`), then regen CSS and re-render. Verify programmatically:
```php
\Bricks\Database::set_active_templates( $page_id );
echo \Bricks\Database::$active_templates['header'];  // should print the specific template ID
```
**A guessed `main` value writes cleanly and applies to NOTHING.** `any` is the "entire website" value — not `entireWebsite`, `everywhere` or `site`. `templates.php` switches on `$condition['main']` against a fixed set — `any`, `frontpage`, `postType`, `archiveType`, `search`, `error`, `terms`, `ids` — and anything else matches no case and is silently skipped: `update_post_meta` returns true, the read-back shows exactly what you wrote, and the template renders on zero pages. (The back-compat line mapping `'hook'` → `'any'` confirms `any` is the site-wide value.)
```php
update_post_meta( $tid, '_bricks_template_settings', [
    'templateConditions' => [ [ 'id' => 'cndhdr', 'main' => 'any' ] ],
] );
```
**Template variants: swap the condition, don't edit the live one.** For a temporary variant (a launch footer without a feature the client hasn't decided on), build it as its own `bricks_template`, generated by script from the original's current tree, then move `templateConditions` onto it and clear the original's. Restore is moving them back. Regenerate Bricks CSS so the new `post-<id>.min.css` exists, purge the page cache, and lock-check both templates before writing. Never leave two templates of one type both on `main: any`.
**First seen:** TAB, 2026-05-30 — a per-page Simple header rendered as an empty shell; resolved after the page-settings write + regen. · **Extended:** WCDP, 2026-08-10 — applying an inherited header/footer site-wide with a descriptive guess rendered them nowhere until `main: any`. · **Extended:** MMHN, 2026-10-05 — a launch footer without the newsletter form, with the original kept restorable by one condition swap.

### Bricks per-page footer/header disable key is `footerDisabled` / `headerDisabled`
**Symptom / When:** Writing `_bricks_page_settings` with `templateFooterDisabled` (or `disableFooter`) to suppress the footer on one page has no effect.
**Why:** `Database::is_template_disabled( $type )` builds the key as `"{$type}Disabled"`. The only keys honored are `headerDisabled` and `footerDisabled`. Any other key name is silently ignored.
**Fix:**
```php
update_post_meta( $page_id, '_bricks_page_settings', [ 'footerDisabled' => true ] );
```
Then regen CSS. Confirm by render — the `<footer>` markup should be absent.
**First seen:** TAB, 2026-05-30 — footer kept rendering under a guessed `templateFooterDisabled` key.

### Bricks has no `defaultHeadingTag` setting — root font-size and the default heading tag live in the THEME STYLE, under keys you will not guess
**Symptom / When:** You go to satisfy `01`'s Bricks Theme Style requirements ("HTML font-size = `var(--root-font-size)`", "Default heading tag = H2") from WP-CLI and cannot find where either lives. Searching `bricks_global_settings` for `defaultHeadingTag` returns nothing.
**Why:** Neither is a global setting. Both are **theme-style** controls, and the key names don't match the UI labels. `grep -rn "defaultHeadingTag" wp-content/themes/bricks/` returns zero hits — the setting does not exist under any name. The root font-size control is labelled "HTML: font-size", and a name search finds an unrelated `htmlFontSize` string in `includes/i18n.php` first — it belongs to the *Style Manager* and is a red herring. The default heading tag sits in the **`heading` element's own theme-style group**, and `includes/elements/heading.php` reads `$this->theme_styles['tag'] ?? 'h3'` — so unset means **h3**, not h2.
**Fix:** Read the control definitions rather than guessing from UI labels: `includes/theme-styles/controls/typography.php` and `.../element-heading.php` each `return [ 'name' => …, 'controls' => … ]`, which is authoritative for both the group key and the control key. The resulting write path is in `02` → "Theme Style keys — root font-size and default heading tag".
**First seen:** WCDP, 2026-08-11 — both keys were assumed from the UI labels and both assumptions were wrong; reading the control files settled it in one pass.

### Writing Bricks content to a page in `wordpress` editor mode renders nothing — flip `_bricks_editor_mode`
**Symptom / When:** You write an element tree to `_bricks_page_content_2` on a freshly-created WP page; the front end renders the default WP template, no Bricks.
**Why:** Bricks only renders `_bricks_page_content_2` when the page is in Bricks editor mode. A page created in WP admin defaults to `wordpress`; writing Bricks content alone doesn't switch it.
**Fix:** Set `_bricks_editor_mode = bricks` alongside the content write (+ `wp_set_current_user(1)` + CSS regen). Verify by curl: the body should carry `brxe-*` elements.
**First seen:** TAB, 2026-05-31 — Thank-you page created in WP admin; setting the mode + writing the 59-element tree rendered it.

### Bricks element tree written via WP-CLI needs populated `children` arrays — `parent` alone renders empty shells
**Symptom / When:** A programmatically-built `_bricks_page_content_2` renders all TOP-LEVEL sections but each is an empty shell — no headings, grids or cards. Element count is correct; nesting via `parent` is correct.
**Why:** Bricks' frontend renderer walks the `children` array on each element, not `parent`. If every element ships `'children'=>[]`, only depth-0 elements render; their descendants are never emitted.
**Fix:** After building the flat element list, derive `children` from `parent` before writing — group ids by parent in document order, then set each element's `children`. Builder-saved trees always have populated `children` (`children:a:3`), which is the golden-rule tell.
**The same model, two more ways it bites:**
- **A wrong `parent` renders fine, and breaks the next script that trusts it.** Because rendering never reads `parent`, a typo that points an element at its grandparent is invisible on the page. A later removal script that filters by `parent` then misses that subtree, and a rebuild reusing the same id scheme leaves **duplicate element ids** in the array. Walk `children` from known roots for any removal or audit, and after every direct tree edit check that the walked count equals the array count. Watch for a top-level `parent` stored as the **string** `"0"`: a strict `=== 0` root scan skips it, so use `== 0`.
- **Top-level order is raw array order.** Nested order lives in the parent's `children`, but `parent: 0` elements have no parent, so they render in the flat array's sequence. `$content[] = $section` or `array_merge` puts a new section last on the page. Place it with `array_splice()` / `array_unshift()`, and read back before regenerating CSS:
```bash
wp post meta get <id> _bricks_page_content_2 --format=json | jq '[.[] | select(.parent==0)] | map({id, name, label})'
```
**First seen:** TAB, 2026-05-31 — 42 elements wrote; 3 sections rendered as empty shells until children were rebuilt. · **Extended:** THT, 2026-09-01 — a grid-builder helper's grandparent `parent` typo led a later cleanup to miss a card subtree, and the rebuild produced 13 duplicate ids. It was caught by a reachability walk before shipping. Same day, a promoted top-level hero element was sitting at the end of the array and would have rendered last.

### Bricks `_cssGlobalClasses` must reference class IDs, not names — name refs persist but emit no class attribute
**Symptom / When:** A built tree renders every element with correct nesting and working loops, but **unstyled** — the global-class names never appear in `class="…"`, so no CSS hooks and no layout. Readback of the meta looks fine (the names are right there).
**Why:** `_cssGlobalClasses` is an array of class **IDs** (the 6-char `id` of each `bricks_global_classes` entry, e.g. `svhsec`), not names (`service-hero`). Bricks resolves each ref against the registry by ID at render; an unknown ID is silently skipped.
**Fix:** Reference IDs; look up first:
```php
$byname = array_column( get_option('bricks_global_classes'), 'id', 'name' );
$id = $byname['service-hero'];   // 'svhsec'
```
**The trap that hides it:** when a class's name and id happen to be identical, referencing by name works — masking the bug until you hit one where name ≠ id.
**The flip side: a rename is a `name` edit with no tree write.** Since elements reference IDs, change `name` in `bricks_global_classes` (keyed on the ID, asserting the expected old name so a drifted class aborts) and every reference follows. Two things don't follow: literal selectors inside the class's own `_cssCustom` (`.old-name{…}` detaches) and child-theme or plugin code that names the class. Grep both first. Verify the new name in markup and emitted CSS, and zero site-wide hits for the old one.
**First seen:** TAB, 2026-05-31 — Single Project built referencing classes by name; all 7 sections rendered with 0 class hits until a name→id repair pass. A sibling template was unaffected because it referenced IDs directly. · **Extended:** MMHN, 2026-07-21 — three BEM renames, zero tree writes.

### Bricks image element with a dynamic source needs the tag to return an ARRAY `[$id]` in image context
**Symptom / When:** An image element whose `image.useDynamicData` points at a *custom* dynamic tag renders nothing — or renders attachment ID 1 / a wrong image. The tag resolves fine in text contexts.
**Why:** `image.php::get_normalized_image_settings()` reads `$images[0]`: `if ( is_numeric( $images[0] ) ) $image['id'] = $images[0]`. Native image tags return an **array** `[ $id ]`. A custom tag returning the bare string `"15585"` makes `$images[0]` the **first character** `"1"` → attachment 1, or nothing.
**Fix:** Make custom image-source tags context-aware. Return `[]` (not `''`) on the empty path in image context:
```php
return $context === 'image' ? [ (int) $id ] : (string) $id;
```
⚠️ **OPEN CONFLICT (MMHN 2026-10-08, untested).** MMHN saw this fail the other way. A custom tag in `image.useDynamicData` returning a string rendered `src="{"`, and returning an **array** fatalled with `preg_split(): Argument #2 ($subject) must be of type string, array given` in `dynamic-data-parser.php`, attributed to Bricks' own priority-10 `Providers::get_tag_value` re-parsing the value after the plugin's priority-10 filter. Likely a Bricks version difference (2.3.x here, 2.4.x there) or a filter-priority one; it wasn't isolated. **Settle it:** on a Local box running current Bricks, register the tag at priority 10 and then at 20, return `[ $id ]` in image context, and record which one fatals. **Workaround that held either way:** skip the dynamic-data layer and inject the image into the element's settings at render, via `bricks/element/settings` keyed on the element id: `$settings['image'] = [ 'id' => $id, 'url' => wp_get_attachment_image_url( $id, 'full' ), 'size' => 'full' ]`, plus `altText` from `_wp_attachment_image_alt`. It emits a full `srcset` and reaches options-page image fields.
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

### A markup-rendering setting on a global class silently does nothing — classes contribute CSS only
**Symptom / When:** A setting written to a global class persists and reads back correctly, but nothing changes in the rendered page. The typical case is the nav-menu element's `menuIcon` (the submenu dropdown caret): the icon object sits on the menu's class, and the toggle keeps rendering Bricks' built-in chevron. A class carrying an empty `menuIcon: []` is the trace of an earlier attempt that hit the same wall.
**Why:** Global-class settings feed Bricks' **CSS emitters**, so every rule a class produces is a stylesheet rule keyed off its typed settings. Settings that produce **markup** (`type: 'icon'` controls, and anything flagged `'rerender' => true` in the element's control definition) are read at render time from `$this->settings`, the **element's own** settings. Class settings are never merged into those. So a render-affecting value on a class is stored, valid, visible in readbacks and completely inert. This is a different mechanism from the dynamic-tag entry above: that one is about markup a class can't reach, and this one is about settings a class can't deliver.
**Fix:** Put markup-rendering settings on the **element**, in the template tree. Keep the class for what it can actually deliver: the control's `css` selector entries, i.e. size and colour rules against the emitted markup. To tell which kind a control is, find it in the element's PHP and look for `'rerender' => true` or a render-time `$settings['<key>']` read. Either one means element-only.
**First seen:** WCDP, 2026-08-18 — swapping the nav dropdown caret to a custom icon set. Set on the two nav classes, it changed nothing after regen. Set on the two nav elements in the header template, the chevron was replaced everywhere, desktop and off-canvas, in one write.

### Editing `bricks_global_classes` via WP-CLI while a Bricks builder tab is open gets silently reverted
**Symptom / When:** You write Global Class settings via `wp eval`, verify the **raw DB + rendered CSS** both show the change, then minutes later the option has reverted — sometimes **partially** (one class sticks, others revert), which reads like a race. `update_option` returned `true`; the immediate re-read was correct; the revert happens later with **no CLI write in between**.
**Why:** An open builder tab holds the entire Global Classes collection in browser memory. On heartbeat autosave (or any manual save) it writes the **whole** option back from that stale copy, clobbering out-of-band CLI edits. Advanced Themer's class manager makes the builder especially write-happy. The reverse also bites: a CLI write can clobber unsaved builder work.
**Fix:** Treat `bricks_global_classes` as **single-writer**. Close every builder tab for the site before editing via CLI; after writing, re-read after **~90s** (one heartbeat window) to confirm it stuck — don't trust the immediate read. If coordination isn't possible, make the change in the builder UI instead. Per-post `_bricks_page_content_2` isn't affected the same way *unless that page is open in the builder*.
**Detect an open tab before writing, not after.** The builder renews the post's `_edit_lock` (`<unix time>:<user id>`) on heartbeat, so a lock younger than WordPress's 150-second window means someone has that template open now. Gate the write on the check, as a separate command: `wp eval '…if ( time() - $t < 150 ) exit( 1 );…' && wp eval-file build.php`. Checking in the same command as the writes only tells you afterward. If a lock was live during a write, have the editor reload that tab **without saving**, then re-read.
**PHP-side trap in the same area:** mutating the classes array through nested references (`foreach($gc as &$c){ $s =& $c['settings']; … }`) can leave the saved value unchanged. Index by position instead: `$gc[$i]['settings'][...] = …`.
**Closing the tab does not clear `_edit_lock`.** It only stops the heartbeat refreshing it, and WordPress lets the lock expire. So "I closed it" plus a 60-second-old lock is ambiguous; what proves the builder is shut is the timestamp **aging past 150s without advancing**. Poll it. The revert's blast radius is site-wide too: renames, new classes and typed settings on any class come back, not just the open page's tree.
⚠️ **OPEN CONFLICT (MMHN 2026-10-08, untested).** This entry says the builder renews `_edit_lock` on heartbeat, so a lock under 150s means an open builder (pkjsupport saw a 12s lock on an open template). MMHN saw a save leave no fresh lock: right after two templates were builder-saved, one carried a 77-day-old `_edit_lock` and the other none, and the only record of the saves was an option-write hook on `bricks_global_classes`. If MMHN is right, a lock-age gate can wave a write through while the builder is open. **Settle it:** open a template in the builder, poll its `_edit_lock` every 30s for 3 minutes, then save and re-read. Until then, treat a stale lock as weak evidence and ask before writing to a builder-maintained tree.
**First seen:** TAB, 2026-06-22 — typed-setting edits on four shared classes verified live in raw DB + render, then reverted (3 of 4) within ~2 min while a builder tab was open. Held permanently once the builder was closed. · **Extended:** pkjsupport, 2026-10-07 — a template was open in the builder (lock 12s old) while the CLI removed fields from it; caught only because a lock query happened to run in the same command. The gate above came out of it. · **Extended:** MMHN, 2026-07-21 — three class renames plus a 12-element tree collapse, both by CLI, both reverted about seven minutes later by a save from a session open the whole time; re-applied against a confirmed-stale lock, and it held.

### Editing a SHARED global class from a page where it's a secondary consumer silently restyles its primary home
**Symptom / When:** A finished, signed-off template quietly loses part of its layout (a centered column goes flush left, a width changes) with no edit to that template, no change to its element tree and nothing in its build history to explain it. It surfaces days later on a page nobody touched.
**Why:** Global classes are one site-wide store, and **a builder save writes the whole option**. When a shared component class is adjusted while looking at a page that merely reuses it, the change lands on every surface, including the one the class was designed for. Nothing in the builder marks a class as load-bearing elsewhere. This is **not** the stale-session revert above: the session is current and the save is legitimate. The blast radius comes from shared ownership.
**Fix:** (1) **Don't tune a shared class from a borrowing page.** Give the borrowing page its own BEM class alongside the shared one and put the deviation there; `_widthMax: 'none'` is the clean opt-out from a shared cap. (2) **Snapshot `bricks_global_classes` before handing the builder over and diff after.** The option has no revision history, so without a snapshot there's no way to prove what changed or restore it. A hook on `pre_update_option_bricks_global_classes` that writes the previous value outside the webroot catches builder saves and CLI writes alike.
**Finding a class's consumers before touching it:** walk every `_bricks_page_*_2` row and match the class **ID** inside `settings._cssGlobalClasses`; a rename doesn't touch the tree.
**First seen:** MMHN, 2026-07-21 — a news single's prose column went flush left under a centered header. A builder save on the About page, which reuses the shared prose class on three blocks, had dropped its auto margins and widened its max-width, a reasonable local fix with an invisible remote cost. Recovered only because same-morning JSON snapshots of the option existed; fixed by restoring the shared class and giving About's blocks their own `_widthMax: 'none'`.

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
**Inside the Email action, the recipient has its own trap.** `email.php` reads a configured recipient only when `$form_settings['emailTo'] === 'custom'` *and* `emailToCustom` is non-empty. Otherwise it falls back to `get_option( 'admin_email' )` with no indication. An address written straight into `emailTo` looks right in the data and mails the site admin. Always write the pair, `'emailTo' => 'custom'` plus `'emailToCustom' => '<address or tag>'` (shape in `02`). `emailToCustom` is dynamic-tag-parsed, so an options-field tag works there.
**First seen:** TAB, 2026-06-11 — a blueprint's leftover `fromName` on three auth forms; confirmed inert in Bricks source rather than chased. · **Extended:** THM, 2026-08-29 — the `'custom'` sentinel, read from `email.php` while routing a report form to an options-page support address.

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

`_width`, `_widthMax`, `_heightMin`, `_padding`, `_margin`, `_typography`, `_border`, `_background`, `_display`, `_alignItems`, `_justifyContent` are element-type agnostic and emit everywhere.

**The grid-template controls are layout-only too.** `_gridTemplateColumns`, `_gridTemplateRows`, `_gridGap` and `_gridAutoRows` are registered only in `elements/container.php`, so on a non-layout element they persist and emit nothing. `_display: 'grid'` still emits, which is why the class looks accepted. The rendered CSS carries `display: grid` but no `grid-template-columns`, and the children stack in one column. This commonly bites a `text-basic` with `tag: custom` + `customTag: 'li'` used as a semantic row with inline spans. Put the whole grid (display, template, gaps, breakpoints) in that class's `_cssCustom`, or rebuild the row as a `div` with real children. Resolve class → element type *before* choosing typed vs custom.

When a typed setting doesn't emit, check that element's own control registration before blaming the write path:
```bash
grep -rn "'_yourKey'" wp-content/themes/bricks/includes/elements/
```
**The same rule applies to bare (non-underscored) keys, and it cuts the other way.** Generic style controls are underscored (`_width`, `_padding`, `_typography`). **Element-specific controls declared in an element's own `set_controls()` are not.** `nav-menu` really stores `menuAlignment`, `menuGap`, `menuTypography`, `menuActiveTypography`, `subMenuTypography`, `subMenuBorder` and `subMenuPadding`, and `divider` really stores `width`, `height` and `color`. Those emit. The same bare `width`/`height` on a `text-link` has no control behind it and emits nothing. So a bare key's *shape* proves nothing either way. Resolve which element types wear the class first, then check the rendered CSS:
```bash
# 1. which elements wear the class? (repeat per template/post and meta key)
wp eval '
$map=[]; foreach(get_option("bricks_global_classes",[]) as $c) if(isset($c["id"],$c["name"])) $map[$c["name"]]=$c["id"];
$target="my-class";
foreach([<header_id>=>"_bricks_page_header_2", <footer_id>=>"_bricks_page_footer_2"] as $pid=>$k){
  $t=get_post_meta($pid,$k,true); if(!is_array($t)) continue;
  foreach($t as $el) foreach(($el["settings"]["_cssGlobalClasses"]??[]) as $cid)
    if(($map[$target]??"")===$cid) printf("%s -> %s (%s)\n",$target,$el["id"],$el["name"]);
}'
# 2. does the key emit? the rendered CSS is the authority, not the DB
curl -sk "https://<site>/<page>/?cb=$RANDOM" | grep -oE '\.my-class[^{]*\{[^}]*\}'
```
⚠️ **Where a wrong-key setting IS dead and the correct key already holds a deliberate value, delete it. Don't migrate it.** Migrating assumes the dead value was the intent. When the live key already carries a different, deliberate value, migrating silently overwrites live design with a stale number. A bulk "fix" of bare keys is worse still, because it strips real `nav-menu`/`divider` styling.
**There is NO write-path difference.** A global class written via the `bricks_global_classes` option and an element tree written into `_bricks_page_content_2` emit **byte-identical** declarations from the same settings matrix. They land in different *homes* — global-class CSS inline, element CSS in `post-{id}.min.css` (see "where CSS actually lives") — but the emitter behaves the same. `02`'s verified schema library is correct on both paths.
**First seen:** VMG, 2026-06-06 — a Contact page typed conversion where gap / flex-direction / max-width "dropped silently"; diagnosed at the time as a CLI-write / control-type limitation, and recorded that way at the 2026-07-15 harvest with an explicit ⚠️ untested-conflict flag against `02`. · **Cause found and the entry rewritten:** MMHN, 2026-07-15 — probe on Bricks 2.3.9, both write paths, one page: a `block` emitted `column-gap:44px; row-gap:55px; flex-direction:column` from `_columnGap`/`_rowGap`/`_direction` and **nothing** from `_gap`/`_flexDirection`; a `heading` emitted `gap:66px; flex-direction:row` from `_gap`/`_flexDirection` and **nothing** from `_columnGap`/`_direction`. A perfect mirror. `_width: 111px`, `_widthMax: 222px`, `_heightMin: 333px` all emitted from CLI on both paths, refuting the "number+units doesn't emit" theory (`_gap` is also number+units and works — on the right element). VMG's original incident was a conversion of **layout-element** classes using **non-layout** key names, which explains gap and flex-direction exactly. The residual "max-width dropped" is unexplained but was most likely `_maxWidth`, the pre-migration key (see the silent-strip startup audit in `01`). · **Grid-template extension:** WCDP, 2026-08-22 — a history page's decade rail (year/name rows on `text-basic` + `customTag: li`) emitted `display:grid` and no columns; verified against the Bricks 2.4.2 source, 2026-09-30. · WCDP, 2026-08-18 — a footer social-link class (on three `text-link` elements) carried bare `width`/`height: 1.5rem` beside a live `_width: auto` / `_height: 44px`. The rendered CSS was byte-identical after the bare pair was deleted, which proved it dead. Migrating it would have replaced a deliberate 44px touch target (WCAG 2.5.8) with 1.5rem. A sweep of all 1,815 global classes returned 32 bare-key hits, and 30 of them were legitimate `nav-menu`/`divider` controls. A bulk fix would have stripped the header navigation's typography, gaps, submenu borders and hover states.

### Bricks layout-element base display: `.brxe-block` is a wrapping, full-width, `flex-start` flex column; `.brxe-div` has NO display rule
**Symptom / When:** Several different layout bugs where every typed setting persists and emits correctly and nothing moves. The emitted CSS is visibly right, which is what makes these expensive:
- **`div` + flex settings:** a class sets `_direction`, `_alignItems`, `_justifyContent`, `_columnGap` or `_rowGap` and children still stack, gaps never appear, a `::before` badge sits above its text instead of beside it.
- **`block` + `_direction: row`:** a two-child row (heading + trailing icon, label + arrow) looks right on desktop, then on mobile the second child drops onto its own line.
- **`block` list of full-width rows:** a ledger/rail/row list whose hairline separators stop short of the container and whose right-aligned column sits mid-page. Each row is narrower than its parent.
- **`block` + `column-count`:** a multi-column list renders as one very tall single column.
- **`block` as a small item in a row:** a badge or share list stretches to the full width of its row.
**Why:** Bricks' base layer (`frontend-light-layer.min.css`) carries these rules and nothing else for the two elements:
```css
.brxe-block{align-items:flex-start;display:flex;flex-direction:column;width:100%}
.brxe-block{flex-wrap:wrap}      /* separate declaration further down the file */
/* .brxe-div: no display rule at all → a plain block box */
```
Each facet follows from one line:
- **`div` is `display:block`.** `flex-direction`, gaps, `align-items` and `justify-content` are inert on it, and Bricks emits them anyway because the emitter does not know or care what the element's display is. `div` + `tag: li` is the normal way to build a list item, so **card and list-item classes are the likeliest to carry dead flex settings.** (The flex/gap control-key entry above covers which *keys* a `div` takes; this entry covers why those keys can still do nothing.)
- **`flex-wrap: wrap`.** Flex containers default to `nowrap`, so the instinct is that a row cannot wrap. Bricks opted `block` in, so `_direction: row` produces a **wrapping** row, and the second child moves down as soon as the first needs the full width. It only shows at narrow viewports.
- **`align-items: flex-start`.** In a flex column, `align-items` controls the cross axis, which is width. `flex-start` makes every child shrink to its content instead of stretching. It is invisible on plain text (a paragraph clamps and wraps anyway) and visible the moment a child needs full width for its own reasons: a `space-between` row, a background, a `border-bottom` separator. A card *grid* never shows it, because `display: grid` stretches items by default.
- **`display: flex`.** CSS multi-column layout does not apply to flex containers, so `column-count` is ignored with no warning.
- **`width: 100%`.** A `block` used as a compact item in a row takes the full row width instead of hugging its content.
- **`.brxe-section` centers, whatever its tag.** The same base layer ships `.brxe-section{align-items:center;display:flex;flex-direction:column;…}`, built to center a Container. Used as a card (`tag: aside`), its shrink-wrapped children (an eyebrow span, a heading) center while full-width children keep left-aligned text, so the stack looks mismatched with no `text-align` anywhere. This is distinct from ACSS's `:where(section)` rule, which needs a real `<section>` tag.
- **The gap axis you didn't set.** `[stack:acss]` ACSS's auto-gap module (`_auto-gap.scss`, gated on its content-gap and Bricks options) emits `:where(.brxe-block)` and `:where(.brxe-div)` with `gap: var(--content-gap)`. At zero specificity it loses to any class rule, but only on the longhands that rule declares. A class setting only `_columnGap` leaves `row-gap` at the section-scale `--content-gap`, which surfaces only when the row wraps, often weeks after the class was built. The winning rule never names your class, so find it by iterating `document.styleSheets` with `el.matches(rule.selectorText)`, not by grepping the class name.
**Fix:** Set the typed control explicitly. Never assume the element's CSS default.

| Facet | Typed fix (on the class) |
|---|---|
| flex settings on a `div` | `'_display' => 'flex'` |
| row wraps on mobile | `'_flexWrap' => 'nowrap'` on the row, plus `'_widthMin' => '1.25rem'` on the trailing icon's class (with wrapping off the icon becomes shrinkable, and `_width` alone is a flex-basis, not a floor) |
| rows shrink to content | `'_alignItems' => 'stretch'` on the **list/parent** class, not the row |
| `column-count` ignored | `'_display' => 'block'` alongside the `column-count` custom CSS. Don't switch the element to `div` instead: that kills every flex/grid setting on the class, the mirror-image trap |
| compact item goes full width | `'_width' => 'auto'` (or a fixed width) |
| `section` used as a card centers its children | `'_alignItems' => 'stretch'` (or `flex-start`), or build it as a `block` with `tag: custom` + `customTag: aside` |
| wrapped lines get a big gap | set `'_rowGap'` explicitly whenever `_columnGap` is set on a row that can wrap |

Switching an element from `div` to `block` also makes flex settings work without `_display`, but it brings `align-items: flex-start`, `flex-wrap: wrap` and `width: 100%` with it, so it is a bigger behavioural change than it looks.
Audit a tree for inert flex settings on `div`-based classes:
```bash
wp eval '
$tree = get_post_meta( <post_id>, "_bricks_page_content_2", true );
$divs = []; foreach ( $tree as $el ) if ( "div" === $el["name"] )
  foreach ( (array) ( $el["settings"]["_cssGlobalClasses"] ?? [] ) as $c ) $divs[$c] = true;
foreach ( get_option( "bricks_global_classes", [] ) as $c ) {
  if ( ! isset( $divs[ $c["id"] ] ) ) continue;
  $s = (array) $c["settings"];
  $flex = array_intersect( array_keys( $s ), ["_direction","_alignItems","_justifyContent","_columnGap","_rowGap","_flexWrap"] );
  if ( $flex && ( $s["_display"] ?? "" ) !== "flex" )
    echo $c["name"] . " -> inert: " . implode( ",", $flex ) . "\n";
}'
```
**Verify:** measure, don't look. For the row facets, compare `el.getBoundingClientRect().width` on a row against its parent (they must match), and take a narrow-viewport screenshot. The DB is right, the emitted CSS is right and the desktop render looks right in every one of these cases. A tall single column from the `column-count` facet can also push later sections past a scroll-reveal trigger, so "content below never fades in" can be a layout bug rather than an animation one.
Related: `.brxe-block` / `.brxe-container` flex-column default (the `.gap--N` entry in ACSS), and ACSS's `section > div` column rule.
**First seen:** WCDP, 2026-08-18 — a numbered steps card whose counter badge stacked above its text (`div`); the same dead settings sat in three other classes on the page, one shipped a fortnight earlier and reviewed twice. Same day, a 390px screenshot showed the external-link arrow wrapping below the title on tool cards and six directory cards, on every mobile visit since they were built (`flex-wrap`); and a key-dates `dl` whose rows ended at different x positions (`align-items`). · WCDP, 2026-08-20 — an event-card date badge and a share-link list both stretched to full width (`width: 100%`). · WCDP, 2026-08-22 — a history honor roll's `column-count: 2` rendered as one tall column (`display: flex`). Same day, new county-office rows exposed the `align-items` facet on **every** row device already shipped: 933, 837, 1200 and 1200px wide inside a 1320px container, all narrow since the day they were built, all fixed with one class edit each. Base rules re-verified against Bricks 2.4.2, 2026-09-30. · **Extended:** THM, 2026-08-30 — a wrapping credit line had a ~30px row gap against a 13.5px column gap (gap axis). THT, 2026-09-06 — an `aside` CTA card's eyebrow and heading centered over left-aligned text (`.brxe-section`).

### A Bricks element with a link is wrapped in an `<a>` — the WRAPPER is the flex item, not the element
**Symptom / When:** A typed `_margin` (or any box value meant to move siblings) on an element in a flex row changes nothing, though the CSS emits and `getComputedStyle` on the element reports the value.
**Why:** When an element carries a `link`, Bricks renders it inside an `<a>` wrapper. The element's classes and `#brxe-<id>` land on the inner node; the `<a>` gets neither. In a flex container the `<a>` is the flex item, so a margin on the inner node can't move it. Siblings without a link tighten normally with the same setting, which makes the failure look arbitrary.
**Fix:** Target the wrapper through its child:
```css
.header-bot__container > a:has(> .header-bot__account) {
	margin-left: calc(var(--space-xs) - var(--content-gap));
}
```
Diagnostic tell: dump the flex container's children and compare against the element list. A bare `a` or `div` you didn't author is a Bricks wrapper. Second tell, for measuring scripts: on an SVG element `e.className` is an `SVGAnimatedString`, not a string, so `className.match(...)` silently fails. Read `getAttribute('class')` instead.
**First seen:** MMHN, 2026-07-20 — tightening a header icon cluster (search, account, cart, burger). Cart and burger responded to a typed negative margin at once; the account icon, which links to `/my-account/`, didn't.

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
**Why:** Two things. (1) Bricks ships SVG upload (`themes/bricks/includes/svg.php`): hooks `upload_mimes` to enable SVG, `wp_handle_upload_prefilter` → sanitizes with `darylldoyle/svg-sanitizer` (the "enshrined" library) on by default, and gates on `Bricks\Capabilities::current_user_can_upload_svg()` — the `bricks_upload_svg` capability, which **no role has by default, administrators included**: `Capabilities::set_defaults()` grants administrators only full builder access, bypass-maintenance and form-submission access. It is assignable per role under Bricks → Settings → Builder Access, or from the CLI. (2) That `upload_mimes` filter only adds SVG when the capability check passes — and **WP-CLI runs as user 0**, so the cap is false and the filter no-ops. The mime/cap state under bare `wp eval` is therefore a false negative.
**Fix:** Don't add SVG mime/sanitization to the core plugin — it would duplicate Bricks' filters (double `upload_mimes`, double sanitize). Rely on Bricks; just grant the capability to the roles that need it — one command, no code: `wp cap add administrator bricks_upload_svg`. If admin SVG uploads fail on a Bricks site, that missing cap is the whole cause. When checking cap/mime state from CLI, `wp_set_current_user(1)` first or the check lies:
```php
wp eval 'wp_set_current_user(1); var_export( array_key_exists("svg", get_allowed_mime_types()) );'  // true
```
**First seen:** Highland, 2026-06-14 — concluded "WP blocks SVG, add a sanitizer to highland-core"; the block was a user-0 CLI artifact and Bricks already handles SVG safely. (General rule: verify any capability/mime/`current_user_can` check from CLI with the current user set.) · **Corrected:** WCDP, 2026-08-10 — SVG uploads were rejected for an administrator; a full hand-rolled module (mime filter, regex sanitiser, dimension shims) was written, then deleted once Bricks' source showed all of it already present and the only gap was the ungranted `bricks_upload_svg` cap. The earlier "admins yes" was wrong; re-verified against Bricks 2.4.2 `Capabilities::set_defaults()`.
**ACF reporting 0×0 for an SVG is correct — don't "fix" it.** Bricks' `svg_one_pixel_fix` (on `wp_get_attachment_image_src`) deliberately sets an SVG's width/height to `false` so WordPress does not emit `width="1" height="1"` on the `<img>`. Restoring dimensions reintroduces what Bricks is suppressing.
**Also bites `wp media import`:** importing an SVG from the CLI fails with `Sorry, you are not allowed to upload this file type` — same root cause (user-0 → SVG mime not enabled). Fix: pass `--user=1` on the command, e.g. `wp media import logo.svg --title="…" --user=1 --porcelain`. First seen Highland, 2026-07-01 (about-page CTA logo import).

### A Bricks custom icon set cannot ship in a plugin — it is `wp_options` + per-install attachment IDs, and a mismatch renders NOTHING
**Symptom / When:** Icons that worked on one install render as nothing on another after a migration, a clone to staging or a Duplicator restore. There's no error, no console warning and no empty box, because the element is simply missing from the output. Or, going the other way, you try to ship an icon set inside the project's core plugin so it travels with the codebase, and it never appears in the builder's icon picker, wherever you register it.
**Why:** Bricks stores custom icon sets as option state. `bricks_icon_sets` is a small, portable set registry. `bricks_custom_icons` has one row per icon, each carrying an **`attachment_id`** and an absolute **`url`** on that install's domain, so it's bound to that install's media library. Bricks exposes **no filter to register an icon set programmatically**, so there's no plugin-side hook to put it behind. When an icon's attachment ID doesn't resolve on the target install, `render_svg()` compares filenames and, if that fails, `return`s with **no output and no error**. Every other layer (element settings, global classes, CSS) is intact and inspectable, so nothing points at the icon layer.
**Fix:** Treat the **SVG files** as the portable artifact and rebuild the set on each install as a build step. `bin/bricks-icon-import.php` in this repo does it idempotently (icons already in the set are matched on name and skipped). PHP does not expand `~`, so give it an absolute path:
```bash
ICON_SET=MPD ICON_SRC=<dir|file.svg[:…]> wp --user=1 eval "include '$HOME/claude-config/bin/bricks-icon-import.php';"   # --user=1: SVG upload is per-role
# ICON_DRY=1 previews without writing
```
Bake two things into every file at import, which the importer does: `fill="currentColor"` (Bricks inlines the file verbatim and rewrites nothing, so without it the icon ignores every colour setting) and `aria-hidden="true"`. A **button's icon is rendered with no attributes at all**, so there's no per-element way to set it. `render_svg()` *replaces* an existing `aria-hidden` rather than duplicating it, so baking it in is safe everywhere. The option shapes and the element `icon` setting (`library: "custom_<setId>"`, `svg.{id, icon_id, url}`) are in `02`.
⚠️ **Don't verify by grepping the page for the filename.** Bricks inlines the SVG contents, so the filename never appears in the output. Elements inside a query loop also render one icon per iteration, so tree counts and `<svg>` counts legitimately differ.
**Editing the media file takes effect at once, and it's the safe way to change an icon.** Bricks reads the file at render, not a stored copy, so normalizing an SVG (`fill="currentColor"`, `width="1em" height="1em"`, prolog and comments stripped) or swapping its design shows on the next request with no re-import and no builder open. To change an icon's weight or design, overwrite the file at `get_attached_file( $id )` and keep the attachment ID: every reference (element trees, `bricks_custom_icons`, the media library) follows with zero tree writes, which matters on builder-maintained headers and footers. Back up the originals, then verify the **served** bytes, since icons are static assets behind a CDN: compare `curl -sk "$URL/…/icon.svg" | md5sum` with `md5sum` of the local file. Also: Bricks keeps enqueuing a whole bundled icon font (Themify and the like) while any one element references it, so self-hosting means swapping every last icon.
**First seen:** WCDP, 2026-08-18 — an inherited header carried seven `svg` elements pointing at an icon set that wasn't on this install. They rendered nothing, and for a week that was read as "this site doesn't use icons", which is why a page shipped without the outbound-link indicator its approved wireframe specified. The set was rebuilt per install with the importer, under a self-hosted-SVG-only icon policy (RemixIcon). · **Extended:** MMHN, 2026-07-16 / 2026-07-20 — replacing the bundled Themify icons with self-hosted SVGs that shipped `#000000` and a fixed 32px until normalized; then seven UI icons swapped from regular to bold weight in place, touching no tree.

### `svg:not([width]){min-width:1em}` outranks a global class — icons silently clamp to 1em
**Symptom / When:** An icon class asks for a size under 1em (`_width: 0.75em`). The rule emits and DevTools shows it applied, but the icon still renders at 1em. Sizing an icon *up* works fine, which makes it look like a units or inheritance problem rather than a specificity one.
**Why:** Bricks ships `svg:not([width]){min-width:1em}` and `svg:not([height]){min-height:1em}` as a floor so unsized inline SVGs can't collapse. `:not([width])` adds an attribute selector, so the rule is **(0,1,1)**. That beats a plain global class at (0,1,0), and `min-width` beats `width` regardless of order. Icon libraries that ship viewBox-only files (RemixIcon among them) match `:not([width])` on every icon, so the floor applies to all of them.
**Fix:** Give the file real `width`/`height` attributes at import time (`width="1em" height="1em"`). `:not([width])` then stops matching and the floor lifts. A presentation attribute loses to any CSS declaration, so classes size the icon freely from then on. `bin/bricks-icon-import.php` already does this for every icon it imports. Escalating the class instead (`.icon.icon`) also works, but it leaves the floor in place on every icon nobody thought to escalate.
**First seen:** WCDP, 2026-08-18 (the rule still ships in Bricks 2.4.2) — outbound-link indicators specified at 0.75em in an approved wireframe rendered at 1em on every button and card. Adding the attributes in the importer fixed all of them at once.


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

### Bricks date formatting on an ACF tag — the bare `:PHP-format` works, `:format(...)` prints the tag on the page
**Symptom / When:** An ACF `date_picker` bound to a Bricks element renders as `2026-11-03` instead of `November 3, 2026`. The obvious fixes make it worse. `{acf_my_date:format("F j, Y")}` and `{acf_my_date:date("F j, Y")}` both **emit the literal tag text into the page**, so visitors see the tag as body copy. There's no error and no log entry, and it looks as if the tag itself is broken.
**Why:** Bricks receives whatever the field's ACF `return_format` produces, so a `Y-m-d` field hands Bricks a `Y-m-d` string and Bricks prints it. Bricks' modifier syntax for this is a bare PHP date-format string after the colon, **not** a function call. An unrecognised modifier isn't stripped and doesn't raise an error. The whole tag falls through unresolved and is printed verbatim.
**Fix:** Use the bare format. It isn't quoted, and commas and spaces in it parse fine:
```
{acf_my_date:F j, Y}            →  November 3, 2026   ✅
{acf_my_date:format("F j, Y")}  →  prints the tag     ❌
{acf_my_date:date("F j, Y")}    →  prints the tag     ❌
{acf_my_date}                   →  2026-11-03
```
Don't fix it by changing the field's `return_format` to `F j, Y`. That makes `get_field()` return a human-readable string, and anything that later sorts, compares or does date math on the field silently breaks. Keep `return_format` as `Y-m-d` and format at the presentation layer. For a plain output tag, `bricks_render_dynamic_data()` is a valid preview and shows the literal-passthrough failure immediately.
**First seen:** WCDP, 2026-08-18 — a key-dates card was populated for the first time and rendered three raw `Y-m-d` strings. All three modifier spellings were tested through `bricks_render_dynamic_data()` in one pass, which showed that two of them fail by printing themselves rather than by erroring.


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
**Same mechanism on BricksExtras elements, and for grid-item keys too.** BE elements (e.g. `xproslider`) register their own control set and don't inherit Bricks' grid-item controls (`_gridItemColumnSpan` etc.). A class that carries typed grid placement therefore emits **no rule at all** on a BE element, even though the same class works on a `div`. The BE element collapses into one grid column while its siblings span correctly. The setting isn't stripped; it sits in the DB doing nothing. Fix: express the placement as `_cssCustom` on the class (`.grid-item--span-3{grid-column:span 3}` plus the breakpoint cancels), or wrap the BE element in a plain `div` that carries the typed class. **The tell: a BE element plus a typed layout key.** Before writing a layout class for an element type, grep this file for that element type.
**First seen:** Highland, 2026-06-20 — `.eyebrow` (text-basic `<p>`) wouldn't emit a typed `_columnGap` between its `::before` line and text; spent multiple passes ruling out value/cache/version/`_direction` before confirming it was the text-vs-div element type. Gap moved to `.eyebrow::before { margin-right }` in the child theme. · **Extended:** WCDP, 2026-09-13 — a Pro Slider meant to span three columns of a 4-col card grid collapsed to one. **Hit again:** WCDP, 2026-09-25, on a second carousel with a typed `grid-item--span-2`. The project entry existed and wasn't consulted.


### Bricks builder open during a WP-CLI meta write → saving from that tab silently clobbers the DB edit
**Symptom / When:** You edit `_bricks_page_content_2` (or header/footer) headlessly while a Bricks builder tab for that same post is open in a browser. The DB write succeeds and the front-end renders correctly, but the builder still shows the *old* tree — and the moment anyone clicks Save in that stale tab, your headless changes vanish.
**Why:** The builder loads the element tree into JS memory on open and treats that in-memory copy as the source of truth. Every Save POSTs the **entire** in-memory tree back over the meta key — it does not diff against, merge with, or re-read the DB first. So a builder session that opened *before* your CLI write is carrying a pre-edit snapshot, and its next Save overwrites the DB with that snapshot. The DB write itself isn't lost on contact — it's lost on the next builder Save. (Verify intactness with a `wp post meta get … --format=json` read-back, not by looking at the builder.)
**Fix:** Sequence the surfaces, never overlap them on the same post. Before a WP-CLI build/edit, close or hard-reload any open builder tab for that post. After a headless edit, a builder that needs to reopen must be **reloaded** so it pulls the fresh DB tree — only then is Save safe. If a clobber already happened, re-run the (idempotent) headless build; it takes seconds. This is the write-side twin of the golden rule's read-side validator: the builder is authoritative on save, so don't let a stale one save over you.
**Same mechanism, second surface: an open ACF options page.** An ACF options form posts **every** field it was rendered with. A Save from a tab loaded before your CLI `update_field()` therefore writes the whole stale state back. It doesn't merge, and a repeater that has since gained a row loses it cleanly, with no error. It looks exactly like "`update_field()` doesn't persist". **The write did persist. Prove it before debugging the write:**
```bash
# a POST timestamped AFTER the CLI write is conclusive
LC_ALL=C grep -a 'POST /wp-admin/admin.php?page=<options-slug>' <webapp apache access log> | tail -5
```
(On RunCloud the access log is `~/logs/apache2/<app>_access.log`, outside the webapp directory.) Then coordinate rather than re-applying. The person has to **reload** the options screen before their next Save, or the row goes again. Rule for both surfaces, and any other screen that POSTs its whole state: before a CLI write to something a human might have open, ask. After one, expect a stale tab to undo it.
**First seen:** Highland, 2026-06-30 — integrated the Leaflet service-area map into homepage #18 via WP-CLI while a builder tab was open; the tab showed the old placeholder and looked "clobbered." DB read-back confirmed the map elements (canvas/caption) were actually intact — the real risk was a Save from the stale tab, avoided by closing all builders. · WCDP, 2026-08-18 — a sixth row added to an options-page repeater from WP-CLI, confirmed in `wp_options`, reverted to five within minutes, twice. The access log showed `POST /wp-admin/admin.php?page=<slug>` after each write, from a settings tab left open during the build. It was first written up as `update_field()` failing to persist, and that diagnosis was wrong.

### A Bricks builder save does not bump `post_modified` and creates no revision — prove a save from the access log
**Symptom / When:** You need to know whether a builder save actually ran after a CLI write. `wp post get <id> --field=post_modified` still shows the CLI write's timestamp, `wp post list --post_type=revision --post_parent=<id>` shows nothing new, and the tree reads back identical to what you wrote. A save that stripped nothing looks exactly like that, and so does no save at all.
**Why:** `Bricks\Ajax::save_post()` writes the `_bricks_page_*_2` meta directly and never goes through `wp_update_post()`. WordPress therefore never touches `post_modified` or the revisions table.
**Fix:** Read the web server's access log. A builder session is `GET /<path>/?bricks=run`, followed by a `POST /wp-admin/admin-ajax.php` with a response of about 290 bytes and the `?bricks=run` referer. That POST is `bricks_save_post`. Then diff the tree against a pre-save snapshot to look for strips. Bricks' prettifier gives a second tell: after a builder save of the classes, `_cssCustom` strings come back with newlines and indentation.
**Two more save signals.** `pre_update_option_bricks_global_classes` fires on every builder save (Bricks rewrites the class registry each time, changed or not), so a snapshot hook on that option records saves, and its referer (also in the PHP error log, `…/?bricks=run`) names the template that was open. If the hook skips identical writes, it records only saves that changed a class. And a builder round-trip that changes nothing is the golden rule passing outright: the JS validator loads the tree, drops what it doesn't recognize and re-saves, so a CLI-built tree that survives byte-identical has been certified by the builder, `_conditions`, loop queries, `_attributes`, `customTag` and `_hidden` classes included. Open and save a CLI-built template on purpose, then diff.
**First seen:** WCDP, 2026-09-12 — clearing builder-save debt on a page. The readback was byte-identical and `post_modified` hadn't changed, which would have read as "not saved" without the log. · **Extended:** MMHN, 2026-07-21 — a CLI-built archive template was reported unsaved on an unchanged `post_modified`; the class-snapshot hook proved the save, which had stripped nothing.


### Frontend-only JS widget (Leaflet map etc.) renders as a blank box inside the Bricks builder canvas — looks clobbered, isn't
**Symptom / When:** A component whose visuals are produced by a front-end JS library (Leaflet map, a slider, anything mounted by an enqueued script) shows as an empty styled `<div>` in the Bricks **builder** canvas, while rendering perfectly on the live page. Easy to misread as a failed/clobbered edit.
**Why:** The builder canvas is its own iframe/context and only loads builder-registered assets. A script enqueued for the front end (here, conditionally via `wp_enqueue_scripts` on `is_front_page()`/`is_page()`) is **not** loaded in the builder, so the mount element stays an empty container. The element, its classes, and its typed settings are all present and correct in the tree — only the JS-painted result is absent.
**Why it's fine:** Nothing to fix. The element box (background/radius/aspect/size from its Bricks/ACSS classes) still shows in the builder so you can position it; the widget itself only needs to be correct on the front end, which is where it's verified (curl/grep for the markup + enqueue, then a real browser to confirm the library initialised). If a builder-canvas preview is ever genuinely wanted, Bricks' `bricks/builder/…` asset hooks can enqueue the script there too — usually not worth it.
**First seen:** Highland, 2026-06-30 — the Leaflet service-area map (init script `highland-core/assets/js/service-area-map.js`, enqueued front-end only) showed as a blank steel box in the builder; verified the map renders on the live homepage (tiles + 9 markers + radius) and confirmed the builder blank is expected, not data loss. See the companion entry above on builder-open clobber risk.

### `bricksData` ships `googleMapInstances` / `leafletMapInstances` on EVERY page — config keys prove nothing about what a page uses
**Symptom / When:** Grepping a Bricks site's HTML for its map technology hits `"leafletMapInstances":[]` and gets read as "this site uses a Leaflet / BricksExtras map element". Every Bricks (+ BricksExtras) page carries these keys as empty arrays in the frontend config, whether or not any map exists anywhere on the install.
**Why:** They are runtime instance registries in the `bricksData` bootstrap, emitted unconditionally. The same is true of other registry-style keys in that object.
**Fix:** Identify a feature by its **markup and enqueued assets**, never by config strings: for a map, a `leaflet.min.js` script tag, an `L.map(` init in a real script file, `.leaflet-container` in the DOM, or an iframe `src`. Then read the actual init script. The recipe (basemap, marker policy, interaction flags) lives there.
**First seen:** WCDP, 2026-08-18 — a sibling project's contact map was to be replicated. The config key was taken as "BricksExtras map element" and an OSM iframe was built as the stand-in. Reading Highland's actual map script revealed the real pattern (self-hosted Leaflet on a plain div, CARTO raster tiles, class-based marker styling), which then replaced the iframe. One config string produced one wrong implementation.

### protomaps-leaflet 5.x: the basemap style option is `flavor`, not `theme` — the wrong name renders blank tiles with no error
**Symptom / When:** `protomapsL.leafletLayer({ url, theme: 'light' })` loads tiles (range requests return 206, canvases are marked `leaflet-tile-loaded`), the archive is valid, and the map is a flat gray rectangle. No console error.
**Why:** v4+ renamed the built-in style selector to `flavor` (basemaps v4 flavors: light, dark, white, grayscale, black). An unknown option is ignored, and `paintRules` / `labelRules` default to empty arrays, so every tile draws nothing.
**Fix:** `flavor: 'light'`. Verify a tile independently first (`pmtiles tile archive.pmtiles z x y | wc -c`), so a blank map is known to be the renderer, not the data.
**First seen:** MMHN, 2026-09-06 — a custom store finder on self-hosted PMTiles.

### CARTO basemaps (Positron / Voyager / Dark Matter) now require an API key — the failure is a watermark tile, not an error
**Symptom / When:** Every tile on a Leaflet map using CartoDB tiles carries "API KEY REQUIRED — carto.com/basemaps/apikey" printed diagonally across it. Nothing on the page reports a problem. Tiles return HTTP 200 `image/png`, the console is clean, no requests fail, and the `img.leaflet-tile-loaded` count is full. Only the pixels (or the tile bytes) show it, and a curl or HTTP-status check passes.
**Why:** In 2026 CARTO ended keyless access to its raster basemaps. It enforces this by serving watermark tiles instead of a 401/403, so any automated check that keys on status codes can't see it. A free key exists (5M tiles/month, attribution required, one key per project, passed as `?key=…` on the tile URL), but it is one more third-party account the client must keep to keep the map alive.
**Fix:** Put the key in a wp-config constant (e.g. `PREFIX_CARTO_API_KEY`), pass it to the script with `wp_localize_script`, and append it as `?key=` on CARTO's `rastertiles/light_all` path (subdomains `abcd`). Keep a keyless fallback in the JS so an unset key never ships the watermark. Esri World Light Gray Canvas is a drop-in fallback for Positron's muted look: `https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}` (note the `{y}/{x}` order), `maxZoom: 16`, attribution `Tiles © Esri — Esri, DeLorme, NAVTEQ`. **Verify with a headless screenshot of the map box, not a status code.** ⚠️ Every site built on Highland's service-area-map pattern breaks the same way.
**First seen:** WCDP, 2026-09-13 — a contact-page HQ map ported from Highland on 2026-08-18, while CARTO was still keyless. Highland itself was fixed the same day.


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

### A grid column span or `grid-auto-rows: 1fr` must be reset to `auto` at the single-column breakpoint
**Symptom / When:** A card spanning two tracks (`_gridItemColumnSpan: 'span 2'`) or a grid with equal-height rows (`_gridAutoRows: '1fr'`) looks right on desktop. At mobile width, either the grid overflows its container and drags the page wider, or every stacked card stretches to the height of the tallest. There's no error.
**Why:** Both are plain-text typed controls that emit raw CSS (`grid-column`, `grid-auto-rows`; schemas in `02`). **`grid-column: span 2` inside a one-column grid doesn't clamp.** It creates an **implicit second column**, so the item overflows. `grid-auto-rows: 1fr` keeps sizing every implicit row to the tallest one, and in a single column that means every card. Both are correct at the breakpoints they were designed at, and both carry on silently where the grid drops to one column.
**Fix:** Cancel both at the breakpoint where the grid becomes one column, using the breakpoint suffix on the outer key:
```php
'_gridItemColumnSpan'                  => 'span 2',
'_gridItemColumnSpan:mobile_landscape' => 'auto',   // grid drops to var(--grid-1) at this breakpoint
'_gridAutoRows'                        => '1fr',
'_gridAutoRows:mobile_landscape'       => 'auto',
```
Then check that each override lands **inside a media query**. An unwrapped breakpoint rule applies at every width and silently collapses the desktop layout too:
```bash
curl -sk "https://<site>/<page>/?cb=$RANDOM" | grep -oE '@media[^{]*\{\.my-class \{grid-(column|auto-rows)[^}]*\}'
```
`_gridAutoRows` only emits when the class really sets `_display: grid`. Its `required` gate in the panel is UI-only.
**First seen:** WCDP, 2026-08-18 — a dark CTA card promoted into a 3-column card grid to fill two empty slots, verified at 3-col (spans 2 of 3), 2-col (full-width band) and 1-col (`auto`). Extended 2026-08-19: equal-height link rows via `_gridAutoRows: 1fr` measured 122px × 6 at 1440 and 117px × 6 at 991, and sized to their content at 390, with the override confirmed inside the media query.


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
**Fix:** Accept it for an admin-only door (arguably desirable — every login goes to one known place). If deep-link-after-login is wanted, remove the redirect action from the form and let `redirect_to` drive. Decide deliberately; don't discover it from a client.

**Also here — a stale docblock to ignore:** `auth-redirects.php::modify_reset_password_email()` documents "only occurs if … the WordPress auth URL behavior is not set to default". **The implementation has no such check** — it rewrites the reset email to the custom page whenever that page is set and published. Verify behaviour, not the comment:
```php
apply_filters( 'retrieve_password_message', $native_msg, 'KEY', 'user', null ); // print the link it produces
```

⚠️ **Two corrections to the above, ECT 2026-08-24.**

**`bricks/auth/custom_redirect_url` cannot solve this** — ignore that suggestion. It fires inside `Auth_Redirects::handle_auth_redirects()` on **`wp_loaded`**, and redirects immediately (`wp_safe_redirect(); exit;`). It governs *where a request to an auth URL is sent*, not *where a successful login lands*. It never sees the form submission.

**And "remove the redirect action and let `redirect_to` drive" has an unstated cost:** with `actions: ['login']` and no `redirect_to` in the URL, `login.php` sets `type: 'success'` and the user is **stranded on the login page** with a success message and no navigation. You have traded a broken deep link for a dead end on the far more common path.

Bricks offers no native "honour `redirect_to`, else fall back" — it is strictly either/or. Get both with the `bricks/form/response` filter (`integrations/form/init.php`), the last hook before the JSON response is sent:

```php
add_filter( 'bricks/form/response', function ( $response, $form ) {
    $settings = $form->get_settings();
    if ( empty( $settings['actions'] ) || ! in_array( 'login', (array) $settings['actions'], true ) ) {
        return $response;   // not the login form
    }
    if ( ! is_user_logged_in() ) {
        return $response;   // sign-in failed — leave the error alone
    }
    if ( ! empty( $response['redirectTo'] ) ) {
        return $response;   // a redirect_to was honoured; don't clobber it
    }
    $response['redirectTo']      = admin_url();
    $response['redirectTimeout'] = 0;
    return $response;
}, 10, 2 );
```

Pair it with `redirectAdminUrl` **unset** and `actions` trimmed to `['login']`.

⚠️ **Treat this hook as unguaranteed.** It is carried in the source under a bare `// NOTE: Undocumented` with **no `@since` tag** — unlike essentially every other Bricks hook, so there is no stated version history and no compatibility promise. Verified present in **2.3.10**; that is the only claim you can make about it. Re-check after every Bricks bump:
```bash
grep -rn "bricks/form/response" wp-content/themes/bricks/includes/
```
The failure mode is at least benign and diagnosable: login succeeds and goes nowhere, rather than login breaking.

**Testing this needs a stub, not curl.** Auth forms almost always carry reCAPTCHA/Turnstile, so a server-side POST can't pass the captcha (see *A client-rendered form cannot be verified with `curl`*). Exercise the registered callback directly — it only calls `get_settings()`:

```php
$stub = fn( array $actions ) => new class( $actions ) {
    private $a; public function __construct( $a ) { $this->a = $a; }
    public function get_settings() { return [ 'actions' => $this->a ]; }
};
wp_set_current_user( 2 );
var_dump( apply_filters( 'bricks/form/response', [ 'message' => 'ok' ], $stub( [ 'login' ] ) ) );
```
Cover: logged-in + no `redirectTo` → admin; `redirectTo` present → preserved; not logged in → untouched; a non-login form → untouched. Then do one real browser login, because the stub proves the filter, not the flow.

**First seen:** Highland, 2026-08-04 — auditing the Bricks custom auth pages before launch. **Re-hit ECT, 2026-08-24** — same misconfiguration on a live site, found while chasing a half-remembered "redirect issue"; the corrections above came from resolving it properly rather than accepting the trade-off.


### A cloned Bricks auth page ships a cleartext password input — audit `type`, not appearance
**Symptom / When:** None. That is the problem. A custom Reset Password (or Login) page built by duplicating another auth page renders, submits, and resets the password correctly — but the new-password field is `type="text"`, so the password is typed in the clear, shoulder-surfable, and captured by browser autofill and form history. ACSS/Bricks styling makes a text and a password input look identical apart from the dots, and nothing in the builder, WordPress, or any scanner flags it.
**Why:** Bricks form fields carry `type` per field in `settings.fields[]`. The duplicate inherits whatever the source page had, and the source is usually a *username* field that was relabelled — label, placeholder and `name` get edited, `type` does not. Two related leftovers travel the same way and are equally invisible because Bricks guards every lookup with `isset()`: `loginPassword` / `loginRemember` pointing at field IDs that exist only on the *source* page, and email-action keys (`fromName`, `emailTo`) from the original template vendor.
**Fix:** Audit the served HTML, never the builder canvas — one grep per auth page:
```bash
curl -s "https://site.com/reset-password/?action=rp&key=K&login=admin" \
  | grep -oE '<input[^>]*type="(text|password)"[^>]*>'
```
Then fix in the meta and assert the read-back (`--user=<admin>`; Bricks 2.3+ drops `_bricks_page_content_2` writes as user 0):
```php
foreach ( $settings['fields'] as $i => $f ) {
    if ( ( $f['id'] ?? '' ) === $field_id ) { $settings['fields'][$i]['type'] = 'password'; }
}
```
Leave the field's custom `name` attribute alone even when it is misleading (a password field still named `…-username-field`): Bricks remaps custom names back to `form-field-{id}` in `integrations/form/init.php` (~line 294), so the name is cosmetic, and renaming can break CSS keyed on the attribute.
**First seen:** ECT, 2026-08-24 — live adult-industry site; the Reset Password page had shipped with `type="text"` since build, found only by diffing the served HTML during an unrelated redirect audit. Every other auth-page defect that day was cosmetic; this one was real.


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
**Why:** Those tags read the global post context. `bricks/query/loop_object` only establishes it when the loop object is a `WP_Post` and you call `setup_postdata()`. Plain arrays have no post context. **Corrected at the WCDP harvest:** this entry used to say ACF's native repeater loop (`objectType: acf_<repeater>`) only works for a repeater on the current post. It works for **options-page** repeaters too, with `{acf_<repeater>_<subfield>}` resolving per row (see "Bricks ACF query loop"). So for an ACF repeater, whether it's on a post or an options page, try the native loop first. A custom query type is for a non-ACF source, or for filtering or ordering the native loop can't express. Inside either kind of loop the loop object is the **row array**, not a `WP_Post`, so a custom tag that assumes `->ID` returns nothing (the branch shape is in `02`).
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
**Curating terms from an options page (ACF taxonomy field) adds three wrinkles.** Set `'save_terms' => 0` and `'load_terms' => 0`, or ACF tries to assign the selected terms to a post that doesn't exist. Keep the order by hand: there's no `post__in` for terms and `get_terms()` re-sorts, so loop the stored ID array and `get_term()` each in sequence (a `multi_select` field does preserve drag order in the admin). And the loop object is a `WP_Term`, so a resolver testing `instanceof WP_Post` falls back to the page. Give term loops their own resolver, and register project tags that read the term directly rather than assuming native term tags resolve.
**First seen:** Highland, 2026-08-04 — `highland_faqs` query type over a Site Options repeater so the client owns the FAQ; rows are arrays, so `{acf_*}` was never going to resolve. · **Clause corrected:** WCDP, 2026-08-22 to 2026-09-13 — five options-page repeaters (officers, county offices, voting links, award rolls, meeting places) loop natively via `objectType: acf_<repeater>` with per-row subfield tags; every one passed a builder save with its `hasLoop` query intact. · **Extended:** MMHN, 2026-07-21 — client-curated homepage category chips from a Site Options `product_cat` field, replacing hardcoded label and URL pairs.

### Bricks maintenance mode serves a plain unbranded page unless a `content`-type template is wired to `maintenanceTemplate`
**Symptom / When:** Bricks → Settings → Maintenance is switched on, anonymous visitors correctly get a 503, but the page they get is Bricks' unstyled default rather than anything of yours.
**Why:** Two separate keys in the `bricks_global_settings` option, and only one of them is the toggle. `maintenanceMode` (`"maintenance"` = 503, `"comingSoon"` = 200, key **absent** = off) turns it on. The Coming Soon value is **camelCase `comingSoon`**: it is the `<option value="comingSoon">` in `includes/admin/admin-screen-settings.php`. The snake_case `coming_soon` appears only in a docblock in `includes/maintenance.php`, so copying it from there writes a value Bricks never matches. `maintenanceTemplate` holds a `bricks_template` post ID whose `_bricks_template_type` is `content`. With no template ID set, or an unpublished one, `Maintenance::get_default_maintenance_page_html()` serves the plain fallback. The custom template renders standalone. Header and footer are OFF by default (`maintenanceRenderHeader` / `maintenanceRenderFooter`, compared as the string `'1'`, opt back in), and search/archive/error templates are zeroed. The page set as Bricks' own `login_page` setting is exempt from the gate (`apply_maintenance_mode()` checks it first), so a custom login form stays reachable.
**The second-order trap:** logged-in users bypass maintenance entirely, so during a maintenance window you only ever see the site *logged in*. Any logged-in-only rendering bug masquerades as a site-wide one for the whole window. Administrators always bypass. With `bypassMaintenanceUserRoles` **unset**, `Capabilities` grants the bypass to **any** logged-in user; setting it to specific roles narrows that.
**The status-code trap:** the two modes are the same gate with different statuses. A `curl -w '%{http_code}'` deploy check that reads a `comingSoon` site's 200 as "the gate arrived off" is wrong. Verify the gate by the rendered `<title>` / template markup, or read `maintenanceMode` directly, never by status alone. Curl the homepage, one other arbitrary page (site-wide, not homepage-only) and the login page (still exempt).
**Fix:** Build a `content`-type template, set both keys, and purge. Note `maintenanceMode` is switched off by **unsetting** the key, not by writing a falsy value.
```bash
wp eval '$s=get_option("bricks_global_settings");$s["maintenanceMode"]="maintenance";update_option("bricks_global_settings",$s);' # on
wp eval '$s=get_option("bricks_global_settings");unset($s["maintenanceMode"]);update_option("bricks_global_settings",$s);'        # off
```
Purge the page cache after either toggle. The template dropdown in the admin filters on `meta_value = content`, so a template of any other type will not appear as an option.
**Three more things a branded cutover page needs.**
- **No `Retry-After`:** mode `maintenance` sends a 503 without one. Add it on `wp` priority 10 (Bricks applies maintenance at priority 9): if `\Bricks\Maintenance::is_applied()` and `get_mode() === 'maintenance'`, send the header.
- **The template's post title leaks into `<title>`:** the SEO plugin titles the page from it, so an internal name like "Maintenance — cutover" becomes the browser-tab title. Override it while `is_applied()` via `pre_get_document_title` and the SEO plugin's title filter.
- **Pre-stage without going live:** build the template with **no** `templateConditions` (it renders nowhere until selected), set `maintenanceTemplate` ahead of time, and leave `maintenanceMode` unset until cutover. Verify by switching `maintenanceMode` on for a minute and curling logged-out.

Robots.txt and `template_redirect` redirects still run in maintenance mode.
**Two more keys, and a bare preview.** `maintenanceRenderPopups` (checkbox, absent = off) and `maintenanceExcludedPosts` sit beside the header and footer keys. Setting the template's own `_bricks_page_settings` to `{"headerDisabled":true,"footerDisabled":true}` (presence = on) keeps its builder preview bare as well as the live page.
**First seen:** MBC, 2026-08-07 — built during a core/WooCommerce major update window; the wiring was found by reading `bricks/includes/maintenance.php` and `admin/admin-screen-settings.php` rather than from any documentation. · **Extended:** WCDP, 2026-09-30 — go-live maintenance page pre-staged with the mode off. · **Corrected:** THM, 2026-08-29 / THT, 2026-09-14, reconciled at their harvest 2026-10-05. THM read `comingSoon` from the admin `<select>` while gating a site before its paywall existed, and THT saw two sibling sites answer 503 (`maintenance`) and 200 (`comingSoon`) in one deploy check. This entry had said `coming_soon`. · **Extended:** MMHN, 2026-10-08 — a branded maintenance template, builder-verified on Bricks 2.4.2.


### `_cssId` on an element inside a query loop duplicates per iteration — any `aria-labelledby` pointing at it collapses to the first item
**Symptom / When:** A card grid built as a query loop, where each card's heading carries a `_cssId` and each card's link references it via `aria-labelledby`. Visually flawless; automated colour/contrast/heading checks all pass. A screen reader announces **every link with the first card's name** — six "Learn More" links all reading "Website Design and Development".
**Why:** `_cssId` is a static string in the element settings and Bricks does **not** dedupe it across loop iterations — the same HTML `id` is emitted once per item. `aria-labelledby` resolves to the **first** matching element in the document, so every reference in the loop points at item one. WCAG 2.4.4 (Link Purpose) and 4.1.2 (Name, Role, Value), both Level A, and invisible to any check that doesn't compute accessible names.
**Detect — worth running on any Bricks site:**
```js
const ids = {}; document.querySelectorAll('[id]').forEach(e => ids[e.id] = (ids[e.id]||0)+1);
console.log(Object.entries(ids).filter(([,c]) => c > 1));
```
**Fix:** Remove both the `_cssId` and the `aria-labelledby`, and name the link from data that resolves **per iteration** — the element's `text` setting, which does:
```
Learn More <span class="hidden-accessible">about {post_title}</span> <span aria-hidden="true">&rarr;</span>
```
`[stack:acss]` `.hidden-accessible` is **ACSS's** visually-hidden utility and ships in `automatic.css` on the front end. (Bricks' own equivalent is `.screen-reader-text`, from `frontend.min.css`.) Do not reach for dynamic data inside `_attributes` without reading the entry on that subject first — a loop-aware *custom* tag does resolve per item there, but whether a *native* tag does is not established.
**Rule:** `aria-labelledby`, `aria-describedby` and `label[for]` can never point between two elements that are both inside the same loop body. Reserve `_cssId` for the static wrapper one level up — which is also what the `_cssId`-as-filter-hook entry above recommends, for a different reason. `01`'s ARIA requirements carry the matching caveat.
**First seen:** JBM, 2026-09-03 — a homepage services grid emitting six identical heading ids, with all six card links announcing the first service's name. Found during a footer accessibility review; it had shipped unnoticed because it is invisible to visual inspection.


### A Bricks CPT template that renders `post_content` = per-page content with zero template risk

**Symptom / When:** A CPT's pages are near-identical because the template supplies most of the body,
and you conclude that differentiating them means editing the Bricks template — a high-blast-radius
change across every post of that type, with a CSS regen and a verification pass.

**Often you don't have to.** If the template includes a post-content element, `post_content` renders
**inline within the template's own heading hierarchy**, and per-page content becomes an ordinary
block-editor edit: no `_bricks_page_*_2` write, no `wp_set_current_user(1)`, no
`regenerate_css_files()`, no risk to the other pages.

**How TAB's happened:** an audit found 12 service pages **76% byte-identical** and correctly
identified the boilerplate as template-level — which invited a template edit. But the template
renders `post_content` directly under its first `<h2>`, so adding seven per-city `<h3>` sections
(+1,067 words) to **one** page was a `wp post update` and nothing else. The location pages had used
the same mechanism weeks earlier without anyone recording that it *was* a mechanism.

**Check before assuming a template edit (two commands):**
```bash
wp db query "SELECT meta_key FROM wp_postmeta WHERE post_id=<ID> AND meta_key LIKE '_bricks%'"
# no rows = the page is fully template-driven, so post_content is your injection point
wp post get <ID> --field=post_content | wc -c   # non-trivial length = it IS being rendered
```
Then confirm placement against the **rendered** page — map heading offsets against a known string
from `post_content` to see exactly where it lands in the hierarchy, so your new headings nest at the
right level. ⚠️ **What this does not solve:** anything genuinely emitted by the template — a bare H1,
shared H2s, furniture. Those remain template-level and stay a multi-page decision. Know which half
of the duplication you are looking at before you pick a tool.

### Bricks dynamic tags do NOT parse inside raw `post_content` — use shortcodes, including `[bricks_template id="X"]` for real Bricks elements
**Symptom / When:** A post body rendered through Bricks' `post-content` element contains a Bricks tag like `{acf_support_email}`, and the literal string prints on the page. Or one page of that kind needs a genuine Bricks element, such as a Form, that raw editor HTML can't provide.
**Why:** The `post-content` element's WordPress-source branch (`includes/elements/post-content.php`) is a bare `the_content()`, with no Bricks dynamic-data pass. Bricks' tag engine runs only on its own typed element settings, inside each element's `render()`. Core does hook `do_shortcode` onto `the_content`, so shortcodes resolve there with no Bricks involvement.
**Fix:** Wrap each value in a small shortcode (`add_shortcode( 'support_email', fn() => esc_html( get_field( 'support_email', 'option' ) ) )`), and use `get_bloginfo( 'name' )` for the site name rather than inventing an `{acf_…}`-shaped tag. For a full Bricks element, build it as its own `content`-type `bricks_template` with no conditions, and embed `[bricks_template id="<id>"]` (registered unconditionally in `includes/templates.php`) in that page's content. This keeps page-specific pieces with the page, unlike a shared template section gated on `{post_id} == <id>`, which breaks silently when the post is recreated.
**First seen:** THM, 2026-08-29 — legal-document bodies printed `{acf_…}` tags literally until shortcodes replaced them. The same build moved a report Form out of a post-ID-gated template section into a `[bricks_template]` embed.

### Bricks re-grants revision support to every Bricks-enabled post type at `init` 999 — a default-priority `remove_post_type_support()` silently loses
**Symptom / When:** `remove_post_type_support( 'page', 'revisions' )` on `init` reads correctly and throws no error, yet `post_type_supports( 'page', 'revisions' )` stays `true`. The same call for `post` works, which makes it look post-type-specific.
**Why:** `Bricks\Revisions::add_revisions_to_all_bricks_enabled_post_types()` (`includes/revisions.php`) runs on `init` at priority **999** and calls `add_post_type_support( $type, 'revisions' )` for every post type Bricks manages (`page` by default, plus any CPT enabled in Bricks settings). A removal at 10 is overwritten later in the same pass. `post` isn't on Bricks' list, so nothing races it.
**Fix:** Remove at a priority above 999, e.g. `add_action( 'init', $cb, 20000 )`, and confirm with `post_type_supports()`. This class reads correct and behaves wrong. If the goal is to stop revisions being written, removing support is enough: `wp_save_post_revision()` checks support first and bails, so a `wp_revisions_to_keep` cap on that type is unreachable.
**First seen:** THM, 2026-08-29 — removing revisions from `post` and `page`. `post` worked and `page` didn't, traced by dumping `$wp_filter['init']`. THT, 2026-09-16 — the same removal extended to every content CPT.

### Bricks `altText` — an empty string is indistinguishable from unset, so you cannot force `alt=""`

**Symptom / When:** You want a deliberate `alt=""` on an image that sits inside a link which already carries descriptive text — the W3C WAI-correct treatment for a redundant linked image. You set the element's Alt Text to an empty string. The render comes back with the **attachment's** alt text instead (often keyword-stuffed legacy media-library copy), not an empty alt.
**Why:** `bricks/includes/elements/image.php`:
```php
if ( ! empty( $settings['altText'] ) ) {
    $this->set_attribute( 'img', 'alt', esc_attr( $this->render_dynamic_data( $settings['altText'] ) ) );
}
```
`! empty('')` is false, so an empty-string `altText` takes the same branch as "never set" — Bricks skips it and the alt falls through to whatever WP resolves from `_wp_attachment_image_alt`.
**Fix:** There is **no way to emit `alt=""` from the typed control.** The reachable options are: (a) accept the attachment alt, (b) set a meaningful value (`{post_title}`), (c) clear `_wp_attachment_image_alt` on the attachment — which affects every other place that image is used, or (d) filter the rendered attribute from the core plugin. On a card whose link already has the title, (b) costs a duplicate screen-reader announcement; weigh that against the attachment alt you would otherwise inherit.
**The corollary that surprises:** an image whose attachment has **no** alt renders `alt=""` *correctly, by accident*. That correctness is fragile — it breaks silently the day someone fills in the media-library alt field. A "correct" empty alt and a missing one are indistinguishable in the rendered HTML.
**First seen:** TAB, 2026-07-15 — setting alt policy on related-post / related-service / services-archive cards. The services archive was already correct **only** because its 12 attachments happened to have empty alt.

### A shared attachment's alt text can only be right for one post — set the image element's `altText` with dynamic data
**Symptom / When:** A content review flags product pages whose image alt names a different product. Retitling products leaves every alt quoting the old title.
**Why:** Attachment alt is one value per attachment, and ACF image fields routinely point several posts at the **same** attachment (a shared nutrition panel, a shared spec sheet). Whatever the attachment alt says is wrong on every post but one, and it never follows a retitle. Separately, a static `altText` on a Bricks image element overrides the attachment alt entirely, so a hardcoded template alt can hide stale attachment alts for months.
**Fix:** Set the image element's `altText` to a dynamic string, e.g. `"altText": "Nutrition facts for {post_title}"`. Bricks 2.4.2's `image.php` passes `altText` through `render_dynamic_data()` before setting `alt`, and the shape survives a builder save (shape in `02`). Keep attachment alts accurate for the media library by naming what the image actually depicts.
**First seen:** MMHN, 2026-09-28 — a client content review plus a product-title convention change. Nutrition panels were shared across up to eight products, and the product template carried a hardcoded alt.

### Bricks image-as-figure puts `brxe-<id>` on the `<figure>`, not the `<img>` — naive verification greps find nothing

**Symptom / When:** You verify an image element by grepping rendered HTML for `<img[^>]*brxe-<id>` and get **zero hits**, so you conclude the element is not rendering — but the page looks fine and the element id *is* present in the HTML.
**Why:** With `tag: "figure"` set (the image-as-figure pattern), Bricks renders:
```html
<figure class="brxe-<id> brxe-image media-cover tag"><picture>…<img class="css-filter size-medium"></picture></figure>
```
The `brxe-<id>` and the global classes land on the **figure**. The inner `<img>` carries only sizing/ShortPixel classes, so an `<img … brxe-<id>` pattern can never match.
**Fix:** Match the wrapper first, then take the first `<img>` inside it. The id sits on the `<img>` only when the image element has **no** `tag` set. (Pairs with the `.tag` entries above the seam — same mechanism, different failure: those are about layout, this is about *verification lying to you*.)
**Also:** an element with no element-level CSS and no script renders **without** its `brxe-<id>` class and without an `id` attribute at all. A section carrying only global classes, an `_attributes` entry and a condition renders as `<section class="brxe-section section--s bg--white" aria-labelledby="…">`. Bricks only emits the per-element hooks when it has something to bind to them. So `#brxe-<id>` / `.brxe-<id>` is not a reliable verification or screenshot target. Target by a stable attribute (`section[aria-labelledby="events-title"]`) or a global class, and treat a missing `brxe-<id>` as expected, not as a strip.
**First seen:** TAB, 2026-07-15 — alt-text audit; three elements read as un-rendered and were all fine. · **Id omitted entirely:** WCDP, 2026-09-11 — three failed screenshot runs on an events section before the rendered opening tag was checked.

### Bricks Color Manager: a color row is `{id, raw, light}` and `light` accepts `var(--…)` — a two-tier palette works
**Symptom / When:** You want the Bricks color picker to offer purpose tokens (`--color-bg`) that point at raw palette values, and you need the stored shape before writing ~30 rows from WP-CLI.
**Why:** Bricks 2.4 takes the variable name from `raw`; there's no `name` key on a color row. It emits `--<name>: <light>` with `light` passed through verbatim, into `color-palettes.min.css` (and again into `style-manager.min.css`, see "Bricks never rebuilds `style-manager.min.css`"). Shades and `.bg-*` / `.text-*` utility classes exist only as extra rows or a non-empty `utilityClasses`, so a palette written without them stays clean. Legacy `hex` / `rgb` keys override `light`, so never write them. The same `--name` must not exist in both the Color Manager and the Variable Manager.
**Fix:** Builder-saved shapes are in `02` → "Bricks Style Manager — Color Manager and Variable Manager rows." Put the purpose tier in the Color Manager (it feeds the color picker) and raw palette values in a Variable Manager category. ⚠️ Bricks doesn't validate references: a `light` pointing at a variable that doesn't exist saves fine and emits a dead `var()`. Resolve every reference with `getComputedStyle` on the front end after a write.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — a builder discovery save. `light: "var(--test-hex)"` saved as written, and the readback also caught a typo'd `raw` that left a reference dangling.

### Opening the Bricks Color Manager writes the 18-color Default palette into an empty `bricks_color_palette`
**Symptom / When:** A palette option that read `[]` suddenly holds a "Default" palette after someone opens the Style Manager, and `--bricks-color-*` variables appear in the picker and on every page.
**Why:** With an empty option, Bricks falls back to its default palette (`database.php`, ~L1597–1650), and the first builder save persists it. `regenerate_css_files()` also falls back to it when the option is empty.
**Fix:** Write your own non-empty palette option so the fallback never triggers. Replace the whole option (dropping "Default") rather than appending a palette to it.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — appeared during a builder discovery save; removed when the project palette was written.

### Theme Style `containerMaxWidth` only reaches ROOT containers — a container inside a section stays at Bricks' 1100px
**Symptom / When:** You set container width to `var(--content-width)` in the Theme Style, the Theme Style CSS shows it, and every container inside a section still renders 1100px wide.
**Why:** `general → containerMaxWidth` targets `.brxe-container.root` (a top-level container). In the standard `SECTION > CONTAINER` structure the container isn't root, so it keeps Bricks' base `@layer bricks` rule, `.brxe-container { width: 1100px }`.
**Fix:** Set the **container group's** `width` control (`bricks_theme_styles[<key>]['settings']['container']['width']`, a number+units field targeting `.brxe-container`). Keep `containerMaxWidth` too, for root containers. Verify with `getBoundingClientRect().width` on a container inside a section, not by reading the Theme Style CSS. `01` → Theme Style requirements names this control.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — the first section built on the stack rendered at 1100px with `containerMaxWidth` set. Fixed through the container group; it measured 1366px at a 1634px viewport.

### Bricks' base `:where(p) { margin-block: 0 1.2em }` stacks with flex `row-gap` — use Theme Style contextual spacing, not CSS
**Symptom / When:** Paragraphs stacked in a container with `row-gap` are spaced wider than the gap (49px apart instead of 30px), while heading-to-paragraph spacing is right.
**Why:** Bricks' base layer (`frontend-layer.min.css`, `@layer bricks`) gives every non-last paragraph a 1.2em bottom margin, and a flex gap adds to margins rather than replacing them. Headings and lists carry UA margins the same way. The rule mentions no class, so it's invisible from a class audit; it was found by walking `document.styleSheets` for rules matching the element.
**Fix:** Theme Style → `contextualSpacing`:
```php
'contextualSpacingRemoveDefaultMargins' => [ 'h1,h2,h3,h4,h5,h6', 'p', 'ul', 'ol', 'figure', 'blockquote' ],  // emits `… {margin: 0;}` unlayered at (0,0,1)
'contextualSpacingHeading'   => 'var(--space-l)',   // flow spacing inside .brxe-text / post content only (`* + h*`)
'contextualSpacingParagraph' => 'var(--space-s)',   // `* + p`
'contextualSpacingCustomTarget' => [ [ 'id' => 'abc123', 'selector' => 'ul', 'marginStart' => 'var(--space-s)' ], /* ol, figure, blockquote likewise */ ],
```
⚠️ **Don't set `contextualSpacingFallback`.** It silently disables the heading spacing; see the next entry.
Removing margins without the contextual values collapses multi-paragraph rich text, where there's no gap to take over. Set both together. BEM classes (0,1,0) still override both rules.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — a defaults-only test section. Gaps measured 30 / 49 / 49px before the fix and 30 / 30 / 30px after.

### Bricks contextual spacing: setting "Fallback spacing" silently overrides the Heading spacing
**Symptom / When:** Theme Style contextual spacing has Heading = `var(--space-l)` and Fallback = `var(--space-s)`. Inside rich text, paragraphs space correctly but headings get the *fallback* value. The heading rule is in the CSS and simply loses.
**Why:** Specificity. The heading selector is `.brxe-text * + :is(h1, …, h6)`, which is (0,1,1). The fallback is `.brxe-text * + *:not(.woocommerce-mini-cart__total, .woocommerce-checkout *, .brxe-woocommerce-account-form-edit-account *)`, and `:not()` takes the specificity of its most specific argument, a class, so it comes out at (0,2,0) and beats every heading. The paragraph rule carries the same `:not()` on `p` (0,2,1), so paragraphs are unaffected, which is why the bug only shows on headings.
**Fix:** Leave `contextualSpacingFallback` unset. Give lists, figures and blockquotes their spacing through `contextualSpacingCustomTarget` rows (`{ id, selector, marginStart }`). Those emit `.brxe-text * + :is(<tag>)` at (0,1,1) on different elements, so nothing collides.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — an `<h5>` in rich text measured 20px (`--space-s`) instead of 45px (`--space-l`). After the swap: h5 45px, p and ul 20px.

### Enabling Bricks Query Filters from WP-CLI doesn't create their tables — the indexer then logs DB errors on every cron tick
**Symptom / When:** After writing `enableQueryFilters: true` into `bricks_global_settings` with `update_option` (for example while copying settings from a sibling), the PHP error log fills with `Table '…_bricks_filters_index_job' doesn't exist` from `Bricks\Query_Filters_Indexer`.
**Why:** The admin settings-save handler (`includes/admin.php`, ~L950) calls `Query_Filters::get_instance()->maybe_create_tables()` when the setting is on. A direct option write skips that handler, along with its other side effects (form-submission table, remote templates, capability defaults, a CSS regen when the cascade-layer setting changes).
**Fix:** After any CLI write of `bricks_global_settings` that turns on query filters: `wp eval 'wp_set_current_user(1); \Bricks\Query_Filters::get_instance()->maybe_create_tables();'`, then confirm the `bricks_filters_index`, `_element` and `_index_job` tables exist. Read the save handler for any other setting you enable from the CLI.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — aligning Bricks settings to a sibling build. Tables created after the fact; no further errors.

### Advanced Themer option names changed between versions — `bricks-advanced-themer__brxc_*` (3.3.x) vs `bricks-advanced-themer_brxc_*` (3.5)
**Symptom / When:** A cross-site read or copy of Advanced Themer settings finds zero rows on one install, though `get_field()` returns values there.
**Why:** AT stores its settings as ACF options under post ID `bricks-advanced-themer`. AT 3.3.15 installs show option rows with a **double** underscore; AT 3.5 on ACF 6.8 writes the standard single underscore. Field names (`brxc_*`) are unchanged.
**Fix:** Copy AT settings with ACF's API, not raw option rows: `acf_get_field( 'brxc_<name>' )` to resolve the field on the *target* version, then `update_field( $field['key'], $value, 'bricks-advanced-themer' )`, which also writes the field-key reference rows. Drop checkbox values the target's `choices` no longer offer (three class-tweak values disappeared between 3.3.15 and 3.5). When grepping options, match both patterns.
**First seen:** pkjsupport, 2026-10-04 — copying AT settings from two AT 3.3.15 siblings to AT 3.5.

### Bricks nav-menu ships `.sub-menu { line-height: 60px }` and `li { margin-left: 30px }` — both survive a typed `menuGap`
**Symptom / When:** A nav-menu styled through typed controls renders dropdown items about 87px tall, and in a column layout every item is indented 30px from its siblings (logo, CTA). On a row layout the 30px stacks on top of `menuGap`.
**Why:** Element default CSS: `:where(.brxe-nav-menu) .bricks-nav-menu .sub-menu { line-height: 60px }` and `… > li { margin: 0 0 0 30px }`. `menuGap` emits `gap` on the `<ul>`, so it doesn't replace the margin. `subMenuTypography` targets the `<a>`, so `line-height: inherit` there just inherits the 60px from the `<ul>`.
**Fix:** On the nav class, set `menuMargin => [ 'left' => '0' ]` (selector `.bricks-nav-menu > li`) and an explicit `line-height` in `subMenuTypography` (and in `menuTypography` if top-level items need it). Measure the item height after; don't trust a screenshot of one item. Shapes: `02` → nav-menu.
**First seen:** pkjsupport, 2026-10-04 (Bricks 2.4.2) — site header. The sibling the header was modeled on carried both overrides, which is how the defaults were identified.

### Bricks text elements don't run shortcodes — use the Shortcode element (Post Content does)
**Symptom / When:** `[my_shortcode]` typed into a Basic Text (`text-basic`) or Rich Text (`text`) element prints literally on the page.
**Why:** Both elements pass their content through `render_dynamic_data()` (Bricks `{tags}`) and nothing else. `do_shortcode` runs only in the **Shortcode** element (`includes/elements/shortcode.php`) and in Post Content (`the_content`). The mirror image of "Bricks dynamic tags do NOT parse inside raw `post_content`".
**Fix:** The Shortcode element, `'shortcode' => '<p>© [year_sc] [name_sc]</p>'`. Surrounding HTML and text pass through, and dynamic tags render first. Output wraps in `div.brxe-shortcode`. Shape: `02`.
**First seen:** pkjsupport, 2026-10-07 (Bricks 2.4.2) — footer copyright year.

### Re-running a WP-CLI build script silently reverts every builder edit made since its last run — dry-run it against the live DB first
**Symptom / When:** A project builds its pages from idempotent scripts (rebuild = re-run), and the client or designer also edits in the builder. A script re-run for an unrelated change quietly puts back old copy, old column counts and old class values, and drops elements added in the builder. Nothing errors, and the readback stays clean, because the reverted state is valid too.
**Why:** The script writes its whole tree and its whole class set. The builder's edits exist only in the database. "Idempotent" means the script reproduces *its own* output, not the site's current state.
**Fix:** Before any re-run, dry-run the script with every write intercepted and diff what it would write against the live DB. Copy each live value into the script until it reads identical. The interception:
- `update_post_metadata` returning `true` (captures `_bricks_page_*_2` and blocks the write)
- `pre_update_option_bricks_global_classes` returning `$old` (captures, blocks)
- a blanket `pre_update_option` returning `$old`
- `wp_insert_post_empty_content` returning `true` (blocks page creation)

Prove the harness with a known difference first, and check the class option's hash is unchanged afterward. For an element the builder added, keep the builder's element ID in the script (a literal-ID key), or the dry run reports it as different forever.
**First seen:** pkjsupport, 2026-10-07 — four builder edits (a capitalized title class, intro copy, a 3-column grid, a footer statement) would have been reverted by the next script run. Caught by the first dry run, which then became a standing step (`site-build/drift-check.php` in that project). Later the same day it caught a footer link and a line break added in the builder.

### Front-end-only CSS: the builder canvas strips `bricks-is-frontend`, and Bricks' component preview keeps it — scope page-shell rules to both facts
**Symptom / When:** A page-shell rule (sticky footer, body flex, full-height layout) in a framework or plugin stylesheet that also loads in the builder canvas reshapes the canvas, or Bricks' component preview.
**Why:** `Setup::body_class()` adds `brx-body bricks-is-frontend wp-embed-responsive` on the front end. The builder canvas removes exactly those three (`builder.php`, `$frontend_only_classes`). The component preview (`components.php`) writes its own `<body class="brx-body bricks-is-frontend …">` with no `#brx-content`.
**Fix:** `body.bricks-is-frontend:has(> #brx-content)` for the shell, and `body.bricks-is-frontend > #brx-content` for the growing child. For a viewport-height rule, subtract the admin bar when logged in, or short pages scroll by 32/46px: `body.admin-bar { min-height: calc(100dvh - var(--wp-admin--admin-bar--height, 32px)) }`. WordPress core defines that variable at both breakpoints. Every other body child on a Bricks page (skip links, the new-tab description span, the admin bar, scripts) is out of flow, so a flex column lays out only header, main and footer.
**First seen:** pkjsupport, 2026-10-07 (Bricks 2.4.2) — a framework sticky-footer default. Verified with the footer at the viewport bottom and 0 scroll at 4 sizes, logged in and out.

### Bricks image element has no `sizes` control — WordPress derives `sizes` from the chosen image size, so Full ships the original
**Symptom / When:** A hero image set to size Full renders `sizes="(max-width: 1920px) 100vw, 1920px"`, and a desktop downloads the 1920 original for a ~585px column, even though `srcset` lists smaller files.
**Why:** WordPress builds `sizes` from the width of the size the element requests. Bricks passes its `image.size` setting through and exposes no separate `sizes` control.
**Fix:** Set the typed `image.size` to the registered size closest to the rendered width (and its `url` to that size's URL). `medium_large` (768w) gave `sizes="(max-width: 768px) 100vw, 768px"`, which means 768w on a 1x desktop and 1536w on 2x from the same `srcset`. Keep `loading: 'eager'` on an above-the-fold image (`02`); Bricks / WordPress already add `fetchpriority="high"`.
**Full-width `sizes` over-fetches in any column or card.** WordPress's `(max-width: Npx) 100vw, Npx` says the image spans the viewport, so a phone at 2× asks for ≥780w for a ~350px slot and skips the 768w candidate, and ShortPixel's `<picture>` copies the same `sizes` onto every `<source>`. When picking a smaller registered size isn't enough, filter `wp_get_attachment_image_attributes` at priority 20 (after core computes `sizes`) and set `sizes` by BEM image class: Bricks merges the element's classes into the attributes it passes to `wp_get_attachment_image()`, and the class is what carries the layout. Derive each value from measured slot widths across viewports, rounded up and split at Bricks' breakpoints, then check candidate boundaries at 1366 1× and 2× (rounding up can tip a slot past the next file). A per-element custom `sizes` attribute also works but must be repeated on every instance of a shared card. **Audit false positive:** when one attachment appears twice on a page, Chrome reuses the cached larger file, so a card "picks" 1536w without downloading anything.
**First seen:** pkjsupport, 2026-10-07 (Bricks 2.4.2) — home hero photo. · **Extended:** MMHN, 2026-10-01 — an About hero pulled 1536w AVIF for a 348px phone slot; the class map took throttled LCP from 4.71 to 2.27 s.

### Bricks native `breadcrumbs` climbs the post CATEGORY on blog singles — "Home / Uncategorized / Title"
**Symptom / When:** You swap a hand-rolled breadcrumb for the native `breadcrumbs` element. It's right on CPT singles and taxonomy archives, but on built-in `post` singles it renders the post's **category** as the parent, typically `Home / Uncategorized / {title}` on a site that doesn't use categories. It also renders trails on top-level archives where you may want none.
**Why:** Bricks builds the ancestor chain from the post's own taxonomy terms, not from `page_for_posts`. On a flat blog feed that parent is meaningless and links to an archive outside the URL map.
**Fix:** Keep the native element and correct the trail with `bricks/breadcrumbs/items`. ⚠️ The filter receives an array of **pre-rendered HTML strings**, not structured data, which Bricks implodes with the separator. Emit its own formats, public statics on the element class:
```php
\Bricks\Element_Breadcrumbs::$link_format;         // <a class="item" href="%s">%s</a>
\Bricks\Element_Breadcrumbs::$current_span_format; // <span class="item" aria-current="page">%s</span>
```
Keep `$items[0]` (Bricks' Home item) so the element's home label and icon settings still apply, and rebuild what follows. Return an **empty array** to suppress the element entirely, which keeps trails off primary-nav destinations. Related: `bricks/breadcrumbs/home_label`, `bricks/breadcrumbs/separator`. Define the trail once and have any legacy shortcode path read the same function; two copies of breadcrumb branching diverge. This is the visual trail only; separately emitted `BreadcrumbList` JSON-LD is unaffected.
**First seen:** MMHN, 2026-07-21 — consolidating four templates onto the native element. The news single regressed from `Home / News / {title}` to `Home / Uncategorized / {title}`; caught because one canary template was converted and rendered before the other three.

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
**Why it only bites CLI builds:** the builder never shows a disabled element in its picker, so a WP-CLI tree write is the only way to reference one that is off. That write reads back perfectly, reports success, and renders nothing: no error, no placeholder. Before writing a tree that uses a BE element the site hasn't used yet, confirm `isset( \Bricks\Elements::$elements['x<name>'] )` after flipping the option.
**Two more faces.** The builder's element search shows "Nothing found" for a disabled element. And a tree that already references one can render a visible "<element> — PHP class does not exist" placeholder box on the front end; the "no placeholder" above probably holds for anonymous visitors, with the box showing only to logged-in or builder-capable viewers (unconfirmed).
**First seen:** KSCBS, 2026-05-10 — Pro Accordion did not render until `bricksextras_pro_accordion` was flipped. · **Extended:** WCDP, 2026-09-12. An `xbreadcrumbs` element written into two templates read back intact and rendered an empty hero, because only the six BE elements the header and Home already used were enabled. · **Extended:** MMHN, 2026-07-16 — twice in one session: a footer WS Forms element showed the placeholder (`bricksextras_ws_forms=0`), and Header Search was missing from the builder search (`bricksextras_header_search=0`).

### BricksExtras `xheadersearch` — typed layout settings emit nothing (empty `$css_selector`), and "live search" is the whole Bricks Query Filters stack
**Symptom / When:** (1) A typed `_margin`, width or other layout setting on the Header Search element saves and reads back, but emits no CSS. (2) Live search is switched on and the results container never renders.
**Why:** (1) The element declares `$css_selector = ''`, so Bricks has no target for typed layout output. It's the "typed setting persists, emits no CSS" family, specific to this element. (2) `maybeLiveSearch` only wraps Bricks' native Query Filters. It needs a nested `filter-search` element (`filterQueryId` pointing at a query loop), a query-loop container whose query settings carry `is_live_search: true` and `is_live_search_wrapper_selector: "#brxe-<id>"` (the container then gets `data-brx-ls-wrapper`), and a built Query Filters index. A looping results container hand-authored inside the element by WP-CLI did not render on the front end. That's a golden-rule boundary: author that piece in the builder, then replicate it.
**Fix:** Put the element's layout in the child theme or on a wrapper, not in typed settings. Treat live search as Query Filters work (enable, index, `filter-search`, result loop); see "Bricks Query Filter elements need a manual reindex AND a cron tick after programmatic creation" and "Enabling Bricks Query Filters from WP-CLI doesn't create their tables". Simple mode (live search off) is a clean `<form role="search">` to `/?s=` and needs none of it.
**First seen:** MMHN, 2026-07-16 — a header search between the CTA and the cart. The overlay and the redirect to `/?s=` worked, and the live dropdown was deferred. The mobile-cluster margin had to move to the child theme because the element's `_margin` never emitted.

### BricksExtras nestable elements: read the default child tree from `get_nestable_item()` — it's builder-authoritative, so building it by CLI isn't a guess
**Symptom / When:** You need to build a BricksExtras nestable element (Pro Accordion, Pro Slider, offcanvas) by WP-CLI. The golden rule forbids guessing a schema, so the apparent options are a builder round-trip with a human or reverse-engineering the plugin's CSS selectors.
**Why:** There's a third source. Nestable BE elements declare their default child tree in PHP: `get_nestable_item()` on the element class (`components/classes/x-*.php`) returns the exact element array the builder inserts when you add the element. Replicating it reproduces what the builder would have produced.
**Fix:** Read the class and replicate the returned structure. For the Pro Accordion it is:
```
block  _hidden._cssClasses = x-accordion_item
 └ block tag=h4  _hidden = x-accordion_heading-wrapper
    └ block  _hidden = x-accordion_header   (+ _alignItems/_direction/_justifyContent/_flexWrap,
              _attributes role=button, tabindex=0)
       ├ text-basic tag=span  _hidden = x-accordion_title
       └ icon                 _hidden = x-accordion_icon
 └ block  _hidden = x-accordion_content
    └ div  _hidden = x-accordion_content-inner
```
⚠️ The identity classes live in **`_hidden._cssClasses`**, not `_cssGlobalClasses`. The plugin's JS finds its parts by those names, and the element breaks silently without them. Your own styling classes ride alongside in `_cssGlobalClasses`. Putting a query loop on the `x-accordion_item` block iterates the whole item.
**Styling:** BE declares its visual defaults with `:where()` (zero specificity), so one project class overrides them. State rules like `.x-accordion_header[aria-expanded=true] .brxe-icon` are (0,2,1); beat those with the doubled-class trick rather than source order.
**First seen:** MMHN, 2026-07-21 — a FAQ accordion on a guide single, built entirely by WP-CLI and verified working (click, Enter, close-sibling) with no builder round-trip.

### Bricks mega menu: there's no BricksExtras mega element — core `nav-menu` and BE Slide Menu render the same template, and an offcanvas `flex-wrap` throws it off-screen
**Symptom / When:** (1) Looking for a BricksExtras mega-menu element. There isn't one, and BE Popover is `role="tooltip"` plus Popper, not a nav panel. (2) After a long sub-panel opens inside a BricksExtras offcanvas, the whole menu vanishes; `.x-offcanvas_inner` reports a `scrollWidth` about twice its width.
**Why:** (1) The mega mechanism is Bricks core. A WP menu item carries post meta `_bricks_mega_menu_template_id` (the template ID, as a string) pointing at a `section`-type template, and any host with mega enabled renders it: the native `nav-menu` (`megaMenu`) and BricksExtras `xslidemenu` (its own `megaMenu` checkbox, rendered as `.brxe-xslidemenu_mega-menu.sub-menu`; it also drops that item's WP child items). So the primary nav can stay on a client-editable WP menu with no `nav-nested` rebuild. (2) A `.brxe-block` inner with `min-height: 100%` inside a fixed-height offcanvas keeps its default `flex-wrap: wrap` (see "Bricks layout-element base display"). Once content passes the box height, the overflow wraps into a second, off-screen column.
**Fix:** One section template, attached to the menu item and rendered by both hosts. Desktop host keys: `megaMenu: true`, `megaMenuSelector: '#brx-header'` (the panel takes that node's width and left edge), `megaMenuToggleOn: click | hover | both`. The full builder-verified shapes for both hosts are in `02`. Style each host's context by container (`.brx-megamenu …`, `.brxe-xslidemenu …`), not by breakpoint. Give the offcanvas inner `_flexWrap: nowrap`. The template renders **twice per page**, so any ids inside it need a per-render suffix. Bricks' source carries a `TODO: Global class styles missing` on mega templates; on 2.4.2 in file CSS mode the class CSS did arrive inline, so verify rather than work around it. An image in the hidden panel is the first `<img>` on every page; see `04` → "A hidden image early in the header… takes the hero's `fetchpriority`".
**Verify:** headless, open the panel by click, hover and keyboard at desktop widths (`aria-expanded`, Escape), open the slide menu inside the offcanvas at 390, and check the sub-panel's `getBoundingClientRect().left` sits inside the viewport.
**First seen:** MMHN, 2026-10-02 — a Shop mega menu: native `nav-menu` on desktop and BE Slide Menu in the mobile offcanvas, rendering one panel template. Both the header and the panel survived a builder save with zero class changes.

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

### BricksExtras control values are typed, and the wrong type fails OPEN — `false` can mean ON
**Symptom / When:** A BricksExtras element written from WP-CLI ignores a setting or does the opposite of what the setting says. `php -l` passes, the DB readback shows exactly what you wrote, the page renders without error, and the defect only shows in a browser.
**Why:** BE controls aren't booleans, and the render code reads each control type differently:

| Control type | How the render reads it | What a boolean does |
|---|---|---|
| `checkbox` (e.g. `pagination`, `pauseOnHover`, `pauseOnFocus`) | `isset( $settings['x'] )` | **`false` turns it ON**, because `false` *is* set. To disable, the key must be **absent**. |
| `select` of strings (e.g. `arrows` `'true'\|'false'`, `keyboard` `'false'\|'focused'\|'global'`, `autoplayscroll` `'autoplay'\|'autoscroll'\|'none'`) | value passed through | `true` isn't one of the options. Splide coerced `keyboard: true` to **`'global'`**, which hijacks the arrow keys document-wide. |

⚠️ **`arrows` has a second trap even when written correctly.** `'false'` is the right value, and BE converts it to a real boolean in the top-level Splide config. It copies the **raw string** into the per-breakpoint config, though, and there `"false"` is a truthy JS string, so Splide builds a `.splide__arrows` wrapper with two buttons at every width at or below the desktop breakpoint. ✅ **This is harmless.** BE's own stylesheet has `.x-slider .splide__arrows:not(.x-splide__arrows){display:none}`: it expects arrows to come from `xproslidercontrol` elements (which carry `.x-splide__arrows`) and hides anything Splide makes itself. The nodes are `display:none`, so they are out of the tab order and the accessibility tree. **Don't write a CSS rule to hide them; one already exists.**
**Fix:** Before writing ANY BE setting from WP-CLI, read its control definition. The builder never lets you produce an invalid value, so reading back builder-saved output doesn't protect a CLI build here. The control definition is the authority:
```bash
grep -n -A10 "controls\['<key>'\]" wp-content/plugins/bricksextras/components/classes/x-<element>.php
```
Then: **checkbox → set `true` or omit the key, never `false`.** **Select → use one of its literal option strings.** On BE 1.7.1 a builder save didn't reintroduce omitted checkbox keys, but re-read them after a save anyway. The `02` ProSlider table records these types.
**A third shape: a select whose render compares against a string none of its options produce.** The OffCanvas `returnFocus` control offers `'true'` / `'false'`, but `x-offcanvas-nestable.php` renders `isset( $settings['returnFocus'] ) ? 'enable' === $settings['returnFocus'] : true`. `'enable' === 'true'` is false, so **any** explicit value, including choosing "Enable" in the builder, disables return-focus, and only the unset default works. Close drops keyboard focus to the top of the document. `unset()` the key headlessly, and verify from the rendered `data-x-offcanvas` JSON, which reads `"returnFocus": false` while the settings say `'true'`. The neighboring `esc_to_close`, `trapFocus` and `preventScroll` pass through correctly, which is what makes the odd one easy to miss.
**Same mechanism as "Bricks Form: `redirectAdminUrl` silently overrides the custom redirect AND mangles it into a literal path"** (must be unset, not `false`, because the code tests `isset()`). That entry was recorded as one element's quirk. It is a pattern across BricksExtras and Bricks alike.
**First seen:** WCDP, 2026-09-14 — building a news ticker on a Pro Slider. One cause produced three bugs in a single element: `pagination => false` rendered dots, `keyboard => true` stole the arrow keys on a page with three sliders, and the `arrows` breakpoint string built DOM that only BE's own CSS was hiding. · **Extended:** THM, 2026-08-11, BE 1.7.1 — the `returnFocus` case on a mobile-header offcanvas, caught by reading back the rendered JSON.

### BricksExtras Media Player defaults `crossorigin="anonymous"` — a cross-origin source with no CORS headers never plays, silently
**Symptom / When:** The player loads its poster and controls, and pressing play gives an endless spinner. There's no console error and no failed request, and `curl -I` on the source returns `200` with `video/mp4`, so it looks like a dynamic-tag or PHP problem.
**Why:** `xmediaplayer` sets `crossorigin="anonymous"` whenever the setting is unset (`media-player-trait.php`: `isset($settings['crossorigin']) ? $settings['crossorigin'] : 'anonymous'`). The browser then treats the media request as CORS-controlled, and if the host is another origin that sends no `Access-Control-Allow-Origin`, it refuses the response without surfacing an event. `canplay` simply never fires. A plain `curl -I` looks the same either way. Use `curl -I -H "Origin: <page origin>"` and look for `access-control-*`.
**Fix:** Send `Access-Control-Allow-Origin` from the media host (required anyway if caption/WebVTT tracks are ever added), or suppress the attribute with BE's own escape hatch, the **string** `'null'`: `$el['settings']['crossorigin'] = 'null';` (shape in `02`). Same-origin playback never enforces CORS, so removing it is safe there too.
**First seen:** THT, 2026-08-31 — a trailer file on the production domain, tested from a local dev origin. The resolved URL and the file both checked out, and the attribute was found only by reading the rendered player markup.

### A typed BricksExtras setting can land on a DESCENDANT — overriding the custom property on the element itself silently loses
**Symptom / When:** A BE element styled through a custom property (`--x-burger-line-color` on a Burger Trigger) ignores your child-theme override on its own class. Inspecting that element shows your value winning, and the component still renders in the old color.
**Why:** Two traps. (1) Bricks emits the typed setting on a child, `#brxe-<id> .x-hamburger-box { --x-burger-line-color: … }`, so the bars below it inherit from that nearer ancestor and never see your declaration. (2) Matching that descendant can't win either, because the selector carries an ID (1,1,0), and hardcoding `#brxe-<id>` breaks the day the element is duplicated.
**Fix:** Override what the property *feeds*. BE paints the bars with `background-color: var(--x-burger-line-color)` at (0,1,0)/(0,1,1), so a scoped rule on the consumer wins with no `!important` and no generated id:
```css
.my-context .my-burger .x-hamburger-inner,
.my-context .my-burger .x-hamburger-inner::before,
.my-context .my-burger .x-hamburger-inner::after { background-color: var(--base); }
```
This inverts the usual "override the token, not the rule" advice in "ACSS — `automatic-bricks.css` enqueues AFTER the child theme…". There the token is declared at `:root` and is reachable. Here its declaration belongs to an ID selector on a descendant. Before writing either kind of override, find the declaring selector by iterating `document.styleSheets` for the property name.
**First seen:** THT, 2026-09-15 — a light burger treatment over a hero video.

### Bricks builder save 500s on ONE template — a stray BricksExtras `extrasCustomQueryCode` placeholder
**Symptom / When:** Builder save returns a 500 on one template while every other template saves. The site serves 200, the template renders, and its DB tree reads back fine. Any WAF audit lines against `admin-ajax.php` around the failure are the WAF *reacting* to a 5xx, not causing it.
**What fixed it:** removing `extrasCustomQueryCode` from the `block` elements carrying it. The value was BE's own demo placeholder (`<style>h1.my-heading{color:crimson}</style><h1 class='my-heading'>Just some custom HTML</h1>`), left over from an old insertion. It never rendered, because the blocks were not in custom-query mode, so stripping it changed nothing on the page, and saving worked immediately.
**Mechanism: open, deliberately not invented.** Two theories fail. Bricks' signature validation can't be it, because `Admin::process_elements_for_signature()` signs `code` only on elements named `code`/`svg` plus `queryEditor`, never a block's BE field. A WAF match on `<style>` in the POST can't be it either: a sibling template with `<style>`/`<script>` in a real `code` element saves fine, and the WAF log held no rule match. The open question is whether BE's own save-time handling of the field throws.
**Fix:** strip it wherever it appears. It is inert.
```php
foreach ( $content as $i => $e ) foreach ( array_keys( (array) ( $e['settings'] ?? [] ) ) as $k )
  if ( stripos( $k, 'extrasCustomQueryCode' ) !== false ) unset( $content[$i]['settings'][$k] );
```
Check every site in the same lineage. It rides along with any duplicated template and sits dormant until someone saves that template.
**The lesson that cost more than the fix:** comparing the failing template against a working one on the same site, and against the same template on a sibling site, corrected two theories that a plain reading of the logs had supported. Where the PHP log is unreadable, a control case is the instrument, and it's a better one, since it isolates the variable.
**First seen:** THM (LIVE), 2026-09-16 — a footer template 500ing on every save, present in every environment in the lineage.

### BricksExtras Pro Slider — `slidePadding` is CSS padding on every slide, NOT Splide's `padding` option
**Symptom / When:** After setting `slidePadding` (expecting Splide's peek/padding option), the cards inside the slider lose their own padding. A card class carrying `--space-l` renders at exactly the `slidePadding` value, and the slides sit that same distance off the container edge.
**Why:** `slidePadding` is a `css` control targeting `.x-slider_slide`, so it emits `#brxe-<id> .x-slider_slide{padding:…}` at id specificity, beating any global class on the slide. Splide's real `padding` option (which shrinks the list and keeps alignment) is not what this control sets. Builder-saved sliders elsewhere on the fleet carry `slidePadding: {left:'0', right:'0'}` precisely to neutralise it. Nothing errors; the page just renders with the wrong padding.
**Fix:** Leave `slidePadding` unset (or `0`) when the slide *is* the card, and pad through the slide's own class. For breathing room around cards (hover lift, shadow), see the clip-window entry below.
**First seen:** WCDP, 2026-09-11 — a Home events carousel; caught by measuring the first slide's computed padding (16px against a `--space-l` class), not by eye.

### BricksExtras Pro Slider — a hover-lift shadow is clipped by the track, and the clip window can't live on the track
**Symptom / When:** Clickable cards inside a Pro Slider keep their hover lift and `box-shadow`, but the shadow is cut flat at the top/bottom and at the container's left/right edges. Adding `padding` plus a negative `margin` to `.splide__track` does nothing horizontally.
**Why:** `.splide__track{overflow:hidden}` is the clip, and **Splide writes `padding-left` / `padding-right` inline on the track on every init** (from its `padding` option, default 0), so CSS padding on the track is overridden. Widening the slider *root* instead is blocked twice: BricksExtras sets `.x-slider{width:100%}` and Bricks caps every element at `max-width:100%`, so a negative margin on the root only shifts it sideways.
**Fix (verified shape, in the slider root's global class):**
```css
.my-carousel{overflow:hidden;width:calc(100% + 2rem);max-width:none;
  margin:calc(-1 * var(--space-xs)) -1rem calc(-1 * var(--space-m));
  padding:var(--space-xs) 1rem var(--space-m)}
.my-carousel .splide__track{overflow:visible}
```
The root becomes the clip window (container ± 1rem, plus vertical room for the lift). The track and list still measure exactly to the container, and Splide's own sizing is untouched because it reads the list width.
**Verify:** measure slide 0's left edge against the container's left edge at three widths.
**First seen:** WCDP, 2026-09-11 — the same carousel build. It took three iterations (track padding → root margin → root width + `max-width`); a measurement script made each wrong turn visible in seconds.


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
**Folded into the toolkit 2026-10-09** (`assets/animations/animations.js`, hardening item 7). The 08-11 hardened build claimed the AJAX re-scan fixed this but still had the bug; a SLVPR harness reproduced it against that file before the fix. The shipped fix only catches up parents that have already fired (a parent not yet in view reveals its new children when it arrives). Projects that copied the toolkit before 2026-10-09 still carry the bug.

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

### ACSS settings — write via `Database_Settings::save_settings()`, never direct option writes `[stack:acss]`
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

### ACSS per-level heading sizes (`h1-min` / `h1-max`) DO reach `--h1` — if they don't, the write path is wrong `[stack:acss]`
**Symptom / When:** You set `h1-max`/`h2-max` and the actual `--h1` still compiles from the modular-scale default — the values sit in the option, may even appear as `--h1-max` in the CSS, but the size doesn't change.
**Why:** The per-level Font Size Override is the real mechanism and it works: on ACSS 3.3.6 a `save_settings()` call carrying only `h1-min: 36` / `h1-max: 76` compiles `--h1: clamp(2.25rem, …, 4.75rem)` — exactly 36 → 76px — and delivers it on the live page (THT, 2026-09-14, single-variable test with everything else untouched; MMHN saw the same on a full brand ladder, 2026-07-16). When the size *doesn't* move, the overrides never reached the compiler: a direct `wp option update` / `option patch` write persists the keys but does not regenerate the stylesheets (see "ACSS settings — write via `Database_Settings::save_settings()`, never direct option writes"), a save under a non-admin context silently guts the output (see the headless-config entry), or the dashboard Save was never actually clicked after a DB-side edit. An earlier version of this entry attributed the symptom to a hidden `$heading-fallbacks` mode in the SCSS; that diagnosis was never reproduced and is withdrawn.
**Fix:** Write the ladder with `wp_set_current_user(1)` + `\Automatic_CSS\Model\Database_Settings::get_instance()->save_settings( $merged, true )` — the full option merged, mobile = min / desktop = max in px — then verify on the *rendered* page (`curl` the compiled `automatic.css?ver=…` for the `--h1: clamp(` line), not the DB. Do **not** re-declare `--h1`…`--h6` in the child theme or any other CSS home to express a ladder (`01` → Pinned custom tokens: irregular ladders are per-level overrides, never token re-declarations).
**Still true from the original entry:** the ACSS 3.3.6 schema has no global `body-line-height` / `text-line-height`, and `h4-line-height` is absent even though h1/h2/h3/h5/h6 have it — those go in the child theme.
**First seen:** TAB, 2026-04-25 — an irregular brand ladder (56→40→28→22→18→16) appeared not to compile from `h1-max`; the child-theme `:root` override that shipped instead is grandfathered as-built. **Corrected:** THT, 2026-09-14 — the H1-only test above proved the override compiles; entry rewritten under `00`'s correction exception (mechanism was wrong, not the incident). MMHN, 2026-07-16 — same result on a full ladder, recorded in the sibling entry "per-level Font Size Override hits a non-geometric brand scale exactly".
### ACSS `option-<slot>-clr` toggles gate whether a color slot compiles at all `[stack:acss]`
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
**Fix:** `[stack:acss]` Re-point the elements to the ACSS gap class (id `acss_import_gap--N`) and delete the bespoke class — zero new classes, no visual change. Only when the element is a block/container (column default) AND the gap maps to a space token. **NB** the ACSS space scale is fluid and large (`--space-xs` ≈ 1.9rem), so a hardcoded `1.5rem` gap has NO token equivalent — keep those as typed element settings rather than snapping to a utility.
**First seen:** TAB, 2026-06-25 — class-consolidation sweep; 17 column-stack classes re-pointed to `gap--{xs,m,l,xl}`.

### `_inner` is dead — the ACSS Container replaces it
`[stack:acss]` Layout pattern is SECTION > CONTAINER (ACSS class) > BEM elements. Do not add `__inner` BEM elements; the ACSS Container handles max-width and centering. Padding is stripped from BEM containers; section spacing utilities handle it. (Full convention in `01`.)

### ACSS — "Remove Deactivated Classes" toggle is the master ACSS→Bricks sync switch (misnamed) `[stack:acss]`
**Symptom / When:** Right-clicking a color field in the Bricks builder shows only the "Default" palette — no ACSS-named palettes — even though ACSS is configured and the variables render fine on the front end. `wp option get bricks_color_palette` returns `[]`.
**Why:** Despite the label, ACSS's "Remove Deactivated Classes" toggle is the gate for the entire ACSS → Bricks DB sync on save. With it off, `Bricks::after_save_settings()` bails before importing palettes, so `bricks_color_palette` stays empty.
**Fix:** WP Admin → Automatic.css → Options → Bricks Enhancements → toggle "Remove Deactivated Classes" ON, then Save the ACSS Dashboard. Verify `bricks_color_palette` now has `acss_import_*` entries.
**Default state:** ships `on` on fresh ACSS installs. Older or hand-disabled installs leave it `off`. Check it at ACSS configuration time on every project — the symptom is silent.
**First seen:** KSCBS, 2026-05-06 — Bricks color picker showed only "Default" swatches.

### ACSS — `clamp()` values in an `@supports` block override the rem fallbacks in `:root` `[stack:acss]`
**Symptom / When:** Auditing ACSS sizes from `automatic-variables.css` shows fixed rem values (`--h2: 2.28rem` ≈ 36px). A child-theme override planned on that basis does not match what renders. The same thing happens with the **space** scale: `--space-xs: 1.896rem` or `--space-s: 2.133rem` makes a correctly drawn `padding-block: var(--space-xs)` look like a mistake, and invites "correcting" it to a smaller step or a magic number.
**Why:** ACSS ships two declarations per typography **and spacing** variable (`--h*`, `--text-*`, `--space-*`). The rem value in `automatic-variables.css` is a fallback. The real value is a `clamp()` redeclaration inside an `@supports (font-size: clamp(...))` block in `automatic.css` (lines ≈ 5099+). Every modern browser supports `clamp()`, so the `@supports` block always wins.
**Fix:** Audit the actual values from `automatic.css` lines 5099+. When overriding a heading/text size from the child theme, write a new `clamp()` to preserve fluid scaling — a fixed rem override loses fluid behavior. Retune the `Xvw + Yrem` slope when changing a max.
Read the clamp for any scale, not the fallback:
```bash
grep -oE '\-\-space-(xs|s|m|l|xl):\s*clamp\([^)]*\)' wp-content/uploads/automatic-css/automatic.css | sort -u
```
At the small end the fallback overstates the space scale by more than 2× (rendered roughly `--space-xs` 0.84rem, `--space-s` 1.13rem, `--space-m` 1.5rem, `--space-l` 2.0rem). The authoring rule doesn't change: reference the token, never the number. But any judgement *about* a token (is this step too big for a 44px row?) has to come from the clamp.
**First seen:** KSCBS, 2026-05-05 — overriding `--h1`/`--h2`; the real gap to brand spec was 2px, not the 6px the rem fallback implied. · WCDP, 2026-08-18 — checking whether an approved wireframe's table-row padding was sane. The fallback file said it was about three times too large, and the clamp said it was correct as drawn.

### ACSS — spacing/text scale stops at `xs`; using `2xs` silently falls back to an invalid var
**Symptom / When:** A typed setting or `_cssCustom` rule using `var(--space-2xs)` or `var(--text-2xs)` saves cleanly and the var reference is in the rendered CSS, but the element renders at default sizing.
**Why:** `[stack:acss]` ACSS emits only `--space-xs/s/m/l/xl/xxl` and `--text-xs/s/m/l/xl/xxl`. There is no `2xs`. `var(--space-2xs)` with no fallback is invalid; the browser drops the property silently.
**Fix:** For tighter sizing than `xs`, use literal rems, or provide a fallback: `var(--space-2xs, 0.5rem)`.
**First seen:** KSCBS, 2026-05-07 — mobile_portrait padding on `home-cta__btn` used `var(--space-2xs)`.

### ACSS — `automatic-bricks.css` enqueues AFTER the child theme; override ACSS tokens via `:root`, not selectors `[stack:acss]`
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

### ACSS — button bg-context wrappers override variant classes via specificity `[stack:acss]`
**Symptom / When:** A button set to `btn--base` (or any `btn--*` variant) inside a `.bg--dark` / `.bg--light` section ignores the variant — it renders the configured bg-context color regardless.
**Why:** ACSS's "BG Color Buttons" system emits `.bg--dark [class*="btn--"]` at (0,3,0), beating variant rules at (0,2,0). `[class*="btn--"]` matches every variant, so the variant override is silently overridden. Separately, the stock `.btn--base` rule is broken outside a `.bg--*` context (off-white text on off-white bg). ACSS ships no `.bg--primary` wrapper — green-section CTAs need a project-defined system.
**Fix:** Either change the bg-context button variables in ACSS Dashboard → Buttons, or override the wrapper at equal-or-higher specificity in the child theme — the child sheet enqueues after `automatic.css`, so `(0,3,0)` matching the ACSS wrapper wins. Encode the brand rule once project-wide rather than fighting it per button.
**First seen:** KSCBS, 2026-05-07 — hero buttons set to `btn--base` rendered green-on-charcoal, failing the dark-section contrast rule.

### ACSS button variants: the Bricks picker, the Button Style dropdown and the compiled CSS are three independent lists — `btn--action` is never in the dropdown, and a picker class can emit zero CSS `[stack:acss]`
**Symptom / When:** Two faces. (1) You enable an ACSS colour's button variant (`option-action-btn` → `on`), confirm `.btn--action` is emitted, and then cannot select it anywhere in the Bricks Button element's Style dropdown. (2) The inverse: `.btn--white` (or any other `btn--*`) sits in the Bricks Global Classes list and the class picker, so it reads as available — but a button wearing it renders unstyled, or falls back to whatever its `style` value alone provides. Designing a wireframe against it produces a button that cannot be built.
**Why:** Three lists that never consult each other:
- **Picker** — the ACSS import registers the *whole* `btn--*` family as Bricks global classes regardless of configuration. Presence there proves nothing.
- **CSS** — the SCSS `load-buttons` mixin emits only the variants whose `option-<slot>-btn` toggle is on (and whose `option-<slot>-clr` is on).
- **Dropdown** — `Buttons_Styles::get_styles_list()` iterates the hardcoded PHP map `Buttons_Styles::$acss_colors_list` (primary / secondary / tertiary / accent / base / neutral / warning / info / danger / success), filtered to the enabled toggles. There is **no `action` row**, so no toggle can ever make it appear. This bites precisely on brands whose button colour is `--action`, the slot ACSS itself recommends for that job.
Separately, `btn--outline` is a **modifier, not a variant**: every emitted rule is compound (`.btn--primary.btn--outline`, `.btn--secondary-light.btn--outline`, …) and bare `.btn--outline` has no rule at all. And the Button `style` control is a single-value select — a value outside its options list renders (Bricks emits `bricks-background-{style}` blind and ACSS's `render_attributes` filter rewrites the prefix) but is exactly what the builder's JS tree validator **resets to the default on the next save**. So neither a missing variant nor a modifier can be smuggled through `style`.
**The same split covers `size`.** ACSS replaces Bricks' own size enum (`sm`/`md`/`lg`/`xl`) through the same controls filter (`add_bricks_buttons_sizes()` → `get_sizes_list()`, default `btn--m`), and the Bricks Button emits the stored value as a class. Whether that class has front-end CSS is a separate question. A plausible `btn--l` / `btn--lg` stores fine and renders identically to no size class when no stylesheet the page actually loads carries its rule. `btn--lg` exists nowhere. The size rules (`btn--xs`…`btn--xl`) compile into `automatic-gutenberg.css`, which is front-end on some installs and editor-only on others. So check the page's `<link>` list as well as the grep.
**Fix:** Before designing or building against a variant, ask the compiled CSS, not the picker:
```bash
grep -ohE '\.btn--[a-z-]+' wp-content/uploads/automatic-css/*.css | sort -u   # what ACSS actually emits
wp eval '$s=get_option("automatic_css_settings",[]); foreach($s as $k=>$v){ if(strpos($k,"option-")===0 && strpos($k,"btn")!==false) printf("%-34s %s\n",$k,$v); }'
```
Then read the Button control's real options list before writing a `style` value:
```bash
wp eval '$e=new \Bricks\Element_Button(); $e->set_controls();
echo json_encode(apply_filters("bricks/elements/button/controls",$e->controls)["style"]["options"]);'
```
To offer a variant the map lacks, register it — at priority **> 10**, so it lands after ACSS replaces the options array at the default priority:
```php
add_filter( 'bricks/elements/button/controls', function ( $controls ) {
    $controls['style']['options']['btn--action'] = 'Action';
    return $controls;
}, 20 );
```
Register it, don't smuggle it. For a filled + outline pair, use one base variant for both and add `acss_import_btn--outline` to `_cssGlobalClasses` on the second — the modifier goes in the class list, never in `style`.
**Related:** "Bricks button utility classes (`btn--outline`, `btn--primary`) are Bricks-injected, not user-defined" — the consuming CSS exists only on a real Bricks Button element.
**First seen:** WCDP, 2026-08-10 — the Donate CTA needed `--action` (the only red in the palette that clears AA under white text) and the dropdown had no way to offer it; fixed with the priority-20 filter. WCDP, 2026-08-18 — an approved wireframe specified a white hero button and a ghost secondary; `.btn--white` was in the picker but `option-white-btn` was off and it emitted nothing. Rebuilt as `btn--secondary-light` (12.39:1 on navy) and `btn--secondary-light` + `btn--outline` (9.34:1), with no new button CSS. ACSS 3.3.6. · **Extended:** THT, 2026-09-01 — a "large" banner CTA built to a mockup's `.btn--lg` would have rendered at base size. The grep found no front-end rule, so the CTA was sized with typed padding/font-size instead.

### ACSS `[class*="btn--"]` matches ANY class containing the substring — a BEM modifier like `header__btn--panel` picks up button skin `[stack:acss]`
**Symptom / When:** An ordinary element styled by its own global class (here a `text-link`) loses its background and renders as a transparent box with a 2px `currentColor` border as soon as it also carries a class whose *name* contains `btn--`. Its global-class CSS is emitted and correct, but something later overrides it.
**Why:** ACSS targets buttons with substring attribute selectors. The compiled `automatic.css` itself ships context rules like `.bg--light [class*="btn--"]` and `.bg--ultra-light [class*="btn--"].btn--outline`, and builds whose button module compiles globally add a bare `[class*="btn--"]:where(:not(.btn--none, …))` rule with background, color, padding and border. Each of these matches *any* class attribute containing `btn--`, so a BEM block named `*__btn` with any `--modifier` trips it. Outside a real button variant, the `--btn-*` variables resolve to nothing useful, and the element gets the skeleton skin.
**Fix:** Never put `btn--` inside a non-button class name. Rename the block (`header__join` / `header__join--panel`). If a rename is impossible, add `.btn--none`, which the bare selector excludes. Audit with `grep -o '\[class\*="btn--"\][^{]*' wp-content/uploads/automatic-css/*.css` to see which contexts apply on the install.
**First seen:** THT, 2026-09-06 — the offcanvas-panel copy of a header CTA pill, modifier `header__btn--panel`.

### ACSS — "light"/"dark" variants of a NEAR-BLACK base resolve to LIGHT colors `[stack:acss]`
**Symptom / When:** You build a dark-theme surface on `var(--base-light)` expecting "slightly lighter than the near-black base" and get a light grey-lavender. White text on it is unreadable.
**Why:** ACSS variant lightness values are absolute-ish scale positions, **not relative offsets** from the base. For a base around `#08090D` (L≈4%), `--base-light` lands in genuinely light territory. There is no generated "base but 7% lighter" variable — the mental model of `-light` meaning "a bit lighter than what I set" is simply wrong at the dark end of the scale.
**Fix:** Derive dark surfaces from the base **hue** with explicit lightness — ACSS exposes the HSL components:
```css
--surface:      hsl( var(--base-h), var(--base-s), 11% );
--surface-deep: hsl( var(--base-h), var(--base-s),  7% );
```
Still brand-tracked (hue and saturation follow the palette), and guaranteed dark.
**First seen:** NLTA, 2026-07-06 — form inputs on a dark page rendered lavender with white text on top.

### ACSS — changing a base color hex in the Dashboard can wipe variation overrides on that family `[stack:acss]`
**Symptom / When:** A manual variation override on a color (e.g. a customized `--base-light`) does not survive a change to the parent base color hex.
**Why:** Not fully pinned down — appears that reconfiguring a parent color regenerates the family and discards manual variation overrides.
**Fix:** Any time you change a base color in the ACSS Dashboard, re-verify variation overrides on that family in the same session.
**First seen:** KSCBS, 2026-05-10 — a Warm Silver override on `--base-light` did not survive a `--base` hex change. (Provisional — if confirmed across more cases, promote to a firmer entry.)

### ACSS — `:where(section…)` makes any hand-rendered `<section>` flex-column-centered; `section > div` forces its children to column `[stack:acss]`
**Symptom / When:** A plugin/PHP-rendered card built as `<section class="card">` (with `<div>` children) renders with content horizontally centred and spread vertically, and direct-child rows you set `display:flex` come out stacked as columns — though your CSS never says so. Shows only on real pages (ACSS loaded), not in a stripped mockup.
**Why:** ACSS ships `section:where(:not(.bricks-shape-divider)){display:flex;flex-direction:column;align-items:center;gap:…}` and `section > div:where(…){display:flex;flex-direction:column;align-items:flex-start;gap:…}`. Intended for Bricks sections, they match ANY top-level `<section>` and its direct `<div>` children. They use `:where()` (specificity 0,0,1) so they're trivially overridden — but ONLY for properties you explicitly declare; relying on element defaults (no `display`/`flex-direction`) lets ACSS win.
**Fix:** On hand-authored sections, declare the layout explicitly: `.card{display:block}` and `flex-direction:row` on every direct-child flex row. Don't rely on element defaults inside a `<section>` on an ACSS site.
**The reverse direction — the gutter.** The same family carries the section gutter, `:where(section:not(section section)) { padding-block: var(--section-padding-block); padding-inline: var(--gutter); }`. It matches the real `<section>` tag, so a Bricks Section element whose `tag` was switched to `div` loses the gutter and sits flush against the viewport on mobile while its siblings are padded. Leave Section elements on the `section` tag, or apply the gutter on the element's class where a `div` is genuinely required.
**Plugin markup gets it too, and the tells look like three different bugs.** WooCommerce's classic order-received and view-order markup is built from `<section class="woocommerce-order-details">`, `<section class="woocommerce-customer-details">` and `<section class="woocommerce-columns--addresses">`. On an ACSS site that produces dead vertical bands (the gutter `padding-block`, once per section), headings that look centered while `text-align` computes to `start` (flex-item `align-items: center`, which `text-align` can't fix), and stacked address columns (`flex-direction: column` beats Woo's own `display: flex`, which sets no direction). Reset on the plugin's classes, not a page class, so My Account → view-order is repaired too: `.woocommerce-order-details, .woocommerce-customer-details { display: block; align-items: initial; padding-block: 0; padding-inline: 0 }`, `.woocommerce-customer-details .woocommerce-column { display: block; align-items: initial }`, `.woocommerce-columns--addresses { display: grid; grid-template-columns: var(--grid-2); gap: var(--space-l) }`.
**The `gap` shorthand covers both axes.** A Bricks Section overridden to `display: grid` still carries ACSS's `gap: var(--container-gap)` on any axis you don't set. Setting only `_columnGap` leaves the row gap, which is invisible on a one-row desktop grid and opens a visible gap once the grid collapses to one column on mobile. Set `_rowGap` as well, and check computed `row-gap` at a mobile width.
**First seen:** VMG, 2026-06-07 — My Account dashboard cards (`<section class="card">`) rendered centered and stacked; the login card too. · **Extended:** THM (inherited from its clone source), 2026-08-09 — the gutter case. Selector re-verified against compiled ACSS 3.3.6 at harvest, 2026-10-05. · **Extended:** MMHN, 2026-07-18 — a 58/42 image-and-panel section opened a gap between image and panel on mobile only, fixed with `_rowGap: 0`. · MMHN, 2026-07-19 — three of five findings logged against a thank-you page were this one rule.

### A fixed ACSS width token in a Container's `_widthMax` replaces its viewport-safe max-width — horizontal overflow on mobile `[stack:acss]`
**Symptom / When:** A narrowed content column (`_widthMax: 'var(--width-l)'` on a Container) looks right on desktop, but at 390px the page scrolls sideways and the column is ~820px wide.
**Why:** The container's own `max-width` is `var(--content-width-safe)` = `min(var(--content-width), calc(100% - var(--gutter) * 2))`. A typed `_widthMax` replaces that whole declaration, and `--width-l` is `calc(var(--content-width) * 0.6)`, a fixed value with no viewport term, so nothing clamps it below the screen width anymore.
**Fix:** `_widthMax: 'min(var(--width-l), var(--content-width-safe))'`. That keeps the narrow column on desktop and the safe cap everywhere else. Or leave the Container alone and narrow an inner block.
**First seen:** THT, 2026-09-06 — an FAQ container, found in a 390px iframe test.

### ACSS palette shades are dashboard-derived — a WP-CLI base-color write leaves the ramp stale `[stack:acss]`
**Symptom / When:** Scripting the ACSS palette via `save_settings`: you write `color-primary` (or `color-accent`), regenerate, and `--primary` updates but `--primary-light/-dark/-hover/…` stay on the OLD colour.
**Why:** The shade ladder (`-light/-dark/-hover/-trans` + the `-h/-s/-l` partials, ~2,113 keys) is computed by the dashboard's JS and **stored in the option**; the SCSS compiler reads those stored keys — it does NOT recompute shades from the base hex. A base-only write updates the base and nothing else. (Everything non-colour — type, radius, buttons, scales, focus — has no derivation and scripts cleanly.)
**Fix:** For the palette, prompt the user to set it in the dashboard once (it runs the derivation), then script the rest by WP-CLI — the established pattern. Or replicate the derivation in PHP: keep base H/S, set each shade's L to its fixed step (ultra-light 95 / light 85 / semi-light 65 / semi-dark 35 / dark 25 / ultra-dark 10; hover a smaller L bump — confirm), write base + all `-h/-s/-l`, then `save_settings`. Convention + procedure: `01`, `02`.
**First seen:** MMHN, 2026-07-16 — `color-accent=#112233` changed `--accent` but left `--accent-light/-dark` gold; confirmed the SCSS reads the stored shade keys.

### ACSS custom CSS / Global SCSS is delivered INLINE (after automatic.css), not as a linked file `[stack:acss]`
**Symptom / When:** Custom vars/rules added in the ACSS Global SCSS are in the on-disk `automatic-custom-css.css`, but the front-end `<head>` doesn't link that file and the linked `automatic.css` still shows the framework default (e.g. `--focus-width:2px`, no `--cream`). Looks like the custom CSS isn't loading / an override was lost.
**Why:** With `cssLoading=file`, the front end enqueues `automatic.css` and the Global SCSS is added **inline** via `wp_add_inline_style` on the ACSS core handle — printed in `<style id="automaticcss-core-inline-css">` immediately AFTER the `automatic.css` `<link>`. The standalone `automatic-custom-css.css` is a build artifact, not what loads; and because the inline block follows `automatic.css`, `:root` overrides in Global SCSS win the cascade.
**Fix:** Verify custom CSS on the **rendered page**, not the disk files (`curl -sk <url> | grep -A2 automaticcss-core-inline-css`). Overriding an ACSS framework variable in Global SCSS is a valid, load-order-safe technique.
**First seen:** MMHN, 2026-07-16 — nearly reported `--focus-width:3px` working off the disk files; the page confirmed it only via the inline block.

### ACSS v3 settings UI is a shadow-DOM front-end overlay — a11y-tree automation can't reach it `[stack:acss]`
**Symptom / When:** Automating the ACSS dashboard, `read_page`/`form_input` return only the WP admin bar; none of the dashboard inputs/toggles/dropdowns are reachable by element ref.
**Why:** As of v3 the settings UI is a real-time dashboard on the front end (`?acssOpenDashboard=1`, or SHIFT+CMD+O in the builder), rendered in a shadow DOM.
**Fix:** Prefer WP-CLI (`01`/`02`) and avoid the dashboard for scriptable settings. If you must drive it (palette only), use screenshot + coordinate clicks; it's a single-expand accordion whose expanded header stays at its compact row, so expand→set→collapse in one batch keeps fields on-screen.
**First seen:** MMHN, 2026-07-16.

### ACSS type: per-level Font Size Override hits a non-geometric brand scale exactly `[stack:acss]`
**Symptom / When:** A brand type scale isn't geometric (H1 46–56, H2 34–48, H3 22–26 — H2:H3 ≠ H1:H2), so ACSS's base-size + single ratio can't land every level.
**Why:** ACSS Typography has a per-level tab (H1…H6, and XXL…XS for text) with a **Font Size Override (mobile / desktop px)** on top of the global base+scale. The mobile/desktop pair is the fluid-clamp min/max — i.e. the brand's range.
**Fix:** Set the brand ranges as per-level overrides (mobile=min, desktop=max); leave base+scale for the unspecified levels. Scriptable via `save_settings`. Pin a floor with a per-level override where a scale step would dip below it (e.g. `text-s`=14/14 for a 14px floor).
**The downward trap — a base change can compile min > max.** ACSS derives the small steps *down* from the base by dividing by each end's scale (`mob-text-scale` 1.2, `text-scale` 1.333). Raise `base-text-mob` 16 → 17 with `base-text-desk` at 18, and two divisions by 1.333 from 18 land below two by 1.2 from 17, so `--text-xs` compiles as `clamp(0.75rem, …, 0.633rem)`. Browsers resolve that to the min, so it *looks* fine, but the token is nonsense and anything doing its own math on the max end gets 10px. After any base or scale change, read the compiled clamps and pin any level whose max is below its min (`text-xs` = 12/12):
```bash
grep -oE -- '--(h[1-6]|text-[a-z]+): *clamp[^;]+' wp-content/uploads/automatic-css/automatic.css
```
**First seen:** MMHN, 2026-07-16. · **Extended:** THT, 2026-09-14 — applying a brand type ladder. The inverted `--text-xs` was caught by reading the compiled clamps, not by eye.

### ACSS is fully configurable headless via `Database_Settings::save_settings()` — but only under an admin context, or it silently GUTS `automatic-bricks.css` `[stack:acss]`
**Symptom / When:** Configuring ACSS (colors, fonts, scales) from WP-CLI. Two failure layers. (1) `\Automatic_CSS\API::update_settings( $vars )` — the documented entry point — fatals: `Call to undefined method Automatic_CSS\Model\Database_Settings::save_vars()` (API.php:86). (2) Worse and **silent**: a successful `save_settings()` from plain WP-CLI regenerates all files and reports success, but `automatic-bricks.css` collapses ~22 KB → **140 bytes** and the core bundles shed ~34 KB — the Bricks button/focus layer (`.btn--primary`, the `bricks-is-frontend` focus system) vanishes from the front end. Nothing errors; the site just quietly loses styling.
**Why:** The real save method is `Database_Settings::save_settings( $values, $trigger_css_generation = true )`. It validates `$values` against the allowed-variable list and resets any **omitted** allowed var to its default — so pass the full merged set, never deltas. The gutting: ACSS compiles a platform's SCSS layer only when that platform class injects its enabler variable (`option-bricks`, `option-ws-form`, …) through the **`automaticcss_framework_variables`** filter. **Every** platform class (`classes/Framework/Platforms/{Bricks,WSForms,Gutenberg,WooCommerce,…}.php`, ACSS 3.3.6) registers that filter inside `if ( is_admin() )`, the same hook family as the palette-sync gotcha below. A plain-CLI generation therefore sees every platform as absent. That's not only Bricks: the whole WS Form layer goes too (~1,350 `.wsf-*` rules plus the `.form--light`/`.form--dark` contexts, out of `automatic.css`), along with the Gutenberg and WooCommerce layers. A form-settings toggle like `option-forms: on` doesn't help, because the platform flag is a second, independent gate. The site mostly *looks* fine, because `automatic.css` core and `automatic-gutenberg.css` carry redundant heading/button rules. The focus system and the whole form skin are what actually go.
**Fix:** Full merge **plus** an admin-context bootstrap via `--require`:
```php
// admin-context.php:  <?php define( 'WP_ADMIN', true );
// wp --require=admin-context.php eval-file configure-acss.php
wp_set_current_user(1); // needs manage_options (CAPABILITY check)
$db = \Automatic_CSS\Model\Database_Settings::get_instance();
$vars = array_merge( $db->get_vars(), $my_changes ); // FULL set, not just deltas
$db->save_settings( $vars, true );
```
Verified byte-identical to a dashboard save with the require in place; without it, compare `automatic-bricks.css` size before/after — that file is the canary. Audit any site whose last ACSS save was headless. Input caveat: an **empty string for a color value fatals the generator** (PHPColors) — "clearing" a color means omitting the key so it resets to its default, not writing `''`.
**Plugin-side alternative** that makes a regen from *any* context (dashboard, WP-CLI, cron) compile the same CSS, with no `--require` to remember. Supply the enabler flags unconditionally from the core plugin, using ACSS's own detection:
```php
add_filter( 'automaticcss_framework_variables', function ( $variables ) {
	if ( 'bricks' === wp_get_theme()->get_template() ) {
		$variables['option-bricks'] = 'on';
	}
	if ( class_exists( 'WS_Form_Common' ) ) {
		$variables['option-ws-form'] = 'on';
	}
	return $variables; // add further platforms the same way, mirroring their Platforms/*.php detection
} );
```
Canaries: `automatic-bricks.css` ~140 bytes = gutted, tens of KB = intact. On a WS Form site, also grep `automatic.css` for `.wsf-`.
**The face that misleads: a missing ACSS button looks like a background-context problem.** Variant buttons (`btn--accent`, `btn--accent.btn--outline`) render as plain Bricks buttons, often first noticed on a `.bg--primary` band, so it looks as if the section background gates them. It doesn't. The unscoped button base and every variant live in `automatic-bricks.css` and apply with no context ancestor; the `.bg--*` rules only override `.btn--outline` colors (compare "ACSS — button bg-context wrappers override variant classes via specificity"). An unstyled ACSS button means this file was gutted or never regenerated: regenerate under an admin context and grep `automatic-bricks.css` for the variant. Don't add a brightness context to make buttons "work".
**First seen:** Highland, 2026-06-14 — `API::update_settings()` fatalled (clean fail, no DB write); switched to `save_settings()` with a full merge. **Corrected Nametank/ext-mem, 2026-08-12** — the earlier "no dashboard Save needed" claim was wrong in a way that mattered: a plain-CLI save on mmhn (2026-07-16) had silently shipped the 140-byte `automatic-bricks.css` for a month before the byte-size comparison here exposed the mechanism. The `WP_ADMIN` require restores full parity and was verified against pre-change sizes on all seven generated files. · WCDP, 2026-08-18 — a form rendered WS Form's stock skin despite `option-forms: on` and a `.form--light` wrapper. No delivered stylesheet had a single `var(--f-` consumer. With the flag filter in place, a CLI regen produced the same 333KB `automatic.css` / 28KB `automatic-bricks.css` as a dashboard regen (was 198KB / 140 bytes). The drop had gone unnoticed because the only rendered form was removed the same day. · **Extended:** MMHN, 2026-07-16 / 2026-07-21 — a gold-outline button rendered plain on a navy band and was first blamed on context scoping; refuted when five `.bg--primary` buttons on two pages styled correctly with no custom CSS.


### ACSS color shade ramps are stored per-shade, NOT recomputed from the master — must rewrite the ramp partials `[stack:acss]`
**Symptom / When:** After setting a master color (`color-primary`) via DB + regenerate, the base `--primary` is correct but the whole shade ramp (`--primary-dark`, `-light`, `-hover`, …) stays the OLD color. The dashboard's color picker recomputes ramps in JS; the PHP save path does not.
**Why:** Each shade's hue/sat/lightness is stored as ~60 option keys per family (`{color}-{mod}-{h,s,l}` plus `-alt` scheme variants). The SCSS compiler emits composites (`--primary-dark`) **from these stored partials** (composites are not themselves option keys). Change only the master and the partials are stale. `API::update_settings()` propagates saturation only (`-s`), not hue/lightness — and even that uses a stale modifier list (`medium` vs this build's `semi-light`/`semi-dark`).
**Fix:** Rewrite the partials, then regenerate. Model validated against the blueprint baseline — every shade inherits master **H** and **S**; **L** is a fixed curve independent of master:
```
ultra-light 95 · light 85 · semi-light 65 · semi-dark 35 · dark 25 · ultra-dark 10 · medium 50
comp = master L · hover = master L × 1.15      (-alt variants mirror the non-alt values)
```
Derive master H/S/L with `new \Automatic_CSS\Helpers\Color( $hex )` (`->h/->s/->l`) for exact consistency. Iterate only existing `{color}-{mod}-{h|s|l}(-alt)$` keys so nothing non-allowed is introduced. Related: each non-core color also needs its `option-{name}-clr` toggle = `'on'` or it emits no CSS at all (secondary/danger/success/warning ship OFF; only primary/base/neutral on by default).
**First seen:** Highland, 2026-06-14 — primary went red but ramp stayed teal (hue 193); rewrote 270 ramp keys across 5 families.

### ACSS `-hover` shades are LIGHTER by default — white on `--action-hover` / `--accent-hover` fails AA while `--primary-hover` may not `[stack:acss]`
**Symptom / When:** A button built on `--action` passes contrast at rest (white on the brand red = 5.88:1) and fails on hover. No warning anywhere; the hover token looks like a sibling of `--primary-hover`, which on the same palette is darker and safe.
**Why:** The stored ramp derives each `-hover` lightness as a *lighter* step (the ×1.15 rule in the ramp entry above), so by construction a white label loses contrast on hover. On the palette where this bit, the stored partials matched that rule exactly for every slot — `<slot>-hover-l` = `<slot>-comp-l` × 1.15 for action (50 → 57.5), accent (71 → 81.65), secondary, base and tertiary — giving white on `--action-hover` **3.93:1**, and `--accent-hover` lighter still. The one exception was `primary-hover-l = 34`, stored as a string and matching no ×1.15 step: an explicit override from the dashboard, and the only reason `--primary-hover` went *darker*. So "the primary hover is fine" says nothing about any other slot — it is the slot most likely to have been hand-tuned.
**Fix:** Never inherit a `-hover` token for white-on-colour without measuring it. Repoint the button's hover token to an in-family shade that clears AA — `var(--action-semi-dark)` measured 7.82:1 — via `Database_Settings::save_settings()` on `btn-action-hover` and `btn-action-hover-border-color`, so the fix lives in the framework rather than in per-element CSS. Same for `btn-action-focus-color`: a red focus ring on a red button is invisible; use `var(--white)`.
**Verify:** read the partials, not the names — `wp option get automatic_css_settings --format=json` and compare each `<slot>-hover-l` against `<slot>-comp-l`; a value that is not ×1.15 is an override somebody set.
**First seen:** WCDP, 2026-08-10 — enabling the action button variant for the Donate CTA. Partials re-read 2026-09-30 (ACSS 3.3.6) to establish that the ×1.15 rule held and `primary-hover` was the override.

### ACSS `--{color}-rgb` partials are SPACE-separated — legacy `rgba(var(--x-rgb), a)` silently kills the whole declaration
**Symptom / When:** A background (or any color-carrying declaration) that composes an ACSS `-rgb` partial into legacy `rgba()` renders as if the declaration were deleted. No error, no fallback; the property just doesn't apply. A multi-layer `background` loses every layer at once, gradient and base color together.
**Why:** `[stack:acss]` ACSS emits partials in modern space-separated form: `--accent-rgb: 224 24 28`, no commas. Substituted into comma syntax, `rgba(var(--accent-rgb), 0)` becomes `rgba(224 24 28, 0)`, which mixes the two syntaxes and is *invalid at computed-value time*. A custom-property substitution failure invalidates the **whole declaration**, not just the one color stop, which is why it reads as "the rule disappeared".
**Fix:** Use modern slash syntax, which composes cleanly with space-separated partials:
```css
background: linear-gradient(to top, var(--accent) 0%, rgb(var(--accent-rgb) / 0) 45%), var(--secondary);
```
`[stack:acss]` Prefer the ready-made `-trans-N` tokens where one exists at the alpha you need. Reach for the `-rgb` partial only for alphas outside that ladder, and always with slash syntax.
**First seen:** WCDP, 2026-08-19 — a duotone hero's gradient used `rgba(var(--accent-rgb),0)` as its transparent stop, and all three photo heroes silently dropped to bare grayscale. Caught by screenshot review; fixed with `rgb(var(--accent-rgb) / 0)`.


### ACSS → Bricks palette sync only hooks under `is_admin()` — CLI saves regenerate CSS but never update the color picker `[stack:acss]`
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


### ACSS flow-spacing `[class*="woocommerce"] * + p > * + *` leaks into Woo form rows — and fires on every page `[stack:acss]`
**Symptom / When:** Two faces. (1) On a WooCommerce classic form (My Account details and addresses, login, register) the first row is tight and every later row's field sits ~16px below its label; side-by-side First/Last name visibly misalign though their markup is identical. (2) On a non-Woo page, inline children of a hand-rendered `<p>` (a span plus a link, two text links side by side) stack with a mystery top margin.
**Why:** ACSS ships `:not(.woocommerce-checkout) [class*="woocommerce"] * + p > * + * { margin-block-start: var(--flow-spacing) }` (0,2,1). Woo builds every form row as a `<p>`, so in every row after the first the input following the label picks up `--flow-spacing` (1em). And WooCommerce adds `woocommerce-js` to `<body>` site-wide, so the attribute selector matches the body on every page: any `<p>` that follows a sibling spaces its own children. Bricks-typed elements rarely trip it (one child per element); PHP- or JS-rendered markup does. Same family as the `:where(section…)` entry: ACSS's global flow rules don't know they're styling plugin or hand-rendered markup.
**Fix:** Override the consumed token on the component, don't out-specify the selector:
```css
.woocommerce form .form-row { --flow-spacing: 0px; }
.woocommerce form .form-row input + span,
.woocommerce form .form-row select + span { display: block; margin-block-start: 6px; } /* field descriptions */
.my-component { --flow-spacing: 0; }
```
Blocks Checkout uses `.wc-block-components-*` markup and the rule already excludes `.woocommerce-checkout`, so neither is affected.
**First seen:** MMHN, 2026-07-20 — "account details form, fields have an align issue": the right-hand input measured `margin-top: 16px`, the left 0. · MMHN, 2026-09-06 — store-finder cards on a non-Woo page, spotted in a builder review.

### ACSS `option-forms` ships ~145 KB of `.form--light` / `.form--dark` rules — about 40% of `automatic.css`, used only if an element carries those classes `[stack:acss]`
**Symptom / When:** `automatic.css` around 350 KB raw on a site that skins its forms elsewhere (WS Form in the child theme, say). Bucketing the file by leading class shows `form--light` and `form--dark` as the two largest groups, far ahead of `:root`.
**Why:** With `option-forms: on`, ACSS compiles a full form skin (every `.wsf-*` WS Form rule included) twice, once per context, scoped under the two context classes. The classes are auto-imported into the Bricks picker (`acss_import_form--light/dark`), which makes them look used; nothing styles unless an element actually carries one.
**Fix:** Confirm there's no consumer: no element meta containing `form--light`, `form--dark` or `acss_import_form--`; nothing reading `var(--f-*)` (child theme, core plugin, `uploads/bricks/css`, `bricks_global_classes`); no `.wsf-` rule outside the contexts. Then set `option-forms: off` with the admin-context full-merge save ("ACSS is fully configurable headless…") and diff the rule sets: only the context rules and the `:root` `--f-*` block should go. Check `automatic-bricks.css` byte size is unchanged (the gutting canary), delete Perfmatters' minified copy of `automatic.css` if one exists, and purge the page cache.
**First seen:** MMHN, 2026-10-03 — a render-blocking CSS pass: 348 → 199 KB raw, 309 → 170 KB served minified.

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

### ACF hook-variation order: `acf/render_field/key=…` fires AFTER the field has rendered — arm string swaps on `prepare_field`
**Symptom / When:** A `gettext` swap (or any pre-render tweak) armed on `acf/render_field/key=<field key>` provably runs, and the original string still shows.
**Why:** `acf_render_field()` fires `acf/render_field` once, and ACF fans it out to variations in array order, `type` then `name` then `key` (`acf_add_action_variations( 'acf/render_field', ['type','name','key'], 0 )`). The field type's own renderer, which echoes strings like "Select taxonomy", is on the `type` variation, so a `key` hook arrives one render late. `acf_prepare_field()` runs before the render action at all.
**Fix:** Arm anything that must precede ACF's renderer on `acf/prepare_field/key=<field key>` (and see the entry above for `_name`). Verify by rendering `acf_render_field_wrap()` under `wp eval` and grepping the output for both strings.
**First seen:** THM, 2026-08-30 — relabeling a relationship field's taxonomy-filter placeholder.

### ACF relationship field's `taxonomy` setting silently zeroes every search result
**Symptom / When:** A Relationship field's search returns nothing for every query, including terms that match published posts, and its taxonomy-filter dropdown has no options.
**Why:** Two settings look alike and aren't. `filters => ['search','taxonomy']` controls which UI controls appear, and the dropdown is populated from the taxonomies registered on the field's `post_type`. The field's own `taxonomy` setting is a **content restriction** taking `taxonomy:term` pairs. A bare taxonomy slug decodes (`acf_decode_taxonomy_term()`, `explode(':')`) to an empty term, which becomes a `tax_query` clause `'terms' => ['']` ANDed into every query. No post can satisfy that. The same value feeds the dropdown's term lookup, so one bad setting produces both symptoms.
**Fix:** Leave `taxonomy` as `array()` unless you mean to hard-restrict to specific terms, and then use full `taxonomy:term` pairs. `filters` alone gives you the dropdown. Prove it by reproducing ACF's query (`acf_decode_taxonomy_terms()` → `tax_query`) in a real `WP_Query` with and without the restriction and comparing `found_posts`.
**First seen:** THM, 2026-08-30 — a relationship field configured with a bare slug as "which taxonomy to offer". Newly created posts were invisible to search from the day it shipped (0 results with the restriction, 2 without).

### An ACF field property computed at registration time silently breaks the options group it reads — compute it in `acf/prepare_field`
**Symptom / When:** You add a `message` field (or any field with computed `choices`, `default_value` or `instructions`) whose text comes from a helper that calls `get_field( …, 'option' )`. The helper reports the wrong state, and the **whole options group it queried stops resolving on the front end**. Values that read correctly from `wp eval` before the field was added now return `0`/`''`. There's no PHP error or warning, HTTP 200 throughout, and `php -l` passes. The damage shows on pages nowhere near the edit.
**Why:** Computing the property inside the definition runs the helper during `acf/include_fields`, which re-enters ACF while it is still registering field groups. `get_field()` returns nothing at that point, and the options group answers empty.
**Fix:** Register the property empty and fill it at render time. `acf/prepare_field` fires when the field is displayed, long after registration, so the helper sees a fully built ACF:
```php
// In the field definition:
'message' => '',   // MUST stay empty — computed below

add_filter( 'acf/prepare_field/key=field_prefix_status_message', function ( $field ) {
    $field['message'] = prefix_state_sentence();   // get_field() is safe here
    return $field;
} );
```
This applies to any property that reads ACF data (`choices`, `default_value`, `instructions`): compute it in a `prepare_field`/`load_field` filter, never in the definition. **Verify** with `acf_prepare_field( acf_get_field( 'field_prefix_status_message' ) )`, and **check the front end too**, because that is where the damage shows.
**First seen:** WCDP, 2026-09-13 — a live-state message under an options-page selector zeroed the "current" value the whole site keyed on. One archive dropped every item to its empty state, and another page lost its dates.

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
**The Bricks side: a tag pointing at a removed field renders LITERALLY.** An existing-but-empty field resolves `{acf_<name>}` to `''`. A removed field has no field object, so Bricks' ACF provider never resolves the tag, and the raw string passes through, as `href="{acf_url_cancel}"` on a link, which is worse than no link. When retiring a field, grep `bricks_global_classes` and every `_bricks_page_*_2` for `{acf_<name>}` and remove those settings in the same pass.
**First seen:** TAB, 2026-04-26 — refactoring a page-level FAQ repeater to a FAQ CPT + relationship; the repeater was removed from the field group first, so the 6 rows had to be read raw. · **Extended:** THM, 2026-08-29 — a footer link rendered a literal tag after its options field was retired, caught on the first post-removal render check.

### A field group rebuilt with the same names nested in `group` fields — `get_field()` returns the OLD top-level value
**Symptom / When:** The inverse of the entry above, and worse: not `null`, but a **stale value returned confidently**. The admin shows the correct content, while `get_field('company_name','option')` and any `{acf_company_name}` dynamic tag return something that appears nowhere in the ACF UI — often a scaffold placeholder from the original build, months after it was replaced.
**Why:** A scaffold registers top-level fields and stores `options_company_name` + `_options_company_name`. The group is later rebuilt with the same field **names** nested inside ACF `group` fields, which store under a prefixed key (`options_business_info_company_name`). The old top-level rows are never deleted. They are invisible in the admin because they are no longer registered — but ACF's raw-meta fallback in `get_field()` happily returns them for the unprefixed name. The old and new values coexist, and which one you get depends entirely on which name you ask for.
**Fix:** Two parts, and both are needed. (1) Repoint every reference to the group-prefixed form — `{acf_business_info_company_name}`, `get_field('business_info_company_name','option')`. (2) Delete the orphaned `options_*` / `_options_*` pairs so the misleading fallback dies.
**Audit programmatically, not by eye** — build the registered set from the field group and diff the option rows against it:
```php
$live = [];
foreach ( acf_get_fields( '<GROUP_KEY>' ) as $g ) {
    $live[ "options_{$g['name']}" ] = 1;
    foreach ( ( $g['sub_fields'] ?? [] ) as $sf ) $live[ "options_{$g['name']}_{$sf['name']}" ] = 1;
}
// then list every options_* row not in $live
```
**One sweep is not enough.** A first cleanup on one project deleted 16 rows and was recorded as complete; a later audit on the same site found 8 more — including a typo-twin of a live field (`burger_ad_info` beside `burger_add_info`) and an abandoned repeater. Re-run the diff after **any** group rebuild, and treat "we cleaned this up once" as unproven.
**First seen:** MBC, 2026-08-07 — a header logo text fallback resolved to the build scaffold's placeholder company name months after the real Site Options group replaced it. Follow-on audit 2026-08-25 found the second batch.

### ACF `group` fields are structural — reorganising into tabs must WRAP them, never replace them
**Symptom / When:** "Reorganise these fields into tabs" arrives as a cosmetic request. If the fields currently sit in ACF `group` fields, the obvious implementation is a data migration that destroys data **silently** — no error, no warning; fields render empty and the old rows sit unreferenced.
**Why:** A `group` field contributes its name to the stored key (`options_business_info_company_name`) and to the Bricks dynamic tag (`{acf_business_info_company_name}`). A `tab` field stores **nothing at all** — it is a rendering marker that sections whatever follows it until the next tab. Swap a group for a tab and every key on the page is renamed at once.
**Fix:** Keep the groups. Insert `tab` fields as siblings *before* them; one tab can span several groups.
```php
'fields' => array(
    array( 'key' => 'field_tab_biz', 'label' => 'Biz Info', 'name' => '', 'type' => 'tab',
           'placement' => 'top', 'endpoint' => 0 ),
    array( 'key' => 'field_existing_group', 'name' => 'business_info', 'type' => 'group', /* untouched */ ),
)
```
**Verify by reading values back through `get_field()`, not by looking at the admin screen** — the screen looks correct either way. Related: `post_id => 'options'` on `acf_add_options_page()` is what produces the `options_*` prefix; change it and every value on the page orphans.
**First seen:** MBC, 2026-08-25 — a Site Options page reorganised into four tabs across five group fields, implemented as a wrap; all 42 option rows verified intact afterward.

### Writing ACF **group** subfield options directly needs FOUR rows, not two — miss the group-level pair and `get_field()` returns `null`
**Symptom / When:** You add a new group to an ACF options field group in PHP, then populate it by writing option rows directly (often deliberate — see the next entry on why round-tripping a group through `get_field()` → `update_field()` corrupts formatted subfields). The raw option is present and correct in `wp_options`. `acf_get_field()` resolves the group with the right `sub_fields` and parent. The Bricks dynamic tag registers and appears in `bricks/dynamic_tags_list`. **And `get_field( 'my_group', 'option' )` still returns `null`.** Anything bound to it renders empty, and any element with an `empty_not` condition on it stays permanently hidden — which looks exactly like a correctly-behaving conditional, so the failure is invisible until someone enters a value and nothing appears.
**Why:** ACF stores a group as a *set*. `update_field()` writes four rows for a one-subfield group:
```
options_<group>                      (empty marker row — the group itself)
_options_<group>                     field_<group_key>
options_<group>_<sub>                the value
_options_<group>_<sub>               field_<sub_key>
```
The two **group-level** rows are what `acf_get_value()` looks for first. Without them the group resolves to `null` regardless of how many subfield rows exist. Writing only the subfield pair — which is enough for a *flat* field — silently fails for a group.
**Fix:** Let ACF write the structure once, then keep using direct writes for later edits:
```php
update_field( 'field_my_group', array( 'my_url' => '' ), 'option' );  // creates all 4 rows
```
Safe for url/text/date subfields. For a group containing a `textarea` with `'new_lines' => 'br'`, create the structure with that subfield empty, then write the textarea's value directly (next entry).
**Verify:** `get_field( '<group>', 'option' )` must return an **array**, not `null` — a populated raw option row proves nothing.
**Watch for:** an existing group that already works is not evidence the technique is sound. One group on the same install accepted a direct subfield write with no trouble *only because its group rows had been created by the original build*; the two groups added later both read `null` until repaired.
**First seen:** WCDP, 2026-08-14 — meeting-schedule links on an options group, caught only because the `empty_not` condition was tested by populating a value rather than trusting the absent link.

### Round-tripping an ACF GROUP through `get_field()` → `update_field()` double-applies subfield formatting
**Symptom / When:** You add one subfield to an ACF group on an options page by reading the group, setting a key, and writing it back. The new value saves correctly — and an *unrelated* sibling subfield silently gains duplicated markup (`Open Saturdays<br /><br />10 AM to Noon`).
**Why:** `get_field()` returns each subfield **formatted**, and a `textarea` with `'new_lines' => 'br'` has already had its newlines converted. `update_field()` then stores that formatted string as the raw value, and the next read formats it *again*. The corruption is invisible until something renders the field, and it compounds once per round trip.
**Fix:** Never round-trip a group you only need to add one key to. Write the single subfield's option row directly (the group-level rows must already exist — previous entry):
```php
update_option( 'options_my_group_my_subfield', 'Open Saturdays, 10 AM to Noon' );
```
Or read unformatted with `get_field( $name, 'option', false )` before merging. If you have already done it, the raw value is repairable — write the plain-text original back to `options_<group>_<sub>` and re-read to confirm.
**First seen:** WCDP, 2026-08-10 — adding a short-hours subfield for the header bar corrupted the sibling hours textarea, which the global footer renders on every page.

### Migrating a UI/JSON field group to PHP — `local` lies, the post types can't be trashed, and children orphan
**Symptom / When:** Moving an ACF group out of the admin UI or Local JSON into `acf_add_local_field_group()`, to satisfy the PHP-registration convention.
**Why:** Three mechanisms collide. (1) While a DB definition and a PHP registration both exist, the reported source is **unreliable** — in one migration, with JSON retired but the DB post still present, one group reported `local => 'php'` and another `local => 'db'`; same plugin, same hook, both confirmed present in `acf_get_local_store('groups')`. (2) ACF's post types (`acf-field-group`, `acf-ui-options-page`) opt out of trash support, so removal is permanent. (3) Field definitions are separate `acf-field` posts parented to the group, and deleting the group does **not** cascade to them.
**Fix:** Register in PHP → export the group post *plus* its `acf-field` children → delete descendants deepest-first, then the group → **only then** confirm `local => 'php'`. Never infer the winning definition from the reported value while both exist.
```bash
# collect descendants before deleting
wp eval '$q=[<GROUP_POST_ID>]; $t=[]; while($q){ $p=array_shift($q);
  foreach(get_posts(["post_type"=>"acf-field","post_parent"=>$p,"numberposts"=>-1,"post_status"=>"any","fields"=>"ids"]) as $i){$t[]=$i;$q[]=$i;} }
  echo implode(",", $t)."\n";'
```
Keep `acf/settings/save_json` pointed at the (now empty) `acf-json/` afterwards: a group someone later creates in the UI then lands as a file on disk instead of invisible DB state, so **the directory becoming non-empty is itself the alarm**. Note the end state also makes the ACF admin effectively read-only for structure — UI edits neither apply nor warn.
**First seen:** MBC, 2026-08-25 — two groups migrated and one deleted. One deletion left 34 orphaned `acf-field` rows, and the `local` inconsistency would have shipped the first group on a false positive had its DB post happened to win too.

### ACF `url` field type rejects relative paths and query strings
**Symptom / When:** A field declared `'type' => 'url'` throws "Value must be a valid URL" and blocks save when you enter an internal link like `/request-a-quote/` or `/request-a-quote/?service=decks`. The same happens with `mailto:` / `tel:` links, and an editor may only notice that "my changes never saved".
**Why:** ACF's `url` validator (`class-acf-field-url.php`, `validate_value()`) is not an RFC check. It's a substring test: valid if the value contains `://`, or starts with `//` (protocol-relative), and invalid otherwise. Relative paths, site-root paths, bare query strings, `mailto:` and `tel:` all lack both, so they always fail.
**Fix:** Use `'type' => 'text'` for internal links and for `mailto:` / `tel:` values. The Bricks tag `{acf_<field>}` resolves a text field identically in a link `useDynamicData` binding, so no template change is needed. (Don't reach for the ACF `link` type as a workaround — it returns an **array**, which breaks a string `{acf_<field>}` tag.)
**First seen:** TAB, 2026-05-30 — a CTA field holding `/request-a-quote/?service=slug` blocked save under `type: url`. · **Extended:** THM, 2026-08-29 — a support field holding a `mailto:` link refused to save. Diagnosed from ACF's validator source, which also corrected this entry's earlier "full RFC URL" explanation.

### An ACF repeater round-trip bakes `new_lines` formatting into storage — `get_field()` → `update_field()` is lossy
**Symptom / When:** You append one row to a repeater the obvious way: `get_field()`, push a row, `update_field()`. The new row is correct and `update_field()` returns `true`, but every **existing** row with a newline in a `textarea` sub-field (`'new_lines' => 'br'`) now renders `<br /><br />`, and the admin textarea shows a literal `<br />`. Only rows that happened to contain a newline are damaged, so spot-checking the row you added proves nothing.
**Why:** `get_field()` returns **formatted** values: the stored `\n` comes back as `<br />\n`. Writing that array back puts the `<br />` **into the database**, and ACF applies `new_lines` again on the next render. The same holds for anything else ACF filters on the way out: `wysiwyg` (wpautop), `image`/`file` (array vs ID), `relationship`/`post_object` (objects vs IDs), and `select` with `return_format: label`. **A helper that formats on read is not safe to write back.**
**Fix:** Append without a round-trip by writing the raw option rows:
```php
$n = (int) get_option( 'options_my_repeater' );                              // current row count
update_option( "options_my_repeater_{$n}_name", 'New row', false );
update_option( "_options_my_repeater_{$n}_name", 'field_prefix_rep_name', false );   // key ref, required
// …one pair per sub-field…
update_option( 'options_my_repeater', $n + 1, false );
```
Alternatively, round-trip but strip the formatting ACF added (or read raw with `get_field( $name, 'option', false )`) before writing, then diff **every** row against its pre-change value.
**Verify with SQL, not `get_field()`.** `get_field()` is the instrument that introduced the damage, and it will show you clean output:
```sql
SELECT option_name, option_value FROM wp_options
WHERE option_name LIKE 'options_<repeater>_%_<subfield>' ORDER BY option_name;
```
**First seen:** WCDP, 2026-09-21 — appending one row to a meetings repeater baked `<br />` into two existing schedules. It was caught on readback and reverted byte for byte. The same SQL check then found that an earlier round-trip had already damaged seven address rows in a second repeater, which had rendered doubled line breaks for weeks. The one row added through the admin was clean, and that dated the damage.

### Bricks ACF query loop: `objectType: acf_<field>`; repeater subfields are `{acf_<repeater>_<subfield>}`
**Symptom / When:** A Query Loop set to an ACF **repeater** renders the right number of rows but every subfield tag (`{acf_feature_title}`) prints **literally**. Relationship loops resolve fine; repeater subfields silently don't.
**Why:** Bricks' ACF provider namespaces repeater subfield tags by the parent repeater (`provider-acf.php` ~L115: `'acf_' . $parent_field['name'] . '_' . $field['name']`). The bare subfield tag isn't in the loop's tag map, so it falls through to literal text.
**Fix:**
- Loop element: `hasLoop = true`, `query.objectType = "acf_<field_name>"`.
- **Repeater** (loop item = a row): `{acf_<repeater>_<subfield>}` → `{acf_service_features_feature_title}`.
- **Relationship** (loop item = the related *post*): use post-context tags — `{post_title}`, `{post_url}`, and the related post's own `{acf_<field>}`.
**Also:** the repeater can live on an ACF **options page**, not just the current post. The native loop and the namespaced subfield tags work unchanged there (WCDP, 2026-09, five options-page repeaters, builder-save verified). A custom dynamic tag inside the loop gets the row as an associative array from `\Bricks\Query::get_loop_object()`, not a `WP_Post`.
**No limits — `posts_per_page` is ignored.** A repeater loop with `posts_per_page: 4` renders every row; the key persists in the DB and reads back correctly. `Provider_Acf::set_loop_query()` returns the field's raw value array and never consults `query_vars` — there is no limit, offset or ordering support for `acf_*` loops. Cap it in PHP, scoped by element id so the same repeater can render in full elsewhere:
```php
add_filter( 'bricks/query/run', function ( $results, $query ) {
    if ( 'acf_my_repeater' !== $query->object_type ) return $results;
    if ( 'abc123' !== $query->element_id ) return $results;   // this loop only
    return array_slice( $results, 0, 4 );
}, 20, 2 );
```
`\Bricks\Query` exposes `element_id`, `object_type`, `settings` and `loop_index` as public properties. Delete the dead `posts_per_page` from the element tree afterwards — left in place it misleads the next reader.
**Relationship / post-object loops are the same, so they cannot paginate.** `set_loop_query()` returns `get_field()`'s whole array for those too, with no `paged` slicing, no `found_posts` and no `max_num_pages`, so a Pagination element shows nothing. For a paginated grid driven by a relationship field, resolve the IDs yourself and feed them to an `objectType: 'post'` loop as `post__in`, in a `bricks/posts/query_vars` filter scoped to that element (keep the element's own `posts_per_page`). That's a real `WP_Query`, and native pagination works. On a singular template, also see "`redirect_canonical` 301s requests you meant to serve…".
**Native Woo tags render empty in a relationship loop.** `{woo_product_price}` and other tags that read the global `$product` come out blank in an `acf_<relationship>` loop of products, because Bricks sets the Woo product global only for product queries. Post-context tags keep working, which makes it look tag-specific. Register a project tag that resolves the product from `\Bricks\Query::get_loop_object()` (not `get_the_ID()`, which is the page here) and returns `wc_get_product( $id )->get_price_html()`, which handles ranges and sale markup. Build add-to-cart contracts the same way.
**First seen:** TAB, 2026-05-29 — repeater loops rendered correct row counts with literal subfield tags; grepping `provider-acf.php` gave the namespaced format. · **Extended:** WCDP, 2026-08-10 — capping a repeater list in the global footer while its own page shows the full set. · **Extended:** THM, 2026-08-30 — a 9-per-page grid on a CPT single, rebuilt from an ACF relationship loop to a `post__in` query and confirmed across two pages. · **Extended:** MMHN, 2026-07-21 — a guide single's related-products row: titles, images and links per item, prices blank.

### ACF `gallery` / `image` fields are NOT loopable in Bricks — a repeater's image SUBFIELD renders per row only with return format `id`
**Symptom / When:** (1) A loop set to `objectType: acf_<gallery_field>` renders the loop shell (`data-start=0 data-end=0`) but **zero items**, though the field has images. (2) A repeater loop renders the right row count and text subfields resolve, but an **image** subfield bound to a Bricks image element renders nothing.
**Why:** Bricks builds its loop-tag registry from a field-type→context map (`provider-acf.php` ~L1088). `gallery` and `image` map to `[CONTEXT_TEXT, CONTEXT_IMAGE]` — **not `CONTEXT_LOOP`**. Only `relationship`, `post_object`, `repeater`, `flexible_content` and `group` get `CONTEXT_LOOP`. A gallery field is never registered as a loop tag, so the query matches nothing.
**Face (2) is a different mechanism: the subfield's return format.** The repeater *is* a loop, and `{acf_<repeater>_<image subfield>}` does resolve per row, but only when the image subfield returns an ID. Tested on Bricks 2.4.2 against one options-page repeater (10 rows, 9 with images), switching only the subfield's `return_format`:

| `return_format` | Images rendered | Per-row `empty_not` gate on the same tag |
|---|---|---|
| `id` | 9 of 9 | correct (9 pass, 1 falls back) |
| `array` | **0**, no `<img>` emitted | **still passes**, so the tiles render blank |
| `url` | **0** | resolves empty, so all 10 fall back |

`array` is the worst case: the gate says the image is there, and the image element prints nothing.
**The golden-rule trap:** the provider's loop *handlers* (`set_loop_query`/`set_loop_object`) both have `case 'gallery'`, which reads like support. **The registration is the source of truth, not the render handlers.** Verify the registered loop tags (dump the provider's `loop_tags` via reflection); don't trust a switch statement.
**Fix for a repeater image subfield:** set the subfield's `return_format` to `id` and bind `'image' => [ 'useDynamicData' => '{acf_<repeater>_<subfield>}', 'size' => '…' ]`. No custom code. Gate a per-row fallback with `_conditions` on the same tag.
**Fix for a gallery field:** expose the images as a **custom query type** returning plain attachment IDs, plus a loop-aware image tag returning `[ $id ]` in image context (see the image-context array entry above):
```php
// query run: return get_field('<gallery>', get_the_ID(), false)   // raw attachment IDs
// tag:       return $context === 'image' ? [ $att_id ] : (string) $att_id;
//            $att_id from \Bricks\Query::get_loop_object()
```
**First seen:** TAB, 2026-06-08 — a gallery loop returned 0 items. **Same mechanism again:** TAB, 2026-06-10 — a repeater's logo-image subfield looped (chips rendered) but every `<img>` was empty. · **Corrected:** pkjsupport, 2026-10-07 — a repeater image subfield rendered natively on Bricks 2.4.2, which conflicted with "including repeater image subfields." Resolved at harvest by test: the original title held only for the `array` and `url` return formats, and the "can't be pulled per-row" mechanism was wrong. TAB's return format wasn't recorded, so whether its failure was the format or its older Bricks is unknown; the table above is what holds on 2.4.2. Test reproducible in one request: reset `acf_get_store( 'fields' )`, filter `acf/load_field` to change the subfield's `return_format`, and render the tree with `\Bricks\Frontend::render_data()`. ⚠️ A `acf/load_field/name=<subfield>` filter added after ACF has loaded the group never fires; reset the store first, or the test proves nothing.

### An ACF options page sizes its labels at (0,3,2) — `.acf-field .acf-label label` only restyles the nested ones
**Symptom / When:** Custom admin CSS to resize ACF field labels applies to **sub-fields inside groups and repeaters** but leaves the **top-level** labels at their original size. The rule is valid, enqueued and visible in DevTools, but it's outranked, and only on some labels. That makes it look like a caching or specificity fluke rather than a miss.
**Why:** On options pages ACF sizes labels with `.form-table > tbody > .acf-field > .acf-label label { font-size: 14px; color: #23282d }` in `acf-input.min.css`, which is **(0,3,2)**. A hand-written `.acf-field .acf-label label` is (0,2,1) and loses. Sub-fields aren't direct children of `.form-table > tbody`, so nothing competes for them and the weak rule appears to work.
**Fix:** Ship both selectors: the short one for nested sub-fields, and a mirror of ACF's own for the top level (equal specificity, so the later source wins):
```css
.acf-field .acf-label label,
.form-table > tbody > .acf-field > .acf-label label { font-size: 16px; }
```
Enqueue with `acf-input` as a dependency so the source order is guaranteed. Gate it to the one screen (`toplevel_page_<menu-slug>`) so it doesn't follow ACF onto CPT edit screens. Version it with `filemtime()`, because a plugin-version string leaves editors on stale admin CSS. When you check the stylesheet URL with `curl`, add a cache-buster, or an edge cache hands back the pre-edit file and it reads as "my rule didn't save".
**First seen:** WCDP, 2026-08-18 (ACF Pro 6.8.x) — labels raised to 16px for volunteer maintainers. With a single selector, every top-level group label would have stayed at 14px.

### ACF left-placed tabs: `.acf-tab-wrap.-left` and `.acf-fields.-sidebar` are JS-applied — curl never shows them
**Symptom / When:** After switching a tab field to `placement: 'left'`, the raw HTML still shows `.acf-fields -top` and no `.acf-tab-wrap`. A headless screenshot taken right after `networkidle` shows every field stacked with no rail.
**Why:** ACF builds the tab wrap and adds `-sidebar` from its input JS after load. `networkidle` can fire before ACF's init finishes on a large field group (27 top-level fields here).
**Fix:** Verify in a real browser context and wait for `.acf-tab-wrap` with `state: 'attached'`. Its box is 0-height, so a visibility wait times out. Admin CSS that skins the rail must target the JS-applied classes and should be scoped to the screen. (Headless admin access without a password: the "mint auth cookies with `wp_generate_auth_cookie`" entry under Diagnostic patterns.)
**First seen:** WCDP, 2026-09-11 — a sidebar-rail pass on an options page; three failed captures before the wait was right.

### A submenu under an ACF options page must hook `admin_menu` above 99 — earlier, its link is a bare `/wp-admin/<slug>` that 404s
**Symptom / When:** A custom admin screen added with `add_submenu_page( '<acf-options-slug>', … )` shows in the menu, but clicking it opens `/wp-admin/<slug>` → 404. Loading `admin.php?page=<slug>` by hand works.
**Why:** ACF PRO registers options pages on `admin_menu` at priority **99** (`pro/admin/admin-options-page.php`). A submenu added earlier (the default 10, or 20) has no registered parent hook yet, so WP builds the link against a parent it can't resolve and drops `admin.php?page=`.
**Fix:** Hook the submenu after ACF, and say why in a comment:
```php
// 100, not 20: ACF registers the parent options page at admin_menu 99.
add_action( 'admin_menu', 'prefix_submenu', 100 );
```
**Verify:** over real authenticated HTTP, and `grep` the dashboard HTML for `href='admin.php?page=<slug>'`. A CLI `do_action('admin_menu')` can't load ACF's admin classes and misreports this; see "Firing admin hooks under `wp eval` gives false negatives — verify admin screens over real HTTP".
**First seen:** WCDP, 2026-09-30 — a 404-log screen under the site's settings page, reported by Michael on launch day. The bug was also in dev, but nobody had clicked the link there.

### Renaming an ACF options-page group subfield orphans its value unless both option rows move
**Symptom / When:** A subfield inside an options-page group is renamed (`address` → `mailing_address`), and the field comes up empty although the old value is still in the database.
**Why:** ACF stores an options group subfield as two `wp_options` rows: `options_<group>_<sub>` (the value) and `_options_<group>_<sub>` (the field-key reference). The name is part of both option names, so a rename points the field at rows that don't exist.
**Fix:** Keep the field **key** and move both rows:
```php
foreach ( [ '', '_' ] as $p ) {
    update_option( "{$p}options_company_mailing_address", get_option( "{$p}options_company_address" ), false );
    delete_option( "{$p}options_company_address" );
}
```
Copy the raw option, not `get_field()` output, which applies formatting (`new_lines` → `<br />`) that would then be applied twice.
**First seen:** pkjsupport, 2026-10-07 — an address field renamed to say what it's for.

### ACF `update_field()` by field KEY on an unregistered group silently writes the KEY as the meta key — verify the registration, not the file
**Symptom / When:** An importer calls `update_field( 'field_abc123', $v, $post_id )` on thousands of posts, every call "succeeds", and the edit screen shows no fields. The meta table holds rows whose `meta_key` is the field key itself. Typical cause: the group's registration code never landed (an append behind a failed `cd dir && cat >> file`, which short-circuits while the next unchained command still runs), and `php -l` passed because lint only checks what *is* in the file.
**Why:** ACF can't resolve a key that was never registered, so it falls back to using the selector string as the meta name. Nothing errors.
**Fix:** After any registration write, verify the registration itself: `wp eval 'var_export( (bool) acf_get_field_group( "group_x" ) );'`, or `function_exists()` / `has_action()` for code. Write importers with direct `update_post_meta( $id, $name, $v )` plus `update_post_meta( $id, "_$name", $field_key )`: deterministic, and independent of ACF's request-time state. To repair, rename the key-named meta rows in place with SQL and add the `_<name>` reference rows.
**First seen:** MMHN, 2026-09-06 — a store CPT's field group never registered; all 6,000+ imported posts carried key-named meta and the admin showed no fields. First misread as a WP-CLI/ACF timing quirk, which doesn't exist.

### Bricks `{acf_<name>}` tags are keyed on the field NAME across every group — a new field named `phone` silently hijacks an options-page `{acf_phone}`
**Symptom / When:** A dynamic tag that has worked for weeks (an `{acf_phone}` fed by a Site Options field) renders empty everywhere, with no error, right after an unrelated ACF group ships. Nothing in the template changed.
**Why:** Bricks' ACF provider walks `acf_get_field_groups()` and registers one tag per field **name** (`'acf_' . $field['name']`). A later group with the same name replaces the earlier entry, and the tag now resolves against that group's location: a CPT field read on a page where no such post exists returns empty. `get_dynamic_tags_list()` shows which definition won (label and group).
**Fix:** Treat ACF field names as a site-wide namespace on any Bricks build: prefix them per group (`store_phone`, not `phone`). To repair after the fact, rename the field in its registration (the field KEY stays), migrate the meta in place (`UPDATE {$wpdb->postmeta} SET meta_key='store_phone' WHERE meta_key='phone' AND post_id IN (<that post type>)`, plus the `_phone` → `_store_phone` reference rows), update every `get_post_meta()` read, and flush caches.
**First seen:** MMHN, 2026-09-06 — a store CPT group with bare `phone` / `city` / `state` / `zip` fields blanked the Site Options phone in the mobile header, an hour after the group registered.

## WordPress core — CPTs, rewrites, canonical, mail

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

### `redirect_canonical` 301s requests you meant to serve — a term archive to a same-slug CPT single, and a custom rewrite endpoint to a trailing slash
**Symptom / When:** `/projects/category/decks-porches/` 301-redirects to `/services/decks-porches/`. The term query resolves fine server-side (`is_tax=1`, `is_404=false`, found>0), the redirects table is empty, the rewrite rule matches — yet the live request redirects.
**Why:** When taxonomy term slugs intentionally mirror post slugs elsewhere (a common, deliberate IA choice), WP core's `redirect_canonical()` sees the shared slug and "helpfully" redirects the term archive to the matching single.
**Fix:** Bail out of `redirect_canonical` for that taxonomy, in the core plugin:
```php
add_filter( 'redirect_canonical', fn( $r, $req ) => is_tax( 'project_category' ) ? false : $r, 10, 2 );
```
**Related:** if a nested-slug taxonomy (`projects/category`) 301s to a same-slug single, also check rewrite ordering — registering the **taxonomy before the post type** fixes a greedy CPT attachment rule sorting ahead of the taxonomy rule.
**Second trigger — a custom rewrite endpoint gets trailing-slash normalised.** Register a rewrite for a fixed path (`^site\.webmanifest$` → `index.php?my_var=1`) and the endpoint returns the right body when you follow redirects — but every request first eats a `301` to `/site.webmanifest/`, and user agents that don't follow it fail to load the resource (for a manifest that reads as "no manifest", with nothing in the log). WordPress treats any path it does not recognise as a candidate for trailing-slash normalisation, and `redirect_canonical` runs on `template_redirect` before your handler; a dot in the path does not exempt it. Same filter, gated on your own query var so nothing else is affected:
```php
add_filter( 'redirect_canonical', fn( $r ) => get_query_var( 'my_var' ) ? false : $r );
```
**Verify:** `curl -sk -D - -o /dev/null <url>` and confirm the **first** response is `200`, not `301` — following redirects (`-L`, a browser) hides the bug.
**Third trigger — a secondary loop's `/page/N/` on a singular page.** `redirect_canonical()` accepts a page suffix on an `is_singular()` URL only as `<!--nextpage-->` content splitting, so a paginated grid on a CPT single 301s back to page 1 even though the loop renders page 2 correctly server-side. The query var isn't the one you'd guess. For a non-hierarchical CPT, `<cpt>/<slug>/page/N/` maps to the archive-style **`paged`**, while a bare `<cpt>/<slug>/N/` maps to `page`, so a bypass that checks `page` never fires. Dump `$GLOBALS['wp_rewrite']->wp_rewrite_rules()` for the CPT before writing it, then:
```php
add_filter( 'redirect_canonical', fn( $url ) => ( is_singular( '<cpt>' ) && get_query_var( 'paged' ) > 1 ) ? false : $url );
```
Bricks' loop reads `paged` as a fallback after `page` (`Query::get_paged_query_var()`), so nothing else changes.
**First seen:** TAB, 2026-05-31 — category archives 301'd to service singles until the filter was added. · **Extended:** WCDP, 2026-08-08 — a PHP-rendered `/site.webmanifest` endpoint 301'd to a trailing slash. WP 7.0.3. · **Extended:** THM, 2026-08-30 — a CPT single's paginated grid. A first bypass on `page` never matched and looked exactly like an unregistered filter.

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
  Cleaner variant — hook core's own size list instead of hand-listing sizes, so it tracks whatever core registers: `require_once ABSPATH . 'wp-admin/includes/class-wp-site-icon.php'; add_filter( 'intermediate_image_sizes_advanced', [ new WP_Site_Icon(), 'additional_sizes' ] );` then regenerate as above. **Verify:** `get_site_icon_url( 32 )` must end in `-32x32.png` — an option that is set proves nothing, since `wp option update site_icon` succeeds with no crops at all.
- SVG favicon → emit it yourself in the core plugin (bundled asset, brand constant): `add_action('wp_head', fn() => printf('<link rel="icon" href="%s" type="image/svg+xml">', esc_url($url)), 2)`. Modern browsers prefer the SVG; Safari/iOS/Android use the native raster set. Add `<meta name="theme-color">` alongside. An SVG with `<style>@media (prefers-color-scheme: dark){...}</style>` gives a free dark-mode favicon.
**Root-path icons need real files.** Browsers request `/favicon.ico`, and iOS requests `/apple-touch-icon.png` and `/apple-touch-icon-precomposed.png`, straight from the document root regardless of any `<link>` tag; no markup suppresses that. Put real files at the web root for those three: the web server answers them without booting PHP (they keep working if WordPress is down or a plugin is deactivated, and the 404s stay out of the log). Everything else (SVG icon, manifest, PNG icons) is discovered via `<link>` and can live with the plugin.
**Alternative — keep the Site Icon convention, own the tags.** `site_icon_meta_tags` filters the array `wp_site_icon()` prints on `wp_head`, `admin_head` and `login_head`, so returning your own set (ico + svg + apple-touch + manifest + theme-color) replaces core's four tags everywhere with no duplicates. ⚠️ `wp_site_icon()` returns early when `! has_site_icon()`, so **the filter never runs until `site_icon` is set**: point it at a 512px attachment (admin, login, the Customizer and REST `site_icon_url` read it) even though the front end uses your tags. A maskable manifest icon needs a full-bleed background with the mark inside the 80% safe circle; headless Chrome rasterizes an SVG to PNG when nothing else is installed (`--headless=new --screenshot --window-size=512,512`, and see the headless entry for its exit trap).
**First seen:** Highland, 2026-06-14 — programmatic Site Icon generated only `thumbnail`; added the size filter + a plugin SVG-favicon module (`inc/favicon.php`). · **Extended:** WCDP, 2026-08-08 — `wp media import` + `wp option update site_icon` left `get_site_icon_url(32)` returning a 250×250 file (regenerated via `WP_Site_Icon::additional_sizes()`), and the access log filled with 404s for the three root icon paths. WP 7.0.3. · **Extended:** pkjsupport, 2026-10-07 — the `site_icon_meta_tags` route, WP 7.1.2.

### `remove_action()` on `admin_head` needs core's REAL priority AND must run after the admin bootstrap
**Symptom / When:** A plugin removes a core callback (e.g. `wp_site_icon`) from `wp_head`, `login_head` and `admin_head` with the same priority. The first two work, and the `admin_head` removal silently returns `false`.
**Why:** Two traps that must both be fixed. (1) **Priority:** `remove_action()` matches hook, callback *and* priority. `wp_site_icon` is on `wp_head` / `login_head` at 99 (`wp-includes/default-filters.php`) but on `admin_head` at the default 10 (`wp-admin/includes/admin-filters.php`). (2) **Timing:** `admin-filters.php` is required only once an admin request bootstraps, after plugins have run their top-level code, so a top-level removal finds nothing yet to remove. The front-end hooks are registered before plugins load, which is why the same pattern works there and masks the problem.
**Fix:**
```php
add_action( 'admin_init', function () { remove_action( 'admin_head', 'wp_site_icon' ); } ); // real priority, after admin bootstrap
```
Verify with `has_action()` under a real admin load order (see "Firing admin hooks under `wp eval`…" for loading it under CLI).
**First seen:** THM, 2026-08-29 — a favicon-unification module. The `admin_head` removal never fired, which stayed invisible only because no Site Icon was set. It would have emitted a competing wp-admin favicon the day someone set one.


### A `wp_mail_from` filter beats an explicit `From:` header — a form plugin's per-message From field is cosmetic
**Symptom / When:** A site runs an SMTP layer (custom module or mailer plugin) alongside a form plugin that lets you set a **From** address per notification. You set the form action's From to `sales@…`, ship it, and every notification arrives as `noreply@…`. Nothing errors, the form UI keeps displaying your value, and the sent mail silently disagrees with the config. Usually only noticed when a *second* email (an autoresponder) needs a different sender.
**Why:** `wp_mail()` parses an explicit `From:` header into `$from_email` (`wp-includes/pluggable.php:329–345`) and **then** applies the filter unconditionally — `$from_email = apply_filters( 'wp_mail_from', $from_email );` at `:437`. There is no "only if unset" guard, so **a `wp_mail_from` filter always wins over the header**, whatever the caller set. Verified on WP 7.1; the ordering is longstanding. (This is the general rule; the WooCommerce entry below is the *exception* — Woo bypasses the filter entirely via its own `woocommerce_email_from_address` setting.)
**Fix:** Don't fight it — one aligned sender is usually the whole point of the SMTP layer (SPF/DKIM alignment). Set From once, centrally, and carry per-message routing intent in **`Reply-To`**, which is **not** filtered: it is parsed at `:374` and passed straight to `$phpmailer->addReplyTo()` at `:495`. So a staff notification sets Reply-To to the *enquirer*, and an autoresponder sets it to the *monitored mailbox* — get that backwards and a lead who hits Reply is writing to `noreply@`. If one message genuinely needs a different From, the filter callback must exempt it deliberately (inspect `$to` or a flag) rather than the caller setting a header and assuming it sticks. Check what actually goes out, since the form UI will lie:
```php
add_action( 'phpmailer_init', function( $m ) { error_log( 'FROM: ' . $m->From . ' REPLY: ' . print_r( $m->getReplyToAddresses(), true ) ); } );
```
**First seen:** TAB, 2026-08-31 — spec'ing a submitter autoresponder on WS Form. The SMTP module hooked `wp_mail_from` at priority 99, so the form action's `sales@` had been discarded on every notification since launch without anyone noticing, because the notification reads fine either way.
**See also:** 04 › Email delivery › "Mailgun SMTP from a core plugin — force From and envelope Sender, or DMARC fails".


### `default_category` still references a term with 0 posts — repoint before deleting Uncategorized

**Symptom / When:** You delete the `uncategorized` term because it has 0 posts and nothing appears to reference it. It deletes cleanly. Later, posts saved with no category behave oddly or the term reappears.
**Why:** `wp_options.default_category` points at term 1. WP assigns it to any post saved without a category. Term count and object relationships both read **0**, so every obvious check says "safe to delete" — the option is the only reference.
**Fix:** Repoint first, then delete:
```bash
wp option update default_category <real_term_id>
wp term delete category 1
```
**"0 posts" is not "nothing references it."** Check `default_category` explicitly.
**First seen:** TAB, 2026-07-15 — taxonomy formalization. The spec's "confirm nothing references it first" earned its keep.

### Updating a PARENT THEME on a live box throws a hard fatal at any request that lands inside the unpack window

**Symptom / When:** A single `PHP Fatal error: Uncaught Error: Failed opening required '.../themes/<theme>/includes/init.php'` (or any theme include) appears in the Apache error log, timestamped to the minute a theme update ran. The file exists when you go to look. The site is fine. It reads like file corruption or a botched update, and invites a restore that isn't needed.
**Why:** WordPress updates a theme by **deleting the old directory and unpacking the new one in place** — there is no atomic swap. `functions.php` is loaded on every request via `wp-settings.php`, so any request served during that window `require_once`s a path that genuinely does not exist yet, and dies. The window is a second or two, which is why it shows up once and never reproduces.
**Fix / rule:** **Correlate the fatal's timestamp against the theme directory mtime before treating it as an incident** — `stat -c '%y %n' wp-content/themes/<theme>/style.css`. Same minute = update-in-flight, benign, no action. Confirm by checking the named file now exists and the front end returns 200. It is only a real fault if the file is *still* missing. Prefer updating parent themes off-peak; on a single-webapp box with a live client there is no way to make the window zero without maintenance mode.
**First seen:** TAB, 2026-09-11. Bricks 2.3.8 → 2.3.13 at 11:49–11:51 UTC; a fatal at `11:50:15` on `themes/bricks/functions.php:204` for a missing `includes/init.php`, referer `wp-admin/admin.php?page=bricks-license`. The file was present at 5,047 bytes with mtime 11:50, Bricks reported 2.3.13, and all six checked URLs returned 200. It hit `admin-ajax`, not a visitor page.

### `class_exists( 'WooCommerce' )` at include time is ALWAYS false when your plugin's folder sorts before `woocommerce/` — the guarded file silently never loads
**Symptom / When:** A core plugin guards its Woo includes with `if ( class_exists( 'WooCommerce' ) ) { require … }` at the top level of its main file. No error, the site runs, but nothing in the guarded file exists: its shortcodes print as literal text and its filters never fire. A `do_shortcode( '[my_thing]' )` smoke test "passes", because WordPress echoes an unregistered shortcode back verbatim, which is truthy.
**Why:** WordPress loads active plugins alphabetically by folder name. A folder like `acme-core/` sorts before `woocommerce/`, so at include time Woo's class doesn't exist yet. The guard looks like a runtime check and is really a load-order check.
**Fix:** Defer it: `add_action( 'plugins_loaded', function () { if ( class_exists( 'WooCommerce' ) ) { require_once …; } } );`. Every plugin's main file has loaded by then, and hooks registered in the required files still bind in time. Verify with `shortcode_exists()` / `has_filter()`, never with `do_shortcode()` output.
**First seen:** MMHN, 2026-07-19 — a shop-archive build's dynamic tags were all undefined despite a clean require. The same guard had been skipping the plugin's Woo integration file for four days.

### `strip_shortcodes()` only strips REGISTERED shortcodes — an old builder's tags (`[vc_row]`, `[ld_*]`) survive migration and leak onto the front end
**Symptom / When:** Content migrated from a page-builder site (WPBakery, LiveMesh, Divi…) shows raw `[vc_row][vc_column][vc_column_text]…` in excerpts, cards and the body. `get_the_excerpt()`, `strip_shortcodes()` and `wp_strip_all_tags()` don't remove it.
**Why:** `strip_shortcodes()` and the excerpt generator build their regex from the **registered** tag list. The old builder isn't installed on the new site, so its tags pass through as literal text, and `wp_kses` ignores them (they're text, not HTML).
**Fix:** A one-time regex pass over the migrated content, keyed on the builder's tag prefixes, that strips wrapper tags and keeps inner content: `preg_replace( '/\[\/?(?:vc_|ld_)[a-z0-9_]*(?:\s[^\]]*)?\]/i', '', $content )`. Layout shortcodes unwrap cleanly. **Functional shortcodes carry content in attributes** (`vc_single_image image="OLD_ID"`, `vc_btn title=…`, `ld_fancy_heading title=…`) that reference dead old-site IDs, and this pass drops them. Snapshot first, log which posts lost images, buttons or headings, and send them to content review rather than calling the transform lossless.
**First seen:** MMHN, 2026-07-18 — a news import from an old WPBakery site: 11 of 19 posts carried `vc_*` / `ld_*` tags, and `[vc_row]` leaked into the archive cards.

### Legacy root-slug redirect silently skipped — the slug resolves to an ATTACHMENT page, not a 404
**Symptom / When:** A 404-gated legacy redirect (`if ( ! is_404() ) return;`) works for every old `/{slug}/` except one or two, which land on the home page with a 302 (`x-redirect-by: WordPress`). The content exists, and its new URL works.
**Why:** Imported images are often named after their post, so an attachment carries the same slug. With the old post gone from the root, WordPress resolves `/{slug}/` to the **attachment page** (a 200), so the 404 gate never fires. The SEO plugin's "redirect attachment pages" setting then 302s an unattached image to the home page. Two plugins each behaving correctly compose into a lost ranking URL.
**Fix:** Gate on `is_404() || is_attachment()` for root single-segment paths, and prefer the post-type lookup over the attachment. Find collisions up front:
```sql
SELECT a.post_name FROM wp_posts a
JOIN wp_posts r ON r.post_name = a.post_name AND r.post_type IN (<your types>) AND r.post_status = 'publish'
WHERE a.post_type = 'attachment';
```
Verify every legacy URL by request; a sample would miss it. Same attachment-slug family as "`get_page_by_path()` as an import existence-check silently matches ATTACHMENT slugs" (WP-CLI). See also "`redirect_canonical` 301s requests you meant to serve".
**First seen:** MMHN, 2026-09-29 — one high-traffic legacy recipe URL went to the home page; found only because the verifier walked every source URL.

### `set_quality()` is ignored when an image editor converts formats (WebP → JPEG)
**Symptom / When:** A script loads a WebP with `wp_get_image_editor()`, calls `set_quality( 68 )`, saves as `image/jpeg`, and the file is byte-identical to a q82 save. Quality steps have no effect.
**Why:** On a save whose output MIME differs from the source, WordPress re-resolves quality for the output format (the `wp_editor_set_quality` default, 82 for JPEG) and discards the value set on the editor.
**Fix:** Force it through the filter around the save, and clear the stat cache before re-reading the size:
```php
add_filter( 'wp_editor_set_quality', $f = fn() => 68, 999 );
$ed->save( $dest, 'image/jpeg' );
remove_filter( 'wp_editor_set_quality', $f, 999 );
clearstatcache( true, $dest );
```
**First seen:** MMHN, 2026-09-29 — generating JPEG share-image renditions from WebP sources; one photo stayed at 368 KB through every quality step-down.

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


### A Bricks accordion can carry FAQ **microdata** that survives the `faqSchema` toggle — "one JSON-LD block" does not prove single-source schema
**Symptom / When:** The Rich Results Test reports **duplicate `FAQPage`** on a page containing exactly **one** `<script type="application/ld+json">`. A grep for `application/ld+json` looks clean, so the duplicate is invisible to a JSON-LD-only audit.
**Why:** The FAQ exists in **two formats**. The visible accordion was hand-authored as a `FAQPage` in **microdata** — `itemscope` / `itemtype` / `itemprop` added as Bricks custom **`_attributes`** on the accordion and its loop children. That is independent of the element's `faqSchema` toggle, which governs only Bricks' own JSON-LD `<script>` output. Disabling `faqSchema` removes the JSON-LD path and leaves the microdata untouched. Once any other layer emits a JSON-LD `FAQPage`, Google merges JSON-LD and microdata into one model → two `FAQPage` entities.
**Detect — check both formats:**
```bash
curl -s <url> | grep -o 'itemtype="https://schema.org/[^"]*"' | sort | uniq -c
curl -s <url> | grep -o 'itemprop=' | wc -l    # 0 = clean
```
**Fix:** Strip `_attributes` whose `name` matches `^item(scope|type|prop)$` or whose `value` contains `schema.org`, and write via `$wpdb->update` — Bricks' Ajax filter silently blocks `update_post_meta` on `_bricks_page_content_2`. The change is visually invisible: the accordion still renders and still opens; only the machine-readable markup changes.
**The general lesson:** counting JSON-LD blocks is **not** sufficient to prove single-source schema. Microdata and RDFa are separate emission paths — any schema migration or cutover validation must grep rendered HTML for `itemtype` / `itemprop` as well.
**BricksExtras Pro Accordion's `faqSchema` is body-scoped.** Switched on, it enqueues a JS schema builder and adds `itemscope itemtype="https://schema.org/FAQPage"` to `<body>` through `bricks/body/attributes`, a guaranteed duplicate wherever schema is owned centrally. It's off by default; leave it off and note why on the element, since the control looks helpful. The detect commands above catch it.
**First seen:** JBM, 2026-06-11 — a migration to a hand-owned `@graph` validated clean for three days by counting JSON-LD blocks, while accordion microdata on twelve templated pages kept producing a duplicate `FAQPage`. · **Extended:** MMHN, 2026-07-21 — a Pro Accordion chosen for a guide FAQ where the core plugin already emitted `FAQPage`; caught before enabling, and verified as exactly one `FAQPage`.

### `wp_rank_math_internal_links` is stale, and a raw HTML link count is inflated by nav/footer chrome
**Symptom / When:** You audit internal links to a page and get two contradictory answers, both wrong. Rank Math's table reports roughly one inbound link and names a source post whose current HTML contains no such link. A raw grep of rendered HTML reports several — from the homepage and every location page — which reads perfectly healthy.
**Why:** The table records only links Rank Math has processed and is **not re-synced when content changes**, so it names sources that no longer link and misses ones that now do. The raw grep meanwhile counts the **header menu, the mobile menu and the footer menu** — site-wide chrome carrying no topical signal. Neither number describes in-content linking, and they fail in *opposite* directions, so agreeing with either is a coin flip.
**Fix:** Count links inside the **content region only** — between the header and `<footer>` on the rendered page — and treat Rank Math's table as a lead, never as evidence:
```bash
curl -s "<url>" | python3 -c "
import sys, re
h = sys.stdin.read(); pre = h[:h.find('<footer')]
print(len(re.findall(r'href=\"[^\"]*<target-path>/?\"', pre)))"
```
Then check each match's byte offset against the header and footer offsets to confirm it is genuinely in-content.
**First seen:** JBM, 2026-09-03 — diagnosing a service page's authority gap. Rank Math reported 1 inbound link (from a post with zero in its HTML); rendered HTML reported 7. The true in-content count was **zero** — every link was menu or footer chrome.


### Rank Math: `wp plugin update` leaves `rank_math_version` behind the code, and a file rollback then puts it AHEAD — Pro re-runs its updater on every admin load

**Symptom / When:** You update (or roll back) Rank Math with WP-CLI or by swapping the plugin directory. Nothing errors. But `wp option get rank_math_version` disagrees with `RANK_MATH_VERSION`, and if Rank Math SEO **Pro** is installed it silently decides it needs to run its update routine on **every** wp-admin page load. Rolling the files back *after* someone has loaded wp-admin leaves the option **higher** than the installed code — the inverse of the usual mismatch, and the one nothing in the UI will tell you about.
**Why:** Rank Math runs its migrations from `Updates::hooks()` → `$this->action( 'admin_init', 'do_updates' )` (`includes/class-updates.php:58`). **WP-CLI never fires `admin_init`**, so a CLI update installs new code and leaves the option untouched. The next genuine wp-admin request fires `perform_updates()`, which walks the migration map and finishes with `update_option( 'rank_math_version', rank_math()->version )` (`:101`). That request can land *between* your update and your rollback. Pro gates its own updater on a bare inequality, not a version comparison — `return $reactivating || ( function_exists('rank_math') && rank_math()->version !== get_option('rank_math_version') );` (`rank-math-pro.php:263`) — so option-ahead-of-code is just as "true" as option-behind-code.
**Fix / rule:** After **any** Rank Math file-level change — CLI update, rollback, directory swap, Version Control rollback — read both and realign explicitly:
```bash
wp eval 'echo "code=".RANK_MATH_VERSION."  option=".get_option("rank_math_version")."\n";'
wp option update rank_math_version <the installed code version>
```
Two riders: **(1)** check `$rank_math_min_version` in `rank-math-pro.php` before updating free — Pro declares a hard floor (3.0.117 → `1.0.274`) and will deactivate itself below it. **(2)** Read the pending migration files in `includes/updates/` before running them on production; they are small and occasionally destructive — `update-1.0.277.2.php` deletes WordPress Application Passwords by name.
**First seen:** TAB, 2026-09-11. RM 1.0.274.1 → 1.0.278 applied via WP-CLI, then rolled back on instruction. Straight after the CLI update: code 1.0.278 / option 1.0.274.1. After an intervening wp-admin hit: option 1.0.278. After restoring the 1.0.274.1 files: code 1.0.274.1 / option **1.0.278**. Realigned by hand. **Rendered titles, meta descriptions, canonicals, robots and the full JSON-LD `@type` census were byte-identical before and after the whole round trip** — the desync is a control-plane fault, not a rendering one, which is exactly why nothing surfaces it.

### SEOPress on a Bricks site — eight defaults to correct before it is trusted (free and PRO)
**Symptom / When:** SEOPress is activated on a Bricks build that already owns social images and all JSON-LD in a core plugin. Everything "works", but the head is wrong in ways no validator flags.
**Why / what each one is:**
1. **It emits its own image tags and a WebSite JSON-LD on activation:** `og:image*`, `twitter:card`, `twitter:image`, empty `twitter:site`/`twitter:creator`, and `#website-schema`. With a core plugin also printing `og:image`/`twitter:card`, every page carries two of each.
2. **Archive title templates ship broken.** `%%cpt_plural%% %%current_pagination%% %%sep%%` has no site title and renders `Events  -`.
3. **`%%post_excerpt%%` is blank on Bricks pages.** It's the same mechanism as "Rank Math `%excerpt%` description template produces junk on Bricks-built Pages": Bricks leaves `post_content` empty, so a page without a hand-written excerpt gets no meta description.
4. **An empty `%%current_pagination%%` leaves a trailing space** in the `<title>`.
5. **Free has no robots.txt editor, and it disables WP core's `wp-sitemap.xml`,** so nothing advertises `/sitemaps.xml` in robots.txt.
6. **Stock defaults print JSON-LD, and two PRO emitters ignore the module toggle.** Free prints the `WebSite` block on the front page whenever Social is on, gated only by `seopress_titles_nositelinkssearchbox`. PRO's manual schemas (`PrintRichSnippets`) check only `seopress_rich_snippets_enable`, and PRO ProfilePage only `seopress_rich_snippets_profilepage_enable`, so switching the module off in the dashboard silences neither. Activation also turns every module on (GA, Dublin Core, AI, llms.txt, bot, local business, breadcrumbs). Set `seopress_titles_nositelinkssearchbox=1` and knowledge type `none`, unset `seopress_rich_snippets_enable` / `_profilepage_enable` / `seopress_breadcrumbs_json_enable`, then lock it in code by returning `''` from `seopress_schemas_website_html`, `_organization_html`, `_local_business_html`, `_profile_page_html`, `seopress_pro_breadcrumbs_json`, `seopress_schemas_faq_html` and `_faq_block_html`. Verify exactly one `ld+json` per page.
7. **Free drops WooCommerce's own noindex on cart, checkout and My Account.** It prints its own robots tag from `seopress_titles_noindex_bypass()` (`inc/functions/options-titles-metas.php`, `wp_head` priority 0), overriding Woo's `wp_robots` noindex; PRO's WooCommerce module merely hooks that filter back to `'1'`. Without PRO, add the filter yourself at priority 20 on `is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url()`. PRO's other three Woo jobs are one-liners: `do_redirect_guess_404_permalink` → false; `remove_action( 'get_the_generator_html' | 'get_the_generator_xhtml', 'wc_generator_tag', 10 )`; and `product:price:amount` (`wc_get_price_including_tax()`) plus `product:price:currency`, gated on `SEOPRESS_PRO_VERSION` being undefined so it can't double-print. Prove parity with byte-identical `<head>` snapshots (before / code + PRO on / PRO off), using a cart cookie so `/checkout/` renders instead of redirecting.
8. **With `blog_public=0` SEOPress prints no robots meta at all,** so its noindex settings can't be verified on a noindexed staging site, only after launch.
**Fix:** Suppress the unwanted emitters with SEOPress's **own output filters**, each returning `''`: `seopress_social_og_thumb`, `seopress_social_twitter_card_summary`, `seopress_social_twitter_card_thumb`, `seopress_social_twitter_card_site`, `seopress_social_twitter_card_creator`, `seopress_schemas_website_html`. Don't switch the Social module off, because that also removes the text tags (`og:title` etc.). Then:
- Rewrite the title templates in the option.
- Write per-page descriptions to `_seopress_titles_desc`.
- `trim()` the title in a `seopress_titles_title` filter.
- Add the `Sitemap:` line with core's `robots_txt` filter.

For CPTs where one template can't make titles unique (recurring events), build titles and descriptions in `seopress_titles_title` / `seopress_titles_desc`, and step aside when `_seopress_titles_title` / `_desc` is set on the post. Keep the whole config as an idempotent script that backs up the option first. Verified on SEOPress 10.2; all six filter names confirmed in its source.
**First seen:** WCDP, 2026-09-30. · **Extended:** MMHN, 2026-09-29 — a second `WebSite` graph on the home page right after activation on defaults (SEOPress 10.2 / PRO 10.2.1). · MMHN, 2026-10-08 — PRO removed; its Woo noindex and three other jobs moved to the core plugin, with `<head>` byte-identical on 11 URLs.

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
**Fix — three routes:**
- **Re-order** when you're theming Woo surfaces site-wide rather than fighting one rule. The child theme cannot win this on ordering, so stop putting Woo overrides there: move them into the project's core plugin and enqueue at `wp_enqueue_scripts` **priority 99 with a dependency on `bricks-woocommerce`**, which lands the sheet after *both* the Woo layer and the Theme Style export. Measured order on one site: child theme at position 5, `bricks-woocommerce-css` at 6, `bricks-theme-style-*` at 9, plugin sheet at 16 — no specificity tricks needed for anything after that. This is the route to prefer when a project has a dedicated Woo override sheet; the two below are for one-offs.
```php
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style( '<prefix>-woo', PLUGIN_URL . 'assets/css/woocommerce.css',
        array( 'bricks-woocommerce' ), filemtime( PLUGIN_DIR . 'assets/css/woocommerce.css' ) );
}, 99 );
```
- **Opt out** when you're fully theming a surface: drop the conventional hook class (e.g. `woocommerce-MyAccount-navigation` from the `<nav>` in the `navigation.php` override; keep your own `.acct__nav`). Per-`<li>` `--{endpoint}` classes from `wc_get_account_menu_item_classes()` are unaffected. Opting out also disables Bricks' account-nav JS that would double with a custom toggle.
- **Out-specify** for a one-off rule, with the doubled-class trick (no `!important`): `.woocommerce-table--order-details.woocommerce-table--order-details tfoot { background: transparent; }` — two classes (0,2,1) beats Bricks' (0,1,2). Same family as the `@layer bricks` and ghost-border cascade fights.
**Process note (carry-forward):** this finding, the ACSS `:where(section…)` one, and the `.btn--primary` one ALL appear only with the full stylesheet cascade loaded. When verifying a PHP-rendered Woo/portal surface by headless screenshot, replicate the page's ENTIRE stylesheet set in source order (Advanced Themer → automatic.css → frontend-light-layer → child style.css → woocommerce-layer → content-default → theme-style-* → post-* → automatic-bricks). A tokens+ACSS+style.css subset gives false confidence — pull the real list from the rendered page's `<link>`s (auth-cookie curl for gated pages).
**Logged-out auth forms, and an ID hidden inside `:not()`.** The same sheet styles Woo's logged-out login and lost-password forms: `.woocommerce-account .woocommerce-form-login:not([class^="brxe-"]) { display: flex; flex-direction: column; text-align: center }`, `… > .woocommerce-form-login:not([class^="brxe-"]) { width: 50%; margin: auto }`, and `.woocommerce-account .woocommerce:not(#brx-content) > h2 { text-align: center }`. The attribute inside `:not()` adds a class's weight, and `:not(#brx-content)` adds an **ID**, so the heading rule is (1,2,1) and no class-only selector can beat it. Bring an ID of your own: `.woocommerce-account:not(.logged-in) #brx-content > .woocommerce > h2 { text-align: left }`. Whenever a rule refuses to lose, read its full selector for `:not()` arguments. Side trap: a My Account dashboard grid on `.woocommerce-account .woocommerce` also matches the logged-out page; scope dashboard layout with `.logged-in`.
**It can't simply be dequeued off store pages while the header carries a mini-cart.** The header `woocommerce-mini-cart` element is styled by this sheet; dropping it outside the store pages changed every page's header (the mobile header grew ~95px). An extract of only the mini-cart, add-to-cart and notice rules, rewrapped in `@layer bricks` and inlined, was pixel-identical on ten pages at two widths but measured no gain beyond Lighthouse noise in an 8-run A/B, so it was reverted (it also depends on Bricks' internal file layout). Chromium's CSS rule-usage coverage (`CSS.startRuleUsageTracking`) is useless on `@layer`-wrapped sheets, reporting more used bytes than the file holds; test removals by pixel diff.
**First seen:** VMG, 2026-06-07 — a dashboard account nav rendered unstyled on the real page after a partial-CSS preview had shown it correct; the order-details tfoot the same day. Re-order route: MBC, 2026-05-22 — a whole Woo visual overhaul kept losing to the same two sheets until the override CSS was moved out of the child theme into the core plugin. · **Extended:** MMHN, 2026-07-20 — unifying Woo's login and lost-password forms with the Bricks auth pages. · MMHN, 2026-10-05 — a render-blocking CSS pass.

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
**See also:** 04 › Email delivery › "Mailgun SMTP from a core plugin — force From and envelope Sender, or DMARC fails".

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

### Bricks WC template types take over the render — the `[woocommerce_*]` shortcode on the page is inert
**Symptom / When:** You open the Cart / Checkout / Shop / single-product WP page in the editor, or run `wp post get N --field=post_content`, and find only a bare shortcode like `[woocommerce_cart]`. Editing that page changes nothing on the front end. Debugging "why does the cart render this markup" through the page's content leads nowhere, because the markup you are looking at is never executed.
**Why:** Bricks ships native WooCommerce **template types** — `wc_cart`, `wc_checkout`, `wc_product`, `wc_archive_product`, `wc_thankyou`, `wc_empty_cart`, `wc_account_*`. A published `bricks_template` of one of those types takes over the render for the matching WC page **entirely**, and the shortcode in the WP page's `post_content` is never run. The Bricks WC elements (`woocommerce-cart-items`, `woocommerce-mini-cart`, `woocommerce-notice`, …) call WC's own functions underneath, so form actions and nonces still flow through `WC_Form_Handler` on POST — which is why the page *behaves* like a normal Woo page while being impossible to find by reading its content.
**Fix:** Edit the template, not the page. Enumerate what actually renders before you start:
```bash
wp post list --post_type=bricks_template --fields=ID,post_title --format=table
wp post meta get <TEMPLATE_ID> _bricks_template_type      # wc_cart, wc_checkout, …
wp post meta get <TEMPLATE_ID> _bricks_page_content_2 --format=json | jq .
```
Keep the type→ID map in the project's `CLAUDE.md`; it is the first thing anyone debugging a Woo surface needs and it is not discoverable from the page.
**First seen:** MBC, 2026-05-22 — found while tracing a cart nonce bug. The working assumption was that the cart shortcode emitted a broken nonce; the shortcode was never executing, and a Bricks `wc_cart` template was the real render path.

### WooCommerce HPOS — `--with-sync` keeps writing legacy `wp_posts` rows until compat mode is explicitly dropped
**Symptom / When:** HPOS is enabled and reads come from `wp_wc_orders`, so the migration looks finished. It isn't. Legacy `wp_posts` `shop_order` rows keep growing in lockstep, and everything that reads them keeps working — which is exactly what hides the problem.
**Why:** `wp wc cot enable --with-sync` makes `OrdersTableDataStore` authoritative but keeps dual-writing to the legacy rows as a safety net. Sync mode is easy to forget: custom reports, dashboards and integrations reading legacy `shop_order` rows continue to function, so nothing signals that the migration is incomplete. The failure arrives later and elsewhere — someone writes a query against `wp_wc_orders` only, it passes in dev, and then compat mode gets dropped in production and whatever still read legacy rows breaks silently.
**Fix:** Treat compat mode as a task with an end date, not a setting. After a safety window (about a week of live orders):
1. Audit for legacy readers — grep the active plugin set, mu-plugins and any external reporting for `shop_order` against `wp_posts`.
2. `wp wc hpos disable_compat_mode` — stops the dual writes.
3. Optionally `wp wc hpos cleanup` to remove the legacy posts. **Order matters:** posts only become eligible once compat mode is off; running cleanup earlier is a silent no-op.
Take a DB dump before enabling HPOS at all. Reverting is `wp db import` — `wp wc cot disable` only flips the flag, it does not migrate data back.
**First seen:** MBC, 2026-05-22 — HPOS enabled with sync on a live store; the compat-mode drop had to be tracked as an explicit open item precisely because nothing in the running site would ever surface it.

### WooCommerce Shipping label printing needs `xmlrpc.php` reachable — a blanket block breaks it
**Symptom / When:** A standard hardening pass adds an `xmlrpc.php → 403` rule, and WooCommerce Shipping label printing stops working. The admin shows a connection/account error; the underlying request 403s.
**Why:** WooCommerce Shipping connects through WordPress.com, and that Jetpack-style handshake runs over `xmlrpc.php`. Blocking the endpoint — the near-universal default in bot-block and brute-force hardening — kills the handshake. The two layers that typically do it are an `.htaccess`/server rule and a performance plugin's "disable XML-RPC" toggle; **both** must be relaxed, and a site can look correctly configured while one of them still blocks.
**Fix:** On any site running WC Shipping, exempt `xmlrpc.php` from the block and leave the plugin toggle off. Mark the exemption **in the file, at the rule**, with the reason and date — the whole failure mode is a future redeploy silently reinstating a rule nobody remembers was removed on purpose:
```apache
# xmlrpc.php block intentionally omitted — required by the WordPress.com /
# WooCommerce Shipping handshake. Do not re-add while WC Shipping is in use.
```
When redeploying a shared hardening block, replace only the delimited block and re-emit it from a source that already omits the rule; never copy another site's file over the top. The exemption can be withdrawn if WC Shipping is ever dropped.
**First seen:** MBC, 2026-05-14 — a fleet-wide bot-block rollout took out label printing on the one site in the fleet using WC Shipping.

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

### Bricks `#brx-content` is a FLEX ITEM of `<body>` — `max-width` + auto margins on it shrink-wrap wide native blocks (Woo Cart/Checkout) and overflow on mobile
**Symptom / When:** Styling a WP-native page rendered by Bricks' default content template, such as a WooCommerce Cart or Checkout **Block**. You cap `#brx-content` with `max-width` and `margin-inline: auto`. Desktop looks right; on mobile the page scrolls sideways (`body.scrollWidth` far past the viewport), `#brx-content` measures ~1366px on a 390px viewport, and the block keeps its two-column desktop layout. `min-width: 0` doesn't fix it. Hand-rolling `display:flex; flex-wrap:wrap; flex-basis` on the block's sidebar layout to force stacking makes it worse: the sidebar sits beside the main column, off-screen.
**Why:** Bricks renders default content in `<article id="brx-content">` with `body { display: flex }` and `#brx-content { flex: 1 }` (see "Front-end-only CSS"), so `#brx-content` is a flex item. Auto margins on a flex item consume the free space and **disable its stretch**, so it sizes to its own max-content width, capped by `max-width`. The Cart/Checkout blocks carry a wide min-content, so the article shrink-wraps to ~1366. The blocks then measure themselves as large: they set `.is-large` / `.is-medium` / `.is-small` / `.is-mobile` from a ResizeObserver on their **own** width, and Woo's stylesheet stacks columns from those classes. Overriding `display` / `flex-wrap` / `flex-basis` fights that system.
**Fix:** Leave `#brx-content` full-width and cap its **direct children**, which are block-level, so `max-width` + auto margins behave normally and the block shrinks to the viewport:
```css
.woocommerce-cart #brx-content, .woocommerce-checkout #brx-content, .woocommerce-account #brx-content {
	min-width: 0; padding-block: var(--section-space-m) var(--section-space-l);
}
.woocommerce-cart #brx-content > *, .woocommerce-checkout #brx-content > *, .woocommerce-account #brx-content > * {
	max-width: var(--content-width); margin-inline: auto; padding-inline: var(--space-m);
}
```
Then skin the block's components (borders, colors, type) without touching its layout mechanics; it stacks itself. Applies to any wide native content on a Bricks default-content page. Verify `body.scrollWidth` equals the viewport at 390. For the cart block above 1320px, see the `.alignwide` entry below.
**First seen:** MMHN, 2026-07-18 — a Woo Cart/Checkout Block skin. Ancestor-chain measurement showed `body` as `display:flex` and `#brx-content` shrink-wrapped to 1366 on a 390 viewport; moving the cap to `#brx-content > *` dropped `scrollWidth` to 390, and Woo stacked natively once the custom flex overrides were removed.

### Bricks ships an UNLAYERED `.alignwide { width: 120%; margin-left: -10% }` — capping `max-width` on a Woo Cart Block isn't enough
**Symptom / When:** The Cart Block (`.wp-block-woocommerce-cart.alignwide`) overflows or sits off-center at desktop widths above 1320px, while the checkout block on the same skin is fine. DevTools shows `.alignwide` from `frontend-light-layer.min.css` with `width: 120%` and `margin-left: -10%` live and only `max-width` struck.
**Why:** Bricks' frontend CSS puts `.alignwide` outside its `@layer bricks` block, so at (0,1,0) it beats Woo's `.wp-block-woocommerce-cart { width: 100% }` on source order. A `@media (max-width: 1320px)` reset hides it below that width, which is why checks at 1024–1280 pass. Bricks' `woocommerce-layer.min.css` adds `.wp-block-woocommerce-checkout.alignwide { width: 100% }` for checkout, but nothing for the cart. A container rule that sets only `max-width` and `margin-inline` leaves `width: 120%` and the negative physical margin in play.
**Fix:** Pin the block with ID specificity, for `.alignfull` too:
```css
.woocommerce-cart #brx-content > .alignwide { width: 100%; margin-left: auto; margin-right: auto; max-width: var(--content-width); }
```
Test above 1320px, not just at mobile and tablet widths.
**First seen:** MMHN, 2026-09-06 — a site owner's incognito Chrome review at ~1373px showed a horizontal scrollbar with the `.alignwide` declarations live. ⚠️ Headless Chromium sweeps at 1366/1373/1375 with a populated cart did **not** reproduce it. Treat "headless says clean" as necessary, not sufficient, for Woo Blocks layout.

### Adding `theme.json` to a Bricks site: keep it settings-only (no `styles`, `defaultPalette: false`) or it leaks base CSS into Gutenberg-rendered content
**Symptom / When:** A Bricks site needs `theme.json` so WooCommerce Blocks (Cart, Checkout) inherit brand tokens. Ship one with a `styles` block, or leave the default palette and font sizes on, and Gutenberg-authored content rendered through a Bricks `post-content` element (news posts, say) picks up WP's global-styles base CSS and preset classes, restyling type and fighting the child theme.
**Why:** `theme.json`'s `styles` key emits `global-styles-inline-css` (element base styles plus layout) site-wide, and `defaultPalette: true` plus default `fontSizes` add preset classes and variables. On a Bricks site almost nothing is block-rendered **except** `the_content()` on post types that keep Gutenberg, so those surfaces are where it shows.
**Fix:** A settings-only file: `version: 3`, `settings.color.palette` (with `defaultPalette: false`, `custom: false`), `typography.fontFamilies` / `fontSizes`, `layout.contentSize` / `wideSize`, and **no `styles` block**. Presets reach the Blocks without element base styles, so it's inert for the Bricks front end. Verify by curling a Gutenberg-rendered post: `grep -c wp-block-library-css` stays 0 and the prose is unchanged. (This is the safe way to do what "Cart/Checkout pages default to BLOCKS" means by theming through `theme.json`.)
**First seen:** MMHN, 2026-07-18 — a token bridge for the Woo Cart/Checkout Blocks; a news single (Gutenberg content in a Bricks post-content element) rendered identically afterward.

### Bricks `woocommerce-notice` element renders "PHP class does not exist" — it's registration-gated behind a site-wide setting, and you don't need it
**Symptom / When:** A `woocommerce-notice` element placed in a product or cart template (the file exists in `bricks/includes/woocommerce/elements/`) renders a pink `woocommerce-notice: PHP class does not exist` box on the front end.
**Why:** Bricks registers the element only when the global setting `woocommerceUseBricksWooNotice` is on (`Woocommerce::use_bricks_woo_notice_element()`), and turning it on also removes every native `woocommerce_output_all_notices` hook site-wide. It's an opt-in "I'll place every notice myself" mode, not a per-template element. An unregistered element name isn't validated at write time; it fails at render.
**Fix:** Don't use it. Bricks already prints `<div class="woocommerce-notices-wrapper">` at the top of its Woo template content; native POST add-to-cart notices land there on reload, and Bricks' AJAX add-to-cart fragments insert there too (when `redirect_after_add` isn't `yes`). Style `.woocommerce-message` / `-error` / `-info` globally. See "Bricks WC template types take over the render".
**First seen:** MMHN, 2026-07-19 — a product-single buy panel; the element was dropped, and the native wrapper plus existing notice styles were verified with a real form POST.

### Woo hides the thank-you page's address block from everyone except the order's own customer — verifying as admin makes it look broken
**Symptom / When:** The order-received page renders with no billing or shipping section. The order has both addresses, no template override is in play, and it looks like something is suppressing customer details.
**Why:** `templates/order/order-details.php` gates the block on one line:
```php
$show_customer_details = $order->get_user_id() === get_current_user_id();
```
A guest order has customer ID 0, so viewing it as admin gives `0 === 1` and Woo correctly withholds one person's address from another account. Separately, an out-of-session visitor hitting the order-received URL gets Woo's `woocommerce-verify-email` form instead of the order (anti-enumeration, also by design).
**Fix:** Nothing to fix. Verify as a buyer sees it: place the test order in the session you screenshot from, or point it at the account you're authenticated as (`$order->set_customer_id( 1 ); $order->save();`). Treat "block missing" as a viewer-identity question before a template or CSS one.
**First seen:** MMHN, 2026-07-19 — logged as "address block is MISSING" after an admin screenshot of a guest test order; reassigning it to the admin rendered both columns at once.

### Woo password show/hide toggle on a Bricks site: no vertical anchor, and two competing eyes that can't be recolored
**Symptom / When:** On Woo account and login password fields the eye button renders **below** the input's bottom-right corner instead of inside it, and it's a small black glyph that ignores `color` and doesn't match the site's own eye icon.
**Why:** Position: Bricks' `woocommerce-layer.min.css` sets `.woocommerce-account form .show-password-input { position: absolute; right: 0; width: 40px; display: flex; align-items: center }` with **no** `top`. The toggle sits at its static position, which is roughly right only while Woo's `span.password-input` wrapper is a flex container. Set the wrapper to `display: block` (a natural move to make it full-width) and the static position becomes the line after the input. Paint: two implementations both draw with pseudo-elements. Woo's `woocommerce-layout.css` uses `::before` with an inline data-URI SVG whose fill is hardcoded (`%23111111`); Bricks' layer uses `::after` with the WooCommerce icon font (`content: "\e010"`). Neither follows `color`, and they disagree about which state shows which icon.
**Fix:** Pin it to the field box, kill both pseudo-elements, and mask the button with your own asset so `background-color` colors it. Equal specificity to Bricks', and the skin loads after `bricks-woocommerce`, so it wins on order:
```css
.woocommerce-account form .show-password-input { top: 0; bottom: 0; height: auto; width: 44px;
	background-color: var(--neutral);
	mask: url(icons/eye.svg) center / 20px 20px no-repeat; -webkit-mask: url(icons/eye.svg) center / 20px 20px no-repeat; }
.woocommerce-account form .show-password-input::before,
.woocommerce-account form .show-password-input::after { content: none; }
.woocommerce-account form .show-password-input.display-password { mask-image: url(icons/eye-slash.svg); -webkit-mask-image: url(icons/eye-slash.svg); }
.woocommerce-account .woocommerce-form-row span.password-input input { padding-right: 44px; }
```
Woo's JS adds `.display-password` while the password is **visible**, so base = eye ("show"), `.display-password` = eye-slash ("hide"). Keep theme-local copies of the SVGs so the URL is theme-relative and survives a domain change. Same sheet as "Bricks — the WooCommerce integration sheet loads AFTER the child theme".
**First seen:** MMHN, 2026-07-20 — the skin's own `display: block` on the wrapper (added two days earlier) had dropped the toggle out of every password field; matched to Bricks' native `passwordToggle` eye on the custom auth pages the same day.

### WooCommerce Blocks analytics hooks — the authoritative list is Woo's own bundled GA script
**Symptom / When:** Wiring GA4 ecommerce on a Blocks cart and checkout. The Blocks data store dispatches `wp.hooks` actions named `experimental__woocommerce_blocks-${event}` and `experimental__woocommerce_blocks-checkout-${event}`, but the names are built dynamically, so grepping the blocks JS finds only the prefix, and the docs are thin.
**Why:** The concrete subscriber list ships in WooCommerce core at `assets/client/blocks/wc-blocks-google-analytics.js` (3 KB; it loads only with the separate Google Analytics Integration plugin, but the file is there to read). It names every hook and payload: `-cart-add-item` / `-cart-remove-item` / `-cart-set-item-quantity` `({product, quantity})`; `-checkout-render-checkout-form` / `-set-email-address` / `-set-shipping-address` / `-set-billing-address` `({storeCart})`; `-checkout-set-selected-shipping-rate` `({shippingRateId})`; `-checkout-set-active-payment-method` `({paymentMethodSlug})`; `-checkout-submit`; `-product-list-render` `({products, listName})`; `-product-view-link` / `-product-render` `({product, listName})`; `-product-search` `({searchTerm})`; `-store-notice-create` `({status, content})`. `product` is the Store API product object (`id`, `sku`, `name`, `prices.price` in minor units plus `prices.currency_minor_unit`, `categories[]`).
**Fix:** Subscribe with `wp.hooks.addAction( name, ns, cb )` (enqueue with `wp-hooks` as a dependency). Two traps. (1) `-cart-add-item` fires only for Store API adds (Blocks buttons, mini-cart). Classic `ajax_add_to_cart` buttons (Bricks product cards) go through wc-ajax and fire jQuery `added_to_cart` on `document.body` instead, and the classic single-product POST form fires neither in the browser; use the server-side `woocommerce_add_to_cart` action plus a WC-session flag flushed on the next render. (2) `-checkout-set-selected-shipping-rate` re-fires on every address edit with the same rate, so dedupe by `shippingRateId` or `add_shipping_info` double-counts. See "A populated GA4 conversion number proves nothing" for verifying from GA4's side.
**First seen:** MMHN, 2026-09-06 — GA4 ecommerce on a Blocks store; every event verified headless through a cash-on-delivery order.

### HPOS: `wp wc shop_order delete --force` only TRASHES — the row stays in `wc_orders`
**Symptom / When:** Cleaning test orders on a store with High-Performance Order Storage. `wp wc shop_order delete <id> --force --user=1` reports "Trashed shop_order", and `SELECT COUNT(*) FROM wp_wc_orders` still counts it, with status `trash`.
**Why:** The REST-backed CLI command's force flag isn't honored for the HPOS order store (seen on WooCommerce 11.1); it routes to the trash path.
**Fix:** `wp eval '$o = wc_get_order( <id> ); if ( $o ) { $o->delete( true ); }'`. CRUD `delete( true )` removes the `wc_orders` row, its address and operational rows, and the order items. Verify with the count afterward.
**First seen:** MMHN, 2026-09-06 — GA4 test orders, plus two earlier test orders that had been "deleted" and were still counted.

### WooCommerce — "Refund amount" is read-only when taxes are enabled, even at zero rates
**Symptom / When:** Order → Refund: the Refund amount field won't take input, the button reads "Refund $0.00 via …", and nothing can be refunded. Looks like a broken refund screen.
**Why:** `includes/admin/meta-boxes/views/html-order-items.php` prints `readonly` on `#refund_amount` whenever `wc_tax_enabled()`, because an order-level amount would skip refunding tax. A store with taxes **enabled at zero rates** (common for food, often inherited from an old site) gets the lock though no tax exists.
**Fix:** Refund by line: set the refund quantity on each product line and type the shipping line's refund total. The amount builds itself; then use the gateway refund button. Don't switch taxes off for this; it's a store setting, so confirm with the client if it ever changes.
**First seen:** MMHN, 2026-10-08 — refunding the live launch test order.

### WooCommerce Stripe Gateway 11.x manages its own webhook — and Stripe caps endpoints at 3 API versions per mode
**Symptom / When:** Planning "the client must add the webhook" for a cutover, or *Configure webhooks* failing with "You have reached the maximum of 3 unique versions of test webhook endpoints".
**Why:** `WC_Stripe_Account::configure_webhooks( 'live'|'test' )` (REST: `POST /wc/v3/wc_stripe/account_keys/configure_webhooks` with `live_mode`; rate-limited to once a minute) uses the secret key to create the endpoint at `home_url()/?wc-api=wc_stripe` on API version `WC_Stripe_API::STRIPE_API_VERSION` (`2026-03-25.dahlia` in 11.0.0), stores the signing secret in `woocommerce_stripe_settings`, and deletes older endpoints at the same URL. Stripe allows at most **3 distinct API versions** among an account's endpoints per mode, so an account carrying old endpoints from other sites refuses a fourth. Webhook health lives in `WC_Stripe_Webhook_State` (`wc_stripe_wh_test_last_success_at` …); read it with `WC_Stripe_Webhook_State::get_webhook_status_message()`, since a `wp option list --search` can miss it.
**Fix:** No client step is needed for webhooks when you hold the secret key. On a domain-preserving cutover the endpoint URL doesn't change, so carrying the old site's `webhook_secret` with the keys keeps events verifying from minute one; run *Configure webhooks* afterward. List `GET /v1/webhook_endpoints` first and clear a dead endpoint if three versions are in use. Test staging with **your own** account's test keys, never live keys before the flip. `woocommerce_stripe_settings` also holds `express_checkout_button_locations` / `link_button_locations` / `amazon_pay_button_locations`: when swapping keys, change only the key fields, never copy the whole option between installs.
**First seen:** MMHN, 2026-10-02 — a staging test-mode checkout reached processing via the plugin-created webhook.

### WooCommerce Stripe: the customer ID is a *user option* (`{prefix}_stripe_customer_id`), and saved cards re-sync from it
**Symptom / When:** Migrating customers between sites. A usermeta query for `_stripe_customer_id` finds nothing, or an importer copies the key under the wrong name, and returning customers lose their saved cards.
**Why:** The gateway reads and writes it with `get_user_option()` / `update_user_option()`, so it's stored under the blog prefix, e.g. **`wp__stripe_customer_id`** (two underscores). For saved cards, `sync_and_retrieve_customer_payment_tokens` (11.0.0) lists the customer's payment methods from Stripe on each logged-in token lookup, creates missing local tokens and deletes local tokens Stripe doesn't have. Legacy `src_` tokens get culled anyway.
**Fix:** Read the ID with the source site's prefix and write it with `update_user_option( $uid, '_stripe_customer_id', $id, false )`. Don't copy `woocommerce_payment_tokens`; the ID alone brings the cards back once **live** keys are in. Test keys answer a live `cus_` with *No such customer*, so prove it after the flip: `wp_set_current_user( $u ); count( WC_Payment_Tokens::get_customer_tokens( $u, 'stripe' ) );`.
**First seen:** MMHN, 2026-10-05 — a customer-account import rehearsal ahead of launch.

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
**⚠️ Render order is each field's `sort_index`, not its position in the section's `fields` array.** A field object added without one defaults to `0` and renders **first**. For example, a consent notice appended under the submit publishes above "First name". When adding a field through the API, give it an explicit `sort_index` (max + 1, or re-index the section) before `db_update_from_object()` → `db_publish()`. Then check the order in the **published** copy (`wsf_form_get_object($id, true)`), not the draft, and re-export the `.wsf.json`, because the portable artifact goes stale silently. This is the API route, and it's safe. The caution in the Turnstile/CAPTCHA entry below is about patching `wp_wsf_field.sort_index` directly in the database, which does nothing until the form is republished.
**⚠️ Verify in a real browser, NOT curl** — WS Form renders fields **client-side** (JS hydrates a `wsf-form-canvas` skeleton), so curl shows the canvas and section labels but few or no `<input>`s. That is expected, not a failure.
**First seen:** TAB, 2026-05-30 — a quote form built entirely via the API and exported as a re-importable artifact; the curl output nearly read as a broken build. · WCDP, 2026-08-18 — a consent-notice field added via the API published at the top of the form until the section was re-indexed.

### WS Form — skin it by overriding root `--wsf-form-*` vars, never by writing rules against `.wsf-field` / `.wsf-label`
**Scope:** this is the fallback for a WS Form site **without** ACSS's form layer — which includes every `bricks-native` site unless its stack file names another form owner. `[stack:acss]` The rest of this paragraph: On the ACSS stack (the 2026-08-18 convention) this entry does **not** apply: WS Form is skinned by ACSS's native form settings (`f-light-*` / `f-dark-*`, written with `save_settings()`), and the WS Form styler is off (`css_style` false). `--wsf-*` remaps are dead code there, because their consumers ship in the skin that `css_style: false` dequeues. See `01` → "Skinning third-party UI — check ACSS first", `assets/acss-form-brand.css`, and "WS Form `css_style: false` drops more than the skin" below.
**Symptom / When:** Hand-authored rules (`.wsf-form .wsf-field {…}`, `.wsf-checkbox label`, `[data-checkbox-style=button]`, `.wsf-label-required`) either lose to WS Form's own skin or match nothing at all. Several of those names **don't exist** — `.wsf-checkbox`/`.wsf-radio` wrappers, `data-checkbox-style` and `.wsf-label-required` are invented (real: choices render in `.wsf-fieldset`; the required marker is `.wsf-required-wrapper`).
**Why:** WS Form's **Styler** (form carries `data-wsf-style-id="N"`) builds its entire skin — 800+ component vars — by deriving them from a small set of root theme vars (`--wsf-form-color-base|base-contrast|primary|secondary|accent|neutral|danger`, `--wsf-form-font-*`), auto-generating shades via `color-mix()`. Crucially the roots are declared at **`:where([data-wsf-style-id="N"])` — ZERO specificity**, deliberately, so author overrides win. Writing component-level rules fights a system designed to be driven from the roots.
**How the derivation works (why remapping the root tier is enough).** The component layer *derives* from ~10 semantic roots (`--wsf-form-color-base`, `-base-contrast`, `-primary`, `-accent`, `-neutral`, `-secondary`, `-success`/`-info`/`-warning`/`-danger`) via `var()` references and `color-mix()` ramps (`--wsf-form-color-primary-dark-20: color-mix(in oklab, var(--wsf-form-color-primary), #000 20%)`). Override a root and every derived var recomputes automatically — `color-mix()` re-evaluates against your value, because `var()` resolves to the element's computed value. Each skin compiles to `uploads/ws-form/css/public/public.style.{id}.css`.
**Fix:** Remap the **root** vars to your design tokens on a plain `.wsf-form { }` block — beats `:where()` trivially, applies to all forms, keeps WS Form's tested layout and a11y intact. Override targeted component vars only where a flat recolor isn't enough (label/legend font-family, field border/radius, focus). Find the real names from the served page:
```bash
curl -s <url> | grep -oE '\-\-wsf-[a-z0-9-]+\s*:'
```
**Specifics worth knowing.** `--wsf-form-color-base-contrast` is the "light text on a coloured fill" role (button text → near-white) — **not** a literal contrast of base. On a DARK theme, field *text* (`--wsf-field-color`) must be remapped separately from field *border* (`--wsf-field-border-color`): both default to `var(--wsf-form-color-base)` but need different values. `--wsf-field-box-shadow-width-focus: 0` gives border-only focus. The submit button renders as `.wsf-button-primary`, drawing the `--wsf-field-button-primary-*` tier (bg = primary, text = base-contrast) — no need to touch the neutral tier. Button typography is fully var-driven (`--wsf-field-button-font-family`/`-weight`/`-letter-spacing`/`-text-transform`). Keep the block a **bridge** (roots → your tokens) and reshade by editing the tokens. Fields are JS-injected at runtime, so a form can't be verified over curl — confirm visually.
Choice fields render as chips only when the field meta `checkbox_style`/`radio_style` = `button`; submit picks the primary family via `class_field_button_type=primary`.
**First seen:** TAB, 2026-05-31 — a ~130-line hand-written `.wsf-*` skin with dead selectors, fighting the Styler. Replaced by a ~25-declaration root remap that branded inputs/labels/legends/help/submit/chips in one pass. · **Scoped:** WCDP, 2026-08-11 to 2026-08-18 — a `--wsf-form-*` bridge was built and shipped on an ACSS site before anyone checked ACSS's own form layer, then the WS Form styler was retired in favour of ACSS native form settings.

### WS Form — per-field-type CSS loads AFTER the theme; the native checkbox IS the styled box (sibling of the label, no wrap mode)
**Symptom / When:** Child-theme CSS overriding a WS Form field rule doesn't take at equal specificity. And trying to put the choice `<input>` *inside* its `<label>`, or drawing your own checkbox box, fights WS Form.
**Why:** (1) WS Form enqueues per-field-type stylesheets (`ws-form-public-checkbox.css`, …) **after** the theme, so on equal specificity it wins on source order. (2) WS Form **styles the native input itself**: `input[type=checkbox].wsf-field { appearance:none; … }` IS the visible box, `:checked::after` is the checkmark, driven by `--wsf-field-checkbox-*` vars. The input is `position:absolute` and is a **sibling rendered before** `label.wsf-label`. There is **no input-inside-label wrap mode** — don't try to nest it.
**Fix:** Beat the source-order win with the doubled-class trick (`.scope.scope [data-row-checkbox] > input.wsf-field + label.wsf-label {…}`). Don't hand-draw a box with `::before` — brand WS Form's native one via the vars. For a clickable card option: keep `label.wsf-label` as the full-width card (override its `margin-left:0`, add left padding), set the row `position:relative`, and absolutely-position the input inside the card's gutter. Add a scoping class per field via the `class_field_wrapper` meta.
**First seen:** TAB, 2026-05-31 — matching choice fields to a wireframe (card grid + pills); needed doubled-class to beat WS Form's checkbox CSS, and a hand-drawn `::before` box turned out to be redundant.

### WS Form `css_style: false` drops more than the skin — the section fieldset chrome and `.wsf-hidden-element` lose their CSS
**Symptom / When:** With the WS Form styler off (`ws_form_css.css_style = false`, the ACSS-stack convention in `01`), the form renders on ACSS's form layer as intended. But each section `<fieldset>` shows the **UA default `2px groove` border**, and a section whose label is set not to render prints it anyway ("Section" above the fields). Choice rows set to Horizontal stack vertically.
**Why:** `css_style: false` dequeues WS Form's **entire** style stack, not just the compiled styler skin: `ws-form-public-base.min.css` and every per-field-type file (`-button`, `-textarea`, `-checkbox`, …). Only `public.layout.min.css` survives, because it's governed by `css_layout`. Everything the base file supplied goes with it: the fieldset reset, the `.wsf-hidden-element` hiding rule, and `.wsf-form .wsf-inline` (the Horizontal layout for choice rows). A section with `label_render: ''` still emits its legend, with the `wsf-hidden-element` class (visually hidden, kept for screen readers). With the skin gone that class matches nothing, so the legend computes to `display: table`, fully visible. `[stack:acss]` ACSS's form layer styles legends but neither hides them nor resets fieldsets.
**Fix:** Three child-theme rules for site-wide chrome. No token covers them, so this is legitimately layer 3:
```css
.wsf-form .wsf-section { border: 0; margin: 0; padding: 0; min-inline-size: 0; }
.wsf-form .wsf-hidden-element { /* standard visually-hidden block */
  position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px;
  overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
.wsf-form .wsf-inline { display: inline-block; margin-inline-end: var(--space-s); }
```
Turn section labels off at the data level (`label_render = ''` per section, set through the PHP API and carried by the `.wsf.json` export), not with CSS.
**First seen:** WCDP, 2026-08-18 — found by a `getComputedStyle` DOM probe after screenshots showed "Section" with a rule through it. The legend markup fix had already landed and been verified in the served JSON, so the missing CSS was the only remaining explanation.

### WS Form prints its entire compiled CSS inline on every page — while identical files sit unused in uploads
**Symptom / When:** The HTML document is unexpectedly large, and much of it is CSS. The rendered page has tens of KB of `.wsf-*` rules in unlabelled `<style>` blocks near `</body>`. That happens on any page with a form, and on **every** page if the global header or footer carries one. Meanwhile `wp-content/uploads/ws-form/css/public/` already holds `public.layout.min.css` and `public.style.N.min.css` with byte-identical content, enqueued nowhere.
**Why:** Two independent settings in the `ws_form_css` option decide delivery. `css_compile` compiles the layout and styler CSS to static files under `uploads/ws-form/`. `css_inline`, when true, makes `class-ws-form-public.php` ignore those files and `echo` the CSS into the document from `wp_footer` instead. So compile + inline is the worst combination: the files are generated and then bypassed, and the files on disk look like proof that file delivery is already happening.
**Fix:** Set `css_inline` to false, but **only after confirming `css_compile` is true**. With compile off, the non-inline branch enqueues `WS_Form_Common::get_api_path('helper/ws-form-css')`, which routes the CSS through a PHP REST request on every page. That's worse than inlining. Use the plugin's own setter so the key lands in the right option bucket (with `wp_set_current_user(1)` first, per the WS Form API entry):
```php
if ( ! WS_Form_Common::option_get( 'css_compile' ) ) { exit( 'compile is off — do not flip' ); }
WS_Form_Common::option_set( 'css_inline', false );
```
Verify on the rendered page, not in the option: the `.wsf-*` `<style>` blocks should be gone and `<link>` tags to `uploads/ws-form/css/public/` should be present.
⚠️ **The enqueue is versioned by the PLUGIN version, not the file** (`?ver=<WS Form version>`). Editing the styler rewrites the file but leaves the URL identical, so returning visitors keep the stale CSS for the full cache lifetime. Purge after any styler change. This matters mainly on a site that brands forms with the WS Form styler. `[stack:acss]` On the ACSS-stack convention the styler is off and the surviving layout file rarely changes.
**First seen:** WCDP, 2026-08-18 — a global footer signup put WS Form on every page, so 57KB of CSS rode inside every HTML response. Turning `css_inline` off cut the document from 218,821 to 161,890 bytes (inline CSS gzipped: 10,384 → 4,776 bytes per page view), against a one-time ~6KB gzipped for two files cached 30 days. That breaks even after two page views.

### WS Form inlines a ~95KB form-definition JSON blob per rendered form — no setting moves it, only placement does
**Symptom / When:** The HTML document is still large after WS Form's CSS has moved to file delivery. The `.wsf-*` style blocks are gone, so the CSS fix looks like it worked, and for CSS it did. The weight is now in a single unlabelled inline `<script>` of roughly 90–100KB. If the global header or footer carries a form, that script is on **every page**, including pages with no form of their own.
**Why:** WS Form renders fields client-side from a JSON definition (see "A client-rendered form cannot be verified with `curl`"), and it prints that definition inline **per rendered form instance**. The blob is the whole form object: every field with its full type metadata, the action set, conditional logic and i18n strings. **It doesn't shrink with field count.** A one-field email signup ships essentially the same blob as a full contact form. The `ws_form_css` settings (`css_compile`/`css_inline`) govern stylesheets only. The definition is per-form, per-instance state rather than a cacheable static asset, so there's no equivalent toggle for it. **The only lever is placement.**
**Fix:** Treat a WS Form in global chrome (header, footer, any sitewide template) as a per-page-view cost you have to justify. Place forms on the pages that need them. Where a sitewide capture really is wanted, measure first and weigh it against a plain server-rendered `<form>` posting to the same handler. Inline script weight is the signal, and no CSS audit will see it:
```bash
curl -sk "https://<site>/<page>/?cb=$RANDOM" | python3 -c '
import re, sys
d = sys.stdin.read()
s = re.findall(r"<script[^>]*>(.*?)</script>", d, re.S)
print(f"doc={len(d)}  inline-script={sum(map(len, s))}  largest={max(map(len, s), default=0)}")'
```
A largest block in the tens of KB on a page with a trivial form (or none) is this blob. Compare a page with the form against one without it. If they match, the source is global chrome.
**First seen:** WCDP, 2026-08-18 — a one-field footer email signup was removed for unrelated reasons, and a content page dropped from 167,515 to 69,806 bytes (−58%). The largest inline script went from 94,511 to 2,231 bytes. The CSS-inlining fix earlier the same day had recovered 57KB. This blob, 66% larger, had sat unnoticed behind it because every check afterwards was looking for CSS.

### WS Form checkbox `required` is cosmetic — `checkbox_min` is the enforcement, and it renders `data-checkbox-min`
**Symptom / When:** A checkbox group with field meta `required: "on"` (published, and verified in the served JSON) renders no `required` attributes and no marker on its group label. It **accepts submission with nothing checked**: the submission saves with the field empty and the notification email goes out.
**Why:** WS Form's required label-mask span never reaches a checkbox group's top label (`div.wsf-label`), and the renderer doesn't translate field-level `required` into per-input `required` attributes (which would demand that ALL boxes are checked). The real "at least one" mechanism is the **`checkbox_min`** field meta. It emits `data-checkbox-min="N"` on the field wrapper and engages the min/max validation, whose CSS ships in `public.layout.min.css` and so survives `css_style: false`.
**Fix:** Set both through the PHP API: `required: "on"` for semantics and `checkbox_min: "1"` for enforcement. Key the visual marker off the attribute the enforcement renders, so the marker can never disagree with the validation:
```css
.wsf-form [data-checkbox-min] > .wsf-label::after { content: " *"; color: var(--f-light-required-color, var(--action)); }
```
Prove it by behaviour, not by the published meta. Fill the required fields, submit with no boxes ticked, and expect a block with values retained. Then tick one box and expect a clean submit. Delete the probe submissions afterwards.
**First seen:** WCDP, 2026-08-18 — a volunteer form's interest group. `required: "on"` was published, and three probe submissions with empty checkboxes were sitting in `wp_wsf_submit` as proof that it enforced nothing.

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


### WS Form tracking meta is written whenever the form toggle is on — an empty value proves nothing

**Symptom / When:** You add a WS Form tracking variable (UTM, referrer, or a custom `query_var` one),
then try to verify it end-to-end by checking the submission meta. You reason that a present-but-empty
row means "the browser sent an empty value" and an absent row means "the JS never ran". **Both
inferences are wrong**, and the check silently gives false confidence.

**Cause:** `ws-form-pro/includes/core/class-ws-form-submit.php:2589` gates on the *form's* meta
toggle, not on the payload:
```php
if(WS_Form_Common::get_object_meta_value($this->form_object, $meta_key, false)) {
    // ...
    $meta_value = isset($tracking['server_query_var'])
        ? WS_Form_Common::get_query_var_nonce($tracking['server_query_var']) : '';
```
`get_query_var_nonce()` returns `''` when the key is absent from the request, and the row is written
regardless. So while the toggle is on, **the row always exists** — empty is the default, not a signal.

**Consequence:** an empty value is ambiguous (no param on the URL *or* the client JS never ran, and
you cannot tell which), and "row missing" is not a diagnosable state at all. **Only a populated value
proves the chain works.** Pre-existing empty rows from before a feature shipped prove only that the
toggle was already enabled — they are not evidence the JS was ever executing.

**Verify it properly, without sending a submission:** the client injects the hidden field during
**form init**, not on submit (`ws-form-public-tracking.js:57`, reached from `form_tracking()` on page
load). So just load the page with the parameter and inspect the DOM:
```js
// on /your-form-page/?svc=decks-porches
document.querySelector('input[name="wsf_svc"]').value   // -> 'decks-porches'
```
No submission, no notification email, no row to clean up. Note the client and server names differ
(`client_query_var` on the URL → `server_query_var` as the hidden input name).

**Also worth knowing:** the JS gate is the mirror of the PHP one — `ws-form-public-tracking.js:75`
skips any tracking id whose form meta is off, so a config block present in the page's inline
`$.WS_Form.tracking` does **not** mean that tracking is active for that form. Check the form's
published config (`wp_wsf_form.published`) for `"tracking_service":"on"`, not just the page source.

### A populated GA4 conversion number proves nothing — WS Form does NOT push to the dataLayer

**Symptom / When:** GA4 shows a healthy `generate_lead` count, it is marked a Key Event, and every
report treats it as leads. Nothing looks wrong. In fact **nothing is measuring form submissions at
all**, and the number is unrelated traffic.

**How TAB's happened:** `generate_lead` was a GA4 **custom event rule** (Admin → Events → Custom
configurations) matching `page_path` starts-with `/contact` + `event_name` equals `page_view` —
contact-page **pageviews**. It read as a real conversion for 8 weeks. **70 events vs 36 actual form
submissions**, and the main lead path (quote form → redirect to `/thank-you/`) was never counted at
all. The rule predated the rebuild, so `/contact` also matched the OLD site's
`/contact-tab-property-enhancement/` — the "conversion rate improved after the rebuild" claim was
comparing different URL sets on different sites.

**The three assumptions that let it survive — check all of them before trusting any form conversion:**
1. ⚠️ **WS Form's `trigger()` dispatches jQuery events on `document` (`wsf-<slug>`), NOT dataLayer
   pushes.** There is no native `wsf-submit` dataLayer entry to build a GTM trigger on. Its Google
   Analytics *action* would push, but that is a per-form action you must switch on
   (`analytics_google` in form meta — empty here).
2. ⚠️ **GA4 enhanced-measurement `form_submit` never fires for WS Form**, because it submits over
   AJAX rather than doing a native form submit. Enhanced measurement will not save you.
3. ⚠️ **Check the GTM container's actual tag list.** TAB's held exactly two tags (GA4 base +
   `click_to_call`) — there was no `generate_lead` tag at all, which alone should have been
   conclusive.

**Verify a conversion event by provenance, not by magnitude.** Ask *what line of code fires this?*
and follow it to a tag. A number that exists and looks plausible is the failure mode, not evidence.
Cross-check against the source of truth — here, `SELECT COUNT(*) FROM wp_wsf_submit` — the moment
the counts disagree by more than a rounding error, stop using the metric.

**The fix pattern:** push your own namespaced event from the plugin's post-accept lifecycle event
(`wsf-submit-success` — see the entry below for why that one), carry the form ID so GTM can filter,
and make it idempotent against re-renders. Then cut over deliberately: publish the new tag, verify
in a live browser, and delete the old rule **the same day** — overlap double-counts, a gap
zero-counts, and the changeover date becomes a permanent seam in the data that every later report
has to respect.

**First seen:** TAB, 2026-08-19. Seam logged in `project-context.md`; pre-2026-08-19 `generate_lead`
is contact pageviews, post- is real submissions, and 2026-08-19 itself is dirty.

### WS Form `.empty()`s the form on every client render — and an attribute "already done" guard survives it and lies

**Symptom / When:** You append hidden inputs to a WS Form `<form>` on DOM ready. They are there in
view-source and in your tests, but on the live page
`document.querySelectorAll('input[name^="your_prefix"]').length` returns **0**, and the values
arrive empty server-side. Meanwhile WS Form's *own* trackings (e.g. `#tracking_service`) populate
fine, which makes it look like a server problem rather than a timing one.

**Why:** WS Form renders client-side after page load and calls `form_canvas_obj.empty()` at the top
of every render (`ws-form-public.js:860`). On this install the form element carries **both**
`wsf-form` and `wsf-form-canvas`, so `form_obj === form_canvas_obj` and the wipe takes your
appended inputs with it. WS Form's own tracking survives only because `form_tracking()` runs at
`ws-form-public.js:394`, i.e. **after** the render, not before it.

⚠️ **The part that turns a one-render bug into a permanent one:** jQuery's `.empty()` removes
**children but not attributes**. So an idempotency guard written as `form.setAttribute('data-done','1')`
outlives the very inputs it vouches for. A MutationObserver watching for re-renders then fires,
calls your inject function, sees the guard, and returns — for the rest of the page's life.
**Never store "already injected" on the parent. Test for the child.**

**Fix — hook WS Form's own lifecycle.** Events are jQuery events on `document`, named `wsf-<slug>`,
dispatched with `[form, form_id, form_instance_id, form_obj, form_canvas_obj, group_index]`, so the
DOM form is the **4th extra argument**:

```js
jQuery(document).on('wsf-rendered wsf-submit-before-ajax', function (e, form, id, inst, formObj) {
    inject(formObj[0]);            // re-add only what is actually missing
});
```
- **`wsf-rendered`** — fires after each render, the same moment WS Form re-runs its own tracking.
- **`wsf-submit-before-ajax`** — fires immediately before `new FormData(this.form_obj[0])`
  (`ws-form-public.js:3223`). This is the **guarantee**: the payload is built from the DOM form, so
  anything present at this instant is posted. Bind here and render timing stops mattering.

`wsf-submit` also exists but fires earlier in `form_post()`; `-before-ajax` is the one adjacent to
serialization. WS Form posts over AJAX, so a native `submit` listener may never fire — do not rely
on it as the primary path.

**Test it against a render cycle, not against server markup.** The class of bug is invisible to
`curl` + grep, which is exactly how it shipped. A shim that runs
`inject → form.empty() → trigger('wsf-rendered') → trigger('wsf-submit-before-ajax')` and asserts
the inputs exist at the end reproduces it in a second:
`~/bin/tests/test_injection_lifecycle.js` (run it against the old code first — if it doesn't fail,
the test is wrong).

**First seen:** TAB, 2026-08-19 — first-touch attribution build. Caught by Mike's live browser
submission (41), not by any server-side check.

### Custom WS Form trackings silently OVERWRITE the plugin's built-ins if you reuse a key name

**Symptom / When:** Registering attribution trackings through `wsf_config_tracking` with the
obvious names — `tracking_utm_source`, `tracking_utm_medium`, `tracking_utm_campaign`,
`tracking_referrer`. There is **no warning and no error**. The filter array is keyed by tracking
name, so your definition simply replaces WS Form's, and the form UI still shows "UTM Source" as if
it were the stock feature. Anyone who later ticks it gets *your* semantics instead of WS Form's,
with nothing to explain why.

**Why it matters here:** WS Form's built-ins are **last-touch** (`client_source: query_var` reads
the current URL at submit). A first-touch implementation reusing those keys makes the two
irreconcilable — and the collision is invisible until someone compares numbers months later.

**Fix:** namespace them. TAB uses `tracking_tab_*` (which also satisfies the `tab_` prefix rule),
leaving all built-ins intact and separately usable. Email vars follow the key: `#tracking_tab_lead_source`.

**Check before shipping any new tracking:**
```php
remove_all_filters( 'wsf_config_tracking' );
$builtin = WS_Form_Config::get_tracking( false );   // is your key already in here?
```
On this install the built-ins that collide are exactly those four; `tracking_gclid`,
`tracking_lead_source` and `tracking_landing_page` were free.

**Two more things worth knowing about the tracking API:**
- **`client_source` is a fixed switch** (`query_var`/`referrer`/`href`/`hostname`/`pathname`/
  `query_string`/`hash`/`os`/`agent`/`geo_location`) — **no cookie or localStorage option**. So
  anything persisted client-side cannot be read by WS Form's own client tracking. Register the
  tracking with **no `client_source` at all** and a `server_source: 'query_var'`; WS Form then
  skips it client-side and reads your POSTed var server-side. Adding a `client_source` would let
  WS Form overwrite your stored value with a live one.
- **Trackings automatically become CSV export columns** via
  `WS_Form_Submit::get_keys_tracking()` → `class-ws-form-submit-export.php:223`, labelled with the
  tracking's `label`. Nothing extra to wire for exports.
- ⚠️ **`get_query_var_nonce()` enforces a nonce as soon as `is_user_logged_in()` is true.** From
  WP-CLI that means a script doing `wp_set_current_user(1)` (required for the WS Form API — see
  below) must also mint `wp_create_nonce( WS_FORM_POST_NONCE_ACTION_NAME )` into
  `$_POST[ WS_FORM_POST_NONCE_FIELD_NAME ]`, or every read returns a bare `403` JSON blob with no
  stack trace.

**First seen:** TAB, 2026-08-19 — first-touch attribution build (plugin 0.9.19). Caught before
shipping; the first implementation did collide on all four.

### WS Form submission retention hides in a JSON-inside-serialized-PHP blob — and `''` means OFF, not "unset"

**Symptom / When:** Looking for WS Form's submission auto-delete / data-retention setting. The
obvious places are all dead ends: it is **not** in the global `ws_form` option, and **not** a
`wp_wsf_form_meta` key you can grep for (there is no `submit_delete`, `delete_expire` or
`data_retention` key — the full form-5 meta key list contains nothing matching `delete|expire|retain|purge|day`).

**Where it actually lives:** on the **"Save to Submissions" (database) action**, at
`wp_wsf_form_meta` `parent_id=<form_id>`, `meta_key='action'`. That column is **PHP-serialized**
(`unserialize()`, *not* `json_decode()` — decoding it as JSON fails silently and returns null), and
the action's own config is a **JSON string nested inside it** at
`$d->groups[0]->rows[$i]->data[1]`. So it is JSON inside serialized PHP, two decodes deep. Keys:
`action_database_expire` (checkbox) and `action_database_expire_duration` (number, days).

⚠️ **The trap that makes this actively misleading:** a duration can sit there looking armed while
the feature is off. TAB's quote form read `expire=''` / `duration='60'` — the `60` is visible in the
UI and reads as "deletes after 60 days", but `''` is an **unchecked checkbox**
(`class-ws-form-action-database.php:68` gates on `if($this->expire)`), so every submission gets
`date_expire = NULL` and nothing ever expires. **Read the toggle, not the number.**

**Also worth knowing before anyone panics about lost submissions:**
- Expiry is stamped in `post()` **at submission time only** — enabling it is **not retroactive**, so
  existing rows can never be caught by switching it on.
- `db_delete_expired()` sets `status='trash'`; it does **not** hard-delete, and nothing in WS Form
  Pro purges trash on a schedule.
- Blanking the duration does **not** mean "never" — `get_config` falls back to the plugin default of
  **90**. The checkbox is the only real off switch.
- `count_submit` on `wp_wsf_form` is the **non-trashed** count. It will disagree with `COUNT(*)`
  whenever anything sits in trash (TAB: 23/10 reported vs 25/11 actual, 3 launch-day test rows
  trashed) — that is correct behaviour, not a broken counter.
- Fastest ground truth, no decoding needed: `SELECT COUNT(*), SUM(date_expire IS NOT NULL) FROM
  wp_wsf_submit`. All-NULL means nothing is armed, whatever the UI shows. Contiguous `id` values
  with no gaps additionally prove nothing has ever been hard-deleted.

### WS Form Google Sheets: a header cell is NOT a mapping — an unmapped column looks wired and silently writes blank
**Symptom / When:** A field is captured correctly, the database proves it, and the Google Sheets
backup shows an **empty column** for it. The column header exists in the sheet, so the integration
reads as configured — nothing in the UI flags a gap.

**Why:** The add-on matches sheet columns to form fields by **column name**, but the name match only
tells it *where* to write. The write itself comes from a **mapping row on the Google Sheets action**.
Add a header cell without adding the mapping and you get a column that is present, correctly named,
permanently blank, and silently so. The same mechanism retires columns: a header left behind after a
form change keeps its slot and stops receiving data.

**How it presented:** a first-touch lead-attribution field was added to a live quote form and its
column appended to the sheet. Every row carried the value in the database; every row was blank in the
sheet. Counted at audit: the action held **10 mappings against a 15-column tab**, and the newest
field was one of the five unmapped.

🔴 **The consequence is worse than a blank cell.** A reader who trusts the sheet sees all-blanks and
concludes *"unattributed traffic"* — a wrong answer that looks like a real one — rather than *"not
wired"*. And if the sheet is the **backup**, that field has **no backup at all**: it exists only in
the database, which is usually the surface whose restore has never been tested.

**Fix:** after adding any field that must reach the sheet, verify the **round trip on a real
submission**, not the schema — submit, then read the cell. Count mappings against columns as a
standing check (`mappings < columns` is normal because of retired columns, but every *live* field
must appear on both sides). ⚠️ **Do not write a verification step that checks the sheet cell for a
field you have not confirmed is mapped** — a blank then proves nothing about the capture, and such a
step will condemn a working chain. Verify capture at the database, and treat the sheet as a separate
delivery question.

**First seen:** TAB, 2026-09-11 — found while verifying the sheet for an unrelated row-count audit.
The project's own lead-source verification recipe had step 2 pointed at exactly this column.

### Reading a WS Form–linked Google Sheet from the server: use the add-on's credential, not a service account
**Symptom / When:** You need to read the spreadsheet a form writes to — to audit rows, reconcile
counts, or check what actually landed — and the project already has a Google service-account key for
Search Console or GA4. It will not work, and the error names the wrong problem.

**Why:** A service account reaches a Sheet only if **(a)** the Sheets API is enabled on *its* cloud
project and **(b)** the file is explicitly shared with the service-account address. An analytics key
satisfies neither, and the failure is `403 PERMISSION_DENIED / SERVICE_DISABLED` naming a project
number — which reads as a quota or billing problem rather than "wrong credential entirely". Sharing
the file is also the wrong instinct: it widens access to client data for a one-off read.

**Fix:** the add-on already holds a working credential with exactly the right scope. It stores a
**refresh token** in the plugin's options and proxies renewal through the vendor's own endpoint (the
vendor holds the OAuth client secret, so there is nothing to configure locally). Instantiate the
action class, call its **`access_token_check()`** to force a refresh, then reach the Sheets service
object — typically a **private** property, so Reflection — and issue the read.

⚠️ **Two operational rules.** (1) Keep the entire read inside **one** `wp eval-file` so the token is
never interpolated into a shell command or printed — a token that reaches stdout reaches the
transcript and the scrollback. (2) Pin the **site's** PHP binary rather than the CLI default if they
differ; the bundled Google client is the vendor's, not yours, and is only tested against the version
the site runs.

**Generalises:** any plugin that maintains its own OAuth connection is a better credential source for
reading that third-party service than a fresh service account — it is already scoped, already
authorised on the specific resource, and already has a refresh path. Look for the plugin's own
token-check method before provisioning anything.

**First seen:** TAB, 2026-09-11.

### WS Form REST submit from the CLI returns a bare 403 unless it carries `wsf_post_mode=submit`
**Symptom / When:** Testing a form end to end with `curl -X POST …/wp-json/ws-form/v1/submit/ -F wsf_form_id=N -F field_<id>=…` returns `403` with an **empty body**: no JSON, no log line. It reads as a nonce or permissions problem, and isn't one (logged out, `security_nonce` off, no nonce is checked).
**Why:** `WS_Form_Submit::setup_from_post()` calls `return_forbidden()` (`header('HTTP/1.0 403 Forbidden'); exit;`) when `wsf_post_mode` isn't one of `submit|save|action`. WS Form's JS always sends it, so only a hand-built request hits this. The honeypot, limits and a bad `wsf_hash` take the same silent exit.
**Fix:** Add `-F wsf_post_mode=submit`. Choice fields post as arrays (`-F "field_77[]=Value"`). The JSON response carries `error_validation` and the per-field messages, so server-side `required` is testable from the shell. On Local, Mailpit's API (`http://localhost:<mailpit web port>/api/v1/messages`, port in `sites.json`) shows the recipients; Reply-To shows only in `/api/v1/message/<ID>/headers`, not the list. A tracing mu-plugin (`rest_pre_dispatch` plus a shutdown `http_response_code()` log) separated "never routed" from "routed, then exited".
**First seen:** pkjsupport, 2026-10-07 (WS Form Pro 1.12.12) — the first test submission of a support form.

### WS Form `wsf_pre_render` runs at render AND at submit — the hook for runtime options and a hidden recipient
**Symptom / When:** A select's options should come from somewhere WS Form can't read (an ACF options-page **repeater**: WS Form's ACF data source reads a field's *choices*, not repeater rows), or the email recipient should come from site options rather than be typed into the form.
**Why:** `wsf_pre_render` / `wsf_pre_render_<id>` is applied in `class-ws-form-public.php` (render) **and** in `WS_Form_Submit::db_form_object_read()` (submit, `class-ws-form-submit.php`), so rows injected there exist at both ends. The rendered page inlines the whole form definition, actions included, so anything injected at render is public.
**Fix:** One filter. Rows are always injected (find the field by a `class_field_wrapper` marker, not by ID, since IDs change on a rebuild). The recipient is injected only when `wp_is_serving_rest_request()`, i.e. while handling the submit, so it never reaches the page (verified: address absent from the page source, present in the delivered mail). ⚠️ **WS Form does not validate a select's posted value against its options:** `field_77[]=Made-up value` was accepted. Treat select values as untrusted text.
**First seen:** pkjsupport, 2026-10-07 (WS Form Pro 1.12.12) — a site dropdown fed by an options-page repeater, and the recipient read from site options.

## Fluent Forms

### Fluent Forms `fluentform_submission_success` never bubbles from the `<form>` — an `e.target` listener reads an empty form id
**Symptom / When:** A GTM→GA4 form-conversion pipeline is built, verified green in Tag Assistant / DebugView, and published — then weeks of real submissions produce **zero** analytics events while the entries sit in `wp_fluentform_submissions` the whole time. Nothing errors anywhere in the chain.
**Why:** FF's success dispatch in `form-submission.js` is threefold, and **none of the three bubbles from the form element**: (1) `$form.triggerHandler(...)` fires only handlers bound directly on the form — no bubbling, so a delegated document listener never sees it; (2) `jQuery(document.body).trigger(...)` reaches a document listener but `e.target` is **body**; (3) a native `CustomEvent` on `document`, where the target is **document**. A listener doing `$(e.target).closest('form').attr('data-form_id')` therefore always resolves to `""` — a GTM Lookup Table falls through to its empty default, a regex-gated trigger drops the event, and the tag never fires.
**The verification trap — this is the part that costs the weeks.** Console-testing with `$('form[data-form_id="3"]').trigger('fluentform_submission_success')` **passes**, because jQuery's `.trigger()` synthesises a bubbling path FF itself never takes. A green DebugView proves the tag works when fed a correct payload; it proves nothing about whether FF ever delivers one.
**Fix:** Read the form from the event **payload**, not the target. FF passes `{form, config, response}` as the CustomEvent `detail`. Native listener — no jQuery dependency, so it also survives delay-JS ordering:
```js
document.addEventListener('fluentform_submission_success', function (e) {
  var form = e.detail && e.detail.form;
  var id = (form && form.getAttribute && form.getAttribute('data-form_id')) ||
           (e.detail && e.detail.config && e.detail.config.id) || '';
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event: 'ff_submit', ff_form_id: String(id) });
});
```
Test the **real** dispatch path, never `.trigger()`:
```js
document.dispatchEvent(new CustomEvent('fluentform_submission_success',
  { detail: { form: document.querySelector('form[data-form_id="3"]') } }));
```
**The only sufficient proof is a ground-truth cross-check:** submission row count in `wp_fluentform_submissions` vs the analytics event count over the same window. Anything short of that can pass while the pipeline is dead.
**First seen:** JBM, 2026-07-15 — 5 real entries across 20 days, 0 analytics events; root cause read directly from the published GTM container source. The listener had been "verified" three weeks earlier by the `.trigger()` method above.

### Global autoload-captcha Turnstile silently breaks *payment* forms only — single-use tokens expire before a slow fill completes
**Symptom / When:** One Fluent Forms payment form throws **"Turnstile verification failed, please try again."** on submit while every other form on the site submits fine. Keys are valid; nothing appears in the error log, because rejection happens before an entry or log row is created.
**Why:** With `_fluentform_global_form_settings → misc.autoload_captcha` on and `captcha_type = turnstile`, FF injects a **page-load** Turnstile widget into *every* form and validates the token server-side on submit. Turnstile tokens are **single-use and expire after ~300s**. A payment form's fill time (card details + billing + the Stripe element) routinely outlives the page-load token → `timeout-or-duplicate` → failure. Short forms submit inside the window, so **only the payment form breaks** — which makes it look form-specific when the cause is a global setting.
**Rule out the keys first** — POST the secret plus a dummy token to `https://challenges.cloudflare.com/turnstile/v0/siteverify`: `invalid-input-response` means the secret is **good** and the problem is client-side; `invalid-input-secret` means the key is wrong.
**Fix:** Disable the CAPTCHA on payment forms only — Stripe's real-card requirement plus Radar already gate bots there, so it is redundant *and* is the thing blocking real payments. Use the per-form hooks; don't disable autoload globally, since the other forms want it:
```php
add_filter( 'fluentform/disable_captcha', function ( $disabled, $form, $type ) {
    return ( isset( $form->id ) && (int) $form->id === PAYMENT_FORM_ID ) ? true : $disabled;
}, 10, 3 );

// Strip the now-decorative widget too (priority > 10, so it runs after injection)
add_filter( 'fluentform/rendering_form', function ( $form ) {
    if ( ! isset( $form->id ) || (int) $form->id !== PAYMENT_FORM_ID ) return $form;
    $form->fields['fields'] = array_values( array_filter( $form->fields['fields'], function ( $f ) {
        return ! in_array( $f['element'] ?? '', array( 'turnstile', 'recaptcha', 'hcaptcha' ), true );
    } ) );
    return $form;
}, 20, 1 );
```
The form HTML is baked into cached pages, so after the render-side change purge **both** layers (page cache then CDN) or the widget-removed markup never serves.
**First seen:** JBM, 2026-05-27 — an invoice/payment form failing at submit for real customers while every other form on the site worked.


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


### SEOPress adds noindex/nofollow/redirection bulk actions to every CPT list — a scoped client role inherits them
**Symptom / When:** A locked-down client role (Business/Site Manager) on a SEOPress site, with SEOPress's metaboxes removed for the role. The CPT list screens still offer *Enable noindex*, *Enable nofollow* and *Enable/Disable redirection* in Bulk actions, and the media library gets an alt-text bulk action. One wrong pick de-indexes a batch of pages.
**Why:** SEOPress registers `bulk_actions-edit-{type}` and `bulk_actions-upload` filters **after default priority**, gated on nothing a content role lacks. Removing the `seopress_cpt` / `seopress_content_analysis` metaboxes removes only the metaboxes. A role's own `bulk_actions-*` filter at priority 10 runs *before* SEOPress re-adds its entries, so it looks written and does nothing.
**Fix:** Filter at `PHP_INT_MAX` for the role, per list screen:
```php
foreach ( array( 'edit-project', 'upload' ) as $screen ) {   // every CPT list the role sees, plus media
    add_filter( "bulk_actions-{$screen}", function ( $actions ) {
        return prefix_is_business_manager() ? array() : $actions;   // or unset only the seopress_* keys
    }, PHP_INT_MAX );
}
```
Verify by fetching the list screen **as the role** over HTTPS and grepping for `bulk-action-selector-top`. The `handle_bulk_actions-*` handler is still registered, so this removes the UI rather than building a hard wall. The same audit applies to any SEO plugin: check list screens, not just the editor.
**First seen:** WCDP, 2026-09-30 — Site Manager role; SEOPress bulk actions were still in the Events list after the metaboxes were gone.

### `bricks_template` is `capability_type => 'post'` — a client role with post caps reaches the template editor by URL
**Symptom / When:** A scoped client role keeps the post caps (because the site's CPTs map onto them — see the load-bearing blog caps entry). Bricks' own menu is admin-only and builder access is denied, so templates look unreachable.
**Why:** Bricks registers `bricks_template` with `show_in_menu => false` but the default `capability_type => 'post'`. So `edit.php?post_type=bricks_template`, `post-new.php?post_type=bricks_template` and `post.php?post=<template id>` all resolve for anyone with `edit_posts`: header, footer, every archive and single template, plus their titles, status and trash. `remove_menu_page()` on anything is cosmetic for the same reason.
**Fix:** An `admin_init` guard for the role that redirects those screens to the dashboard. Use the same list for any post type whose menu you hide (Posts, a switched-off CPT):
```php
add_action( 'admin_init', function () {
    if ( ! prefix_is_business_manager() || wp_doing_ajax() ) return;
    global $pagenow;
    $blocked = array( 'post', 'bricks_template' );
    $type = null;
    if ( in_array( $pagenow, array( 'edit.php', 'post-new.php' ), true ) ) {
        $type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : 'post';
    } elseif ( 'post.php' === $pagenow && isset( $_GET['post'] ) ) {
        $type = get_post_type( absint( $_GET['post'] ) );
    }
    if ( $type && in_array( $type, $blocked, true ) ) { wp_safe_redirect( admin_url() ); exit; }
} );
```
Verify as the role: `edit.php?post_type=bricks_template` should 302 to `/wp-admin/`.
**`admin_init` is right only for a role that holds the screen's capability.** For a role that lacks it (a `read`-only reviewer kept out of wp-admin entirely), `wp-admin/menu.php`'s `user_can_access_admin_page()` check `wp_die()`s a 403 on screens like `edit.php` *before* `admin_init` fires, so the redirect never runs. Hook `init` (priority ≤ 10) with an `is_admin()` guard instead, exempting `wp_doing_ajax()`, REST, `admin-post.php` and any screen the role needs (`profile.php` for a password change).
**The opposite surprise, building from `shop_manager`.** Reading `get_role( 'shop_manager' )->capabilities` as a custom role's base copies the caps but not two grants WooCommerce attaches to the literal `shop_manager` slug: `wc_shop_manager_has_capability` adds `edit_users`, and `wc_modify_editable_roles` allows `customer`. A cloned role can't edit customer accounts until you grant that explicitly, scoped to `customer` with `map_meta_cap`. For templates, add `map_meta_cap` → `do_not_allow` for edit, delete and publish on `bricks_template` alongside the URL guard. Verify with a `user_can()` loop on a throwaway user (include `\Bricks\Capabilities::current_user_can_use_builder()`), then over HTTP with minted cookies, then delete the user.
**First seen:** WCDP, 2026-09-30 — Site Manager role built from the Business Manager playbook. · **Extended:** THT, 2026-09-14 — a `read`-only reviewer role: `/wp-admin/` redirected, while `edit.php` showed core's 403. · **Extended:** MMHN, 2026-10-02 — a custom shop role built from Woo's `shop_manager` caps.


### `editable_roles` returning an empty array breaks Advanced Themer's ACF field — ACF warnings in admin for a restricted role
**Symptom / When:** `PHP Warning: Trying to access array offset on null in …/acf-field-functions.php on line 73/74` (plus `class-acf-field-clone.php:145` and WS Form's `class-ws-form-acf.php`), only for a restricted role, only on admin screens where WS Form builds its config (a custom submissions viewer calling `db_get_submit_fields()`, for one).
**Why:** Advanced Themer filters its "User Roles Permissions" field (`acf/load_field/key=field_6388e73289b6a`) through `get_editable_roles()` and does a bare `return;` when that list is empty, handing ACF `null`. WS Form's term data source (`config_meta_keys`, admin only) walks every ACF field group, so the null surfaces as warnings. A role guard that makes `editable_roles` return `array()` is the trigger.
**Fix:** Scope `editable_roles` to a non-empty list (`[ 'customer' ]`, Woo's own rule for shop managers) and block promotion with `map_meta_cap` (`promote_user` → `do_not_allow`) instead of relying on an empty list.
**Verify:** count the warning in the error log before and after an HTTP request to the screen as that role. To trace one like it, a temporary `set_error_handler` mu-plugin logging the `acf_get_field` argument finds the key.
**First seen:** MMHN, 2026-10-02 — a custom shop role plus a read-only form-submissions viewer.

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
1. **Specificity** — `[stack:acss]` ACSS ships `.clickable-parent:not(a) { position: static }` at `(0,1,1)`, which beats a plain `.card { position: relative }` at `(0,1,0)`. With no positioning context the stretched pseudo has nothing to stretch to.
2. **`clip-path` on the anchor** — a visually-hidden anchor using `clip-path: inset(50%)` clips its own pseudo-elements. The `::after` renders into an invisible box regardless of how it is positioned.
3. **`position: absolute` on the anchor** — makes the anchor its own containing block, so the absolute `::after` sizes against the 1×1 anchor rather than the card.
**Fix:** Prefer the visible-anchor pattern — wrap the primary content in a real `<a>`, keep secondary links as siblings of it. No pseudo-element, no utility, no specificity fight. Where `.clickable-parent` is genuinely wanted (card has no inner links), use the compound selector `.card.clickable-parent { position: relative }` to reach `(0,2,0)`, and keep both `clip-path` and `position: absolute` off the anchor. Convention and markup live in `01` (clickable card and focus patterns); this entry is the incident record behind that rule.
**First seen:** ext-mem video site build, 2026-04 — three separate stretched-link failures in one session. Harvested from `archive/claude-code-bricks-playbook.md` §5.3 at archive time, 2026-07-24.

### `<details>` content can't be force-shown on desktop (Chrome `::details-content` content-visibility)
**Symptom / When:** A `<details>`/`<summary>` used as a responsive menu (collapsed on mobile, "always open" on desktop via CSS) renders the content HIDDEN on desktop — the CSS override to keep it open has no effect, so a desktop sidebar collapses to nothing.
**Why:** Recent Chrome hides closed-`<details>` content via a `::details-content` pseudo with `content-visibility:hidden`, not a simple `display:none` on the child. Overriding the child's `display` doesn't un-hide it, and `::details-content` support is too new/uneven to rely on.
**Fix:** For a disclosure that must be open at one breakpoint and collapsible at another, use a checkbox-controlled pattern (`input + label + list`, `:checked ~ list{display}`) or a `<button aria-expanded>` + JS toggling a class — both give full author control of `display`. Reserve `<details>` for collapse-everywhere cases.
**First seen:** VMG, 2026-06-07 — an account-nav disclosure; the desktop sidebar collapsed to nothing until it was switched to a checkbox toggle.

### A `*/` inside a CSS comment silently eats the NEXT rule
**Symptom / When:** A newly added CSS rule is delivered in the stylesheet but never applies. The browser's parsed `cssRules` for that sheet is missing exactly that rule while its neighbours survive, and the file curls back byte-perfect.
**Why:** CSS comments don't nest and have no escaping: the first `*/` anywhere ends the comment. A comment containing a glob-ish token such as `brxe-*/list--none` terminates at `brxe-*/`. The rest of the comment text becomes invalid CSS, and the parser error-recovers by discarding everything up to the next `}`, which is the first real rule after the comment. Nothing reports an error. BEM wildcards in comments (`brxe-*`, `card__*`) make this likely to recur.
**Fix:** Never write a bare `*/` inside a CSS comment. Spell it out (`brxe- or list--none`) or space it (`* /`).
**Verify:** compare the sheet's parsed rules against the file:
```js
[...document.styleSheets].find(s => s.href?.includes('style.css')).cssRules.length
[...sheet.cssRules].map(r => r.selectorText)
```
A delivered-but-unparsed rule means a parse error upstream, and the comment immediately before it is the first suspect.
**First seen:** WCDP, 2026-08-22 — a sitewide list-marker rollout rendered custom markers alongside the browser discs they were meant to replace. The `list-style: none` rule was the one being swallowed.

### CSS custom properties do not substitute inside `url("data:image/svg+xml,…")`
**Symptom / When:** Tokenizing SVG icon colors fails. The data-URI icon renders an unparseable literal `var(...)`, or keeps its old hex after every other token in the rule has updated.
**Why:** A URL value is opaque to CSS, and no variable substitution happens inside it. Hex baked into data-URI SVG markup is static.
**Fix:** Three options, by trade-off.
1. **Hardcode the hex** in the data URI. Fastest, but it has to be re-edited with every palette change.
2. **`mask-image` + `background-color`.** The SVG becomes a stencil, and `background-color`, which does take `var()`, supplies the color:
   ```css
   .icon {
       background-color: var(--accent);
       -webkit-mask: url("data:image/svg+xml,…") center / 18px 18px no-repeat;
               mask: url("data:image/svg+xml,…") center / 18px 18px no-repeat;
   }
   ```
   If the element already uses `background-color` for a fill (a chevron on a colored `<select>`), move the icon to a `::after`.
3. **Inline `<svg>` with `stroke="currentColor"`**, inheriting `color`. This is the cleanest token-driven route, but the icon must live in the markup, which usually means a template change.
**First seen:** THM (inherited from its clone source), 2026-08-09.

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
**Second route — a real-HTTPS probe, when simulation isn't enough.** Setting `$_SERVER['HTTPS']` is the right tool for introspecting one object, but it only fakes the single var you set. When the code path depends on the genuine request environment — the full `$_SERVER`, proxy headers, `FORCE_SSL_*`, `wp_redirect()` canonicalisation, redirect-loop guards — run the check in real PHP-FPM context instead:
```bash
cat > <WEBROOT>/wp-content/uploads/_diag.php <<'PHP'
<?php
require __DIR__ . '/../../wp-load.php';
header('Content-Type: text/plain');
echo 'is_ssl: ' . var_export( is_ssl(), true ) . "\n";
PHP
curl -s https://example.com/wp-content/uploads/_diag.php; rm <WEBROOT>/wp-content/uploads/_diag.php
```
**Delete the probe in the same command.** A wp-load-bootstrapping PHP file under `uploads/` is web-accessible and is a live exposure for as long as it exists — never leave one behind "just for now".
**Scope note:** this is not only a gateway problem. Anything gated on `is_ssl()` reports a false negative under WP-CLI; gateways are just where it is loudest because the symptom looks like a broken payment config.
**First seen:** VMG, 2026-06-07 — after a live gateway flip, `is_available()` and the Payment Methods nav both read as missing in `wp eval`; both flipped to present once HTTPS was simulated. (The origin terminated real HTTPS, so `is_ssl()` was genuinely true on real requests.) Probe route: MBC, 2026-05-22, during payment-gateway verification after an HPOS migration.


### Local: `wp db query` fails on the mysql socket; `wp eval`/`wp option get` work
**Symptom / When:** `wp db query "…"` errors `Can't connect to local MySQL server through socket '/tmp/mysql.sock'`, while `wp option get`, `wp eval`, `wp post list` in the same shell work.
**Why:** `wp db query` shells out to the `mysql` client, which needs the live socket path; Local's bundled MySQL uses its own socket and is only up while the site runs (and not at `/tmp/mysql.sock`). PHP-based WP-CLI commands connect via `DB_HOST`/mysqli and are unaffected. (A fully stopped Local site fails all DB access — no `mysqld`.)
**Fix:** Use PHP-path WP-CLI (`wp eval`, `wp eval-file`, `wp option get/update`, `wp post *`) for DB work on Local; avoid `wp db query`/`wp db cli`, and use `$wpdb` inside `wp eval` for SQL. If everything DB-related fails, start the Local site first.
**No flag rescues it.** Passing Local's real socket (`--socket=…/Local/run/<siteId>/mysql/mysqld.sock`) fails with the identical `/tmp/mysql.sock` error: WP-CLI runs a SQL-modes preflight query before yours, and the preflight ignores pass-through flags. `wp db export` does work on the same box, so the failure is specific to `query` / `cli`.
**First seen:** MMHN, 2026-07-16. · **Extended:** pkjsupport, 2026-10-04 — `query` and `query --socket` both exit 1, `wp db export` exits 0.

### `wp media import` of SVG fails as CLI user 0 — sideload as an admin user
**Symptom / When:** `wp media import icon.svg` errors "Sorry, you are not allowed to upload this file type" even though SVG uploads work fine in wp-admin and Bricks' SVG support is enabled.
**Why:** Bricks registers the SVG mime via an `upload_mimes` filter gated by `current_user_can_upload_svg()`. WP-CLI runs as user 0, the capability check fails, and the mime never registers — same silent-capability family as the `_bricks_page_*_2` write gate.
**Fix:** Sideload inside `wp eval` with the user set first:
```php
wp_set_current_user( 1 );
require_once ABSPATH . 'wp-admin/includes/{image,file,media}.php';
$aid = media_handle_sideload( [ 'name' => 'icon.svg', 'tmp_name' => $tmp ], 0, 'icon' );
```
Bricks' sanitizer still runs on the upload. To add the icon to a custom set, don't hand-append to the options. Use `bin/bricks-icon-import.php`. Why the set is per-install state is in "A Bricks custom icon set cannot ship in a plugin…", and the option shapes are in `02`.
**First seen:** Highland, 2026-07-11 — adding a Remix `star-line` icon to the MPD set for the Google-review CTA.


### `wp eval-file` dies silently (exit 0, zero output) on some script files — run via `wp eval 'include ...'`
**Symptom / When:** `wp eval-file build-x.php` returns exit 0 with no output at all — not even a first-line `WP_CLI::log()` — while `php -l` passes and small probe files in the same directory run fine. Nothing lands in the DB.
**Why:** RunCloud's bundled wp-cli phar `EvalFile_Command` strips the opening tag and `eval()`s the source. Some file contents trip a silent bailout in that eval path (mechanism unconfirmed — the failing file had a large HTML nowdoc with UTF-8 en dashes and a ~300-line element-tree array; a sibling script of similar size/shape ran fine). Truncated versions of the same file DO report parse errors, so the eval path can error loudly — this failure mode is specifically silent.
**Fix:** Run the identical file as `wp eval 'include "/path/to/build-x.php";'` — `include` compiles it as a normal PHP file and it executes correctly. Cheap habit: if an eval-file script produces no output where output is expected, don't debug the script first — rerun via include.
**Watch for (inside the escape hatch):** `include` still runs the file inside a function, so its top-level variables are not globals. See the next entry.
**And an uncaught `throw` dies the same way.** Inside `wp eval` or `wp eval 'include …'`, an uncaught exception prints nothing, exits 0 and silently skips the rest of the script, while everything written before it has landed. Don't `throw` for guard failures in a build script. Use `echo "ABORT: …"; return;` (a `return` ends the include visibly), end every script with a sentinel echo whose absence counts as failure, and make writes skip-if-present so a re-run is safe.
**First seen:** Highland, 2026-07-11 — the Request-an-Estimate page build script; identical file ran perfectly via include on the first try. · **Extended:** THT, 2026-09-06 — an id-length guard threw on a 5-char element id. Global classes written earlier in the script landed, the template write didn't, and only the missing progress line gave it away.

### `wp eval-file` and the `wp eval 'include …'` workaround do NOT run the file in global scope — `global $var` sees nothing
**Symptom / When:** A script defines `$cls` at the top and a helper function does `global $cls;`. The function sees an empty variable and the script fails on the first lookup, even though the assignment is plainly at the top of the same file. The quieter face: helpers that fill a shared array through `global $E` write to the real global while the top-level code reads an empty local. Every check passes, `php -l` is clean, and the script "succeeds" with nothing written, or every element a helper builds lands with a setting `null`.
**Why:** WP-CLI's eval-file runs the file body inside a function frame, and so does the `wp eval 'include …'` escape hatch from the entry above (the include happens inside `eval()`, inside a method). So the file's top-level variables are function-locals, not globals. `global` therefore binds to a genuinely empty global of the same name. Nothing about the file *looks* wrong.
**Fix:** Use `$GLOBALS` explicitly on both sides when a script needs shared state across its own functions:
```php
$GLOBALS['my_cls'] = [ /* … */ ];
function k( $n ) { return $GLOBALS['my_cls'][ $n ]; }
```
Or declare `global $X;` at the top of the file before assigning, so both sides bind the real global, or pass state as arguments and avoid the shared-global pattern entirely. Either way, keep read-back counts and a non-empty assertion on shared settings in the success output: `written: 0 elements` catches this, a bare "WRITE OK" does not.
**First seen:** WCDP, 2026-08-10 — building a footer element tree, where a class-name→id map was shared with an element-builder helper. · WCDP, 2026-08-19 — an /about build script run via `wp eval 'include …'` wrote a 0-element tree with exit 0 and every integrity check green; the readback count in the log exposed it. · WCDP, 2026-09-13 — five `icon` elements built by a helper that read a file-level `$ICON` through `global` all shipped `icon: null`; the builder showed them as "no icon selected".

### WP-CLI in the wrong webapp: a cross-site class-id collision silently edits a sibling install
**Symptom / When:** A keyed edit to a Bricks global class ("find id X, set one key") reports success, but the change never appears on the target site, and a DIFFERENT site on the same box changes instead. The underlying cause: the shell's working directory persists across tool calls, so a `wp` command that follows a screenshot or utility command runs against whatever webapp was last `cd`-ed into.
**Why:** Two Bricks installs on one box can genuinely share a 6-char class id. Both WCDP and MMHN abbreviated their About-hero lead class to `abhled` (`about-hero__lead` vs `about-hero__lede`). An id-keyed edit that lands on the wrong install therefore finds a real class and overwrites it. "The id can't exist elsewhere" feels safe; it isn't.
**Fix:** Three rules for every WP-CLI write:
1. `cd <webapp-root> &&` INSIDE the same command string as the `wp` call (or pass `--path=`). Never trust inherited cwd. **`--path=` is a server answer only.** On LocalWP every site shares identical `wp-config.php` credentials (`local` / `root` / `localhost`) and is told apart purely by a per-site MySQL socket, which PHP takes from the Site Shell's own `php.ini` (`mysqli.default_socket`). `--path=<other site>` therefore bootstraps the other site's code and then reads and writes the **shell's** database, with no error. On Local, use that site's own Site Shell, or invoke PHP with `-d mysqli.default_socket=<sibling socket>`. Either way, prove the target with `wp option get siteurl` first. (Distinct from "Local: `wp db query` fails on the mysql socket", which fails loudly. This one succeeds wrongly.)
2. Key class edits on **id AND name**, so a collision fails closed:
   ```php
   if ( $c['id'] === 'abhled' && $c['name'] === 'about-hero__lead' ) { /* edit */ }
   ```
3. Make the first line of every write script a site assertion:
   ```php
   if ( 'https://expected.host' !== untrailingslashit( get_option( 'siteurl' ) ) ) { WP_CLI::error( 'wrong site' ); }
   ```
An element-count or `is_array()` guard at the top of a write script also works and is not paperwork: it turns a wrong-site run into a harmless error. The siteurl assertion is the strongest form, because it fails on the first line regardless of what the tree contains. Keep build scripts: the recovery below came from one.
**First seen:** WCDP/MMHN, 2026-08-22 — an /about hero-lead color edit landed `color: white` on MMHN's lede, which sits on cream, making it near-invisible on a client-viewable staging site for ~15 minutes. Restored from MMHN's own class-creation script. An earlier wrong-site run the same day was stopped by an `is_array()` guard; a third, later that day, was aborted by the script's element-count assertion before any write. · **Extended:** THM, 2026-08-09 — on Local, `wp option get siteurl --path=<sibling>` answered with the shell's own site. It was caught on a read, before any write.

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

### Vendor-updater products are invisible to WP-CLI — `wp plugin/theme list` prints `none` whether or not an update exists
**Symptom / When:** You audit versions from the CLI, every premium product reports `none`, and you report the site current. It may not be. Confirmed for **Bricks** (theme) and **HappyFiles Pro**; assume it for any product with its own updater.
**Why:** These register their update check on `pre_set_site_transient_update_{plugins,themes}` from **admin-only** code paths (`bricks/includes/license.php`, `happyfiles-pro/includes/pro/settings.php`), so a WP-CLI run never contacts the vendor. WP-CLI prints `none` for anything **absent from the update transient** — which is indistinguishable from "checked, and current". Forcing a refresh with `delete_site_transient('update_plugins')` + `wp_update_plugins()` does not help; the hook still never fires outside admin.
**Not a licensing fault.** HappyFiles read `License active` at the same moment it failed to report. Don't go hunting for an expired key.
**Fix:** Confirm those products at the vendor dashboard. Before claiming any site is fully current, list what never reported — anything absent from **both** arms of the transient is unverified:
```bash
wp eval '$t=get_site_transient("update_plugins"); $all=array_keys(get_plugins());
$known=array_merge(array_keys((array)($t->response??[])),array_keys((array)($t->no_update??[])));
foreach(array_diff($all,$known) as $m) echo "NOT REPORTING: $m\n";'
```
In-house plugins and mu-plugins legitimately appear (they have no updater). Everything else in that list is a gap. Note `wp plugin list` also reports `version higher than expected` for products whose installed build is ahead of the update API's record — that is normal for some premium plugins, not a fault.
**First seen:** MBC, 2026-08-25 — a version audit surfaced three pending updates and implied the rest were current; the theme and one premium plugin were in neither arm of the transient and had simply never been asked.


### An emergency plugin deactivation outlives the fix, and edge-cached HTML hides the outage
**Symptom / When:** A plugin update fatals every request (`Class "…" not found`, `vendor/autoload.php` missing). Deactivating from `plugins.php` recovers the site, the files are replaced, and everything **looks** fine afterwards — because nothing forces the reactivation. Meanwhile the plugin is still off: for an SEO plugin that means titles falling back to `Site – Tagline`, meta descriptions gone, redirects dead, sitemap 404ing at origin.
**Why:** Two independent failures compound. The update replaced the plugin files incompletely, and emergency deactivation — the correct immediate move — has no follow-up gate. Then the page cache and CDN keep serving pre-outage HTML for the rest of the TTL, so a browser visit or a plain `curl` shows **correct** titles from the edge while origin serves fallbacks. The surface check confirms the wrong thing.
**Fix:** Reinstall the files, `wp plugin activate`, then verify with a **cache-busted** request (`curl "https://<site>/?cb=$(date +%s)"`) that titles, meta, redirects and sitemap are actually back, and purge page cache → CDN so the outage-window HTML doesn't sit at the edge.
**Standing hygiene after any update session:** run `wp plugin list` and diff the `status` column against the expected active set. Any session that touched `plugins.php` can leave a plugin deactivated, and the site will look healthy from outside for hours.
**First seen:** JBM, 2026-07-15 — an SEO plugin update fataled, was deactivated, the files were re-replaced eleven minutes later, and the reactivation was missed. Caught ~45 minutes on by a cache-busted title check; settings and all 34 stored redirects had survived the deactivation.


### `wp post term remove` takes a slug/name, not a term_id — and reports success either way

**Symptom / When:** `wp post term remove 14951 category 14` prints **`Success: Removed term.`** and the term is still attached. A PHP warning appears (`Trying to access array offset on value of type null` in `wp-includes/taxonomy.php`).
**Why:** The command resolves terms by **slug/name** by default. `14` is looked up as a term *named* "14", which does not exist → nothing is removed → the command still reports success.
**Fix:** Pass the slug (`wp post term remove 14951 category outdoor-living`) or add `--by=id`. **Verify the term list afterward — "Success" is not evidence.**
**First seen:** TAB, 2026-07-15 — trimming a post to one category; the false success was caught only by a follow-up count.

### `wp plugin list` reports "version higher than expected" for premium plugins with no wp.org slug — it is a permanent false positive, not a signal

**Symptom / When:** The `update` column on a commercial plugin reads `version higher than expected` and never clears, however many times you refresh update transients. It looks like a corrupted install or a downgrade.
**Why:** WP-CLI resolves the update column against the **wp.org repository by directory slug**. A premium plugin distributed outside wp.org has no matching entry, so the comparison is meaningless and WP-CLI reports the installed version as "higher" than the nothing it found. It is not evidence of anything.
**Fix / rule:** Confirm and then ignore it permanently — `curl -s -o /dev/null -w '%{http_code}' https://api.wordpress.org/plugins/info/1.0/<slug>.json` returning **404** proves the slug is unclaimed. Also check the plugin header for an `Update URI:` line: core honours it and will **refuse** any wp.org plugin claiming that slug, which closes the one real risk here (a squatter publishing under the same slug and being auto-installed over your paid plugin). A premium plugin with **no** `Update URI` and a slug that **does** exist on wp.org is the genuinely dangerous combination — that one is worth acting on.
**First seen:** TAB, 2026-09-11. `duplicator-pro` 4.6.7 flagged on every `wp plugin list`. `api.wordpress.org` → 404 (`{"error":"Plugin not found."}`), and the header declares `Update URI: https://duplicator.com/`. Benign on both counts; logged so it stops being re-investigated at each audit.

### `get_page_by_path()` as an import existence-check silently matches ATTACHMENT slugs — imports skip real posts
**Symptom / When:** An idempotent import loop using `get_page_by_path( $slug, OBJECT, '<post_type>' )` to skip already-imported posts skips one that was never imported. The count comes up one short, with no error; the skip counter absorbs it.
**Why:** With a single post type, `get_page_by_path()` queries `post_type IN ( '<post_type>', 'attachment' )`; attachments always ride along. Any media file whose attachment slug equals a post slug (near-guaranteed when featured images are named after their posts) makes the check truthy for a post that doesn't exist.
**Fix:** Check with an exact query: `get_posts( [ 'post_type' => '<type>', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] )`. Then verify final counts against the source corpus (a source-slugs minus staged-slugs diff), never the script's own created/skipped tally. The same collision breaks legacy redirects; see "Legacy root-slug redirect silently skipped".
**First seen:** MMHN, 2026-07-18 — a recipe import came up 86 of 87. The missing post was shadowed by the featured image of a near-identically slugged sibling, the exact pair the IA doc had warned never to merge.

## Analytics & search measurement

> **Scope note (2026-09-11).** Added as a deliberate expansion, recorded in `00`. The knowledgebase's
> line is that ongoing maintenance of a delivered site is the project's business, not portable stack
> knowledge — but *measurement* is not maintenance. Search Console and GA4 behave the same way on
> every property, their traps are counter-intuitive and expensive, and we will pay for them again on
> the next engagement that reports a number to a client. Entries here are about the **instruments**,
> not about any site's performance.

### GSC's query / page / query×page views disagree — the query view silently drops ~37% of clicks

**Symptom / When:** You compute anything from Search Console's **Queries** view — a brand/non-brand
split, a long-tail analysis, a "top terms" report — and treat it as the site's search performance.
It is a **censored sample**, and nothing in the UI or the API says so.

**Measured on TAB, same property, same 28-day window (2026-07-24 → 08-21):**

| Aggregation | Clicks | Impressions |
|---|---:|---:|
| **Page** (`gsc-pages`) | **56** | **4,206** |
| Query × page | 36 | 2,925 |
| Query (`gsc-queries`) | 35 | 2,468 |

The query dimension sees **~63% of clicks and ~59% of impressions**. ⚠️ **Cause:** Google withholds
rare/anonymised queries entirely to protect user privacy — they are not aggregated into an "other"
bucket, they are simply absent. **The withheld tail is disproportionately long-tail, which on any
local-services site is disproportionately non-brand** — i.e. the censoring bites hardest on exactly
the segment you are usually trying to measure.

**The bind, and there is no way out of it:** only the **page** view gives a true click total, and
only the **query** view can split brand from non-brand. You cannot have both. So any
"non-brand clicks" figure is an **estimate from a censored sample, not a count.**

**Fix:** don't try to reconcile the views — they will never agree. Instead (1) pull **both** every
time, so the gap is visible rather than discovered later; (2) define the metric **operationally**
("as reported in GSC's Queries view, 28 days, brand = <explicit rule>") so it is at least
*reproducible*; and (3) if it is going in front of a client as a target, **disclose the censoring in
the document.** A metric two people cannot recompute identically is not a metric. ⚠️ On TAB this had
gone unnoticed across two baselines because the regeneration recipe pulled queries and **never
pulled pages at all** — if your recipe only has one of the two, that is the bug.

### GA4 silently restates closed historical windows — never put a decimal on a GA4 share

**Symptom / When:** A figure recorded weeks ago from GA4 will not reproduce today, and two documents
written days apart disagree about a window that closed long before either was written. It reads like
someone made an arithmetic error. Nobody did.

**How TAB's happened:** two docs recorded the same fortnight as **75/266 = 28.2%** and
**75/265 = 28.3%**, and a session was booked to "re-pull and settle it." The re-pull settled it in
the opposite direction — **neither reproduces.** The numerator 75 now appears on exactly one
candidate window and the denominator on **none**; it reads **263**. GA4 had restated it downward by
2–3 sessions. The original figure was *right when taken*.

⚠️ **Two distinct traps here, and they compound.** (1) **Restatement** — GA4 reprocesses (bot
filtering, session stitching, consent modelling) well after a window closes. (2) **Window
ambiguity** — "06-30 → 07-14" is read four different ways by four different tools, and on a small
site a one-day boundary shift moves the share by half a point. Together they make a two-decimal
figure unfalsifiable.

**Fix:** **round to the precision the data can actually support** — "about 28%" is true on every
candidate window and immune to the next restatement. Never carry a GA4 percentage to one decimal
into a client document, and never spend a session reconciling one. **Bigger consequence: do not put
a GA4-derived number in a contract.** It will not hold still for twelve months. If a metric has to
be contractual, source it from GSC, which does not retroactively restate.

**Technique, if you must isolate a historical window and your puller only takes `days`:** GA4
sessions are **additive across disjoint date ranges**, so `range(A→C) − range(B→C) = range(A→B)`.
Two pulls reconstruct any closed window without adding date-range support. Cheap and exact — and it
is also how you *detect* restatement, by rebuilding a window you already have a recorded figure for.

### A GSC "average position" is a window average — so a length-matched pair of windows is the only honest before/after
**Symptom / When:** You ship a change, pull 28 days, and compare it to the baseline 28 days. The
number moves and you attribute it to the change. Two distinct errors are usually baked in.

**(1) The lag.** Search Console data is **~3 days behind**, so "the last 28 days" silently means a
window ending three days ago. Immediately after a change, the available clean post-change window is
*shorter than the pre-change one* — and comparing an 18-day post window against a 28-day baseline is
not a delta, it is two different measurements. **Match the lengths**, even when that means throwing
away baseline days you already have.

**(2) The average hides the event.** Position is averaged across the window, so a window that
**straddles** the change reports a blend of both states. A 28-day window containing a change on day
10 will understate it; the same query can read 5.1 on a post-change window and 9.2 on a window that
merely *includes* the pre-change days — with no contradiction and no error.

**Fix:** define both windows explicitly, end them both before the lag boundary, make them equal
length, and **start the post window the day AFTER the change** (exclude the ship day itself — it is
partially both states). Then say which windows you used in the write-up, because the next person will
otherwise compare your figure against a different pair and find a "discrepancy" that is only arithmetic.

⚠️ **Corollary worth its own line: a GSC position and a live SERP check can disagree and both be
right.** The live check is one searcher, one place, one moment; the GSC figure is an average over
everyone who saw the result. Query-level positions are also contaminated by non-blue-link surfaces
(local pack, AI Overview citations), so a strong "position" may not be a listing at all. When they
conflict, neither is the error — **they are measuring different things**, and the reconciliation is
the finding.

**First seen:** TAB, 2026-09-11.

### `esc_js()` on a URL query string inside a `<script>` breaks it: `&` becomes `&amp;`, so a GTM environment never applies
**Symptom / When:** A GTM environment (Staging, Preview) is configured with `gtm_auth` / `gtm_preview`, the page source shows them in the loader, and the site still serves the **Live** container version. Staging traffic then reaches the client's real GA4 property through Live's tags.
**Why:** `esc_js()` encodes `&` as `&amp;`. That's right for an inline `onclick` attribute, but inside a `<script>` element the string is literal, so the URL becomes `…gtm.js?id=GTM-X&amp;gtm_auth=…` and the parameter is named `amp;gtm_auth`. GTM ignores unknown parameters and falls back to Live. Nothing errors, and the dataLayer still works, so event-level testing passes.
**Fix:** Don't run URL fragments through `esc_js()`. Build them from `rawurlencode()`d parts and guard with a character whitelist (`preg_replace( '/[^A-Za-z0-9_.%&=-]/', '', $s )`), or emit with `wp_json_encode()`. `esc_attr()` in the `<noscript>` iframe `src` is fine, because browsers decode entities in attributes.
**Verify:** the page source shows `&gtm_preview=env-N`, not `&amp;`, in the loader; and on the network, the environment's version loads no `gtag/js` / `analytics.js` when its tags are paused.
**First seen:** MMHN, 2026-10-01 — found during performance profiling, when more than half the page's JS on staging turned out to be Live-container Google scripts. Present for almost four weeks.

## Diagnostic patterns

**First move when a rendered page is wrong: `curl` it.** The raw server response has no JS, browser cache or CSS applied, so one request partitions the problem. Correct HTML means the fault is in JS, CSS or a cache layer, and PHP is exonerated. Wrong HTML means it's server-side, and the browser is irrelevant. Make the check print something either way (`00`, evidence discipline). (THM, inherited from its clone source, 2026-08-09.)

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
**The other half of the same blind spot — a section merely *styled* for the thing you are removing.** Even a whole-blob string sweep misses it, because `_cssGlobalClasses` stores six-character class **IDs** (`nddpgh`), never readable names (see "Bricks `_cssGlobalClasses` must reference class IDs, not names"). A section built as `reservations__*` contains the concept nowhere as text. Resolve names to IDs first, then search the IDs — and check `bricks_global_classes_trash` too, since trashed classes stay in the option and can still be referenced by live content:
```bash
wp eval 'foreach (["bricks_global_classes","bricks_global_classes_trash"] as $o) {
  foreach ((array) get_option($o) as $cl)
    if (stripos($cl["name"] ?? "", "TERM") !== false) echo $o.": ".$cl["name"]." = ".$cl["id"]."\n"; }'
wp db query "SELECT post_id, meta_key FROM wp_postmeta
  WHERE meta_key LIKE '_bricks_page%' AND (meta_value LIKE '%ID1%' OR meta_value LIKE '%ID2%');"
```
**First seen:** Highland, 2026-07-22 — removing a township name from the site for client location privacy. The text sweep reported 1/1 hits on all four pages and passed; two pages still carried the name inside map-canvas `aria-label` attributes. A whole-blob assertion caught it; a per-key one would have shipped it. Class-ID half: MBC, 2026-08-25 — a policy-change copy sweep returned two pages and looked complete while a live `reservations__*` class block existed that it structurally could not have found.

### Testing as a logged-in user from the CLI — mint auth cookies with `wp_generate_auth_cookie`
**Symptom / When:** A bug only reproduces logged-in (admin bar, maintenance-mode bypass, an admin screen, user-specific rendering), but `curl` is anonymous and there is no browser on the box. **This catalog already tells you to "verify with an auth-cookie curl" in several places — this is the recipe those entries assume.**
**Why:** WP auth is two cookies (`LOGGED_IN_COOKIE`, plus `SECURE_AUTH_COOKIE` over HTTPS) whose values `wp_generate_auth_cookie()` will mint for any user id and expiry. Send **both**. The front end accepts `logged_in` alone, but wp-admin bounces to the login screen without the auth cookie, and that bounce can be mistaken for your own role redirect firing. A session token from `WP_Session_Tokens` makes the cookie revocable the moment you are done.
**Fix:** Short expiry, never echo the value, destroy the token and shred the file afterwards:
```bash
umask 077
wp eval '$u=1; $exp=time()+300; $tok=WP_Session_Tokens::get_instance($u)->create($exp);
file_put_contents("/tmp/.c", LOGGED_IN_COOKIE."=".wp_generate_auth_cookie($u,$exp,"logged_in",$tok).";"
  .(defined("SECURE_AUTH_COOKIE")?SECURE_AUTH_COOKIE:"wordpress_sec")."=".wp_generate_auth_cookie($u,$exp,"secure_auth",$tok));
file_put_contents("/tmp/.t",$tok);'

curl -s -o /tmp/.o -b "$(cat /tmp/.c)" -w '%{http_code}\n' "https://example.com/wp-admin/admin.php?page=<slug>"

wp eval '$t=trim(file_get_contents("/tmp/.t")); WP_Session_Tokens::get_instance(1)->destroy($t);'
shred -u /tmp/.c /tmp/.t /tmp/.o
```
**Two traps.** Send a browser-like `-A` user-agent if the site runs a bot block, or the request may be filtered. And **follow or inspect redirects** — a bare `curl` without `-L` on an admin URL returns an empty body with no error, which reads exactly like a fatal.
**Store pages and carts.** The minted `logged_in` cookie is enough for the front end and for WooCommerce's coming-soon gate. In Playwright, pass it with `context.addCookies([{ name, value, domain, path: '/', httpOnly: true, secure: true }])`. To seed a Blocks cart for a screenshot, drive `?add-to-cart=<id>&quantity=<n>` navigations in the same context (Store API hydration needs JS, so curl sees an empty cart). ⚠️ **An add-to-cart test through a minted cookie writes to that real user's cart:** the wc session row and `_woocommerce_persistent_cart_<blog id>` user meta survive logins. Clean up with `DELETE FROM {$wpdb->prefix}woocommerce_sessions WHERE session_key='<uid>'` plus `delete_user_meta( <uid>, '_woocommerce_persistent_cart_' . get_current_blog_id() )`, knowing that empties anything the person really had in it. Mint for a throwaway user where you can.
**First seen:** MBC, 2026-08-07 — an admin-bar layout bug needed logged-in HTML to diff against anonymous. Reused throughout 2026-08-25 to verify admin screens after an ACF migration. · **Extended:** MMHN, 2026-07-18 / 2026-07-19 — verifying gated cart, checkout and account surfaces; a notice-flow test landed items in the real admin user's cart.

### Firing admin hooks under `wp eval` gives false negatives — verify admin screens over real HTTP
**Symptom / When:** You want to confirm an admin page or menu registered, so you fire its hook from the CLI — `wp eval 'do_action("admin_menu"); …'` — and it reports the menu entry missing. The page is fine.
**Why:** WP-CLI is not an admin request. The admin globals (`$menu`, `$submenu`, screen context) are never initialised, so hooks expecting them emit warnings from `wp-admin/includes/plugin.php` and populate nothing. **The result is indistinguishable from a genuine registration failure**, which is the dangerous part: it invites you to "fix" working code, or to record a real success as a failure.
**Fix:** Split the question. Use CLI for **registration state** — `acf_get_options_pages()`, `acf_get_field_groups()`, `has_action()` — and a real authenticated request (recipe above) for **rendering**. Assert on content, not just status:
```bash
grep -c "My Tab Label" /tmp/.o           # 0 = genuinely absent
grep -ciE "fatal error|critical error" /tmp/.o
```
They answer different questions, and only the second is evidence the screen works.
**When you do need a CLI simulation, load what an admin request loads.** WP-CLI never requires `wp-admin/includes/*` or the admin-bar files, so `wp_add_dashboard_widget()` or `WP_Admin_Bar` is undefined (a fatal), or worse, a missing dependency makes something report "nothing registered". Require the files and set the preconditions first:
```php
require_once ABSPATH . 'wp-admin/includes/dashboard.php';    // wp_add_dashboard_widget(), wp_dashboard_setup()
require_once ABSPATH . 'wp-admin/includes/template.php';
require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';  // WP_Admin_Bar
require_once ABSPATH . 'wp-includes/admin-bar.php';           // wp_admin_bar_* node callbacks
wp_set_current_user( 1 ); set_current_screen( 'dashboard' );
```
`WP_Admin_Bar::add_menus()` only *registers* the `admin_bar_menu` callbacks. Nodes exist only after a separate `do_action( 'admin_bar_menu', $bar )`, so checking right after `add_menus()` reports real nodes as missing.
**First seen:** MBC, 2026-08-25 — after moving an options page to `acf_add_options_page()`, the CLI check reported zero menu entries; an authenticated request returned 200 with every tab rendering. Trusting the CLI would have meant rolling back a correct migration. · **Extended:** THM, 2026-08-29 — verifying a role-scoped dashboard-widget wipe and an Admin Bar logo swap. Both fataled on the missing requires, and both reproduced the real request once the files were loaded.


---

### Verify link presence against BOTH absolute and relative hrefs — Bricks emits relative

**Symptom / When:** A link-presence check (`grep 'href="https://site.tld/path/"'`) reports a required link **missing** from a page that plainly has it.
**Why:** Bricks templates/components emit **relative** hrefs (`/request-a-quote/`) when the link is built from a dynamic tag or site setting, while inline editorial links written into `post_content` are usually **absolute**. A verifier matching one form silently misses the other — and a false negative on a checklist item costs more time than the check saved.
**Fix:**
```bash
grep -oE 'href="(https://site\.tld)?/path/"' page.html | wc -l
```
**Related:** rendered HTML is frequently **one long line**, so `grep -c` returns `1` for "present at all" and `0`/`1` never means occurrences. Use `grep -o … | wc -l`, or parse. Three false readings in one session traced to this.
**First seen:** TAB, 2026-07-15 — a required CTA link read as absent on the Medina page; the template's Dark CTA had it relative.

### Headless Chrome clamps the layout viewport to a ~500px minimum — a `--window-size=375` screenshot is a CROP of a 500px layout
**Symptom / When:** Verifying mobile layout headlessly: an element that should be visible at 375px is absent from every screenshot, and probing finds a phantom "mobile overflow" — body `scrollWidth` reads ~485–500, elements sit at x > 375, and even a page reduced to a bare skip-link still measures 500px wide.
**Why:** `--headless=new --window-size=375,…` enforces a ~500px minimum window width for *layout* while the screenshot canvas honours the requested 375 — so the page lays out at 500px and the PNG is a left-edge crop. Media queries keyed at ≥500px (e.g. ≤991 mobile rules) still match, so mid-size breakpoints behave normally and the artifact only bites at true-phone widths — exactly when you are least likely to suspect the tool.
The general form, which outlives any Chrome version: **a screenshot is evidence about the harness as much as about the page.** A tool that silently substitutes its own viewport converts "I could not measure this" into "this is broken" — the same false-conclusion shape as the empty-grep trap in `00` → Evidence discipline.
**Fix:** For sub-500 viewports, embed the page in an iframe of the target width inside a wider headless window (`<iframe src="…" style="width:375px">` — cross-origin blocks script probes into it, but the screenshot is honest), or drive a real browser with proper device emulation.
**Windowed Chrome has the same floor, and the same-origin iframe beats it with full script access.** A browser driver's `resize_window` to 360px silently leaves `innerWidth` at the old width (OS / Chrome minimum window width), so every "mobile" measurement is desktop. From the page's own JS context, create `<iframe src="{same page}?cb=…" style="width:360px;height:800px;opacity:0;position:fixed">`, wait for `onload` and `contentDocument.fonts.ready`, measure with `contentWindow.getComputedStyle` / `getBoundingClientRect`, then remove it. Same origin, so full DOM access at an exact viewport width. Repeat at the minimum, midpoint and maximum of a fluid range: midpoint values landing exactly halfway confirm the clamp math. A long page adds a ~15px scrollbar inside the iframe; account for it before calling a width wrong.
**Companion trap: `--screenshot` writes the file and never exits.** With `--virtual-time-budget`, headless Chrome writes the PNG and keeps running, so the shell call runs to its own timeout and looks like a failed capture. Wrap each capture in a hard limit and treat the file as the result: `perl -e 'alarm 30; exec @ARGV' "$CHROME" --headless=new … --screenshot=out.png URL`, then `pkill` the profile dir. A `file://` harness iframing an `https` page with a self-signed cert hung outright; use a real page. Before trusting a negative, sanity-check the harness: render a page you know is correct at the same width, and if that looks broken too, the tool is the problem.
Companion diagnostic when no CDP tooling is available: save the page locally, append a `<script>` that writes `getComputedStyle` / `getBoundingClientRect` results into `document.title`, and read it back via `--dump-dom` — a poor man's headless evaluate. Same family as "Diagnostic JS via a Bricks code element".
**First seen:** Nametank (ext-mem), 2026-08-13 — the new header burger appeared "missing" in every 375px screenshot after the offcanvas build; an hour of cascade archaeology chased a phantom overflow (solo body children each "measuring" ~500) before the clamp was identified. The layout had been correct all along; an iframe-embedded 375px viewport showed the burger rendering perfectly.
⚠️ **UNVERIFIED SINCE.** The ~500px figure is a claim about a tool, not about the stack, and Chrome moves. Re-verify against the current Chrome before relying on the number. Consider also whether raw headless-Chrome CLI is still the house method for visual verification — if a real-browser driver with `resize_window` has replaced it, this entry's audience is narrower, though the principle and the `--dump-dom` fallback both survive.
**Re-confirmed for windowed Chrome:** pkjsupport, 2026-10-04 — `resize_window` to 360px left `innerWidth` at 1634; the same-origin iframe measured 360 / 863 / 1366 exactly. The headless no-exit trap: pkjsupport, 2026-10-07.

### A regex delete across a PHP file can silently remove hundreds of lines — and `php -l` will not notice
**Symptom / When:** After "removing one function" from a plugin file with a Python/sed regex, unrelated features die quietly. Dynamic tags render as their literal `{tag}` text, and a section gated on one of them disappears. Nothing fatals and the linter passes.
**Why:** A pattern like `/\*\*\n \* (?:[^\n]*\n)*? \*/\nfunction target_fn` anchors on the FIRST docblock in the file (the file header). The lazy quantifier then walks forward until it reaches the target, deleting everything in between. What's left is still valid PHP, so `php -l` tests nothing. The failure only shows on the rendered front end, on whichever page depended on the code that was cut.
**Fix:** Never delete across a PHP file with a regex. Anchor on the function's exact text: a `str.replace` of the verbatim block with `assert s.count(old) == 1`. Assert the line count moved by the expected amount before writing. Keep the plugin in git, or snapshot the file before any structural edit; on a dev box with no backups there is no other copy.
**First seen:** WCDP, 2026-09-13 — retiring one Bricks dynamic tag deleted 380 lines of the tag-registration file: the tag map, all three Bricks filters and seven value functions. The Home events carousel vanished because its `compare: empty` gate tag now rendered literally. The file was rebuilt from the session's earlier readbacks plus each tag's documented behaviour.

### A hidden tab freezes transitions and `requestAnimationFrame` — a focus ring "missing" and a measurement script that hangs
**Symptom / When:** Two faces, both from an automation tab that isn't in front. (1) Checking a focus style: the element matches `:focus` and `:focus-visible`, the custom properties resolve, yet computed `border-color` / `box-shadow` still show the unfocused values. It reads as an a11y failure. (2) A measurement script that waits on `requestAnimationFrame` hangs until the tool times out ("renderer may be frozen").
**Why:** Chrome doesn't advance CSS transitions or fire rAF in a tab whose `document.visibilityState` is `hidden`. With a transition on the property (WS Form: 0.2s on border-color and box-shadow), computed style reports the start value indefinitely.
**Fix:** Check `document.visibilityState` first. To measure a transitioned end state, set `el.style.transition = 'none'`, re-focus, read, then restore. Wait with `setTimeout(…, 80)`, not rAF. Same family as `00`'s "a real result that measures the wrong property."
**First seen:** pkjsupport, 2026-10-07 — a WS Form field's focus ring (accent border plus a 2px `--focus-color` ring) read as absent until the transition was suppressed. Later the same day a rAF-based measurement hung in a background tab.

### Diagnostic: stop reasoning about the cascade — dump the matched CSS rules via CDP
**Symptom / When:** "My rule isn't applying" on a Bricks/ACSS/Woo stack, where ACSS, Bricks' integration sheets, Bricks inline global-class CSS and the child theme all target the same elements and load in a non-obvious order.
**Why:** Specificity intuition fails on this stack more often than it succeeds, because the losing factor is usually invisible in the selector you're looking at: an ID smuggled inside `:not()`, an attribute selector adding a class's weight, an `#brx-content > *` container rule, ACSS `:where()` rules that lose ties on purpose, or your own earlier over-broad rule.
**Fix:** Ask the browser which rules matched. CDP returns them in cascade order with origins, so the winner is unambiguous:
```js
const cdp = await ctx.newCDPSession(page);
await cdp.send('DOM.enable'); await cdp.send('CSS.enable');
const doc = await cdp.send('DOM.getDocument');
const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: doc.root.nodeId, selector: '.my-el' });
const m = await cdp.send('CSS.getMatchedStylesForNode', { nodeId });
for (const e of m.matchedCSSRules) {
  const props = e.rule.style.cssProperties.filter(x => /^margin/.test(x.name) && x.value); // narrow to the property
  if (props.length) console.log(e.rule.selectorList.text, '->', props.map(x => x.name + ':' + x.value).join('; '));
}
```
Narrow the property filter, or the output is unreadable. Pair it with a `getComputedStyle` read: **a correct computed value with no visual change means you're styling the wrong node** (see "A Bricks element with a link is wrapped in an `<a>`"), not losing a specificity fight.
**First seen:** MMHN, 2026-07-20 — used four times in one session. It found an ACSS flow-spacing rule nobody suspected, a greedy in-house `a:not(.button)` rule, Bricks' `:not(#brx-content)` ID, and a container rule outranking class-only selectors. Every guess made before measuring was wrong at least half the time.

# === PROJECT SECTION — "we learned" ===

*Empty at kickoff. New gotchas discovered during this project's build append below, in the entry format above. At go-live these are reviewed and the validated ones fold into the established section of the master.*
