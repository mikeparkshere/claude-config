#!/usr/bin/env node
'use strict';
/**
 * wp-to-flat reduce: <harvest>/raw/bundle.json -> <harvest>/reduced/
 *
 *   node reduce.js <harvest dir>
 *
 *   reduced/inventory.md       site summary, candidate check, page inventory, SEO, menus, forms, integrations,
 *                              fonts, media, other URLs, existing redirects, and FLAGS (feeds MIGRATION.md)
 *   reduced/pages/<key>.md     one per public URL: what renders there (header, content, footer) in reading order
 *   reduced/templates/<key>.md shared headers/footers, reduced once and referenced from the pages
 *   reduced/urls.json          every public URL with its kind, for url-map.js
 *   reduced/media.json         referenced images: id, file, alt, size, who uses them
 *
 * These files are what Claude reads. raw/ is not.
 * One reducer per builder in reducers/. A page built with a builder that has no reducer is listed in FLAGS
 * (Gate 1 stop). No dependencies; runs on Node 14+.
 */
const fs = require('fs');
const path = require('path');

const dir = process.argv[2];
if (!dir) { console.error('usage: node reduce.js <harvest dir>'); process.exit(2); }
const B = JSON.parse(fs.readFileSync(path.join(dir, 'raw/bundle.json'), 'utf8'));
const OUT = path.join(dir, 'reduced');
['', 'pages', 'templates'].forEach(d => fs.mkdirSync(path.join(OUT, d), { recursive: true }));

const reducers = {};
fs.readdirSync(path.join(__dirname, 'reducers')).forEach(f => { if (f.endsWith('.js')) reducers[f.replace(/\.js$/, '')] = require('./reducers/' + f); });

const flags = [];
const flag = s => { if (!flags.includes(s)) flags.push(s); };
const home = B.site.home.replace(/\/$/, '');
const rel = u => (typeof u === 'string' && u.indexOf(home) === 0 ? u.slice(home.length) || '/' : u);

/* ---------- text helpers ---------- */
const ENT = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', ndash: '–', mdash: '—', hellip: '…', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“', copy: '©', reg: '®', trade: '™', middot: '·', bull: '•', times: '×' };
function decode(s) {
  return s.replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (m, e) => {
    if (e[0] === '#') return String.fromCodePoint(e[1].toLowerCase() === 'x' ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10));
    return ENT[e.toLowerCase()] !== undefined ? ENT[e.toLowerCase()] : m;
  });
}
function text(html) {
  let s = String(html == null ? '' : html);
  s = s.replace(/<!--[\s\S]*?-->/g, '')
    .replace(/<(script|style)[\s\S]*?<\/\1>/gi, '')
    .replace(/<a\b[^>]*href=["']([^"']*)["'][^>]*>([\s\S]*?)<\/a>/gi, (m, h, t) => '[' + t.replace(/<[^>]+>/g, '').trim() + '](' + rel(h) + ')')
    .replace(/<(strong|b)\b[^>]*>([\s\S]*?)<\/\1>/gi, '**$2**')
    .replace(/<(em|i)\b[^>]*>([\s\S]*?)<\/\1>/gi, '_$2_')
    .replace(/<li\b[^>]*>/gi, '\n- ')
    .replace(/<h([1-6])\b[^>]*>([\s\S]*?)<\/h\1>/gi, (m, l, t) => '\nH' + l + ': ' + t.replace(/<[^>]+>/g, '').trim() + '\n')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(p|div|ul|ol|blockquote|section|figure)>/gi, '\n')
    .replace(/<[^>]+>/g, '');
  return decode(s).replace(/[ \t ]+/g, ' ').replace(/ *\n */g, '\n').replace(/\n{2,}/g, '\n').trim();
}
const one = s => text(s).replace(/\n/g, ' ');

/* ---------- lookups ---------- */
const posts = B.posts;
const byId = {}; posts.forEach(p => { byId[p.id] = p; });
const media = {}; (B.media || []).forEach(m => { media[m.id] = m; });
const menus = {}; (B.menus || []).forEach(m => { menus[m.id] = m; });
const bricks = B.bricks || { templates: [], class_names: {}, components: [] };
const templates = bricks.templates.filter(t => t.post_type === 'bricks_template');
const tplById = {}; bricks.templates.forEach(t => { tplById[t.id] = t; });
const dyn = (B.dynamic && B.dynamic.values) || {};
const frontId = String((B.dynamic && B.dynamic.front_page) || B.site.page_on_front || '');

/* ---------- Bricks template conditions -> which header / content / footer applies ---------- */
function condScore(c, p, isError) {
  switch (c.main) {
    case 'any': return 2;
    case 'frontpage': return p && p.is_front ? 9 : 0;
    case 'postType': return p && [].concat(c.postType || []).includes(p.type) ? 7 : 0;
    case 'ids': return p && [].concat(c.ids || []).map(Number).includes(p.id) ? 10 : 0;
    case 'error': return isError ? 8 : 0;
    default: return 0;   // archives, terms, search: not a singular URL
  }
}
function pick(type, p, isError) {
  let best = null, bestScore = 0;
  templates.filter(t => t.type === type).forEach(t => {
    const conds = t.conditions || [];
    if (conds.some(c => c.exclude && condScore(c, p, isError) > 0)) return;
    const sc = Math.max.apply(null, [0].concat(conds.filter(c => !c.exclude).map(c => condScore(c, p, isError))));
    if (sc > bestScore) { best = t; bestScore = sc; }
  });
  if (!best && type === 'error') best = templates.find(t => t.type === 'error') || null;
  return best;
}

/* ---------- reducer context ---------- */
function makeCtx(postId) {
  const stack = [String(postId || frontId)];
  const ctx = {
    classNames: bricks.class_names || {},
    flag: flag,
    text: text,
    rel: rel,
    media: id => media[id] || null,
    menu: id => menus[id] || null,
    template: id => tplById[id] || null,
    posts: type => posts.filter(p => p.type === type),
    dyn: s => String(s).replace(/\{[a-z][a-zA-Z0-9_:\-|@ ]*\}/g, tag => {
      const cur = dyn[stack[stack.length - 1]] || {}, fp = dyn[frontId] || {};
      if (cur[tag] !== undefined) return cur[tag];
      if (fp[tag] !== undefined) return fp[tag];
      flag('Unresolved dynamic tag ' + tag + ' (harvest could not render it)');
      return tag;
    }),
    link: l => {
      if (!l || typeof l !== 'object') return '';
      if (l.type === 'internal' && l.postId) return byId[l.postId] ? rel(byId[l.postId].url) : 'post #' + l.postId + ' (not public)';
      if (l.useDynamicData) return rel(one(ctx.dyn(l.useDynamicData)));
      if (l.url) return rel(one(ctx.dyn(l.url))) + (l.newTab ? ' (new tab)' : '');
      return l.type || '';
    },
    withPost: (id, fn) => { stack.push(String(id)); try { fn(); } finally { stack.pop(); } },
    featured: () => { const p = byId[stack[stack.length - 1]]; return p && p.featured ? media[p.featured] || null : null; },
    postContent: () => { const p = byId[stack[stack.length - 1]]; return p ? text(p.post_content) : ''; },
  };
  return ctx;
}

function reduceTree(elements, postId) {
  return reducers.bricks(elements, makeCtx(postId)).join('\n');
}

/* ---------- SEO, from plugin meta (+ rendered head when raw/html exists) ---------- */
function seoFromMeta(p) {
  const m = p.seo || {}, plug = B.seo.plugin;
  const pick1 = keys => { for (const k of keys) if (m[k] !== undefined && m[k] !== '' && m[k] !== null) return m[k]; return ''; };
  const sets = {
    seopress: { title: ['_seopress_titles_title'], description: ['_seopress_titles_desc'], canonical: ['_seopress_robots_canonical'], noindex: ['_seopress_robots_index'], og_title: ['_seopress_social_fb_title'], og_description: ['_seopress_social_fb_desc'], og_image: ['_seopress_social_fb_img'] },
    rankmath: { title: ['rank_math_title'], description: ['rank_math_description'], canonical: ['rank_math_canonical_url'], noindex: ['rank_math_robots'], og_title: ['rank_math_facebook_title'], og_description: ['rank_math_facebook_description'], og_image: ['rank_math_facebook_image'] },
    yoast: { title: ['_yoast_wpseo_title'], description: ['_yoast_wpseo_metadesc'], canonical: ['_yoast_wpseo_canonical'], noindex: ['_yoast_wpseo_meta-robots-noindex'], og_title: ['_yoast_wpseo_opengraph-title'], og_description: ['_yoast_wpseo_opengraph-description'], og_image: ['_yoast_wpseo_opengraph-image'] },
  };
  const r = { source: plug || 'none' };
  const set = sets[plug];
  if (set) Object.keys(set).forEach(k => { r[k] = pick1(set[k]); });
  if (r.noindex && typeof r.noindex === 'object') r.noindex = [].concat(r.noindex).includes('noindex') ? 'noindex' : '';
  // Global templates fill what the post leaves empty.
  const o = B.seo.options || {};
  if (plug === 'seopress' && o.seopress_titles) {
    const t = ((o.seopress_titles.seopress_titles_single_titles || {})[p.type]) || {};
    if (!r.title && t.title) r.title = t.title + '  (template)';
    if (!r.description && t.description) r.description = t.description + '  (template)';
  }
  if (plug === 'rankmath' && o.rankmath_titles) {
    if (!r.title && o.rankmath_titles['pt_' + p.type + '_title']) r.title = o.rankmath_titles['pt_' + p.type + '_title'] + '  (template)';
    if (!r.description && o.rankmath_titles['pt_' + p.type + '_description']) r.description = o.rankmath_titles['pt_' + p.type + '_description'] + '  (template)';
  }
  if (plug === 'yoast' && o.yoast_titles) {
    if (!r.title && o.yoast_titles['title-' + p.type]) r.title = o.yoast_titles['title-' + p.type] + '  (template)';
    if (!r.description && o.yoast_titles['metadesc-' + p.type]) r.description = o.yoast_titles['metadesc-' + p.type] + '  (template)';
  }
  // Leftovers from a plugin switch: say so, since parity should match what renders, not what's stored.
  const owner = k => /^_seopress_/.test(k) ? 'seopress' : /^rank_math_/.test(k) ? 'rankmath' : /^_yoast_wpseo_/.test(k) ? 'yoast' : /^_aioseo_/.test(k) ? 'aioseo' : null;
  const others = Object.keys(m).map(owner).filter(o => o && o !== plug).filter((v, i, a) => a.indexOf(v) === i);
  if (others.length) r.leftover_meta_from = others.join(', ');
  return r;
}
function seoFromHtml(key) {
  const f = path.join(dir, 'raw/html', key + '.html');
  if (!fs.existsSync(f)) return null;
  const h = fs.readFileSync(f, 'utf8');
  const head = (h.match(/<head[\s\S]*?<\/head>/i) || [h])[0];
  const meta = (attr, name) => { const m = head.match(new RegExp('<meta[^>]+' + attr + '=["\']' + name + '["\'][^>]*>', 'i')); return m ? decode((m[0].match(/content=["']([^"']*)["']/i) || [])[1] || '') : ''; };
  const types = []; (h.match(/<script[^>]+application\/ld\+json[^>]*>[\s\S]*?<\/script>/gi) || []).forEach(s => {
    (s.match(/"@type"\s*:\s*("[^"]+"|\[[^\]]*\])/g) || []).forEach(t => { (t.match(/"([A-Za-z]+)"/g) || []).slice(1).forEach(x => types.push(x.replace(/"/g, ''))); });
  });
  const hosts = new Set(); (h.match(/<(script|link|iframe|img|source)\b[^>]+(src|href)=["']https?:\/\/[^"'/]+/gi) || []).forEach(x => hosts.add(x.replace(/^.*\/\//, '')));
  return {
    title: decode(((head.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '').trim()),
    description: meta('name', 'description'), robots: meta('name', 'robots'),
    canonical: rel(((head.match(/<link[^>]+rel=["']canonical["'][^>]*>/i) || [''])[0].match(/href=["']([^"']+)/i) || [])[1] || ''),
    og_title: meta('property', 'og:title'), og_description: meta('property', 'og:description'), og_image: meta('property', 'og:image'),
    h1: (h.match(/<h1\b[\s\S]*?<\/h1>/gi) || []).map(one),
    schema_types: Array.from(new Set(types)), generator: meta('name', 'generator'), third_party_hosts: Array.from(hosts),
  };
}

/* ---------- per page ---------- */
const keyOf = p => p.type + '-' + (p.slug || p.id);
const usedTemplates = {};
const pageRows = [];
posts.forEach(p => {
  const header = pick('header', p), footer = pick('footer', p);
  let source, body;
  if (p.builder === 'bricks') { source = 'Bricks (own content)'; body = reduceTree(p.bricks, p.id); }
  else {
    const single = pick('content', p);
    if (single) { source = 'Bricks template "' + single.title + '"' + (p.builder !== 'empty' ? ' + ' + p.builder + ' post content' : ''); body = reduceTree(single.elements, p.id); }
    else if (reducers[p.builder]) { source = p.builder; body = reducers[p.builder](p, makeCtx(p.id)).join('\n'); }
    else if (p.builder === 'classic' || p.builder === 'gutenberg') { source = p.builder + ' (plain text extraction)'; body = text(p.post_content); }
    else if (p.builder === 'empty') { source = 'empty'; body = '(no content)'; flag('Empty page: ' + rel(p.url)); }
    else { source = p.builder + ' (NO REDUCER)'; body = ''; flag('No reducer for builder "' + p.builder + '": ' + rel(p.url) + ' — Gate 1 stop; writing it is part of this port'); }
  }
  [header, footer].forEach(t => { if (t) usedTemplates[t.id] = t; });
  const seo = seoFromMeta(p), rendered = seoFromHtml(keyOf(p));
  const featured = p.featured && media[p.featured] ? media[p.featured].rel : '';
  const acf = (B.acf && B.acf.posts[p.id]) || {};
  const lines = [
    '# ' + p.title, '',
    '- URL: `' + rel(p.url) + '`' + (p.is_front ? ' (front page)' : ''),
    '- Type: ' + p.type_label + ' (`' + p.type + '`), id ' + p.id + ', modified ' + p.modified.slice(0, 10),
    '- Content source: ' + source,
    '- Header: ' + (header ? header.title : 'none') + '; footer: ' + (footer ? footer.title : 'none') + ' (see ../templates/)',
    featured ? '- Featured image: ' + featured :null,
    p.excerpt ? '- Excerpt: ' + one(p.excerpt) :null,
    Object.keys(acf).length ? '- Fields: ' + Object.keys(acf).map(k => acf[k].label + ' = ' + (typeof acf[k].value === 'object' ? JSON.stringify(acf[k].value) : one(acf[k].value))).join('; ') :null,
    '', '## SEO', '',
    '- Title: ' + (rendered ? rendered.title + ' (rendered)' : (seo.title || '(plugin default)')),
    '- Description: ' + (rendered ? rendered.description + ' (rendered)' : (seo.description || '(none)')),
    rendered ? '- Canonical: ' + rendered.canonical + '; robots: ' + (rendered.robots || 'default') + '; schema: ' + rendered.schema_types.join(', ') : (seo.canonical ? '- Canonical: ' + seo.canonical : null),
    rendered ? '- H1: ' + (rendered.h1.join(' | ') || '(none)') :null,
    seo.noindex ? '- Robots meta: ' + seo.noindex :null,
    seo.og_title || seo.og_description || seo.og_image ? '- Open Graph: ' + [seo.og_title, seo.og_description, seo.og_image ? rel(seo.og_image) : ''].filter(Boolean).join(' / ') :null,
    seo.leftover_meta_from ? '- Leftover SEO meta from: ' + seo.leftover_meta_from + ' (inactive; not what renders)' :null,
    '', '## Content', body,
  ].filter(x => x !== null);
  fs.writeFileSync(path.join(OUT, 'pages', keyOf(p) + '.md'), lines.join('\n') + '\n');
  pageRows.push({ p: p, source: source, header: header, footer: footer, seo: seo, rendered: rendered });
});

// 404
const errTpl = pick('error', null, true);
if (errTpl) {
  const h = pick('header', null, true), f = pick('footer', null, true);
  [h, f].forEach(t => { if (t) usedTemplates[t.id] = t; });
  fs.writeFileSync(path.join(OUT, 'pages', '404.md'), ['# 404 page', '', '- Content source: Bricks template "' + errTpl.title + '"', '- Header: ' + (h ? h.title : 'none') + '; footer: ' + (f ? f.title : 'none'), '', '## Content', reduceTree(errTpl.elements, null)].join('\n') + '\n');
}

// Shared templates
Object.keys(usedTemplates).forEach(id => {
  const t = usedTemplates[id];
  const conds = (t.conditions || []).map(c => (c.exclude ? 'not ' : '') + c.main + (c.postType ? ' ' + [].concat(c.postType).join(',') : '') + (c.ids ? ' ' + [].concat(c.ids).join(',') : '')).join('; ');
  fs.writeFileSync(path.join(OUT, 'templates', t.type + '-' + t.slug + '.md'), ['# ' + t.type + ': ' + t.title, '', '- Applies to: ' + (conds || '(no conditions)'), '', reduceTree(t.elements, null)].join('\n') + '\n');
});
templates.filter(t => !usedTemplates[t.id] && !['content', 'error'].includes(t.type)).forEach(t => flag('Bricks ' + t.type + ' template "' + t.title + '" applies to no public URL (unused?)'));
templates.filter(t => t.type === 'content' && !posts.some(p => p.builder !== 'bricks' && pick('content', p) === t)).forEach(t => flag('Bricks content template "' + t.title + '" applies to no public URL'));

/* ---------- urls.json ---------- */
const urls = posts.map(p => ({ path: rel(p.url), kind: 'page', type: p.type, id: p.id, title: p.title, keep: true }));
const A = B.archives || {};
(A.terms || []).forEach(t => urls.push({ path: rel(t.url), kind: 'term', type: t.taxonomy, title: t.name, count: t.count, keep: false }));
(A.authors || []).forEach(a => urls.push({ path: rel(a.url), kind: 'author', title: a.slug, keep: false }));
(A.post_type_archives || []).forEach(a => urls.push({ path: rel(a.url), kind: 'archive', type: a.type, keep: false }));
if (errTpl) urls.push({ path: '(404)', kind: '404', keep: true });
fs.writeFileSync(path.join(OUT, 'urls.json'), JSON.stringify({ home: home, urls: urls, attachment_pages: A.attachment_pages, attachments_total: A.attachments_total, redirects: B.seo.redirects }, null, 1));

/* ---------- media.json ---------- */
fs.writeFileSync(path.join(OUT, 'media.json'), JSON.stringify((B.media || []).map(m => ({ id: m.id, file: m.rel, alt: m.alt, caption: m.caption, width: m.width, height: m.height, mime: m.mime, master: m.source_kind, used_by: m.referenced_by, missing: m.missing || undefined })), null, 1));
(B.media || []).filter(m => m.missing).forEach(m => flag('Referenced media #' + m.id + ' is not an attachment (deleted?) — used by ' + m.referenced_by.join(', ')));
const noAlt = (B.media || []).filter(m => !m.missing && !m.alt && /^image\//.test(m.mime || '') && m.referenced_by.some(r => !/site icon/.test(r)));
if (noAlt.length) flag('Media-library alt text empty for ' + noAlt.length + ' image(s): ' + noAlt.map(m => m.rel.split('/').pop()).join(', ') + ' (the element may set its own alt; see page files)');

/* ---------- inventory.md ---------- */
const S = B.site, C = B.candidate;
const inv = [];
const h = s => inv.push('', '## ' + s, '');
inv.push('# Harvest inventory: ' + S.name, '', '_Harvested ' + B.harvested_at.slice(0, 16).replace('T', ' ') + ' UTC from ' + S.home + '. Generated by reduce.js; feeds MIGRATION.md._');

h('Site');
inv.push('- Name: ' + S.name + (S.tagline ? ' — ' + S.tagline : ''), '- WordPress ' + S.wp_version + ', PHP ' + S.php + ', theme ' + S.theme.name + ' ' + S.theme.version + (bricks.version ? ', Bricks ' + bricks.version : ''),
  '- Language ' + S.language + ', timezone ' + S.timezone + ', permalinks `' + S.permalink_structure + '`', '- Front page: ' + (S.show_on_front === 'page' ? (byId[S.page_on_front] ? byId[S.page_on_front].title : '#' + S.page_on_front) : 'latest posts'),
  '- Search engines: ' + (S.blog_public ? 'allowed' : 'discouraged'));
if (!S.blog_public) flag('blog_public is 0 (search engines discouraged): rendered robots/SEO may not match production');
if (S.multisite) flag('Multisite install: harvest covered the current site only');

h('Candidate check');
const pub = C.published_by_type || {};
const nonPage = Object.keys(pub).filter(t => !['page', 'attachment', 'bricks_template'].includes(t) && pub[t] > 0);
inv.push('- Published: ' + Object.keys(pub).filter(t => pub[t]).map(t => t + ' ' + pub[t]).join(', '));
inv.push('- Blog posts: ' + (pub.post || 0) + '; WooCommerce: ' + (C.woocommerce ? 'YES' : 'no') + '; EDD: ' + (C.edd ? 'YES' : 'no') + '; membership/LMS plugins: ' + (C.membership_plugins.join(', ') || 'none'));
inv.push('- Users: ' + Object.keys(C.users_by_role).filter(r => C.users_by_role[r] > 0).map(r => r + ' ' + C.users_by_role[r]).join(', ') + '; approved comments: ' + C.comments_approved);
const pages = posts.length;
let verdict = 'candidate';
if (pub.post > 0) { verdict = 'NOT a candidate (blog posts)'; flag('Site has ' + pub.post + ' published blog posts: out of scope, stop at Gate 1'); }
if (C.woocommerce || C.edd) { verdict = 'NOT a candidate (ecommerce)'; flag('Ecommerce active: out of scope, stop at Gate 1'); }
if (C.membership_plugins.length) { verdict = 'NOT a candidate (members/LMS)'; flag('Membership/LMS plugin active: out of scope, stop at Gate 1'); }
const subs = Object.keys(C.users_by_role).filter(r => !['administrator', 'editor', 'author', 'none'].includes(r) && C.users_by_role[r] > 0);
if (subs.length) flag('Non-staff user roles present (' + subs.join(', ') + '): check for user-facing logins');
if (pages > 15) flag(pages + ' public URLs: over the ~15-page scope');
nonPage.filter(t => t !== 'post').forEach(t => flag('Custom post type "' + t + '" (' + pub[t] + ' published): a list of items; decide data file vs. one file per item'));
inv.push('- **Verdict: ' + verdict + '** (' + pages + ' public URLs)');

h('Pages');
inv.push('| URL | Title | Type | Content source | Header / footer | Modified |', '| --- | --- | --- | --- | --- | --- |');
pageRows.forEach(r => inv.push('| `' + rel(r.p.url) + '` | ' + r.p.title + ' | ' + r.p.type + ' | ' + r.source + ' | ' + (r.header ? r.header.title : '–') + ' / ' + (r.footer ? r.footer.title : '–') + ' | ' + r.p.modified.slice(0, 10) + ' |'));
if (errTpl) inv.push('| (404) | 404 | – | Bricks template "' + errTpl.title + '" | | |');

h('SEO (' + (B.seo.plugin || 'no SEO plugin') + ')');
inv.push('| URL | Title | Description | Notes |', '| --- | --- | --- | --- |');
pageRows.forEach(r => {
  const t = r.rendered ? r.rendered.title : r.seo.title || '(default)';
  const d = r.rendered ? r.rendered.description : r.seo.description || '(none)';
  const notes = [r.rendered ? 'rendered' : 'from meta', r.seo.noindex ? 'noindex' : '', r.seo.canonical ? 'custom canonical' : '', r.seo.leftover_meta_from ? 'leftover ' + r.seo.leftover_meta_from + ' meta' : ''].filter(Boolean).join('; ');
  inv.push('| `' + rel(r.p.url) + '` | ' + t.replace(/\|/g, '\\|') + ' | ' + String(d).replace(/\|/g, '\\|') + ' | ' + notes + ' |');
});
if (!pageRows.some(r => r.rendered)) inv.push('', '_Rendered values not fetched (no --base-url): titles/descriptions are stored meta; "(template)" values carry unresolved plugin variables._');
if (pageRows.some(r => r.seo.leftover_meta_from)) flag('Leftover SEO meta from an inactive plugin: parity must use rendered values (run harvest with --base-url)');

h('Menus');
(B.menus || []).forEach(m => {
  inv.push('- **' + m.name + '**' + (m.locations.length ? ' (' + m.locations.join(', ') + ')' : ''));
  m.items.forEach(i => inv.push('  ' + (i.parent ? '  ' : '') + '- ' + i.title + ' → ' + rel(i.url) + (i.target ? ' (new tab)' : '')));
});
if (!(B.menus || []).length) inv.push('(none)');

h('Forms');
if ((B.forms || []).length) B.forms.forEach(f => inv.push('- ' + f.plugin + ' #' + f.id + ' "' + f.title + '"' + (f.to ? ' → ' + f.to : '') + ': ' + f.fields.map(x => x.label + ' (' + x.type + ')').join(', ')));
else inv.push('No form-plugin forms. (Bricks form elements, if any, show up in the page files as [form].)');

h('Integrations and injected scripts');
const sc = B.scripts || {};
if (!Object.keys(sc).length) inv.push('(none found)');
Object.keys(sc).forEach(k => {
  const v = sc[k];
  if (Array.isArray(v)) inv.push('- ' + k + ': ' + v.map(x => x.name || x.title).join(', '));
  else if (typeof v === 'object') inv.push('- ' + k + ': ' + Object.keys(v).map(x => x + '=' + (typeof v[x] === 'string' && v[x].length > 60 ? v[x].length + ' chars' : JSON.stringify(v[x]))).join(', '));
  else inv.push('- ' + k + ': ' + String(v).length + ' chars');
  if (k !== 'seopress_analytics' && k !== 'site_kit_ga4') flag('Injected code in ' + k + ': review by hand and carry over what is still wanted');
});
const hosts = new Set(); pageRows.forEach(r => { if (r.rendered) r.rendered.third_party_hosts.forEach(x => hosts.add(x)); });
if (hosts.size) inv.push('- Third-party hosts in rendered pages: ' + Array.from(hosts).join(', '));
if (Array.from(hosts).some(x => /typekit|use\.fontawesome|fonts\.adobe/.test(x))) flag('Licensed font/icon service in use (Adobe/Font Awesome kit): do not download; check licensing');

h('Plugins');
const act = S.plugins.filter(p => p.active);
inv.push('- Active (' + act.length + '): ' + act.map(p => p.name + ' ' + p.version).join(', '));
const custom = act.filter(p => !p.has_readme);
if (custom.length) { inv.push('- Probably custom or premium (no readme.txt): ' + custom.map(p => p.name).join(', ')); custom.forEach(p => flag('Plugin "' + p.name + '" has no readme.txt: if custom, read what it adds (CPTs, shortcodes, dynamic tags, schema, head output)')); }
if (S.mu_plugins.length) inv.push('- Must-use: ' + S.mu_plugins.join(', '));

h('Fonts');
inv.push('- On disk: ' + ((B.fonts_on_disk || []).join(', ') || 'none'));
if (bricks.custom_fonts && bricks.custom_fonts.length) inv.push('- Bricks custom fonts: ' + bricks.custom_fonts.map(f => f.name).join(', '));
inv.push('- Bricks Google Fonts: ' + (bricks.google_fonts_disabled ? 'disabled' : 'enabled (check rendered pages for fonts.googleapis.com)'));

h('Media (' + (B.media || []).length + ' referenced)');
(B.media || []).forEach(m => inv.push('- ' + (m.missing ? '#' + m.id + ' MISSING' : m.rel + ' (' + m.width + 'x' + m.height + ', master: ' + m.source_kind + ')' + (m.alt ? ' alt="' + m.alt + '"' : ' no alt')) + ' — ' + m.referenced_by.join(', ')));

h('Other public URLs (redirect candidates)');
inv.push('- Taxonomy archives with content: ' + ((A.terms || []).map(t => rel(t.url)).join(', ') || 'none'));
inv.push('- Author archives: ' + ((A.authors || []).map(a => rel(a.url)).join(', ') || 'none'));
inv.push('- Post type archives: ' + ((A.post_type_archives || []).map(a => rel(a.url)).join(', ') || 'none'));
inv.push('- Attachment pages: ' + (A.attachment_pages ? 'enabled' : 'disabled') + ' (' + A.attachments_total + ' attachments in the library)');
const red = B.seo.redirects || {};
Object.keys(red).forEach(k => inv.push('- Existing redirects (' + k + '): ' + red[k].length));
if (!Object.keys(red).length) inv.push('- Existing redirects: none');
if (Object.keys(red).length) flag('Existing redirects in ' + Object.keys(red).join(', ') + ': carry them into the redirect plan');

h('ACF');
if (B.acf) {
  inv.push('- Field groups: ' + B.acf.groups.map(g => g.title + ' (' + (g.fields.filter(f => f.name).map(f => f.name).join(', ') || 'layout only') + ')').join('; '));
  const opt = B.acf.options || {};
  if (Object.keys(opt).length) inv.push('- Options (' + B.acf.options_pages.join(', ') + '): ' + Object.keys(opt).map(k => opt[k].label + ' = ' + (typeof opt[k].value === 'object' ? JSON.stringify(opt[k].value) : one(opt[k].value))).join('; '));
} else inv.push('(ACF not active)');

inv.splice(3, 0, '', '## FLAGS (' + flags.length + ')', '', flags.length ? flags.map(f => '- ' + f).join('\n') : '(none)');
fs.writeFileSync(path.join(OUT, 'inventory.md'), inv.join('\n') + '\n');

console.log('reduced: ' + posts.length + ' pages, ' + Object.keys(usedTemplates).length + ' shared templates, ' + (B.media || []).length + ' media, ' + flags.length + ' flags -> ' + OUT);
