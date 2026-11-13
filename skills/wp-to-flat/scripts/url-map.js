#!/usr/bin/env node
'use strict';
/**
 * wp-to-flat url-map: the redirect plan, from reduced/urls.json (+ an optional plan file).
 *
 *   node url-map.js <harvest dir> [--plan plan.json] [--format htaccess|nginx] [--out <file>]
 *
 * Writes:
 *   <out>                        rules: an .htaccess block (default; hybrid webapps) or nginx directives
 *   reduced/redirect-plan.md     the table for MIGRATION.md: from, to, code, why
 *   reduced/redirect-tests.tsv   from, expected status, expected Location: input for parity-check.js
 *
 * Defaults (each overridable in the plan):
 *   - Kept pages map to themselves (no rule). A page renamed or dropped in the plan gets a 301.
 *   - ?p= / ?page_id= with a known ID -> that page's new URL; other WP query routes -> home.
 *   - Archives (terms, authors, post type archives) and attachment pages -> 301 home.
 *   - Old sitemap names -> /sitemap.xml.
 *   - Referenced media with a mapping in plan.media -> 301 to the new file, any WP size suffix or
 *     -scaled variant included. Referenced media without one is listed as a gap.
 *   - Everything else WordPress served (wp-admin, wp-includes, wp-json, wp-content, wp-login.php, xmlrpc.php,
 *     feeds) -> 410 Gone, which crawlers drop faster than a 404.
 *   - plan.host set -> www.<host> -> https://<host>.
 *
 * plan.json (all keys optional):
 *   { "host": "example.com",
 *     "pages":    { "/old-path/": "/new-path/" },            // rename; null = dropped (-> home)
 *     "archives": { "/team/": "/#team" },                    // target for an archive/term/author URL
 *     "media":    { "2024/04/photo.webp": "/assets/img/photo.webp" },  // uploads path, no size suffix
 *     "extra":    [ { "from": "/old/", "to": "/new/", "code": 301 } ] }
 *
 * Every target is absolute https://%{HTTP_HOST}/...: Apache sits behind nginx and never sees TLS, so a relative
 * or mod_dir redirect would say http://. Targets with a #fragment get a query-preserving twin, or Apache
 * appends the query (utm_ tags) after the fragment where analytics never sees it.
 */
const fs = require('fs');
const path = require('path');

const args = process.argv.slice(2);
const dir = args[0];
const opt = (k, d) => { const i = args.indexOf('--' + k); return i > -1 ? args[i + 1] : d; };
if (!dir) { console.error('usage: node url-map.js <harvest dir> [--plan plan.json] [--format htaccess|nginx] [--out file]'); process.exit(2); }
const fmt = opt('format', 'htaccess');
const U = JSON.parse(fs.readFileSync(path.join(dir, 'reduced/urls.json'), 'utf8'));
const media = JSON.parse(fs.readFileSync(path.join(dir, 'reduced/media.json'), 'utf8'));
const planFile = opt('plan', fs.existsSync(path.join(dir, 'plan.json')) ? path.join(dir, 'plan.json') : null);
const plan = planFile ? JSON.parse(fs.readFileSync(planFile, 'utf8')) : {};
const out = opt('out', path.join(dir, 'reduced', fmt === 'nginx' ? 'redirects.conf' : 'redirects.htaccess'));

const rows = [];    // plan table
const tests = [];   // from, code, location
const gaps = [];
const R = { host: [], early: [], media: [], gone: [], routes: [], query: [] };   // rule groups, in output order
const esc = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const testHost = plan.host ? 'https://' + plan.host : '';

/* ---------- target resolution ---------- */
const pages = U.urls.filter(u => u.kind === 'page');
const newPath = p => (plan.pages && Object.prototype.hasOwnProperty.call(plan.pages, p)) ? (plan.pages[p] === null ? '/' : plan.pages[p]) : p;

/* ---------- rule builders (one rule = from-pattern, to, code, why, sample for the test) ---------- */
function redirect(group, re, to, code, why, sample, label) {
  R[group].push({ re: re, to: to, code: code || 301, why: why });
  rows.push([label || sample, to, code || 301, why]);
  if (sample) tests.push([sample, code || 301, to.indexOf('http') === 0 ? to : testHost + to]);
}
function gone(re, why, sample, label) {
  R.gone.push({ re: re, code: 410, why: why });
  rows.push([label || sample, '—', 410, why]);
  if (sample) tests.push([sample, 410, '']);
}

// www -> apex
if (plan.host) {
  R.host.push({ host: plan.host });
  rows.push(['www.' + plan.host + '/*', 'https://' + plan.host + '/*', 301, 'one host']);
  tests.push(['https://www.' + plan.host + '/', 301, 'https://' + plan.host + '/']);
}

// Kept pages: self (tested 200). Renamed/dropped: 301.
pages.forEach(p => {
  const to = newPath(p.path);
  if (to === p.path) { tests.push([p.path, 200, '']); return; }
  redirect('early', '^' + esc(p.path.replace(/^\//, '')).replace(/\/$/, '') + '/?$', to, 301, plan.pages[p.path] === null ? 'page dropped' : 'page renamed', p.path);
});
if (U.urls.some(u => u.kind === '404')) tests.push(['/wp-to-flat-missing-page/', 404, '']);

// Archives, terms, authors
U.urls.filter(u => ['term', 'author', 'archive'].includes(u.kind)).forEach(u => {
  const to = (plan.archives && plan.archives[u.path]) || '/';
  redirect('early', '^' + esc(u.path.replace(/^\//, '')).replace(/\/$/, '') + '/?$', to, 301, u.kind + ' archive', u.path);
});
// Plan archives that WordPress didn't list (e.g. a CPT base with no archive page)
Object.keys(plan.archives || {}).filter(p => !U.urls.some(u => u.path === p)).forEach(p =>
  redirect('early', '^' + esc(p.replace(/^\//, '')).replace(/\/$/, '') + '/?$', plan.archives[p], 301, 'old route (plan)', p));
// Path prefixes with pages under them but no URL of their own (a CPT base like /team/ with has_archive off):
// people trim URLs, so suggest a target.
Array.from(new Set(pages.map(p => p.path.replace(/[^/]+\/$/, '')).filter(b => b !== '/' && b !== ''))).forEach(b => {
  if (!U.urls.some(u => u.path === b) && !(plan.archives && plan.archives[b])) gaps.push('Prefix ' + b + ' has pages under it but no page of its own (404 on the old site too): consider plan.archives["' + b + '"]');
});
// Any author / attachment route the harvest couldn't enumerate
redirect('routes', '^author/', '/', 301, 'author archives', '/author/someone/', '/author/*');
redirect('routes', '(^|/)attachment/', '/', 301, 'attachment pages', '/some-page/attachment/photo/', '*/attachment/*');

// Sitemaps
redirect('early', '^(sitemaps|sitemap_index|wp-sitemap|[a-z0-9_-]+-sitemap\\d*)\\.xml$', '/sitemap.xml', 301, 'old sitemap names', '/sitemap_index.xml', '/sitemap_index.xml, /page-sitemap.xml, /wp-sitemap.xml …');

// Existing plugin redirects: carry them, pointed at their new targets
Object.keys(U.redirects || {}).forEach(src => (U.redirects[src] || []).forEach(r => {
  let from = r.from || r.url || '', to = r.to || r.action_data || r.url_to || '';
  if (src === 'rankmath') { try { const s = (typeof r.sources === 'string' && r.sources[0] === 'a') ? null : r.sources; from = s && s[0] ? s[0].pattern : String(r.sources); } catch (e) { from = String(r.sources); } }
  if (!from || !to) return;
  const fromPath = '/' + String(from).replace(/^https?:\/\/[^/]+/, '').replace(/^\//, '');
  const toPath = newPath(String(to).replace(U.home, '') || '/');
  redirect('early', '^' + esc(fromPath.replace(/^\//, '')).replace(/\/$/, '') + '/?$', toPath, 301, 'existing redirect (' + src + ')', fromPath);
}));

// Extra rules from the plan
(plan.extra || []).forEach(x => redirect('early', '^' + esc(x.from.replace(/^\//, '')).replace(/\/$/, '') + '/?$', x.to, x.code || 301, x.why || 'plan', x.from));

// Media: mapped -> 301; referenced but unmapped -> gap
const mediaMap = plan.media || {};
media.filter(m => !m.missing).forEach(m => {
  const to = mediaMap[m.file];
  if (!to) { gaps.push('No new path for referenced media ' + m.file + ' (used by ' + m.used_by.join(', ') + '): add it to plan.media, or accept a 410'); return; }
  const dot = m.file.lastIndexOf('.'), base = m.file.slice(0, dot), ext = m.file.slice(dot + 1);
  // Any WP size suffix, the -scaled copy, and ShortPixel/WebP Express style .webp/.avif twins.
  const re = '^wp-content/uploads/' + esc(base) + '(-scaled)?(-\\d+x\\d+)?\\.' + esc(ext) + '(\\.(webp|avif))?$';
  redirect('media', re, to, 301, 'old media', '/wp-content/uploads/' + base + '-300x300.' + ext, '/wp-content/uploads/' + m.file + ' (+ sizes)');
});
Object.keys(mediaMap).filter(k => !media.some(m => m.file === k)).forEach(k => gaps.push('plan.media maps ' + k + ', which the harvest did not see referenced (typo, or an image only external links use?)'));

// Gone for good
gone('^(wp-admin|wp-includes|wp-json|wp-content)(/|$)', 'WordPress internals and unused uploads', '/wp-json/', '/wp-admin/, /wp-includes/, /wp-json/, /wp-content/* (unmapped)');
gone('^(wp-login|xmlrpc|wp-cron|wp-signup|wp-trackback|wp-comments-post)\\.php$', 'WordPress endpoints', '/wp-login.php', '/wp-login.php, /xmlrpc.php …');
gone('^(comments/)?feed/?$', 'feeds', '/feed/', '/feed/, /comments/feed/');
gone('^.+/feed/?$', 'feeds', '/some-page/feed/', '*/feed/');

// Query-string routes: known IDs to their page, the rest home
pages.forEach(p => {
  const to = newPath(p.path);
  R.query.push({ q: '(^|&)(p|page_id)=' + p.id + '(&|$)', to: to });
  rows.push(['/?p=' + p.id + ', /?page_id=' + p.id, to, 301, 'WP ID link to ' + p.title]);
  tests.push(['/?page_id=' + p.id, 301, testHost + to]);
});
R.query.push({ q: '(^|&)(p|page_id|author|attachment_id|cat|tag)=[^&]+', to: '/' });
rows.push(['/?p=…, ?page_id=…, ?author=…, ?cat=…, ?tag=…, ?attachment_id=… (other)', '/', 301, 'WP query routes']);
tests.push(['/?p=999999', 301, testHost + '/']);

/* ---------- render ---------- */
function htTarget(to) { return /^https?:/.test(to) ? to : 'https://%{HTTP_HOST}' + to; }
function htRedirect(r) {
  const to = htTarget(r.to);
  if (to.indexOf('#') > -1) {   // query before the fragment, then the plain version
    const [a, frag] = to.split('#');
    return 'RewriteCond %{QUERY_STRING} .\nRewriteRule ' + r.re + ' ' + a + (a.indexOf('?') > -1 ? '&' : '?') + '%{QUERY_STRING}#' + frag + ' [R=' + r.code + ',L,NE]\n' +
      'RewriteRule ' + r.re + ' ' + to + ' [R=' + r.code + ',L,NE]';
  }
  return 'RewriteRule ' + r.re + ' ' + to + ' [R=' + r.code + ',L,NE]';
}
let text;
if (fmt === 'nginx') {
  const ng = to => /^https?:/.test(to) ? to : 'https://$host' + to;
  const L = ['# BEGIN wp-to-flat redirects (generated by url-map.js; regenerate, don\'t hand-edit)',
    '# nginx server-block directives. RunCloud: the API cannot write nginx config, so these go in through the panel;',
    '# on a hybrid webapp prefer the .htaccess output instead.'];
  R.host.forEach(h => L.push('if ($host = www.' + h.host + ') { return 301 https://' + h.host + '$request_uri; }'));
  R.query.forEach(q => L.push('if ($args ~ "' + q.q + '") { return 301 ' + ng(q.to) + '; }'));
  R.early.concat(R.media, R.routes).forEach(r => L.push('location ~ "/' + r.re.replace(/^\^/, '^/').replace(/^\/\^\//, '^/') + '" { return ' + r.code + ' ' + ng(r.to) + '; }'));
  R.gone.forEach(r => L.push('location ~ "' + r.re.replace(/^\^/, '^/').replace(/^\^\/\.\+/, '^/.+') + '" { return 410; }'));
  L.push('# END wp-to-flat redirects');
  text = L.join('\n').replace(/location ~ "\/\^\//g, 'location ~ "^/').replace(/location ~ "\/\(\^\|\/\)/g, 'location ~ "(^|/)') + '\n';
} else {
  const L = ['# BEGIN wp-to-flat redirects (generated by url-map.js from the harvest; regenerate, don\'t hand-edit)',
    '# Goes after the site\'s own routing. Needs RewriteEngine On. Hybrid webapp: nginx hands missing files to Apache,',
    '# so old media paths reach these rules too.'];
  if (R.host.length) { L.push('# One host'); R.host.forEach(h => L.push('RewriteCond %{HTTP_HOST} ^www\\.(' + esc(h.host) + ')$ [NC]', 'RewriteRule ^ https://%1%{REQUEST_URI} [R=301,L,NE]')); }
  if (R.early.length) { L.push('# Old pages, archives, sitemaps, plugin redirects'); R.early.forEach(r => L.push(htRedirect(r))); }
  if (R.media.length) { L.push('# Old media, any WordPress size suffix'); R.media.forEach(r => L.push(htRedirect(r))); }
  L.push('# Everything else WordPress served is gone for good');
  R.gone.forEach(r => L.push('RewriteRule ' + r.re + ' - [G,L]'));
  L.push('# Stray WordPress routes');
  R.routes.forEach(r => L.push(htRedirect(r).replace(/\[R=301,L,NE\]/g, '[R=301,L,NE,QSD]')));
  L.push('# WordPress ID and query links');
  R.query.forEach(q => L.push('RewriteCond %{QUERY_STRING} ' + q.q + ' [NC]', 'RewriteRule ^ ' + htTarget(q.to) + ' [R=301,L,QSD]'));
  L.push('# END wp-to-flat redirects');
  text = L.join('\n') + '\n';
}
fs.writeFileSync(out, text);

const md = ['# Redirect plan', '', '_Generated by url-map.js' + (planFile ? ' with ' + path.basename(planFile) : ' (no plan file: defaults only)') + '. Rules: `' + path.relative(dir, out) + '`._', ''];
if (gaps.length) md.push('## Gaps', '', gaps.map(g => '- ' + g).join('\n'), '');
md.push('## Kept URLs (no rule; must return 200)', '', pages.filter(p => newPath(p.path) === p.path).map(p => '- `' + p.path + '` ' + p.title).join('\n'), '');
md.push('## Rules', '', '| From | To | Code | Why |', '| --- | --- | --- | --- |');
rows.forEach(r => md.push('| `' + r[0] + '` | ' + (r[1] === '—' ? '—' : '`' + r[1] + '`') + ' | ' + r[2] + ' | ' + r[3] + ' |'));
fs.writeFileSync(path.join(dir, 'reduced/redirect-plan.md'), md.join('\n') + '\n');
fs.writeFileSync(path.join(dir, 'reduced/redirect-tests.tsv'), tests.map(t => t.join('\t')).join('\n') + '\n');
console.log('url-map: ' + rows.length + ' plan rows, ' + tests.length + ' tests, ' + gaps.length + ' gaps -> ' + out);
if (gaps.length) gaps.forEach(g => console.log('  gap: ' + g));
