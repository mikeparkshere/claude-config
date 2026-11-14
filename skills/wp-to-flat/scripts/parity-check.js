#!/usr/bin/env node
'use strict';
/**
 * wp-to-flat parity check: the old site (as harvested) vs the new build, page by page, plus every redirect.
 *
 *   node parity-check.js <harvest dir> --new <https://new-host> [--staging] [--plan plan.json]
 *
 *   --new      the new build's origin: staging or production
 *   --staging  the new build is a staging host: noindex there is expected, not a failure
 *
 * Needs a harvest run with --base-url (old rendered pages in raw/html/) and url-map.js output (redirect-tests.tsv).
 * Writes reduced/parity-report.md. Exit 1 if anything FAILs (Gate 4 blocker), else 0.
 *
 * Kept pages (old URL -> its new URL via plan.pages):
 *   FAIL   new page not 200; canonical missing or not self; noindex on a production host; no H1 or several H1s;
 *          title or description missing; generator meta present
 *   REVIEW title, description, H1, og:title/description differ from the old page; og:image missing or not 200;
 *          a schema type the old page had is gone
 *   PASS   everything else
 * New pages (in the new sitemap, not on the old site): the FAIL rules only.
 * Redirects: each redirect-tests.tsv row must give the expected status and Location, and every 301 must land on a
 *   200 in one hop (no chains).
 *
 * Plain HTTP, no JavaScript: nothing fires analytics. No dependencies; Node 14+.
 */
const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const { extractHead } = require('./lib/head');

const args = process.argv.slice(2);
const dir = args[0];
const opt = k => { const i = args.indexOf('--' + k); return i > -1 ? args[i + 1] : null; };
const NEW = (opt('new') || '').replace(/\/$/, '');
const staging = args.includes('--staging');
if (!dir || !NEW) { console.error('usage: node parity-check.js <harvest dir> --new <https://new-host> [--staging] [--plan plan.json]'); process.exit(2); }
const B = JSON.parse(fs.readFileSync(path.join(dir, 'raw/bundle.json'), 'utf8'));
const OLD = B.site.home.replace(/\/$/, '');
const planFile = opt('plan') || (fs.existsSync(path.join(dir, 'plan.json')) ? path.join(dir, 'plan.json') : null);
const plan = planFile ? JSON.parse(fs.readFileSync(planFile, 'utf8')) : {};
const newPath = p => (plan.pages && Object.prototype.hasOwnProperty.call(plan.pages, p)) ? (plan.pages[p] === null ? null : plan.pages[p]) : p;
const rel = u => u.replace(OLD, '') || '/';

function get(url, method) {
  return new Promise(resolve => {
    const lib = url.startsWith('https:') ? https : http;
    const req = lib.request(url, { method: method || 'GET', headers: { 'User-Agent': 'wp-to-flat-parity/1', 'Accept': 'text/html,*/*' }, timeout: 20000 }, res => {
      let body = '';
      res.setEncoding('utf8');
      res.on('data', c => { if (body.length < 3e6) body += c; });
      res.on('end', () => resolve({ status: res.statusCode, location: res.headers.location || '', headers: res.headers, body: body }));
    });
    req.on('timeout', () => { req.destroy(); resolve({ status: 0, location: '', headers: {}, body: '', error: 'timeout' }); });
    req.on('error', e => resolve({ status: 0, location: '', headers: {}, body: '', error: e.message }));
    req.end();
  });
}
const abs = (loc, base) => { try { return new URL(loc, base).href; } catch (e) { return loc; } };
const norm = s => String(s || '').replace(/\s+/g, ' ').replace(/[’‘]/g, "'").replace(/[“”]/g, '"').trim();

(async () => {
  const rows = [];   // {url, check, level, detail}
  const add = (url, check, level, detail) => rows.push({ url, check, level, detail: detail || '' });

  function hardChecks(url, page, res, expectedPath) {
    if (res.status !== 200) { add(url, 'status', 'FAIL', 'HTTP ' + res.status + (res.location ? ' → ' + res.location : '')); return false; }
    const robots = (page.robots + ' ' + (res.headers['x-robots-tag'] || '')).toLowerCase();
    if (/noindex/.test(robots)) add(url, 'robots', staging ? 'PASS' : 'FAIL', 'noindex' + (staging ? ' (staging: expected)' : ' on a production host'));
    if (!page.canonical_abs) add(url, 'canonical', 'FAIL', 'missing');
    else if (abs(page.canonical_abs, NEW) !== NEW + expectedPath) add(url, 'canonical', 'FAIL', 'not self: ' + page.canonical_abs);
    if (page.h1.length !== 1) add(url, 'h1', 'FAIL', page.h1.length + ' H1s' + (page.h1.length ? ': ' + page.h1.join(' | ') : ''));
    if (!page.title) add(url, 'title', 'FAIL', 'missing');
    if (!page.description) add(url, 'description', 'FAIL', 'missing');
    if (page.generator) add(url, 'generator', 'FAIL', page.generator);
    return true;
  }

  // 1. Kept pages
  const kept = B.posts.map(p => ({ old: rel(p.url), key: p.type + '-' + (p.slug || p.id), title: p.title }));
  for (const k of kept) {
    const np = newPath(k.old);
    if (np === null) { add(k.old, 'page', 'PASS', 'dropped in plan (redirect tested below)'); continue; }
    const of = path.join(dir, 'raw/html', k.key + '.html');
    if (!fs.existsSync(of)) { add(k.old, 'old page', 'FAIL', 'not harvested: rerun harvest.sh with --base-url'); continue; }
    const o = extractHead(fs.readFileSync(of, 'utf8'), OLD);
    const res = await get(NEW + np);
    const n = extractHead(res.body, NEW);
    const label = k.old + (np !== k.old ? ' → ' + np : '');
    if (!hardChecks(label, n, res, np)) continue;
    const cmp = (field, name) => {
      if (!o[field] && !n[field]) return;
      if (norm(o[field]) === norm(n[field])) add(label, name, 'PASS');
      else add(label, name, 'REVIEW', 'old: "' + (o[field] || '(none)') + '"<br>new: "' + (n[field] || '(none)') + '"');
    };
    cmp('title', 'title'); cmp('description', 'description');
    const oh = norm(o.h1[0]), nh = norm(n.h1[0]);
    add(label, 'h1', oh === nh ? 'PASS' : 'REVIEW', oh === nh ? '' : 'old: "' + (o.h1.join(' | ') || '(none)') + '"<br>new: "' + n.h1.join(' | ') + '"');
    cmp('og_title', 'og:title'); cmp('og_description', 'og:description');
    if (!n.og_image) add(label, 'og:image', 'REVIEW', 'missing');
    else { const r = await get(abs(n.og_image, NEW), 'HEAD'); add(label, 'og:image', r.status === 200 ? 'PASS' : 'REVIEW', r.status === 200 ? '' : n.og_image + ' → HTTP ' + r.status); }
    const lost = o.schema_types.filter(t => !n.schema_types.includes(t));
    add(label, 'schema', lost.length ? 'REVIEW' : 'PASS', lost.length ? 'old types gone: ' + lost.join(', ') + ' (new: ' + n.schema_types.join(', ') + ')' : 'new: ' + n.schema_types.join(', '));
  }

  // 2. New pages: in the new sitemap, not on the old site
  const sm = await get(NEW + '/sitemap.xml');
  const smPaths = (sm.body.match(/<loc>[^<]+/g) || []).map(x => x.replace(/<loc>https?:\/\/[^/]+/, ''));
  if (sm.status !== 200) add('/sitemap.xml', 'status', 'FAIL', 'HTTP ' + sm.status);
  const keptNew = kept.map(k => newPath(k.old)).filter(Boolean);
  keptNew.filter(p => !smPaths.includes(p)).forEach(p => add(p, 'sitemap', 'FAIL', 'kept page missing from /sitemap.xml'));
  for (const p of smPaths.filter(p => !keptNew.includes(p))) {
    const res = await get(NEW + p);
    if (hardChecks(p + ' (new)', extractHead(res.body, NEW), res, p)) add(p + ' (new)', 'page', 'PASS', 'new page, basics ok');
  }

  // 3. Redirects
  const tf = path.join(dir, 'reduced/redirect-tests.tsv');
  if (!fs.existsSync(tf)) add('(redirects)', 'tests', 'FAIL', 'no redirect-tests.tsv: run url-map.js');
  else {
    const prodOrigin = plan.host ? 'https://' + plan.host : null;
    const swap = u => (prodOrigin && u.indexOf(prodOrigin) === 0 ? NEW + u.slice(prodOrigin.length) : u);
    for (const line of fs.readFileSync(tf, 'utf8').split('\n').filter(Boolean)) {
      const [from, codeS, loc] = line.split('\t');
      const code = +codeS;
      if (/^https?:/.test(from) && prodOrigin && NEW !== prodOrigin && from.indexOf('www.' + plan.host) > -1) { add(from, 'redirect', 'PASS', 'skipped: www rule only testable on the production host'); continue; }
      const url = /^https?:/.test(from) ? swap(from) : NEW + from;
      if (code === 200) continue;   // kept pages are checked above
      const r = await get(url);
      const want = loc ? swap(loc) : '';
      const gotLoc = r.location ? abs(r.location, url) : '';
      if (r.status !== code) { add(from, 'redirect', 'FAIL', 'want ' + code + (want ? ' → ' + want : '') + ', got ' + r.status + (gotLoc ? ' → ' + gotLoc : '')); continue; }
      if (want && gotLoc !== want) { add(from, 'redirect', 'FAIL', 'want → ' + want + ', got → ' + gotLoc); continue; }
      if (code === 301) {
        const t = await get(gotLoc.split('#')[0]);
        if (t.status !== 200) { add(from, 'redirect', 'FAIL', 'target ' + gotLoc + ' answers ' + t.status + (t.location ? ' → ' + t.location + ' (chain)' : '')); continue; }
      }
      add(from, 'redirect', 'PASS', code + (gotLoc ? ' → ' + gotLoc.replace(NEW, '') : ''));
    }
  }

  // Report
  const count = l => rows.filter(r => r.level === l).length;
  const md = ['# Parity report', '',
    '_Old: ' + OLD + ' (harvested ' + B.harvested_at.slice(0, 16).replace('T', ' ') + ' UTC). New: ' + NEW + (staging ? ' (staging)' : '') + '. Generated ' + new Date().toISOString().slice(0, 16).replace('T', ' ') + ' UTC by parity-check.js._', '',
    '**' + count('FAIL') + ' FAIL, ' + count('REVIEW') + ' REVIEW, ' + count('PASS') + ' PASS.** ' + (count('FAIL') ? 'Gate 4 is blocked until every FAIL is fixed.' : 'No blockers. Every REVIEW needs a yes from Michael (intended change) or a fix.'), ''];
  ['FAIL', 'REVIEW', 'PASS'].forEach(l => {
    const rs = rows.filter(r => r.level === l);
    if (!rs.length) return;
    md.push('## ' + l, '', '| URL | Check | Detail |', '| --- | --- | --- |');
    rs.forEach(r => md.push('| `' + r.url + '` | ' + r.check + ' | ' + String(r.detail).replace(/\|/g, '\\|') + ' |'));
    md.push('');
  });
  fs.writeFileSync(path.join(dir, 'reduced/parity-report.md'), md.join('\n'));
  console.log('parity: ' + count('FAIL') + ' FAIL, ' + count('REVIEW') + ' REVIEW, ' + count('PASS') + ' PASS -> ' + path.join(dir, 'reduced/parity-report.md'));
  process.exit(count('FAIL') ? 1 : 0);
})();
