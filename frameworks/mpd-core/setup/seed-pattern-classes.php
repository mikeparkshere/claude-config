<?php
/**
 * mpd-core — register the pattern anchors as name-only Bricks global classes.
 *
 * The patterns (`clickable-parent`, `focus-parent--shadow`, `focus-parent--outline`) live in mpd-core.css. As CSS
 * alone they never appear in the builder's class picker, so builds typed them as raw `_cssClasses` strings.
 * Registered here with EMPTY settings, they're pickable, emit no CSS of their own, and readback counts them
 * as patterns rather than missing BEM classes. Add them alongside an element's BEM class, never instead of it.
 *
 * Usage (from the WordPress root):  wp eval-file path/to/seed-pattern-classes.php
 * Idempotent: an existing class with the same name is left alone.
 * ⚠️ bricks_global_classes is single-writer (`03`): close every builder tab first, re-read after ~90s.
 */

wp_set_current_user( 1 );
$names   = [ 'clickable-parent', 'focus-parent--shadow', 'focus-parent--outline' ];
$classes = get_option( 'bricks_global_classes', [] ) ?: [];
$have    = array_column( $classes, 'name' );
$ids     = array_column( $classes, 'id' );

$added = [];
foreach ( $names as $name ) {
	if ( in_array( $name, $have, true ) ) continue;
	$h = md5( 'mpd-core:class:' . $name ); $id = '';
	for ( $i = 0; strlen( $id ) < 6; $i++ ) { $id .= chr( 97 + hexdec( $h[ $i ] ) % 26 ); }   // letters only: never all-numeric
	if ( in_array( $id, $ids, true ) ) { fwrite( STDERR, "id collision for $name\n" ); exit( 1 ); }
	$classes[] = [ 'id' => $id, 'name' => $name, 'settings' => [], 'modified' => time() * 1000, 'user_id' => 1 ];   // settings: array, never stdClass (`02`)
	$added[] = $name;
}

if ( ! $added ) { echo "pattern classes: all present\n"; return; }
update_option( 'bricks_global_classes', $classes );
echo 'pattern classes added: ' . implode( ', ', $added ) . "\n";
