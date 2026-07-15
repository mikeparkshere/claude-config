# 04 — Hosting, Cutover & Performance

**How the hosting layer behaves, and what bites during a go-live. The ops accumulator.**

Established 2026-07-15 at the TAB harvest. Verified against RunCloud (hybrid Nginx+Apache, PHP 8.4) / RunCache 0.1.0 / Cloudflare / ShortPixel / Perfmatters.

---

## How this file works

Same contract as `03`: **inherited, not authored**. A lookup catalog, consulted by symptom — not read cover to cover. Same seam, same entry format, same bar (>~15 minutes to figure out, likely to bite again), same go-live harvest.

**Why this file exists separately from `03`.** `03` is the build stack — how Bricks, ACSS, ACF and WS Form behave while you build. This file is the layer underneath and after: the box, the CDN, the caches, the cutover itself. They're different domains and you consult them at different moments. Mixing them made `03` slower to scan by symptom, which is `03`'s whole job.

**Scope note.** `00` originally scoped the knowledgebase to *"Local → Staging → go-live"* and explicitly not to live-site maintenance. This file deliberately extends past that line: a cutover IS the end of the lifecycle, and the post-launch performance entries were bought at real cost on a real launch. The scope grew; that's an honest expansion, recorded here rather than left implicit. See `00`.

**Entry format** — match it exactly:

```
### [Short pattern title — what you would search to find this]
**Symptom / When:** [the observable failure]
**Why:** [the underlying mechanism, briefly]
**Fix:** [the actual fix, copy-pasteable where possible]
**First seen:** [project], [YYYY-MM-DD] — [the concrete incident]
```

---

# === ESTABLISHED — "we know" ===

## RunCloud

### RunCloud hybrid (Nginx+Apache) serves static files by the file's "other"-read bit — `.htaccess` does NOT protect them
**Symptom / When:** Project docs left in the public web root (`docs/`, `assets/`, `notes/`, a stray `.html`/`.png`/`.csv`) stay reachable at HTTP 200 even with a per-dir `Require all denied` `.htaccess` and a root `<FilesMatch>` deny. Confusingly the protection is **partial** — most files 403, but a few static ones leak, and **not by extension** (one `.png` leaks while sibling `.png`s 403). Reads like a random misconfiguration.
**Why:** On RunCloud's **hybrid** stack Nginx is the front and serves static assets **directly**, never consulting Apache — so `.htaccess` (an Apache mechanism) is bypassed for anything Nginx serves itself. The access decision for those files falls to the filesystem **"other" (world) read bit**: mode `644` (`o+r`) → served; mode `640`/`600` (`o-r`) → 403. Files that happen to be proxied to Apache get 403 from `.htaccess` — which is exactly why the leak looks random. **The discriminator is the permission bit, not the extension and not the `.htaccess`.**
**Fix:** Make protection mechanism-independent with filesystem perms:
```bash
chmod -R o-rwx docs assets notes && chmod o-rwx CLAUDE.md
```
Owner (the PHP-FPM/Apache user) keeps full access, so the site and any Remote-SSH session are unaffected. Verify by curling each file — a `200` means it's still leaking. Note `wp-config.php` returning 200 with a **0-byte body** is fine (PHP executed, no source emitted); `.htaccess` is 403 because nginx-rc blocks dotfiles natively regardless of perms.
**Also:** the per-webapp Nginx vhost (`/etc/nginx-rc/conf.d/*.conf`) is root-owned and unreadable to the webapp user, and the RunCloud API does not expose it — editing Nginx is not an available lever without panel or root access. Perms need none of them.
**First seen:** TAB, 2026-06-27 — a cutover left project docs in the web root; 3 of ~100 files (two `.html` + one `.png`, all `644`) leaked at 200 while `600` favicons were already 403. `chmod -R o-rwx` flipped all to 403 with zero site impact, and superseded a planned "needs an Nginx deny rule via the panel" workaround.

### RunCloud Let's Encrypt via API — `environment` is `live` (not `production`), and issuance takes minutes (poll ≥15)
**Symptom / When:** `POST /servers/{id}/webapps/{wid}/ssl` to issue LE. (1) `"environment":"production"` → 422 "selected environment is invalid." (2) After a valid request, `validUntil` stays `null` and 443 stays closed for several minutes — which reads as failure.
**Why:** RunCloud's LE env enum is `live`/`staging`. And issuance (HTTP-01 validation + nginx deploy) is an **async background job** that commonly takes ~8–10 minutes, not seconds — a 5-minute poll times out before the cert lands.
**Fix:**
```json
{"provider":"letsencrypt","authorizationMethod":"http-01","environment":"live","enableHttp":true,"enableHsts":false}
```
Required: `provider`/`enableHttp`/`enableHsts`, plus `authorizationMethod` and `environment` for LE. **Poll `validUntil` for ≥15 minutes before assuming failure.** The webapp-level cert is a SAN over ALL attached domains, so every attached hostname must resolve to the box for HTTP-01 — or use `dns-01`.
**First seen:** TAB, 2026-06-27 — guessed `production` (422), then a 5-minute poll timed out and triggered a **premature DNS rollback and an ~8-minute https blip**. The cert had actually issued fine ~9 minutes in. The rollback was the outage, not the cert.

## Cloudflare

### A DNS record flipped to proxied needs Universal SSL **active** first — and a zone-scoped token can't read cert status
**Symptom / When:** You flip apex/www to proxied (orange) and visitors get a cert error. Or you want to confirm the edge cert is ready but `GET /zones/{id}/ssl/certificate_packs` returns 9109 "Unauthorized."
**Why:** Cloudflare's Universal SSL edge cert provisions **asynchronously** after a zone goes active (minutes to hours). Proxying before it's active serves no valid edge cert. A token scoped to DNS + ZoneSettings + ZoneRead lacks the SSL/cert read scope, so you cannot check status via API — you need the dashboard.
**Fix:** Confirm Universal SSL is **Active** (dashboard → SSL/TLS → Edge Certificates) before flipping to proxied, or canary on `www` first. **Correct sequence with origin-cert-via-LE:** point apex **grey** → issue LE at the origin → confirm origin https → *only then* flip to orange + SSL Full (strict). Verify the proxied result with `--resolve` to a CF anycast IP (`server: cloudflare` + `cf-ray` present).
**First seen:** TAB, 2026-06-27 — held the orange flip until Universal SSL was dashboard-confirmed; the scoped token couldn't read cert_packs.

## Cutover

### Verifying a cutover *from the origin box itself* — the box's own DNS cache still points at the OLD origin
**Symptom / When:** Post-cutover, `curl https://thedomain/` **from the migrated box** returns the OLD site (stale content, old headers), while real users and `dig @8.8.8.8` show the new origin. Leads to hours chasing a phantom "stale cache" (FastCGI? Cloudflare?) that does not exist.
**Why:** During cutover the box queried the apex while it still pointed at the old IP, and cached that (systemd-resolved / the record's TTL). Bare `curl` from the box keeps hitting the old origin until the cache expires. **Nothing to do with any page cache** — which is exactly why it burns so much time.
**Fix:** Never trust bare `curl` from the box during a cutover.
```bash
curl --resolve domain:443:<NEW_ORIGIN_IP> https://domain/   # the new origin directly
curl --resolve domain:443:<CF_ANYCAST_IP> https://domain/   # the public/edge path
getent hosts domain                                          # what the box actually resolves
```
`resolvectl flush-caches` clears it, but `--resolve` is the reliable habit. Note the inverse also matters post-launch: once the cache clears, bare curl becomes trustworthy again — re-verify rather than carrying the workaround forever.
**First seen:** TAB, 2026-06-27 — a long false-alarm "RunCloud/Cloudflare is serving a stale homepage" investigation; the box's resolver simply still pointed the apex at the old box.

### Verify a served static asset via its `?ver=` URL — the BARE file URL is served from a stale static cache
**Symptom / When:** You edit the child theme `style.css`, `curl` the plain file URL to verify, and get the OLD content — even though the on-disk file is correct and the page itself shows the new styling.
**Why:** Nginx/RunCloud static-file caching serves a cached copy for the bare URL. WordPress enqueues the stylesheet with `?ver=filemtime(...)`, so the *page* always requests a fresh build when the file changes — but a bare `curl …/style.css` can hit the stale cache and mislead verification.
**Fix:** Verify against the exact URL the page references (grep the page for `style.css?ver=…` and curl that), or append a cache-buster (`?cb=$RANDOM`). Trust the on-disk file and the versioned URL, not the bare URL.
**First seen:** TAB, 2026-06-25 — chased a "CSS not applying" ghost for several steps; the versioned URL had served the correct CSS all along.

## RunCache (RunCloud Hub successor)

### RunCache flushes rewrite rules on activation → every CPT page 404s
**Symptom / When:** After activating/installing RunCache (or possibly a later cache op), **every custom-post-type URL 404s** — through the CDN AND at origin — while regular pages still 200. `wp rewrite list --format=count` shows a truncated set (e.g. **95**, missing the CPT rules) although `permalink_structure` is correct and the posts exist.
**Why:** The plugin calls `flush_rewrite_rules()` on a request where the core plugin's CPTs aren't registered yet, regenerating `rewrite_rules` WITHOUT them. **The SEO spine silently goes down** — and a cache plugin is the last thing you'd suspect.
**Fix:** `wp rewrite flush` (restores the full set — e.g. 95→194).
> **Standing rule: after ANY RunCache settings or cache operation, spot-check that a CPT URL returns 200.** The rewrite count is the canary.
**First seen:** TAB, 2026-06-28 — RunCache activation between sessions 404'd every service/location/project page; caught during routine cache verification, not by monitoring.

### RunCache native page-cache exclusions are enforced at NGINX and synced via the RunCloud API — editing the WP option alone doesn't reach nginx
**Symptom / When:** You add a URL/cookie exclusion but the page still serves cached (`x-runcache-status: HIT`); or you can't find where the rules live (there's no `/page-cache` endpoint on the RunCloud v3 API for a hybrid webapp).
**Why:** `runcloud-native` caching is FastCGI/nginx-level (config `runcache-nginx.conf`). Rules compile into nginx and are pushed via the RunCloud API using `rcapi_key/secret` stored in the `runcache_runcloud_settings` option. A plain `update_option` never regenerates nginx.
**Fix:** Edit via the plugin API, then push:
```php
( new \RunCache\Core\Rules() )->save_settings( $s );  // exclude_url_mch = regex array, e.g. '/request-a-quote.*'
```
```bash
wp runcache-native send-settings   # pushes to nginx — without this, nothing changes
```
Defaults already exclude `/wp-admin/`, `/login.*`, `wp-json`, `feed`, `sitemap`, cart/checkout/account + bypass the logged-in cookie. **Verify origin-direct** (`curl --resolve …:<origin-ip>`): form/auth pages should return `x-runcache-status: BYPASS`.
**Why it matters:** cached HTML on a form page serves **stale form nonces** — the form silently fails for real users.
**First seen:** TAB, 2026-06-28 — excluding the quote/thank-you/contact/auth pages.

### RunCache v0.1.0 — BOTH the object-cache flush and `wp cache flush` are broken (`array_merge()` error), and stale reads will silently clobber your work
**Symptom / When:** Three escalating faces of one bug. (1) `wp cache flush` prints `[RunCache][error] Redis flush error: array_merge(): Argument #2 must be of type array, string given`. (2) After a bulk `wp search-replace` on posts, `get_post()` / rendered pages read **stale** while `wp db query` shows the DB is correct. (3) Worst: a later `wp_update_post()` on a search-replaced post **writes the stale object back**, silently reverting the fix.
**Why:** RunCache 0.1.0's Redis dropin has a broken group/full flush. Per-key get/set/delete work fine, so normal site operation is unaffected — which is why it hides. But `wp search-replace` writes **direct SQL** and its cache invalidation no-ops, so Redis keeps the OLD post object. `get_post()` then returns that stale object, and `wp_update_post()` persists it over your SQL fix. **Reads diverge by path:** `wp db query`/`LOCATE()` hit MySQL (fresh); `get_post`/`get_posts`/page renders hit Redis (stale).
**Fix:**
```bash
# force-flush Redis BEFORE any get_post-based edit or verification
PW=$(grep -oP "RUNCACHE_REDIS_PASSWORD',\s*'\K[^']+" wp-config.php)
redis-cli -a "$PW" -n 0 FLUSHDB     # single-app box — all keys are runcache*
wp runcache purge
# then re-check a CPT URL returns 200 (rewrite canary)
```
**Verify content writes with `wp db query` + `LOCATE()` (MySQL-direct), NOT `get_post` or rendered HTML**, until caches are flushed. Sequence edits so any `wp_update_post` on a post runs *before* a search-replace on that same post — or flush between them. For page-cache clears use `wp runcache purge`, not `wp cache flush`.
**First seen:** TAB, 2026-06-28 (the flush error, thought benign) → **TAB, 2026-07-15** (the real cost): an internal-link cleanup where search-replace fixed a URL, then a later `eval-file` did `get_post` → `wp_update_post` and restored the broken one. `LOCATE()` proved only that post was dirty. Re-running the replace + `FLUSHDB` (10,632 stale keys) + `runcache purge` resolved it.

### RunCache Redis object cache: config lives in `provider.redis` → `RUNCACHE_REDIS_*` consts; the legacy `RCWP_*` fallback won't pick up a plain host+password
**Symptom / When:** `wp runcache enable-object-cache` reports success but no dropin installs, no `RUNCACHE_REDIS_*` consts appear, Redis stays at 0 keys and `wp_using_ext_object_cache()` is none — even with `RCWP_REDIS_HOST`/`RCWP_REDIS_PASSWORD` in wp-config.
**Why:** `enable` uses the existing (empty) `provider.redis`. The `RCWP_*` fallback needs BOTH `RCWP_REDIS_PASSWORD` **and** `RCWP_REDIS_DOMAIN`, and treats the password as a `[username,password]` array — a plain string plus a missing DOMAIN makes it bail silently.
**Fix:** Set the provider explicitly:
```php
( new \RunCache\Core\ObjectCache() )->save_settings([
  'enable' => true, 'object_cache_type' => 'redis',
  'provider' => [ 'redis' => [ 'host'=>'127.0.0.1','port'=>'6379','password'=>$pw,'key'=>'runcache:tab:' ] ],
]);
```
Writes the consts and installs `object-cache.php`. Redis password = `/etc/redis/redis.conf` `requirepass` (sudo). Verify `wp_using_ext_object_cache()` and a climbing `dbsize`.
**⚠️ Before a second app shares Redis:** set a `maxmemory` cap and an eviction policy — a default install is uncapped `noeviction`.
**First seen:** TAB, 2026-06-28 — bare `enable-object-cache` no-op'd until `provider.redis` was set.

## Performance

### ShortPixel on Bricks behind Cloudflare: use `deliverWebp=1` (Global `<picture>`) and leave SPIO's own CDN off
**Symptom / When:** WebP/AVIF generate but aren't delivered to Bricks-rendered images, or images get mis-cached at the CF edge.
**Why:** Three interacting facts. The `.htaccess`/Accept-header delivery mode relies on `Vary`, which Cloudflare mis-caches. Bricks renders images **outside `the_content`**, so the WP-hooks mode misses them entirely. And SPIO's own CDN (`useCDN`) rewrites image URLs off the CF zone — redundant, and it loses edge caching + WAF.
**Fix:** `spio_settings`: `deliverWebp=1` (Altered/**Global** output-buffer → rewrites the whole page to `<picture>`), `createWebp/createAvif=1`, `useCDN=false`. Verify: the page emits `<picture>` with `image/avif` + `image/webp` sources, and image URLs stay on-domain (CF edge-caches them, MISS→HIT).
**First seen:** TAB, 2026-06-28 — Global picture-rewrite was the only mode that both caught Bricks images and stayed CF-safe.

### Perfmatters RUCSS (Remove Unused CSS, async) regresses LCP on an already-lean Bricks site
**Symptom / When:** Enabling "Remove Unused CSS" (async) makes mobile LCP **worse** on most templates, even though TTFB and CLS are fine.
**Why:** Async inlines the used-CSS then loads full stylesheets asynchronously. On a lean site (small render-blocking CSS, fast TTFB) the async full-CSS lands **after** first paint and pushes the LCP element's final styling later — you're paying async's cost without having the problem it solves. It also **strips classes injected at runtime by JS**.
**Fix:** On a lean Bricks+ACSS site RUCSS usually isn't worth it — measure per template and revert if net-negative. Keep `minify_css/js` regardless (safe win). If you do keep RUCSS, exclude: **any CSS whose classes are added at runtime** (scroll-reveal/animation stylesheets — otherwise elements stay permanently hidden), form CSS, and the conversion pages.
**First seen:** TAB, 2026-06-28 — RUCSS regressed LCP on 3 of 4 templates (1.8→3.4s, 2.9→4.8s); reverted, kept minify.

### PSI lab (mobile) is unreliable under load — confirm with desktop + objective metrics + an A/B before "fixing" a regression
**Symptom / When:** PSI mobile shows a large perf/LCP regression (88→69, LCP 5–6s) with "Something went wrong" errors and big run-to-run swings — while objective signals say the site is fast (TTFB ~40ms, LCP images 0ms, all assets 200, CLS ~0).
**Why:** PSI's 4× mobile CPU throttle plus transient infra issues produce inflated, noisy LCP that resource timing doesn't corroborate. Easy to misdiagnose and then "fix" something that was never broken — the fix is the risk here, not the symptom.
**Fix:** Cross-check with **desktop** PSI (low throttle) + objective metrics, and run an **A/B** (toggle the suspected cause) before acting. Desktop green + A/B doesn't move LCP ⇒ lab noise → trust **CrUX field data** (GSC, ~28 days).
**First seen:** TAB, 2026-06-28 — a post-cache mobile read tanked to ~70/LCP 5–6s; desktop was 96/99 (LCP <1.5s) and an animations on/off A/B left LCP unchanged, confirming a lab artifact.

---

# === PROJECT SECTION — "we learned" ===

*Empty at kickoff. New hosting/cutover/performance gotchas discovered during this project append below, in the entry format above. At go-live these are reviewed and the validated ones fold into the established section of the master.*
