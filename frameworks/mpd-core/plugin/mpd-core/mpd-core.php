<?php
/**
 * Plugin Name:       mpd-core
 * Description:       Michael Parks Design framework layer for native Bricks sites: framework defaults Bricks has no typed control for, plus the clickable-parent and focus-parent patterns. Consumes design tokens from the Bricks Style Manager; defines none.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Michael Parks Design
 * License:           GPL-2.0-or-later
 *
 * Framework only. Nothing site-specific belongs here: it is versioned across sites, and the canonical copy
 * lives in claude-config/frameworks/mpd-core/. Site code goes in the site's own functionality plugin.
 */

defined( 'ABSPATH' ) || exit;

define( 'MPD_CORE_VERSION', '0.2.0' );

/**
 * Frontend and the Bricks builder canvas (the canvas iframe runs wp_enqueue_scripts too).
 * Skipped only in the builder's main panel, so framework CSS never styles the builder UI itself.
 * After bricks-frontend, so Bricks' `@layer bricks` is declared first; the file also declares the
 * layer order explicitly, so the defaults sit above Bricks' base layer either way.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'bricks_is_builder_main' ) && bricks_is_builder_main() ) {
		return;
	}

	$path = plugin_dir_path( __FILE__ ) . 'css/mpd-core.css';
	if ( ! file_exists( $path ) ) {
		return;
	}

	wp_enqueue_style(
		'mpd-core',
		plugin_dir_url( __FILE__ ) . 'css/mpd-core.css',
		wp_style_is( 'bricks-frontend', 'registered' ) ? [ 'bricks-frontend' ] : [],
		MPD_CORE_VERSION . '.' . filemtime( $path )   // version + mtime: cache-busts on every edit
	);
}, 20 );
