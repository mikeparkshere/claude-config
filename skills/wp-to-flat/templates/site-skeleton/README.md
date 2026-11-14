# Site skeleton

A starting point that already follows `flatsite.md`. Copy it to the new webapp's root (not into the skill), then:

1. Replace `example.com` everywhere (`includes/config.php`, `public/.htaccess`, `public/robots.txt`) with the production
   apex, and the example business details with the real ones from the harvest inventory.
2. Write `includes/config.local.php` on the server from `config.local.php.example` (staging values, Mailgun, form
   recipient). It's gitignored; never commit it.
3. Set the webapp's public path to `/public`. Everything outside it (`includes/`, `tools/`, `MIGRATION.md`,
   `components.json`) is unreachable over HTTP.
4. Paste `url-map.js` output between the `wp-to-flat redirects` markers in `public/.htaccess`.
5. Every new page: a folder with an `index.php` like `public/index.php`, a row in `$site['pages']`, then
   `php tools/build-sitemap.php`.
6. Every new partial: a row in `components.json`.

Local preview: `php -S 127.0.0.1:8080 -t public` from the root (no `.htaccess`, so no redirects or headers there).

Verified 11/14/2026: boots under `php -S` with header, footer and one component; the contact handler answers 405 to
GET, 422 to bad input, 503 when unconfigured (fails closed), quietly accepts a honeypot hit, never echoes request
input, and delivered a real message through the Mailgun API; scrub lint passes clean and fails on a planted
`wp-content` path.
