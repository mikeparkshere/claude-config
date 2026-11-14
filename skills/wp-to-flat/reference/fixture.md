# Test fixture: a restored copy of the retired WordPress site

The skill's scripts are validated against a restored copy of a finished port's old WordPress site, kept permanently on
its own webapp and subdomain. The finished flat build is the answer key: every page, heading, image and link in it
should trace back to the fixture's reduced output.

Before a session works against the fixture, confirm it's still in this state. If any of it isn't, stop and tell Michael.

| Check | How |
| --- | --- |
| Own subdomain on Michael's domain, own webapp of type `wordpress` | the retired site's webapp may still exist (rollback window) and hold a **placeholder domain**; never reuse it, pick a fresh name |
| Never indexed | `X-Robots-Tag: noindex, nofollow` header + `blog_public` 0, both forced by an mu-plugin |
| No outbound mail | mu-plugin returns `false` from `pre_wp_mail` and logs the attempt; test with `wp eval 'var_dump(wp_mail(...));'` |
| No cron | `DISABLE_WP_CRON` true and no RunCloud cron job for the webapp |
| No analytics | the SEO or analytics plugin's tracking turned off; rendered pages carry no tag ID |
| No backups out | backup plugin schedules deactivated (they carry the original's remote storage credentials) |
| Own object-cache namespace | RunCloud Hub `wp-content/rcwp-redis-config.php` set to the fixture's domain and its own prefix (see below) |

Basic auth isn't required (Michael, 11/13/2026): these are low-traffic brochure sites with nothing to hide beyond search.

## Restoring one (first done 11/14/2026)

1. **Get the package without a browser.** Duplicator Pro's CLI has no download command, but its storage adapters do the
   work for the admin's "download from remote" button. From the *original* site, `wp eval-file` a script that loads the
   backup (`Duplicator\Package\AbstractPackage::getById`), gets the remote storage
   (`Duplicator\Models\Storages\AbstractStorageEntity::getById`), reaches its protected `getAdapter()` by reflection,
   and calls `copyFromStorage($file, $dest, $offset, $length)` in 16 MB chunks into a private folder. Compare sizes
   with `fileSize()`. 121 MB took 13 s from B2.
2. **Unpack without the installer wizard:** `Duplicator\Libs\DupArchive\DupArchiveExpandBasicEngine::expandDirectory($daf, '', $dest, '')`.
   It doesn't verify per-file hashes, so `diff -rq` the result against the original webapp if it still exists. Root
   `.htaccess` and `wp-config.php` are stored separately under
   `dup-installer/dup_descriptors_*/orig_files/` (`source_site_htaccess`, `source_site_wpconfig`); the database is
   `dup_descriptors_*/db_dumps/*-dump.sql`.
3. **Create the webapp** with `POST …/webapps/wordpress` (never `/custom` for WordPress). It installs stock WordPress
   with its own database and config; type `wordpress` already sets `open_basedir`. Tune FPM by `PATCH …/settings/fpmnginx`.
   Issue SSL once DNS resolves.
4. **Swap in the site:** clear the stock files, rsync the extract (leave out `dup-installer/`, the archive index, and any
   notes or `CLAUDE.md` that would land in the public webroot), restore the original `.htaccess`. Build `wp-config.php`
   from the original with the new webapp's DB credentials, salts and `WP_CACHE_KEY_SALT`, and `WP_ENVIRONMENT_TYPE`
   `staging`.
5. **Block mail before the database goes in** (the mu-plugin above).
6. **Database:** the Duplicator dump uses `CREATE TABLE IF NOT EXISTS` with no drops, so `wp db reset` the new (stock)
   database first, then `wp db import`. `search-replace` the https, http, www and JSON-escaped (`https:\/\/`) forms of the
   old origin, `--skip-columns=guid --precise`. Leave bare-domain mentions in copy alone.
7. **Object cache:** RunCloud Hub's Redis config came with the files, so the copy shares the original's namespace and
   serves its cached URLs. `wp cache flush` once (selective: only that namespace), then set the copy's own domain and
   prefix in `rcwp-redis-config.php`, and confirm the two webapps' `selective_key` values differ.
8. **Lock down** per the table, then check from outside: every page 200, 404 page 404, noindex header and meta,
   no tag ID, no links to the production origin.
