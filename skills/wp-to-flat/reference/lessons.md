# Lessons

One dated entry per port, written at the skill retro (phase 12). Client-neutral: no names, domains or project
details. Each lesson says what changed in the skill because of it, or that nothing did and why.

---

## 2026-11 — first port (Bricks + ACF + SEOPress brochure site, 4 URLs → 6)

The skill was written *after* this port, from its retro, then validated against a restored copy of the old site.

**Build**
- A data file earned its place on day one: hours, prices, team and reviews appear on several pages and in the schema.
  → `architecture.md` overrides the handoff's "no data layer".
- GSAP was planned and dropped; CSS plus an IntersectionObserver did everything. → No GSAP by default.
- mpd-core was planned and never used; the site built its own token layer from the brand system. → Left **open**:
  ask at Gate 2.
- Apache sits behind nginx and never sees TLS, so mod_dir's trailing-slash redirect said `http://`. → `DirectorySlash
  Off` plus an explicit https rule; every redirect target hard-codes `https://%{HTTP_HOST}`.
- A redirect target with a `#fragment` lost UTM tags: Apache appended the query after the fragment. → `url-map.js`
  emits a query-preserving twin rule.
- The image build emptied its output folder first, so a rebuild on the live site 404'd every photo for three minutes.
  → `architecture.md` warns; build to a temp folder and swap next time.
- Staging privacy by host-keyed `noindex` in the repo worked better than server-level auth (no panel work, nothing to
  remove at launch). → Folded into `flatsite.md`.
- `.htaccess` redirects caught old media paths, because nginx hands missing static files to Apache on a hybrid
  webapp. → No nginx panel config needed for redirects.

**Harvest and reduce**
- `--skip-plugins` hid the custom post type, so its pages vanished from the harvest. → Load WordPress normally.
- CPT pages had no Bricks content of their own; a template with a post-type condition drew them. → `reduce.js`
  resolves templates, headers and footers by Bricks's own condition rules.
- The site registered its own dynamic tags. → The harvest asks Bricks to render every tag in each post's context.
- An SEO-plugin switch left the old plugin's meta behind, and title templates carry unresolved variables. → Parity
  uses rendered pages only.
- Schema extraction dropped the first type on each page, found on the fixture's first rendered run. → Fixed; the
  extractor is shared by reduce and parity.

**Validation**
- The generated redirect plan matched the hand-written production rules on 22 of 25 tests; the 3 others were an
  improvement (old `?page_id=` links to their own page, not home), applied to production after the parity check flagged
  them.
- Parity against the finished site: 0 FAIL, 8 REVIEW, all deliberate and logged in the project's decision log.

**Fixture**
- A restored WordPress copy shared the original's Redis object-cache namespace, because RunCloud Hub's config travels
  in `wp-content/`. → `fixture.md` step 7; fleet KB 04.
- The retired site's webapp held a no-DNS placeholder domain; pointing DNS at it brought the dark site back. →
  `cutover.md`: never reuse the placeholder.

---

## 2026-11 — second port (Bricks + ACSS + SEOPress + WS Form brochure site, 9 URLs → 6, three forms)

The first port run end to end by the skill: harvest to cutover and decommission in one day.

**Harvest and reduce**
- 166 orphaned Bricks elements (parent deleted in the builder) read as hidden sections, duplicate blocks and a
  "missing" image. Bricks never renders orphans. → The reducer skips and flags them.
- Header and footer had no conditions and were reported unused; Bricks applies them as default templates. → The
  harvest records "disable default templates"; the reducer applies the default.
- A home-page photo strip came from attachment query loops filtered by a media-folder taxonomy; 17 images were
  missed. → `harvest.php` resolves attachment loops; `harvest.sh` sweeps rendered pages for uploads images as a
  safety net; `reduce.js` adds them to `media.json` as `rendered-only`.
- WS Form fields came back empty (`wsf_field` has no `form_id`). → Read from `wsf_form.published`, actions included.
- List descriptions (a drink and its ingredients) were dropped. → Kept.
- Rank Math redirects came through as serialized PHP, one of them trashed. → Parsed; inactive skipped.
- Live pages first-painted unstyled: an optimizer's "used CSS" snapshot predated a builder update and the real
  stylesheets waited for user interaction. Captures inherited it, and a background video stopped `networkidle`
  from ever arriving. → `shot` nudges the page, falls back from network-idle, and blocks analytics (the first
  captures had logged page views on the live site).

**Build**
- A data file again earned its place: menus, gallery, hours, form definitions.
- One definitions file for three forms (markup and validation from the same source); a conditional field in CSS
  `:has()`; honeypot plus the old reCAPTCHA v3 key. → `forms.md` rewritten as tested.
- A single-weight display face looked solid only because the old theme asked for weight 600 and the browser faked
  it. → `architecture.md`: check for synthetic bold before matching a face.
- The hero video was 38 MB; the client supplied cuts. → `architecture.md` → Video.
- White on the brand pink failed contrast at button size; 19 px at weight 700 counts as large text and passed
  without touching the color.
- `partial()` didn't import `$secrets`, so the form rendered without the captcha key; caught at cutover by watching
  which hosts the page loaded. → Skeleton fixed.

**QA and cutover**
- The scrub lint passed sitemap URLs whose pages didn't exist (`php -S` fallback). → Router + missing-page probe.
- Parity: 0 FAIL, 15 REVIEW, all new H1s and meta descriptions (the old inner pages had neither).
- The old site's mail key was dead and its transport silent; booking emails had stopped weeks before. Found only
  when the new form failed on staging. → SKILL.md phase 1 and `forms.md`: test the mail credential at harvest.
- Production and staging on the same server: cutover was a webapp domain move plus an SSL reissue, ~30 s of HTTPS
  downtime. Decommissioning by API also removed the database and its user.

---

## 2026-11 — third port (Elementor + Elementor Pro + Rank Math + WS Form brochure site, 26 URLs → 18, two forms)

The first non-Bricks port. Harvest to cutover in one day; 10 project pages from one template and a data file.

**Scope**
- The inventory said "not a candidate": a blog, and 26 URLs. The blog was dormant (newest post two and a half years
  old) and the CPT was a title plus an ACF gallery per item. Dropping the blog with 301s and rendering the CPT from a
  data file brought it inside scope. → Nothing changed: Gate 1 is where that call belongs, and it was made there.
- The old site ran as a second system user, and `runcloud` couldn't read its webroot. → `harvest.md`: harvest as the
  owner, move the output.

**Harvest and reduce**
- No Elementor reducer. → `reducers/elementor.js`, Elementor in `harvest.php`, its theme-builder templates mapped
  onto the existing picker in `reduce.js`. Dynamic tags are resolved by the reducer (Elementor doesn't render them
  at harvest the way Bricks does).
- Non-image attachments (video) lost their path, because `wp_get_original_image_path()` returns false for them. →
  Fixed in `harvest.php`.
- Video under a root `/assets/` folder, outside uploads, was invisible to the media walk. → `harvest.md`: copy by hand.
- The mail check returned 401 for a working key: Post SMTP stores keys base64-encoded, and a send-only key 401s on
  every read endpoint. → `forms.md`: decode, then test with a test-mode send.

**Build**
- The hero film opened on a fade from black, so frame 0 made a black LCP poster. → `architecture.md` → Video.
- `.no-js` fallbacks laid the mobile menu out in flow until deferred JS removed the class: CLS 0.3. → Keyed on
  `@media (scripting: none)`; `architecture.md` → Motion.
- A header loop variable overwrote the project template's record (includes share file scope). The fatal came after
  a 200, so only the PHP error log showed it. → `architecture.md` → Content: name records specifically; smoke tests
  read the error log.
- The old site's captcha was reCAPTCHA **v2**; the skill's form flow assumes v3. Moved to Cloudflare Turnstile. →
  `forms.md` → Spam. Headless Chrome never gets a Turnstile token, so the last form test is a real-browser send.
- Every image lacked alt text (4 of 140 had any). Alt was written from contact sheets of the photos; project meta
  descriptions came from the same look. Slow, but the old boilerplate description repeated ×10 was a parity REVIEW
  and an SEO loss.

**QA and cutover**
- Parity found one FAIL: a plugin redirect to a slashless target chained. → `url-map.js` adds the slash.
- The scrub lint 404'd every templated URL (`php -S` ignores `.htaccess`). → Its router follows internal rewrites.
- Two staging certs recorded `validUntil` and never deployed: the webapp was in basic SSL mode and they'd been issued
  at the domain level. → `cutover.md`, `flatsite.md`, KB 04.
- RunCloud's placeholder `index.html` shadowed the home page; the KB already had it. → `flatsite.md` scaffold step.
- Cutover HTTPS gap: ~18 s, then ~10 s to retire staging. Parity on production 0 FAIL; CSP enforcing after a
  production check with GTM and Turnstile loaded.
- The old site had no off-site backup storage. → `cutover.md`: local DB + tarball at cutover; off-site before
  decommission.
