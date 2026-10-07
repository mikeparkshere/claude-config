<?php
/**
 * mpd-core — export the Style Manager to a JSON file in the site's repo: the committed record of what's live.
 *
 * Row shapes are Bricks' own, so the file can go back in through Bricks' importer, which accepts legacy
 * single-file `{variables, categories}`, `{colorPalette}` and `{themeStyles}` (unified-global-transfer.php:2856-2935).
 * No timestamp, so the file only diffs when tokens change. Custom fonts are recorded by title, weights and
 * file name, never attachment ID (IDs are per-install; `03`: imported templates carry another site's numeric IDs).
 *
 * Usage (from the WordPress root):  OUT=path/in/site/repo/style-manager-export.json wp eval-file path/to/export.php
 * OUT is required: the default used to be this script's own folder, which in claude-config is a public repo.
 */

$out = getenv( 'OUT' );
if ( ! $out ) { fwrite( STDERR, "OUT=<path to the export JSON in the site's repo> is required\n" ); exit( 2 ); }

$fonts = [];
foreach ( get_posts( [ 'post_type' => 'bricks_fonts', 'post_status' => 'any', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ] ) as $f ) {
	$faces = get_post_meta( $f->ID, 'bricks_font_faces', true ) ?: [];
	$files = [];
	foreach ( $faces as $variants ) foreach ( (array) $variants as $v ) foreach ( (array) $v as $fmt => $att ) if ( is_numeric( $att ) ) $files[ basename( get_attached_file( $att ) ) ] = true;
	$fonts[] = [ 'title' => $f->post_title, 'weights' => array_map( 'strval', array_keys( $faces ) ), 'files' => array_keys( $files ) ];
}

$ts = get_option( 'bricks_theme_styles', [] ) ?: [];

$export = [
	'_meta'        => [
		'framework' => 'mpd-core ' . ( defined( 'MPD_CORE_VERSION' ) ? MPD_CORE_VERSION : '(plugin inactive)' ),
		'spec'      => 'the site\'s TOKENS.md',
		'bricks'    => defined( 'BRICKS_VERSION' ) ? BRICKS_VERSION : null,
		'note'      => 'Record of the live Style Manager. Regenerate with export.php after any token change.',
	],
	'colorPalette' => get_option( 'bricks_color_palette', [] ),
	'categories'   => get_option( 'bricks_global_variables_categories', [] ),
	'variables'    => get_option( 'bricks_global_variables', [] ),
	'themeStyles'  => array_map( fn( $id, $s ) => [ 'id' => $id ] + $s, array_keys( $ts ), $ts ),
	'customFonts'  => $fonts,
];

file_put_contents( $out, wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
printf( "exported: %d palette colors, %d categories, %d variables, %d theme styles, %d fonts → %s\n",
	array_sum( array_map( fn( $p ) => count( $p['colors'] ?? [] ), $export['colorPalette'] ) ),
	count( $export['categories'] ), count( $export['variables'] ), count( $export['themeStyles'] ), count( $fonts ), basename( $out ) );
