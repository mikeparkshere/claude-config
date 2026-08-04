<?php
/**
 * Point WP-CLI at the right MySQL socket when run inside a Local (by Flywheel)
 * site.
 *
 * Local runs one MySQL instance per site on a per-site unix socket under
 * ~/Library/Application Support/Local/run/<site-id>/mysql/mysqld.sock, but the
 * site's wp-config.php just says DB_HOST 'localhost'. The web SAPI works
 * because Local sets mysqli.default_socket in its own php.ini; a Homebrew
 * `wp` on the PATH has no idea, tries the compiled-in default (/tmp/mysql.sock)
 * and dies with "Error establishing a database connection".
 *
 * `WP_CLI_PHP_ARGS` does not help — /opt/homebrew/bin/wp is a phar with a
 * `#!/usr/bin/env php` shebang, so there is nowhere to inject `-d`.
 *
 * WP-CLI loads `require` files during bootstrap, before wp-config.php, and
 * mysqli.default_socket is PHP_INI_ALL, so setting it here lands in time.
 *
 * Outside a Local site this is a no-op — it never touches a normal install.
 *
 * Wired up via ~/.wp-cli/config.yml.
 */

( function () {

	$home = getenv( 'HOME' );
	if ( ! $home ) {
		return;
	}

	$sites_json = $home . '/Library/Application Support/Local/sites.json';
	if ( ! is_readable( $sites_json ) ) {
		return;
	}

	$cwd = getcwd();
	if ( ! $cwd ) {
		return;
	}

	$sites = json_decode( file_get_contents( $sites_json ), true );
	if ( ! is_array( $sites ) ) {
		return;
	}

	// Longest matching site path wins, so a site nested inside another
	// directory can't be shadowed by a shorter prefix.
	$best_id  = null;
	$best_len = -1;

	foreach ( $sites as $id => $site ) {

		$path = $site['path'] ?? '';
		if ( '' === $path ) {
			continue;
		}

		// Local stores paths with a literal ~ prefix.
		if ( str_starts_with( $path, '~' ) ) {
			$path = $home . substr( $path, 1 );
		}

		$path = rtrim( $path, '/' );

		if ( $cwd === $path || str_starts_with( $cwd, $path . '/' ) ) {
			$len = strlen( $path );
			if ( $len > $best_len ) {
				$best_len = $len;
				$best_id  = $id;
			}
		}
	}

	if ( null === $best_id ) {
		return;
	}

	$sock = $home . '/Library/Application Support/Local/run/' . $best_id . '/mysql/mysqld.sock';

	// If the socket isn't there the site is simply stopped — say so plainly
	// rather than letting it fail later as a confusing connection error.
	if ( ! file_exists( $sock ) ) {
		if ( class_exists( '\WP_CLI' ) ) {
			\WP_CLI::warning( "Local site '$best_id' matched but its MySQL socket is missing — is the site running?" );
		}
		return;
	}

	ini_set( 'mysqli.default_socket', $sock );
	ini_set( 'pdo_mysql.default_socket', $sock );
} )();
