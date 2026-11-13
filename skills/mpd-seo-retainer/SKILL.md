---
name: mpd-seo-retainer
description: "Use on a Michael Parks Design project whose CLAUDE.md declares engagement: seo-retainer, for the retainer work itself: client SEO reports and quarterly reporting, the KPI (non-brand organic clicks, the brand rule, restating a series), content briefs and competitive reads, targeting decisions (top-3 / AI Overview naming, persist or drop), the content calendar and draft review, and DataForSEO via dfs.py. NOT for builds, maintenance-only clients, or Track 1 (maintenance and ops) work on a retainer site."
---

# MPD SEO retainer

How MPD runs a post-launch SEO retainer. **This file is the loader and the enforcement layer. The canon is
`~/claude-config/seo/retainer-method.md`: read it fully before retainer work.** Don't restate it here.

---

## Hard rules

Five that govern almost everything. Full versions, with the incidents behind them, are in the canon.

1. **Two tracks; start from the open items.** Place every request as Track 1 (maintenance) or Track 2 (the SEO
   mandate), and read the project's open-items list before acting on the request. Say where the request sits
   against it. If a session is all Track 1, say so.
2. **The brand rule is pinned in code.** Non-brand clicks are counted by one function in the project. Never
   re-implement it, never edit its exclusion list to change a number, and restate the whole series if it ever
   changes.
3. **Live-check any ranking shown to a client.** From the town, with the AI Overview loaded
   (`dfs.py serp … --coord "lat,lng" --aio`). A GSC average position is not what a searcher sees. Report favorable
   visibility even at zero clicks, labeled honestly, never as KPI movement.
4. **Briefs target top-3 or an AI Overview naming.** "Page one" is not an accepted success criterion. The
   competitive read is not skippable.
5. **Nothing waits on the client.** Write around an unknown fact and close the item; batch genuine questions for
   Michael to send.

---

## Read protocol

- **Confirm the engagement first.** `grep -m1 '^engagement:' CLAUDE.md` must say `seo-retainer`
  (`knowledgebase/00`, read protocol step 4). If it doesn't, this skill doesn't apply.
- **Read fully:** `~/claude-config/seo/retainer-method.md`.
- **Read the project's own copy too**, if it keeps one: entries below its seam are newer than the master and
  additive; nothing above the seam overrides the master.
- **Instrument behavior** (Search Console, GA4) is in `~/claude-config/knowledgebase/03-stack-gotchas.md` →
  Analytics & search measurement. Consult by symptom; never read `03` whole.
- **DataForSEO usage, caps and setup:** `~/claude-config/wordpress-runcloud.md` → DataForSEO.

**Drift check.** The project `CLAUDE.md` engagement line and the box's `~/.dataforseo/config.json` retainer list
must agree. If one says retainer and the other doesn't, report it to Michael. Don't fix it.
