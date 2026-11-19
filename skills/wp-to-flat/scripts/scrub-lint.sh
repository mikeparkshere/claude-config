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
: > "$tmp/render"; : > "$tmp/warn"; : > "$tmp/probe"
if [ $render -eq 1 ]; then
  port=$(python3 -I -c 'import socket; s=socket.socket(); s.bind(("127.0.0.1",0)); print(s.getsockname()[1])')
  # Router: php -S on its own serves index.php for ANY missing path, so a sitemap URL whose page was never built
  # would answer 200. Serve real files, directory index.php, else a genuine 404 (via 404.php when it exists).
  cat > "$tmp/router.php" <<'PHP'
<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = realpath($root . $path);
if ($file !== false && strpos($file, realpath($root)) === 0) {
    if (is_file($file)) return false;
    if (is_dir($file) && is_file($file . '/index.php') && substr($path, -1) === '/') { chdir($file); require $file . '/index.php'; return true; }
}
// Internal rewrites from public/.htaccess (a template route like `RewriteRule ^projects/([a-z0-9-]+)/?$ /projects/project.php?slug=$1 [L]`).
// php -S ignores .htaccess, so without this every templated URL lints as a 404. External redirects are skipped.
foreach (is_file($root . '/.htaccess') ? file($root . '/.htaccess') : [] as $line) {
    if (!preg_match('~^\s*RewriteRule\s+(\S+)\s+(/[^\s?]+\.php)(\?\S*)?\s~', $line, $r)) continue;
    if (!preg_match('~' . str_replace('~', '\~', $r[1]) . '~', ltrim($path, '/'), $m)) continue;
    $q = preg_replace_callback('~\$(\d)~', fn($x) => $m[(int) $x[1]] ?? '', ltrim($r[3] ?? '', '?'));
    parse_str($q, $vars); $_GET = $vars + $_GET;
    if (is_file($root . $r[2])) { chdir(dirname($root . $r[2])); require $root . $r[2]; return true; }
}
http_response_code(404);
if (is_file($root . '/404.php')) { require $root . '/404.php'; }
return true;
PHP
  (cd "$root/public" && exec php -S 127.0.0.1:$port -t "$root/public" "$tmp/router.php" >/dev/null 2>&1) & srv=$!
  for i in $(seq 1 30); do curl -s -o /dev/null "http://127.0.0.1:$port/" && break; sleep 0.2; done
  { [ -f "$root/public/sitemap.xml" ] && grep -oE '<loc>[^<]+' "$root/public/sitemap.xml" | sed -E 's#<loc>https?://[^/]+##'; } > "$tmp/paths"
  [ -s "$tmp/paths" ] || echo / > "$tmp/paths"
  n=0
  while read -r p; do
    n=$((n+1)); code=$(curl -s -o "$tmp/page$n.html" -w '%{http_code}' "http://127.0.0.1:$port$p")
    printf '%s\t%s\t%s\n' "$p" "$code" "$tmp/page$n.html" >> "$tmp/fetched"
  done < "$tmp/paths"
  # A path that can't exist must 404, or the server above isn't telling the truth about missing pages.
  code=$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$port/scrub-lint-missing-probe-$$/")
  [ "$code" = 404 ] || printf '%s\t%s\n' "(missing-page probe)" "answered $code, expected 404: the local server is masking missing pages" > "$tmp/probe"
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
  filter < "$tmp/render.raw" > "$tmp/render"; cat "$tmp/probe" >> "$tmp/render"
  echo "rendered: $(wc -l < "$tmp/fetched") pages via php -S"
fi

fail=0
if [ -s "$tmp/src" ]; then echo; echo "SOURCE HITS ($(wc -l < "$tmp/src")):"; sed 's/^/  /' "$tmp/src"; fail=1; fi
if [ -s "$tmp/render" ]; then echo; echo "RENDERED HITS ($(wc -l < "$tmp/render")):"; sed 's/^/  /' "$tmp/render"; fail=1; fi
if [ -s "$tmp/warn" ]; then echo; echo "WARNINGS (placeholders, not failures):"; sort -u "$tmp/warn" | sed 's/^/  /'; fi
[ $fail -eq 0 ] && echo "scrub lint: clean" || echo "scrub lint: FAILED (Gate 4 blocker)"
exit $fail
