'use strict';
/**
 * SEO-relevant facts from one rendered HTML page. Shared by reduce.js (old site) and parity-check.js (old vs new).
 * Regex-based on purpose: no dependencies, and these are well-formed pages from known generators.
 */
const ENT = { amp: '&', lt: '<', gt: '>', quot: '"', apos: "'", nbsp: ' ', ndash: '–', mdash: '—', hellip: '…', rsquo: '’', lsquo: '‘', rdquo: '”', ldquo: '“', copy: '©', reg: '®', trade: '™', middot: '·', bull: '•', times: '×' };
function decode(s) {
  return String(s || '').replace(/&(#x?[0-9a-f]+|[a-z]+);/gi, (m, e) => {
    if (e[0] === '#') return String.fromCodePoint(e[1].toLowerCase() === 'x' ? parseInt(e.slice(2), 16) : parseInt(e.slice(1), 10));
    return ENT[e.toLowerCase()] !== undefined ? ENT[e.toLowerCase()] : m;
  });
}
const plain = s => decode(String(s || '').replace(/<[^>]+>/g, ' ')).replace(/\s+/g, ' ').trim();

/**
 * @param {string} h     the page HTML
 * @param {string} home  the page's own origin (https://host), used to make URLs relative and to skip "own" hosts
 */
function extractHead(h, home) {
  home = String(home || '').replace(/\/$/, '');
  const rel = u => (u && home && u.indexOf(home) === 0 ? u.slice(home.length) || '/' : u || '');
  const head = (h.match(/<head[\s\S]*?<\/head>/i) || [h])[0];
  const attr = (tag, name) => decode((tag.match(new RegExp(name + '=["\']([^"\']*)["\']', 'i')) || [])[1] || '');
  const meta = (key, name) => {
    const m = head.match(new RegExp('<meta[^>]+' + key + '=["\']' + name + '["\'][^>]*>', 'i'));
    return m ? attr(m[0], 'content') : '';
  };
  const types = [];
  (h.match(/<script[^>]+application\/ld\+json[^>]*>[\s\S]*?<\/script>/gi) || []).forEach(s => {
    (s.match(/"@type"\s*:\s*("[^"]+"|\[[^\]]*\])/g) || []).forEach(t => {
      (t.replace(/^"@type"\s*:\s*/, '').match(/"([A-Za-z]+)"/g) || []).forEach(x => types.push(x.replace(/"/g, '')));
    });
  });
  const own = home.replace(/^https?:\/\//, '');
  const hosts = new Set();
  (h.match(/<(script|link|iframe|img|source)\b[^>]+(src|href)=["']https?:\/\/[^"'/]+/gi) || []).forEach(x => hosts.add(x.replace(/^.*\/\//, '')));
  const canonTag = (head.match(/<link[^>]+rel=["']canonical["'][^>]*>/i) || [''])[0];
  return {
    title: decode(((head.match(/<title[^>]*>([\s\S]*?)<\/title>/i) || [])[1] || '').trim()),
    description: meta('name', 'description'),
    robots: meta('name', 'robots'),
    canonical_abs: attr(canonTag, 'href'),
    canonical: rel(attr(canonTag, 'href')),
    og_title: meta('property', 'og:title'),
    og_description: meta('property', 'og:description'),
    og_image: meta('property', 'og:image'),
    og_url: meta('property', 'og:url'),
    twitter_card: meta('name', 'twitter:card'),
    lang: ((h.match(/<html[^>]+lang=["']([^"']+)/i) || [])[1] || ''),
    h1: (h.match(/<h1\b[\s\S]*?<\/h1>/gi) || []).map(x => plain(x.replace(/<\/?h1\b[^>]*>/gi, ''))),
    schema_types: Array.from(new Set(types)),
    generator: meta('name', 'generator'),
    third_party_hosts: Array.from(hosts).filter(x => x !== own && x !== 'www.' + own),
  };
}

module.exports = { extractHead, decode, plain };
