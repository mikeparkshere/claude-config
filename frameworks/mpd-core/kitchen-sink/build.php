<?php
/**
 * mpd-core — the kitchen sink: one private page that shows every token and pattern on this install.
 *
 * One private page (slug mpd-core-kitchen-sink). Each section is a builder function; SECTIONS (env, comma list)
 * picks which ones are written, in page order. The whole tree is rebuilt on every run, with IDs derived from
 * names, so re-runs replace rather than duplicate. Token NAMES come from the values file, so the page always
 * matches the site's tokens; labels show token names, never resolved values, so nothing site-specific is baked
 * into the tree.
 *
 * Usage (from the WordPress root):
 *   VALUES=path/to/values.php [SECTIONS=defaults,colors,type,spacing,layout,patterns] wp eval-file path/to/kitchen-sink/build.php
 *
 * Needs, in order: the token scripts (apply-tokens.php, the CSS regen, apply-theme-style.php) and
 * setup/seed-pattern-classes.php (the patterns section looks those classes up by name).
 * ⚠️ bricks_global_classes is single-writer (`03`): close every builder tab first, re-read after ~90s.
 *
 * Schemas: `02` library only (element envelope, section/container/block/heading/text-basic, `_attributes`,
 * `_cssId`, typed `_typography` / `_background` / `_padding`, Global Class shape). Gotchas applied:
 *   wp_set_current_user(1) (00), `_bricks_editor_mode` = bricks (03), `children` derived from `parent` (03),
 *   layout + gap on the Container, not the Section (03), read-back in a separate process (03).
 * BEM: every element gets a named class, the block class goes on the Section, the Container gets
 * `block__container` (`01`). The one exception is `defaults`, which proves the Theme Style and so carries none.
 */

wp_set_current_user( 1 );

$file = getenv( 'VALUES' );
if ( ! $file || ! is_readable( $file ) ) { fwrite( STDERR, "VALUES=<path to values.php> is required and must be readable\n" ); exit( 2 ); }
$v = require $file;

$slug     = 'mpd-core-kitchen-sink';
$sections = array_filter( array_map( 'trim', explode( ',', getenv( 'SECTIONS' ) ?: 'defaults,colors,type,spacing,layout,patterns' ) ) );

// Stable 6-letter Bricks-style id (letters only, never all-numeric).
function ks_id( $name ) {
	$h = md5( 'mpd-core-ks:' . $name ); $id = '';
	for ( $i = 0; strlen( $id ) < 6; $i++ ) $id .= chr( 97 + hexdec( $h[ $i ] ) % 26 );
	return $id;
}

$tree = [];
function ks_el( &$tree, $key, $name, $parent, $settings = [], $label = '' ) {
	$el = [ 'id' => ks_id( $key ), 'name' => $name, 'parent' => $parent ? ks_id( $parent ) : 0, 'children' => [], 'settings' => $settings ];
	if ( $label ) $el['label'] = $label;
	$tree[] = $el;
	return $el['id'];
}

// ------------------------------------------------------------------ global classes
// Shape per `02` (Global Class): settings is always an array, never stdClass. Typed settings first;
// `_cssCustom` only where no typed control exists. List resets come from mpd-core's defaults.
$classes = [];
function ks_class( &$classes, $name, $settings ) {
	$classes[ $name ] = [ 'id' => ks_id( 'class:' . $name ), 'name' => $name, 'settings' => $settings, 'modified' => time() * 1000, 'user_id' => 1 ];
	return $classes[ $name ]['id'];
}
// A name-only BEM class: names the element, emits no CSS.
$bem = function ( $name ) use ( &$classes ) { return ks_class( $classes, $name, [] ); };

// Mono font from the values file, resolved like apply-theme-style.php: a custom font is custom_font_<id>,
// a generic family (ui-monospace …) is used as-is.
$mono_font = $v['fonts']['mono'];
if ( ! empty( $mono_font['custom'] ) ) {
	$fid = get_posts( [ 'post_type' => 'bricks_fonts', 'title' => $mono_font['custom'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] );
	if ( ! $fid ) { fwrite( STDERR, "custom font '{$mono_font['custom']}' is not registered (bricks_fonts)\n" ); exit( 1 ); }
	define( 'KS_MONO', 'custom_font_' . $fid[0] );
} else {
	define( 'KS_MONO', $mono_font['family'] );
}
define( 'KS_MONO_FALLBACK', $mono_font['fallback'] );
$raw = fn( $token ) => [ 'raw' => "var(--$token)" ];

// The opening of every classed section: Section (block class), Container (block__container), h2, intro.
$open = function ( &$tree, $key, $block, $title, $intro ) use ( $bem ) {
	ks_el( $tree, $key, 'section', null, [ '_cssGlobalClasses' => [ $bem( $block ) ],
		'_attributes' => [ [ 'id' => ks_id( "$key-aria" ), 'name' => 'aria-labelledby', 'value' => "$key-title" ] ] ], $title );
	ks_el( $tree, "$key-container", 'container', $key, [ '_cssGlobalClasses' => [ $bem( "{$block}__container" ) ] ] );
	ks_el( $tree, "$key-h2", 'heading', "$key-container", [ 'text' => $title, 'tag' => 'h2', '_cssId' => "$key-title", '_cssGlobalClasses' => [ $bem( "{$block}__title" ) ] ] );
	ks_el( $tree, "$key-intro", 'text-basic', "$key-container", [ 'tag' => 'p', 'text' => $intro, '_cssGlobalClasses' => [ $bem( "{$block}__intro" ) ] ] );
};

// ------------------------------------------------------------------ sections

/**
 * defaults — a section with NO classes (spec Stage 6.1), deliberately: everything it shows comes from the
 * Theme Style: section padding (--section-space-m / --gutter), container row-gap (--content-gap), the sans
 * family, the heading ladder, --color-text on --color-bg, link color.
 */
$build['defaults'] = function ( &$tree ) {
	ks_el( $tree, 'defaults', 'section', null, [
		'_attributes' => [ [ 'id' => ks_id( 'defaults-aria' ), 'name' => 'aria-labelledby', 'value' => 'ks-title' ] ],
	], 'Defaults only (no classes)' );
	ks_el( $tree, 'defaults-container', 'container', 'defaults' );
	ks_el( $tree, 'defaults-h1', 'heading', 'defaults-container', [ 'text' => 'mpd-core kitchen sink', 'tag' => 'h1', '_cssId' => 'ks-title' ] );
	ks_el( $tree, 'defaults-intro', 'text-basic', 'defaults-container', [
		'tag'  => 'p',
		'text' => 'This page proves the mpd-core tokens and patterns on this install.',
	] );
	ks_el( $tree, 'defaults-note', 'text-basic', 'defaults-container', [
		'tag'  => 'p',
		'text' => 'This section carries no classes. Its spacing, type and color come entirely from the mpd-core Theme Style and tokens. <a href="#ks-title">A sample link</a> shows the link color.',
	] );
};

/**
 * colors — purpose tokens first (what components use), then the palette (not for components). One class paints
 * every chip from `--swatch`, passed per chip as a style-attribute custom property (data, not styling; no
 * ID-selector CSS). Meta lines show what a purpose token points at, by name; palette chips show the name only.
 */
$build['colors'] = function ( &$tree ) use ( &$classes, $raw, $bem, $open, $v ) {
	$c_group = ks_class( $classes, 'ks-colors__group', [ '_rowGap' => 'var(--space-s)' ] );
	$c_list  = ks_class( $classes, 'ks-colors__list', [
		'_display' => 'grid',
		'_gridTemplateColumns' => 'var(--grid-4)',
		'_gridTemplateColumns:tablet_portrait' => 'var(--grid-3)',
		'_gridTemplateColumns:mobile_landscape' => 'var(--grid-2)',
		'_columnGap' => 'var(--space-m)', '_rowGap' => 'var(--space-l)',
	] );
	$c_item  = ks_class( $classes, 'ks-colors__item', [ '_rowGap' => 'var(--space-xs)' ] );
	$c_chip  = ks_class( $classes, 'ks-colors__chip', [
		'_width' => '100%', '_aspectRatio' => '3/2',
		'_background' => [ 'color' => $raw( 'swatch' ) ],
		'_border' => [ 'width' => [ 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1' ], 'style' => 'solid', 'color' => $raw( 'color-border' ), 'radius' => [ 'top' => 'var(--radius)', 'right' => 'var(--radius)', 'bottom' => 'var(--radius)', 'left' => 'var(--radius)' ] ],
	] );
	$c_token = ks_class( $classes, 'ks-colors__token', [ '_typography' => [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text' ) ] ] );
	$c_meta  = ks_class( $classes, 'ks-colors__meta', [ '_typography' => [ 'font-size' => 'var(--text-s)', 'color' => $raw( 'color-text-secondary' ) ] ] );
	$c_h3    = $bem( 'ks-colors__group-title' );

	$open( $tree, 'colors', 'ks-colors', 'Color',
		'Components use the purpose tokens (<code>--color-*</code>). The palette exists only for them to point at.' );

	// $rows: token => meta line ('' for none)
	$list = function ( $key, $title, $rows ) use ( &$tree, $c_group, $c_list, $c_item, $c_chip, $c_token, $c_meta, $c_h3 ) {
		ks_el( $tree, "colors-$key", 'block', 'colors-container', [ '_cssGlobalClasses' => [ $c_group ] ], $title );
		ks_el( $tree, "colors-$key-h3", 'heading', "colors-$key", [ 'text' => $title, 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
		ks_el( $tree, "colors-$key-list", 'block', "colors-$key", [ 'tag' => 'ul', '_cssGlobalClasses' => [ $c_list ],
			'_attributes' => [ [ 'id' => ks_id( "colors-$key-role" ), 'name' => 'role', 'value' => 'list' ] ] ] );
		foreach ( $rows as $token => $meta ) {
			$k = "colors-$key-$token";
			ks_el( $tree, $k, 'block', "colors-$key-list", [ 'tag' => 'li', '_cssGlobalClasses' => [ $c_item ] ] );
			ks_el( $tree, "$k-chip", 'div', $k, [ '_cssGlobalClasses' => [ $c_chip ], '_attributes' => [
				[ 'id' => ks_id( "$k-chip-style" ), 'name' => 'style', 'value' => "--swatch: var(--$token)" ],
				[ 'id' => ks_id( "$k-chip-aria" ), 'name' => 'aria-hidden', 'value' => 'true' ],
			] ] );
			ks_el( $tree, "$k-token", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$token", '_cssGlobalClasses' => [ $c_token ] ] );
			if ( $meta !== '' ) ks_el( $tree, "$k-meta", 'text-basic', $k, [ 'tag' => 'p', 'text' => $meta, '_cssGlobalClasses' => [ $c_meta ] ] );
		}
	};

	// Purpose: show the pointer only when the value is a single var(); anything else would be a literal value.
	$purpose = [];
	foreach ( $v['purpose'] ?? [] as $token => $value ) {
		$purpose[ $token ] = ( is_string( $value ) && preg_match( '/^var\(\s*(--[\w-]+)\s*\)$/', $value, $m ) ) ? "→ {$m[1]}" : '';
	}
	$palette = array_fill_keys( array_keys( $v['palette'] ?? [] ), '' );

	if ( $purpose ) $list( 'purpose', 'Purpose (what components use)', $purpose );
	if ( $palette ) $list( 'palette', 'Palette (not for components)', $palette );
};

/**
 * type — the size ladder (display, h1–h6, text-* from the values file's Typography category), reference data
 * in mono, and running text. Specimens are paragraphs, not headings (real h1s in a ladder would break the
 * outline); size and line height come in as data (`--sample-size`, `--sample-lh`), weight from a modifier.
 * The running-text sample uses real h4/h5 inside a rich-text element, so it exercises the Theme Style heading
 * rules and contextual spacing.
 */
$build['type'] = function ( &$tree ) use ( &$classes, $raw, $bem, $open, $v ) {
	$c_block  = ks_class( $classes, 'ks-type__block', [ '_rowGap' => 'var(--space-m)' ] );
	$c_ladder = ks_class( $classes, 'ks-type__ladder', [ '_rowGap' => 'var(--space-l)' ] );
	$c_row    = ks_class( $classes, 'ks-type__row', [
		'_display' => 'grid', '_gridTemplateColumns' => 'var(--grid-1-2)', '_gridTemplateColumns:mobile_landscape' => 'var(--grid-1)',
		'_columnGap' => 'var(--space-m)', '_rowGap' => 'var(--space-xs)',
	] );
	$c_label  = ks_class( $classes, 'ks-type__label', [ '_typography' => [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text-secondary' ) ] ] );
	$c_sample = ks_class( $classes, 'ks-type__sample', [ '_typography' => [ 'font-size' => 'var(--sample-size)', 'line-height' => 'var(--sample-lh)', 'color' => $raw( 'color-text' ) ] ] );
	$c_sample_h = ks_class( $classes, 'ks-type__sample--heading', [ '_typography' => [ 'font-weight' => (string) ( $v['theme_style']['heading_weight'] ?? '600' ) ] ] );
	$c_facts  = ks_class( $classes, 'ks-type__facts', [
		'_display' => 'grid', '_gridTemplateColumns' => 'var(--grid-4)', '_gridTemplateColumns:mobile_landscape' => 'var(--grid-2)',
		'_columnGap' => 'var(--space-m)', '_rowGap' => 'var(--space-m)',
	] );
	$c_fact   = ks_class( $classes, 'ks-type__fact', [ '_rowGap' => 'var(--space-xs)' ] );
	$c_dt     = ks_class( $classes, 'ks-type__fact-label', [ '_typography' => [ 'font-size' => 'var(--text-s)', 'color' => $raw( 'color-text-secondary' ) ] ] );
	$c_dd     = ks_class( $classes, 'ks-type__fact-value', [ '_margin' => [ 'left' => '0' ], '_typography' => [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text' ) ] ] );
	$c_prose  = ks_class( $classes, 'ks-type__prose', [ '_widthMax' => 'var(--reading-width)' ] );
	$c_h3     = $bem( 'ks-type__block-title' );

	// Size tokens only: display, h1–h6, text-*. font-* and line-height-* are skipped.
	$ladder = array_values( array_filter( array_keys( $v['variables']['Typography'] ?? [] ), fn( $t ) => preg_match( '/^(display|h[1-6]|text-[\w-]+)$/', $t ) ) );
	$sample = fn( $is_h ) => $is_h ? 'Section heading sample' : 'The quick brown fox jumps over the lazy dog.';

	$open( $tree, 'type', 'ks-type', 'Type',
		'The sans family handles headings and body. The mono family handles reference data: IDs, version numbers, dates. Each row shows the size token and the line-height token it pairs with.' );

	// Scale
	ks_el( $tree, 'type-scale', 'block', 'type-container', [ '_cssGlobalClasses' => [ $c_block ] ], 'Scale' );
	ks_el( $tree, 'type-scale-h3', 'heading', 'type-scale', [ 'text' => 'Scale', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
	ks_el( $tree, 'type-ladder', 'block', 'type-scale', [ 'tag' => 'ul', '_cssGlobalClasses' => [ $c_ladder ],
		'_attributes' => [ [ 'id' => ks_id( 'type-ladder-role' ), 'name' => 'role', 'value' => 'list' ] ] ] );
	foreach ( $ladder as $tok ) {
		$is_h = strpos( $tok, 'text-' ) !== 0;
		$lh   = $is_h ? 'line-height-heading' : 'line-height-body';
		$k    = "type-row-$tok";
		ks_el( $tree, $k, 'block', 'type-ladder', [ 'tag' => 'li', '_cssGlobalClasses' => [ $c_row ] ] );
		ks_el( $tree, "$k-label", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$tok · --$lh", '_cssGlobalClasses' => [ $c_label ] ] );
		ks_el( $tree, "$k-sample", 'text-basic', $k, [ 'tag' => 'p', 'text' => $sample( $is_h ),
			'_cssGlobalClasses' => $is_h ? [ $c_sample, $c_sample_h ] : [ $c_sample ],
			'_attributes' => [ [ 'id' => ks_id( "$k-style" ), 'name' => 'style', 'value' => "--sample-size: var(--$tok); --sample-lh: var(--$lh)" ] ] ] );
	}

	// Reference data
	ks_el( $tree, 'type-ref', 'block', 'type-container', [ '_cssGlobalClasses' => [ $c_block ] ], 'Reference data' );
	ks_el( $tree, 'type-ref-h3', 'heading', 'type-ref', [ 'text' => 'Reference data', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
	ks_el( $tree, 'type-facts', 'block', 'type-ref', [ 'tag' => 'custom', 'customTag' => 'dl', '_cssGlobalClasses' => [ $c_facts ] ] );
	foreach ( [ 'ID' => 'REF-2026-0001', 'Version' => 'v1.0', 'Date' => '2026-01-01', 'Ticket' => 'TKT-000123' ] as $dt => $dd ) {
		$k = 'type-fact-' . sanitize_title( $dt );
		ks_el( $tree, $k, 'block', 'type-facts', [ '_cssGlobalClasses' => [ $c_fact ] ] );
		ks_el( $tree, "$k-dt", 'text-basic', $k, [ 'tag' => 'custom', 'customTag' => 'dt', 'text' => $dt, '_cssGlobalClasses' => [ $c_dt ] ] );
		ks_el( $tree, "$k-dd", 'text-basic', $k, [ 'tag' => 'custom', 'customTag' => 'dd', 'text' => $dd, '_cssGlobalClasses' => [ $c_dd ] ] );
	}

	// Running text
	ks_el( $tree, 'type-prose-block', 'block', 'type-container', [ '_cssGlobalClasses' => [ $c_block ] ], 'Running text' );
	ks_el( $tree, 'type-prose-h3', 'heading', 'type-prose-block', [ 'text' => 'Running text', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
	ks_el( $tree, 'type-prose', 'text', 'type-prose-block', [ '_cssGlobalClasses' => [ $c_prose ], 'text' =>
		'<h4>A fourth-level heading</h4>'
		. '<p>Running text sits at the body size and line height, capped at the reading width. Paragraphs in rich text get flow spacing; everywhere else, margins are zero.</p>'
		. '<p>Inline code such as <code>REF-2026-0001</code> uses the mono family. <a href="#type-title">A link inside running text</a> uses the link color.</p>'
		. '<ul><li>A list item</li><li>A second list item</li><li>A third list item</li></ul>'
		. '<h5>A fifth-level heading</h5>'
		. '<p>A closing paragraph, to show the spacing after a smaller heading.</p>' ] );
};

/**
 * spacing — every Spacing token as a bar exactly as wide as the token (`--bar`), grouped by prefix.
 */
$build['spacing'] = function ( &$tree ) use ( &$classes, $raw, $bem, $open, $v ) {
	$c_group = ks_class( $classes, 'ks-space__group', [ '_rowGap' => 'var(--space-s)' ] );
	$c_list  = ks_class( $classes, 'ks-space__list', [ '_rowGap' => 'var(--space-s)' ] );
	$c_row   = ks_class( $classes, 'ks-space__row', [
		'_display' => 'grid', '_gridTemplateColumns' => 'var(--grid-1-2)', '_gridTemplateColumns:mobile_landscape' => 'var(--grid-1)',
		'_columnGap' => 'var(--space-m)', '_rowGap' => 'var(--space-xs)', '_alignItemsGrid' => 'center',
	] );
	$c_label = ks_class( $classes, 'ks-space__label', [ '_typography' => [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text-secondary' ) ] ] );
	$c_bar   = ks_class( $classes, 'ks-space__bar', [ '_width' => 'var(--bar)', '_height' => 'var(--space-xs)', '_background' => [ 'color' => $raw( 'color-accent' ) ] ] );
	$c_h3    = $bem( 'ks-space__group-title' );

	$groups = [ 'Space' => [], 'Section spacing' => [], 'Gaps and other spacing' => [] ];
	foreach ( array_keys( $v['variables']['Spacing'] ?? [] ) as $tok ) {
		if ( strpos( $tok, 'section-space-' ) === 0 ) $groups['Section spacing'][] = $tok;
		elseif ( strpos( $tok, 'space-' ) === 0 ) $groups['Space'][] = $tok;
		else $groups['Gaps and other spacing'][] = $tok;
	}

	$open( $tree, 'spacing', 'ks-space', 'Spacing',
		'<code>--space-*</code> spaces things inside a component; <code>--section-space-*</code> spaces sections. Each bar below is exactly as wide as its token.' );
	foreach ( array_filter( $groups ) as $title => $rows ) {
		$g = 'spacing-' . sanitize_title( $title );
		ks_el( $tree, $g, 'block', 'spacing-container', [ '_cssGlobalClasses' => [ $c_group ] ], $title );
		ks_el( $tree, "$g-h3", 'heading', $g, [ 'text' => $title, 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
		ks_el( $tree, "$g-list", 'block', $g, [ 'tag' => 'ul', '_cssGlobalClasses' => [ $c_list ], '_attributes' => [ [ 'id' => ks_id( "$g-role" ), 'name' => 'role', 'value' => 'list' ] ] ] );
		foreach ( $rows as $tok ) {
			$k = "$g-$tok";
			ks_el( $tree, $k, 'block', "$g-list", [ 'tag' => 'li', '_cssGlobalClasses' => [ $c_row ] ] );
			ks_el( $tree, "$k-label", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$tok", '_cssGlobalClasses' => [ $c_label ] ] );
			ks_el( $tree, "$k-bar", 'div', $k, [ '_cssGlobalClasses' => [ $c_bar ], '_attributes' => [
				[ 'id' => ks_id( "$k-style" ), 'name' => 'style', 'value' => "--bar: var(--$tok)" ],
				[ 'id' => ks_id( "$k-aria" ), 'name' => 'aria-hidden', 'value' => 'true' ],
			] ] );
		}
	}
};

/**
 * layout — Grid tokens as live grids (`--g`), Layout width tokens as bars (`--bar`, capped by the container like
 * real content), and every Radius token on a tile (`--r`).
 */
$build['layout'] = function ( &$tree ) use ( &$classes, $raw, $bem, $open, $v ) {
	$all = fn( $x ) => [ 'top' => $x, 'right' => $x, 'bottom' => $x, 'left' => $x ];
	$mono = [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text-secondary' ) ];

	$c_group  = ks_class( $classes, 'ks-layout__group', [ '_rowGap' => 'var(--space-m)' ] );
	$c_sample = ks_class( $classes, 'ks-layout__sample', [ '_rowGap' => 'var(--space-xs)' ] );
	$c_label  = ks_class( $classes, 'ks-layout__label', [ '_typography' => $mono ] );
	// Width set explicitly: an empty grid inside a flex-start block column shrinks to its borders (03: block base display).
	$c_grid   = ks_class( $classes, 'ks-layout__grid', [ '_width' => '100%', '_display' => 'grid', '_gridTemplateColumns' => 'var(--g)', '_columnGap' => 'var(--grid-gap)', '_rowGap' => 'var(--grid-gap)' ] );
	$c_cell   = ks_class( $classes, 'ks-layout__cell', [ '_height' => 'var(--space-xl)', '_background' => [ 'color' => $raw( 'color-surface-raised' ) ], '_border' => [ 'width' => $all( '1' ), 'style' => 'solid', 'color' => $raw( 'color-border' ), 'radius' => $all( 'var(--radius)' ) ] ] );
	$c_bar    = ks_class( $classes, 'ks-layout__bar', [ '_width' => 'var(--bar)', '_height' => 'var(--space-xs)', '_background' => [ 'color' => $raw( 'color-accent' ) ] ] );
	// Wrapping row: sets BOTH gaps (a row-gap default would otherwise fill the unset axis; Stage 5 rule).
	$c_shapes = ks_class( $classes, 'ks-layout__shapes', [ '_direction' => 'row', '_flexWrap' => 'wrap', '_alignItems' => 'flex-start', '_columnGap' => 'var(--space-l)', '_rowGap' => 'var(--space-m)' ] );
	// A Bricks block is width:100% by default, so each item would wrap onto its own line: size the item to its tile.
	$c_shape  = ks_class( $classes, 'ks-layout__shape', [ '_width' => 'auto' ] );
	$c_tile   = ks_class( $classes, 'ks-layout__tile', [ '_width' => 'var(--space-xxl)', '_aspectRatio' => '1', '_background' => [ 'color' => $raw( 'color-accent-tint' ) ], '_border' => [ 'width' => $all( '1' ), 'style' => 'solid', 'color' => $raw( 'color-accent' ), 'radius' => $all( 'var(--r)' ) ] ] );
	$c_h3     = $bem( 'ks-layout__group-title' );
	$c_note   = $bem( 'ks-layout__note' );

	$open( $tree, 'layout', 'ks-layout', 'Layout',
		'Grid templates, content widths, and radius. Grids gap at <code>--grid-gap</code>; widths are capped by their container, so on a narrow screen the wider ones meet the edge.' );

	// Cells per grid: repeat(N, …) gives N; otherwise count top-level tracks; auto-fit/fill repeats get 4.
	$tracks = function ( $val ) {
		if ( ! is_string( $val ) ) return 1;
		$val = trim( $val );
		if ( preg_match( '/^repeat\(\s*(\d+)\s*,/', $val, $m ) ) return max( 1, (int) $m[1] );
		if ( strpos( $val, 'repeat(' ) === 0 ) return 4;
		$depth = 0; $n = 0; $in = false;
		foreach ( str_split( $val ) as $ch ) {
			if ( $ch === '(' ) $depth++;
			elseif ( $ch === ')' ) $depth--;
			if ( $depth === 0 && ctype_space( $ch ) ) { $in = false; continue; }
			if ( ! $in ) { $n++; $in = true; }
		}
		return max( 1, $n );
	};

	// Grids
	$grids = $v['variables']['Grid'] ?? [];
	if ( $grids ) {
		ks_el( $tree, 'layout-grids', 'block', 'layout-container', [ '_cssGlobalClasses' => [ $c_group ] ], 'Grids' );
		ks_el( $tree, 'layout-grids-h3', 'heading', 'layout-grids', [ 'text' => 'Grid templates', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
		foreach ( $grids as $tok => $val ) {
			$k = "layout-$tok";
			ks_el( $tree, $k, 'block', 'layout-grids', [ '_cssGlobalClasses' => [ $c_sample ] ] );
			ks_el( $tree, "$k-label", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$tok", '_cssGlobalClasses' => [ $c_label ] ] );
			ks_el( $tree, "$k-grid", 'div', $k, [ '_cssGlobalClasses' => [ $c_grid ], '_attributes' => [
				[ 'id' => ks_id( "$k-style" ), 'name' => 'style', 'value' => "--g: var(--$tok)" ], [ 'id' => ks_id( "$k-aria" ), 'name' => 'aria-hidden', 'value' => 'true' ] ] ] );
			for ( $i = 1, $n = $tracks( $val ); $i <= $n; $i++ ) ks_el( $tree, "$k-cell-$i", 'div', "$k-grid", [ '_cssGlobalClasses' => [ $c_cell ] ] );
		}
	}

	// Widths: every Layout token with "width" in its name.
	$widths = array_values( array_filter( array_keys( $v['variables']['Layout'] ?? [] ), fn( $t ) => strpos( $t, 'width' ) !== false ) );
	if ( $widths ) {
		ks_el( $tree, 'layout-widths', 'block', 'layout-container', [ '_cssGlobalClasses' => [ $c_group ] ], 'Widths' );
		ks_el( $tree, 'layout-widths-h3', 'heading', 'layout-widths', [ 'text' => 'Widths', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
		foreach ( $widths as $tok ) {
			$k = "layout-$tok";
			ks_el( $tree, $k, 'block', 'layout-widths', [ '_cssGlobalClasses' => [ $c_sample ] ] );
			ks_el( $tree, "$k-label", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$tok", '_cssGlobalClasses' => [ $c_label ] ] );
			ks_el( $tree, "$k-bar", 'div', $k, [ '_cssGlobalClasses' => [ $c_bar ], '_attributes' => [
				[ 'id' => ks_id( "$k-style" ), 'name' => 'style', 'value' => "--bar: var(--$tok)" ], [ 'id' => ks_id( "$k-aria" ), 'name' => 'aria-hidden', 'value' => 'true' ] ] ] );
		}
	}

	// Radius: one tile per Radius token.
	$radii = array_keys( $v['variables']['Radius'] ?? [] );
	if ( $radii ) {
		ks_el( $tree, 'layout-shapes', 'block', 'layout-container', [ '_cssGlobalClasses' => [ $c_group ] ], 'Radius' );
		ks_el( $tree, 'layout-shapes-h3', 'heading', 'layout-shapes', [ 'text' => 'Radius', 'tag' => 'h3', '_cssGlobalClasses' => [ $c_h3 ] ] );
		ks_el( $tree, 'layout-shapes-note', 'text-basic', 'layout-shapes', [ 'tag' => 'p', '_cssGlobalClasses' => [ $c_note ],
			'text' => 'Every step of the radius scale. Components use <code>--radius</code>; status badges use <code>--radius-pill</code>.' ] );
		ks_el( $tree, 'layout-shapes-row', 'block', 'layout-shapes', [ 'tag' => 'ul', '_cssGlobalClasses' => [ $c_shapes ],
			'_attributes' => [ [ 'id' => ks_id( 'layout-shapes-role' ), 'name' => 'role', 'value' => 'list' ] ] ] );
		foreach ( $radii as $tok ) {
			$k = "layout-shape-$tok";
			ks_el( $tree, $k, 'block', 'layout-shapes-row', [ 'tag' => 'li', '_cssGlobalClasses' => [ $c_shape ] ] );
			ks_el( $tree, "$k-tile", 'div', $k, [ '_cssGlobalClasses' => [ $c_tile ], '_attributes' => [
				[ 'id' => ks_id( "$k-style" ), 'name' => 'style', 'value' => "--r: var(--$tok)" ], [ 'id' => ks_id( "$k-aria" ), 'name' => 'aria-hidden', 'value' => 'true' ] ] ] );
			ks_el( $tree, "$k-label", 'text-basic', $k, [ 'tag' => 'p', 'text' => "--$tok", '_cssGlobalClasses' => [ $c_label ] ] );
		}
	}
};

/**
 * patterns — the mpd-core patterns on cards. Links point at #hash targets so a real click proves which link it
 * hit. Card 1 has a secondary link (must stay independently clickable); cards 2–3 have only the title link
 * (the focus-parent use case, per mpd-core.css). Pattern classes are the name-only globals from
 * setup/seed-pattern-classes.php, looked up by name and added after the card's BEM class.
 */
$build['patterns'] = function ( &$tree ) use ( &$classes, $raw, $bem, $open ) {
	$all = fn( $x ) => [ 'top' => $x, 'right' => $x, 'bottom' => $x, 'left' => $x ];

	$by_name = array_column( get_option( 'bricks_global_classes', [] ) ?: [], 'id', 'name' );
	$pat = [];
	foreach ( [ 'clickable-parent', 'focus-parent--shadow', 'focus-parent--outline' ] as $name ) {
		if ( empty( $by_name[ $name ] ) ) { fwrite( STDERR, "missing global class '$name': run setup/seed-pattern-classes.php first\n" ); exit( 1 ); }
		$pat[ $name ] = $by_name[ $name ];
	}

	$c_cards  = ks_class( $classes, 'ks-patterns__cards', [
		'_display' => 'grid', '_gridTemplateColumns' => 'var(--grid-3)', '_gridTemplateColumns:tablet_portrait' => 'var(--grid-1)',
		'_columnGap' => 'var(--grid-gap)', '_rowGap' => 'var(--grid-gap)',
	] );
	$c_card   = ks_class( $classes, 'ks-card', [
		'_padding' => $all( 'var(--space-l)' ), '_rowGap' => 'var(--space-s)',
		'_background' => [ 'color' => $raw( 'color-surface' ) ],
		'_border' => [ 'width' => $all( '1' ), 'style' => 'solid', 'color' => $raw( 'color-border' ), 'radius' => $all( 'var(--radius)' ) ],
		'_border:hover' => [ 'color' => $raw( 'color-accent' ) ],
	] );
	$c_status = ks_class( $classes, 'ks-card__status', [
		'_padding' => [ 'top' => 'calc(var(--space-xs) / 2)', 'bottom' => 'calc(var(--space-xs) / 2)', 'left' => 'var(--space-s)', 'right' => 'var(--space-s)' ],
		'_border' => [ 'radius' => $all( 'var(--radius-pill)' ) ],
		'_background' => [ 'color' => $raw( 'status-tint' ) ],
		'_typography' => [ 'font-size' => 'var(--text-s)', 'font-weight' => '500', 'color' => $raw( 'status' ) ],
	] );
	$c_title  = ks_class( $classes, 'ks-card__title', [ '_typography' => [ 'color' => $raw( 'color-text' ) ] ] );
	$c_meta   = ks_class( $classes, 'ks-card__meta', [ '_typography' => [ 'font-family' => KS_MONO, 'fallback' => KS_MONO_FALLBACK, 'font-size' => 'var(--text-xs)', 'color' => $raw( 'color-text-secondary' ) ] ] );
	$c_desc   = ks_class( $classes, 'ks-card__desc', [ '_typography' => [ 'font-size' => 'var(--text-s)', 'color' => $raw( 'color-text-secondary' ) ] ] );

	// [key, pattern class names, status, status token, title, href, meta line, description, secondary link?]
	$cards = [
		[ 'clickable', [ 'clickable-parent' ], 'Success', 'success', 'Clickable card', '#card-clickable', 'REF-0001 · v1.0',
			'The whole card follows the title link.', 'Secondary: <a href="#card-secondary">its own link</a>' ],
		[ 'shadow', [ 'clickable-parent', 'focus-parent--shadow' ], 'Warning', 'warning', 'Focus ring as shadow', '#card-shadow', 'REF-0002 · v1.0',
			'Keyboard focus draws a shadow ring on the card.', '' ],
		[ 'outline', [ 'clickable-parent', 'focus-parent--outline' ], 'Neutral', 'neutral', 'Focus ring as outline', '#card-outline', 'REF-0003 · v1.0',
			'Keyboard focus draws an outline on the card.', '' ],
	];

	$open( $tree, 'patterns', 'ks-patterns', 'Patterns',
		'mpd-core ships two patterns. <code>.clickable-parent</code> makes a whole card clickable through the link in its heading, while other links inside stay clickable on their own. <code>.focus-parent--shadow</code> and <code>.focus-parent--outline</code> move the keyboard focus ring from the link to the card. Tab through the cards, then click them.' );
	ks_el( $tree, 'patterns-cards', 'block', 'patterns-container', [ 'tag' => 'ul', '_cssGlobalClasses' => [ $c_cards ],
		'_attributes' => [ [ 'id' => ks_id( 'patterns-cards-role' ), 'name' => 'role', 'value' => 'list' ] ] ] );
	foreach ( $cards as [ $key, $names, $status, $stok, $title, $href, $meta, $desc, $secondary ] ) {
		$k = "patterns-$key";
		ks_el( $tree, $k, 'block', 'patterns-cards', [ 'tag' => 'li',
			'_cssGlobalClasses' => array_merge( [ $c_card ], array_map( fn( $n ) => $pat[ $n ], $names ) ) ], implode( ' ', $names ) );
		ks_el( $tree, "$k-status", 'text-basic', $k, [ 'tag' => 'p', 'text' => $status, '_cssGlobalClasses' => [ $c_status ],
			'_attributes' => [ [ 'id' => ks_id( "$k-status-style" ), 'name' => 'style', 'value' => "--status: var(--color-$stok); --status-tint: var(--color-$stok-tint)" ] ] ] );
		ks_el( $tree, "$k-title", 'heading', $k, [ 'tag' => 'h3', 'text' => $title, 'link' => [ 'type' => 'external', 'url' => $href ], '_cssGlobalClasses' => [ $c_title ] ] );
		ks_el( $tree, "$k-meta", 'text-basic', $k, [ 'tag' => 'p', 'text' => $meta, '_cssGlobalClasses' => [ $c_meta ] ] );
		ks_el( $tree, "$k-desc", 'text-basic', $k, [ 'tag' => 'p', 'text' => $desc, '_cssGlobalClasses' => [ $c_desc ] ] );
		if ( $secondary ) ks_el( $tree, "$k-secondary", 'text-basic', $k, [ 'tag' => 'p', 'text' => $secondary, '_cssGlobalClasses' => [ $c_desc ] ] );
	}
};

// ------------------------------------------------------------------ assemble

foreach ( $sections as $s ) {
	if ( empty( $build[ $s ] ) ) { fwrite( STDERR, "unknown section: $s\n" ); exit( 1 ); }
	$build[ $s ]( $tree );
}

// children from parent, in document order (03: parent alone renders empty shells)
$index = [];
foreach ( $tree as $i => $el ) $index[ $el['id'] ] = $i;
foreach ( $tree as $el ) if ( $el['parent'] ) $tree[ $index[ $el['parent'] ] ]['children'][] = $el['id'];

$ids = array_column( $tree, 'id' );
if ( count( $ids ) !== count( array_unique( $ids ) ) ) { fwrite( STDERR, "ID collision\n" ); exit( 1 ); }

// ------------------------------------------------------------------ page + write

$page = get_page_by_path( $slug, OBJECT, 'page' );
$pid  = $page ? $page->ID : wp_insert_post( [
	'post_type' => 'page', 'post_name' => $slug, 'post_status' => 'private',
	'post_title' => 'mpd-core kitchen sink',
], true );
if ( is_wp_error( $pid ) ) { fwrite( STDERR, $pid->get_error_message() . "\n" ); exit( 1 ); }

// global classes: replace ours by id, keep everyone else's (not gated like page meta)
if ( $classes ) {
	$existing = get_option( 'bricks_global_classes', [] ) ?: [];
	$ours     = array_column( $classes, 'id' );
	$existing = array_values( array_filter( $existing, fn( $c ) => ! in_array( $c['id'] ?? '', $ours, true ) ) );
	update_option( 'bricks_global_classes', array_merge( $existing, array_values( $classes ) ) );
}
update_post_meta( $pid, '_bricks_editor_mode', 'bricks' );
update_post_meta( $pid, '_bricks_page_content_2', $tree );

\Bricks\Assets_Files::regenerate_css_files();
printf( "page #%d (%s): %d elements, %d classes, across %d section(s): %s\n", $pid, get_post_status( $pid ), count( $tree ), count( $classes ), count( $sections ), implode( ', ', $sections ) );
