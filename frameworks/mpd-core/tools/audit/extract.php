<?php
/**
 * mpd-core Stage 2 — per-site usage extractor.
 *
 * Runs inside ONE site via `wp eval-file` (audit.sh supplies the socket). Parses everything
 * in PHP and writes a single anonymous JSON file. Prints one summary line. Never echoes
 * postmeta, option values, or anything that identifies the site.
 *
 * Env: OUT (output path), SITE_ID (e.g. site-03), REDACT (comma list of site-identifying
 * tokens; any custom name containing one is replaced by a count).
 */

$out     = getenv( 'OUT' );
$site_id = getenv( 'SITE_ID' );
$redact  = array_filter( array_map( 'strtolower', explode( ',', (string) getenv( 'REDACT' ) ) ), fn( $t ) => strlen( $t ) >= 3 );

global $wpdb;

// ---------- ACSS reference sets (from this site's own install) ----------
$acss_dir      = WP_PLUGIN_DIR . '/automaticcss-plugin';
$acss_present  = is_dir( $acss_dir );
$acss_classes  = [];
$acss_defaults = [];
if ( $acss_present ) {
	$c = json_decode( (string) @file_get_contents( "$acss_dir/config/classes.json" ), true );
	$acss_classes = array_flip( $c['classes'] ?? [] );
	$v = json_decode( (string) @file_get_contents( "$acss_dir/config/variables.json" ), true );
	foreach ( $v['variables'] ?? [] as $k => $def ) $acss_defaults[ $k ] = $def['default'] ?? null;
}
// Variables ACSS actually declares on this site (compiled output).
$acss_vars = [];
foreach ( [ 'automatic-variables.css', 'automatic.css' ] as $f ) {
	$p = WP_CONTENT_DIR . "/uploads/automatic-css/$f";
	if ( is_file( $p ) && preg_match_all( '/(?<![\w-])--([a-z0-9][a-z0-9-]*)\s*:/i', file_get_contents( $p ), $m ) ) {
		foreach ( $m[1] as $n ) $acss_vars[ strtolower( $n ) ] = true;
	}
}

// ---------- helpers ----------
$is_redacted = function ( $name ) use ( $redact ) {
	$n = strtolower( $name );
	foreach ( $redact as $t ) if ( strpos( $n, $t ) !== false ) return true;
	return false;
};
$CODE_KEYS = [ '_cssCustom', 'cssCode', 'code', 'customCss', 'customScriptsHeader', 'customScriptsBodyHeader', 'customScriptsBodyFooter', 'css', 'scss' ];

$S = [
	'var_refs'      => [],   // name => count   (var(--name) anywhere)
	'bare_refs'     => [],   // name => count   (bare --name in a NON-code Bricks field = ACSS shorthand)
	'var_by_source' => [],   // source => count
	'gclass_use'    => [],   // acss global class name => element uses
	'gclass_custom_uses' => 0,
	'gclass_custom_used' => [], // id => true (count only, names never stored)
	'raw_classes'   => [],   // acss utility name => count (from _cssClasses strings)
	'raw_custom_tokens' => 0,
	'patterns'      => [ 'clickable-parent' => 0, 'focus-parent' => 0, 'root_selector' => 0, 'include_clickable' => 0, 'include_focus' => 0 ],
	'elements'      => 0,
	'posts'         => 0,
];

$count_vars = function ( $str, $source ) use ( &$S ) {
	if ( ! is_string( $str ) || strpos( $str, '--' ) === false ) return;
	if ( preg_match_all( '/var\(\s*--([a-z0-9][a-z0-9-]*)/i', $str, $m ) ) {
		foreach ( $m[1] as $n ) { $n = strtolower( $n ); $S['var_refs'][ $n ] = ( $S['var_refs'][ $n ] ?? 0 ) + 1; }
		$S['var_by_source'][ $source ] = ( $S['var_by_source'][ $source ] ?? 0 ) + count( $m[1] );
	}
};
$count_bare = function ( $str ) use ( &$S ) {
	if ( ! is_string( $str ) || strpos( $str, '--' ) === false ) return;
	if ( preg_match_all( '/(?<![\w-])--([a-z][a-z0-9-]*)/i', $str, $m, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $m[1] as $hit ) {
			[ $n, $off ] = $hit;
			$before = substr( $str, max( 0, $off - 12 ), min( 12, $off ) );
			$after  = substr( $str, $off + strlen( $n ), 3 );
			if ( preg_match( '/var\(\s*--$/i', $before ) ) continue;   // proper var(--x)
			if ( preg_match( '/^\s*:/', $after ) ) continue;           // a declaration, not a use
			$n = strtolower( $n );
			$S['bare_refs'][ $n ] = ( $S['bare_refs'][ $n ] ?? 0 ) + 1;
		}
	}
};
$count_patterns = function ( $str ) use ( &$S ) {
	if ( ! is_string( $str ) ) return;
	$S['patterns']['clickable-parent']  += substr_count( $str, 'clickable-parent' );
	$S['patterns']['focus-parent']      += preg_match_all( '/focus-parent/', $str );
	$S['patterns']['root_selector']     += substr_count( $str, '%root%' );
	$S['patterns']['include_clickable'] += preg_match_all( '/@include\s+clickable-parent/', $str );
	$S['patterns']['include_focus']     += preg_match_all( '/@include\s+focus-parent/', $str );
};
// Walk a Bricks settings array: var() everywhere, bare --x only outside code fields.
$walk = function ( $node, $source, $in_code = false ) use ( &$walk, $count_vars, $count_bare, $count_patterns, $CODE_KEYS ) {
	if ( is_array( $node ) ) {
		foreach ( $node as $k => $v ) $walk( $v, $source, $in_code || ( is_string( $k ) && in_array( $k, $CODE_KEYS, true ) ) );
		return;
	}
	if ( ! is_string( $node ) ) return;
	$count_vars( $node, $source );
	$count_patterns( $node );
	if ( ! $in_code ) $count_bare( $node );
};

// ---------- global classes ----------
$classes  = get_option( 'bricks_global_classes', [] ) ?: [];
$by_id    = [];
foreach ( $classes as $c ) {
	$is_acss = strpos( $c['id'] ?? '', 'acss_import_' ) === 0 || isset( $acss_classes[ $c['name'] ?? '' ] );
	$by_id[ $c['id'] ?? '' ] = [ 'name' => $c['name'] ?? '', 'acss' => $is_acss ];
	$walk( $c['settings'] ?? [], 'global_classes' );
}
$S['gclass_defined'] = [ 'total' => count( $classes ), 'acss' => count( array_filter( $by_id, fn( $c ) => $c['acss'] ) ) ];

// ---------- element trees (postmeta) ----------
$meta = $wpdb->get_results( "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id
	WHERE pm.meta_key IN ('_bricks_page_content_2','_bricks_page_header_2','_bricks_page_footer_2')
	AND p.post_status NOT IN ('trash','auto-draft','inherit')" );
$posts = [];
foreach ( $meta as $row ) {
	$tree = maybe_unserialize( $row->meta_value );
	if ( ! is_array( $tree ) ) continue;
	$posts[ $row->post_id ] = true;
	foreach ( $tree as $el ) {
		if ( ! is_array( $el ) ) continue;
		$S['elements']++;
		$set = $el['settings'] ?? [];
		$walk( $set, 'elements' );
		foreach ( (array) ( $set['_cssGlobalClasses'] ?? [] ) as $cid ) {
			if ( ! isset( $by_id[ $cid ] ) ) continue;
			if ( $by_id[ $cid ]['acss'] ) { $n = $by_id[ $cid ]['name']; $S['gclass_use'][ $n ] = ( $S['gclass_use'][ $n ] ?? 0 ) + 1; }
			else { $S['gclass_custom_uses']++; $S['gclass_custom_used'][ $cid ] = true; }
			$count_patterns( $by_id[ $cid ]['name'] );
		}
		if ( ! empty( $set['_cssClasses'] ) && is_string( $set['_cssClasses'] ) ) {
			foreach ( preg_split( '/\s+/', trim( $set['_cssClasses'] ) ) as $tok ) {
				if ( $tok === '' || strpos( $tok, '{' ) !== false ) continue;
				if ( isset( $acss_classes[ $tok ] ) ) $S['raw_classes'][ $tok ] = ( $S['raw_classes'][ $tok ] ?? 0 ) + 1;
				else $S['raw_custom_tokens']++;
			}
		}
	}
}
$S['posts'] = count( $posts );
$S['gclass_custom_used'] = count( $S['gclass_custom_used'] );

// ---------- theme styles, global settings, variables, palette ----------
$walk( get_option( 'bricks_theme_styles', [] ) ?: [], 'theme_styles' );
$walk( get_option( 'bricks_global_settings', [] ) ?: [], 'global_settings' );
$gv = get_option( 'bricks_global_variables', [] ) ?: [];
$walk( $gv, 'global_variables' );
$pal = get_option( 'bricks_color_palette', [] ) ?: [];
$S['bricks_vars'] = count( $gv );
$S['palette_colors'] = array_sum( array_map( fn( $p ) => count( $p['colors'] ?? [] ), is_array( $pal ) ? $pal : [] ) );

// ---------- ACSS settings: custom CSS + framework-shaping values ----------
$acss = get_option( 'automatic_css_settings', [] ) ?: [];
$S['acss_installed'] = $acss_present;
$S['acss_version']   = $acss_present ? ( get_plugin_data( "$acss_dir/automaticcss-plugin.php", false, false )['Version'] ?? null ) : null;
if ( $acss ) {
	foreach ( [ 'custom-global-css' ] as $k ) if ( ! empty( $acss[ $k ] ) ) { $walk( $acss[ $k ], 'acss_custom_css', true ); $count_patterns( $acss[ $k ] ); }
	$shape = [ 'root-font-size', 'vp-min', 'vp-max', 'space-scale', 'mob-space-scale', 'text-scale', 'mob-text-scale', 'heading-scale', 'mob-heading-scale', 'base-radius', 'radius-scale', 'focus-width', 'focus-style', 'body-max-width' ];
	foreach ( $shape as $k ) $S['acss_shape'][ $k ] = [ 'value' => $acss[ $k ] ?? null, 'default' => $acss_defaults[ $k ] ?? null ];
	$changed = 0; $toggles_on = [];
	foreach ( $acss as $k => $v ) {
		if ( ! array_key_exists( $k, $acss_defaults ) ) continue;
		if ( (string) $v !== (string) $acss_defaults[ $k ] ) $changed++;
		if ( strpos( $k, 'option-' ) === 0 && $v === 'on' ) $toggles_on[] = $k;   // generic framework toggles, no brand data
	}
	$S['acss_changed_from_default'] = $changed;
	$S['acss_toggles_on'] = $toggles_on;
}

// ---------- child theme CSS ----------
$child = get_stylesheet_directory();
if ( $child !== get_template_directory() && is_dir( $child ) ) {
	foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $child, FilesystemIterator::SKIP_DOTS ) ) as $f ) {
		if ( ! preg_match( '/\.(css|scss)$/', $f ) || strpos( $f, '/node_modules/' ) !== false ) continue;
		$src = file_get_contents( $f );
		$walk( $src, 'child_theme', true );
	}
}

// ---------- classify + redact ----------
$classify = function ( array $refs ) use ( $acss_vars, $is_redacted ) {
	$out = [ 'acss' => [], 'custom' => [], 'redacted_custom' => 0 ];
	foreach ( $refs as $n => $cnt ) {
		if ( isset( $acss_vars[ $n ] ) )  $out['acss'][ $n ] = $cnt;
		elseif ( $is_redacted( $n ) )     $out['redacted_custom'] += $cnt;
		else                              $out['custom'][ $n ] = $cnt;
	}
	arsort( $out['acss'] ); arsort( $out['custom'] );
	return $out;
};
$S['var_refs']  = $classify( $S['var_refs'] );
$S['bare_refs'] = $classify( $S['bare_refs'] );
arsort( $S['gclass_use'] ); arsort( $S['raw_classes'] );

$S = [ 'site' => $site_id, 'bricks' => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : null, 'acss_vars_known' => count( $acss_vars ) ] + $S;
file_put_contents( $out, wp_json_encode( $S, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
printf( "%s  bricks=%s acss=%s  posts=%d elements=%d  var() acss/custom=%d/%d  bare=%d  acss-classes-used=%d\n",
	$site_id, $S['bricks'], $S['acss_version'] ?: 'none', $S['posts'], $S['elements'],
	array_sum( $S['var_refs']['acss'] ), array_sum( $S['var_refs']['custom'] ) + $S['var_refs']['redacted_custom'],
	array_sum( $S['bare_refs']['acss'] ) + array_sum( $S['bare_refs']['custom'] ) + $S['bare_refs']['redacted_custom'],
	count( $S['gclass_use'] ) + count( $S['raw_classes'] ) );
