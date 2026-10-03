#!/usr/bin/env python3
"""
ga4.py — Google Analytics 4 (Data API v1) query tool. Service-account auth, no external deps.

Same self-contained pattern as gsc.py: signs a service-account JWT (RS256), exchanges
it for an OAuth2 token, calls the GA4 Data API. Key + property come from the same
client profile as gsc.py (see "Client profiles" below).

Scope: analytics.readonly. GA4 renamed Conversions -> Key events: the metric is `keyEvents`.
--limit defaults to 50 and sorts by the first metric, so rare events (e.g. generate_lead)
get truncated off multi-dimension reports: raise --limit when hunting one.

Usage:
  python3 ~/claude-config/bin/ga4.py metadata          # access check
  python3 ~/claude-config/bin/ga4.py report --start 2026-05-24 --end 2026-06-20 \
      --dimensions sessionDefaultChannelGroup --metrics sessions,totalUsers --limit 25
  python3 ~/claude-config/bin/ga4.py report --start 2026-05-24 --end 2026-06-20 \
      --dimensions date,eventName --metrics eventCount,keyEvents --limit 500
  python3 ~/claude-config/bin/ga4.py --client NAME report ...   # explicit profile
"""
import argparse, base64, json, os, sys, time
import requests
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import padding

TOKEN_URL = "https://oauth2.googleapis.com/token"
API = "https://analyticsdata.googleapis.com/v1beta"
SCOPE = "https://www.googleapis.com/auth/analytics.readonly"


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
    claims = {"iss": sa["client_email"], "scope": SCOPE, "aud": TOKEN_URL,
              "iat": now, "exp": now + 3600}
    signing_input = (b64u(json.dumps(header).encode()) + "." +
                     b64u(json.dumps(claims).encode())).encode("ascii")
    pk = serialization.load_pem_private_key(sa["private_key"].encode(), password=None)
    sig = pk.sign(signing_input, padding.PKCS1v15(), hashes.SHA256())
    assertion = signing_input.decode() + "." + b64u(sig)
    r = requests.post(TOKEN_URL, data={
        "grant_type": "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion": assertion}, timeout=30)
    if r.status_code != 200:
        sys.exit(f"Token exchange failed ({r.status_code}): {r.text}")
    return r.json()["access_token"]


def auth_headers(sa):
    return {"Authorization": f"Bearer {access_token(sa)}"}


def cmd_metadata(sa, args):
    url = f"{API}/properties/{args.property}/metadata"
    r = requests.get(url, headers=auth_headers(sa), timeout=30)
    if r.status_code != 200:
        sys.exit(f"metadata failed ({r.status_code}): {r.text}")
    data = r.json()
    mets = [m["apiName"] for m in data.get("metrics", [])]
    dims = [d["apiName"] for d in data.get("dimensions", [])]
    print(f"Access OK on property {args.property}.")
    print(f"\n{len(mets)} metrics. Conversion-related:")
    for m in mets:
        if any(k in m.lower() for k in ("conversion", "keyevent", "event")):
            print(f"  {m}")
    print(f"\nCommon metrics present: " +
          ", ".join(m for m in ("sessions", "totalUsers", "activeUsers",
                                "screenPageViews", "engagedSessions", "keyEvents",
                                "conversions") if m in mets))
    if args.json:
        print("\n--- all dimensions ---"); print(", ".join(dims))
        print("\n--- all metrics ---"); print(", ".join(mets))


def cmd_report(sa, args):
    body = {
        "dateRanges": [{"startDate": args.start, "endDate": args.end}],
        "dimensions": [{"name": d} for d in args.dimensions.split(",")] if args.dimensions else [],
        "metrics": [{"name": m} for m in args.metrics.split(",")],
        "limit": str(args.limit),
    }
    if args.dimensions:
        body["orderBys"] = [{"metric": {"metricName": args.metrics.split(",")[0]}, "desc": True}]
    url = f"{API}/properties/{args.property}:runReport"
    r = requests.post(url, headers=auth_headers(sa), json=body, timeout=60)
    if r.status_code != 200:
        sys.exit(f"runReport failed ({r.status_code}): {r.text}")
    data = r.json()
    if args.json:
        print(json.dumps(data, indent=2)); return
    dim_names = [h["name"] for h in data.get("dimensionHeaders", [])]
    met_names = [h["name"] for h in data.get("metricHeaders", [])]
    print("\t".join(dim_names + met_names))
    for row in data.get("rows", []):
        dvals = [d["value"] for d in row.get("dimensionValues", [])]
        mvals = [m["value"] for m in row.get("metricValues", [])]
        print("\t".join(dvals + mvals))
    print(f"\nrows={len(data.get('rows', []))}  (property {args.property}, {args.start}→{args.end})")


def main():
    p = argparse.ArgumentParser(description="GA4 Data API query tool")
    p.add_argument("--client", help="profile name in ~/.gsc/ (default: current webapp)")
    p.add_argument("--key", help="service-account JSON key path (overrides profile)")
    p.add_argument("--property", help="GA4 numeric property ID (overrides profile)")
    sub = p.add_subparsers(dest="cmd", required=True)
    m = sub.add_parser("metadata", help="confirm access + list available metrics/dimensions")
    m.add_argument("--json", action="store_true")
    q = sub.add_parser("report", help="run a GA4 report")
    q.add_argument("--start", required=True)
    q.add_argument("--end", required=True)
    q.add_argument("--dimensions", default="", help="comma list, e.g. sessionDefaultChannelGroup,landingPagePlusQueryString")
    q.add_argument("--metrics", default="sessions,totalUsers", help="comma list")
    q.add_argument("--limit", type=int, default=50)
    q.add_argument("--json", action="store_true")
    args = p.parse_args()

    prof = resolve_profile(args.client)
    args.key = args.key or prof.get("key")
    args.property = args.property or prof.get("ga4_property")
    if not args.key or not args.property:
        sys.exit("Need a key and a GA4 property: pass --key/--property or create a profile in ~/.gsc/.")
    sa = load_key(args.key)
    {"metadata": cmd_metadata, "report": cmd_report}[args.cmd](sa, args)


if __name__ == "__main__":
    main()
