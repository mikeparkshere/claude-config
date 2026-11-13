'use strict';
/**
 * Bricks reducer: one element tree -> readable lines. Section order, headings with levels, body copy, buttons and
 * link targets, images with alt text, and a one-line layout note per section. Nothing else: no class names, no
 * markup, no styling values. (Class names are read only to turn ACSS-style hints like grid--3 into words.)
 *
 * reduceBricks(elements, ctx) -> string[]
 * ctx (from reduce.js): text(html) dyn(str) postId link(linkObj) media(id) menu(id) posts(type) withPost(id)
 *                       template(id) classNames postContent()
 */

const CONTAINERS = new Set(['section', 'container', 'block', 'div']);
const SKIP = new Set(['divider', 'toggle', 'icon', 'pie-chart', 'progress-bar']);   // no content of their own

function reduceBricks(elements, ctx, opts) {
  opts = opts || {};
  const els = (elements || []).filter(e => e && e.id);
  const byId = {};
  els.forEach(e => { byId[e.id] = e; });
  const isRoot = e => !e.parent || e.parent === 0 || e.parent === '0' || !byId[e.parent];
  const kids = e => (e.children || []).map(id => byId[id]).filter(Boolean);
  const out = [];
  const pad = d => '  '.repeat(d);

  const s = e => (e.settings && !Array.isArray(e.settings) ? e.settings : {});
  const classes = e => (s(e)._cssGlobalClasses || []).map(id => ctx.classNames[id]).filter(Boolean);

  function layoutNote(e) {
    const bits = [];
    const inner = kids(e).length === 1 && CONTAINERS.has(kids(e)[0].name) ? kids(e)[0] : e;
    const st = s(inner), outer = s(e);
    const n = kids(inner).length;
    if (st._display === 'grid' || st._gridTemplateColumns) bits.push('grid' + (st._gridTemplateColumns ? ' ' + String(st._gridTemplateColumns).slice(0, 40) : ''));
    else if (st._direction === 'row') bits.push('row');
    const hints = classes(e).concat(classes(inner)).map(c => {
      let m = c.match(/^grid--(?:[a-z-]*-)?(\d+)$/); if (m) return m[1] + '-col grid';
      m = c.match(/^grid--(\d+-\d+)$/); if (m) return m[1] + ' grid';
      m = c.match(/^bg--([a-z-]+)$/); if (m) return m[1] + ' background';
      m = c.match(/^(?:section|pad-section)--([a-z]+)$/); if (m) return 'section padding ' + m[1];
      return null;
    }).filter(Boolean);
    bits.push.apply(bits, Array.from(new Set(hints)));
    const bg = outer._background || {};
    if (bg.image) bits.push('background image ' + imageLabel(bg.image));
    if (bg.videoUrl) bits.push('background video');
    if (bg.color) bits.push('background color');
    if (outer._gradient) bits.push('gradient overlay');
    bits.push(n + ' item' + (n === 1 ? '' : 's'));
    return bits.join('; ');
  }

  function imageLabel(img) {
    if (!img) return '(none)';
    if (img.useDynamicData) {
      if (/featured_image/.test(img.useDynamicData)) { const f = ctx.featured(); return (f ? f.rel + ' ' + f.width + 'x' + f.height : '(no featured image)') + ' [featured image]'; }
      const v = ctx.dyn(img.useDynamicData), src = (v.match(/src=["']([^"']+)/) || [])[1] || v;
      return String(src).split('/').pop().split('?')[0] + ' [' + img.useDynamicData + ']';
    }
    const m = img.id ? ctx.media(img.id) : null;
    const file = m ? m.rel : (img.filename || String(img.url || '').split('/').pop());
    return file + (m && m.width ? ' ' + m.width + 'x' + m.height : '') + (img.id ? ' #' + img.id : '');
  }

  function textOf(v) { return ctx.text(ctx.dyn(String(v == null ? '' : v))); }

  function linkStr(l) { const t = ctx.link(l); return t ? ' → ' + t : ''; }

  function headingLevel(e) {
    const t = String(s(e).tag === 'custom' ? s(e).customTag : s(e).tag || '');
    const m = t.match(/^h([1-6])$/);
    return m ? +m[1] : (e.name === 'heading' ? 3 : 0);
  }

  function line(d, str) { if (str && String(str).trim()) out.push(pad(d) + str); }

  function generic(e, d) {
    // Unknown element (plugin or new Bricks type): print text-ish settings and any link, so nothing is silently lost.
    const st = s(e), found = [];
    Object.keys(st).forEach(k => {
      if (/^(text|title|content|label|description|heading|subtitle|subheading|name|caption|buttonText)$/i.test(k) && typeof st[k] === 'string') found.push(textOf(st[k]));
    });
    line(d, '[' + e.name + ']' + (found.length ? ' ' + found.join(' | ') : '') + (st.link ? linkStr(st.link) : ''));
    if (Array.isArray(st.items)) st.items.forEach(it => line(d + 1, '- ' + Object.keys(it).filter(k => typeof it[k] === 'string' && !/^(id|icon)$/.test(k)).map(k => textOf(it[k])).filter(Boolean).join(' — ')));
  }

  function loopPosts(st) {
    const q = st.query || {};
    let types = q.post_type || (q.objectType === 'post' || !q.objectType ? ['post'] : []);
    if (!Array.isArray(types)) types = [types];
    if (q.objectType && q.objectType !== 'post') return { label: q.objectType + ' query (not expanded)', posts: [] };
    const posts = [].concat.apply([], types.map(t => ctx.posts(t)));
    return { label: types.join(', '), posts: posts };
  }

  function render(e, d) {
    const st = s(e);
    if (st._hidden && st._hidden._cssHidden) return;

    // Query loops: render the children once per matching post, with that post as dynamic-data context.
    if (st.hasLoop) {
      const lp = loopPosts(st);
      line(d, '[loop over ' + lp.label + ': ' + lp.posts.length + ' item' + (lp.posts.length === 1 ? '' : 's') + ']');
      lp.posts.forEach(p => ctx.withPost(p.id, () => {
        line(d + 1, '• ' + p.title);
        const solo = Object.assign({}, e, { settings: Object.assign({}, st, { hasLoop: false }) });
        renderInner(solo, d + 2);
      }));
      return;
    }
    renderInner(e, d);
  }

  function renderInner(e, d) {
    const st = s(e);
    const n = e.name;
    if (CONTAINERS.has(n)) {
      if (st.link) line(d, '(whole block links' + linkStr(st.link) + ')');
      kids(e).forEach(k => render(k, d));
      return;
    }
    if (SKIP.has(n)) return;
    const lvl = headingLevel(e);
    switch (n) {
      case 'heading':
      case 'text-basic':
      case 'text':
      case 'rich-text': {
        const t = textOf(st.text);
        if (lvl) line(d, 'H' + lvl + ': ' + t.replace(/\n+/g, ' ') + (st.link ? linkStr(st.link) : ''));
        else t.split('\n').forEach(x => line(d, x));
        break;
      }
      case 'text-link': line(d, '[link] ' + textOf(st.text) + linkStr(st.link)); break;
      case 'button': line(d, '[button] ' + textOf(st.text) + linkStr(st.link)); break;
      case 'image': {
        const fm = st.image && /featured_image/.test(st.image.useDynamicData || '') ? ctx.featured() : null;
        const alt = st.altText ? textOf(st.altText) : (st.image && st.image.id && ctx.media(st.image.id) ? ctx.media(st.image.id).alt : fm ? fm.alt : '');
        line(d, '[image] ' + imageLabel(st.image) + (alt ? ' alt="' + alt + '"' : ' (no alt)') + (st.link ? linkStr(st.link) : ''));
        break;
      }
      case 'image-gallery':
        line(d, '[gallery] ' + ((st.items && st.items.images) || []).map(imageLabel).join(', '));
        break;
      case 'svg': line(d, '[svg] ' + (st.file ? (st.file.filename || String(st.file.url || '').split('/').pop()) : '') + (st.link ? linkStr(st.link) : '')); break;
      case 'logo': line(d, '[logo] ' + (st.logoText ? textOf(st.logoText) : 'site logo') + (st.logoUrl ? ' → ' + st.logoUrl : ' → /')); break;
      case 'post-title': line(d, 'H' + (lvl || 1) + ': ' + ctx.dyn('{post_title}')); break;
      case 'post-excerpt': line(d, textOf('{post_excerpt}')); break;
      case 'post-content': {
        const c = ctx.postContent();
        line(d, '[post content]');
        c.split('\n').forEach(x => line(d + 1, x));
        break;
      }
      case 'list':
        (st.items || []).forEach(it => line(d, '- ' + [textOf(it.title), it.meta ? textOf(it.meta) : ''].filter(Boolean).join(' — ') + (it.link ? linkStr(it.link) : '')));
        break;
      case 'testimonials':
        (st.items || []).forEach(it => {
          const who = [textOf(it.name), textOf(it.title)].map(x => x.replace(/^[-—–\s*]+|[*]+/g, '').trim()).filter(Boolean).join(', ');
          line(d, '> ' + textOf(it.content) + (who ? ' — ' + who : '') + (it.image ? ' [photo ' + imageLabel(it.image) + ']' : ''));
        });
        break;
      case 'map': {
        const a = (st.addresses || []).map(x => x.address || [x.latitude, x.longitude].filter(Boolean).join(',')).filter(Boolean);
        const where = st.address ? textOf(st.address) : a.join('; ');
        line(d, '[map] ' + where + (st.zoom ? ' zoom ' + st.zoom : '') + (st.address && a.length ? ' (also has coordinates ' + a.join('; ') + ')' : ''));
        if (!st.address && /52\.516\d*,\s*13\.37\d*/.test(a.join(';')) && ctx.flag) ctx.flag('A map element still carries the Bricks demo coordinates (Berlin): check which location the live map shows');
        break;
      }
      case 'social-icons':
        line(d, '[social] ' + (st.icons || []).map(i => (i.label || (i.icon && i.icon.icon) || 'icon').toString().replace(/^.*fa-/, '') + linkStr(i.link)).join(', '));
        break;
      case 'nav-menu':
      case 'nav-nested': {
        const m = ctx.menu(st.menu);
        line(d, '[nav' + (m ? ': ' + m.name : '') + '] ' + (m ? m.items.filter(i => !i.parent).map(i => i.title + ' → ' + ctx.rel(i.url)).join(', ') : ''));
        if (n === 'nav-nested') kids(e).forEach(k => render(k, d + 1));
        break;
      }
      case 'carousel':
      case 'slider': {
        if (st.type === 'posts' || st.query) {
          const lp = loopPosts(st);
          line(d, '[' + n + ' of ' + lp.label + ': ' + lp.posts.length + ']');
          lp.posts.forEach(p => ctx.withPost(p.id, () => line(d + 1, '• ' + (st.fields || []).map(f => textOf(f.dynamicData)).filter(Boolean).join(' | '))));
        } else if (st.items && st.items.images) {
          line(d, '[' + n + ' images] ' + st.items.images.map(imageLabel).join(', '));
        } else {
          line(d, '[' + n + ']');
          (st.items || []).forEach(it => line(d + 1, '- ' + [it.title, it.content, it.buttonText].filter(Boolean).map(textOf).join(' | ') + (it.link ? linkStr(it.link) : '')));
        }
        break;
      }
      case 'accordion':
      case 'tabs':
        (st.items || st.tabs || []).forEach(it => { line(d, '[' + n + '] ' + textOf(it.title)); textOf(it.content).split('\n').forEach(x => line(d + 1, x)); });
        break;
      case 'accordion-nested':
      case 'tabs-nested':
      case 'slider-nested':
      case 'offcanvas':
        line(d, '[' + n + ']');
        kids(e).forEach(k => render(k, d + 1));
        break;
      case 'video': line(d, '[video] ' + (st.youTubeId ? 'YouTube ' + st.youTubeId : st.vimeoId ? 'Vimeo ' + st.vimeoId : st.fileUrl || st.videoType || '')); break;
      case 'form':
        line(d, '[form] fields: ' + (st.fields || []).map(f => (f.label || f.placeholder || f.type) + ' (' + f.type + (f.required ? ', required' : '') + ')').join('; '));
        line(d + 1, 'actions: ' + (st.actions || []).join(', ') + (st.emailTo ? '; email to: ' + (st.emailTo === 'custom' ? st.emailToCustom : st.emailTo) : '') + (st.successMessage ? '; success: "' + textOf(st.successMessage) + '"' : ''));
        break;
      case 'code':
        line(d, '[code block: ' + [st.code ? 'HTML/PHP ' + String(st.code).length + ' chars' : '', st.cssCode ? 'CSS ' + String(st.cssCode).length + ' chars' : '', st.javascriptCode ? 'JS ' + String(st.javascriptCode).length + ' chars' : ''].filter(Boolean).join(', ') + (st.executeCode ? ', executes' : '') + '] (review by hand)');
        break;
      case 'shortcode': line(d, '[shortcode] ' + (st.shortcode || '') + ' (review by hand)'); break;
      case 'template': {
        const t = ctx.template(st.template);
        line(d, '[template: ' + (t ? t.title : '#' + st.template) + ']');
        if (t && !(opts.seen || []).includes(t.id)) reduceBricks(t.elements, ctx, { seen: (opts.seen || []).concat(t.id), nested: true }).forEach(x => line(d + 1, x));
        break;
      }
      default: generic(e, d);
    }
  }

  const roots = els.filter(isRoot);
  roots.forEach((r, i) => {
    if (opts.nested) { render(r, 0); return; }
    const st = s(r);
    const name = r.label || (st._cssId ? '#' + st._cssId : '') || r.name;
    out.push('');
    out.push('### ' + (i + 1) + '. ' + name);
    out.push('_layout: ' + layoutNote(r) + '_');
    if (st.hasLoop) render(r, 0); else kids(r).forEach(k => render(k, 0));
    if (!kids(r).length && !CONTAINERS.has(r.name)) render(r, 0);
  });
  return out;
}

module.exports = reduceBricks;
