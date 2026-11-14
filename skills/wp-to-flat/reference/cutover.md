# Cutover

The launch itself is **`flatsite.md` → Launch → Path A (domain move)**: the new webapp was built under a staging
subdomain, and the production domain moves onto it. This file covers only the WordPress-specific parts around that.
Don't duplicate the runbook here.

## Before (the day before is fine)

- **Back up the WordPress site, twice over:** a Duplicator package to off-site storage, plus a DB export and a files
  tarball on the server (mode 600, outside any webroot). Confirm the off-site upload completed.
- `parity-check.js` against the staging host: zero FAILs, every REVIEW answered.
- `scrub-lint.sh`: clean.
- Old sitemaps, `?p=` IDs and media paths are in the redirect rules (`url-map.js`), and the rules were exercised in a
  throwaway subfolder (`redirects.md` → Verifying).
- Pick a closed or quiet hour: HTTPS on the apex is down for the minute between moving the domain and reissuing SSL.

## The move

RunCloud won't leave a webapp with zero domains, so before the production domain can leave the WordPress webapp it
needs a **placeholder domain with no DNS** (e.g. `old-<site>.michaelparksdesign.com`). Record which name it is.
⚠️ Never give that placeholder DNS later, and never reuse it for anything: a record pointing at it brings the dark
WordPress site back online. Use a fresh name for a test fixture.

## After

- Rerun `parity-check.js` against **production** (now including the www rule).
- Analytics: confirm a live `page_view`; re-mark any key events.
- Search Console: resubmit the sitemap; watch Coverage for 404s over the next weeks.
- **Turn off the WordPress site's cron** (its RunCloud cron job, via `PATCH …/cronjobs/{id}/status`, then read
  `enabled` back). It keeps running against a site nobody visits otherwise.
- Its object cache: a dark site keeps its Redis namespace. If a copy of it is ever restored elsewhere, give the copy its
  own (`fixture.md` → step 7).

## Decommission (after a 2–4 week rollback window)

1. A final Duplicator package to off-site storage, and a final DB export to `~/backups/<site>/`.
2. Export anything the client may want later: form entries, comments, user list.
3. Delete the cron job, then the webapp (it cascades to its database).
4. Keep the placeholder's name noted, in case old links to it ever surface.
