<?php
/**
 * Import self-hosted SVG icons into a Bricks custom icon set.
 *
 * Bricks 2.0+ stores icon sets in three wp_options and exposes NO filter to
 * register them programmatically, so a curated set cannot ship in a plugin —
 * it has to be rebuilt per install against that install's attachment IDs.
 * This script is that rebuild. Source SVGs stay in version control; this maps
 * them onto whatever attachment IDs the target site hands out.
 *
 * Usage (eval-file can no-op silently on RunCloud — use the include form):
 *
 *   ICON_SET=MPD ICON_SRC=/path/to/icons \
 *     wp eval 'include "~/claude-config/bin/bricks-icon-import.php";'
 *
 *   ICON_SRC accepts directories and individual .svg files, colon-separated.
 *   ICON_DRY=1 reports what it would do and writes nothing.
 *
 * Idempotent: an icon already in the set (matched on name) is skipped, so
 * re-running after adding files to the source only imports the new ones.
 *
 * Verified against Bricks 2.3.10. Shapes read back from builder-saved output
 * per the golden rule — see 02.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( "Run through wp eval / wp eval-file.\n" );
}

// Bricks gates the SVG mime type on a capability check; WP-CLI runs as user 0,
// so without this every sideload fails with "not allowed to upload this file type".
wp_set_current_user( 1 );

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$set_name = getenv( 'ICON_SET' ) ?: 'MPD';
$src_raw  = getenv( 'ICON_SRC' ) ?: '';
$dry      = (bool) getenv( 'ICON_DRY' );

if ( '' === $src_raw ) {
	exit( "ABORT: set ICON_SRC to a directory or colon-separated list of .svg files\n" );
}

/* -- collect source files --------------------------------------------------- */

$files = [];
foreach ( explode( ':', $src_raw ) as $path ) {
	$path = trim( $path );
	if ( '' === $path ) {
		continue;
	}
	if ( is_dir( $path ) ) {
		foreach ( (array) glob( rtrim( $path, '/' ) . '/*.svg' ) as $f ) {
			$files[] = $f;
		}
	} elseif ( is_file( $path ) ) {
		$files[] = $path;
	} else {
		exit( 'ABORT: not found — ' . $path . "\n" );
	}
}

$files = array_values( array_unique( $files ) );
sort( $files );

if ( ! $files ) {
	exit( "ABORT: no .svg files found under ICON_SRC\n" );
}

echo 'source: ' . count( $files ) . " svg file(s)\n";
echo 'target set: ' . $set_name . ( $dry ? "   [DRY RUN]\n" : "\n" );

/* -- find or create the set ------------------------------------------------- */

$sets   = get_option( 'bricks_icon_sets', [] );
$sets   = is_array( $sets ) ? $sets : [];
$set_id = '';

foreach ( $sets as $s ) {
	if ( isset( $s['name'] ) && strcasecmp( $s['name'], $set_name ) === 0 ) {
		$set_id = $s['id'];
		break;
	}
}

$rand = function ( $prefix ) {
	$c = 'abcdefghijklmnopqrstuvwxyz0123456789';
	$s = '';
	for ( $i = 0; $i < 9; $i++ ) {
		$s .= $c[ random_int( 0, strlen( $c ) - 1 ) ];
	}
	return $prefix . $s;
};

if ( '' === $set_id ) {
	$set_id = $rand( 'set_' );
	$sets[] = [
		'id'   => $set_id,
		'name' => $set_name,
	];
	echo 'set: creating "' . $set_name . '" (' . $set_id . ")\n";
} else {
	echo 'set: reusing "' . $set_name . '" (' . $set_id . ")\n";
}

/* -- import ----------------------------------------------------------------- */

$icons    = get_option( 'bricks_custom_icons', [] );
$icons    = is_array( $icons ) ? $icons : [];
$existing = [];
foreach ( $icons as $i ) {
	if ( ( $i['setId'] ?? '' ) === $set_id ) {
		$existing[ $i['name'] ?? '' ] = true;
	}
}

$added = 0;
$skipped = 0;
$warned = [];

echo "\n";

foreach ( $files as $file ) {
	$name = pathinfo( $file, PATHINFO_FILENAME );

	if ( isset( $existing[ $name ] ) ) {
		printf( "  %-32s skipped (already in set)\n", $name );
		$skipped++;
		continue;
	}

	// House requirement: Bricks inlines the file verbatim and does not rewrite
	// fill/stroke, so a hardcoded colour ships as-is and cannot inherit the brand.
	$svg = (string) file_get_contents( $file );
	if ( false === strpos( $svg, 'currentColor' ) ) {
		$warned[] = $name;
	}

	// Icons are decorative by policy — the accessible name belongs to the
	// containing link/button, never the glyph. Bricks adds no aria-hidden of
	// its own, and a button's icon takes no attributes, so there is no
	// per-element way to set it: it has to live in the file. render_svg()
	// REPLACES an existing aria-hidden rather than duplicating it, so baking
	// it in here is safe wherever the icon is later used.
	$normalised = $svg;
	if ( false === stripos( $normalised, 'aria-hidden' ) ) {
		$normalised = preg_replace( '/<svg\b/i', '<svg aria-hidden="true"', $normalised, 1 );
	}

	if ( $dry ) {
		printf( "  %-32s would import%s\n", $name, ( $normalised !== $svg ? '  (+aria-hidden)' : '' ) );
		$added++;
		continue;
	}

	// sideload MOVES the file — hand it a copy so the source stays intact
	$tmp = wp_tempnam( basename( $file ) );
	if ( ! $tmp ) {
		echo 'ABORT: could not stage ' . $file . "\n";
		return;
	}
	if ( false === file_put_contents( $tmp, $normalised ) ) {
		echo 'ABORT: could not write staged copy of ' . $file . "\n";
		return;
	}

	$id = media_handle_sideload(
		[
			'name'     => basename( $file ),
			'tmp_name' => $tmp,
		],
		0,
		$name
	);

	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		echo 'ABORT: ' . $name . ' — ' . $id->get_error_message() . "\n";
		return;
	}

	$icons[] = [
		'id'            => $rand( 'icon_' ),
		'name'          => $name,
		'url'           => wp_get_attachment_url( $id ),
		'setId'         => $set_id,
		'attachment_id' => (int) $id,
	];

	printf( "  %-32s imported  attachment %d\n", $name, $id );
	$added++;
}

if ( $dry ) {
	echo "\nDRY RUN — nothing written. " . $added . ' would import, ' . $skipped . " already present.\n";
	return;
}

update_option( 'bricks_icon_sets', array_values( $sets ) );
update_option( 'bricks_custom_icons', array_values( $icons ) );

/* -- verify ----------------------------------------------------------------- */

$sets_rb  = get_option( 'bricks_icon_sets', [] );
$icons_rb = get_option( 'bricks_custom_icons', [] );

$in_set = array_values(
	array_filter(
		$icons_rb,
		function ( $i ) use ( $set_id ) {
			return ( $i['setId'] ?? '' ) === $set_id;
		}
	)
);

echo "\nset '" . $set_name . "' now holds " . count( $in_set ) . ' icon(s); ' . count( $sets_rb ) . " set(s) total\n";

$bad = 0;
foreach ( $in_set as $i ) {
	$path = get_attached_file( $i['attachment_id'] ?? 0 );
	if ( ! $path || ! file_exists( $path ) ) {
		echo '  BROKEN: ' . ( $i['name'] ?? '?' ) . " — attachment file missing\n";
		$bad++;
	}
}

if ( $warned ) {
	echo "\n⚠️  no currentColor (will not inherit brand colour): " . implode( ', ', $warned ) . "\n";
	echo "   Either normalise the source file or set the element's typed _fill,\n";
	echo "   which adds Bricks' .fill class (svg.fill * { fill: inherit }) —\n";
	echo "   note that targets DESCENDANTS, so a fill on the root <svg> still wins.\n";
}

echo "\n";
echo $bad ? "FAILED: {$bad} broken attachment(s)\n" : "OK — " . $added . " imported, " . $skipped . " skipped\n";
echo "\nElement setting shape for these icons:\n";
echo '  "icon": { "library": "custom_' . $set_id . '", "svg": { "id": <attachment_id>, "icon_id": "<icon id>", "url": "<url>" } }' . "\n";
