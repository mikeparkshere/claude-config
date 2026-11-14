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
