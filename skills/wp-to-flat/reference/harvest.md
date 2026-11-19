# Harvest and reduce

Two steps, both scripted. Claude reads only `reduced/`; `raw/` holds builder blobs that would swamp the context.

```bash
scripts/harvest.sh --path <wp root> --out <dir> [--ssh <host>] [--base-url https://<host>] [--screens]
node scripts/reduce.js <dir>          # Node 14+, no dependencies
```

Keep `<dir>` outside the skill folder and out of git (the script refuses a path inside the skill). It holds
client content. A per-site `_harvest/` (gitignored) or `~/harvests/<site>/` both work.

## What comes out

| File | Use |
| --- | --- |
| `reduced/inventory.md` | Gate 1. FLAGS first, then site, candidate verdict, pages, SEO, menus, forms, integrations, plugins, fonts, media, other URLs, ACF. Feeds `MIGRATION.md` |
| `reduced/pages/<type>-<slug>.md` | One per public URL: content source, header/footer, fields, SEO, then sections in reading order (headings with levels, copy, `[button]`/`[link]` with targets, `[image]` with file and alt, a `_layout:` line per section) |
| `reduced/templates/` | Shared headers and footers, reduced once |
| `reduced/urls.json` | Every public URL with its kind and `keep` flag, plus existing redirects. Input for `url-map.js` |
| `reduced/media.json` | Referenced images: file, alt, size, master kind, who uses them |
| `media/` | The best master per referenced image, at its uploads path |

Without `--base-url`, SEO is stored meta (plugin variables like `%%sep%%` unresolved). With it, rendered
title, description, canonical, robots, H1s, schema types and third-party hosts come from the live HTML, and
those win. Parity needs the rendered run.

## Things the first fixture taught (11/13/2026, Bricks + SEOPress + ACF)

- **Load WordPress normally.** `--skip-plugins` hides custom post types and breaks their permalinks, so CPT
  pages vanish from the harvest. Loading plugins is still read-only as long as nothing writes.
- **Bricks content meta exists on revisions too.** Only published posts count.
- **CPT pages often have no Bricks content of their own.** A `content` template with a `postType` condition
  draws them, filled with ACF fields and the post content. `reduce.js` picks the template by Bricks's
  condition rules (ids > front page > error > post type > any; excludes win) and renders it with that
  post as context. Headers and footers are chosen the same way.
- **Let Bricks render dynamic tags.** Sites register their own (`{site_phone}`-style tags in a custom
  plugin). The harvest calls `bricks_render_dynamic_data()` for every tag in every post's context, so the
  reducer never needs to know them. A tag that still shows as `{…}` in output is flagged.
- **Best image master:** ShortPixel's backup (`uploads/ShortpixelBackups/wp-content/uploads/<path>`) beats the
  unscaled original (`wp_get_original_image_path`, before WordPress's `-scaled` copy), which beats the upload.
- **SEO plugin switches leave meta behind.** Stored meta from an inactive plugin isn't what renders, so the
  reducer reports it as leftover and parity uses rendered values.
- **Pages can also be Bricks templates** (they carry `_bricks_template_type`). Only `bricks_template`
  posts count as templates.
- **Elements keep demo settings.** A map with an address can still carry Bricks's sample Berlin
  coordinates. The reducer shows the address and flags demo coordinates only when nothing else is set.
- **Content isn't always where its element says.** Review names typed into the review text stay in the text;
  the reducer reports the source faithfully rather than guessing.

## Things the second port taught (11/16/2026, Bricks + ACSS + SEOPress + WS Form)

- **Orphaned Bricks elements.** Deleting a parent in the builder can leave its children in the page data with a
  `parent` id that no longer exists. Bricks never renders them. The reducer used to promote them to top-level
  sections, which produced "hidden" sections, duplicated blocks and a missing image no visitor ever saw (166
  orphans across six pages). Now they're skipped and counted in FLAGS. If a page note shows content the live page
  doesn't, check the FLAGS for orphans before calling it hidden.
- **Default templates.** A Bricks header or footer with **no conditions** applies site-wide unless Settings →
  Templates → "Disable default templates" is on. The harvest records that setting; the reducer applies it.
- **Attachment query loops** (a slider or gallery fed by a media folder, e.g. HappyFiles `taxonomy::term_id` filters)
  are resolved by `harvest.php` while WordPress is loaded; their images reach `media/` and the page notes. Safety
  net: `harvest.sh` sweeps the rendered pages for every uploads image (CDN-rewritten URLs included) and copies any the
  data walk missed; `reduce.js` flags them and adds them to `media.json` as `rendered-only`.
- **WS Form** keeps the live form as JSON in `wsf_form.published`; `wsf_field` has no `form_id` column. The harvest
  reads fields, required flags, options and the email/message actions from that JSON. A form with no email action
  only ever saved to the database: the inventory says so.
- **List elements carry descriptions** (`items[].description`), e.g. a drink and its ingredients. The reducer keeps them.
- **Optimizer-delayed CSS** (Perfmatters "Remove Unused CSS" + delayed stylesheets) makes a capture without user
  interaction look broken, and a stale used-CSS snapshot can make the live site look broken to visitors too. `shot`
  now nudges the mouse and wheel before capturing. Report a stale snapshot to Michael as a live-site finding.
- **Background video** keeps the network busy, so `networkidle` never comes. `shot` falls back to DOM-ready plus a
  settle time.
- **Captures fire analytics** on the live site. `shot` blocks analytics and ad beacons by default
  (`--allow-analytics` to let them through).

## Things the third port taught (11/19/2026, Elementor + Elementor Pro + Rank Math + WS Form)

- **Elementor support is three parts.** `harvest.php` stores each post's `_elementor_data` as `elementor`, plus
  `B.elementor`: every published `elementor_library` template with its `_elementor_conditions`, and the active kit's
  global colors and typography. `reduce.js` maps the theme-builder templates onto the Bricks picker (header, footer,
  `single*` → content, `error-404` → error; `include/general`, `include/singular/<type>[/<id>]`, `front_page`,
  `not_found404`) and writes archive templates to `reduced/templates/`, since a CPT archive in the menu becomes a
  listing page. `reducers/elementor.js` reads the tree.
- **Elementor's dynamic tags are not rendered at harvest** (Bricks's are). The reducer resolves the common ones
  itself: post title/excerpt/URL/featured image, site title/logo, ACF text/image/gallery by the field name after the
  colon in the tag's `key`. Anything else prints `{tag-name}` and is flagged.
- **The kit globals are the token step's starting point.** The inventory lists them under "Elementor kit globals".
- **The site owner's user may not be `runcloud`.** On a box with a second system user, run `harvest.sh` as that user
  (copy `harvest.sh` + `harvest.php` into its home first: it can't traverse `~/.claude`), then move the output to
  `runcloud`. The harvest is read-only either way.
- **Media outside uploads is invisible to the harvest.** Videos under a root `/assets/` showed up only as URLs in the
  page notes; copy them by hand to `media/_outside-uploads/`.
- **Non-image attachments** (MP4, PDF) used to get an empty path, because `wp_get_original_image_path()` returns false
  for them. Fixed: they fall back to the attached file.
- **`url-map.js`: an archive whose plan target is its own path is kept** (tested 200, no rule), for a CPT archive
  that becomes a real page.

## Adding a builder

One file in `scripts/reducers/<builder>.js` exporting `(elementsOrPost, ctx) => string[]`. `harvest.php`
detects the builder per post (`bricks`, `elementor`, `divi`, `wpbakery`, `gutenberg`, `classic`, `empty`);
classic and Gutenberg fall back to plain text extraction. A builder with no reducer is a Gate 1 flag, and
writing it is part of that port. Reducers exist for `bricks` and `elementor`. A builder with its own template system
(theme builder) needs its templates normalized in `reduce.js` too, the way Elementor's are.
