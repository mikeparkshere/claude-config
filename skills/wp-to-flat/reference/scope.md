# Scope

## A candidate

- A brochure site: roughly **15 public URLs or fewer** (`flatsite.md` says under 10 for a hand-built site; a port can run
  a little larger because the content already exists).
- No blog. A handful of news posts that will never grow can become pages; say so at Gate 1.
- No ecommerce, no member or course areas, no user-facing logins.
- Forms limited to contact or quote requests.
- A custom post type for a short list (team, services, locations) is fine. It becomes a data file the pages read from,
  with a page per item where the old site had one.

## Not a candidate: stop at Gate 1

The inventory's verdict line and FLAGS catch these: published blog posts, WooCommerce or EDD active, a membership or
LMS plugin, non-staff user roles with accounts, a builder with no reducer (writing the reducer becomes part of the port,
so it's a scope conversation, not an automatic no).

Also stop and raise it if the client edits content often and expects to keep doing it themselves. A flat site moves
every edit to Michael; that has to be a decision, not a surprise.

## When a generator fits better

`flatsite.md` → "When to step up to a generator": content generated from data at scale (a blog, a directory, dozens of
locations), or static hosting with no PHP. A short list rendered by PHP from a data file isn't that.
