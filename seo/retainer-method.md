# SEO Retainer Method

**Michael Parks Design — canon for SEO retainer engagements.** Loaded only on projects whose `CLAUDE.md` declares
`engagement: seo-retainer` (`knowledgebase/00`, read protocol step 4), through the `mpd-seo-retainer` skill.

> **PROVISIONAL.** Built from one retainer: a local home-services contractor, 2026-07 → 2026-11. The next retainer extends or corrects it; it doesn't fork it. Rules here are as strong as one
> engagement's evidence, and several say so.

How MPD runs a post-launch SEO retainer: choosing and pinning the KPI, measuring honestly, targeting content,
running the content program, reporting to the client, and spending on paid data. Each rule carries the incident
that taught it.

---

## Scope

**This file does not move the knowledgebase's scope line.** `knowledgebase/` stays the canon for builds through
cutover, and `00`'s two scope notes stand as written. This is a **separate canon for one kind of engagement**,
read only when a project says it is that kind of engagement. A build project never loads it; a maintenance-only
client never loads it.

**The instruments stay where they are.** How Search Console and GA4 misbehave (censored query view, restated
windows, window-average positions) lives in `knowledgebase/03` → **Analytics & search measurement**, because it is
true on every property whatever the engagement. This file is the method built on top of those instruments. Where a
rule depends on an instrument quirk, it points there instead of restating it.

**Never here:** a client's rankings, traffic, leads, content calendar, targets, or anything a client said. Those are
project business. This repo is public (`00`): mechanisms, not coordinates. Provenance is generic ("first
retainer, 2026-09") and never names the client or carries its numbers.

**Stack-agnostic.** Nothing below assumes WordPress. Where setup differs, the WordPress/RunCloud path is in
`wordpress-runcloud.md` and a flat site uses the equivalent noted in §9.

---

## How this file grows: the seam and the harvest

This file has two parts, divided by the `=== RETAINER SECTION ===` marker at the bottom, on the same contract as
`03`/`04` (`00` → Write protocol, The go-live harvest):

- **Above the seam: ESTABLISHED.** Inherited when a retainer starts. Don't edit it from a project session.
- **Below the seam: RETAINER.** Empty in the master. A retainer project keeps its own copy of this file in its
  docs and appends what it learns below the seam, in the entry format at the bottom.

**Harvest trigger: each quarterly report.** A retainer has no go-live, so the moment its learning is reviewed is
the quarterly report, when the method has just been exercised end to end. At that point Michael reviews the
project copy's entries below the seam; validated ones fold into the master above it, provenance kept and scrubbed;
wrong or client-specific ones are dropped. A rule that a second retainer contradicts is flagged for Michael, not
silently edited (`00`, the conflict clause).

---

## 1. The engagement has two tracks, and the quiet one is the mandate

**Rule.** Split every request into **Track 1, maintenance** (updates, backups, incidents, ad-hoc edits) or
**Track 2, SEO** (the retainer deliverable). They have different definitions of done and are billed separately.
Start each session from the open-items list, not from the incoming request, and say out loud where the request
sits against it.

**Why.** Track 1 generates incidents, and incidents feel urgent. A whole session can close with good work done
while the item with the deadline didn't move. That's the normal outcome, not the unlucky one.

**Provenance.** First retainer, 2026-08: made a standing session-start instruction after sessions repeatedly
drifted entirely into ops. 2026-11: open items moved to one structured `TODO.md` with generated boards, because
the same items lived as prose in three places and drifted apart.

---

## 2. The KPI: one number that only SEO can move, pinned in code

**Rule.** The headline KPI is **non-brand organic clicks per 28 days** from the GSC **Queries** view, against a
dated target agreed in writing with the client. Not organic share of traffic, not rankings, not impressions.

- **Pin the brand rule in code, once.** One function decides brand vs non-brand, with the rationale and a
  do-not-edit warning on its exclusion list. Every report, script and doc calls it. Changing it changes the
  client-facing number, so a change needs a decisions-log entry and a restatement of the whole series.
- **Write the counting rule into the client agreement**, including any deliberate exception (a query that matches
  the brand token but is a different business). Disagreements about definitions surface at the worst moment.
- **Disclose the censoring.** The Queries view withholds rare queries (`03` → Analytics & search measurement),
  concentrated in exactly the long tail this KPI measures. Present it to the client as a consistent, reproducible
  sample, not a census, and never as total organic traffic.
- **Supporting numbers, every report:** absolute organic sessions; organic conversion rate, only once a clean
  conversion signal exists (§3); organic share **next to paid-ad context**, never as the headline.

**Why not share.** Organic share is a ratio, so the client's own ad spend moves it: a successful paid campaign
reads as an SEO failure, a paused one as an SEO win. **Why not a contracted baseline from before launch.** It may not
be reproducible from current data, and the gap it implies may have closed before the work started.

**Provenance.** First retainer, 2026-07 → 08: the contracted metric was a share-of-traffic target whose baseline
predated the rebuild and could not be reproduced, and share moved with the client's ads rather than with search.
Separately, three brand rules were in simultaneous use and gave a 3× spread on the same window (`00` → *A metric
with two implementations will drift*). The replacement KPI, the pinned rule and the disclosed censoring were
agreed in writing.

---

## 3. Measurement hygiene before any number reaches the client

**Rules.**
1. **A dated baseline document**, regenerated and re-dated periodically, with the commands that rebuild it. Every
   search figure quoted anywhere carries the window it came from.
2. **Pull query × page before targeting anything by position.** A query-only average folds homepage local-pack
   "1.0" rows into the real organic position and invents page-two near-misses.
3. **The conversion signal must fire on an accepted submission**, not a pageview. Check what analytics is actually
   counting before quoting any conversion rate; mark the date it changed and never compare across that seam.
4. **Leads are counted from the form's own records**, not analytics (which loses a share to blockers). Screen out
   test submissions first, **including any the client made**, by name and by "test" in the message, never by a
   surname alone, and dedupe double-submits seconds apart.
5. **Capture first-touch lead source on the form** (source, UTMs, click ID, referrer, landing page) so a lead can be
   attributed to a channel at all. Store it under keys that can't collide with the form tool's built-ins.
6. **A ranking shown to a client is checked live**, from the town, with the AI Overview loaded (§7). GSC average
   position blends every searcher, device, image block and AI placement.

**Provenance.** First retainer: the analytics "conversion" had been counting contact-page views, and never the quote
form at all (2026-08). A batch of our own verification submissions sat in the lead table with false attribution
(2026-08). A quarterly draft's headline ranking claim did not reproduce live (2026-11).

---

## 4. Targeting: aim at top three or an AI Overview naming

**Rule.** Every content brief names a target of **top-3 organic**, or the business **named** (not merely cited)
in Google's AI Overview. A brief whose success criterion is "page one" is not accepted.

- **Page one is the halfway mark, not nothing.** It's the prerequisite for top-3, the leading indicator the ranking
  lag forces us to rely on, and legitimate client-facing evidence of work. Report it (§6); never present it as KPI
  movement.
- **Persist or drop by depth and direction, never by clicks alone.** Shallow and improving (4–10, moving up):
  persist; that's where to spend. Shallow and flat across two windows: decide on the competitive read. Deep and
  flat (>12 across two windows): drop it.
- **Rank query shapes by where top-3 is reachable, not by volume.** On a local service business,
  `[service] + [city]` is usually held by incumbents with the same page type. Informational long-tail, comparison
  and cost content, and conversational "who is the best…" questions are where a newer site can win, and the last
  group is where AI Overviews name businesses.
- **Cited vs named is the opportunity.** If the AI Overview quotes the site as a source while naming competitors as
  the picks, content quality is already sufficient; work on whatever drives the naming.
- **Don't commission content against a gap whose cause is geographic** (map-pack distance from the business
  address). Content can't move it.
- **Apply the client's standing rulings before asking.** Where an existing client decision already settles a
  question (which markets they serve, what they don't sell), apply it and don't escalate.

**Provenance.** First retainer, 2026-09: on the measured service+city searches, positions 4–10 returned zero clicks
across hundreds of impressions, while a live check showed organic space was reachable but the site sat several
screens down. 2026-11: the first verified AI Overview naming came on a conversational "who is the best [service]
contractor in [town]" search, from a location page ranking #1.
⚠️ **The evidence is asymmetric:** "4–10 earns nothing" was measured; "top-3 pays" is a prior from general CTR
behavior. Say so when quoting it.

---

## 5. The content program: a calendar raced against the ranking lag

**Rules.**
1. **Work back from the target date.** Rankings lag 3–6 months, so the last publish date that reliably counts is
   about six months before the target. Put that date on the calendar as a rail; work after it can't register.
2. **Seed the calendar from classified demand, inversely to existing coverage.** Spend slots where demand is high
   and coverage thin, not where the blog already has posts.
3. **Weekly cadence, briefs batched three to four at a time, about a month ahead.** A missed week is a lost slot,
   not a deferred one. If cadence can't hold, cut to a named priority subset out loud; never slip silently.
4. **Pipeline: brief → draft → review.**
   - **Brief** (in a Claude Code session, so it lands as a file): a **competitive read is non-skippable** (AI
     Overview cited vs named, the top 5–7 organic results, the top 2–3 competitor pages' H1, length, cities,
     reviews, brands, whether they publish cost), the target band, a thesis no competitor could write, and sourced
     figures only.
   - **Draft** (claude.ai): hand the session exactly two files, a self-contained **context pack** (voice, hard scope
     boundaries, credentials verbatim, service area, target band, publishing rules) and the brief. Nothing else,
     nothing less.
   - **Review** (Michael, the only gate), with checks that each earned their place by failing once: claims hidden in
     lists; credentials transferred across brands; SEO title and description present; every figure sourced;
     drafting-venue artifacts (a second `<h1>`, an empty `<h2>`, HTML comments carrying editorial notes, all
     invisible in an editor); required internal links present and resolving; length and meta within limits;
     **US spelling run through a scanner**; no negative capability claims ("we don't" is a business fact and can be
     as wrong as "we do").
5. **Get an autonomy grant for execution, and keep facts with the client.** The client delegates the program;
   Michael reviews and publishes. New facts about the business (prices, capabilities, credentials, awards) still
   come from the client, asynchronously. Write around the gap and add the fact when it arrives; never hold a slot for
   it and never invent it.

**Provenance.** First retainer, 2026-09 → 11: a weekly calendar planned against a target-minus-six-months rail
shipped every slot on its date. In review, drafting-venue artifacts arrived on every early draft and British
spelling on every draft; a required internal link was dropped once; two negative capability claims were caught.

---

## 6. Reporting to the client

**Structure, in this order:**
1. **The KPI, stated plainly**, as a series of non-overlapping 28-day windows against the target. If it's flat, say
   flat and explain the lag in one paragraph. Name a first (the first click on a program post) as a first, not a
   trend.
2. **Visibility: the work showing up in search.** Page-one and top-3 counts over time for non-brand searches,
   **homepage excluded** so local-pack rows don't inflate it, plus a short table of **live-checked** examples.
   Quote an AI Overview that names the business; it's the strongest evidence available.
3. **Leads**, from the form's records, tests removed, with first-touch source.
4. **Supporting numbers** (§2).
5. **Delivery of the work**: what was published, on schedule or not, early positions labeled as leading indicators.
6. **What's next**, with dates.

**Rules.**
- **Report favorable rank and visibility even at zero clicks.** The work is meant to produce leads, but underselling
  the interest it generates is its own failure; missing clicks have many causes (AI answers, layout, rankings still
  settling). Label it honestly: never present a position gain as KPI movement, and always put the click number
  beside it.
- **Live-check every named ranking before it goes in.** Drop rows that don't reproduce; keep GSC for trends.
- **Re-pull the series fresh for each report** and note any restatement internally (GSC data settles for days).
- **US English and the client's register.** Internal docs drift British; convert anything lifted from them.
- **Draft in the project with an internal header** (sources, exclusions, restatements, what was left out and why);
  strip it before sending. Michael sends.

**Provenance.** First retainer, first quarterly, 2026-11. The first draft omitted a favorable ranking because it had
no clicks, and Michael ruled that interest the work produces is reported, not undersold. The revised draft's
GSC-average highlight table then failed a live spot-check on half its rows and was rebuilt from live results, which
surfaced an AI Overview naming the business.

---

## 7. Paid SEO data (DataForSEO): retainer clients only, capped

**Rule.** `~/claude-config/bin/dfs.py` makes paid calls only for sites recorded as on SEO retainer on that box,
within per-call, daily and monthly caps that live in a config file with no command-line override. Exceptions are
dated overrides, added only when Michael asks. The webapp is inferred from `/webapps/<app>/`; anywhere else
(a flat site, the Mac) pass `--client NAME`. Setup and caps: `wordpress-runcloud.md` → DataForSEO.

**Use it for** what GSC can't answer: a real organic rank, what a searcher in a specific town sees, whether the AI
Overview **names** or only **cites** the business, and search volume for terms the site doesn't rank for yet. Always
pass `--coord` (the town's lat,lng) and `--aio` for local work.

**Provenance.** First retainer, 2026-11: the first live runs surfaced three API quirks (the redirect-wrapped domain,
small towns invalid as location names, the asynchronous AI Overview) and changed a client report.

---

## 8. The client's time is the scarce resource

**Rules.**
- **Nothing waits on the client.** No open item is owned by them or blocked on their reply. An unknown fact is
  written around and the item closes; if they volunteer it later, apply it then.
- **Don't take the client a question data or a standing ruling can answer.** Escalating an answerable question
  spends their attention and implies a settled ruling is open.
- **Batch what genuinely needs them** (facts about the business: prices, scope, credentials) into one message;
  Michael picks what gets sent.

**Provenance.** First retainer, 2026-08 (a market question escalated that a standing client ruling already
answered) and 2026-11 (open items closed or rewritten when "nothing waits on the client" became absolute).

---

## 9. Starting a new retainer: checklist

Works for a WordPress site or a flat site; the WordPress/RunCloud specifics are in `wordpress-runcloud.md`.

1. **Declare it.** Add `engagement: seo-retainer` to the project `CLAUDE.md` (`knowledgebase/99` template). Record
   the site in that box's `~/.dataforseo/config.json` `"retainer"` list (on WordPress boxes, `wordpress-runcloud.md`
   step 3a asks once per box). The two must agree; a mismatch is a drift report for Michael.
2. **Copy this file** into the project's docs as its working copy, with an empty section below the seam.
3. **Per-client GSC/GA4 access** (on RunCloud: `wordpress-runcloud.md` → Search Console + GA4; the same
   service-account pattern works for any site).
4. **Audit the conversion signal and the lead capture** before quoting anything (§3): what analytics counts,
   first-touch capture on the form (a form plugin's hidden fields, or the flat site's form handler), and a
   test-submission screen against wherever submissions are stored.
5. **Baseline document**, dated, with the rebuild commands (§3).
6. **Propose the KPI in writing:** non-brand clicks, the brand rule in code, the censoring disclosed, the supporting
   numbers, and a target derived from the addressable demand (§2). Get it agreed before reporting against it.
7. **Targeting rule and content calendar**, against the target-minus-six-months rail (§4–5). The publish step differs
   by stack; the brief → draft → review pipeline doesn't.
8. **Context pack and brief template** before the first draft (§5).
9. **Ask for the execution autonomy grant** early (§5).
10. **Schedule the quarterly report**, which is also this file's harvest (see the seam), and a periodic live AI
    Overview check on the program's main terms (§6–7).

---

# === RETAINER SECTION — "we learned" ===

> Empty in the master. In a retainer project's copy, new rules and corrections append here, reviewed at each
> quarterly report. Entry format:
>
> ```
> ### [Short rule title]
> **Rule:** [what to do]
> **Why:** [the mechanism]
> **Provenance:** [retainer, generic description], [YYYY-MM] — [the incident, scrubbed]
> **Extends / contradicts:** [the § above it touches, if any; a contradiction is flagged, not edited]
> ```
