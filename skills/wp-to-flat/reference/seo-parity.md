# SEO parity

```bash
scripts/harvest.sh --path <wp root> --out <dir> --base-url https://<old host> --screens   # old rendered pages
node scripts/reduce.js <dir> && node scripts/url-map.js <dir>                             # plan + redirect tests
node scripts/parity-check.js <dir> --new https://<new host> [--staging]
```

Writes `reduced/parity-report.md`; exits 1 on any FAIL. **Gate 4 needs zero FAILs, and every REVIEW answered**: either
Michael confirms it's an intended change (point at the decision log entry) or it gets fixed.

## What it checks

| Kept page (old URL → new URL) | Level |
| --- | --- |
| new page not 200; canonical missing or not self; noindex on production; not exactly one H1; title or description missing; generator meta | FAIL |
| title, description, H1, og:title, og:description differ from the old page | REVIEW |
| og:image missing or not 200; a schema type the old page had is gone | REVIEW |

New pages (in the new sitemap, not on the old site) get the FAIL rules. Kept pages missing from the new sitemap FAIL.
Every row of `redirect-tests.tsv` must give the expected status and Location, and each 301 must land on a 200 in
one hop. The www rule is only testable on the production host, so it's skipped against staging.

Comparisons ignore whitespace and curly-vs-straight quotes. Old values come from the **rendered** old pages
(`raw/html/`), never stored plugin meta: plugin title templates and leftover meta from a plugin switch make stored values
wrong.

## Reading the report

A REVIEW is not a defect. On the first port all eight were deliberate and logged: a fact corrected in the
description, a rewritten bio, headings moved to Title Case, a new hero H1, H1s trimmed to a name. The report's job is to
make every one of them a decision someone made, not a drift nobody noticed.

The first run also caught a real gap: the redirect plan sent old `?page_id=` links to each page, but the live rules
still sent them all home. That's a FAIL until the rules or the plan change.

Plain HTTP, no JavaScript, so running it against production fires no analytics.
