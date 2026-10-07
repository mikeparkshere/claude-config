<?php
/**
 * mpd-core — read back a builder-saved Bricks tree without the blob ever entering a session.
 *
 * The golden rule's readback (`02`), made safe for context: Bricks postmeta never reaches the session
 * (`02`, "Discovery cost"). The full tree and every global class it uses go to disk; stdout gets
 * a summary only: the element outline, every distinct settings key (marked when the KB already names it),
 * and rule flags with values truncated.
 *
 * Writes (mpd-core/schema-seed/):
 *   <slug>.tree.json     — the element tree, as saved
 *   <slug>.classes.json  — the global classes the tree references, as saved
 *   <slug>.summary.md    — what stdout prints
 *
 * Env:
 *   PAGE     post ID or slug (page or bricks_template). Required.
 *   KEY      content | header | footer. Default: whichever `_bricks_page_*_2` key holds a tree.
 *   ROOTS    comma list of element IDs or labels; limits everything to those subtrees.
 *   OUTLINE  max outline lines (default 200; 0 = none).
 *   OUTDIR   where the files go. REQUIRED: a scratchpad, or a gitignored folder in the site's repo. Never this
 *            script's own folder, which in claude-config is a public repo.
 *
 * Flags: element-bound styling (`01`: styled elements get a class), bare `--var` outside `var()` (hard
 * rule 7), hex / rgb literals (no literal colors), `_cssCustom` (typed-settings-first), unregistered
 * breakpoint suffixes (`02`), dangling class IDs, IDs that break the house convention (`02`, info only),
 * and the `01` ARIA rules checkable from the tree: section without aria-labelledby, image rendering an
 * empty alt, eyebrow without aria-hidden. An empty alt is right for a decorative image; the flag asks.
 * BEM (`01`): an element with no class besides the mpd-core patterns (every element gets a named hook,
 * styled or not), a section with no block class (the block goes on the Section, not its Container),
 * and a class name that isn't block, block__element or --modifier form.
 *
 * Read-only. Usage (from the WordPress root): PAGE=12 OUTDIR=/path/to/scratch wp eval-file path/to/readback.php
 */

$page = getenv( 'PAGE' );
if ( ! $page ) { fwrite( STDERR, "PAGE is required (ID or slug)\n" ); exit( 2 ); }

$post = is_numeric( $page ) ? get_post( (int) $page ) : ( get_page_by_path( $page, OBJECT, [ 'page', 'bricks_template' ] ) );
if ( ! $post ) { fwrite( STDERR, "no page or template: $page\n" ); exit( 2 ); }

$keys = [ 'content' => '_bricks_page_content_2', 'header' => '_bricks_page_header_2', 'footer' => '_bricks_page_footer_2' ];
$which = getenv( 'KEY' );
if ( ! $which ) foreach ( $keys as $k => $meta ) if ( get_post_meta( $post->ID, $meta, true ) ) { $which = $k; break; }
$tree = $which ? get_post_meta( $post->ID, $keys[ $which ], true ) : [];
if ( ! is_array( $tree ) || ! $tree ) { fwrite( STDERR, "no Bricks tree on #{$post->ID} ({$post->post_name})\n" ); exit( 1 ); }

// ---- scope to ROOTS ----------------------------------------------------------------------------------
$by_id = array_column( $tree, null, 'id' );
$kids  = [];
foreach ( $tree as $el ) $kids[ $el['parent'] ?? 0 ][] = $el['id'];

$roots = array_filter( array_map( 'trim', explode( ',', getenv( 'ROOTS' ) ?: '' ) ) );
if ( $roots ) {
	$start = [];
	foreach ( $tree as $el ) if ( in_array( $el['id'], $roots, true ) || in_array( $el['label'] ?? '', $roots, true ) ) $start[] = $el['id'];
	if ( ! $start ) { fwrite( STDERR, 'no element matches ROOTS=' . implode( ',', $roots ) . "\n" ); exit( 1 ); }
} else {
	$start = $kids[0] ?? [];
}
$keep = [];
$walk = function ( $id, $depth ) use ( &$walk, &$keep, $kids ) {
	$keep[ $id ] = $depth;
	foreach ( $kids[ $id ] ?? [] as $c ) $walk( $c, $depth + 1 );
};
foreach ( $start as $id ) $walk( $id, 0 );
// $keep was filled depth-first, so its key order is tree order (the stored array is not).
$scoped = array_values( array_filter( array_map( fn( $id ) => $by_id[ $id ] ?? null, array_keys( $keep ) ) ) );

// ---- classes ------------------------------------------------------------------------------------------
$all_classes = array_column( get_option( 'bricks_global_classes', [] ) ?: [], null, 'id' );
$used = []; $dangling = [];
foreach ( $scoped as $el ) foreach ( $el['settings']['_cssGlobalClasses'] ?? [] as $cid ) {
	if ( isset( $all_classes[ $cid ] ) ) $used[ $cid ] = $all_classes[ $cid ];
	else $dangling[] = "{$el['id']} → $cid";
}

// ---- what the KB already names ------------------------------------------------------------------------
$kb = '';
foreach ( [ getenv( 'HOME' ) . '/claude-config/knowledgebase/02-build-pipeline.md', ABSPATH . 'docs/stack-gotchas.md' ] as $f ) if ( is_readable( $f ) ) $kb .= file_get_contents( $f );
$known = fn( $key ) => $kb !== '' && preg_match( '/[\'"`]' . preg_quote( $key, '/' ) . '[\'"`:]/', $kb );

$bps       = array_column( \Bricks\Breakpoints::$breakpoints, 'key' );
$base_key  = fn( $k ) => explode( ':', $k )[0];
$not_style = [ '_cssGlobalClasses', '_cssClasses', '_cssId', '_attributes', '_hidden', '_conditions', '_interactions' ];

$flags = [];
$flag  = function ( $where, $rule, $detail = '' ) use ( &$flags ) {
	$detail = preg_replace( '/\s+/', ' ', (string) $detail );
	$flags[] = sprintf( '%-24s %-22s %s', $where, $rule, mb_strlen( $detail ) > 80 ? mb_substr( $detail, 0, 77 ) . '…' : $detail );
};

// Walk every string leaf of a settings array; $fn( path, value ).
$leaves = function ( $arr, $fn, $path = '' ) use ( &$leaves ) {
	foreach ( $arr as $k => $v ) {
		$p = $path === '' ? (string) $k : "$path.$k";
		is_array( $v ) ? $leaves( $v, $fn, $p ) : ( is_string( $v ) ? $fn( $p, $v ) : null );
	}
};

$check_values = function ( $where, $settings ) use ( $leaves, $flag ) {
	$leaves( $settings, function ( $path, $v ) use ( $where, $flag ) {
		if ( preg_match( '/(^|\.)(url|link|href|text|content|altText)(\.|$)/', $path ) ) return;   // copy and URLs, not styling
		// bare --var: not inside var( and not a declaration (--x: …)
		if ( preg_match_all( '/(?<![\w-])--[a-z][\w-]*/i', $v, $m, PREG_OFFSET_CAPTURE ) ) foreach ( $m[0] as [ $name, $at ] ) {
			$before = substr( $v, max( 0, $at - 4 ), min( 4, $at ) );
			$after  = ltrim( substr( $v, $at + strlen( $name ) ) );
			if ( $before !== 'var(' && ( $after === '' || $after[0] !== ':' ) ) { $flag( $where, 'bare --var', "$path = $v" ); break; }
		}
		if ( preg_match( '/#[0-9a-f]{3,8}\b|\brgba?\(|\bhsla?\(/i', $v ) ) $flag( $where, 'literal color', "$path = $v" );
	} );
};

$check_keys = function ( $where, $settings ) use ( $bps, $flag ) {
	foreach ( array_keys( $settings ) as $k ) {
		if ( $k === '_cssCustom' ) $flag( $where, '_cssCustom', $settings[ $k ] );
		foreach ( array_slice( explode( ':', $k ), 1 ) as $suffix ) {
			$pseudo = in_array( $suffix, [ 'hover', 'focus', 'focus-visible', 'focus-within', 'active', 'visited', 'before', 'after' ], true );
			if ( ! $pseudo && ! in_array( $suffix, $bps, true ) ) $flag( $where, 'unregistered suffix', $k );
		}
	}
};

// ---- elements -----------------------------------------------------------------------------------------
$el_keys = []; $outline = []; $names = [];
foreach ( $scoped as $el ) {
	$s = $el['settings'] ?? [];
	$names[ $el['name'] ] = ( $names[ $el['name'] ] ?? 0 ) + 1;
	foreach ( array_keys( $s ) as $k ) $el_keys[ $el['name'] ][ $k ] = true;

	$where = "{$el['id']} {$el['name']}";
	if ( ! preg_match( '/^(?=.*[a-z])[a-z0-9]{6}$/i', $el['id'] ) ) $flag( $where, 'id convention (info)', $el['id'] );
	$bound = array_filter( array_keys( $s ), fn( $k ) => $k[0] === '_' && ! in_array( explode( ':', $k )[0], $not_style, true ) );
	if ( $bound ) $flag( $where, 'element-bound style', implode( ' ', $bound ) );
	$check_keys( $where, $s );
	$check_values( $where, $s );

	// `01` ARIA rules that are checkable from the tree.
	$attr = array_column( $s['_attributes'] ?? [], 'value', 'name' );
	$cls_names = array_map( fn( $c ) => $all_classes[ $c ]['name'] ?? '', $s['_cssGlobalClasses'] ?? [] );
	$renders_section = $el['name'] === 'section' ? in_array( $s['tag'] ?? 'section', [ 'section' ], true ) : ( ( $s['tag'] ?? '' ) === 'section' );
	if ( $renders_section && empty( $attr['aria-labelledby'] ) && empty( $attr['aria-label'] ) ) $flag( $where, 'a11y: section name', 'no aria-labelledby' );
	if ( $el['name'] === 'image' && ! isset( $s['altText'] ) ) {
		$media_alt = isset( $s['image']['id'] ) ? get_post_meta( (int) $s['image']['id'], '_wp_attachment_image_alt', true ) : '';
		if ( $media_alt === '' && empty( $s['image']['useDynamicData'] ) ) $flag( $where, 'a11y: image alt', 'renders alt="" (no altText, no media-library alt)' );
	}
	if ( in_array( 'eyebrow', $cls_names, true ) && ( $attr['aria-hidden'] ?? '' ) !== 'true' ) $flag( $where, 'a11y: eyebrow', 'no aria-hidden="true"' );
	// BEM (`01`). Patterns are framework classes, not a block, so they don't count as the element's hook.
	$bem_names = array_filter( $cls_names, fn( $n ) => $n !== '' && ! preg_match( '/^(clickable-parent|focus-parent--)/', $n ) );
	if ( ! $bem_names ) $flag( $where, 'bem: no class', ( $el['label'] ?? '' ) . ( ! empty( $s['_cssClasses'] ) ? " raw: {$s['_cssClasses']}" : '' ) );
	if ( $el['name'] === 'section' && $bem_names && ! array_filter( $bem_names, fn( $n ) => strpos( $n, '__' ) === false ) ) $flag( $where, 'bem: no block class', implode( ' ', $bem_names ) );
	foreach ( $bem_names as $n ) if ( ! preg_match( '/^[a-z0-9]+(-[a-z0-9]+)*(__[a-z0-9]+(-[a-z0-9]+)*)?(--[a-z0-9]+(-[a-z0-9]+)*)?$/', $n ) ) $flag( $where, 'bem: name form', $n );

	$cls = array_map( fn( $c ) => '.' . ( $all_classes[ $c ]['name'] ?? "?$c" ), $s['_cssGlobalClasses'] ?? [] );
	$tag = isset( $s['tag'] ) ? ( $s['tag'] === 'custom' ? '<' . ( $s['customTag'] ?? '?' ) . '>' : "<{$s['tag']}>" ) : '';
	$other = array_diff( array_keys( $s ), [ '_cssGlobalClasses', 'tag', 'customTag' ] );
	$outline[] = str_repeat( '  ', $keep[ $el['id'] ] ) . trim( sprintf( '%s %s%s %s %s %s',
		$el['id'], $el['name'], $tag, isset( $el['label'] ) ? "\"{$el['label']}\"" : '', implode( ' ', $cls ), $other ? '{' . implode( ', ', $other ) . '}' : '' ) );
}

// ---- classes ------------------------------------------------------------------------------------------
$cls_keys = [];
foreach ( $used as $c ) {
	$s = $c['settings'] ?? [];
	foreach ( array_keys( $s ) as $k ) $cls_keys[ $k ] = true;
	$check_keys( ".{$c['name']}", $s );
	$check_values( ".{$c['name']}", $s );
}
foreach ( $dangling as $d ) $flag( $d, 'dangling class id' );

// ---- write --------------------------------------------------------------------------------------------
$dir  = getenv( 'OUTDIR' );
if ( ! $dir ) { fwrite( STDERR, "OUTDIR is required (a scratchpad or a gitignored folder)\n" ); exit( 2 ); }
wp_mkdir_p( $dir );
$slug = $post->post_name . ( $which !== 'content' ? "-$which" : '' ) . ( $roots ? '-' . sanitize_title( implode( '-', $roots ) ) : '' );
$json = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
file_put_contents( "$dir/$slug.tree.json", wp_json_encode( $scoped, $json ) . "\n" );
file_put_contents( "$dir/$slug.classes.json", wp_json_encode( array_values( $used ), $json ) . "\n" );

$fmt_keys = function ( $set ) use ( $known, $base_key ) {
	$out = [];
	foreach ( array_keys( $set ) as $k ) $out[] = $k . ( $known( $base_key( $k ) ) ? '' : ' (new)' );
	sort( $out );
	return implode( ', ', $out );
};

$lines   = [];
$lines[] = "# Schema readback — #{$post->ID} {$post->post_name} ($which)";
$lines[] = '';
$lines[] = sprintf( 'Bricks %s · %d elements%s · %d classes · %d flags · %s', BRICKS_VERSION, count( $scoped ),
	$roots ? ' (of ' . count( $tree ) . ')' : '', count( $used ), count( $flags ), gmdate( 'Y-m-d H:i' ) . ' UTC' );
$lines[] = '';
$lines[] = '## Element types';
$lines[] = '';
arsort( $names );
foreach ( $names as $n => $count ) $lines[] = "- `$n` ×$count — " . ( isset( $el_keys[ $n ] ) ? $fmt_keys( $el_keys[ $n ] ) : "(no settings)" );
$lines[] = '';
$lines[] = '## Class setting keys';
$lines[] = '';
$lines[] = $cls_keys ? $fmt_keys( $cls_keys ) : '(none)';
$lines[] = '';
$lines[] = '(new) = the key is not named in `02` or `docs/stack-gotchas.md`. A candidate for the schema library, not a fault.';
$lines[] = '';
$lines[] = '## Flags';
$lines[] = '';
$lines[] = $flags ? "```\n" . implode( "\n", $flags ) . "\n```" : '(none)';
$max = getenv( 'OUTLINE' ) === false ? 200 : (int) getenv( 'OUTLINE' );
if ( $max > 0 ) {
	$lines[] = '';
	$lines[] = '## Outline';
	$lines[] = '';
	$lines[] = "```\n" . implode( "\n", array_slice( $outline, 0, $max ) ) . ( count( $outline ) > $max ? "\n… " . ( count( $outline ) - $max ) . ' more' : '' ) . "\n```";
}
$summary = implode( "\n", $lines ) . "\n";
file_put_contents( "$dir/$slug.summary.md", $summary );

echo $summary;
echo "\nwrote $slug.{tree,classes}.json + $slug.summary.md\n";
