# Architecture

The build follows **`flatsite.md`**: structure, `$site` config and page registry, partials, security, headers, staging
and launch. This file adds only what a *port* decides on top of that. Where the first port's retro overrode the
original handoff, it says so.

## Content

- **Sitewide facts** (name, phone, address, hours, email, socials) live in `$site`, per `flatsite.md`.
- **Lists that feed more than one place go in one data file** (`includes/data.php`): hours, prices, team members,
  reviews, job openings. The pages *and* the JSON-LD read from it, so a price or an hour changes in one place.
  *Retro over handoff:* the handoff said "no content data layer". The first port needed one on day one, because hours
  appear in the header, the hours page, the footer and the schema.
- **Copy unique to one page stays in that page's file**, passed into components as arguments.
- **Includes share file scope.** On the third port `header.php`'s `foreach ($site['pages'] as $key => $p)` overwrote a
  template's `$p` record; the fatal came after the 200, so only the PHP error log showed it. Name records for what they
  are (`$project`), and make smoke tests read the error log, not just status codes. A template that finds no record
  includes a shared not-found body; including `404.php` re-requires the bootstrap.
- **A custom post type becomes a data-file list plus one thin page file per item** where the old site had a URL
  per item (a 3-line file that sets the slug and includes a shared profile partial).

## Head data

Each page sets `$page` before the bootstrap (`flatsite.md` → Page file). Keys map one-to-one to SEO-plugin fields
(title, description, canonical, robots, OG image), which is half of the way back to WordPress (`reversibility.md`).
JSON-LD is **one `ld+json` script holding one `@graph`**, built from `$site` and the data file: the business node,
`WebSite`, `WebPage`, plus `Person` and similar per page. Carry the old site's schema types forward; the parity check
reports any that go missing.

## Components

Partials with arguments (`partial('hero', [...])`, `flatsite.md` → bootstrap). One BEM block per partial; utilities
only for one-off layout. **Fill `components.json` as each partial is built**, with its arguments and its WordPress
equivalent. That's the reverse map.

## CSS

One stylesheet, tokens first (brand colors, type scale, spacing), then blocks. Cascade layers
`reset, tokens, core, components, utilities`; root font size 100%.
*Open:* the handoff made a vendored **mpd-core** the base. The first port built its own token layer from the brand
system and never used mpd-core, so that default is **unvalidated**. Ask Michael at Gate 2 on the next port.

## Motion

CSS transitions plus a small IntersectionObserver for scroll reveals; `prefers-reduced-motion` handled once, in the
CSS and the observer. **No GSAP by default.** *Retro over handoff:* the first port dropped GSAP entirely and lost
nothing. Add it, self-hosted and on the pages that need it only, when a timeline or sequence earns it.


**No-JS fallbacks key on `@media (scripting: none)`, not a `.no-js` class.** Deferred JS removes the class after first
paint, so anything laid out differently for no-JS moves: on the third port the mobile menu sat in flow until `main.js`
ran (CLS 0.3 on mobile). Scroll reveals likewise go under `@media (scripting: enabled)`.

## Images

- Masters in `src/img/`, never in `public/`. A build script (`sharp`, run by hand with nvm's Node; system Node is too
  old on some boxes) writes AVIF/WebP/JPEG sets at several widths to `public/assets/img/build/`. **Commit the output.**
- **Name built files by content hash.** RunCloud caches `/assets/` for 30 days, so a changed image needs a new URL.
- Every image goes out as a `<picture>` with `width`, `height` and a `sizes` that matches the layout.
- Harvested masters: `harvest.sh` copies the best one (an image optimizer's pre-compression backup, else WordPress's
  unscaled original).
- ⚠️ If the build empties its output folder first, **every image 404s until it finishes** on a live site. Run it
  off-hours, or build to a temp folder and swap.

## Fonts

Self-hosted `woff2`, subset to the characters and weight axes actually used (the first port went from 113 KB to
57 KB). Preload them, use `font-display: optional` with metric-matched local fallbacks so the swap can't shift layout.
Font files carry no content hash, so **rename the file if it changes**. Flag Adobe Fonts or any licensed source at
Gate 1 instead of downloading.

**Synthetic bold.** If the old theme asked for a weight a font doesn't ship (a single-weight display face set to a
heading weight of 600), the browser fakes the bold, and that can change the look entirely: on the second port a
triple-line outline face rendered solid. Compare the live render with the face drawn at its own weight before
matching it, and keep `font-weight` + `font-synthesis: weight` when the faux bold *is* the brand look.

## Video

A background video is the hero's motion, not its LCP: make a poster frame the LCP image (preloaded), and load the
video after `load`, never under `prefers-reduced-motion` or Save-Data. Pick a cut by viewport in JS (portrait for
phones, 1280, 1920); `<source media>` isn't reliable. Encode H.264 with no audio track and `+faststart`; name files by
content hash like images. A 38 MB original is not a web asset: ask for cuts (the second port got 10/12/23 MB).


Check the first second before picking the poster: a film that **opens on a fade from black** makes frame 0 a black
LCP image. Start the cuts after the fade (`-ss`) and take the poster from the same frame, so the handoff doesn't jump.
Probe the audio: a track at a few kb/s is silence, and the cuts can drop it. No ffmpeg on the box: `ffmpeg-static` as a
dev dependency, run by hand like `sharp` (third port).

## Third parties

- **Analytics:** load only on the production host, and as a file (`assets/js/ga.js`), not Google's inline snippet,
  so the CSP needs no `'unsafe-inline'`.
- **Maps:** prefer a self-hosted Leaflet with OpenStreetMap tiles, loaded only when the map nears view. OSM's tile
  servers refuse requests with no Referer, so `Referrer-Policy` must stay `strict-origin-when-cross-origin`.
- Every third-party host needs a CSP entry, or it fails silently.

## Budget

Under 150 KB of CSS + JS + fonts on first load, mobile LCP under 2 s, CLS 0, Lighthouse 95+ on every page. Measure with
Lighthouse on the staging host. Analytics adds ~150 ms of TBT on production that staging won't show.

## Visual checks

Headless Chrome (`~/tools/browser/shot`, where installed) at 390 and 1440 before calling any visual change done.
Full-page captures need `--reduced-motion`, or scroll-revealed blocks come out blank. `shot` blocks analytics by
default, nudges the page so delayed CSS loads, and survives pages that never go network-idle (video). It lives only
on the box (`~/tools/browser/`, not in a repo; the 11/16/2026 changes kept a `.bak` beside it).
