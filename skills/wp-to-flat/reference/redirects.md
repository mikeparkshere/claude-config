# Redirect plan

```bash
node scripts/url-map.js <harvest dir> [--plan plan.json] [--format htaccess|nginx] [--out <file>]
```

Reads `reduced/urls.json` and `reduced/media.json`; a `plan.json` in the harvest dir is picked up automatically.
Writes the rules, `reduced/redirect-plan.md` (the table for `MIGRATION.md`) and `reduced/redirect-tests.tsv`
(expected status and Location per URL, for the parity check).

## Defaults, and why

| URLs | Result | Why |
| --- | --- | --- |
| Kept pages | no rule, must answer 200 | URLs match WordPress's trailing-slash permalinks |
| `?p=<id>`, `?page_id=<id>` with a known ID | 301 to that page's new URL | the harvest knows every ID, so send people to the page they meant, not home |
| Other WP query routes (`?author=`, `?cat=`, `?tag=`, `?attachment_id=`, unknown IDs) | 301 home | |
| Term, author, post type archives; `*/attachment/*`; `/author/*` | 301 home, or `plan.archives` | |
| A path prefix with pages under it but no page of its own (a CPT base) | **gap**: suggest a target | people trim URLs; the first port redirected one to the home page's team section |
| Old sitemap names (`sitemap_index.xml`, `*-sitemap.xml`, `wp-sitemap.xml`) | 301 `/sitemap.xml` | Search Console still has them on file |
| Referenced media in `plan.media` | 301 to the new file, any size suffix, `-scaled`, `.webp`/`.avif` twins | image search and old links |
| Referenced media not in `plan.media` | **gap**, falls to 410 | decide per file |
| `wp-admin`, `wp-includes`, `wp-json`, the rest of `wp-content`, `wp-login.php`, `xmlrpc.php`, feeds | **410 Gone** | crawlers drop a 410 faster than a 404; it's machine traffic, nothing to send anywhere |
| `www.` | 301 to the apex (`plan.host`) | one host |

## Rules that came from production, not theory

- **Hard-code `https://%{HTTP_HOST}` in every target.** Apache sits behind nginx and never sees TLS, so a
  relative target, or mod_dir's own trailing-slash redirect, comes out as `http://`. Set `DirectorySlash Off` in
  the site's routing and add the slash with an explicit https rule.
- **A target with a `#fragment` needs a twin rule for query strings.** Apache appends the query *after* the
  fragment, where analytics never sees it (UTM tags on a printed card were lost this way). The generator emits
  `RewriteCond %{QUERY_STRING} .` + `…?%{QUERY_STRING}#frag` first, then the plain rule.
- **On a hybrid webapp, nginx hands missing files to Apache,** even `.webp`/`.jpg`, so `.htaccess` rules catch
  old media paths. No panel config needed.
- Put the block **after** the site's own routing, which owns `RewriteEngine On`, robots and the www rule.

## Verifying

1. The plan: run `redirect-tests.tsv` against the old site's URLs as you build. Parity-check does this.
2. The rules themselves, before cutover: mount them in a throwaway subfolder of the new webroot
   (`public/_test<n>/.htaccess` = `RewriteEngine On` + the block), curl a sample of each rule through that
   prefix, then delete the folder in the same command so the tamper check stays clean. A broken `.htaccess`
   there only affects that folder.

The nginx output is written but **hasn't been run against nginx** (no root on the fleet boxes). nginx `return` drops
the query string, so UTM tags don't survive it.
