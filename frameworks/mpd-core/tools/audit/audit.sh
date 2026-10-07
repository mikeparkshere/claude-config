#!/usr/bin/env bash
# mpd-core — framework usage audit across LocalWP sites (how mpd-core's scope was measured; re-run to re-scope).
#
# Each site is reached through ITS OWN MySQL socket (Local's Site Shell `wp` only reaches the
# site the shell was opened for, and a wrong-site read returns normal-looking data). Every read
# is guarded on siteurl. Output is anonymous: $OUTDIR/raw/site-NN.json. The ID -> site mapping lives
# only in $OUTDIR/site-map.private.json: OUTDIR must be a scratchpad or a gitignored folder, never this
# repo (claude-config is public, and the map names client sites).
#
# Usage: OUTDIR=/path/to/scratch audit.sh                 # every running Local site
#        OUTDIR=… SITES="slug1 slug2" audit.sh           # limit to these folder slugs
#        OUTDIR=… EXCLUDE="slug3" SELF_SLUG=slug4 audit.sh   # skip sites; SELF_SLUG = the site this shell belongs to
set -uo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
: "${OUTDIR:?OUTDIR is required (a scratchpad or a gitignored folder, never this repo)}"
RAW="$OUTDIR/raw"; MAP="$OUTDIR/site-map.private.json"
LOCAL_JSON="$HOME/Library/Application Support/Local/sites.json"
WP=/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp
SELF_SLUG="${SELF_SLUG:-}"
EXCLUDE="${EXCLUDE:-}"
mkdir -p "$RAW"

# site list: id|slug|name|running   (python only for JSON parsing; prints nothing identifying to the report)
ROWS=()
while IFS= read -r line; do ROWS+=("$line"); done < <(python3 - "$LOCAL_JSON" <<'EOF'
import json, os, sys
d = json.load(open(sys.argv[1]))
for sid, s in sorted(d.items(), key=lambda kv: kv[1].get("path", "")):
    slug = os.path.basename(os.path.expanduser(s.get("path", "")).rstrip("/"))
    sock = os.path.expanduser(f"~/Library/Application Support/Local/run/{sid}/mysql/mysqld.sock")
    print(f"{sid}|{slug}|{s.get('name','')}|{int(os.path.exists(sock))}")
EOF
)

# stable anonymous ID per Local site id
anon_id() {
  python3 - "$MAP" "$1" "$2" "$3" <<'EOF'
import json, os, sys
path, sid, slug, name = sys.argv[1:]
m = json.load(open(path)) if os.path.exists(path) else {}
for k, v in m.items():
    if v.get("local_id") == sid: print(k); sys.exit()
k = f"site-{len(m) + 1:02d}"
m[k] = {"local_id": sid, "slug": slug, "name": name}
json.dump(m, open(path, "w"), indent=2); print(k)
EOF
}

ok=0; skipped=("")
for row in "${ROWS[@]}"; do
  IFS='|' read -r sid slug name running <<<"$row"
  [[ " $EXCLUDE " == *" $slug "* ]] && continue
  [[ -n "${SITES:-}" && " $SITES " != *" $slug "* ]] && continue
  docroot="$HOME/Local Sites/$slug/app/public"
  if [[ "$running" != 1 ]]; then skipped+=("$slug (not running)"); continue; fi
  if [[ ! -f "$docroot/wp-config.php" ]]; then skipped+=("$slug (no wp-config)"); continue; fi

  S="$HOME/Library/Application Support/Local/run/$sid/mysql/mysqld.sock"
  wpx() { ( cd "$docroot" && php -d "mysqli.default_socket=$S" -d "pdo_mysql.default_socket=$S" "$WP" "$@" 2>/dev/null ); }

  url="$(wpx option get siteurl)"
  host="${url#*://}"; host="${host%%/*}"; label="${host%%.*}"
  # guard: must be the sibling, never this site, never empty
  if [[ -z "$url" || ( -n "$SELF_SLUG" && "$url" == *"$SELF_SLUG"* ) || "$label" != "${slug}"* && "$slug" != "${label}"* ]]; then
    # slug and host label can legitimately differ (e.g. folder "acme", host "acme-x"); allow if host contains slug sans dashes
    if [[ -z "$url" || ( -n "$SELF_SLUG" && "$url" == *"$SELF_SLUG"* ) || "${host//-/}" != *"${slug//-/}"* ]]; then
      skipped+=("$slug (siteurl guard failed)"); continue
    fi
  fi

  id="$(anon_id "$sid" "$slug" "$name")"
  blog="$(wpx option get blogname)"
  redact="$(printf '%s\n' "$slug" "$name" "$label" "$blog" | tr 'A-Z' 'a-z' | tr -c 'a-z0-9\n' '\n' | grep -E '^.{3,}$' | sort -u | paste -sd, -)"
  if OUT="$RAW/$id.json" SITE_ID="$id" REDACT="$redact" \
       php -d "mysqli.default_socket=$S" -d "pdo_mysql.default_socket=$S" "$WP" --path="$docroot" eval-file "$HERE/extract.php" 2>/dev/null; then
    ok=$((ok + 1))
  else
    skipped+=("$id (extractor exit $?)")
  fi
done

echo "audited: $ok"
[ ${#skipped[@]} -gt 1 ] && printf 'skipped: %s\n' "${skipped[@]:1}" || echo "skipped: none"
