<?php
/**
 * mpd-core — the site-wide Bricks Theme Style: framework defaults as typed settings.
 *
 * Usage (from the WordPress root):  VALUES=path/to/values.php wp eval-file path/to/apply-theme-style.php
 *
 * Typed-settings-first: everything Theme Style can express lives here, not in plugin CSS. Group/control keys
 * from includes/theme-styles/controls/*.php (Bricks 2.4.2) and `02` → "Theme Style keys". Values consume tokens;
 * the only literals are weights and letter-spacing, which come from the values file's theme_style block.
 *
 * Idempotent: one style under a fixed key, replaced on re-run. Run apply-tokens.php first.
 */

wp_set_current_user( 1 );
$file = getenv( 'VALUES' );
if ( ! $file || ! is_readable( $file ) ) { fwrite( STDERR, "VALUES=<path to values.php> is required and must be readable\n" ); exit( 2 ); }
$v  = require $file;
$ts = $v['theme_style'];

// A custom font resolves to custom_font_<id> (Bricks quotes any other named family, `03`); a generic family
// (system-ui, ui-monospace, sans-serif …) is emitted unquoted. The fallback is appended verbatim after it.
$font = function ( $f ) {
	if ( ! empty( $f['custom'] ) ) {
		$id = get_posts( [ 'post_type' => 'bricks_fonts', 'title' => $f['custom'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] );
		if ( ! $id ) { fwrite( STDERR, "custom font '{$f['custom']}' is not registered (bricks_fonts)\n" ); exit( 1 ); }
		return [ 'font-family' => 'custom_font_' . $id[0], 'fallback' => $f['fallback'] ];
	}
	return [ 'font-family' => $f['family'], 'fallback' => $f['fallback'] ];
};
$sans = $font( $v['fonts']['sans'] );

$color = fn( $token ) => [ 'raw' => "var(--$token)" ];
$headings = [];
foreach ( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] as $h ) {
	$headings[ 'typographyHeading' . strtoupper( $h ) ] = array_filter( [
		'font-size'   => "var(--$h)",
		'line-height' => $ts['heading_line_heights'][ $h ] ?? null,   // null: inherits --line-height-heading
	] );
}

$key   = 'mpdcor';
$style = [
	'label'    => 'mpd-core',
	'settings' => [
		'conditions' => [ 'conditions' => [ [ 'id' => 'mpdany', 'main' => 'any' ] ] ],
		'typography' => [
			'typographyHtml'     => 'var(--root-font-size)',
			'typographyBody'     => array_filter( $sans + [
				'font-size'      => 'var(--text-m)',
				'font-weight'    => $ts['body_weight'],
				'line-height'    => 'var(--line-height-body)',
				'letter-spacing' => $ts['body_letter_spacing'],
				'color'          => $color( 'color-text' ),
			] ),
			'typographyHeadings' => $sans + [ 'font-weight' => $ts['heading_weight'], 'line-height' => 'var(--line-height-heading)', 'color' => $color( 'color-text' ) ],
			'focusOutline'       => 'var(--focus-width) solid var(--focus-color)',
		] + $headings,
		'heading'    => [ 'tag' => 'h2' ],   // unset = h3 (`02`)
		'general'    => [
			'containerMaxWidth' => 'var(--content-width)',   // root containers only, so the container group below too (`03`)
			'siteBackground'    => [ 'color' => $color( 'color-bg' ) ],
		],
		'links'      => [
			'typography'       => [ 'color' => $color( 'color-link' ) ],
			'typography:hover' => [ 'color' => $color( 'color-link-hover' ) ],
		],
		'section'    => [
			'padding' => [ 'top' => 'var(--section-space-m)', 'right' => 'var(--gutter)', 'bottom' => 'var(--section-space-m)', 'left' => 'var(--gutter)' ],
		],
		// Row gap ONLY, never both axes: a both-axes default silently fills whichever axis a class leaves unset.
		// Blocks/containers are flex columns by default, so row-gap is the stacking axis. Rule that follows:
		// any wrapping row layout sets its own row-gap.
		'container'  => [ '_rowGap' => 'var(--content-gap)', 'width' => 'var(--content-width)' ],
		'block'      => [ '_rowGap' => 'var(--content-gap)' ],
		// Bricks' base layer gives :where(p) a 1.2em bottom margin, which stacks with the row-gap. Remove default
		// margins; restore flow spacing only inside rich text via contextual spacing. NOT contextualSpacingFallback:
		// its selector is (0,2,0) and silently beats the heading rule (`03`). Other flow elements are custom targets.
		'contextualSpacing' => [
			'contextualSpacingRemoveDefaultMargins' => [ 'h1,h2,h3,h4,h5,h6', 'p', 'ul', 'ol', 'figure', 'blockquote' ],
			'contextualSpacingHeading'   => 'var(--space-l)',
			'contextualSpacingParagraph' => 'var(--space-s)',
			'contextualSpacingCustomTarget' => array_map( fn( $tag ) => [ 'id' => 'mpd' . substr( md5( $tag ), 0, 3 ), 'selector' => $tag, 'marginStart' => 'var(--space-s)' ], [ 'ul', 'ol', 'figure', 'blockquote', 'form' ] ),
		],
		'button'     => [
			'primaryBackground'       => $color( 'color-accent' ),
			'primaryBackground:hover' => $color( 'color-accent-hover' ),
			'primaryTypography'       => [ 'color' => $color( 'color-text-on-accent' ), 'font-weight' => $ts['button_weight'] ],
		],
	],
];

$all = get_option( 'bricks_theme_styles', [] ) ?: [];
$all[ $key ] = $style;
update_option( 'bricks_theme_styles', $all );
echo "theme style '$key' written (" . count( $all ) . " total); body font {$sans['font-family']}\n";
