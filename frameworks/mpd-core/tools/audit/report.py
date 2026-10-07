#!/usr/bin/env python3
"""mpd-core Stage 2 — aggregate raw/site-NN.json into reports/usage-report.md.

Reads only the anonymous raw files. Prints a short summary; the full report goes to disk.
"""
import collections, datetime, glob, json, os, re, sys

OUTDIR = os.environ.get("OUTDIR") or sys.exit("OUTDIR is required (the same folder audit.sh wrote to)")
RAW = sorted(glob.glob(os.path.join(OUTDIR, "raw", "site-*.json")))
OUT = os.path.join(OUTDIR, "usage-report.md")
if not RAW:
    sys.exit("no raw files — run audit.sh first")

def _fix(o):
    """PHP encodes an empty map as []; turn those back into {} so .items() works."""
    if isinstance(o, dict):
        return {k: _fix(v) for k, v in o.items()}
    if isinstance(o, list) and not o:
        return {}
    return o
sites = [_fix(json.load(open(p))) for p in RAW]
for s in sites:
    s["acss_toggles_on"] = list(s.get("acss_toggles_on") or [])
N = len(sites)

def tally(getter):
    total, per = collections.Counter(), collections.defaultdict(set)
    for s in sites:
        for name, n in getter(s).items():
            total[name] += n
            per[name].add(s["site"])
    return total, per

acss_vars, acss_vars_sites = tally(lambda s: s["var_refs"]["acss"])
cust_vars, cust_vars_sites = tally(lambda s: s["var_refs"]["custom"])
bare_vars, bare_sites = tally(lambda s: {**s["bare_refs"]["acss"], **s["bare_refs"]["custom"]})
classes, classes_sites = tally(lambda s: collections.Counter(s["gclass_use"]) + collections.Counter(s["raw_classes"]))

FAMILIES = [
    ("Section spacing", r"^section-space-"), ("Spacing", r"^space-"), ("Gaps / gutter", r"^(gutter|content-gap|container-gap|grid-gap|gap)"),
    ("Headings", r"^h[1-6]($|-)"), ("Text sizes", r"^text-(xs|s|m|l|xl|xxl)$"), ("Type (other)", r"^(heading|text|font)-"),
    ("Layout / width", r"^(content-width|width-|root-font-size|vp-)"), ("Grid", r"^grid-"), ("Radius", r"^radius"),
    ("Focus", r"^focus-"), ("Shadow", r"^(box-)?shadow"), ("Buttons", r"^btn-"), ("Forms (--f-*)", r"^f-"),
    ("Semantic color", r"^(warning|info|success|danger)"),
    ("Brand color", r"^(primary|secondary|accent|action|base|neutral|tertiary|white|black|shade)"),
]
def family(name):
    for label, rx in FAMILIES:
        if re.search(rx, name):
            return label
    return "Other"

def table(counter, persite, rows=None, min_sites=1):
    out = ["| name | uses | sites |", "|---|---:|---:|"]
    items = [(k, v) for k, v in counter.most_common() if len(persite[k]) >= min_sites]
    for k, v in items[:rows] if rows else items:
        out.append(f"| `{k}` | {v} | {len(persite[k])}/{N} |")
    return "\n".join(out) if len(out) > 2 else "_none_"

L = []
W = L.append
W("# Stage 2 — ACSS usage report")
W("")
W(f"Generated {datetime.date.today():%B %-d, %Y} from {N} sites (`audit/raw/site-NN.json`, anonymous IDs). "
  "Produced by `audit/audit.sh` + `extract.php` + `report.py`; nothing here was read from raw postmeta in a session.")
W("")
W("## Sites audited")
W("")
W("| site | Bricks | ACSS | posts w/ Bricks data | elements | var() uses | ACSS settings changed from default |")
W("|---|---|---|---:|---:|---:|---:|")
for s in sites:
    v = sum(s["var_refs"]["acss"].values()) + sum(s["var_refs"]["custom"].values()) + s["var_refs"]["redacted_custom"]
    W(f"| {s['site']} | {s['bricks']} | {s.get('acss_version') or '—'} | {s['posts']} | {s['elements']} | {v} | {s.get('acss_changed_from_default', '—')} |")
W("")
W("Where the `var()` uses come from (all sites):")
W("")
src = collections.Counter()
for s in sites: src.update(s["var_by_source"])
W(" · ".join(f"{k} **{v}**" for k, v in src.most_common()))
W("")

W("## 1. ACSS variables, by family")
W("")
W("Ranked by total uses; **sites** is how many of the audited sites use it at all. Only variables ACSS itself declares on that site count here.")
W("")
fam = collections.defaultdict(list)
for k in acss_vars:
    fam[family(k)].append(k)
order = [f for f, _ in FAMILIES] + ["Other"]
for f in order:
    if f not in fam: continue
    sub = collections.Counter({k: acss_vars[k] for k in fam[f]})
    W(f"### {f} — {len(sub)} variables, {sum(sub.values())} uses")
    W("")
    W(table(sub, acss_vars_sites))
    W("")

W("## 2. Utility classes (ACSS)")
W("")
W("Element uses of ACSS-registered global classes plus ACSS class names in raw `_cssClasses`.")
W("")
W(table(classes, classes_sites))
W("")
gc_def = sum(s["gclass_defined"]["acss"] for s in sites)
cu = sum(s["gclass_custom_uses"] for s in sites)
W(f"For scale: the audited sites define **{gc_def}** ACSS global classes between them and use **{len(classes)}** distinct ones. "
  f"Custom (project BEM) global classes account for **{cu}** element uses. Custom names aren't recorded, by design.")
W("")

W("## 3. Patterns")
W("")
W("| pattern | occurrences | sites |")
W("|---|---:|---:|")
for p in ["clickable-parent", "focus-parent", "root_selector", "include_clickable", "include_focus"]:
    tot = sum(s["patterns"][p] for s in sites)
    n = sum(1 for s in sites if s["patterns"][p])
    W(f"| `{p}` | {tot} | {n}/{N} |")
W("")
W("`root_selector` counts literal `%root%`. Bricks rewrites it to the real selector on UI save, so a low count doesn't mean the pattern is rare.")
W("")

W("## 4. Bare `--var` shorthand")
W("")
tot_bare = sum(bare_vars.values()) + sum(s["bare_refs"]["redacted_custom"] for s in sites)
W(f"**{tot_bare} occurrences** of bare `--name` in non-code Bricks fields across {N} sites.")
W("")
W(table(bare_vars, bare_sites) if bare_vars else
  "None found. The detector was checked on known cases before the run (`--space-m` and `calc(--space-l / 2)` count; `var(--x)`, `var( --x )` and a `--x:` declaration don't), "
  "so this is a real zero, not a silent failure. Likely reason: Advanced Themer's autoformat wraps variables in `var()` as they're typed.")
W("")

W("## 5. Candidates to drop — used on only one site")
W("")
single = collections.Counter({k: v for k, v in acss_vars.items() if len(acss_vars_sites[k]) == 1})
W(f"{len(single)} ACSS variables appear on exactly one site.")
W("")
W(table(single, acss_vars_sites))
W("")
single_c = collections.Counter({k: v for k, v in classes.items() if len(classes_sites[k]) == 1})
W(f"{len(single_c)} ACSS utility classes appear on exactly one site:")
W("")
W(table(single_c, classes_sites))
W("")

W("## 6. Custom (non-ACSS) variables")
W("")
W("Project tokens that ACSS doesn't declare: pinned brand tokens, child-theme variables, plugin variables. "
  "Any name containing a site's slug, domain or title was redacted and only counted. Use these to spot recurring custom tokens that mpd-core might want to standardize.")
W("")
red = sum(s["var_refs"]["redacted_custom"] for s in sites)
W(f"Redacted (site-identifying) uses: **{red}**.")
W("")
W(table(cust_vars, cust_vars_sites, min_sites=2))
W("")
W(f"Custom variables on only one site: {sum(1 for k in cust_vars if len(cust_vars_sites[k]) == 1)} names (not listed).")
W("")

W("## 7. ACSS settings that shape the framework")
W("")
keys = ["root-font-size", "vp-min", "vp-max", "space-scale", "mob-space-scale", "text-scale", "mob-text-scale",
        "heading-scale", "mob-heading-scale", "base-radius", "radius-scale", "focus-width", "focus-style", "body-max-width"]
W("| setting | default | " + " | ".join(s["site"] for s in sites) + " |")
W("|---|---|" + "---|" * N)
for k in keys:
    d = next((s["acss_shape"][k]["default"] for s in sites if s.get("acss_shape")), "")
    W(f"| `{k}` | {d} | " + " | ".join(str((s.get("acss_shape") or {}).get(k, {}).get("value", "—")) for s in sites) + " |")
W("")
tog = collections.Counter()
for s in sites: tog.update(s.get("acss_toggles_on", []))
W("Framework toggles switched on (`option-*`), by number of sites:")
W("")
W(", ".join(f"`{k}` {v}/{N}" for k, v in tog.most_common()) or "_none_")
W("")
W("## 8. Proposed keep / drop (for Mike to correct)")
W("")
W("Rules, applied mechanically. Strike or flip anything that's wrong; Stage 3 takes the corrected list.")
W("")
W("1. **Keep** anything used on 2+ sites.")
W("2. **Keep the whole ladder.** If any step of a scale is kept, keep every step (`space`, `text`, `section-space`, `h1`–`h6`, `radius`), so the scale stays coherent.")
W("3. **Colors by pattern.** Role names come from the brand guide. What carries over is which shade and transparency steps recur.")
W("4. **Drop** single-site items outside a kept ladder.")
W("5. **Out of mpd-core scope:** ACSS's button system (`btn-*` variables) and form layer (`f-*`). mpd-core ships no button or form module; forms are the open WS Form decision.")
W("")
LADDERS = {
    "space": ["space-" + x for x in ["xs", "s", "m", "l", "xl", "xxl"]],
    "text": ["text-" + x for x in ["xs", "s", "m", "l", "xl", "xxl"]],
    "section-space": ["section-space-" + x for x in ["xs", "s", "m", "l", "xl", "xxl"]],
    "headings": ["h%d" % i for i in range(1, 7)],
    "radius": ["radius", "radius-xs", "radius-s", "radius-m", "radius-l", "radius-xl", "radius-xxl", "radius-circle"],
}
in_ladder = {v: k for k, vs in LADDERS.items() for v in vs}
COLOR = re.compile(r"^(primary|secondary|accent|action|base|neutral|tertiary|white|black|success|warning|info|danger)(?:-(.*))?$")
keep, drop, oos = [], [], []
kept_ladders = {in_ladder[k] for k in acss_vars if k in in_ladder and len(acss_vars_sites[k]) >= 2}
shade_sites = collections.defaultdict(set)
for k in acss_vars:
    m = COLOR.match(k)
    if m and m.group(2):
        shade_sites[m.group(2)] |= acss_vars_sites[k]
for k in sorted(acss_vars):
    n = len(acss_vars_sites[k])
    if k.startswith(("btn-", "f-")):
        oos.append(k); continue
    if k in in_ladder:
        (keep if in_ladder[k] in kept_ladders else drop).append(k); continue
    m = COLOR.match(k)
    if m:
        continue  # colors summarized below by pattern
    (keep if n >= 2 else drop).append(k)
for name, vs in LADDERS.items():
    if name in kept_ladders:
        for v in vs:
            if v not in keep: keep.append(v + " *(fills ladder, unused)*")
W("**Keep (non-color):** " + ", ".join(f"`{k}`" if "*" not in k else f"`{k.split()[0]}` " + " ".join(k.split()[1:]) for k in keep))
W("")
W("**Drop (non-color):** " + (", ".join(f"`{k}`" for k in drop) or "_none_"))
W("")
W("**Out of scope (button and form systems):** " + (", ".join(f"`{k}`" for k in oos) or "_none_"))
W("")
roles = collections.defaultdict(set)
for k in acss_vars:
    m = COLOR.match(k)
    if m: roles[m.group(1)] |= acss_vars_sites[k]
W("**Color roles** (sites using any variable in the role): " + ", ".join(f"`{r}` {len(v)}/{N}" for r, v in sorted(roles.items(), key=lambda kv: -len(kv[1]))))
W("")
keep_sh = sorted([sh for sh, v in shade_sites.items() if len(v) >= 2], key=lambda x: (not x.startswith(("ultra", "light", "dark", "hover")), x))
drop_sh = sorted([sh for sh, v in shade_sites.items() if len(v) < 2])
W("**Shade and transparency steps to keep** (recur on 2+ sites in any role): " + ", ".join(f"`-{x}`" for x in keep_sh))
W("")
W("**Steps to drop** (one site only): " + ", ".join(f"`-{x}`" for x in drop_sh))
W("")
W("**Utility classes.** Keep `list--none` (3/5), plus the patterns `clickable-parent`, `focus-parent`, `focus-parent--outline`; those are spec Stage 5 regardless of count. "
  "`btn--primary` / `btn--secondary` / `btn--base` / `btn--outline` are Bricks button-style classes, not mpd-core utilities (`03`: \"Bricks button utility classes … are Bricks-injected\"). Confirm at Stage 4. "
  "`form--light` waits on the WS Form decision. Drop the rest. The spec's starter utilities `.padding--*`, `.margin--*`, `.gap--*`, `.text--*` and `.bg--*` have almost no real use, so propose shipping none of them in alpha.")
W("")
W("**Framework values for Stage 3:** root 100% (already locked) · fluid range **360–1366** (5/5 and 4/5 sites; the spec default is 1440) · focus width 2px, outline style.")
W("")
W("---")
W("")
W("**Checkpoint:** Mike marks keep/drop. Stage 3 (`TOKENS.md`) takes only what's kept.")

os.makedirs(os.path.dirname(OUT), exist_ok=True)
open(OUT, "w").write("\n".join(L) + "\n")

# terminal summary
print(f"{N} sites · {len(acss_vars)} ACSS vars used ({sum(acss_vars.values())} uses) · {len(classes)} ACSS classes used · bare={tot_bare}")
print("families:", ", ".join(f"{f} {len(fam[f])}" for f in order if f in fam))
print(f"single-site ACSS vars: {len(single)} · custom vars on 2+ sites: {sum(1 for k in cust_vars if len(cust_vars_sites[k]) >= 2)}")
print("report:", os.path.relpath(OUT))
