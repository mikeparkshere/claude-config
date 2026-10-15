<?php
/**
 * Plugin Name: MP User Enumeration Hardening
 * Description: Closes unauthenticated user-enumeration vectors: the REST users routes, ?author=N ID probing, author archives, and the oEmbed author fields. Leaves all authenticated access intact.
 * Version:     1.0.0
 * Author:      Michael Parks
 * License:     GPL-2.0-or-later
 *
 * Authenticated requests are deliberately untouched, so Gutenberg author
 * pickers, WooCommerce Analytics, and key-authenticated wc/v3 calls keep
 * working. Only anonymous callers are refused.
 *
 * Per-site opt-outs (define in wp-config.php before this loads):
 *   define( 'MP_UEH_BLOCK_AUTHOR_ARCHIVES', false );  // keep /author/<slug>/ public
 *   define( 'MP_UEH_GENERIC_LOGIN_ERRORS', false );   // keep core login error text
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MP_UEH_BLOCK_AUTHOR_ARCHIVES' ) ) {
	define( 'MP_UEH_BLOCK_AUTHOR_ARCHIVES', true );
}
if ( ! defined( 'MP_UEH_GENERIC_LOGIN_ERRORS' ) ) {
	define( 'MP_UEH_GENERIC_LOGIN_ERRORS', true );
}

/**
 * 1. REST: require authentication on the wp/v2/users routes.
 *
 * The original permission_callback is preserved and still runs for logged-in
 * users, so capability checks (edit_users, list_users, self-access on
 * users/me) behave exactly as core intends.
 */
function mp_ueh_guard_rest_users( $endpoints ) {
	$routes = array(
		'/wp/v2/users',
		'/wp/v2/users/(?P<id>[\d]+)',
		'/wp/v2/users/me',
	);

	foreach ( $routes as $route ) {
		if ( empty( $endpoints[ $route ] ) ) {
			continue;
		}

		foreach ( $endpoints[ $route ] as $i => $handler ) {
			// Only guard reads; writes already demand capabilities.
			if ( empty( $handler['methods'] ) || false === strpos( $handler['methods'], 'GET' ) ) {
				continue;
			}

			$original = isset( $handler['permission_callback'] ) ? $handler['permission_callback'] : null;

			$endpoints[ $route ][ $i ]['permission_callback'] = function ( $request ) use ( $original ) {
				if ( ! is_user_logged_in() ) {
					return new WP_Error(
						'rest_forbidden',
						__( 'Authentication required.', 'mp-ueh' ),
						array( 'status' => 401 )
					);
				}

				return is_callable( $original ) ? call_user_func( $original, $request ) : true;
			};
		}
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'mp_ueh_guard_rest_users' );

/**
 * 2. Block ?author=N before redirect_canonical can map the ID to a slug.
 *
 * parse_request runs ahead of the canonical redirect, so the 301 that would
 * otherwise disclose the username never happens.
 */
function mp_ueh_block_author_probing( $wp ) {
	if ( is_user_logged_in() || is_admin() ) {
		return;
	}

	$probing_by_id = isset( $wp->query_vars['author'] ) || isset( $_GET['author'] );

	$probing_archive = MP_UEH_BLOCK_AUTHOR_ARCHIVES
		&& ( isset( $wp->query_vars['author_name'] ) || isset( $wp->query_vars['author'] ) );

	if ( ! $probing_by_id && ! $probing_archive ) {
		return;
	}

	unset( $wp->query_vars['author'], $wp->query_vars['author_name'] );
	$wp->query_vars['error'] = '404';
}
add_action( 'parse_request', 'mp_ueh_block_author_probing' );

/**
 * 3. Strip author identity out of oEmbed responses.
 */
function mp_ueh_scrub_oembed_author( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
}
add_filter( 'oembed_response_data', 'mp_ueh_scrub_oembed_author' );

/**
 * 4. Generic login errors, so a wrong username and a wrong password are
 *    indistinguishable and the form cannot be used to validate accounts.
 */
function mp_ueh_generic_login_errors() {
	if ( ! MP_UEH_GENERIC_LOGIN_ERRORS ) {
		return null;
	}

	return __( 'The credentials entered are incorrect.', 'mp-ueh' );
}
add_filter( 'login_errors', 'mp_ueh_generic_login_errors' );
