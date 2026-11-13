# Scrub lint

```bash
scripts/scrub-lint.sh <site root> --harvest <harvest dir>
```

A failing lint is a **Gate 4 blocker**, not a note. Exit 0 clean, 1 hits.

## What it checks

**Layer 1, source** (`public/`, `inc/`, text files): `wp-content`, `wp-includes`, `wp-json`, `wp-admin`, `wp-emoji`,
`xmlrpc`, `brxe-`, `bricks-`, `wp-block-`, `wp-image-`. With `--harvest`, also any harvested image name carrying a
WordPress size suffix (`photo-300x200.jpg`, `photo-scaled-1024x768.webp`), the sign of a URL copied from the old
site instead of a rebuilt image.

**Layer 2, rendered:** every URL in `public/sitemap.xml` plus `public/404.php`, served by `php -S` on a random
local port. Each must answer 200 (the 404 page 404) and carry no generator meta, no oEmbed discovery link, no
fingerprint from the list above, and no leftover shortcode (`[name ...]`) in visible text. `[LIKE THIS]`
placeholders are warnings, not failures.

## Why it's built this way

- **Server config is skipped** (`.htaccess`, `*.conf`): redirect rules have to name `wp-content` and friends.
  `url-map.js` output lives there.
- **Shortcodes are checked in rendered text, not source.** In source the pattern matches `arr[i]`, `$x[key]` and
  CSS `[hidden]`. Scripts, styles, `<code>` and `<pre>` are stripped before matching.
- **Local render, never production.** No analytics hits, nothing on the live site, and it works before the domain
  exists. Pages that key analytics or HSTS on the production host stay quiet on `127.0.0.1`.
- **The size-suffix check needs the harvest.** A generic `-\d+x\d+\.` pattern fails on legitimate files like
  `favicon-32x32.png`, so the lint matches only names the old site's media library actually had.

## Allowlist

`<site root>/.scrub-allow`: one extended regex per line, matched against `path:line: text` (source) or
`path: kind: match` (rendered). Comment every entry with the reason. Keep it short; an entry is a decision Michael
should be able to read.

## Verified (11/13/2026)

Clean on the first finished port. In a scratch copy with planted leaks, it caught each one: a `brxe-` class in CSS, a
`[contact-form-7]` shortcode in copy, a `/wp-content/uploads/...-300x300.webp` path (as fingerprint and size suffix),
and generator and oEmbed tags on every rendered page. It warned on a `[PRICE TBD]` placeholder, and the allowlist
suppressed exactly the entry it named.
