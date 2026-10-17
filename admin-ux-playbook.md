# Client Admin UX Pass — Portable Playbook

The companion to `business-manager-role-playbook.md`. The role playbook decides **what the client can reach**;
this one decides **what every screen they reach looks like and says**. Run it once the role exists and the
content model has settled, and before the client account is created.

It is a method plus a set of recipes, not a drop-in file set: every build's fields differ, so the audit is
repeated per project and the recipes are applied where the audit finds the matching problem.

*Origin: SLVPR (Site Manager role, Bricks + ACF, CPTs rendered entirely from fields), 2026-10-17. The client
account had never been used; the pass turned a 4,500px case study screen into four tabs and caught a data-loss
bug that no one had seen, because no one had ever clicked Update as the client.*

---

## What you get

- Edit screens with no dead boxes, related fields side by side, long groups in left tabs.
- Help text written for the client: what the field does on the page, in plain words.
- Fields that are yours (legal identity, schema data, link targets) read-only for the client, **enforced on save**.
- Revisions capped site-wide and invisible to the client, while still saving for you.
- Proof, from a save test run as the client's role, that saving every screen changes nothing it shouldn't.

---

## The method

### 1. See it as the role

Screenshots from an admin account show the wrong screens: different menus, different boxes, different Screen
Options. Log in as the role from the CLI with a short-lived session, then drive a headless browser.

```bash
# A session for the role's test user (ID 3 here), cookies to a 600 file outside every webroot. Never echo them.
umask 077; wp eval '
$uid = 3; $exp = time() + 2 * HOUR_IN_SECONDS;
$tok = WP_Session_Tokens::get_instance( $uid )->create( $exp );
echo json_encode( [ "token" => $tok, "cookies" => [
  [ "name" => SECURE_AUTH_COOKIE, "value" => wp_generate_auth_cookie( $uid, $exp, "secure_auth", $tok ) ],
  [ "name" => LOGGED_IN_COOKIE,   "value" => wp_generate_auth_cookie( $uid, $exp, "logged_in",   $tok ) ] ] ] );' > "$SCRATCH/role-cookies.json"
```

`npm install puppeteer-core` in a scratch directory, point it at any installed Chrome or headless shell, set
both cookies (`secure: true`, the site's domain), and for each screen take a full-page screenshot plus a box
inventory:

```js
// visible boxes and what Screen Options offers: the inventory the screenshot can't give you
[...document.querySelectorAll('.postbox')].filter(b => b.offsetParent).map(b => b.id + ':' + b.querySelector('h2')?.textContent.trim())
[...document.querySelectorAll('#adv-settings input.hide-postbox-tog')].map(i => i.value + (i.checked ? '+' : '-'))
```

In the block editor, wait for `wp.data.select('core/editor').getCurrentPostId()` and read the sidebar rows
(`.editor-post-panel__row-label`) and panels (`.editor-sidebar .components-panel__body-title`).

When done: `WP_Session_Tokens::get_instance( $uid )->destroy( $token )` and delete the cookie file. The client's
own sessions are untouched.

### 2. Inventory the fields

One dump of every ACF group (type, name, label, width, required, help length) shows the one-per-line runs and
the jargon faster than clicking through tabs:

```bash
wp eval 'foreach ( acf_get_field_groups() as $g ) {
  echo "\n## {$g["title"]} ({$g["key"]})\n";
  $walk = function ( $fields, $d ) use ( &$walk ) { foreach ( $fields as $f ) {
    $ins = trim( $f["instructions"] ?? "" );
    printf( "%s%-12s %-26s w=%-3s req=%d %s\n", str_repeat( "  ", $d ), $f["type"], $f["name"] ?: "-",
      $f["wrapper"]["width"] ?? "", ! empty( $f["required"] ), $ins ? "ins: " . substr( $ins, 0, 90 ) : "" );
    if ( ! empty( $f["sub_fields"] ) ) $walk( $f["sub_fields"], $d + 1 );
  } };
  $walk( acf_get_fields( $g["key"] ), 1 );
}'
```

Plugin option pages (Advanced Themer and the like) show up too; skip any the role can't reach.

### 3. Audit, propose, ask

Run the checklist below against the screenshots and the dump. Present it as numbered proposals; most are
yours to make, but **ask** about locks, about anything that hides a field she might expect to edit, and about
trimming the block editor (see the checklist).

### 4. Apply, then prove it with a save test as the role

The save test is not optional. It is how the pass found a bug that had been live since the content went in.

1. Snapshot everything a save could touch: for each test post, title, excerpt, content, menu order, slug,
   status, every meta row (minus `_edit_lock` / `_edit_last`) and its terms **in `term_order`**; plus every
   `options_%` row.
2. As the role, open each edit screen and click Update with no edits (and Save on each options page).
3. Snapshot again and diff.

**Expected noise, not bugs:**
- The first editor save of a rich-text field stores WordPress's format: line breaks instead of `<p>`, `&#039;`
  as `'`, `&` as `&amp;`. It renders the same; check one page to be sure.
- An ACF text field saved through the admin stores `&` as `&amp;`. Also renders the same.
- `post_modified`, `_edit_last`, `_edit_lock`, and `_encloseme` on posts.
- An empty bidirectional relationship field that never had a row gains one (`""`).

**Anything else is a finding.** On SLVPR it was outlet order: the taxonomy box saved terms alphabetically (recipe
below).

### 5. Force-test every lock

A lock that's only `readonly` in the DOM isn't one. For each locked field, remove the attribute in the
browser, change the value, save, and confirm the stored value didn't move. Same for locked term slugs.

---

## Audit checklist

**Dead weight**
- [ ] **Content editor and Featured image on field-driven CPTs.** If no post of the type has `post_content` or
  `_thumbnail_id`, and no template uses `post-content` / `{featured_image}` for it, drop `editor` and
  `thumbnail` from `supports`. An empty editor at the top of the screen invites typing that never appears.
  ```bash
  wp db query "SELECT post_type, SUM(post_content<>'') content, SUM(EXISTS(SELECT 1 FROM wp_postmeta m
    WHERE m.post_id=p.ID AND m.meta_key='_thumbnail_id')) thumb, COUNT(*) n FROM wp_posts p
    WHERE post_type IN ('service','case_study') AND post_status='publish' GROUP BY post_type"
  ```
- [ ] **Blog features nobody uses:** Tags (0 terms), comments and trackbacks (closed), the Author panel (when
  the byline comes from a field, not the WordPress user). Check the front end reads none of them first.
- [ ] **Hidden fields that still render** (a plugin's options page the role can reach, a metabox for a disabled
  feature).

**Layout**
- [ ] **One field per line** where two belong together: name + sector (60/40), number + label (30/70), first +
  last name (50/50), paired profile URLs (50/50), two relationship pickers (50/50).
- [ ] **Order breaks rows.** A full-width field between two paired fields splits the pair onto separate lines.
  Reorder, don't just set widths.
- [ ] **Long groups → left tabs by task** (`'placement' => 'left'`): e.g. Client / Story / Results / Connections.
- [ ] **Native boxes renamed for what they are on this site:** the excerpt as "Card summary" under the title,
  Post Attributes as "Display order", a shared taxonomy box titled per post type.
- [ ] **Image previews capped** so a portrait doesn't fill the screen.

**Help text**
- [ ] Says what the field does **on the page, and where** ("Shown under the page title").
- [ ] Ends with what empty means, where that matters ("Empty: the section isn't shown").
- [ ] No developer vocabulary: schema, `legalName`, meta/OG, `sameAs`, `tel:`, utm, H1, excerpt, lede,
  anchor, "inline-variable SVG". Labels use the client's words ("Opening paragraph", not "Lede").
- [ ] No house rules addressed to the client ("Only what is true", "Use verbatim"). Those are yours; keep them
  in the project docs.
- [ ] Doesn't talk about the client in the third person on her own screen ("her opening paragraphs"). A neutral
  name is fine where several people may edit ("the founder's portrait").
- [ ] Replace WordPress's generic core help where it's wrong for the site (the category description is
  "not prominent… some themes may show it" — on a site that uses it as the archive intro, it's wrong).

**Ownership**
- [ ] **Fields that are yours → read-only for the role, enforced on save:** legal and display name, year
  founded, schema logo, machine-readable hours. *Ask.*
- [ ] **Values that links depend on → generated, hidden, never changed:** in-page anchors the menu links to;
  term slugs that form archive URLs (WordPress doesn't redirect a renamed term). *Ask.*
- [ ] **Hierarchy the site doesn't use:** hide Parent Category if categories aren't nested.

**Revisions**
- [ ] Cap site-wide (5 is the house number) and hide from the role. Keep them saving: they are your safety net
  and, on Bricks, the way you prove a builder save (`03`).

**Block editor**
- [ ] **No H1 in the heading block** when the post title is the H1 (and a TOC reads H2s). Always.
- [ ] **Don't trim the block inserter by default.** A client who knows Gutenberg reads a short list as being
  hemmed in. Offer it, with the list of blocks the post template actually styles, and let the agency decide.

---

## Recipes

Prefix everything (`prefix_`); gate client-only behavior on the role helper from the role playbook
(`prefix_is_business_manager()` / `prefix_is_site_manager()`). Layout recipes apply to everyone, so you see what
the client sees. File them in `inc/edit-screens.php`; revisions-hiding sits with the role in
`inc/admin-experience.php`.

### Card summary: the excerpt as its own box under the title

```php
add_action( 'add_meta_boxes', function ( $post_type ) {
	if ( in_array( $post_type, array( 'service', 'case_study' ), true ) ) {
		remove_meta_box( 'postexcerpt', $post_type, 'normal' );
	}
}, 20 );

add_action( 'edit_form_after_title', function ( $post ) {
	$help = array( 'service' => 'One or two sentences. Shown on service cards and in the Services menu.' );
	if ( ! isset( $help[ $post->post_type ] ) ) return;
	?>
	<div id="prefix-card-summary" class="postbox" style="margin: 20px 0 0;">
		<div class="postbox-header"><h2 class="hndle">Card summary</h2></div>
		<div class="inside">
			<label class="screen-reader-text" for="excerpt">Card summary</label>
			<textarea rows="2" name="excerpt" id="excerpt" style="width: 100%;" aria-describedby="prefix-cs-help"><?php echo esc_textarea( $post->post_excerpt ); ?></textarea>
			<p class="description" id="prefix-cs-help"><?php echo esc_html( $help[ $post->post_type ] ); ?></p>
		</div>
	</div>
	<?php
} );
```

Core saves `$_POST['excerpt']` to `post_excerpt` itself; no save handler needed.

### Renaming a core box: in place, never remove-and-re-add

⚠️ `remove_meta_box()` sets the box to `false` so nothing can bring it back, and `add_meta_box()` with that id
returns early. Remove-and-re-add **deletes** the box.

```php
add_action( 'add_meta_boxes', function ( $post_type ) {
	global $wp_meta_boxes;
	$titles = array( 'pageparentdiv' => 'Display order' );
	if ( 'location' === $post_type ) $titles['tagsdiv-outlet'] = 'Local media';
	foreach ( $titles as $id => $title ) {
		foreach ( (array) ( $wp_meta_boxes[ $post_type ]['side'] ?? array() ) as $priority => $boxes ) {
			if ( ! empty( $boxes[ $id ] ) ) $wp_meta_boxes[ $post_type ]['side'][ $priority ][ $id ]['title'] = $title;
		}
	}
}, 20 );
// Help inside Post Attributes:
add_action( 'page_attributes_misc_attributes', fn() => print '<p class="description">Lower numbers show first.</p>' );
// A taxonomy box's help line is its label: 'separate_items_with_commas' => 'Separate outlets with commas.'
```

### A sorted taxonomy keeps its order through a save

⚠️ With `'sort' => true` alone, the tag box lists terms by name and Update writes that alphabetical order back
over the entry order. Any client save silently re-sorts. Add the `args`:

```php
register_taxonomy( 'outlet', array( 'case_study' ), array(
	'sort' => true,
	'args' => array( 'orderby' => 'term_order' ),   // get_the_terms() and the edit box follow entry order
	// …
) );
```

Prove it: `get_terms_to_edit( $id, 'outlet' )` lists them in entry order; save as the role; re-read with
`wp_get_object_terms( $id, 'outlet', [ 'orderby' => 'term_order' ] )`.

### Image previews

⚠️ ACF writes the preview size inline on the wrapper (`max-width: 768px`) and `max-height: 100%` on the
`<img>`, so a plain selector loses. The one legitimate `!important`:

```php
add_action( 'admin_head', fn() => print '<style>.acf-field.acf-field-image .acf-image-uploader .image-wrap { max-width: 240px !important; }</style>' );
```

### Locked fields: read-only for the role, enforced on save

```php
foreach ( array( 'field_prefix_business_name', 'field_prefix_business_legal_name' ) as $key ) {
	add_filter( "acf/prepare_field/key={$key}", function ( $field ) {
		if ( ! prefix_is_site_manager() ) return $field;
		$field['readonly']          = 1;
		$field['required']          = 0;
		$field['wrapper']['class'] .= ' prefix-locked';
		$field['instructions']      = trim( $field['instructions'] . ' Set by your web team; ask them to change it.' );
		return $field;
	} );
	// The lock that holds: whatever is posted, the role's save keeps the stored value.
	add_filter( "acf/update_value/key={$key}", function ( $value, $post_id, $field ) {
		return prefix_is_site_manager() ? get_option( 'options_' . $field['name'] ) : $value;   // options page; get_post_meta() on a post
	}, 10, 3 );
}
// Image, checkbox and time fields have no readonly state; dim them and drop their buttons for the role:
// .prefix-locked .acf-input { pointer-events: none; opacity: .7; } .prefix-locked .acf-actions,
// .prefix-locked .acf-image-uploader .acf-button { display: none; }
```

### Link targets: generated once, hidden, never changed

```php
// Fill an empty anchor from its row's title after ACF saves the repeater; existing anchors are never touched.
add_action( 'acf/save_post', function ( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'service' !== get_post_type( (int) $post_id ) ) return;
	$rows = get_field( 'supporting_services', (int) $post_id, false );   // raw rows, keyed by sub-field KEY
	if ( ! is_array( $rows ) ) return;
	$t = 'field_prefix_support_title'; $a = 'field_prefix_support_anchor';
	$taken = array_filter( array_map( fn( $r ) => (string) ( $r[ $a ] ?? '' ), $rows ) ); $changed = false;
	foreach ( $rows as $i => $r ) {
		if ( '' !== trim( (string) ( $r[ $a ] ?? '' ) ) || '' === trim( (string) ( $r[ $t ] ?? '' ) ) ) continue;
		$base = sanitize_title( $r[ $t ] );
		for ( $anchor = $base, $n = 2; in_array( $anchor, $taken, true ); $n++ ) $anchor = "$base-$n";
		$rows[ $i ][ $a ] = $taken[] = $anchor; $changed = true;
	}
	if ( $changed ) update_field( 'field_prefix_support', $rows, (int) $post_id );
}, 20 );
// Hidden for the role but still posted, so existing values survive: readonly + ACF's own hidden class.
add_filter( 'acf/prepare_field/key=field_prefix_support_anchor', function ( $f ) {
	if ( prefix_is_site_manager() ) { $f['readonly'] = 1; $f['wrapper']['class'] .= ' acf-hidden'; }
	return $f;
} );
```

Test the fill on a throwaway draft: two rows with the same title and one with an anchor already set should
come back `title`, `title-2`, and the existing anchor unchanged. Delete the draft.

**Term slugs** (category archive URLs): read-only on the edit screen for the role, and enforced:

```php
add_filter( 'wp_update_term_data', function ( $data, $term_id, $taxonomy ) {
	if ( 'category' === $taxonomy && prefix_is_site_manager() ) {
		$term = get_term( $term_id, 'category' );
		$data['slug']   = $term->slug;
		$data['parent'] = $term->parent;   // if Parent Category is hidden too
	}
	return $data;
}, 10, 3 );
```

### Revisions: capped for everyone, invisible to the role

```php
// After Bricks (priority 10 sets 100 for templates and Bricks-edited posts). 0 stays 0; unlimited becomes 5.
add_filter( 'wp_revisions_to_keep', fn( $num ) => 0 === (int) $num ? 0 : 5, 20 );
```

For the role, close every way in:

| Where | How |
|---|---|
| Revisions box (classic screens) | `remove_meta_box( 'revisionsdiv', $type, 'normal' )` on `add_meta_boxes`, 9999 |
| "Revisions: N Browse" in the Publish box | core prints it with no filter: `#submitdiv .misc-pub-revisions { display: none; }` on `admin_head-post.php` |
| Block editor panel | the editor shows it only from the post's REST links. Drop them: `$response->remove_link( 'version-history' ); $response->remove_link( 'predecessor-version' );` on `rest_prepare_{$type}` for every `show_in_rest` type |
| `revision.php` | add to the role playbook's `admin_init` URL guard |
| REST `/revisions` routes | `rest_pre_dispatch`: 403 when the route matches `#/revisions(/|$)#`. Autosaves are a separate route and keep working |

Verify as the role: `wp.data.select('core/editor').getCurrentPostRevisionsCount()` is 0 on a post that has
revisions, `revision.php` redirects, and a fetch of `/wp-json/wp/v2/posts/<id>/revisions` answers 403.
Existing revisions past the cap are trimmed the next time each post is saved; no cleanup needed.

### Blog: features off, headings without H1

```php
add_action( 'init', function () {
	unregister_taxonomy_for_object_type( 'post_tag', 'post' );   // check: 0 tags, and the front end reads none
	remove_post_type_support( 'post', 'comments' );
	remove_post_type_support( 'post', 'trackbacks' );
	remove_post_type_support( 'post', 'author' );               // only where the byline comes from a field
}, 20 );

add_filter( 'register_block_type_args', function ( $args, $name ) {
	if ( 'core/heading' === $name ) $args['attributes']['levelOptions']['default'] = array( 2, 3, 4, 5, 6 );
	return $args;
}, 10, 2 );
```

### Rewriting core help text on one screen

`gettext`, scoped to the screen and the exact source string (curly quotes come through as entities):

```php
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	global $pagenow;
	if ( 'default' !== $domain || ! in_array( $pagenow, array( 'edit-tags.php', 'term.php' ), true )
		|| 'category' !== ( $_GET['taxonomy'] ?? '' ) ) return $translation;
	if ( 'The description is not prominent by default; however, some themes may show it.' === $text ) {
		return 'Shown under the title on this category\'s page.';
	}
	return $translation;
}, 10, 3 );
```

---

## Customization checklist per project

- [ ] Role playbook done and verified first; reuse its role helper here.
- [ ] Session + screenshots + box inventory for every screen the role can open (posts, each CPT, each options
  page, categories list and edit, the block editor).
- [ ] Field dump; checklist run; proposals numbered; locks, link targets and block trimming asked, not assumed.
- [ ] Dead boxes checked against data **and** templates before removing supports.
- [ ] Save test as the role on one post per type, each options page, and a category; diff explained line by line.
- [ ] Every lock force-tested.
- [ ] Test session destroyed, cookie file deleted.
- [ ] The project's `CLAUDE.md` records what the role sees and what's locked, so the next session doesn't undo it.
