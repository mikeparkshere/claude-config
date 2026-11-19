'use strict';
/**
 * Elementor reducer: one element tree -> readable lines, in the same shape as the Bricks reducer. Section order,
 * headings with levels, body copy, buttons and link targets, images with alt text, and a one-line layout note per
 * top-level section. No class names, markup or styling values.
 *
 * reduceElementor(elementsOrPost, ctx, opts) -> string[]
 *   elementsOrPost: an Elementor tree (array of {id, elType, widgetType, settings, elements}) or a harvested post,
 *                   whose own tree is post.elementor.
 *   ctx (from reduce.js): text rel link media menu template posts withPost featured postContent post acf site form flag
 *
 * Dynamic tags live in settings.__dynamic__ as shortcode-like strings ([elementor-tag name="post-title" settings=…]).
 * Unlike Bricks, nothing renders them in WordPress at harvest time, so the common ones resolve here from the post,
 * its ACF fields and the site; anything else prints as {tag-name} and is flagged.
 *
 * Elementor keeps empty settings as [] (PHP's empty array), and leaves settings for options that are switched off
 * (a hidden title's text, a button's link with the button removed), so read what renders, not what is stored.
 */

const CONTAINERS = new Set(['section', 'column', 'container']);
const SKIP = new Set(['spacer', 'icon', 'menu-anchor', 'sidebar', 'progress', 'reading-progress', 'lottie', 'post-navigation', 'search-form', 'theme-builder-placeholder']);

function reduceElementor(input, ctx, opts) {
  opts = opts || {};
  const roots = Array.isArray(input) ? input : ((input && input.elementor) || []);
  const out = [];
  const pad = d => '  '.repeat(d);
  const line = (d, str) => { if (str && String(str).trim()) out.push(pad(d) + str); };
  const s = e => (e && e.settings && !Array.isArray(e.settings) ? e.settings : {});
  const kids = e => (e.elements || []).filter(Boolean);
  const textOf = v => ctx.text(String(v == null ? '' : v));
  const oneLine = v => textOf(v).replace(/\n+/g, ' ');

  /* ---------- dynamic tags ---------- */
  function parseTag(str) {
    const name = (String(str).match(/name="([^"]+)"/) || [])[1] || '';
    let set = {};
    const raw = (String(str).match(/settings="([^"]*)"/) || [])[1];
    if (raw) { try { set = JSON.parse(decodeURIComponent(raw)); } catch (x) { set = {}; } }
    return { name: name, set: set };
  }
  const acfName = set => String(set.key || '').split(':').pop();
  function featuredImg() { const f = ctx.featured(); return f ? { id: f.id } : null; }
  // Returns a string (text tags) or an image object / id list (media tags); null when the tag is unknown.
  function resolveTag(str) {
    const t = parseTag(str), n = t.name, p = ctx.post(), wrap = v => (v === '' || v == null ? (t.set.fallback || '') : (t.set.before || '') + v + (t.set.after || ''));
    switch (n) {
      case 'post-title': case 'page-title': return wrap(p ? p.title : '');
      case 'post-excerpt': return wrap(p ? p.excerpt : '');
      case 'post-url': case 'post-link': return p ? ctx.rel(p.url) : '';
      case 'post-date': return wrap(p ? String(p.date).slice(0, 10) : '');
      case 'site-title': return wrap(ctx.site.name);
      case 'site-tagline': return wrap(ctx.site.tagline);
      case 'site-url': return '/';
      case 'archive-title': return '(archive title)';
      case 'post-featured-image': return featuredImg() || (t.set.fallback && t.set.fallback.id ? { id: t.set.fallback.id } : null);
      case 'site-logo': return ctx.site.custom_logo ? { id: ctx.site.custom_logo } : null;
      case 'acf-text': case 'acf-textarea': case 'acf-number': case 'acf-url': case 'acf-color': {
        const f = ctx.acf(acfName(t.set));
        return wrap(f ? (Array.isArray(f.value) ? f.value.join(', ') : f.value) : '');
      }
      case 'acf-image': { const f = ctx.acf(acfName(t.set)); return f && f.value ? { id: f.value } : null; }
      case 'acf-gallery': { const f = ctx.acf(acfName(t.set)); return f && Array.isArray(f.value) ? f.value.map(id => ({ id: id })) : []; }
      default:
        if (ctx.flag) ctx.flag('Unresolved Elementor dynamic tag "' + n + '" (the reducer does not model it; check the live page)');
        return '{' + n + '}';
    }
  }
  // A setting's value with its dynamic tag (if any) resolved.
  function val(e, key) {
    const dy = s(e).__dynamic__;
    if (dy && dy[key]) return resolveTag(dy[key]);
    return s(e)[key];
  }

  /* ---------- labels ---------- */
  function imageLabel(img) {
    if (!img) return '(none)';
    if (typeof img !== 'object') img = { id: img };
    const m = img.id ? ctx.media(img.id) : null;
    const file = m ? m.rel : String(img.url || '').split('/').pop();
    return (file || '(empty)') + (m && m.width ? ' ' + m.width + 'x' + m.height : '') + (img.id ? ' #' + img.id : '');
  }
  function altOf(img, explicit) {
    if (explicit) return explicit;
    if (img && typeof img === 'object' && img.alt) return img.alt;
    const m = img && (img.id || typeof img !== 'object') ? ctx.media(img.id || img) : null;
    return m ? m.alt : '';
  }
  function linkStr(l, e, key) {
    if (e && key && s(e).__dynamic__ && s(e).__dynamic__[key]) { const v = resolveTag(s(e).__dynamic__[key]); return v ? ' → ' + ctx.rel(v) : ''; }
    if (!l || typeof l !== 'object' || !l.url) return '';
    return ' → ' + ctx.rel(l.url) + (l.is_external === 'on' ? ' (new tab)' : '') + (l.nofollow === 'on' ? ' (nofollow)' : '');
  }
  function hidden(e) {
    const st = s(e), h = ['desktop', 'tablet', 'mobile'].filter(dv => st['hide_' + dv]);
    return h.length ? ' (hidden on ' + h.join(', ') + ')' : '';
  }
  const level = (tag, dflt) => { const m = String(tag || dflt || '').match(/^h([1-6])$/); return m ? +m[1] : 0; };

  function layoutNote(e) {
    const st = s(e), bits = [];
    if (e.elType === 'section') {
      const cols = kids(e).length;
      bits.push(cols + '-column section');
      if (st.layout === 'full_width') bits.push('full width');
    } else {
      if (st.container_type === 'grid') bits.push('grid' + (st.grid_columns_grid && st.grid_columns_grid.size ? ' ' + st.grid_columns_grid.size + ' columns' : ''));
      else if (st.flex_direction === 'row' || st.flex_direction === 'row-reverse') bits.push('row');
      if (st.content_width === 'full') bits.push('full width');
    }
    if (st.min_height && st.min_height.size) bits.push('min-height ' + st.min_height.size + (st.min_height.unit || 'px'));
    const bgImg = val(e, 'background_image');
    if (st.background_background === 'video' && st.background_video_link) bits.push('background video ' + st.background_video_link);
    else if (bgImg && (bgImg.id || bgImg.url)) bits.push('background image ' + imageLabel(bgImg));
    else if (st.background_background === 'classic' && (st.background_color || (st.__globals__ && st.__globals__.background_color))) bits.push('background color');
    if (st.background_background === 'gradient') bits.push('gradient background');
    if (st.background_overlay_background) bits.push('overlay');
    if (st.html_tag && st.html_tag !== 'div') bits.push('<' + st.html_tag + '>');
    const n = kids(e).length;
    bits.push(n + ' item' + (n === 1 ? '' : 's'));
    return bits.join('; ') + hidden(e);
  }

  /* ---------- widgets ---------- */
  function generic(e, d) {
    const st = s(e), found = [];
    Object.keys(st).forEach(k => {
      if (/^(title|title_text|text|heading|description|description_text|content|editor|label|caption|button_text|name)$/.test(k) && typeof st[k] === 'string' && st[k].trim()) found.push(oneLine(st[k]));
    });
    line(d, '[' + e.widgetType + ']' + (found.length ? ' ' + found.join(' | ') : '') + linkStr(st.link) + hidden(e));
    Object.keys(st).filter(k => Array.isArray(st[k]) && st[k].length && typeof st[k][0] === 'object' && !/^(__|_)/.test(k)).forEach(k => {
      st[k].forEach(it => {
        const txt = Object.keys(it).filter(x => typeof it[x] === 'string' && /(title|text|content|name|label|description|heading)/.test(x) && it[x].trim()).map(x => oneLine(it[x]));
        if (txt.length) line(d + 1, '- ' + txt.join(' — ') + linkStr(it.link));
      });
    });
  }

  function postsList(e, d, type) {
    const list = ctx.posts(type), st = s(e);
    // Skin-prefixed in the posts widget (classic_/cards_/full_content_posts_per_page); Elementor's default is 6.
    const skin = st._skin || 'classic', per = +(st[skin + '_posts_per_page'] || st.posts_per_page || 6);
    line(d, '[loop over ' + type + ': ' + list.length + ' item' + (list.length === 1 ? '' : 's') + (per < list.length ? ', shows ' + per + ' per page' : '') + ']' + hidden(e));
    list.forEach(p => line(d + 1, '• ' + oneLine(p.title) + ' → ' + ctx.rel(p.url)));
  }

  function expandTemplate(id, d, label) {
    const t = ctx.template(id);
    line(d, '[' + label + ': ' + (t ? t.title : '#' + id) + ']');
    if (t && !(opts.seen || []).includes(t.id)) reduceElementor(t.elements, ctx, { seen: (opts.seen || []).concat(t.id), nested: true }).forEach(x => line(d + 1, x));
  }

  function render(e, d) {
    if (!e) return;
    if (CONTAINERS.has(e.elType)) {
      const st = s(e);
      if (st.link && st.link.url) line(d, '(whole block links' + linkStr(st.link) + ')');
      const h = hidden(e); if (h) line(d, '(container' + h + ')');
      kids(e).forEach(k => render(k, d));
      return;
    }
    if (e.elType !== 'widget') { kids(e).forEach(k => render(k, d)); return; }
    const w = e.widgetType, st = s(e);
    if (SKIP.has(w)) return;
    const hd = hidden(e);
    switch (w) {
      case 'heading': {
        const t = oneLine(val(e, 'title'));
        const lv = level(st.header_size, 'h2');
        line(d, (lv ? 'H' + lv + ': ' : '[' + (st.header_size || 'text') + '] ') + t + linkStr(st.link, e, 'link') + hd);
        break;
      }
      case 'theme-post-title': case 'theme-page-title': case 'theme-archive-title': {
        const t = oneLine(val(e, 'title') || (w === 'theme-archive-title' ? '(archive title)' : (ctx.post() || {}).title || ''));
        line(d, 'H' + (level(st.header_size, 'h1') || 1) + ': ' + t + ' [' + w.replace('theme-', '') + ']' + hd);
        break;
      }
      case 'theme-site-title': line(d, '[site title] ' + ctx.site.name + ' → /' + hd); break;
      case 'theme-site-logo': case 'site-logo': {
        const img = val(e, 'image') || (ctx.site.custom_logo ? { id: ctx.site.custom_logo } : null);
        line(d, '[logo] ' + imageLabel(img) + ' → ' + (st.link_to === 'custom' && st.link ? ctx.rel(st.link.url) : '/') + hd);
        break;
      }
      case 'text-editor': textOf(val(e, 'editor')).split('\n').forEach(x => line(d, x)); if (hd) line(d, '(text' + hd + ')'); break;
      case 'button': line(d, '[button] ' + oneLine(val(e, 'text') || 'Click here') + linkStr(st.link, e, 'link') + hd); break;
      case 'image': case 'theme-post-featured-image': {
        const img = val(e, 'image') || (w === 'theme-post-featured-image' ? featuredImg() : null);
        const alt = altOf(img);
        const link = st.link_to === 'custom' ? linkStr(st.link, e, 'link') : st.link_to === 'file' ? ' → (opens full image)' : '';
        const cap = st.caption_source === 'custom' && st.caption ? ' caption "' + oneLine(st.caption) + '"' : '';
        line(d, '[image' + (w !== 'image' ? ': featured' : '') + '] ' + imageLabel(img) + (alt ? ' alt="' + alt + '"' : ' (no alt)') + cap + link + hd);
        break;
      }
      case 'icon-box': case 'image-box': {
        const lv = level(st.title_size, 'h3');
        const pre = w === 'image-box' && st.image ? '[image] ' + imageLabel(st.image) + ' ' : '';
        line(d, pre + (lv ? 'H' + lv + ': ' : '') + oneLine(val(e, 'title_text')) + linkStr(st.link, e, 'link') + hd);
        if (st.description_text) textOf(val(e, 'description_text')).split('\n').forEach(x => line(d + 1, x));
        break;
      }
      case 'icon-list':
        (st.icon_list || []).forEach(it => line(d, '- ' + oneLine(it.text) + linkStr(it.link)));
        break;
      case 'gallery': {
        // Elementor Pro gallery: single (gallery[]) or multiple (galleries[] with their own image lists), or ACF via dynamic tag.
        if (st.gallery_type === 'multiple') {
          (st.galleries || []).forEach(g => line(d, '[gallery: ' + (g.gallery_title || 'untitled') + '] ' + (g.multiple_gallery || []).map(imageLabel).join(', ')));
        } else {
          const imgs = val(e, 'gallery') || [];
          const src = st.__dynamic__ && st.__dynamic__.gallery ? ' (from ' + parseTag(st.__dynamic__.gallery).name + ')' : '';
          line(d, '[gallery' + src + ': ' + imgs.length + ' image' + (imgs.length === 1 ? '' : 's') + ']' + hd);
          imgs.forEach(img => line(d + 1, '[image] ' + imageLabel(img) + (altOf(img) ? ' alt="' + altOf(img) + '"' : ' (no alt)')));
        }
        break;
      }
      case 'image-gallery': case 'image-carousel': {
        const imgs = val(e, w === 'image-gallery' ? 'wp_gallery' : 'carousel') || [];
        line(d, '[' + (w === 'image-gallery' ? 'gallery' : 'carousel') + ': ' + imgs.length + ' images]' + hd);
        imgs.forEach(img => line(d + 1, '[image] ' + imageLabel(img) + (altOf(img) ? ' alt="' + altOf(img) + '"' : ' (no alt)')));
        break;
      }
      case 'media-carousel':
        line(d, '[media carousel: ' + (st.slides || []).length + ' slides]' + hd);
        (st.slides || []).forEach(sl => line(d + 1, sl.type === 'video' && sl.video ? '[video] ' + sl.video.url : '[image] ' + imageLabel(sl.image) + (altOf(sl.image) ? ' alt="' + altOf(sl.image) + '"' : ' (no alt)')));
        break;
      case 'slides':
        line(d, '[slider: ' + (st.slides || []).length + ' slides]' + hd);
        (st.slides || []).forEach(sl => {
          line(d + 1, '• ' + [sl.heading, sl.description].filter(Boolean).map(oneLine).join(' | ') + (sl.button_text ? ' [button] ' + oneLine(sl.button_text) : '') + linkStr(sl.link));
          if (sl.background_image && (sl.background_image.id || sl.background_image.url)) line(d + 2, '[background] ' + imageLabel(sl.background_image));
        });
        break;
      case 'testimonial-carousel': case 'reviews':
        (st.slides || []).forEach(sl => line(d, '> ' + oneLine(sl.content) + (sl.name ? ' — ' + oneLine(sl.name) : '') + (sl.title ? ', ' + oneLine(sl.title) : '') + (sl.image && (sl.image.id || sl.image.url) ? ' [photo ' + imageLabel(sl.image) + ']' : '')));
        break;
      case 'testimonial':
        line(d, '> ' + oneLine(st.testimonial_content) + (st.testimonial_name ? ' — ' + oneLine(st.testimonial_name) : '') + (st.testimonial_job ? ', ' + oneLine(st.testimonial_job) : '') + hd);
        break;
      case 'video': {
        const t = st.video_type || 'youtube';
        const url = t === 'hosted' ? (st.insert_url === 'yes' ? st.external_url && st.external_url.url : st.hosted_url && st.hosted_url.url) : st[t + '_url'];
        line(d, '[video] ' + t + ' ' + (url || '(default sample video: check the live page)') + (st.show_image_overlay === 'yes' && st.image_overlay ? ' overlay ' + imageLabel(st.image_overlay) : '') + hd);
        if (!url && ctx.flag) ctx.flag('A video widget has no URL of its own (Elementor falls back to its sample video): check what the live page plays');
        break;
      }
      case 'ws-form': {
        const f = ctx.form('ws-form', st.form_id);
        line(d, '[form] WS Form #' + st.form_id + (f ? ' "' + f.title + '"' : '') + ' (fields and actions: inventory → Forms)' + hd);
        break;
      }
      case 'form':
        line(d, '[form] ' + (st.form_name || '') + ' fields: ' + (st.form_fields || []).map(f => (f.field_label || f.placeholder || f.field_type || 'text') + ' (' + (f.field_type || 'text') + (f.required === 'true' || f.required === 'yes' ? ', required' : '') + ')').join('; ') + hd);
        line(d + 1, 'actions: ' + [].concat(st.submit_actions || ['email']).join(', ') + (st.email_to ? '; email to: ' + st.email_to : '') + (st.success_message ? '; success: "' + oneLine(st.success_message) + '"' : ''));
        break;
      case 'shortcode': line(d, '[shortcode] ' + (st.shortcode || '') + ' (review by hand)' + hd); break;
      case 'html': line(d, '[code block: HTML ' + String(st.html || '').length + ' chars] (review by hand)' + hd); break;
      case 'google_maps': line(d, '[map] ' + (st.address || '') + (st.zoom && st.zoom.size ? ' zoom ' + st.zoom.size : '') + hd); break;
      case 'nav-menu': {
        const m = ctx.menu(st.menu);
        line(d, '[nav' + (m ? ': ' + m.name : st.menu ? ': ' + st.menu + ' (menu not found)' : '') + '] ' + (m ? m.items.filter(i => !i.parent).map(i => i.title + ' → ' + ctx.rel(i.url)).join(', ') : '') + hd);
        break;
      }
      case 'social-icons':
        line(d, '[social] ' + (st.social_icon_list || []).map(i => String((i.social_icon && i.social_icon.value) || i.social || 'icon').replace(/^.*fa-/, '') + linkStr(i.link)).join(', ') + hd);
        break;
      case 'share-buttons':
        line(d, '[share buttons] ' + (st.share_buttons || []).map(b => b.button || 'facebook').join(', ') + hd);
        break;
      case 'divider': if (st.look === 'line_text' && st.text) line(d, '— ' + oneLine(st.text) + ' —'); break;
      case 'accordion': case 'toggle': case 'tabs':
        (st.tabs || []).forEach(it => { line(d, '[' + w + '] ' + oneLine(it.tab_title)); textOf(it.tab_content).split('\n').forEach(x => line(d + 1, x)); });
        break;
      case 'nested-accordion': case 'nested-tabs': {
        const items = st.items || st.tabs || [];
        kids(e).forEach((k, i) => { line(d, '[' + w.replace('nested-', '') + '] ' + oneLine((items[i] || {}).item_title || (items[i] || {}).tab_title || 'item ' + (i + 1))); render(k, d + 1); });
        break;
      }
      case 'call-to-action':
        line(d, '[call to action] ' + [st.title, st.description].filter(Boolean).map(oneLine).join(' | ') + (st.button ? ' [button] ' + oneLine(st.button) : '') + linkStr(st.link) + (st.bg_image && (st.bg_image.id || st.bg_image.url) ? ' [background ' + imageLabel(st.bg_image) + ']' : '') + hd);
        break;
      case 'flip-box':
        line(d, '[flip box] front: ' + [st.title_text_a, st.description_text_a].filter(Boolean).map(oneLine).join(' | ') + ' / back: ' + [st.title_text_b, st.description_text_b].filter(Boolean).map(oneLine).join(' | ') + (st.button_text ? ' [button] ' + oneLine(st.button_text) : '') + linkStr(st.link) + hd);
        break;
      case 'counter': line(d, '[counter] ' + [st.prefix, st.ending_number, st.suffix].filter(x => x !== undefined && x !== '').join('') + (st.title ? ' ' + oneLine(st.title) : '') + hd); break;
      case 'star-rating': line(d, '[rating] ' + (st.rating || '') + (st.title ? ' ' + oneLine(st.title) : '') + hd); break;
      case 'theme-post-content': {
        const p = ctx.post();
        line(d, '[post content]');
        if (p && p.elementor && p.elementor.length && !(opts.seen || []).includes('post:' + p.id)) reduceElementor(p.elementor, ctx, { seen: (opts.seen || []).concat('post:' + p.id), nested: true }).forEach(x => line(d + 1, x));
        else ctx.postContent().split('\n').forEach(x => line(d + 1, x));
        break;
      }
      case 'theme-post-excerpt': case 'post-excerpt': line(d, oneLine((ctx.post() || {}).excerpt || '') || '(excerpt)'); break;
      case 'post-info': line(d, '[post meta] ' + (st.icon_list || []).map(i => i.type).join(', ') + hd); break;
      case 'posts': case 'archive-posts': case 'loop-grid': case 'loop-carousel': case 'portfolio': {
        const type = st.posts_post_type || st.post_query_post_type || (w === 'archive-posts' ? '(current archive)' : 'post');
        if (w === 'archive-posts' || type === 'current_query') { line(d, '[archive listing: the current archive\'s posts]' + hd); break; }
        postsList(e, d, type);
        if (st.template_id) expandTemplate(st.template_id, d + 1, 'loop item template');
        break;
      }
      case 'template': expandTemplate(st.template_id, d, 'template'); break;
      case 'global': expandTemplate(e.templateID, d, 'global widget'); break;
      default:
        if (kids(e).length) { line(d, '[' + w + ']' + hd); kids(e).forEach(k => render(k, d + 1)); }
        else generic(e, d);
    }
  }

  roots.filter(Boolean).forEach((r, i) => {
    if (opts.nested) { render(r, 0); return; }
    const st = s(r);
    const name = st._title || (st._element_id ? '#' + st._element_id : '') || (r.elType === 'widget' ? r.widgetType : r.elType);
    out.push('');
    out.push('### ' + (i + 1) + '. ' + name);
    if (CONTAINERS.has(r.elType)) { out.push('_layout: ' + layoutNote(r) + '_'); kids(r).forEach(k => render(k, 0)); }
    else render(r, 0);
  });
  return out;
}

module.exports = reduceElementor;
