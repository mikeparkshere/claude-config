<?php
/**
 * mpd-core — write a site's token values into the Bricks Style Manager.
 *
 * Usage (from the WordPress root):
 *   VALUES=path/to/values.php wp eval-file path/to/apply-tokens.php            (DRY=1 to preview)
 *   VALUES is required; start from values.example.php beside this file.
 *
 * Row shapes are builder-verified (Bricks 2.4.2; `02` → "Bricks Style Manager — Color Manager and Variable
 * Manager rows"):
 *   color row     { id, raw: "var(--name)", light: "<value>" }   (no name key; var() accepted as light)
 *   variable row  { id, name (no leading --), value, category }
 *   category row  { id, name }
 *
 * Tiers: purpose colors go in the Color Manager, so the color picker offers what components should use;
 * the palette (labeled not-for-components) and every non-color token go in the Variable Manager.
 *
 * Idempotent: IDs derive from the token name, so a re-run replaces rather than duplicates. Replaces the whole
 * palette option (which also keeps Bricks from persisting its Default palette, `03`) and the whole variables +
 * categories options. Refreshes style-manager.min.css from the fresh options; run the CSS regen in a
 * SEPARATE request afterwards (`03`: a same-request regen writes the pre-update palette).
 */

wp_set_current_user( 1 );
$dry  = getenv( 'DRY' ) === '1';
$file = getenv( 'VALUES' );
if ( ! $file || ! is_readable( $file ) ) { fwrite( STDERR, "VALUES=<path to values.php> is required and must be readable\n" ); exit( 2 ); }
$v = require $file;

// Stable 6-letter Bricks-style id from a name (letters only: never all-numeric).
$bid = function ( $name ) {
	$h = md5( 'mpd-core:' . $name ); $id = '';
	for ( $i = 0; strlen( $id ) < 6; $i++ ) { $id .= chr( 97 + hexdec( $h[ $i ] ) % 26 ); }
	return $id;
};

// [ 'fluid', min_px, max_px ] → clamp() over meta.fluid_min..fluid_max at a 16px root.
$rem   = fn( $px ) => rtrim( rtrim( sprintf( '%.4f', $px / 16 ), '0' ), '.' ) . 'rem';
$vmin  = (float) $v['meta']['fluid_min']; $vmax = (float) $v['meta']['fluid_max'];
$value = function ( $x ) use ( $rem, $vmin, $vmax ) {
	if ( ! is_array( $x ) ) return (string) $x;
	[ $kind, $min, $max ] = $x;
	if ( $kind !== 'fluid' ) { fwrite( STDERR, "unknown value form: $kind\n" ); exit( 1 ); }
	if ( $min == $max ) return $rem( $min );
	$slope = ( $max - $min ) / ( $vmax - $vmin );
	$b     = ( $min - $slope * $vmin ) / 16;
	return sprintf( 'clamp(%s, calc(%.4fvw + %.4frem), %s)', $rem( min( $min, $max ) ), $slope * 100, $b, $rem( max( $min, $max ) ) );
};

$groups = [ 'Palette (not for components)' => $v['palette'] ] + $v['variables'];

// ---------------------------------------------------------------- build rows
$categories = []; $variables = []; $names = [];
foreach ( $groups as $cat => $vars ) {
	$cid = $bid( 'cat:' . $cat );
	$categories[] = [ 'id' => $cid, 'name' => $cat ];
	foreach ( $vars as $name => $val ) {
		if ( isset( $names[ $name ] ) ) { fwrite( STDERR, "DUPLICATE: $name\n" ); exit( 1 ); }
		$variables[] = [ 'id' => $bid( 'var:' . $name ), 'name' => $name, 'value' => $value( $val ), 'category' => $cid ];
		$names[ $name ] = 'variable';
	}
}
$colors = [];
foreach ( $v['purpose'] as $name => $val ) {
	if ( isset( $names[ $name ] ) ) { fwrite( STDERR, "COLLISION: $name in both stores\n" ); exit( 1 ); }
	$colors[] = [ 'id' => $bid( 'color:' . $name ), 'raw' => "var(--$name)", 'light' => $value( $val ) ];
	$names[ $name ] = 'color';
}
$palette_opt = [ [ 'id' => $bid( 'palette:mpd-core' ), 'name' => 'mpd-core', 'colors' => $colors ] ];

// Guard: every var() reference must resolve to a token defined here (Bricks doesn't validate them, `03`).
$missing = [];
foreach ( array_merge( array_column( $variables, 'value' ), array_column( $colors, 'light' ) ) as $val ) {
	if ( preg_match_all( '/var\(--([a-z0-9-]+)\)/', $val, $m ) ) foreach ( $m[1] as $ref ) if ( ! isset( $names[ $ref ] ) ) $missing[ $ref ] = true;
}
if ( $missing ) { fwrite( STDERR, 'UNRESOLVED var() refs: ' . implode( ', ', array_keys( $missing ) ) . "\n" ); exit( 1 ); }
$ids = array_merge( array_column( $variables, 'id' ), array_column( $colors, 'id' ), array_column( $categories, 'id' ) );
if ( count( $ids ) !== count( array_unique( $ids ) ) ) { fwrite( STDERR, "ID COLLISION\n" ); exit( 1 ); }

printf( "tokens (%s): %d variables in %d categories + %d purpose colors = %d\n", $v['meta']['site'], count( $variables ), count( $categories ), count( $colors ), count( $variables ) + count( $colors ) );
if ( $dry ) {
	foreach ( $variables as $row ) if ( strpos( $row['value'], 'clamp(' ) === 0 && in_array( $row['name'], [ 'h1', 'text-l' ], true ) ) echo "  e.g. --{$row['name']}: {$row['value']}\n";
	echo "DRY RUN\n"; return;
}

update_option( 'bricks_global_variables_categories', $categories );
update_option( 'bricks_global_variables', $variables );
update_option( 'bricks_color_palette', $palette_opt );
// style-manager.min.css loads last and is not rebuilt by regenerate_css_files(); refresh it from the fresh options.
if ( ! \Bricks\Ajax::generate_style_manager_css_file() ) { fwrite( STDERR, "style-manager.min.css NOT written\n" ); exit( 1 ); }
echo "written; style-manager.min.css refreshed. Next, in a separate request: wp eval 'wp_set_current_user(1); \\Bricks\\Assets_Files::regenerate_css_files();'\n";
