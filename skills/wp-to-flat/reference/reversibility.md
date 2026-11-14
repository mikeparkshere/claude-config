# Reversibility: keeping a path back to WordPress

A flat site should never be a dead end. If the client later needs a CMS, the way back is mapped, not reverse-engineered.

| Flat build | WordPress equivalent |
| --- | --- |
| Public URLs | Already identical: trailing-slash permalinks, `/%postname%/`, custom post type bases kept |
| `$page` (title, description, canonical, robots, OG image) | SEO plugin fields, one-to-one (Rank Math, SEOPress, Yoast) |
| `$site` sitewide facts | an options page (ACF, registered in PHP) |
| Data-file lists (team, services, hours, prices) | a custom post type or an ACF repeater on the options page |
| Partials | Bricks components with matching properties, or ACF field groups; **`components.json` says which** |
| JSON-LD `@graph` | the SEO plugin's schema settings plus a small custom-plugin filter for anything it can't express |
| `.htaccess` redirects | carried as-is (hybrid webapp), or into the SEO plugin's redirect manager |
| Built images (AVIF/WebP/JPEG sets) | the masters in `src/img/` upload to the media library; WordPress makes its own sizes |

## `components.json`

One entry per partial, written **while** it's built (`templates/components.json`):

```json
{
  "hero": {
    "file": "includes/partials/hero.php",
    "block": "hero",
    "args": ["heading", "subheading", "image", "cta_label", "cta_url"],
    "wp_equivalent": "Bricks component 'Hero' with matching properties; or ACF group: heading, subheading, image, cta (link)"
  }
}
```

Keep `wp_equivalent` concrete enough that someone could build it without opening the flat site.

The retired WordPress site itself is deleted after the rollback window. Keep its final backup (Duplicator package
off-site plus a DB export) as the content-level way back.
