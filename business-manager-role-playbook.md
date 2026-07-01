# Business Manager Role — Portable Build Playbook

A drop-in recipe for a **scoped client account** on any WordPress + Bricks + ACSS + **WS Form Pro** build. Gives the client a clean, branded wp-admin where they can manage the blog, media, and site options, and **read contact-form submissions** — without ever reaching the page builder, the plugin/theme/user/settings surfaces, or WS Form's form builder.

This is the same stack shipped on KSCBS (`kscbs-functionality` core plugin). It's proven in production; copy the three files, run the find/replace, sync, verify.

---

## What you get

- A custom **Business Manager** role (Editor-tier blog access, no `*_pages` caps → the Bricks builder and all Bricks-built pages stay out of reach).
- A **branded, de-noised dashboard**: every default/plugin widget wiped, "Welcome to WordPress" panel gone, a single "Quick Actions" widget, branded footer, trimmed admin bar, suppressed nags, "Welcome, `<first name>`" heading.
- A **read-only Form Submissions viewer** (list + single-submission detail) that reads WS Form data server-side and needs **no WS Form capability**.
- **Self-healing** capability sync (version-stamped) so cap edits travel local → staging → production on the next load.

---

## Stack assumptions

| Thing | Expectation |
|---|---|
| WordPress | 6.0+ |
| Page builder | Bricks (pages built as `page` post type → role gets **no** `edit_pages`) |
| Forms | **WS Form Pro** (the submissions viewer targets its data model) |
| Core plugin | Project has a dedicated functionality plugin (`<prefix>-functionality`) with `inc/` includes — never functions.php |
| Blog | Standard `post` type; client manages posts |

If the site has **no blog**, drop the `post`/`upload_files`/`manage_categories` caps and the related dashboard cards.

---

## Find & replace before you paste

Pick a project prefix (lowercase `prefix`, uppercase `PREFIX`) and substitute throughout:

| Placeholder | Replace with | Example |
|---|---|---|
| `prefix_` | function/hook/cap prefix | `kscbs_` |
| `PREFIX_` | constant prefix | `KSCBS_` |
| `Prefix` | brand/company short name | `KS Custom Business Services` |
| `#006629` | brand accent colour | your primary |
| `Michael Parks / Jus B Media` | your agency credit | — |

One-liner rename once the files are in place (from the plugin dir):

```bash
grep -rl 'prefix_\|PREFIX_' inc/ | xargs sed -i 's/PREFIX_/KSCBS_/g; s/prefix_/kscbs_/g'
```

---

## File layout

```
<prefix>-functionality/
├── <prefix>-functionality.php     ← add 2 require_once lines (Step 4)
└── inc/
    ├── roles.php                  ← Step 1
    ├── admin-experience.php       ← Step 2
    └── submissions.php            ← Step 3
```

---

## The core idea (read this first)

**The role gets `read` + blog caps + `upload_files` + two custom caps — and deliberately no WS Form caps.**

Why no WS Form caps? WS Form's **native** Submissions list table reads the parent *form* object to build its columns, and that read hard-requires `read_form`:

```
WS_Form_WP_List_Table_Submit::__construct()
  → WS_Form_Submit->db_get_submit_fields()
    → db_form_object_read()
      → WS_Form_Form->db_read()   ← throws Uncaught Exception "Insufficient user capabilities (read_form)"
```

So granting only `read_submission` **WSODs** the native Submissions screen. Granting `read_form` fixes the fatal but re-exposes the Forms list and the builder-adjacent UI — the opposite of a locked-down client account.

**The fix:** never touch WS Form's admin UI. Ship a custom read-only page that reads submission data with WS Form's own `$bypass_user_capability_check = true` argument (every `db_read*` / `db_get_*` method accepts it), gated on a project capability. No WS Form cap is required or granted; the builder stays unreachable.

---

## Step 1 — `inc/roles.php`

Registers the role, the two custom caps, self-heal sync, and grants the custom caps to administrators.

```php
<?php
/**
 * Custom roles & capabilities — the scoped client "Business Manager" account.
 *
 * Editor-tier blog access, media, site options, and read-only form submissions.
 * NO *_pages caps (Bricks builder + built pages stay out of reach) and NO WS
 * Form caps (see inc/submissions.php for why submissions are read via a custom
 * bypass page instead).
 *
 * Idempotent + self-healing: bump PREFIX_ROLES_VERSION after editing $caps and
 * the new set re-syncs on the next load, across every environment.
 *
 * @package Prefix
 */

defined( 'ABSPATH' ) || exit;

define( 'PREFIX_BUSINESS_MANAGER_ROLE', 'prefix_business_manager' );

/** Custom cap gating the ACF/site-options page (kept off manage_options). */
define( 'PREFIX_CAP_MANAGE_SITE_OPTIONS', 'prefix_manage_site_options' );

/**
 * Capability gating the custom read-only Form Submissions page. Kept separate
 * from any WS Form capability on purpose — the page reads WS Form data with the
 * plugin's own capability bypass, so the client never needs WS Form's own caps
 * (which would drag the form builder back into reach).
 */
define( 'PREFIX_CAP_VIEW_SUBMISSIONS', 'prefix_view_submissions' );

/** Bump to re-sync the role's capability set on the next load. */
define( 'PREFIX_ROLES_VERSION', '1' );

add_action( 'init', 'prefix_sync_roles' );

function prefix_sync_roles() {

	if ( get_option( 'prefix_roles_version' ) === PREFIX_ROLES_VERSION ) {
		return;
	}

	$caps = array(
		// wp-admin access.
		'read'                        => true,

		// Blog posts only (post type `post`) — Editor tier, no pages.
		'edit_posts'                  => true,
		'edit_others_posts'           => true,
		'edit_published_posts'        => true,
		'edit_private_posts'          => true,
		'publish_posts'               => true,
		'read_private_posts'          => true,
		'delete_posts'                => true,
		'delete_others_posts'         => true,
		'delete_published_posts'      => true,
		'delete_private_posts'        => true,

		// Categories, tags, and any custom term taxonomies.
		'manage_categories'           => true,

		// Media library.
		'upload_files'                => true,

		// NO WS Form caps. Submissions are read via inc/submissions.php using
		// the plugin's own capability bypass. Granting read_submission alone
		// WSODs WS Form's native list table (it needs read_form); granting
		// read_form re-exposes the builder. Do NOT add WS Form caps here.

		// Custom read-only Form Submissions page (inc/submissions.php).
		PREFIX_CAP_VIEW_SUBMISSIONS   => true,

		// Custom-scoped site-options page (inc/options-pages.php).
		PREFIX_CAP_MANAGE_SITE_OPTIONS => true,
	);

	// Re-add cleanly so a bumped version fully replaces the prior cap set.
	// (remove_role does NOT unassign users from the slug.)
	remove_role( PREFIX_BUSINESS_MANAGER_ROLE );
	add_role( PREFIX_BUSINESS_MANAGER_ROLE, 'Business Manager', $caps );

	// Administrators must keep the custom caps (they're not auto-granted).
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		foreach ( array( PREFIX_CAP_MANAGE_SITE_OPTIONS, PREFIX_CAP_VIEW_SUBMISSIONS ) as $admin_cap ) {
			if ( ! $admin->has_cap( $admin_cap ) ) {
				$admin->add_cap( $admin_cap );
			}
		}
	}

	update_option( 'prefix_roles_version', PREFIX_ROLES_VERSION );
}
```

> If you don't have a site-options page, delete `PREFIX_CAP_MANAGE_SITE_OPTIONS` (constant, cap entry, and the admin-grant loop entry).

---

## Step 2 — `inc/admin-experience.php`

The scoped, branded dashboard. Everything is gated on `prefix_is_business_manager()`; administrators see stock WordPress.

```php
<?php
/**
 * Admin experience — client-facing UI/UX for the Business Manager role.
 * All gated to Business Managers; administrators see stock WordPress.
 *
 * @package Prefix
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the current (or given) user a client Business Manager — and not an admin?
 * The manage_options exclusion means a dual-role account never gets trimmed.
 */
function prefix_is_business_manager( $user = null ) {
	if ( null === $user ) {
		$user = wp_get_current_user();
	}
	if ( ! $user || ! $user->exists() ) {
		return false;
	}
	return in_array( PREFIX_BUSINESS_MANAGER_ROLE, (array) $user->roles, true )
		&& ! user_can( $user, 'manage_options' );
}

/**
 * Wipe the dashboard and install one branded widget. Priority 9999 so it runs
 * after core + every plugin has registered its widgets — then drop them all.
 */
add_action( 'wp_dashboard_setup', function() {
	if ( ! prefix_is_business_manager() ) {
		return;
	}
	global $wp_meta_boxes;
	$wp_meta_boxes['dashboard'] = array();
	remove_action( 'welcome_panel', 'wp_welcome_panel' );

	wp_add_dashboard_widget( 'prefix_welcome', 'Quick Actions', 'prefix_render_welcome_widget' );
}, 9999 );

/** Branded quick-actions widget. Inline styles — one-off admin surface. */
function prefix_render_welcome_widget() {
	$green = '#006629';

	$actions = array(
		array(
			'label' => 'Form Submissions',
			'desc'  => 'Read contact-form leads',
			'url'   => admin_url( 'admin.php?page=prefix-submissions' ),
			'cap'   => PREFIX_CAP_VIEW_SUBMISSIONS,
		),
		array(
			'label' => 'Edit Site Options',
			'desc'  => 'Phone, hours, social links, logos',
			'url'   => admin_url( 'admin.php?page=prefix-site-options' ),
			'cap'   => PREFIX_CAP_MANAGE_SITE_OPTIONS,
		),
		array(
			'label' => 'Manage Blog Posts',
			'desc'  => 'Edit and publish articles',
			'url'   => admin_url( 'edit.php' ),
			'cap'   => 'edit_posts',
		),
		array(
			'label' => 'Media Library',
			'desc'  => 'Images and downloads',
			'url'   => admin_url( 'upload.php' ),
			'cap'   => 'upload_files',
		),
	);

	echo '<p style="font-size:14px;margin:0 0 16px;">Use the shortcuts below to manage your site.</p>';
	echo '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">';
	foreach ( $actions as $a ) {
		if ( ! empty( $a['cap'] ) && ! current_user_can( $a['cap'] ) ) {
			continue;
		}
		printf(
			'<a href="%1$s" style="display:block;padding:12px 14px;border:1px solid #dcdcde;border-left:3px solid %2$s;border-radius:2px;text-decoration:none;background:#fff;">'
				. '<span style="display:block;font-weight:600;color:#1d2327;font-size:13px;">%3$s</span>'
				. '<span style="display:block;color:#646970;font-size:12px;margin-top:2px;">%4$s</span></a>',
			esc_url( $a['url'] ), esc_attr( $green ), esc_html( $a['label'] ), esc_html( $a['desc'] )
		);
	}
	echo '</div>';
	echo '<p style="margin:16px 0 0;padding-top:12px;border-top:1px solid #f0f0f1;color:#646970;font-size:12px;">'
		. 'Need help with your website? Contact Michael Parks / Jus B Media.</p>';
}

/** Branded admin footer; clear the WP version string. */
add_filter( 'admin_footer_text', function( $text ) {
	return prefix_is_business_manager()
		? 'Prefix &mdash; website by <strong>Michael Parks / Jus B Media</strong>.'
		: $text;
} );
add_filter( 'update_footer', function( $text ) {
	return prefix_is_business_manager() ? '' : $text;
}, 11 );

/**
 * Hide the native WS Form menu for Business Managers.
 *
 * The form builder is where the live form could be broken, and WS Form's native
 * Submissions list table hard-requires `read_form` anyway — so it can't be
 * safely exposed to a submissions-only role. Client submission review is
 * provided by the custom read-only page in inc/submissions.php.
 */
add_action( 'admin_menu', function() {
	if ( ! prefix_is_business_manager() ) {
		return;
	}
	remove_menu_page( 'ws-form' );
}, 9999 );

/** Admin-bar trim: drop the wp-logo + updates nodes, strip "Howdy,". */
add_action( 'admin_bar_menu', function( $bar ) {
	if ( ! prefix_is_business_manager() ) {
		return;
	}
	$bar->remove_node( 'wp-logo' );
	$bar->remove_node( 'updates' );
	$account = $bar->get_node( 'my-account' );
	if ( $account && ! empty( $account->title ) ) {
		$bar->add_node( array( 'id' => 'my-account', 'title' => trim( str_replace( 'Howdy,', '', $account->title ) ) ) );
	}
}, 9999 );

/**
 * Nag suppression everywhere except the site-options screen (where the
 * "Options Updated" confirmation is genuine). Runs before notices print.
 */
add_action( 'admin_head', function() {
	if ( ! prefix_is_business_manager() ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && false !== strpos( $screen->id, 'prefix-site-options' ) ) {
		return;
	}
	remove_all_actions( 'admin_notices' );
	remove_all_actions( 'all_admin_notices' );
	remove_all_actions( 'user_admin_notices' );
}, 1 );

/** Dashboard H1 → "Welcome, <first name>" (core H1 has no filter → swap on load). */
add_action( 'admin_head-index.php', function() {
	if ( ! prefix_is_business_manager() ) {
		return;
	}
	$user  = wp_get_current_user();
	$first = $user->first_name ? $user->first_name : $user->display_name;
	printf(
		'<script>document.addEventListener("DOMContentLoaded",function(){var h=document.querySelector(".wrap > h1");if(h){h.textContent=%s;}});</script>',
		wp_json_encode( sprintf( 'Welcome, %s', $first ) )
	);
} );
add_filter( 'admin_title', function( $admin_title, $title ) {
	if ( ! prefix_is_business_manager() ) {
		return $admin_title;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'dashboard' === $screen->id ) {
		$user  = wp_get_current_user();
		$first = $user->first_name ? $user->first_name : $user->display_name;
		return sprintf( 'Welcome, %s', $first ) . ' &lsaquo; ' . get_bloginfo( 'name' ) . ' &mdash; WordPress';
	}
	return $admin_title;
}, 10, 2 );

/** Optional: trim noisy Posts list-table columns for the client. */
add_filter( 'manage_edit-post_columns', function( $columns ) {
	if ( ! prefix_is_business_manager() ) {
		return $columns;
	}
	unset( $columns['author'], $columns['tags'] );
	return $columns;
} );
```

> **Login screen:** on these builds wp-login.php is redirected to a front-end login page, so no login branding lives here. If your build uses stock wp-login.php, add `login_headerurl` / `login_headertext` filters or a small CSS enqueue on `login_enqueue_scripts`.

---

## Step 3 — `inc/submissions.php`

**What this file is:** the custom, read-only Form Submissions viewer for the Business Manager. It adds a top-level **Submissions** admin menu (envelope icon, gated on `PREFIX_CAP_VIEW_SUBMISSIONS`) with two screens:

- **List view** — a paginated table of leads (Received date + each form field), newest first, with a "New" badge on unread rows and a **View** button per row.
- **Detail view** — clicking a row opens one submission in full: every field shown complete (full message, `mailto:` email, `tel:` phone).

It exists because WS Form's *native* Submissions screen requires `read_form` (which would re-expose the form builder). Instead, this file reads the data server-side with WS Form's own `$bypass_user_capability_check = true` — so the client sees their leads **without any WS Form capability** and can never reach the builder. It is **read-only**: no edit / delete / export path, and it never writes the `viewed` flag.

```php
<?php
/**
 * Form Submissions — custom read-only viewer for Business Managers.
 *
 * Surfaces WS Form leads WITHOUT granting any WS Form capability and WITHOUT
 * touching WS Form's native admin UI (its list table hard-requires read_form).
 *
 * Safe by construction:
 *   - Gated on PREFIX_CAP_VIEW_SUBMISSIONS (Business Managers + admins).
 *   - All WS Form reads pass $bypass_user_capability_check = true.
 *   - READ ONLY. No mark-read / edit / delete / export path exists; the
 *     `viewed` flag is shown but never written.
 *
 * @package Prefix
 */

defined( 'ABSPATH' ) || exit;

define( 'PREFIX_SUBMISSIONS_PER_PAGE', 25 );

add_action( 'admin_menu', function() {
	add_menu_page(
		__( 'Form Submissions', 'prefix' ),
		__( 'Submissions', 'prefix' ),
		PREFIX_CAP_VIEW_SUBMISSIONS,
		'prefix-submissions',
		'prefix_render_submissions_page',
		'dashicons-email-alt',
		26
	);
}, 20 );

/**
 * Resolve the form being viewed. Returns [ (int) form_id, (array) forms ].
 * Selected id is validated against the real form list.
 */
function prefix_submissions_resolve_form() {
	$forms = array();
	if ( class_exists( 'WS_Form_Form' ) ) {
		$ws_form_form = new WS_Form_Form();
		$forms        = (array) $ws_form_form->get_all( true ); // published: [ ['id','label'], ... ]
	}
	if ( empty( $forms ) ) {
		return array( 0, array() );
	}
	$form_ids  = array_map( static function( $f ) { return (int) $f['id']; }, $forms );
	$requested = isset( $_GET['wsf_form'] ) ? absint( wp_unslash( $_GET['wsf_form'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, validated below.
	$selected  = in_array( $requested, $form_ids, true ) ? $requested : (int) $forms[0]['id'];
	return array( $selected, $forms );
}

function prefix_render_submissions_page() {

	if ( ! current_user_can( PREFIX_CAP_VIEW_SUBMISSIONS ) ) {
		wp_die( esc_html__( 'You do not have permission to view form submissions.', 'prefix' ) );
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Form Submissions', 'prefix' ) . '</h1>';

	if ( ! class_exists( 'WS_Form_Submit' ) || ! class_exists( 'WS_Form_Form' ) ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'The forms plugin is not available right now.', 'prefix' ) . '</p></div></div>';
		return;
	}

	list( $form_id, $forms ) = prefix_submissions_resolve_form();
	if ( 0 === $form_id ) {
		echo '<p>' . esc_html__( 'No forms have been set up yet.', 'prefix' ) . '</p></div>';
		return;
	}

	// Form selector (only when >1 form).
	if ( count( $forms ) > 1 ) {
		echo '<form method="get" style="margin:16px 0;">';
		echo '<input type="hidden" name="page" value="prefix-submissions" />';
		echo '<label for="wsf_form" style="margin-right:8px;font-weight:600;">' . esc_html__( 'Form:', 'prefix' ) . '</label>';
		echo '<select name="wsf_form" id="wsf_form" onchange="this.form.submit()">';
		foreach ( $forms as $f ) {
			printf( '<option value="%d"%s>%s</option>', (int) $f['id'], selected( (int) $f['id'], $form_id, false ), esc_html( $f['label'] ) );
		}
		echo '</select></form>';
	}

	$ws_form_submit          = new WS_Form_Submit();
	$ws_form_submit->form_id = $form_id;
	$submit_fields           = (array) $ws_form_submit->db_get_submit_fields( true ); // bypass

	// Single-submission detail view.
	$view_id = isset( $_GET['view'] ) ? absint( wp_unslash( $_GET['view'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, validated in query.
	if ( $view_id > 0 ) {
		prefix_render_submission_detail( $form_id, $view_id, $submit_fields );
		echo '</div>';
		return;
	}

	// Publish-only, form-scoped — independent of WS Form's request query vars.
	$where    = sprintf( "form_id = %d AND status = 'publish'", $form_id );
	$total    = (int) $ws_form_submit->db_read_count( '', $where, true );
	$per_page = PREFIX_SUBMISSIONS_PER_PAGE;
	$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only pagination.
	$pages    = max( 1, (int) ceil( $total / $per_page ) );
	$paged    = min( $paged, $pages );
	$offset   = ( $paged - 1 ) * $per_page;

	$rows = (array) $ws_form_submit->db_read_all( '', $where, '', 'id DESC', $per_page, $offset, true, true, true ); // last arg = bypass

	echo '<p style="color:#646970;">' . sprintf(
		esc_html( _n( '%s submission', '%s submissions', $total, 'prefix' ) ),
		esc_html( number_format_i18n( $total ) )
	) . '</p>';

	if ( empty( $rows ) ) {
		echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No submissions yet.', 'prefix' ) . '</p></div></div>';
		return;
	}

	echo '<table class="wp-list-table widefat fixed striped"><thead><tr>';
	echo '<th style="width:150px;">' . esc_html__( 'Received', 'prefix' ) . '</th>';
	foreach ( $submit_fields as $field ) {
		echo '<th>' . esc_html( isset( $field['label'] ) ? $field['label'] : '' ) . '</th>';
	}
	echo '<th style="width:90px;"></th></tr></thead><tbody>';

	foreach ( $rows as $row ) {

		$is_new     = isset( $row->viewed ) && ! (int) $row->viewed;
		$detail_url = add_query_arg(
			array( 'page' => 'prefix-submissions', 'wsf_form' => $form_id, 'paged' => $paged, 'view' => (int) $row->id ),
			admin_url( 'admin.php' )
		);

		echo '<tr>';

		$date_raw = isset( $row->date_added ) ? $row->date_added : '';
		$date_out = $date_raw ? mysql2date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $date_raw ) : '';
		echo '<td><a href="' . esc_url( $detail_url ) . '" style="font-weight:600;text-decoration:none;">' . esc_html( $date_out ) . '</a>';
		if ( $is_new ) {
			echo ' <span style="display:inline-block;margin-left:4px;padding:1px 6px;border-radius:2px;background:#006629;color:#fff;font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;vertical-align:middle;">' . esc_html__( 'New', 'prefix' ) . '</span>';
		}
		echo '</td>';

		foreach ( $submit_fields as $field_id => $field ) {
			$value = prefix_submissions_field_value( $row, $field_id );
			$type  = isset( $field['type'] ) ? $field['type'] : '';
			echo '<td>' . prefix_submissions_render_value( $value, (string) $type ) . '</td>';
		}

		echo '<td><a href="' . esc_url( $detail_url ) . '" class="button button-small">' . esc_html__( 'View', 'prefix' ) . '</a></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';

	if ( $pages > 1 ) {
		echo '<div class="tablenav bottom"><div class="tablenav-pages">';
		echo wp_kses_post( paginate_links( array(
			'base'      => add_query_arg( array( 'page' => 'prefix-submissions', 'wsf_form' => $form_id, 'paged' => '%#%' ), admin_url( 'admin.php' ) ),
			'format'    => '',
			'current'   => $paged,
			'total'     => $pages,
			'prev_text' => '&lsaquo;',
			'next_text' => '&rsaquo;',
		) ) );
		echo '</div></div>';
	}

	echo '</div>';
}

/** Single submission, in full. Scoped by id + form + publish status. */
function prefix_render_submission_detail( $form_id, $view_id, $submit_fields ) {

	$paged    = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only.
	$back_url = add_query_arg( array( 'page' => 'prefix-submissions', 'wsf_form' => $form_id, 'paged' => $paged ), admin_url( 'admin.php' ) );

	echo '<p style="margin-top:12px;"><a href="' . esc_url( $back_url ) . '">&lsaquo; ' . esc_html__( 'Back to submissions', 'prefix' ) . '</a></p>';

	$where = sprintf( "id = %d AND form_id = %d AND status = 'publish'", $view_id, $form_id );

	$ws_form_submit          = new WS_Form_Submit();
	$ws_form_submit->form_id = $form_id;
	$rows = (array) $ws_form_submit->db_read_all( '', $where, '', '', 1, 0, true, true, true ); // bypass
	$row  = ! empty( $rows ) ? reset( $rows ) : null;

	if ( null === $row ) {
		echo '<div class="notice notice-error inline"><p>' . esc_html__( 'That submission could not be found.', 'prefix' ) . '</p></div>';
		return;
	}

	$date_out = ! empty( $row->date_added ) ? mysql2date( get_option( 'date_format' ) . ', ' . get_option( 'time_format' ), $row->date_added ) : '';

	echo '<h2 style="margin-top:8px;">' . esc_html( sprintf( /* translators: %d: submission id */ __( 'Submission #%d', 'prefix' ), (int) $row->id ) ) . '</h2>';
	if ( $date_out ) {
		echo '<p style="color:#646970;margin-top:0;">' . esc_html__( 'Received', 'prefix' ) . ' ' . esc_html( $date_out ) . '</p>';
	}

	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $submit_fields as $field_id => $field ) {
		$label = isset( $field['label'] ) ? $field['label'] : '';
		$type  = isset( $field['type'] ) ? $field['type'] : '';
		$value = prefix_submissions_field_value( $row, $field_id );
		echo '<tr><th scope="row" style="width:200px;">' . esc_html( $label ) . '</th>';
		echo '<td>' . prefix_submissions_render_value( $value, (string) $type, false ) . '</td></tr>';
	}
	echo '</tbody></table>';
}

/** Pull a scalar value for a field id from a submission row's meta. */
function prefix_submissions_field_value( $row, $field_id ) {
	$meta_key = 'field_' . $field_id;
	if ( ! isset( $row->meta[ $meta_key ] ) ) {
		return '';
	}
	$m = $row->meta[ $meta_key ];
	if ( ! is_array( $m ) ) {
		return (string) $m;
	}
	$value = isset( $m['value'] ) ? $m['value'] : '';
	if ( is_array( $value ) ) { // checkbox/select groups → comma-joined
		$value = implode( ', ', array_map( 'strval', $value ) );
	}
	return (string) $value;
}

/** Escape + light affordances for email / phone / long text. Never raw HTML. */
function prefix_submissions_render_value( $value, $type, $collapse = true ) {
	$value = trim( $value );
	if ( '' === $value ) {
		return '<span style="color:#a7aaad;">&mdash;</span>';
	}
	switch ( $type ) {
		case 'email':
			return is_email( $value ) ? '<a href="' . esc_attr( 'mailto:' . $value ) . '">' . esc_html( $value ) . '</a>' : esc_html( $value );
		case 'tel':
			$tel = preg_replace( '/[^0-9+]/', '', $value );
			return $tel ? '<a href="' . esc_attr( 'tel:' . $tel ) . '">' . esc_html( $value ) . '</a>' : esc_html( $value );
		case 'textarea':
			if ( $collapse && strlen( $value ) > 120 ) {
				$preview = esc_html( mb_substr( $value, 0, 120 ) ) . '&hellip;';
				return '<details><summary style="cursor:pointer;">' . $preview . '</summary><div style="margin-top:6px;white-space:pre-wrap;">' . nl2br( esc_html( $value ) ) . '</div></details>';
			}
			return '<div style="white-space:pre-wrap;">' . nl2br( esc_html( $value ) ) . '</div>';
		default:
			return esc_html( $value );
	}
}
```

---

## Step 4 — Wire into the main plugin

In `<prefix>-functionality.php`, load the includes (order: `roles.php` early so its constants exist for the others):

```php
require_once PREFIX_FUNCTIONALITY_PATH . 'inc/roles.php';
require_once PREFIX_FUNCTIONALITY_PATH . 'inc/admin-experience.php';
require_once PREFIX_FUNCTIONALITY_PATH . 'inc/submissions.php';
```

---

## Step 5 — Deploy & verify (WP-CLI)

```bash
# 1. Lint
php -l inc/roles.php && php -l inc/admin-experience.php && php -l inc/submissions.php

# 2. Force the role sync (also fires automatically on next page load)
wp eval 'prefix_sync_roles();'
wp option get prefix_roles_version          # → your PREFIX_ROLES_VERSION

# 3. Verify the cap model on a real Business Manager user (swap 9 for the ID)
wp eval 'foreach(["prefix_view_submissions"=>"YES","read_form"=>"no","read_submission"=>"no","edit_pages"=>"no"] as $c=>$want){printf("%-4s %-26s want:%s\n", user_can(9,$c)?"YES":"no",$c,$want);}'

# 4. Functionally render the page AS that user (no fatal, real data)
wp eval 'wp_set_current_user(9); ob_start(); prefix_render_submissions_page(); echo (strlen(ob_get_clean())>0 ? "render OK\n" : "EMPTY\n");'

# 5. Create the client account when ready
wp user create kim kim@example.com --role=prefix_business_manager --first_name=Kim --send-email
```

**OPcache note (OpenLiteSpeed / lsphp on RunCloud):** defaults are `opcache.validate_timestamps=On`, `revalidate_freq=2`, so edited files are picked up within ~2s and no reset is needed. Role caps live in the DB (via the object cache), so the CLI sync is authoritative immediately. If a host runs `validate_timestamps=0`, reset OPcache / restart PHP after deploying.

---

## Capability model — quick reference

| Capability | Granted? | Effect |
|---|---|---|
| `read`, `edit_posts` … `delete_private_posts` | ✅ | Full blog management, no pages |
| `manage_categories` | ✅ | Categories/tags + custom term taxonomies |
| `upload_files` | ✅ | Media library |
| `prefix_view_submissions` | ✅ | Custom read-only Submissions page |
| `prefix_manage_site_options` | ✅ | Custom-scoped options page |
| `edit_pages` / any `*_pages` | ❌ | **Bricks builder + built pages unreachable** |
| `read_form` / `edit_form` / `read_submission` (WS Form) | ❌ | Native WS Form UI never exposed |
| `manage_options`, `activate_plugins`, `switch_themes`, `edit_users`, `manage_options_wsform` | ❌ | Settings/plugins/themes/users all hidden |

---

## The WS Form gotcha (why the custom viewer exists)

> **WS Form's native Submissions list table requires `read_form`, not `read_submission`.**
> Its `WP_List_Table_Submit::__construct()` reads the parent form object (`WS_Form_Form->db_read()`) to build columns, and that read throws `Uncaught Exception: Insufficient user capabilities (read_form)` → **E_ERROR / WSOD** if the user only has `read_submission`. Granting `read_form` fixes the fatal but re-exposes the Forms list. Neither is acceptable for a locked-down client — hence the custom page reading with `$bypass_user_capability_check = true`.

WS Form data model the viewer relies on:
- `WS_Form_Form->get_all( true )` → `[ ['id'=>, 'label'=>], ... ]` (published forms; no cap check).
- `WS_Form_Submit` with `->form_id` set; `db_get_submit_fields( true )` → `field_id => ['label','type', ...]`.
- `db_read_all( $join, $where, $group_by, $order_by, $limit, $offset, $get_meta, $get_expanded, $bypass )` → rows; each row `->date_added`, `->status`, `->viewed`, and `->meta['field_'.$id]['value']`.
- `db_read_count( $join, $where, $bypass )` → int.
- Every method's **last positional arg is the bypass flag** — always pass `true` here.

---

## Customization checklist per project

- [ ] Run the find/replace (`prefix`/`PREFIX`, brand colour, agency credit).
- [ ] Confirm the site-options page slug matches (`prefix-site-options`) — or drop that cap/card if none.
- [ ] Drop blog caps + the Posts/Media cards if the site has no blog.
- [ ] If **not** WS Form Pro: replace `inc/submissions.php`'s reads with the target plugin's read API (the role + admin-experience layers are form-agnostic). Gravity Forms → `GFAPI::get_entries()`; Fluent Forms → its submission model; etc. Keep it read-only + gated on `prefix_view_submissions`.
- [ ] Set `PREFIX_ROLES_VERSION` and bump it whenever you edit `$caps`.
- [ ] Verify with the Step 5 commands, then create the client account.
- [ ] If wp-login.php is not redirected on this build, add login branding.

---

*Origin: KSCBS build (`kscbs-functionality` v1.5.2), 2026. Proven in production on OpenLiteSpeed / lsphp / RunCloud.*
