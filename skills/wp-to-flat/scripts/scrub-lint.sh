#!/usr/bin/env bash
# wp-to-flat scrub lint: fail if WordPress or builder output leaked into the flat build. Gate 4 blocker.
#
#   scrub-lint.sh <site root> [--harvest <dir>] [--no-render]
#
#   <site root>  the flat site's app root (holds public/ and inc/)
#   --harvest    harvest dir; adds the check for harvested image names with a WP size suffix (photo-300x200.jpg)
#   --no-render  skip layer 2
#
# Layer 1, source: public/ and inc/ text files must not contain wp-content, wp-includes, wp-json, wp-admin,
#   wp-emoji, xmlrpc, brxe-, bricks-, wp-block-, wp-image-. Server config (.htaccess, *.conf) is skipped: redirect
#   rules have to name the old paths. So are .git and node_modules.
# Layer 2, rendered: every URL in public/sitemap.xml plus the 404 page, served locally by `php -S` (production
#   is never touched, and host-keyed analytics stays off), must answer 200 (404 for the 404 page) and carry no
#   generator meta, no oEmbed discovery link, no WP/Bricks fingerprint, and no leftover shortcode in visible text.
#   `[LIKE THIS]` placeholders are reported as warnings, not failures.
#
# Allowlist: <site root>/.scrub-allow, one extended regex per line (# comments ok), matched against each hit
#   ("path:line: text"). Every entry needs a comment saying why.
# Exit 0 = clean, 1 = hits, 2 = usage.
set -uo pipefail
root=${1:-}; shift || true
harvest='' render=1
while [ $# -gt 0 ]; do case $1 in --harvest) harvest=$2; shift 2;; --no-render) render=0; shift;; *) echo "unknown option: $1" >&2; exit 2;; esac; done
[ -n "$root" ] && [ -d "$root/public" ] || { echo "usage: scrub-lint.sh <site root with public/> [--harvest dir] [--no-render]" >&2; exit 2; }
root=$(cd "$root" && pwd)
tmp=$(mktemp -d); trap 'rm -rf "$tmp"; [ -n "${srv:-}" ] && kill $srv 2>/dev/null' EXIT
allow="$root/.scrub-allow"
filter() { if [ -s "$allow" ]; then grep -vEf <(grep -vE '^\s*(#|$)' "$allow") || true; else cat; fi; }

FP='wp-content|wp-includes|wp-json|wp-admin|wp-emoji|xmlrpc|brxe-|bricks-|wp-block-|wp-image-'
dirs=(); for d in public inc; do [ -d "$root/$d" ] && dirs+=("$root/$d"); done

# Layer 1
grep -rnIiE "$FP" "${dirs[@]}" --exclude=.htaccess --exclude='*.conf' --exclude-dir=.git --exclude-dir=node_modules 2>/dev/null \
  | sed "s#^$root/##" | cut -c1-220 | filter > "$tmp/src"
if [ -n "$harvest" ] && [ -f "$harvest/reduced/media.json" ]; then
  names=$(python3 -I -c '
import json, sys, re, os
n = [os.path.splitext(os.path.basename(m["file"]))[0] for m in json.load(open(sys.argv[1])) if m.get("file")]
print("|".join(re.escape(x) for x in sorted(set(n))))' "$harvest/reduced/media.json")
  [ -n "$names" ] && grep -rnIE "($names)(-scaled)?-[0-9]+x[0-9]+\." "${dirs[@]}" --exclude=.htaccess --exclude='*.conf' --exclude-dir=.git --exclude-dir=node_modules 2>/dev/null \
    | sed "s#^$root/##" | cut -c1-220 | filter | sed 's/^/[size-suffix] /' >> "$tmp/src"
elif [ -z "$harvest" ]; then echo "note: no --harvest, so the WP size-suffix check on image names was skipped"; fi

# Layer 2
: > "$tmp/render"; : > "$tmp/warn"
if [ $render -eq 1 ]; then
  port=$(python3 -I -c 'import socket; s=socket.socket(); s.bind(("127.0.0.1",0)); print(s.getsockname()[1])')
  (cd "$root/public" && exec php -S 127.0.0.1:$port -t "$root/public" >/dev/null 2>&1) & srv=$!
  for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$port/" && break; sleep 0.2; done
  { [ -f "$root/public/sitemap.xml" ] && grep -oE '<loc>[^<]+' "$root/public/sitemap.xml" | sed -E 's#<loc>https?://[^/]+##'; } > "$tmp/paths"
  [ -s "$tmp/paths" ] || echo / > "$tmp/paths"
  n=0
  while read -r p; do
    n=$((n+1)); code=$(curl -s -o "$tmp/page$n.html" -w '%{http_code}' "http://127.0.0.1:$port$p")
    printf '%s\t%s\t%s\n' "$p" "$code" "$tmp/page$n.html" >> "$tmp/fetched"
  done < "$tmp/paths"
  if [ -f "$root/public/404.php" ]; then
    code=$(curl -s -o "$tmp/page404.html" -w '%{http_code}' "http://127.0.0.1:$port/404.php")
    printf '%s\t%s\t%s\n' '(404 page)' "$code" "$tmp/page404.html" >> "$tmp/fetched"
  fi
  python3 -I - "$tmp/fetched" "$FP" > "$tmp/render.raw" 2> "$tmp/warn" <<'PY'
import sys, re, html
fetched, fp = sys.argv[1], sys.argv[2]
for line in open(fetched):
    path, code, f = line.rstrip('\n').split('\t')
    want = '404' if path == '(404 page)' else '200'
    if code != want: print(f"{path}: HTTP {code} (want {want})"); continue
    h = open(f, encoding='utf-8', errors='replace').read()
    for m in re.finditer(r'<meta[^>]+name=["\']generator["\'][^>]*>', h, re.I): print(f"{path}: generator meta: {m.group(0)[:120]}")
    for m in re.finditer(r'<link[^>]+(json|xml)\+oembed[^>]*>', h, re.I): print(f"{path}: oEmbed discovery link: {m.group(0)[:120]}")
    for m in re.finditer(r'[^\s"\'<>]*(' + fp + r')[^\s"\'<>]*', h, re.I): print(f"{path}: fingerprint: {m.group(0)[:120]}")
    text = re.sub(r'<(script|style|template|code|pre)\b[\s\S]*?</\1>', ' ', h, flags=re.I)
    text = html.unescape(re.sub(r'<[^>]+>', ' ', text))
    for m in re.finditer(r'\[/?[a-z][a-z0-9_-]*(\s[^\]\n]{0,80})?\]', text): print(f"{path}: leftover shortcode: {m.group(0)}")
    for m in re.finditer(r'\[[A-Z][A-Z ,]{3,}\]', text): print(f"{path}: placeholder: {m.group(0)}", file=sys.stderr)
PY
  filter < "$tmp/render.raw" > "$tmp/render"
  echo "rendered: $(wc -l < "$tmp/fetched") pages via php -S"
fi

fail=0
if [ -s "$tmp/src" ]; then echo; echo "SOURCE HITS ($(wc -l < "$tmp/src")):"; sed 's/^/  /' "$tmp/src"; fail=1; fi
if [ -s "$tmp/render" ]; then echo; echo "RENDERED HITS ($(wc -l < "$tmp/render")):"; sed 's/^/  /' "$tmp/render"; fail=1; fi
if [ -s "$tmp/warn" ]; then echo; echo "WARNINGS (placeholders, not failures):"; sort -u "$tmp/warn" | sed 's/^/  /'; fi
[ $fail -eq 0 ] && echo "scrub lint: clean" || echo "scrub lint: FAILED (Gate 4 blocker)"
exit $fail
