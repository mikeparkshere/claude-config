#!/usr/bin/env bash
# wp-to-flat harvest: read-only snapshot of a WordPress site into <out>/.
#
#   harvest.sh --path <wp root> --out <dir> [--ssh <host>] [--base-url <https://host>] [--auth-file <file>]
#              [--screens] [--no-media]
#
#   --path       WordPress root on the machine that runs it (local, or on --ssh host)
#   --out        local output dir; keep it OUTSIDE the skill folder and out of git (it holds client content)
#   --ssh        run WP-CLI and copy media over SSH (host alias or user@host)
#   --base-url   also fetch each public URL's rendered HTML (raw/html/) plus robots.txt; needed for parity and
#                rendered SEO. Without it, SEO comes from plugin meta only.
#   --auth-file  basic-auth credentials for --base-url: two lines, "username: x" / "password: y"
#   --screens    full-page screenshots at 1440 and 390 (needs ~/tools/browser/shot and --base-url)
#   --no-media   skip copying original media files
#
# Output: raw/bundle.json (everything WordPress knows; scripts read it, Claude doesn't), raw/html/, media/
# (best master per referenced image), screens/. Then run: node reduce.js <out>
#
# Read-only: WP-CLI only reads; files are copied out, never written back. Nothing runs on the site's webroot.
set -euo pipefail
here=$(cd "$(dirname "$0")" && pwd)
path='' out='' ssh='' base='' auth='' screens=0 media=1
while [ $# -gt 0 ]; do
  case $1 in
    --path) path=$2; shift 2;; --out) out=$2; shift 2;; --ssh) ssh=$2; shift 2;;
    --base-url) base=${2%/}; shift 2;; --auth-file) auth=$2; shift 2;;
    --screens) screens=1; shift;; --no-media) media=0; shift;;
    -h|--help) sed -n '2,22p' "$0"; exit 0;;
    *) echo "unknown option: $1" >&2; exit 2;;
  esac
done
[ -n "$path" ] && [ -n "$out" ] || { echo "usage: harvest.sh --path <wp root> --out <dir> [options]; --help for more" >&2; exit 2; }
case $(cd "$(dirname "$out")" 2>/dev/null && pwd)/ in "$here"/../*) echo "refusing: --out is inside the skill folder" >&2; exit 2;; esac

run() { if [ -n "$ssh" ]; then ssh -o BatchMode=yes "$ssh" "$(printf '%q ' "$@")"; else "$@"; fi; }
mkdir -p "$out/raw"
log="$out/raw/harvest.log"; : > "$log"
say() { echo "$*" | tee -a "$log"; }

say "== harvest $(date -Is) path=$path ${ssh:+ssh=$ssh} ${base:+base=$base}"
run wp --path="$path" core is-installed || { say "not a WordPress install: $path"; exit 1; }

# 1. Bundle. PHP warnings/deprecations go to the log, not the JSON.
if [ -n "$ssh" ]; then
  ssh -o BatchMode=yes "$ssh" "wp --path=$(printf '%q' "$path") eval-file -" < "$here/harvest.php" > "$out/raw/bundle.json" 2>>"$log"
else
  wp --path="$path" eval-file - < "$here/harvest.php" > "$out/raw/bundle.json" 2>>"$log"
fi
python3 -I -c 'import json,sys; json.load(open(sys.argv[1]))' "$out/raw/bundle.json" || { say "bundle.json is not valid JSON; see $log"; exit 1; }
say "bundle: $(wc -c < "$out/raw/bundle.json") bytes"

# 2. Media masters (only files the site actually references), kept at their uploads-relative path.
if [ $media -eq 1 ]; then
  mkdir -p "$out/media"
  python3 -I -c '
import json, sys
for m in json.load(open(sys.argv[1]))["media"]:
    if not m.get("missing") and m.get("source"): print(m["source"] + "\t" + m["rel"])
' "$out/raw/bundle.json" > "$out/raw/media-list.tsv"
  n=0; miss=0
  while IFS=$'\t' read -r src rel; do
    dest="$out/media/$rel"; mkdir -p "$(dirname "$dest")"
    if [ -n "$ssh" ]; then scp -q -o BatchMode=yes "$ssh:$src" "$dest" 2>>"$log" && n=$((n+1)) || miss=$((miss+1))
    else cp -p "$src" "$dest" 2>>"$log" && n=$((n+1)) || miss=$((miss+1)); fi
  done < "$out/raw/media-list.tsv"
  say "media: $n copied, $miss failed"
fi

# 3. Rendered pages (optional). URLs come from the bundle's own home_url, re-pointed at --base-url, so a
#    fixture on another hostname still works.
if [ -n "$base" ]; then
  mkdir -p "$out/raw/html"
  curl_auth=()
  if [ -n "$auth" ]; then
    u=$(sed -n '1s/^[^:]*: *//p' "$auth"); p=$(sed -n '2s/^[^:]*: *//p' "$auth")
    curl_auth=(-K <(printf 'user = "%s:%s"\n' "$u" "$p"))
  fi
  python3 -I -c '
import json, sys
b = json.load(open(sys.argv[1])); home = b["site"]["home"].rstrip("/")
for p in b["posts"]: print(p["type"] + "-" + p["slug"] + "\t" + p["url"].replace(home, "", 1))
' "$out/raw/bundle.json" > "$out/raw/url-list.tsv"
  printf 'robots\t/robots.txt\n404-probe\t/wp-to-flat-404-probe/\n' >> "$out/raw/url-list.tsv"
  while IFS=$'\t' read -r key rel; do
    code=$(curl -s "${curl_auth[@]}" -o "$out/raw/html/$key.html" -D "$out/raw/html/$key.headers" -w '%{http_code}' "$base$rel?nocache=$RANDOM" || echo 000)
    echo "$code $rel" >> "$out/raw/html/status.txt"
  done < "$out/raw/url-list.tsv"
  say "html: $(grep -c '^200' "$out/raw/html/status.txt" || true) of $(wc -l < "$out/raw/url-list.tsv") returned 200 (see raw/html/status.txt)"
  if [ $screens -eq 1 ] && [ -x "$HOME/tools/browser/shot" ]; then
    mkdir -p "$out/screens"
    while IFS=$'\t' read -r key rel; do
      [ "$key" = robots ] && continue
      "$HOME/tools/browser/shot" "$base$rel" --out "$out/screens" --widths 1440,390 --reduced-motion ${auth:+--auth-file "$auth"} >>"$log" 2>&1 || say "screens failed: $rel"
    done < "$out/raw/url-list.tsv"
    say "screens: $(ls "$out/screens" | wc -l) files"
  fi
fi
say "done. Next: node $here/reduce.js $out"
