<?php
/**
 * wp-to-flat harvester. Runs inside WordPress through WP-CLI and prints ONE JSON bundle to stdout:
 *
 *   wp --path=<root> eval-file harvest.php            (local)
 *   ssh <host> wp --path=<root> eval-file - < harvest.php   (remote; harvest.sh does this)
 *
 * READ-ONLY. It never calls update_*, add_*, delete_*, wp_insert_*, or anything that writes. WordPress loads
 * normally (plugins and theme), because custom post types, their permalinks and ACF field objects exist only
 * when their plugins load.
 *
 * The bundle carries raw builder data (Bricks element trees, post_content). Scripts read it; Claude reads only
 * what reduce.js writes. Don't open bundle.json in a session.
 */

global $wpdb;
$B = ['schema' => 1, 'harvested_at' => gmdate('c')];

/* ---------- helpers ---------- */
$uploads = wp_get_upload_dir();
$meta_all = function ($id) {
	$out = [];
	foreach (get_post_meta($id) as $k => $vals) $out[$k] = maybe_unserialize($vals[0]);
	return $out;
};
$plugin_active = function ($needle) {
	foreach ((array) get_option('active_plugins', []) as $p) if (strpos($p, $needle) === 0) return true;
	return false;
};
$table = function ($name) use ($wpdb) {
	$t = $wpdb->prefix . $name;
	return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $t)) === $t ? $t : null;
};

/* ---------- site ---------- */
if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
$all_plugins = get_plugins();
$active = (array) get_option('active_plugins', []);
$plugins = [];
foreach ($all_plugins as $file => $h) {
	$dir = WP_PLUGIN_DIR . '/' . dirname($file);
	$plugins[] = [
		'file' => $file, 'name' => $h['Name'], 'version' => $h['Version'], 'author' => wp_strip_all_tags($h['Author']),
		'uri' => $h['PluginURI'], 'update_uri' => $h['UpdateURI'] ?? '', 'active' => in_array($file, $active, true),
		// Heuristic for "custom": no readme.txt (wordpress.org plugins all ship one). reduce.js flags these for review.
		'has_readme' => dirname($file) !== '.' && (file_exists("$dir/readme.txt") || file_exists("$dir/README.txt")),
	];
}
$theme = wp_get_theme();
$B['site'] = [
	'name' => get_bloginfo('name'), 'tagline' => get_bloginfo('description'),
	'home' => home_url('/'), 'siteurl' => site_url('/'), 'language' => get_bloginfo('language'),
	'timezone' => wp_timezone_string(), 'permalink_structure' => get_option('permalink_structure'),
	'blog_public' => (int) get_option('blog_public'), 'wp_version' => get_bloginfo('version'), 'php' => PHP_VERSION,
	'show_on_front' => get_option('show_on_front'), 'page_on_front' => (int) get_option('page_on_front'),
	'page_for_posts' => (int) get_option('page_for_posts'),
	'theme' => ['name' => $theme->get('Name'), 'stylesheet' => get_stylesheet(), 'template' => get_template(), 'version' => $theme->get('Version')],
	'site_icon' => (int) get_option('site_icon'), 'custom_logo' => (int) get_theme_mod('custom_logo'),
	'plugins' => $plugins,
	'mu_plugins' => array_values(array_map(fn($h) => $h['Name'], get_mu_plugins())),
	'dropins' => array_keys(get_dropins()),
	'multisite' => is_multisite(),
];

/* ---------- candidate check ---------- */
$types = get_post_types(['public' => true], 'objects');
$counts = [];
$skip_types = ['attachment', 'bricks_template', 'elementor_library', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation'];
foreach ($types as $t) if (!in_array($t->name, $skip_types, true)) $counts[$t->name] = (int) (wp_count_posts($t->name)->publish ?? 0);
$users = count_users();
$B['candidate'] = [
	'published_by_type' => $counts,
	'woocommerce' => class_exists('WooCommerce'),
	'edd' => class_exists('Easy_Digital_Downloads'),
	'users_by_role' => $users['avail_roles'],
	'comments_approved' => (int) wp_count_comments()->approved,
	'membership_plugins' => array_values(array_filter(array_map(fn($p) => $p['active'] ? $p['file'] : null, $plugins),
		fn($f) => $f && preg_match('~^(memberpress|paid-memberships|ultimate-member|buddypress|bbpress|sfwd-lms|lifterlms|restrict-content|woocommerce-memberships|wishlist-member|s2member)~', $f))),
];

/* ---------- posts (every public URL that is a post object) ---------- */
$B['posts'] = [];
foreach ($types as $t) {
	if (in_array($t->name, $skip_types, true)) continue;
	$q = get_posts(['post_type' => $t->name, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'suppress_filters' => true]);
	foreach ($q as $p) {
		$m = $meta_all($p->ID);
		$bricks = $m['_bricks_page_content_2'] ?? null;
		$builder = 'empty';
		if (is_array($bricks) && $bricks && ($m['_bricks_editor_mode'] ?? 'bricks') === 'bricks') $builder = 'bricks';
		elseif (!empty($m['_elementor_data']) && ($m['_elementor_edit_mode'] ?? '') === 'builder') $builder = 'elementor';
		elseif (!empty($m['_et_pb_use_builder']) && $m['_et_pb_use_builder'] === 'on') $builder = 'divi';
		elseif (strpos($p->post_content, 'vc_row') !== false) $builder = 'wpbakery';
		elseif (has_blocks($p->post_content)) $builder = 'gutenberg';
		elseif (trim($p->post_content) !== '') $builder = 'classic';
		// SEO meta: every key from the three common plugins, whichever is active (leftovers from a switch included).
		$seo = [];
		foreach ($m as $k => $v) if (preg_match('~^(_seopress_|rank_math_|_yoast_wpseo_|_aioseo_)~', $k) && !preg_match('~(_internal_links|_seo_score|_analytics|_linkdex|_content_score|_primary_)~', $k)) $seo[$k] = $v;
		$B['posts'][] = [
			'id' => $p->ID, 'type' => $p->post_type, 'type_label' => $t->labels->singular_name, 'slug' => $p->post_name,
			'title' => get_the_title($p), 'url' => get_permalink($p), 'parent' => $p->post_parent, 'menu_order' => $p->menu_order,
			'page_template' => $m['_wp_page_template'] ?? '', 'modified' => $p->post_modified_gmt, 'date' => $p->post_date_gmt,
			'excerpt' => $p->post_excerpt, 'featured' => (int) ($m['_thumbnail_id'] ?? 0), 'builder' => $builder,
			'is_front' => $p->ID === (int) get_option('page_on_front'), 'is_posts_page' => $p->ID === (int) get_option('page_for_posts'),
			'post_content' => $builder === 'bricks' ? '' : $p->post_content,
			'bricks' => $builder === 'bricks' ? array_values($bricks) : null,
			'seo' => $seo,
		];
	}
}

/* ---------- Bricks: templates, components, class names, scripts ---------- */
$B['bricks'] = null;
if (get_template() === 'bricks' || defined('BRICKS_VERSION')) {
	$tpls = [];
	foreach ($wpdb->get_results("SELECT p.ID, p.post_title, p.post_name, p.post_type, p.post_status, m.meta_value AS type FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_bricks_template_type' WHERE p.post_status = 'publish'") as $r) {
		$key = $r->type === 'header' ? '_bricks_page_header_2' : ($r->type === 'footer' ? '_bricks_page_footer_2' : '_bricks_page_content_2');
		$s = get_post_meta($r->ID, '_bricks_template_settings', true);
		$tpls[] = [
			'id' => (int) $r->ID, 'title' => $r->post_title, 'slug' => $r->post_name, 'post_type' => $r->post_type, 'type' => $r->type,
			'conditions' => is_array($s) ? ($s['templateConditions'] ?? []) : [],
			'elements' => array_values((array) get_post_meta($r->ID, $key, true)),
		];
	}
	$classes = [];
	foreach ((array) get_option('bricks_global_classes', []) as $c) if (isset($c['id'], $c['name'])) $classes[$c['id']] = $c['name'];
	$gs = (array) get_option('bricks_global_settings', []);
	$B['bricks'] = [
		'version' => defined('BRICKS_VERSION') ? BRICKS_VERSION : null,
		'templates' => $tpls,
		'components' => array_values((array) get_option('bricks_components', [])),
		'class_names' => $classes,
		'scripts' => array_filter(array_intersect_key($gs, array_flip(['customScriptsHeader', 'customScriptsBodyHeader', 'customScriptsBodyFooter']))),
		'custom_fonts' => array_map(fn($f) => ['id' => $f->ID, 'name' => $f->post_title, 'faces' => get_post_meta($f->ID, 'bricks_font_faces', true)],
			get_posts(['post_type' => 'bricks_fonts', 'numberposts' => -1, 'post_status' => 'publish'])),
		'google_fonts_disabled' => !empty($gs['disableGoogleFonts']),
	];
}

/* ---------- Bricks dynamic data, rendered by Bricks itself ----------
 * Every distinct {tag} in any element tree, rendered once per post (as that post's context) and once with the
 * front page as context (for headers/footers). Custom tags registered by site plugins resolve too, which is the
 * point: the reducer never has to know them. Values are what the live site would print. */
$B['dynamic'] = null;
if ($B['bricks'] && function_exists('bricks_render_dynamic_data')) {
	$blob = wp_json_encode(array_merge(array_column($B['posts'], 'bricks'), array_column($B['bricks']['templates'], 'elements'), [$B['bricks']['components']]), JSON_UNESCAPED_SLASHES);
	preg_match_all('~\{([a-z][a-zA-Z0-9_:\-\|@ ]*)\}~', $blob, $mm);
	$tags = array_values(array_unique($mm[0]));
	$ctx = array_unique(array_merge(array_column($B['posts'], 'id'), [(int) get_option('page_on_front')]));
	$dyn = [];
	foreach ($ctx as $pid) {
		if (!$pid) continue;
		$GLOBALS['post'] = get_post($pid); setup_postdata($GLOBALS['post']);
		foreach ($tags as $tag) {
			$v = bricks_render_dynamic_data($tag, $pid);
			$dyn[$pid][$tag] = is_scalar($v) ? (string) $v : wp_json_encode($v);
		}
	}
	wp_reset_postdata();
	$B['dynamic'] = ['tags' => $tags, 'front_page' => (int) get_option('page_on_front'), 'values' => $dyn];
}

/* ---------- ACF: field definitions and values (unformatted: images as IDs) ---------- */
$B['acf'] = null;
if (function_exists('acf_get_field_groups')) {
	$groups = [];
	foreach (acf_get_field_groups() as $g) {
		$groups[] = ['key' => $g['key'], 'title' => $g['title'], 'location' => $g['location'],
			'fields' => array_map(fn($f) => ['name' => $f['name'], 'label' => $f['label'], 'type' => $f['type'],
				'sub_fields' => isset($f['sub_fields']) ? array_map(fn($s) => ['name' => $s['name'], 'label' => $s['label'], 'type' => $s['type']], $f['sub_fields']) : null],
				acf_get_fields($g) ?: [])];
	}
	$vals = function ($id) {
		$out = [];
		foreach (get_field_objects($id, false) ?: [] as $name => $f) if (is_array($f)) $out[$name] = ['label' => $f['label'], 'type' => $f['type'],
			// Groups and repeaters come back keyed by field key when unformatted; format those so names show.
			'value' => in_array($f['type'], ['group', 'repeater', 'flexible_content'], true) ? get_field($name, $id) : $f['value']];
		return $out;
	};
	$per_post = [];
	foreach ($B['posts'] as $p) { $v = $vals($p['id']); if ($v) $per_post[$p['id']] = $v; }
	$B['acf'] = ['groups' => $groups, 'posts' => $per_post, 'options' => $vals('option'),
		'options_pages' => function_exists('acf_get_options_pages') ? array_keys(acf_get_options_pages() ?: []) : []];
}

/* ---------- SEO plugin: which one is active, plus its global templates ---------- */
$seo_plugin = $plugin_active('wp-seopress') ? 'seopress' : ($plugin_active('seo-by-rank-math') ? 'rankmath' : ($plugin_active('wordpress-seo') ? 'yoast' : ($plugin_active('all-in-one-seo') ? 'aioseo' : null)));
$B['seo'] = [
	'plugin' => $seo_plugin,
	'options' => array_filter([
		'seopress_titles' => get_option('seopress_titles_option_name') ?: null,
		'seopress_social' => get_option('seopress_social_option_name') ?: null,
		'rankmath_titles' => get_option('rank-math-options-titles') ?: null,
		'yoast_titles' => get_option('wpseo_titles') ?: null,
	]),
	'redirects' => [],
];
if (($t = $table('rank_math_redirections')) && ($r = $wpdb->get_results("SELECT sources, url_to, header_code, status FROM $t", ARRAY_A))) $B['seo']['redirects']['rankmath'] = $r;
if (($t = $table('redirection_items')) && ($r = $wpdb->get_results("SELECT url, action_data, action_code, status FROM $t", ARRAY_A))) $B['seo']['redirects']['redirection'] = $r;
$sp = get_posts(['post_type' => 'seopress_404', 'numberposts' => -1, 'post_status' => 'any']);
if ($sp) $B['seo']['redirects']['seopress'] = array_map(fn($p) => ['from' => $p->post_title, 'to' => get_post_meta($p->ID, '_seopress_redirections_value', true),
	'code' => get_post_meta($p->ID, '_seopress_redirections_type', true), 'enabled' => get_post_meta($p->ID, '_seopress_redirections_enabled', true)], $sp);

/* ---------- other public URLs: archives, authors, attachments ---------- */
$terms = [];
foreach (get_taxonomies(['public' => true], 'objects') as $tx) {
	if ($tx->name === 'post_format') continue;
	foreach (get_terms(['taxonomy' => $tx->name, 'hide_empty' => true]) as $tm) $terms[] = ['taxonomy' => $tx->name, 'slug' => $tm->slug, 'name' => $tm->name, 'count' => $tm->count, 'url' => get_term_link($tm)];
}
$authors = array_map(fn($u) => ['id' => $u->ID, 'slug' => $u->user_nicename, 'url' => get_author_posts_url($u->ID)],
	get_users(['has_published_posts' => true, 'fields' => ['ID', 'user_nicename']]));
$B['archives'] = [
	'terms' => $terms, 'authors' => $authors,
	'post_type_archives' => array_values(array_filter(array_map(fn($t) => $t->has_archive ? ['type' => $t->name, 'url' => get_post_type_archive_link($t->name)] : null, $types))),
	'attachment_pages' => (int) get_option('wp_attachment_pages_enabled', 1),
	'attachments_total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'"),
];

/* ---------- menus ---------- */
$locs = get_nav_menu_locations();
$B['menus'] = array_map(fn($m) => [
	'id' => $m->term_id, 'name' => $m->name, 'locations' => array_keys(array_filter($locs, fn($id) => $id === $m->term_id)),
	'items' => array_map(fn($i) => ['id' => $i->ID, 'parent' => (int) $i->menu_item_parent, 'title' => $i->title, 'url' => $i->url,
		'object' => $i->object, 'object_id' => (int) $i->object_id, 'type' => $i->type, 'target' => $i->target], wp_get_nav_menu_items($m->term_id) ?: []),
], wp_get_nav_menus());

/* ---------- forms (definitions only; never submissions) ---------- */
$forms = [];
if ($t = $table('wsf_form')) {
	foreach ($wpdb->get_results("SELECT id, label, status FROM $t WHERE status <> 'trash'") as $f) {
		$ft = $wpdb->prefix . 'wsf_field';
		$forms[] = ['plugin' => 'ws-form', 'id' => (int) $f->id, 'title' => $f->label, 'status' => $f->status,
			'fields' => $wpdb->get_results($wpdb->prepare("SELECT label, type FROM $ft WHERE form_id = %d ORDER BY sort_index", $f->id), ARRAY_A)];
	}
}
foreach (get_posts(['post_type' => 'wpcf7_contact_form', 'numberposts' => -1]) as $f) {
	$mail = get_post_meta($f->ID, '_mail', true);
	preg_match_all('~\[([a-z]+\*?)\s+([\w-]+)~', (string) get_post_meta($f->ID, '_form', true), $mm, PREG_SET_ORDER);
	$forms[] = ['plugin' => 'cf7', 'id' => $f->ID, 'title' => $f->post_title, 'to' => $mail['recipient'] ?? '',
		'fields' => array_map(fn($x) => ['label' => $x[2], 'type' => $x[1]], $mm)];
}
if ($t = $table('gf_form')) {
	$mt = $wpdb->prefix . 'gf_form_meta';
	foreach ($wpdb->get_results("SELECT f.id, f.title, m.display_meta FROM $t f LEFT JOIN $mt m ON m.form_id = f.id WHERE f.is_trash = 0") as $f) {
		$d = json_decode((string) $f->display_meta, true) ?: [];
		$forms[] = ['plugin' => 'gravity', 'id' => (int) $f->id, 'title' => $f->title,
			'fields' => array_map(fn($x) => ['label' => $x['label'] ?? '', 'type' => $x['type'] ?? ''], $d['fields'] ?? [])];
	}
}
foreach (get_posts(['post_type' => 'wpforms', 'numberposts' => -1]) as $f) {
	$d = json_decode($f->post_content, true) ?: [];
	$forms[] = ['plugin' => 'wpforms', 'id' => $f->ID, 'title' => $f->post_title,
		'fields' => array_values(array_map(fn($x) => ['label' => $x['label'] ?? '', 'type' => $x['type'] ?? ''], $d['fields'] ?? []))];
}
if ($t = $table('fluentform_forms')) {
	foreach ($wpdb->get_results("SELECT id, title, form_fields FROM $t") as $f) {
		$d = json_decode((string) $f->form_fields, true) ?: [];
		$forms[] = ['plugin' => 'fluentform', 'id' => (int) $f->id, 'title' => $f->title,
			'fields' => array_map(fn($x) => ['label' => $x['settings']['label'] ?? ($x['attributes']['name'] ?? ''), 'type' => $x['element'] ?? ''], $d['fields'] ?? [])];
	}
}
$B['forms'] = $forms;

/* ---------- injected scripts and analytics ---------- */
$scripts = [];
if ($o = get_option('seopress_google_analytics_option_name')) $scripts['seopress_analytics'] = array_filter((array) $o, fn($v, $k) => $v !== '' && $v !== null && preg_match('~(ga4|_ua|gtag|enable|other_tracking|matomo|clarity|tag_manager|gtm)~i', $k), ARRAY_FILTER_USE_BOTH);
if ($o = get_option('googlesitekit_analytics-4_settings')) $scripts['site_kit_ga4'] = ['measurementID' => $o['measurementID'] ?? ''];
foreach (['ihaf_insert_header', 'ihaf_insert_body', 'ihaf_insert_footer'] as $k) if ($v = get_option($k)) $scripts[$k] = $v;
if ($o = get_option('perfmatters_options')) {
	$pm = array_filter([
		'local_analytics' => !empty($o['analytics']['enable_local_ga']) ? ($o['analytics']['tracking_id'] ?? 'on') : null,
		'header_code' => $o['assets']['header_code'] ?? null, 'body_code' => $o['assets']['body_code'] ?? null, 'footer_code' => $o['assets']['footer_code'] ?? null,
	]);
	if ($pm) $scripts['perfmatters'] = $pm;
}
if ($t = $table('snippets')) $scripts['code_snippets'] = $wpdb->get_results("SELECT name, scope, active, CHAR_LENGTH(code) AS chars FROM $t", ARRAY_A);
foreach (get_posts(['post_type' => 'wpcode', 'numberposts' => -1, 'post_status' => 'publish']) as $s) $scripts['wpcode'][] = ['title' => $s->post_title, 'chars' => strlen($s->post_content)];
if ($B['bricks'] && $B['bricks']['scripts']) $scripts['bricks'] = $B['bricks']['scripts'];
$B['scripts'] = $scripts;

/* ---------- media actually referenced ---------- */
$refs = [];   // attachment id => [referrer labels]
$add = function ($id, $by) use (&$refs) { $id = (int) $id; if ($id > 0) $refs[$id][$by] = true; };
$walk = function ($node, $by) use (&$walk, $add) {
	if (!is_array($node)) return;
	// Bricks image objects: {id, filename|url|full, size}. Galleries: {images: [{id,...}]}.
	if (isset($node['id']) && is_numeric($node['id']) && (isset($node['url']) || isset($node['filename']) || isset($node['full']))) $add($node['id'], $by);
	foreach ($node as $v) $walk($v, $by);
};
$scan_urls = function ($blob, $by) use ($add, $uploads) {
	if (!preg_match_all('~' . preg_quote(wp_make_link_relative($uploads['baseurl']), '~') . '/[^"\'\s)\\\\]+~', $blob, $mm)) return;
	foreach (array_unique($mm[0]) as $u) {
		$full = preg_replace('~-\d+x\d+(?=\.\w+$)~', '', $u);
		$id = attachment_url_to_postid(home_url($full)) ?: attachment_url_to_postid(home_url(preg_replace('~(\.\w+)$~', '-scaled$1', $full)));
		if ($id) $add($id, $by);
	}
};
foreach ($B['posts'] as $p) {
	$by = $p['type'] . ':' . $p['slug'];
	if ($p['featured']) $add($p['featured'], "$by (featured)");
	if ($p['bricks']) { $walk($p['bricks'], $by); $scan_urls(wp_json_encode($p['bricks'], JSON_UNESCAPED_SLASHES), $by); }
	if ($p['post_content']) { $scan_urls($p['post_content'], $by); if (preg_match_all('~wp-image-(\d+)~', $p['post_content'], $mm)) foreach ($mm[1] as $id) $add($id, $by); }
	foreach (['_seopress_social_fb_img', 'rank_math_facebook_image', '_yoast_wpseo_opengraph-image'] as $k) if (!empty($p['seo'][$k])) $scan_urls((string) $p['seo'][$k], "$by (og)");
}
foreach ($B['bricks']['templates'] ?? [] as $t) { $walk($t['elements'], "template:{$t['slug']}"); $scan_urls(wp_json_encode($t['elements'], JSON_UNESCAPED_SLASHES), "template:{$t['slug']}"); }
if (!empty($B['bricks']['components'])) $walk($B['bricks']['components'], 'components');
if ($B['acf']) {
	$acf_walk = function ($fields, $by) use (&$acf_walk, $add) {
		foreach ($fields as $name => $f) {
			if (in_array($f['type'], ['image', 'file'], true) && is_numeric($f['value'])) $add($f['value'], "$by acf:$name");
			if ($f['type'] === 'gallery' && is_array($f['value'])) foreach ($f['value'] as $id) $add($id, "$by acf:$name");
		}
	};
	foreach ($B['acf']['posts'] as $pid => $fields) { $p = get_post($pid); $acf_walk($fields, $p->post_type . ':' . $p->post_name); }
	$acf_walk($B['acf']['options'], 'options');
}
if ($B['site']['site_icon']) $add($B['site']['site_icon'], 'site icon');
if ($B['site']['custom_logo']) $add($B['site']['custom_logo'], 'custom logo');

$media = [];
foreach ($refs as $id => $by) {
	$a = get_post($id);
	if (!$a || $a->post_type !== 'attachment') { $media[] = ['id' => $id, 'missing' => true, 'referenced_by' => array_keys($by)]; continue; }
	$file = get_attached_file($id);
	$orig = function_exists('wp_get_original_image_path') ? wp_get_original_image_path($id) : $file;   // pre "-scaled" upload
	$rel = ltrim(str_replace($uploads['basedir'], '', (string) $orig), '/');
	$sp = $uploads['basedir'] . '/ShortpixelBackups/' . ltrim(str_replace(ABSPATH, '', $uploads['basedir']), '/') . '/' . $rel;
	$md = wp_get_attachment_metadata($id) ?: [];
	$media[] = [
		'id' => $id, 'title' => $a->post_title, 'alt' => get_post_meta($id, '_wp_attachment_image_alt', true), 'caption' => $a->post_excerpt,
		'mime' => $a->post_mime_type, 'width' => $md['width'] ?? null, 'height' => $md['height'] ?? null, 'url' => wp_get_attachment_url($id),
		'rel' => $rel,
		// Best master available: ShortPixel's pre-optimization backup, else the unscaled original, else the file.
		'source' => file_exists($sp) ? $sp : ($orig && file_exists($orig) ? $orig : $file),
		'source_kind' => file_exists($sp) ? 'shortpixel-backup' : ($orig !== $file ? 'unscaled-original' : 'upload'),
		'bytes' => file_exists((string) $file) ? filesize($file) : null,
		'referenced_by' => array_keys($by),
	];
}
$B['media'] = $media;

/* ---------- fonts on disk (theme, uploads) ---------- */
$fonts = [];
foreach ([get_stylesheet_directory(), $uploads['basedir']] as $root) {
	if (!is_dir($root)) continue;
	$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
	foreach ($it as $f) if (preg_match('~\.(woff2?|ttf|otf)$~i', $f->getFilename()) && strpos($f->getPathname(), 'ShortpixelBackups') === false) $fonts[] = str_replace(ABSPATH, '', $f->getPathname());
}
$B['fonts_on_disk'] = $fonts;

echo wp_json_encode($B, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
