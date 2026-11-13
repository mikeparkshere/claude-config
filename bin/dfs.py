#!/usr/bin/env python3
"""
dfs.py: DataForSEO client with spend guardrails. Agency-wide account, one ledger per box.

  python3 ~/claude-config/bin/dfs.py balance                 # account balance (free call)
  python3 ~/claude-config/bin/dfs.py spend                   # this box's ledger: today / month / by webapp
  python3 ~/claude-config/bin/dfs.py serp "deck builder near me" --coord "40.71,-74.01" --aio \
      [--device desktop] [--depth 10] [--find example.com]
  python3 ~/claude-config/bin/dfs.py volume "patio cost" "deck cost" --location "United States"
  python3 ~/claude-config/bin/dfs.py ranked example.com --location "United States" [--limit 100]
  Any command: --dry-run (print the request + cost estimate, call nothing) · --fresh (skip the cache) · --note TEXT

GUARDRAILS (read before raising any of them; raising one needs Michael's OK in the session):
  0. SEO-retainer sites only. Paid calls run only for a webapp listed in "retainer" in ~/.dataforseo/config.json.
     The webapp is the one you are inside (/webapps/<app>/), or --client NAME. Anything else is refused, including
     "no webapp". A one-off exception is an "overrides" entry with an expiry, added only when Michael asks:
       {"retainer": ["app-example"], "retainer_asked": "2026-01-15",
        "overrides": {"app-x": {"until": "2026-11-20", "why": "Michael: one-off competitor read"}}}
     No "retainer" key at all means the box was never asked: the session startup asks Michael and records it.
  1. Endpoint allowlist. Only the commands above. No generic passthrough, no task_post bulk queues.
  2. Size caps per call: serp depth <= 20, volume <= 100 keywords, ranked limit <= 200.
  3. Spend caps, checked BEFORE every paid call against a worst-case estimate:
       per call $0.25 · per box per day $2.00 · per box per month $15.00
     Override only in ~/.dataforseo/config.json ({"per_call": .., "daily": .., "monthly": ..}). There is no
     command-line flag to exceed a cap, on purpose: an agent can add a flag without anyone noticing.
  4. The estimate is the larger of the built-in ceiling and the highest cost ever actually billed for that
     endpoint on this box, so it learns from real invoices and never trusts a guess that turned out low.
  5. Cache: an identical request is served from disk (SERP 24 h, volume 30 days, ranked 7 days). Free repeats.
  6. Ledger: every paid call is appended to ~/.dataforseo/ledger.jsonl with the BILLED cost from the response,
     the webapp it ran from, and a note. `spend` reads it.
  The caps are per box. The account-wide backstop is the spending limit in the DataForSEO dashboard.

Credentials: ~/.dataforseo/credentials, one line "login:password" (API login, not the dashboard password),
mode 600, scp'd from the Mac. Never in this repo, never pasted into chat, never in ~/.env.
"""
import argparse, base64, fcntl, hashlib, json, os, stat, sys, time
from datetime import datetime, timezone
import requests

API = "https://api.dataforseo.com/v3"
HOME = os.path.expanduser("~/.dataforseo")
CRED = os.path.join(HOME, "credentials")
LEDGER = os.path.join(HOME, "ledger.jsonl")
CACHE = os.path.join(HOME, "cache")
CONFIG = os.path.join(HOME, "config.json")

DEFAULT_CAPS = {"per_call": 0.25, "daily": 2.00, "monthly": 15.00}
# Worst-case ceilings in USD per call, deliberately above list price. Real billed costs raise these (rule 4);
# nothing lowers them.
ENDPOINTS = {
    "serp":   {"path": "serp/google/organic/live/advanced",                 "ceiling": 0.02, "ttl": 86400},
    "volume": {"path": "keywords_data/google_ads/search_volume/live",       "ceiling": 0.10, "ttl": 30 * 86400},
    "ranked": {"path": "dataforseo_labs/google/ranked_keywords/live",       "ceiling": 0.05, "ttl": 7 * 86400},
}
MAX_DEPTH, MAX_KEYWORDS, MAX_LIMIT = 20, 100, 200


def die(msg):
    sys.exit(f"dfs: {msg}")


CLIENT = None  # set from --client


def webapp():
    if CLIENT:
        return CLIENT
    parts = os.getcwd().split(os.sep)
    return parts[parts.index("webapps") + 1] if "webapps" in parts and parts.index("webapps") + 1 < len(parts) else "-"


def config():
    if not os.path.exists(CONFIG):
        return {}
    with open(CONFIG) as f:
        return json.load(f)


def retainer_gate():
    """Paid calls only for SEO-retainer webapps, or a dated override Michael asked for."""
    cfg, app = config(), webapp()
    if "retainer" not in cfg:
        die(f"this box has never recorded which webapps are on SEO retainer ({CONFIG} has no \"retainer\" key). "
            "Ask Michael, record the list (an empty list is a valid answer), then retry.")
    if app == "-":
        die("can't tell which webapp this is for. Run it from /webapps/<app>/ or pass --client NAME.")
    if app in cfg["retainer"]:
        return f"retainer:{app}"
    ov = (cfg.get("overrides") or {}).get(app)
    if ov and datetime.now(timezone.utc).strftime("%Y-%m-%d") <= ov.get("until", ""):
        return f"override:{app} until {ov['until']} ({ov.get('why', '')})"
    die(f"{app} is not on SEO retainer on this box (retainer: {', '.join(cfg['retainer']) or 'none'}). "
        "Paid DataForSEO calls need Michael to ask for an override.")


def auth():
    if not os.path.exists(CRED):
        die(f"no credentials at {CRED}. scp it from the Mac (one line, login:password), then chmod 600.")
    mode = stat.S_IMODE(os.stat(CRED).st_mode)
    if mode & 0o077:
        die(f"{CRED} is mode {oct(mode)}; run chmod 600 {CRED} first.")
    with open(CRED) as f:
        line = f.read().strip()
    if ":" not in line:
        die(f"{CRED} must be one line, login:password.")
    return "Basic " + base64.b64encode(line.encode()).decode()


def caps():
    c = dict(DEFAULT_CAPS)
    c.update({k: float(v) for k, v in config().items() if k in DEFAULT_CAPS})
    return c


def ledger_rows():
    if not os.path.exists(LEDGER):
        return []
    with open(LEDGER) as f:
        return [json.loads(l) for l in f if l.strip()]


def estimate(cmd):
    seen = [r["cost"] for r in ledger_rows() if r["cmd"] == cmd]
    return max([ENDPOINTS[cmd]["ceiling"]] + seen)


def spent(rows, since):
    return sum(r["cost"] for r in rows if r["ts"] >= since)


def guard(cmd):
    """Refuse the call if its worst-case cost would break any cap. Returns the estimate."""
    c, est, rows = caps(), estimate(cmd), ledger_rows()
    now = datetime.now(timezone.utc)
    day = now.strftime("%Y-%m-%dT00:00:00")
    month = now.strftime("%Y-%m-01T00:00:00")
    today, mtd = spent(rows, day), spent(rows, month)
    if est > c["per_call"]:
        die(f"{cmd} could cost up to ${est:.4f}, over the per-call cap of ${c['per_call']:.2f}.")
    if today + est > c["daily"]:
        die(f"daily cap: ${today:.4f} spent today on this box + up to ${est:.4f} > ${c['daily']:.2f}. Stopping.")
    if mtd + est > c["monthly"]:
        die(f"monthly cap: ${mtd:.4f} spent this month on this box + up to ${est:.4f} > ${c['monthly']:.2f}. Stopping.")
    return est


def cache_path(cmd, body):
    h = hashlib.sha256((cmd + json.dumps(body, sort_keys=True)).encode()).hexdigest()[:24]
    return os.path.join(CACHE, f"{cmd}-{h}.json")


def call(cmd, body, args):
    """POST one task to an allowlisted endpoint, behind cache, caps and ledger."""
    cp = cache_path(cmd, body)
    if not args.fresh and os.path.exists(cp) and time.time() - os.path.getmtime(cp) < ENDPOINTS[cmd]["ttl"]:
        age = (time.time() - os.path.getmtime(cp)) / 3600
        print(f"(cached {age:.1f} h ago, no charge; --fresh to re-pull)", file=sys.stderr)
        with open(cp) as f:
            return json.load(f)
    basis = retainer_gate()
    est = guard(cmd)
    if args.dry_run:
        print(json.dumps({"endpoint": ENDPOINTS[cmd]["path"], "body": body, "worst_case_usd": est}, indent=1))
        sys.exit(0)
    r = requests.post(f"{API}/{ENDPOINTS[cmd]['path']}", json=body,
                      headers={"Authorization": auth(), "Content-Type": "application/json"}, timeout=120)
    try:
        j = r.json()
    except ValueError:
        die(f"HTTP {r.status_code}, non-JSON response.")
    cost = float(j.get("cost") or 0)
    os.makedirs(HOME, mode=0o700, exist_ok=True)
    with open(LEDGER, "a") as f:
        fcntl.flock(f, fcntl.LOCK_EX)
        f.write(json.dumps({"ts": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%S"), "cmd": cmd,
                            "cost": cost, "webapp": webapp(), "basis": basis, "note": args.note or "",
                            "what": body[0].get("keyword") or body[0].get("target") or
                                    f"{len(body[0].get('keywords', []))} keywords"}) + "\n")
    os.chmod(LEDGER, 0o600)
    if j.get("status_code") != 20000:
        die(f"API {j.get('status_code')}: {j.get('status_message')} (billed ${cost:.4f})")
    task = (j.get("tasks") or [{}])[0]
    if task.get("status_code") != 20000:
        die(f"task {task.get('status_code')}: {task.get('status_message')} (billed ${cost:.4f})")
    os.makedirs(CACHE, mode=0o700, exist_ok=True)
    with open(cp, "w") as f:
        json.dump(j, f)
    print(f"(billed ${cost:.4f}; worst case was ${est:.4f})", file=sys.stderr)
    return j


def real_site(it):
    """(domain, url) for an organic item. Google now wraps result links in google.com/goto redirects, and
    DataForSEO reports domain=google.com for those; the real site is only in the breadcrumb."""
    dom, url = it.get("domain") or "", it.get("url") or ""
    if dom in ("google.com", "www.google.com") and it.get("breadcrumb"):
        url = it["breadcrumb"].replace(" › ", "/").replace("›", "/").strip()
        dom = url.split("//", 1)[-1].split("/", 1)[0]
        if dom.startswith("www."):
            dom = dom[4:]
    return dom, url


def results(j):
    return ((j.get("tasks") or [{}])[0].get("result") or [{}])


# --- commands -----------------------------------------------------------------------------------------------

def cmd_balance(args):
    r = requests.get(f"{API}/appendix/user_data", headers={"Authorization": auth()}, timeout=60).json()
    if r.get("status_code") != 20000:
        die(f"API {r.get('status_code')}: {r.get('status_message')}")
    money = (results(r)[0] or {}).get("money", {})
    print(f"balance ${float(money.get('balance', 0)):.2f}")
    if args.json:
        print(json.dumps(money, indent=1))


def cmd_spend(args):
    rows, c = ledger_rows(), caps()
    now = datetime.now(timezone.utc)
    today = spent(rows, now.strftime("%Y-%m-%dT00:00:00"))
    mtd = spent(rows, now.strftime("%Y-%m-01T00:00:00"))
    cfg = config()
    print("retainer: " + (", ".join(cfg["retainer"]) or "none" if "retainer" in cfg else "NEVER ASKED (ask Michael)"))
    for app, ov in (cfg.get("overrides") or {}).items():
        live = now.strftime("%Y-%m-%d") <= ov.get("until", "")
        print(f"override: {app} until {ov.get('until')} ({ov.get('why', '')}){'' if live else ' EXPIRED'}")
    print(f"today ${today:.4f} of ${c['daily']:.2f} · month ${mtd:.4f} of ${c['monthly']:.2f} · per call ${c['per_call']:.2f}")
    by = {}
    for r in rows:
        if r["ts"] >= now.strftime("%Y-%m-01"):
            k = (r["webapp"], r["cmd"])
            n, s = by.get(k, (0, 0.0))
            by[k] = (n + 1, s + r["cost"])
    for (app, cmd), (n, s) in sorted(by.items()):
        print(f"  {app:<20} {cmd:<7} {n:>4} calls  ${s:.4f}")


def cmd_serp(args):
    if args.depth > MAX_DEPTH:
        die(f"depth {args.depth} > {MAX_DEPTH}. Deeper SERPs cost more and rarely matter.")
    body = [{"keyword": args.keyword, "location_name": args.location, "language_code": "en",
             "device": args.device, "depth": args.depth}]
    if args.aio:
        body[0]["load_async_ai_overview"] = True
    if args.coord:  # "lat,lng" of the town itself; small towns are often missing from location_name
        body[0].pop("location_name"); body[0]["location_coordinate"] = args.coord
    j = call("serp", body, args)
    if args.json:
        print(json.dumps(j, indent=1)); return
    res = results(j)[0] or {}
    items = res.get("items") or []
    print(f"{args.keyword!r} · {args.location} · {args.device} · types: {', '.join(res.get('item_types') or [])}")
    find = (args.find or "").lower()
    for it in items:
        if it.get("type") == "ai_overview":
            refs = it.get("references") or []
            print(f"AI OVERVIEW at rank {it.get('rank_absolute')}: {len(refs)} references")
            for ref in refs:
                mark = "  <==" if find and find in (ref.get("domain") or "").lower() else ""
                print(f"    ref  {ref.get('domain')}  {ref.get('url')}{mark}")
            if find:
                text = json.dumps(it).lower()
                print(f"    {args.find} named in AIO text: {'YES' if find.split('.')[0] in text else 'no'} (crude check; read --json)")
        elif it.get("type") in ("local_pack",):
            print(f"local_pack  {it.get('rank_absolute'):>3}  {it.get('title')}")
    print("organic:")
    hit = None
    for it in items:
        if it.get("type") == "organic":
            dom, url = real_site(it)
            mark = "  <==" if find and find in dom.lower() else ""
            if mark and hit is None:
                hit = it
            print(f"  #{it.get('rank_group'):>2} (abs {it.get('rank_absolute'):>2})  {dom}  {url}  · {(it.get('title') or '')[:50]}{mark}")
    if find:
        print(f"{args.find}: " + (f"organic #{hit['rank_group']}" if hit else f"not in the top {args.depth} organic"))


def cmd_volume(args):
    if len(args.keywords) > MAX_KEYWORDS:
        die(f"{len(args.keywords)} keywords > {MAX_KEYWORDS} per call.")
    body = [{"keywords": args.keywords, "location_name": args.location, "language_code": "en"}]
    j = call("volume", body, args)
    if args.json:
        print(json.dumps(j, indent=1)); return
    for r in sorted(results(j), key=lambda r: -(r.get("search_volume") or 0)):
        print(f"{r.get('search_volume') or 0:>7}  cpc {r.get('cpc') or 0:>6.2f}  {r.get('competition') or '-':<7}  {r.get('keyword')}")


def cmd_ranked(args):
    if args.limit > MAX_LIMIT:
        die(f"limit {args.limit} > {MAX_LIMIT}.")
    body = [{"target": args.target, "location_name": args.location, "language_code": "en", "limit": args.limit,
             "order_by": ["keyword_data.keyword_info.search_volume,desc"]}]
    j = call("ranked", body, args)
    if args.json:
        print(json.dumps(j, indent=1)); return
    for it in (results(j)[0] or {}).get("items") or []:
        kd, se = it.get("keyword_data") or {}, (it.get("ranked_serp_element") or {}).get("serp_item") or {}
        print(f"{(kd.get('keyword_info') or {}).get('search_volume') or 0:>7}  #{se.get('rank_group', '-'):>3}  "
              f"{kd.get('keyword')}  {se.get('url', '')}")


def main():
    p = argparse.ArgumentParser(description="DataForSEO with spend guardrails (see module docstring).")
    sub = p.add_subparsers(dest="cmd", required=True)
    common = argparse.ArgumentParser(add_help=False)
    common.add_argument("--dry-run", action="store_true")
    common.add_argument("--fresh", action="store_true")
    common.add_argument("--json", action="store_true")
    common.add_argument("--note", default="")
    common.add_argument("--location", default="United States")
    common.add_argument("--client", help="webapp name, when not running from inside /webapps/<app>/")
    sub.add_parser("balance", parents=[common])
    sub.add_parser("spend", parents=[common])
    s = sub.add_parser("serp", parents=[common]); s.add_argument("keyword")
    s.add_argument("--device", default="desktop", choices=["desktop", "mobile"])
    s.add_argument("--depth", type=int, default=10); s.add_argument("--find")
    s.add_argument("--coord", help='search from a point, "lat,lng" (overrides --location)')
    s.add_argument("--aio", action="store_true",
                   help="also load Google's AI Overview (it renders asynchronously; without this it is often missing)")
    v = sub.add_parser("volume", parents=[common]); v.add_argument("keywords", nargs="+")
    r = sub.add_parser("ranked", parents=[common]); r.add_argument("target"); r.add_argument("--limit", type=int, default=100)
    args = p.parse_args()
    global CLIENT
    CLIENT = args.client
    {"balance": cmd_balance, "spend": cmd_spend, "serp": cmd_serp, "volume": cmd_volume, "ranked": cmd_ranked}[args.cmd](args)


if __name__ == "__main__":
    main()
