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

## Adding a builder

One file in `scripts/reducers/<builder>.js` exporting `(elementsOrPost, ctx) => string[]`. `harvest.php`
detects the builder per post (`bricks`, `elementor`, `divi`, `wpbakery`, `gutenberg`, `classic`, `empty`);
classic and Gutenberg fall back to plain text extraction. A builder with no reducer is a Gate 1 flag, and
writing it is part of that port.
