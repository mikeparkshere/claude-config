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
  # Images the data walk can miss (query loops, shortcodes, CSS backgrounds) still show up in the rendered HTML,
  # often behind an image CDN. Collect every uploads path, strip WordPress size suffixes, and copy the best master
  # (optimizer backup > unscaled > upload) of any file not already in media/. reduce.js reads rendered-media.json.
  python3 -I - "$out/raw/bundle.json" "$out/raw/html" > "$out/raw/rendered-media.tsv" <<'PY'
import json, os, re, sys
b = json.load(open(sys.argv[1])); site = b["site"]
up = site.get("uploads_path", "/wp-content/uploads").rstrip("/")
pat = re.compile(re.escape(up) + r"/((?:\d{4}/\d{2}/)?[^\"'\s)?,]+?\.(?:jpe?g|png|webp|gif|avif|svg))", re.I)
seen = {}
for f in sorted(os.listdir(sys.argv[2])):
    if not f.endswith(".html") or f.startswith(("404-probe", "robots")): continue
    for rel in set(pat.findall(open(os.path.join(sys.argv[2], f), errors="replace").read())):
        if rel.split("/")[0] in ("bricks", "automatic-css", "ShortpixelBackups", "cache"): continue
        rel = re.sub(r"-\d+x\d+(?=\.\w+$)", "", rel)
        rel = re.sub(r"-scaled(?=\.\w+$)", "", rel)
        seen.setdefault(rel, set()).add(f[:-5])
json.dump({k: sorted(v) for k, v in sorted(seen.items())}, open(os.path.join(os.path.dirname(sys.argv[2]), "rendered-media.json"), "w"), indent=1)
for rel in sorted(seen): print(rel)
PY
  basedir=$(python3 -I -c 'import json,sys; print(json.load(open(sys.argv[1]))["site"].get("uploads_basedir",""))' "$out/raw/bundle.json")
  if [ $media -eq 1 ] && [ -n "$basedir" ]; then
    rn=0
    while read -r rel; do
      [ -f "$out/media/$rel" ] && continue
      ext="${rel##*.}"; stem="${rel%.*}"
      src=''
      for cand in "$basedir/ShortpixelBackups/wp-content/uploads/$rel" "$basedir/$rel" "$basedir/$stem-scaled.$ext"; do
        if run test -f "$cand" 2>/dev/null; then src=$cand; break; fi
      done
      [ -n "$src" ] || continue
      mkdir -p "$(dirname "$out/media/$rel")"
      if [ -n "$ssh" ]; then scp -q -o BatchMode=yes "$ssh:$src" "$out/media/$rel" 2>>"$log" && rn=$((rn+1)); else cp -p "$src" "$out/media/$rel" 2>>"$log" && rn=$((rn+1)); fi
    done < "$out/raw/rendered-media.tsv"
    say "rendered-only media: $(wc -l < "$out/raw/rendered-media.tsv") upload images on the pages, $rn more copied to media/"
  fi
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
