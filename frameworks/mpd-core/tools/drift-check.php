<?php
/**
 * mpd-core — drift check: dry-run a WP-CLI build script against the live database.
 *
 * A project that builds pages from idempotent scripts AND lets people edit in the builder can't re-run a
 * script blindly: it rewrites its whole tree and class set, silently reverting every builder edit (`03`,
 * "Re-running a WP-CLI build script silently reverts every builder edit"). This runs the script with every
 * database write intercepted and prints what it WOULD change. Copy each "live" value into the script until
 * it reads "identical", then re-run it for real.
 *
 * Usage (from the WordPress root):
 *   SCRIPT=site-build/pages.php wp eval 'include "path/to/drift-check.php";'
 * SCRIPT is relative to the WordPress root, or absolute.
 *
 * Prove it once per project with a known difference (edit one value in the builder, expect one "~" line),
 * and confirm the class option's hash is unchanged afterwards.
 * Not for scripts that write plugin tables (forms) or content the database owns after launch.
 */
$script = getenv( 'SCRIPT' );
$captured_meta = []; $captured_classes = null;
$live_classes = get_option( 'bricks_global_classes', [] );

add_filter( 'update_post_metadata', function ( $check, $id, $key, $value ) use ( &$captured_meta ) {
	if ( strpos( $key, '_bricks_page_' ) === 0 ) { $captured_meta[ "$id:$key" ] = $value; return true; }
	return true;   // block every other meta write too
}, -999, 4 );
add_filter( 'add_post_metadata', fn() => true, -999 );
add_filter( 'pre_update_option_bricks_global_classes', function ( $new, $old ) use ( &$captured_classes ) { $captured_classes = $new; return $old; }, -999, 2 );
add_filter( 'pre_update_option', fn( $v, $o, $old ) => $old, -999, 3 );   // block all other option writes
add_filter( 'wp_insert_post_empty_content', '__return_true' );               // block post creation
// menu item updates go through wp_update_post -> blocked by the filter above
// Bricks regen writes files only; harmless, but skip it:
add_filter( 'pre_wp_update_nav_menu_item', fn() => 0 );

if ( ! $script ) { fwrite( STDERR, "SCRIPT is required\n" ); exit( 2 ); }
$path = $script[0] === '/' ? $script : ABSPATH . $script;
if ( ! is_readable( $path ) ) { fwrite( STDERR, "not readable: $path\n" ); exit( 2 ); }
ob_start(); include $path; ob_end_clean();

$norm = fn( $v ) => wp_json_encode( $v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
echo "== $script\n";
foreach ( $captured_meta as $k => $tree ) {
	[ $id, $key ] = explode( ':', $k, 2 );
	$live = get_post_meta( (int) $id, $key, true );
	$lb = array_column( (array) $live, null, 'id' ); $sb = array_column( (array) $tree, null, 'id' );
	$diff = [];
	foreach ( $sb as $eid => $e ) {
		if ( ! isset( $lb[ $eid ] ) ) { $diff[] = "  + $eid {$e['name']} (script only)"; continue; }
		foreach ( array_unique( array_merge( array_keys( $e['settings'] ?? [] ), array_keys( $lb[ $eid ]['settings'] ?? [] ) ) ) as $sk ) {
			$a = $e['settings'][ $sk ] ?? null; $b = $lb[ $eid ]['settings'][ $sk ] ?? null;
			if ( $norm( $a ) !== $norm( $b ) ) $diff[] = sprintf( "  ~ %s %s .%s\n      script: %s\n      live:   %s", $eid, $e['name'], $sk, mb_substr( $norm( $a ), 0, 220 ), mb_substr( $norm( $b ), 0, 220 ) );
		}
		foreach ( [ 'children', 'parent', 'label' ] as $f ) if ( $norm( $e[ $f ] ?? null ) !== $norm( $lb[ $eid ][ $f ] ?? null ) ) $diff[] = "  ~ $eid $f";
	}
	foreach ( array_diff_key( $lb, $sb ) as $eid => $e ) $diff[] = "  - $eid {$e['name']} (live only)";
	printf( "#%d %s: %s\n%s", $id, $key, $diff ? count( $diff ) . ' differences' : 'identical', $diff ? implode( "\n", $diff ) . "\n" : '' );
}
if ( $captured_classes !== null ) {
	$lc = array_column( $live_classes, null, 'id' ); $n = 0;
	foreach ( $captured_classes as $c ) {
		$l = $lc[ $c['id'] ] ?? null;
		if ( ! $l ) { echo "  class + {$c['name']} (script only)\n"; $n++; continue; }
		if ( $l['name'] !== $c['name'] || $norm( $l['settings'] ) !== $norm( $c['settings'] ) ) {
			printf( "  class ~ %s\n      script: %s\n      live:   %s\n", $c['name'], mb_substr( $norm( $c['settings'] ), 0, 300 ), mb_substr( $norm( $l['settings'] ), 0, 300 ) ); $n++;
		}
	}
	echo $n ? "" : "classes: identical\n";
}
