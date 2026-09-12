---
name: client-status-board
description: "Use when creating or updating a client-facing project status board on status.michaelparksdesign.com: init a board for a project, push outstanding items at session close (translated out of build vocabulary), append work-log entries, or read board state / what changed since a date. NOT for internal task tracking, Notion sync, or anything the client should never see."
---

# Client status board

One private, link-shareable status page per client project, served centrally from
`status.michaelparksdesign.com` (webapp `app-buildstatus` on mpd2025_01, 104.207.142.31).
Full spec: `~/webapps/app-buildstatus/docs/status-board-build-brief.md` on that box; app
conventions in `~/webapps/app-buildstatus/CLAUDE.md`.

The board is a record, not a demand list. Most items originate with the end client; the
agency partner chases them. **Everything pushed to a board is client-facing copy** — the
same internal/client separation enforced everywhere else in the KB.

## Operations

| op | what | when |
|---|---|---|
| `init` | create a board, write tokens into project CLAUDE.md | project hits DEV, or a live site becomes a retainer |
| `push` | add outstanding items | session close — replaces composing an email |
| `log` | append a work-log entry | session close, alongside push |
| `read` | board state + what changed since a date | session open |

## Auth model — which token, from where

- **Per-board `master` token** — lives in the project's CLAUDE.md `## Status board`
  section. Authorizes `push`, `log`, `read`, and all board-scoped admin actions.
  `resolved_by` writes as "Michael". Scoped to one board; this is the token sessions use.
- **Per-board `agency` token** — the shareable credential. It IS the client-facing URL.
  `resolved_by` writes as the board's `prepared_for`.
- **Global admin token** — only in `data/secrets.json` on mpd2025_01. Needed for
  `create_board` and the all-boards index. **Never copy it into a project CLAUDE.md or a
  transcript.** Reach it via the helper, which injects it server-side:
  `echo "$PAYLOAD_JSON" | ssh runcloud@104.207.142.31 webapps/app-buildstatus/bin/board-admin.sh [admin|toggle|board|index]`
  (on mpd2025_01 itself, run the script directly, no ssh).

Wrong slug/token pairs return **404**, never 403. A 404 on a known slug means the token
is wrong — check CLAUDE.md against reality before retrying, don't brute-force.
Rate limit: 60 toggles/token/hour. Admin/board/index endpoints are not toggle-limited.

## API contract

Base `https://status.michaelparksdesign.com`. All JSON.

- `GET /api/board.php?slug=<slug>&k=<token>` → full board, `tokens` stripped, plus
  `viewer: "agency"|"master"`. Retired items are not returned.
- `POST /api/toggle.php` `{slug, k, item_id, checked, note?}` → updated board.
  Server sets `resolved_at`/`resolved_by`; re-check with note updates the note only.
- `POST /api/admin.php` `{action, k, slug, ...}` — master/admin token. Actions:
  `create_board` (admin token only), `add_item`, `edit_item`, `retire_item`,
  `append_log`, `rotate_token`, `set_phase`. Validation errors → 422 with `message`.
- `GET /api/index.php?k=<admin>` → all boards: `{slug, project, prepared_for, phase,
  open, oldest_open_requested, oldest_days, updated}`, sorted oldest-open first.

### Item shape (add_item accepts; board returns)

```json
{ "title": "Headshots for the twelve committee members",
  "owner": "client",              // or "agency" — drives the With-label and hero split
  "blocking": "the Leadership page. It's the last main page left to finish.",
  "meanwhile": "it's built with gray placeholders cropped to the final size.",
  "requested": "2026-09-02",      // defaults to today
  "urgent": false }               // true = one mail to hello@ fires when it clears
```

`create_board` requires `slug` (`[a-z0-9-]{3,40}`, from the webapp directory name),
`project`, `client_label`; accepts `subtitle`, `prepared_for`, `phase`, `preview_url`.
Response carries `url` and both tokens — **the only time tokens are returned. Write them
into CLAUDE.md immediately.**

## init

1. Prompt for: project name, agency (`prepared_for` — or the client's own display name
   for direct work; it is what `resolved_by` and the split line print, so "Jus B Media"
   or "Tony", never "direct"), **`client_label`** (how copy names the end client: "the
   committee", "the owner", "Tony" — no sane default, always ask), preview URL.
2. Slug from the webapp directory name (`app-wcdems` → `wcdems`).
3. Create:
   ```bash
   echo '{"action":"create_board","slug":"wcdems","project":"WC Dems","subtitle":"Website rebuild","prepared_for":"Jus B Media","client_label":"the committee","phase":"DEV","preview_url":"https://wcdems.parksheredev.com"}' \
     | ssh runcloud@104.207.142.31 webapps/app-buildstatus/bin/board-admin.sh admin
   ```
4. Append to the project CLAUDE.md (exact shape — `read` greps for it):
   ```markdown
   ## Status board
   - Share URL (give to agency/client): https://status.michaelparksdesign.com/<slug>?k=<agency token>
   - Master token (sessions use this): <master token>
   - client_label: "<label>"  ·  prepared_for: "<name>"
   ```
5. Output the share URL to Michael.

## push

At session close, list what the session was blocked on, propose items, ask owner per
item only if genuinely ambiguous, then one `add_item` per item via the board's master
token (no ssh needed):

```bash
curl -s -X POST https://status.michaelparksdesign.com/api/admin.php \
  -H 'Content-Type: application/json' \
  -d '{"action":"add_item","k":"<master>","slug":"<slug>","title":"...","owner":"client","blocking":"...","meanwhile":"..."}'
```

This should take five seconds of Michael's attention, not a composition session. Draft
the items, show them as a batch, push on a nod.

### Copy rules — enforced on every item, no exceptions

- **No build vocabulary.** Bricks, ACSS, ACF, CPT, WP-CLI, staging, DNS, cron, plugin
  names — none of it. Translate: "the events calendar", "the preview site", "the form".
- **Title is a noun phrase naming the thing needed**, not an instruction. "Headshots for
  the twelve committee members", never "Send us headshots".
- **`blocking` names a specific page or feature**, never "the project". Lowercase-initial
  fragment — it follows the bolded "Holding up:" label.
- **`meanwhile` is not optional in spirit.** It states what is happening in the item's
  absence and, where true, how fast things resolve once it lands ("placeholders are in;
  the real photos drop in about twenty minutes once they land"). This line is what keeps
  the page from reading as a nag. It is the one most likely to get skipped. Do not skip it.
- **No second person for a client-owned item.** The reader (agency) is not the owner;
  "you" for the client's homework misaddresses both.
- Brand voice: direct, technical, confident. Sentence case. No exclamation points.
- Dates honest: `requested` = when the ask actually first went out, not today, if the
  session knows better.

## log

```bash
curl -s -X POST https://status.michaelparksdesign.com/api/admin.php \
  -H 'Content-Type: application/json' \
  -d '{"action":"append_log","k":"<master>","slug":"<slug>","entry":"Events calendar is working. Monthly meetings are loaded through December."}'
```

Same copy rules. One or two sentences, client-readable, names pages/features. Log what
shipped, not what was refactored.

## read

Session opener: if the project CLAUDE.md has a `## Status board` section, fetch and
report before anything else:

```bash
curl -s "https://status.michaelparksdesign.com/api/board.php?slug=<slug>&k=<master>"
```

Report shape (compute from `items[]` and `log[]`, `since` = last session date):

```
Status board: 4 outstanding, oldest 9 days (headshots, with the committee).
Since Tuesday: logo files cleared by Jus B Media — "Dropbox link went out Thursday".
```

- Outstanding: items with `checked: false`; oldest by `requested`; owner label from
  `owner` (`client` → client_label, `agency` → prepared_for).
- Changes since: items with `resolved_at >= since` (name + resolved_by + note, quoted),
  plus log entries with `date >= since`.
- Nothing changed and nothing open → one line: `Status board: clear.`

## Gotchas

- Items are never hard-deleted — `retire_item` hides them. An item pushed by mistake
  gets retired, not edited into something else.
- Re-checking an already-checked item updates the note only; `resolved_at` stands.
- `urgent: true` exists ONLY to fire the one mail on clear. It is not displayed as a
  badge and is not a priority flag. Default false; use it rarely or it means nothing.
- Out of scope by design (do not "improve" the board with these): due dates, priority
  flags, comment threads, client logins, file uploads. See brief §11 for why.
- The share URL is the credential. Never paste an agency URL into anything public;
  `rotate_token` when an agency relationship ends.
