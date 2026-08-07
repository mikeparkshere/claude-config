#!/usr/bin/env bash
# set-phase.sh — read or set a project's phase gate.
#
#   set-phase.sh                 report phase + whether the enforcement layer agrees
#   set-phase.sh --set STAGING   rewrite hard rule 0 and install the matching settings
#   set-phase.sh --install       (re)install settings to match the phase already on disk
#   set-phase.sh --uninstall     remove the gate from this project, leave CLAUDE.md alone
#   set-phase.sh --dir /path     operate on a project other than $PWD
#
# Canonical phase = hard rule 0 in the project CLAUDE.md:
#   0. **PHASE: STAGING.** ...
# Valid: DEV | STAGING | LIVE, each optionally suffixed +TXN.
# No readable phase means LIVE. Fail closed, always.
#
# Exit codes: 0 clean · 1 missing file or bad template · 2 bad arguments
#             3 demotion refused · 4 drift detected (report mode only)

set -euo pipefail

REPO="${CLAUDE_CONFIG_DIR:-$HOME/claude-config}"
DIR="$PWD"
MODE="report"
WANT=""

while [ $# -gt 0 ]; do
  case "$1" in
    --set)     MODE="set"; WANT="${2:?--set needs a phase}"; shift 2 ;;
    --install)   MODE="install"; shift ;;
    --uninstall) MODE="uninstall"; shift ;;
    --dir)     DIR="${2:?--dir needs a path}"; shift 2 ;;
    -h|--help) sed -n '2,12p' "$0"; exit 0 ;;
    *) echo "unknown arg: $1" >&2; exit 2 ;;
  esac
done

CLAUDE_MD="$DIR/CLAUDE.md"
SETTINGS="$DIR/.claude/settings.json"

read_phase() {
  [ -f "$CLAUDE_MD" ] || { echo ""; return; }
  grep -m1 -oE '^0\. \*\*PHASE: (DEV|STAGING|LIVE)(\+TXN)?\.' "$CLAUDE_MD" 2>/dev/null \
    | sed -E 's/^0\. \*\*PHASE: //; s/\.$//' || echo ""
}

# Distinguishes the three ways a phase can be unreadable. All fail closed to LIVE,
# but they send you looking in different places, so the report says which.
phase_status() {
  [ -f "$CLAUDE_MD" ] || { echo "NOFILE"; return; }
  grep -qE '^0\. \*\*PHASE:' "$CLAUDE_MD" 2>/dev/null || { echo "NORULE"; return; }
  [ -n "$(read_phase)" ] || { echo "BADPHASE"; return; }
  echo "OK"
}

src_for() {
  case "$1" in
    DEV)                 echo "" ;;
    STAGING|STAGING+TXN) echo "$REPO/permissions/staging.settings.json" ;;
    LIVE)                echo "$REPO/permissions/live.settings.json" ;;
    LIVE+TXN)            echo "$REPO/permissions/live-txn.settings.json" ;;
    *)                   echo "$REPO/permissions/live.settings.json" ;;
  esac
}

install_settings() {
  local phase="$1" src; src="$(src_for "$phase")"
  if [ -z "$src" ]; then
    if [ -f "$SETTINGS" ]; then
      rm -f "$SETTINGS"; rmdir "$DIR/.claude" 2>/dev/null || true
      echo "removed  $SETTINGS (DEV inherits the user-level baseline)"
    else
      echo "no file  DEV needs no project settings"
    fi
    return
  fi
  [ -f "$src" ] || { echo "missing template: $src" >&2; exit 1; }
  # A settings file that fails to parse is ignored and the gate silently disappears.
  # Never install one that has not parsed here first.
  if command -v jq >/dev/null 2>&1; then
    jq -e . "$src" >/dev/null 2>&1 || { echo "REFUSED: $src is not valid JSON" >&2; exit 1; }
  elif command -v python3 >/dev/null 2>&1; then
    python3 -c 'import json,sys; json.load(open(sys.argv[1]))' "$src" \
      || { echo "REFUSED: $src is not valid JSON" >&2; exit 1; }
  fi
  mkdir -p "$DIR/.claude"
  cp "$src" "$SETTINGS"
  echo "installed $SETTINGS  <- $(basename "$src")"
  echo "verify:   run /permissions in a fresh session — a file on disk is not a file that parsed"
}

case "$MODE" in
  set)
    WANT="$(printf '%s' "$WANT" | tr '[:lower:]' '[:upper:]')"
    case "$WANT" in DEV|STAGING|LIVE|STAGING+TXN|LIVE+TXN) ;; *)
      echo "invalid phase: $WANT (DEV|STAGING|LIVE, optional +TXN)" >&2; exit 2 ;; esac
    [ -f "$CLAUDE_MD" ] || { echo "no CLAUDE.md at $DIR" >&2; exit 1; }
    CUR="$(read_phase)"
    if [ -z "$CUR" ]; then
      echo "no hard rule 0 in $CLAUDE_MD — add it from the 99 template first" >&2; exit 1
    fi
    # Demotions are deliberate: refuse to loosen without --force-demote via manual edit.
    rank() { case "${1%%+*}" in DEV) echo 0;; STAGING) echo 1;; LIVE) echo 2;; *) echo 2;; esac; }
    if [ "$(rank "$WANT")" -lt "$(rank "$CUR")" ]; then
      echo "REFUSED: $CUR -> $WANT is a demotion." >&2
      echo "Loosening the gate is done by editing hard rule 0 in CLAUDE.md by hand," >&2
      echo "then running --install. That deliberateness is the point." >&2
      exit 3
    fi
    cp "$CLAUDE_MD" "$CLAUDE_MD.pre-phase.bak"
    tmp="$(mktemp)"
    sed -E "s/^0\. \*\*PHASE: (DEV|STAGING|LIVE)(\+TXN)?\./0. **PHASE: ${WANT}./" "$CLAUDE_MD" > "$tmp"
    mv "$tmp" "$CLAUDE_MD"
    if [ "$(read_phase)" != "$WANT" ]; then
      mv "$CLAUDE_MD.pre-phase.bak" "$CLAUDE_MD"
      echo "REFUSED: rewrite did not produce a readable rule 0. CLAUDE.md restored." >&2
      exit 1
    fi
    rm -f "$CLAUDE_MD.pre-phase.bak"
    echo "phase    $CUR -> $WANT  (CLAUDE.md rule 0)"
    install_settings "$WANT"
    echo
    echo "This session is not yet covered by the new file. Either /clear and re-open,"
    echo "or add the ask rules live with /permissions."
    ;;
  uninstall)
    if [ -f "$SETTINGS" ]; then
      rm -f "$SETTINGS"; rmdir "$DIR/.claude" 2>/dev/null || true
      echo "removed  $SETTINGS"
    else
      echo "nothing  no project settings file at $DIR"
    fi
    echo "note     hard rule 0 in CLAUDE.md is left in place; it is inert on its own"
    ;;
  install)
    P="$(read_phase)"
    if [ -z "$P" ]; then
      echo "no readable phase ($(phase_status)) — installing the LIVE tier, failing closed"
      P="LIVE"
    fi
    install_settings "$P"
    ;;
  report)
    ST="$(phase_status)"; P="$(read_phase)"; DRIFT=0
    case "$ST" in
      OK)       echo "Phase:    $P  (CLAUDE.md rule 0)" ;;
      NOFILE)   echo "Phase:    LIVE  (no CLAUDE.md — unmarked project, failing closed)" ;;
      NORULE)   echo "Phase:    LIVE  (CLAUDE.md present, no hard rule 0 — failing closed)" ;;
      BADPHASE) echo "Phase:    LIVE  (rule 0 unreadable — failing closed)"
                echo "          found: $(grep -m1 -E '^0\. \*\*PHASE:' "$CLAUDE_MD" | cut -c1-72)"
                echo "          valid: DEV | STAGING | LIVE, each optionally +TXN" ;;
    esac
    EXP="$(src_for "${P:-LIVE}")"
    if [ -n "$EXP" ] && [ -f "$SETTINGS" ]; then
      if cmp -s "$EXP" "$SETTINGS"; then
        echo "Gate:     .claude/settings.json matches $(basename "$EXP")"
      else
        echo "Gate:     DRIFT — .claude/settings.json differs from $(basename "$EXP")"; DRIFT=1
      fi
    elif [ -n "$EXP" ]; then
      if [ "$ST" = "OK" ]; then
        echo "Gate:     DRIFT — rule 0 says $P but no .claude/settings.json on disk"
        echo "          the install was skipped or lost. --install fixes it."
      else
        echo "Gate:     DRIFT — unmarked project with no gate installed"
        echo "          add rule 0 from the 99 template, or --install to fit the LIVE tier."
      fi
      DRIFT=1
    elif [ -f "$SETTINGS" ]; then
      echo "Gate:     DRIFT — phase is DEV but a project settings file is present"; DRIFT=1
    else
      echo "Gate:     none required (DEV)"
    fi
    [ "$DRIFT" -eq 0 ] || exit 4
    ;;
esac
