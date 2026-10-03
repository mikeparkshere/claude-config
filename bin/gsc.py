#!/usr/bin/env python3
"""
gsc.py — Google Search Console query tool (service-account auth, no external deps).

Uses system `cryptography` + `requests` only. Signs a service-account JWT (RS256),
exchanges it for an OAuth2 access token, and calls the Search Console v3 REST API.

Key + site come from a client profile (see "Client profiles" below), so inside a
webapp no flags are needed:
  python3 ~/claude-config/bin/gsc.py sites            # must list ONLY this client's property
  python3 ~/claude-config/bin/gsc.py email
  python3 ~/claude-config/bin/gsc.py query --start 2026-05-24 --end 2026-06-23 \
      --dimensions page --rows 50 [--filter 'page~~/locations/'] [--search-type web]
  python3 ~/claude-config/bin/gsc.py --client NAME query ...   # explicit profile
Scope: webmasters.readonly. GSC data lags ~2-3 days; set --end accordingly.

`query` prints a TSV table (and totals). `--json` dumps raw API JSON instead.
"""
import argparse, base64, json, os, sys, time
import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding

TOKEN_URL = "https://oauth2.googleapis.com/token"
API = "https://www.googleapis.com/webmasters/v3"
SCOPE = "https://www.googleapis.com/auth/webmasters.readonly"


# --- Client profiles --------------------------------------------------------
# Keys and property IDs never live in this repo. Each client gets a profile at
# ~/.gsc/<name>.json (mode 600), next to its key:
#   {"key": "~/.gsc/<name>-sa.json", "ga4_property": "123456789",
#    "gsc_site": "sc-domain:example.com"}
# Resolution: --client NAME > the webapp you are inside (/webapps/<app>/ → <app>.json)
# > the only profile on the box. Explicit --key/--property/--site always win.
PROFILE_DIR = os.path.expanduser("~/.gsc")


def resolve_profile(name):
    if not name:
        parts = os.getcwd().split(os.sep)
        if "webapps" in parts and parts.index("webapps") + 1 < len(parts):
            app = parts[parts.index("webapps") + 1]
            if os.path.exists(os.path.join(PROFILE_DIR, f"{app}.json")):
                name = app
    if not name:
        found = [f[:-5] for f in sorted(os.listdir(PROFILE_DIR))
                 if f.endswith(".json") and not f.endswith("-sa.json")] if os.path.isdir(PROFILE_DIR) else []
        if len(found) == 1:
            name = found[0]
        elif found:
            sys.exit(f"Several profiles in {PROFILE_DIR} ({', '.join(found)}) — pass --client NAME.")
        else:
            return {}
    path = os.path.join(PROFILE_DIR, f"{name}.json")
    if not os.path.exists(path):
        sys.exit(f"No profile {path}.")
    with open(path) as f:
        prof = json.load(f)
    prof["_name"] = name
    return prof


def b64u(data: bytes) -> str:
    return base64.urlsafe_b64encode(data).rstrip(b"=").decode("ascii")


def load_key(path):
    with open(os.path.expanduser(path)) as f:
        return json.load(f)


def access_token(sa):
    now = int(time.time())
    header = {"alg": "RS256", "typ": "JWT"}
    claims = {
        "iss": sa["client_email"],
        "scope": SCOPE,
        "aud": TOKEN_URL,
        "iat": now,
        "exp": now + 3600,
    }
    signing_input = (b64u(json.dumps(header).encode()) + "." +
                     b64u(json.dumps(claims).encode())).encode("ascii")
    pk = serialization.load_pem_private_key(sa["private_key"].encode(), password=None)
    sig = pk.sign(signing_input, padding.PKCS1v15(), hashes.SHA256())
    assertion = signing_input.decode() + "." + b64u(sig)
    r = requests.post(TOKEN_URL, data={
        "grant_type": "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion": assertion,
    }, timeout=30)
    if r.status_code != 200:
        sys.exit(f"Token exchange failed ({r.status_code}): {r.text}")
    return r.json()["access_token"]


def auth_headers(sa):
    return {"Authorization": f"Bearer {access_token(sa)}"}


def cmd_email(sa, args):
    print(sa["client_email"])


def cmd_sites(sa, args):
    r = requests.get(f"{API}/sites", headers=auth_headers(sa), timeout=30)
    if r.status_code != 200:
        sys.exit(f"sites.list failed ({r.status_code}): {r.text}")
    entries = r.json().get("siteEntry", [])
    if not entries:
        print("No properties accessible to this service account yet.")
        print("→ Grant it access in Search Console (Settings → Users and permissions).")
        return
    for e in entries:
        print(f"{e['permissionLevel']:18}  {e['siteUrl']}")


def _parse_filter(expr):
    # 'page~~/locations/'  contains;  'page==https://...'  equals;  'query~/seo/'  includes
    for op, gop in (("~~", "contains"), ("==", "equals"), ("!~", "notContains"), ("!=", "notEquals")):
        if op in expr:
            dim, val = expr.split(op, 1)
            return {"dimension": dim.strip(), "operator": gop, "expression": val.strip()}
    sys.exit(f"Bad --filter '{expr}'. Use dim~~val (contains) or dim==val (equals).")


def cmd_query(sa, args):
    body = {
        "startDate": args.start,
        "endDate": args.end,
        "dimensions": args.dimensions.split(",") if args.dimensions else [],
        "rowLimit": args.rows,
        "type": args.search_type,
    }
    if args.filter:
        body["dimensionFilterGroups"] = [{"filters": [_parse_filter(f) for f in args.filter]}]
    url = f"{API}/sites/{requests.utils.quote(args.site, safe='')}/searchAnalytics/query"
    r = requests.post(url, headers=auth_headers(sa), json=body, timeout=60)
    if r.status_code != 200:
        sys.exit(f"searchAnalytics.query failed ({r.status_code}): {r.text}")
    data = r.json()
    if args.json:
        print(json.dumps(data, indent=2)); return
    rows = data.get("rows", [])
    dims = body["dimensions"]
    header = dims + ["clicks", "impressions", "ctr", "position"]
    print("\t".join(header))
    tc = ti = 0.0
    for row in rows:
        keys = row.get("keys", [])
        c, i = row["clicks"], row["impressions"]
        tc += c; ti += i
        cells = keys + [f"{c:.0f}", f"{i:.0f}", f"{row['ctr']*100:.2f}%", f"{row['position']:.1f}"]
        print("\t".join(str(x) for x in cells))
    if rows:
        avg_ctr = (tc / ti * 100) if ti else 0
        print(f"\nTOTAL\tclicks={tc:.0f}\timpressions={ti:.0f}\tctr={avg_ctr:.2f}%\trows={len(rows)}")
    else:
        print("(no rows for this range/filter)")


def main():
    p = argparse.ArgumentParser(description="Google Search Console query tool")
    p.add_argument("--client", help="profile name in ~/.gsc/ (default: current webapp)")
    p.add_argument("--key", help="service-account JSON key path (overrides profile)")
    sub = p.add_subparsers(dest="cmd", required=True)
    sub.add_parser("email", help="print the service-account email (to grant in GSC)")
    sub.add_parser("sites", help="list properties this SA can access")
    q = sub.add_parser("query", help="run a Search Analytics query")
    q.add_argument("--site", help="sc-domain:example.com OR https://example.com/ (default: profile gsc_site)")
    q.add_argument("--start", required=True)
    q.add_argument("--end", required=True)
    q.add_argument("--dimensions", default="page", help="comma list: page,query,date,country,device")
    q.add_argument("--rows", type=int, default=100)
    q.add_argument("--filter", action="append", help="e.g. 'page~~/locations/' (repeatable, AND)")
    q.add_argument("--search-type", default="web", choices=["web", "image", "video", "news", "discover"])
    q.add_argument("--json", action="store_true")
    args = p.parse_args()

    prof = resolve_profile(args.client)
    args.key = args.key or prof.get("key")
    if not args.key:
        sys.exit("No key: pass --key or create a profile in ~/.gsc/ (see docstring).")
    if args.cmd == "query":
        args.site = args.site or prof.get("gsc_site")
        if not args.site:
            sys.exit("No site: pass --site or set gsc_site in the profile.")
    sa = load_key(args.key)
    {"email": cmd_email, "sites": cmd_sites, "query": cmd_query}[args.cmd](sa, args)


if __name__ == "__main__":
    main()
