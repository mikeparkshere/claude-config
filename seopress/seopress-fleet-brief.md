# SEOPress — Fleet Evaluation Brief

> **Status: evaluation, not adopted.** Fleet convention is currently RankMath Pro. Oakham Trailer Sales (`app-oakham`) was the first SEOPress site, configured 2026-09-07 on **SEOPress Free 10.2**. Punch Barber Shop (`app-punch`) is the first **Rank Math → SEOPress port**, 2026-10-06 on 10.3 (§7a). The fleet decision is pending; this document accumulates everything needed to make it, and everything needed to execute it if the answer is yes.
>
> Two migration paths will exist if we adopt: **RankMath → SEOPress ports** (most Tier 2/3 sites) and **clean installs** (new builds). Both are covered below.
>
> Every claim here was verified against SEOPress 10.2 source or observed on a live site. Where something is inferred rather than tested, it says so.

---

## 1. Why this is worth evaluating: the CLI story

**SEOPress ships zero WP-CLI commands.** I grepped the entire plugin including `vendor/` for `WP_CLI::add_command` — there are none. That sounds like a strike against it, and it is the opposite.

Every setting SEOPress has lives in **eight plain `wp_options` rows holding serialized arrays**. No custom tables, no transients, no cache layer over the settings or the sitemap. That means the whole plugin is configurable with `wp option update --format=json` and `wp eval`, and a configuration is a JSON file you can diff, commit, and replay.

Compare RankMath: options split across dashed names (`rank-math-options-titles`, `rank-math-options-general`, …) plus its own tables, plus `Cache::invalidate_storage()` required after any direct write or the sitemap silently serves stale content. SEOPress has no equivalent invalidation step — a plain `update_option` takes effect on the very next request.

**For a fleet managed from the CLI, this is the single strongest argument in SEOPress's favour.**

### The eight options

| Option | Holds |
|---|---|
| `seopress_toggle` | Feature module on/off switches |
| `seopress_titles_option_name` | Title/meta templates per CPT + per taxonomy, robots directives |
| `seopress_xml_sitemap_option_name` | Sitemap enable + which CPTs/taxonomies are included |
| `seopress_social_option_name` | OG/X card config + **Knowledge Graph (Organization schema)** |
| `seopress_advanced_option_name` | Verification codes, admin columns, head cleanup, image alt |
| `seopress_instant_indexing_option_name` | Bing/IndexNow key + auto-submit |
| `seopress_google_analytics_option_name` | GA4 / Matomo / Clarity (not created until used) |
| `seopress_db_version` | Schema marker — don't touch |

### Per-post and per-term data is ordinary meta

`_seopress_titles_title`, `_seopress_titles_desc`, `_seopress_robots_index`, `_seopress_robots_follow`, `_seopress_robots_canonical`, `_seopress_robots_primary_cat`, `_seopress_social_fb_title/desc/img`, `_seopress_social_twitter_title/desc/img`, `_seopress_analysis_target_kw`.

All settable with `wp post meta update` / `wp term meta update`. This is what makes both bulk authoring and the RankMath port tractable from the CLI.

### Settings export/import are callable services

```php
seopress_get_service( 'ExportSettings' )->handle( $exclude_categories );
seopress_get_service( 'ImportSettings' )->handle( $array );
```

Both bypass the nonce/`admin_init` wrappers, so they run from `wp eval` with no admin context. `ExportSettings::getSiteSpecificCategories()` defines strippable groups — `knowledge_graph`, `social_profiles`, `analytics_ids`, `verification_codes`, `license`, `api_keys` — which is exactly the "build once, replay everywhere" mechanism a fleet needs.

**A scrubbed Oakham baseline is committed at `seopress/seopress-fleet-baseline.json`, produced by `seopress/export-baseline.php`.**

⚠️ **`ExportSettings`'s own scrub categories are incomplete — do not trust them alone.** Every category is defined as a list of `seopress_social_*`, `seopress_google_analytics_*`, `seopress_advanced_*` or `seopress_pro_*` keys, so anything site-specific living in **`seopress_titles_option_name`** passes straight through. Confirmed leak: `seopress_titles_home_site_title_alt` — the site's alternate name, which feeds schema `alternateName` — survives a `knowledge_graph` exclusion and ships the client's business name in the "scrubbed" baseline. Registered post types and taxonomies leak the same way, since no category covers them either.

`export-baseline.php` strips all of these explicitly on top of `ExportSettings`. **Always `grep -i <clientname>` the output before committing a baseline.**

### There is also a REST API

`/seopress/v1/…` — `Options/SitemapsSettings`, `Options/AdvancedSettings`, `Metas/RobotSettings`, `Diagnostics/SitemapTest`, and more. Cookie/nonce authenticated, so `wp option` remains the cleaner CLI path, but it exists if we ever want remote orchestration.

---

## 2. Free vs Pro — the real boundary

Verified by reading the front-end emitters, not the marketing matrix.

### Schema is where free ends, and it ends early

**SEOPress Free emits exactly two JSON-LD blocks, both on the front page only:**

- `WebSite` — `inc/functions/options-social.php:88`
- `Organization` — `src/Actions/Front/Schemas/PrintHeadJsonSchema.php`, gated on `is_front_page()` and on a Knowledge Graph type being set

`src/JsonSchemas/` contains three classes total: `Organization`, `ContactPoint`, `Image`. **There is no Product, no per-page LocalBusiness, no BreadcrumbList, no FAQ, no Article schema anywhere in free.**

The Knowledge Graph type selector does offer `LocalBusiness` alongside Organization, Corporation, OnlineStore, EducationalOrganization, GovernmentOrganization, NGO, NewsMediaOrganization, OnlineBusiness, and Person — with a full address block, phone, email and `contactPoint`. So free *can* put a legitimate LocalBusiness node on the homepage. Its limits:

- **No industry subtypes.** No `AutoDealer`, `HomeAndConstructionBusiness`, `HealthAndBeautyBusiness`, etc. Only the ten types above.
- **Homepage only.** No opening hours, no geo coordinates, no `priceRange`, no multi-location.

### Activation switches on all six free modules — audit them, don't inherit them

The free dashboard exposes six module tiles: **Titles & Metas, Social, XML Sitemap, Analytics, Instant Indexing, Advanced**. All six are enabled at activation regardless of whether the site can use them. On Oakham, two were doing nothing at all until audited:

- **Analytics** — the `seopress_google_analytics_option_name` row **does not even get created**. The module is on, renders nothing, and reads as configured on the dashboard. Turn it off unless you're actually putting GA4/Matomo/Clarity in it. The baseline ships it **off** for this reason; enable it deliberately when configuring analytics.
- **Advanced** — enables nine settings, three of which are inert or unwanted on our stack:
  - `..._advanced_image_auto_alt_txt` hooks **`wp_content_img_tag`**, which only fires for images inside `the_content`. **Bricks renders images through its own elements, so this has almost no surface on any of our builds.** (It is *not* a filename scraper — it copies an attachment's existing alt meta onto images missing one. Benign, just idle.)
  - `..._advanced_replytocom` is pointless on any site without comments.
  - **Five admin columns** (title, meta desc, score, noindex, nofollow) get added to *every* post type's list table. Every MPD site has deliberately-designed custom admin columns, so this crowds a screen someone built to a shape. The **score** column is worse than cosmetic noise: it renders empty until content analysis has been run per-post, which on a Bricks site it never has been.

  Baseline keeps `..._advanced_attachments` (attachment-page redirect), `..._advanced_tax_desc_editor`, and the **noindex + nofollow columns only** — those two are genuinely useful at-a-glance robots status.

**Instant Indexing is the one to leave on.** It's the free module that most justifies its tile: SEOPress auto-generates the IndexNow key at activation and serves it at `/{key}.txt`, then submits on publish **and update and trash**, for any publicly viewable post type. On an inventory site where units arrive and sell, that pings Bing/Yandex/Seznam on every stock change for nothing. Verify the key file actually resolves (`curl -o /dev/null -w '%{http_code}' https://site/{key}.txt`) — the submissions silently no-op if it doesn't.

### The toggle option is mostly theatre

`seopress_toggle` holds 16 switches, but only **eight are read anywhere in free code**: `titles`, `social`, `xml-sitemap`, `advanced`, `google-analytics`, `instant-indexing`, `robots`, `white-label`.

The other eight — `local-business`, `rich-snippets`, `breadcrumbs`, `404`, `bot`, `dublin-core`, `llms`, `ai`, `inspect-url` — are inert placeholders only Pro reads. **Do not read a toggle being "on" as evidence a feature is active.**

### Other Pro gates worth knowing

- **robots.txt editor** is Pro. Free only appends the `Sitemap:` line to WordPress's virtual robots.txt (`inc/functions/options-robots-txt.php`).
- Redirections, 404 monitoring, breadcrumbs, WooCommerce integration, video/news/HTML sitemaps, and the schema builder are all Pro.

### Recommendation on Pro

For a brochure-plus-inventory site of the kind we build, **Pro's schema builder is the weakest reason to buy it** — we write better structured data in the core plugin, driven by real ACF data, than any form-driven UI produces. The genuine Pro value is redirections and 404 monitoring, and only on sites that have migrated URLs or accumulated link rot.

**Hold off per-site. Buy Pro reactively, not as a fleet default.**

---

## 3. Division of labour: plugin vs `[client]-core`

This is the part that should become convention regardless of which SEO plugin wins.

**The SEO plugin owns** — all CLI-configurable, all fleet-portable through the export JSON:
title/meta templates, robots directives, canonicals, XML sitemap, OG and X card tags, search-engine verification codes, IndexNow, `<head>` cleanup, admin columns.

**The core plugin owns** — because it has the data and the plugin does not:

- **Industry-correct LocalBusiness schema** (`AutoDealer`, etc.) with hours, geo, `areaServed`, `priceRange`, sourced from the ACF Site Options the site already maintains.
- **Per-item `Product` / `Offer` schema** for any inventory CPT, with `availability` mapped from the site's own status vocabulary. No plugin can get this right — on Oakham, for instance, a `pulled` consignment must map to `Discontinued`, never `OutOfStock`, and no UI exposes that distinction.
- **`BreadcrumbList`**, since Bricks renders the visual breadcrumb.
- **Output corrections** for the plugin's wrong defaults (see §6).

SEOPress gives clean seams for this: `seopress_get_json_data_organization` (filter the data array) and `seopress_schemas_organization_html` (filter the rendered JSON string). Our schema and theirs compose rather than collide.

**This is a point in SEOPress's favour over RankMath**, whose schema layer is much harder to partially override without fighting it.

### The pattern, proven on Oakham

Built 2026-09-07 as `inc/schema.php`; **0 errors from validator.schema.org on every node**. Reusable shape for any local-business + inventory site:

**Augment the plugin's business node, don't replace it.** `seopress_get_json_data_organization` hands you the assembled array. Set the industry `@type` there (`AutoDealer`, `HomeAndConstructionBusiness`, …) and add what free cannot express — `@id`, `image`, top-level `telephone`, `openingHoursSpecification`, `geo`, `hasMap`, `areaServed`, `priceRange`. The name, address and logo keep coming from the SEOPress UI, so the client still edits them without a deploy, and the page carries one business node rather than two competing ones.

Details worth copying:

- **`image` is not `logo`.** Google's Local Business guidance wants a photograph; a brand mark is a poor stand-in. Find a real photo in the media library and reference it behind a filter so it can be swapped without a file edit.
- **`telephone` belongs on the business**, not only nested in `contactPoint` — SEOPress only writes the nested one.
- **Derive `priceRange` from live inventory**, cached in a transient busted on save. A hardcoded range or a `"$$"` placeholder goes stale or says nothing.
- **Resolve `geo` from the client's Google Business Profile short link** rather than geocoding the address yourself: `curl -sL -o /dev/null -w '%{url_effective}'` on a `maps.app.goo.gl` link returns a URL containing `@lat,lng`. That guarantees the schema and the GBP describe the same point, which is the whole purpose of the property.
- **Anchor everything to one `@id`** (`home_url('/#dealer')`) and point each product's `offers.seller` at it, so the nodes form a graph instead of unlinked islands.

**Map availability from the site's own status vocabulary, not from a plugin dropdown.** This is the single clearest argument for owning schema in the core plugin. On Oakham: `available` → `InStock`, `pending` → `LimitedAvailability`, `sold` → `SoldOut`, and `pulled` → `Discontinued` **with no `Offer` emitted at all**, because a consignor withdrawal is not the dealer's sale to price. No plugin UI — free or Pro — exposes that distinction. It only exists because the CPT's status vocabulary does.

**Exclude data that isn't already public.** VIN is stored on Oakham's units but rendered nowhere; structured data must not be the thing that first publishes it. Audit the front end before mapping a field into schema.

**Validate with the real validator, from the CLI:**
```bash
curl -s -X POST https://validator.schema.org/validate --data-urlencode "url=<page>" \
  | tail -c +6 | python3 -c "import sys,json; d=json.load(sys.stdin); ..."
```
The response is JSON prefixed with `)]}'` — strip the first five bytes before parsing. Walk every `tripleGroups[].nodes[]`, not just the first group, or you will miss half the nodes on the page and think a type isn't being parsed.

---

## 4. Clean-install playbook

Order matters. Steps 1–2 must precede any content work.

1. **Install + activate.** SEOPress seeds sensible defaults immediately, including auto-detecting registered CPTs and taxonomies into the title templates. **Skip the setup wizard entirely** — everything it does is reachable from CLI, and the wizard writes `'none'` string literals where the settings screen writes `''`.
2. **Fix the sitemap.** Defaults include `post` + `category` whether or not the site uses them, and **exclude every custom post type**. On a site whose content *is* a CPT, the default sitemap advertises nothing that matters. See gotcha #2.
3. **Set the Knowledge Graph** — needs a **raster** logo (gotcha #6). **Take the schema `name` from the client's Google Business Profile, verbatim**, not from the WP site title or an ACF company-name field — those drift from GBP and are the wrong authority for an entity Google is trying to reconcile with a Knowledge Panel. Put the other form in `seopress_titles_home_site_title_alt`, which feeds `alternateName` on both the `Organization`/`LocalBusiness` and `WebSite` nodes. Left unset, `alternateName` falls back to the site title and you get `name` and `alternateName` identical — noise in both nodes.
4. **Set archive titles** for CPT archives; the seeded template ends in a dangling separator (gotcha #4).
5. **Audit the six module tiles** — activation turns all of them on regardless of use. Analytics in particular creates no option row and renders nothing. See §2.
6. **Apply the core-plugin correction module** (§5).
7. **Purge page cache.** RunCloud Hub caches sitemap XML.
8. **Verify from outside**: `/sitemaps.xml` index, each child sitemap 200s and has `<loc>` entries, `/robots.txt` advertises the sitemap, the IndexNow key file resolves, one representative page's `<head>`.
9. **Export a scrubbed baseline** and commit it.

Start from `seopress/seopress-fleet-baseline.json` rather than SEOPress's own defaults — it already has steps 2 and 4 encoded for the built-in post types.

---

## 5. Portable core-plugin module

`inc/seo.php` in the client core plugin. The Oakham implementation is the reference; the three fixes in it are **stack-level, not site-specific**, and belong on every SEOPress site:

| Fix | Why |
|---|---|
| `seopress_social_og_type` → `product` on inventory singulars | SEOPress hardcodes `product` for WooCommerce/EDD only and falls through to `article` for every other post type |
| `seopress_social_twitter_card_site` / `_creator` → `''` when no X handle | Suppresses two empty meta tags SEOPress emits on every page (gotcha #3) |
| `seopress_titles_title` / `_desc` whitespace collapse | Removes double spaces and dangling separators left by empty dynamic tags (gotcha #4) |

The og:type post type is the only per-site variable.

### Generated descriptions for any CPT-driven site

**Check `post_excerpt` coverage before trusting SEOPress's description defaults.** The seeded template is `%%post_excerpt%%`. Where a site has no excerpts — which is every Bricks build we make, since editors never touch the classic excerpt field — WordPress auto-generates one by trimming `post_content`. Two failure modes follow, and both were live on Oakham:

- **Pages with empty `post_content` emit no description tag at all.** A Bricks-only page has nothing to trim. Oakham's Service and Contact pages had no meta description whatsoever, and nothing in the SEOPress UI flags it.
- **Everything else emits an over-length content dump.** Oakham's trailers ran 164–302 characters against a ~155 truncation point.

For an inventory or catalogue CPT, **generate the description from structured fields rather than authoring per-post meta.** Authoring doesn't scale and goes stale on the first price change. The Oakham implementation (`inc/seo.php` → `oakham_seo_build_trailer_description()`) is the reference pattern:

1. Assemble in ordered clauses — name → type → specs → call-to-action.
2. **Trim from the tail inwards** until under budget, so a long product name costs detail rather than yielding a truncated sentence. A blind `substr()` produces mid-word garbage; a clause ladder always ends on a full stop.
3. **Branch on availability status**, and never let an unavailable item advertise a price.
4. **Bail when `_seopress_titles_desc` is set**, so a hand-written override always wins and the client keeps per-item control.

Same pattern for titles: measure, and only intervene when over budget. **Measure entity-decoded** — a site name containing `&` is stored as `&amp;`, so the raw string is four characters longer than the SERP shows, and a naive `mb_strlen()` reports every title as over budget.

Static pages are the opposite case: seed those as `_seopress_titles_desc` post meta rather than code, so the client can revise them in the metabox without a deploy.

---

## 6. Verified gotchas

Each was hit and confirmed on 2026-09-07 unless noted.

### 1. Emitters load on `wp_head` priority 0 — `remove_action` from earlier hooks is a silent no-op

SEOPress does not register its social or title emitters at plugin load. `inc/functions/options.php:139` hooks `seopress_load_social_options()` on **`wp_head` priority 0**, and that callback is what `require`s `options-social.php` — the file whose top level contains the `add_action( 'wp_head', 'seopress_social_*', 1 )` calls.

So nothing is hooked until `wp_head` priority 0 has already run. A `remove_action()` on `init`, `wp`, or `template_redirect` **removes nothing and reports no error**. A removal at `wp_head` priority 0 is a coin flip on plugin registration order.

**Always use the output filters instead** — they're resolved by `has_filter()` at emit time, so registration order is irrelevant. Titles work the same way (`wp_head` priority 0 → `seopress_load_titles_options`).

> **This cost real time to find, because it fails deceptively.** A `wp eval` test of the removal *passes* — CLI loads the file by a different path, so the actions genuinely are hooked and genuinely do get removed. It only fails on the front end. **Verify SEOPress hook surgery with `curl`, never with `wp eval`.**

### 2. Default sitemap config is wrong on any CPT-driven site

Out of the box: `post` and `category` included, **every custom post type and custom taxonomy excluded**. On Oakham that meant the entire 9-unit inventory — the whole point of the site — was absent, while two empty sitemaps for unused blog surfaces were advertised to Google.

Canonical shape is `key => [ 'include' => '1' ]` (`SitemapOption::normalizeIncludeList`). Removing a key entirely excludes it.

**Adding a CPT does not need a rewrite flush.** `Router::registerRewriteRules()` registers one generic pattern, `^([^/]+?)-sitemap([0-9]+)?\.xml$`, for all post types. Pure option write.

### 3. Empty X handle ships two empty meta tags on every page — SEOPress bug

`SocialOption::searchOptionByKey()` returns **`NULL`** for a key never saved. Both X attribution emitters guard with `'' !== $handle`. `'' !== null` is **`true`**, so the guard passes and SEOPress prints:

```html
<meta name="twitter:site" content="">
<meta name="twitter:creator" content="">
```

on **every page of every install where the X username was never filled in** — which is most client sites. Not cosmetic: empty attribution tags are a validator warning and a sloppy signal.

Fix with the filters (see gotcha #1 for why not `remove_action`). Note the asymmetry: `twitter:creator` checks its value is non-empty before echoing, so it's fully suppressed; `twitter:site` echoes whatever the filter returns, so an empty return leaves one stray newline and no tag. Harmless.

### 4. Empty dynamic tags leave double spaces and dangling separators

SEOPress substitutes `%%tag%%` variables in place and never tidies up. The stock archive template it seeds is `%%cpt_plural%% %%current_pagination%% %%sep%%` — on page 1, `%%current_pagination%%` is empty, so you get `Trailers For Sale  - Site Name` (double space), and templates ending in `%%sep%%` emit a trailing separator with nothing after it.

**This ships in SEOPress's own defaults**, so it affects every archive on every clean install. Fixed generically in the core plugin via `seopress_titles_title` / `seopress_titles_desc`.

### 5. `ImportSettings` — and the dashboard module tiles — silently un-autoload options

`ImportSettings::handle()` writes with `update_option( $name, $value, false )` — autoload **false**, for all eight options. Since `update_option` also updates the autoload flag on an existing row, importing a settings JSON turns eight autoloaded options into eight extra queries on every request.

**The same bug is in the UI.** `inc/admin/ajax/Dashboard.php:40` writes `update_option( 'seopress_toggle', $opts, false )` — so **every time someone flips a module tile in wp-admin, `seopress_toggle` stops being autoloaded.** It's read on essentially every request via `seopress_get_toggle_option()`, so this is a permanent extra query bought with one click. Re-assert it after any dashboard visit where tiles were touched. When toggling from CLI, pass `true` explicitly.

Disabled modules are stored as the string `'0'`, not removed — match that from CLI so the dashboard renders the tile correctly.

**Re-assert autoload after any import.** Verify with:
```sql
SELECT option_name, autoload FROM wp_options WHERE option_name LIKE 'seopress%';
```
Both `auto` and `on` mean autoloaded; `off` is the problem.

### 6. Brand SVG logos are unusable for schema

Google requires the Organization/LocalBusiness `logo` to be a raster image (SEOPress's own field help: JPG/PNG/WebP/GIF, min 200×200). **Our Bricks/ACSS builds ship SVG logos as standard**, so on most fleet sites there will be no usable raster logo in the media library at all.

Rasterize from the SVG rather than settling for the favicon:
```bash
rsvg-convert -w 600 -h <proportional> logo-dark.svg -o /tmp/logo-raw.png
convert /tmp/logo-raw.png -background white -alpha remove -alpha off \
        -bordercolor white -border 24 -limit memory 128MB logo-schema.png
wp media import logo-schema.png --title="… — schema logo" --porcelain
```
Pick the **dark** logo variant (dark elements, meant for light backgrounds) — knowledge panels render on white. RunCloud boxes are memory-tight: keep the `-limit` flags and convert one file at a time.

### 7. The whitelist sanitizer is safe to call from CLI

`seopress_sanitize_options_fields()` (global function, `inc/admin/sanitize/Sanitize.php`) iterates a whitelist and modifies matching keys in place. **It does not drop unknown keys.** Safe and correct to pipe writes through it — it's exactly what the settings screen and the importer use, so it keeps CLI writes byte-identical to UI writes (notably: it prepends `@` to X handles and preserves meaningful whitespace in title templates, which `sanitize_text_field` would collapse).

### 8. Method-name trap: `getSeparator()`, not `getTitleSeparator()`

`TitleOption` has `getSeparator()`. Calling the plausible-sounding `getTitleSeparator()` throws a fatal that takes down every front-end page. Generally: **verify SEOPress service method names against source before shipping a filter that calls one** — a typo here is a white screen, not a warning.

### 10. Empty `fb:app_id` / `fb:pages` tags — same NULL guard bug, and **no filter**

Seen on 10.3 (Punch, 2026-10-06). `seopress_social_facebook_app_id_hook()` and `seopress_social_facebook_link_ownership_id_hook()` guard with `'' !== $value` on a key that's NULL when never saved, so every page gets `<meta property="fb:app_id" content="">` and `<meta property="fb:pages" content="">`. Unlike the X tags (gotcha #3), neither emitter has an output filter. **Fix in config, not code:** save both keys as `''` in `seopress_social_option_name` (`seopress_social_facebook_app_id`, `seopress_social_facebook_link_ownership_id`). Saving `seopress_social_accounts_twitter` as `''` would fix gotcha #3 the same way. Add all three to the baseline.

### 11. SEOPress 9.9+ names the classic SEO box `seopress_metabox_opener` — `seopress_cpt` guards miss it

Seen on 10.3 (ECT, 2026-10-06). `src/Actions/Admin/ModuleMetabox.php` registers the classic-editor box as
`seopress_metabox_opener` (context filterable via `seopress_metabox_opener_context`). Removing only
`seopress_cpt` / `seopress_content_analysis`, as `business-manager-role-playbook.md` and KB `03` describe,
**leaves the SEO box visible to a client role.** Fix for non-admins: filter `seopress_metaboxe_seo` to `[]`,
remove all three IDs in every context at `PHP_INT_MAX`, and strip `seopress*` list columns. Verify as the
role over HTTPS **with an admin control**. ECT's first check "passed" for both roles because it grepped
for the wrong ID. Reference: `app-ectlive` `ect-core/includes/seopress.php`.

### 12. `seopress_get_json_data_organization` sees unresolved `%%placeholders%%`

Seen on 10.3 (ECT, 2026-10-06). The data filter gets `'sameAs' => ['%%social_account_twitter%%', …]` and
`'description' => '%%social_knowledge_description%%'`. Values resolve afterward. Put structural changes
(`@type`) in the data filter and any value comparison (de-duping `sameAs` against the core plugin's own
social URLs, dropping a description that just repeats the name) in `seopress_schemas_organization_html`,
which receives the finished JSON. Done the other way round, the X URL shipped twice.

### 9. Page cache holds sitemap XML

RunCloud Hub Native cache serves `/sitemaps.xml` and its children. Always `wp runcloud-hub purgeall` after sitemap option changes, and cache-bust (`?cb=$RANDOM`) when verifying by curl.

---

## 7. RankMath → SEOPress port

**First executed 2026-10-06 on Punch Barber Shop (`app-punch`, SEOPress 10.3)**: live, no staging, because the site is 4 URLs with 0 redirects. The importer-source notes below still stand; the measured result is in **§7a**. On a site with real volume, a redirect map or plugin-owned schema, **dry-run on a staging clone first.**

### What SEOPress's importer covers

`src/Actions/Admin/Importer/RankMath.php`. Importers also exist for AIOSEO, SiteSEO and SureRank; Yoast, Squirrly, SmartCrawl, Slim SEO, WP Meta SEO, SEO Framework and Premium SEO Pack have separate handlers under `inc/admin/ajax/migrate/`.

**Post and term meta map** (identical for both):

| RankMath | SEOPress |
|---|---|
| `rank_math_title` | `_seopress_titles_title` |
| `rank_math_description` | `_seopress_titles_desc` |
| `rank_math_facebook_title` / `_description` / `_image` | `_seopress_social_fb_title` / `_desc` / `_img` |
| `rank_math_twitter_title` / `_description` / `_image` | `_seopress_social_twitter_title` / `_desc` / `_img` |
| `rank_math_canonical_url` | `_seopress_robots_canonical` |
| `rank_math_focus_keyword` | `_seopress_analysis_target_kw` |
| `rank_math_robots` (array) | `_seopress_robots_index` / `_follow` / `_imageindex` / `_snippet` |
| `rank_math_primary_category` / `_primary_product_cat` | `_seopress_robots_primary_cat` |

Dynamic tags are translated through a `Tags` class as values are copied, so RankMath's `%title%`-style variables become SEOPress's `%%post_title%%` form. Settings-level migration (`migrateSettings()`) additionally maps the Knowledge Graph type across.

### What it does NOT cover — plan for these separately

- **Schema.** RankMath's per-post schema (`rank_math_schema_*`) has no SEOPress-free destination. Anything relying on RankMath's Product/LocalBusiness/FAQ output **must be reimplemented in the core plugin before the switch**, or the site loses its rich results.
- **Redirections.** RankMath's redirection table has no free destination at all. This alone may force Pro on any site with a redirect map — **audit `rank_math_redirections` before quoting a port.**
- **404 log**, internal-link counters, and Analytics module data: not migrated, not replaceable in free.
- **Per-CPT schema defaults** (`pt_<cpt>_default_rich_snippet` in `rank-math-options-titles`): no equivalent.

### Running it from CLI

`process()` is `check_ajax_referer` + `is_admin()` + `manage_options` gated, and the three workers (`migrateSettings()`, `migratePostQuery( $offset, $increment )`, `migrateTermQuery()`) are `protected`. So the importer is **not directly callable from `wp eval`**.

Two options:

1. **Reflection** into the protected methods from `wp eval`. Works, but couples us to private API that can change between releases.
2. **Reimplement the meta copy in a CLI script.** The map above is the whole of it — roughly twenty lines, plus batching. **Preferred**, because it gives us a dry-run mode, a per-post log, and control over what happens when both plugins have a value for the same field.

Either way: **run with both plugins active**, verify, then deactivate RankMath. Never uninstall RankMath before the port is verified — its meta is the only copy of the data.

### Sequencing a port

1. Audit first: redirections count, schema types in use, per-CPT schema defaults, focus keywords.
2. Decide Pro / no-Pro **from the redirection audit**, not from feature preference.
3. Build the core-plugin schema module **before** switching, so structured data never goes dark.
4. Port meta with both plugins active. Verify a sample across every post type.
5. Configure SEOPress from the fleet baseline + site specifics.
6. Compare `<head>` and sitemap output against a pre-switch capture, page by page.
7. Deactivate RankMath. Keep it installed for one indexing cycle.
8. Resubmit the sitemap in Search Console — **the URL changes** (`/sitemap_index.xml` → `/sitemaps.xml`).


### 7a. Measured: Punch Barber Shop, 2026-10-06

**Scale:** 4 public URLs, 0 redirects, schema already in the core plugin. **Time:** about 20 minutes of execution after a 30-minute audit. The duplicate-head window (both plugins active) was about 2 minutes.

- **Port script, not the importer.** 10 values: 3 titles, 4 descriptions, 3 focus keywords, plus one `rank_math_robots` noindex → `_seopress_robots_index=yes`. Variable translation `%sep%`→`%%sep%%`, `%sitename%`→`%%sitetitle%%`, `%title%`→`%%post_title%%`. Skip empty values: Rank Math stores empty `canonical_url`/`facebook_image` rows on attachments and posts, and copying them is noise. Skip the bookkeeping keys (`internal_links_processed`, `analytic_object_id`, `seo_score`). Dry run by default, with an `apply` arg. Reference copies are in `/home/runcloud/backups/punch/{configure,port}.php` on that box.
- **Verify by head diff against a pre-switch capture, not by eye.** Parse title/meta/canonical from saved HTML of every URL before and after. Expected and accepted differences: no `og:updated_time`, no `og:image:type`, `og:type` `website` instead of Rank Math's `article` on singulars, and robots directive order.
- **The OG image chain matches Rank Math's** when `seopress_social_facebook_img` is the default and `seopress_social_facebook_img_default` is **unset**: post meta → featured image → global default. Setting `_img_default` = `1` forces the global image over featured images.
- **Sitemap continuity is free.** SEOPress 301s `/sitemap_index.xml` → `/sitemaps.xml` itself (`src/Actions/Sitemap/Render.php`), and Rank Math's unnumbered child names (`page-sitemap.xml`) still resolve because the rewrite's page number is optional. Free **does** append the `Sitemap:` line to virtual robots.txt; no `robots_txt` filter was needed.
- **Run validator.schema.org on the core plugin's node during the port.** It caught a pre-existing invalid `@type` (`BarberShop` isn't in schema.org; `HairSalon` is the barber type). Rank Math's suppressed graph had hidden nothing, but nobody had validated the replacement node in three months.
- **New gotcha found:** #10 (empty `fb:` tags, no filter).

### 7b. Measured: Decades Nightclub, 2026-10-06

Second port, and the **first with redirects**. Tier 3, 7 public URLs, 3 active Rank Math redirects, Rank Math-owned LocalBusiness schema. About 45 minutes including the audit. Copied Punch's `configure.php` / `port.php` / `inc/seopress.php` nearly verbatim.

- **Redirects didn't force Pro.** On a RunCloud `hybrid` webapp, `RedirectMatch 301` lines in `.htaccess` (after `# END WordPress`) replace a small Rank Math redirect map with no plugin at all. Confirm by the absence of `X-Redirect-By: Rank Math` on the response. Revise §7 step 2 accordingly: Pro becomes necessary for a *large or client-edited* redirect map, not for any map.
- **Rank Math's schema was worse than nothing**: `NightClub` with the 9–5 default hours on every day, no address or phone, a `Person` node naming the admin login, and `Article` on the homepage. Replaced by a core-plugin node (0 errors on the validator). Audit the live JSON-LD before deciding it's worth preserving.
- **Two more parity keys for the baseline:** `seopress_social_twitter_card_img_size = 'large'` (otherwise X cards drop from `summary_large_image` to `summary`), and CPT archive descriptions (`seopress_titles_archive_titles[<cpt>]['description']`), which Rank Math had and SEOPress seeds empty.
- **Physical `robots.txt`** (nginx-served) holds the old `sitemap_index.xml` line. The 301 covers it, but edit the line.
- **Verification trap:** a `?cb=` cache-buster on `/sitemap_index.xml` makes SEOPress's redirect miss and WordPress 301s to the homepage instead. Test that one URL without a query string.
- `/author/<login>/` was live under Rank Math. SEOPress's author-archive disable 301s it home, which stops exposing the admin login.

### 7c. Measured: East Coast Talents, 2026-10-06

Third port, and **the first with real volume**: ~60 organic clicks/day, 67 sitemap URLs, 10 active
redirects (one at 62,505 hits), Rank Math-owned schema and a load-bearing talent title template.
**46 seconds of maintenance mode**; about 45 minutes of verification after it. Prep took a session:
the schema emitter (ect-core 1.0.13), a URL Inspection replacement for Rank Math's (1.0.12), and
capture / configure / port / redirect artifacts. Runbook: `app-ectlive` box, `~/backups/ectlive-s9/RUNBOOK.md`.

- **Head diff by script, not by eye.** `capture.py before|after|diff` over 80 URLs (sitemap + noindex
  privacy terms, pagination, author, attachment, search, 404, sitemap, robots.txt), with an accepted-list.
  It surfaced the one real regression: a news post with no Rank Math description, whose SEOPress
  `%%post_excerpt%%` ran past 250 characters. Fixed by pinning the old one-liner as `_seopress_titles_desc`.
- **`max-image-preview:large` is automatic** on every indexable page in SEOPress Free. Rank Math had
  been omitting it on CPT archives, so the switch was an improvement there.
- **`RedirectMatch` after `# END WordPress` fires** (mod_alias), even though a mod_rewrite rule there
  wouldn't. It was probed live with a throwaway path, anchored and unanchored, before relying on it. Rank
  Math's "contains" match maps to an unanchored pattern. Query strings pass through, as Rank Math did.
- **`bricks_template` is a public post type** (and `template_tag`/`template_bundle` public taxonomies).
  Noindex them and keep them out of the sitemap in `configure.php`, or SEOPress seeds them as indexable.
- **Runbook order:** maintenance **off**, *then* purge and warm. A warm under maintenance asserts 503s.
- **Cloudflare can hold the old `robots.txt`** (4 h max-age) after the switch; the old sitemap URL
  301s, so it's harmless. Purge it if the zone token allows.
- New gotchas: #11 (metabox opener ID), #12 (organization filter runs pre-resolution).

---

## 8. Decision inputs

**For SEOPress**
- Configuration is eight `wp_options` rows: fully CLI-driveable, diffable, replayable across the fleet. RankMath is not.
- No cache-invalidation dance after direct writes.
- Clean filter seams for composing our own schema alongside the plugin's.
- Free tier covers titles/meta/sitemap/OG competently, so the Pro decision becomes per-site rather than fleet-wide.
- A scrubbed baseline JSON makes a new site's SEO config a one-command operation.

**Against**
- Free schema is thin enough that we take on the structured-data work ourselves. That's arguably correct anyway — but it is real core-plugin work per site, and it must be built *before* any RankMath port or the site loses rich results mid-flight.
- Redirections are Pro-only with no free fallback; any site with a redirect map either buys Pro or needs another solution.
- Real bugs in shipping defaults (gotchas #3 and #4 affect every install untouched out of the box). Not disqualifying, and both are fixed once in the portable core-plugin module — but it means SEOPress cannot be dropped in unattended.
- Migration cost is per-site and non-trivial for anything with schema or redirects.

**Still unknown**
- One RankMath port executed (Punch, §7a): small, clean, about 20 minutes. Still unmeasured on a site with volume, redirects or plugin-owned schema. **The next useful experiment is a dry-run port on a staging clone of a Tier 3 site** — that produces the number the fleet decision actually turns on.
- SEOPress Pro has not been trialled on any site.
- No SEOPress site has been through a full indexing cycle yet, so there is no ranking or Search Console evidence either way.

---

*Created 2026-09-07 from the Oakham configuration session. Update as sites are added or the port is trialled.*
