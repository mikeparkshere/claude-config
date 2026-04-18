# Claude Code × Bricks — Direct Edit Playbook

**Michael Parks Design — how to safely drive Bricks through Claude Code (WP-CLI, direct DB mutations) when the paste-to-Bricks workflow isn't the right tool.**

Version 1.0 — Captured April 2026 from the ext-mem video site build session. Companion to `wireframe-to-bricks-rules.md` (Claude.ai paste workflow) and `wireframe-to-bricks-system-design.md` (workflow reasoning).

---

## 1. When to use which surface

| Task | Surface |
|---|---|
| Generating new element trees from wireframes | **Claude.ai clipboard paste** (`wireframe-to-bricks-rules.md`) |
| Building or extending a template with Bricks UI controls available | **Bricks builder** (visual, authoritative) |
| Bulk ops: batch class creation, global class CSS population, custom query types, dynamic tags, CPT scaffolding, ACF fields, admin columns | **Claude Code direct DB edit** (this doc) |

The surfaces are complementary, not substitutes. When direct-DB edits stall (Bricks stripping your writes, specificity wars, missing schema knowledge), the fix is usually to drop down to the builder for schema discovery, then resume via Claude Code.

---

## 2. The Golden Rule

**Never guess Bricks internal schemas. Build ONE example of the thing you need in the builder, save, read it back, then replicate that shape programmatically.**

This is the single most reliable principle for direct-DB work. It's also the core insight of [`wpgaurav/bricks-skills`](https://github.com/wpgaurav/bricks-skills) — the repo teaches clipboard-paste JSON generation but the method applies identically here: use the builder's own output as the authoritative shape, not docs or guesses.

Discovery workflow:
1. Open the target template in the Bricks builder
2. Add the minimum version of the element you need (e.g., one Query Loop block with only Query Type set)
3. Save the template
4. Read it back via `wp post meta get <id> _bricks_page_content_2 --format=json`
5. Use the returned shape as the schema template

Every schema guess in this session that wasn't discovered this way got silently rejected by Bricks' JS-side validator on the next builder load. Every schema discovered this way stuck.

---

## 3. Authentication — non-negotiable for post meta writes

Bricks hooks `update_post_metadata` and **returns false when `current_user_can_use_builder()` fails**. WP-CLI runs with no current user by default, so direct `update_post_meta()` on `_bricks_page_content_2` (or header/footer equivalents) silently no-ops — no error, no warning, your write just doesn't land.

Fix: first line of any WP-CLI script that writes Bricks template meta:

```php
wp_set_current_user( 1 );  // or any user ID with builder access
```

`bricks_global_classes` option writes are **not** gated this way — you can update that option without `wp_set_current_user()`. Useful to know when isolating failure modes.

---

## 4. Authoritative schemas learned

Each of these was discovered via the Golden Rule above. Treat them as verified; treat anything not here as needing fresh discovery.

### 4.1 Query Loop — Posts

```json
{
  "hasLoop": true,
  "query": {
    "objectType": "post",
    "post_type": ["video"],
    "exclude_current_post": true
  }
}
```

- `post_type` is an **array**, not a string
- Exclude current post: use `exclude_current_post: true`. **Never** `post__not_in: ["{post_id}"]` — dynamic tag doesn't parse inside that array, and the invalid shape may cause Bricks to reject the whole query on builder load
- Bricks fills in `posts_per_page`, `orderby`, `order` from main-query defaults if omitted

### 4.2 Query Loop — Custom (registered via filter)

```json
{
  "hasLoop": true,
  "query": {
    "objectType": "your_custom_type"
  }
}
```

Custom types register via `bricks/setup/control_options`:

```php
add_filter( 'bricks/setup/control_options', function( $opts ) {
    $opts['queryTypes']['your_custom_type'] = __( 'Your Label', 'textdomain' );
    return $opts;
});

add_filter( 'bricks/query/run', function( $results, $query ) {
    if ( $query->object_type !== 'your_custom_type' ) return $results;
    return array( /* iterable of objects */ );
}, 10, 2 );
```

### 4.3 Image with dynamic src

```json
{
  "image": {
    "useDynamicData": "{acf_field_name}"
  },
  "altText": "{post_title}"
}
```

- `useDynamicData` is inside `image`, but `altText` is at the top level of settings
- Confirmed path for ACF URL fields via `get_field()` under the hood

### 4.4 Link (on container tag=a, or on button)

```json
{
  "link": {
    "type": "meta",
    "useDynamicData": "{post_url}"
  }
}
```

- The Link control on Container/Block elements **only appears when HTML tag is `a`**. Change tag first, Link appears.
- For non-dynamic external URLs: `"type": "external", "url": "..."`

### 4.5 Block HTML tag options (native, no `customTag` needed)

`div`, `section`, `a`, `article`, `nav`, `ol`, `ul`, `li`, `aside`, `address`, `figure`, `custom`

For anything not in this list (e.g., `dl`, `dt`, `dd`), use `"tag": "custom"` plus `"customTag": "dl"`.

### 4.6 Bricks Conditions (hide-when-empty et al.)

```json
{
  "_conditions": [
    [
      {
        "id": "random6char",
        "key": "dynamic_data",
        "dynamic_data": "{acf_field}",
        "compare": "empty_not"
      }
    ]
  ]
}
```

Nested array shape: outer array = AND groups, inner arrays = OR rules within each AND group. For simple "hide if field empty", one outer array with one inner rule.

### 4.7 Global Class shape

```json
{
  "id": "6-char-alphanumeric",
  "name": "class-name",
  "settings": {
    "_cssCustom": "/* CSS goes here */"
  },
  "modified": 1776526083000,
  "user_id": 1
}
```

**`settings` must be `array()` — never `new stdClass()`.** A stdClass here crashes Bricks' interactions loader (`includes/interactions.php:512`, "Cannot use object of type stdClass as array") on every subsequent request. Fatal site-wide, requires direct-MySQL recovery to fix.

Custom CSS stored in `_cssCustom` uses literal class selectors (`.my-class { ... }`), not `%root%`. Bricks substitutes `%root%` → literal at UI save time, but the stored value is always literal.

### 4.8 Custom dynamic tags

```php
add_filter( 'bricks/dynamic_tags_list', function( $tags ) {
    $tags[] = array(
        'name'  => '{my_tag}',
        'label' => 'My Tag',
        'group' => 'My Group',
    );
    return $tags;
});

add_filter( 'bricks/dynamic_data/render_tag', function( $tag, $post, $context = 'text' ) {
    if ( $tag !== '{my_tag}' ) return $tag;
    return 'resolved value';
}, 10, 3 );

// Needed separately for when the tag appears inside larger content strings
add_filter( 'bricks/dynamic_data/render_content', function( $content, $post, $context = 'text' ) {
    if ( false === strpos( $content, '{my_tag}' ) ) return $content;
    return str_replace( '{my_tag}', 'resolved value', $content );
}, 10, 3 );
```

Inside the render filter, `\Bricks\Query::get_loop_object()` returns the current loop item if called during a loop iteration.

### 4.9 ACF field name convention in Bricks dynamic tags

Bricks dynamic tags use **underscore**, not colon: `{acf_video_duration}`, not `{acf:video_duration}`. This is a common trip-up when writing from paste-workflow habits.

---

## 5. Failure modes (time sinks we hit today)

### 5.1 Silent strip on next builder load

**Symptom:** PHP write reports success, readback via WP-CLI confirms the new element, but after the user opens the builder the element reverts or disappears.

**Cause:** Bricks' JS-side tree validator runs when the builder loads the template. Unknown settings keys or malformed values get silently dropped; the cleaned tree is re-saved when the builder next writes (auto-save or explicit).

**Fix:** Use the Golden Rule. Any schema guess that's not builder-output-verified will get stripped.

### 5.2 Global Class crashes site

**Symptom:** Site returns "Critical error" on every request after writing to `bricks_global_classes`. Error log shows `Cannot use object of type stdClass as array` in `bricks/includes/interactions.php`.

**Cause:** `'settings' => new stdClass()` instead of `'settings' => array()`.

**Recovery:** WordPress won't bootstrap, so WP-CLI fails. Fix directly via MySQL PDO:

```bash
mysql --socket="$SOCK" -uroot -proot local  # or your local's MySQL socket
```

Read `wp_options.option_value` for `bricks_global_classes`, `unserialize()` it in a standalone PHP script, surgically remove or fix the stdClass entries, `serialize()` and write back.

**Prevention:** `'settings' => array()` always. Even when setting the field later and initially you just need a placeholder.

### 5.3 Stretched-link patterns break subtly

The clickable-card pattern (ACSS `.clickable-parent` or custom `::after`) has at least three ways to silently break:

1. **Specificity**: ACSS's `.clickable-parent:not(a)` at `(0,1,1)` beats a plain `.card { position: relative }` at `(0,1,0)`. Use compound `.card.clickable-parent { position: relative }` to climb to `(0,2,0)`.

2. **clip-path on the anchor**: a visually-hidden anchor using `clip-path: inset(50%)` clips its own pseudo-elements. The stretched `::after` renders into an invisible box regardless of its absolute positioning.

3. **position: absolute on the anchor**: makes the anchor its own containing block. The absolute `::after` then positions relative to the 1×1 anchor, not the card.

**Better pattern in practice:** skip stretched-link entirely when the card has inner links (e.g., taxonomy tags). Structure as:

```html
<li class="card">
  <a href="{post_url}" class="card__link">
    <!-- poster, title, anything that should share the primary click target -->
  </a>
  <p class="card__meta">
    <a href="...">inner link</a> · <a href="...">inner link</a>
  </p>
</li>
```

Visible anchor wraps primary clickable content. Secondary links as siblings. No pseudo-elements, no ACSS utility, no specificity wars. Valid HTML, cleanly pasteable.

### 5.4 Bricks doesn't parse dynamic tags in arbitrary query fields

Writing `post__not_in: ["{post_id}"]` doesn't work — Bricks doesn't resolve dynamic tags inside that array. Use the dedicated boolean `exclude_current_post: true` and Bricks injects the post ID at render time.

General rule: where Bricks has a native UI control for a setting, that setting has its own schema key. Don't try to reach the same behavior via WordPress-level keys (`post__not_in`, `meta_query` with dynamic strings) — use the Bricks key first.

---

## 6. ACSS specificity cheat sheet

Common selector specificity values when overriding ACSS utilities:

| Selector | Specificity |
|---|---|
| `.card` | `(0,1,0)` |
| `.card.clickable-parent` | `(0,2,0)` |
| `.card:hover` | `(0,2,0)` |
| `.clickable-parent:not(a)` | `(0,1,1)` — `:not(a)` adopts the specificity of `a` which is `(0,0,1)` |
| `.clickable-parent:not(a) a` | `(0,1,2)` |
| `.card .card__meta a` | `(0,2,1)` |

When ACSS utility rules override your custom-class rules, climb specificity via compound-class selectors rather than `!important`. `!important` trumps specificity but kills debuggability.

---

## 7. Patch: `wireframe-to-bricks-rules.md` Section 6.3

The current Section 6.3 ("Clickable Parent Pattern") contradicts Section 5.2 ("Avoid Features That Drop on Import") — it prescribes a structural `::after` for the stretched-link mechanism while Section 5.2 forbids structural pseudo-elements. It also doesn't address the specificity or `clip-path` gotchas.

**Proposed replacement for Section 6.3:**

> ### 6.3 Clickable Card Pattern
>
> For cards where the entire primary content (image + title) should be clickable, wrap that content in a visible `<a>` element rather than using a stretched-link pseudo. Secondary links (category tags, meta actions) live as siblings of the anchor, inside the same list item.
>
> ```html
> <li class="card">
>   <a href="{url}" class="card__link">
>     <div class="card__media">
>       <img src="…" alt="…">
>     </div>
>     <h3 class="card__title">Title</h3>
>   </a>
>   <p class="card__meta">
>     <a href="…">Category</a> · <a href="…">Tag</a>
>   </p>
> </li>
> ```
>
> This pattern avoids `::after` pseudo-elements (which drop on import per Section 5.2), avoids nested anchors, and lets inner links navigate independently without specificity battles against the primary click surface.
>
> **Alternative — ACSS `.clickable-parent` utility** — is available when the card has *no* inner links (purely primary content). Apply `.clickable-parent` to the list item. Requires:
>
> - A direct-child `<a>` inside the card (not wrapped in a heading — direct descendant)
> - A positioning context: `.card.clickable-parent { position: relative; }` (compound selector needed to beat ACSS's `.clickable-parent:not(a) { position: static }` at equal specificity)
> - Direct-child anchor must NOT use `position: absolute` for its visual-hiding technique, and must NOT apply `clip-path` — both break the stretched pseudo
>
> Given these constraints, prefer the visible-anchor pattern above for most cases.

---

## 8. Related resources

- **`wpgaurav/bricks-skills`** ([repo](https://github.com/wpgaurav/bricks-skills)) — markdown skill files for Claude.ai and Claude Code covering Bricks JSON structure (`bricksCopiedElements` clipboard format), element shapes, breakpoint conventions, hooks. Intended workflow: AI generates Bricks JSON → user right-click-pastes into Structure panel. Complementary to this doc — bricks-skills covers the clipboard-paste generation path, this doc covers the direct-DB-edit path. Both rely on the same Golden Rule.
- **Bricks theme source** — when schemas aren't in your own install to read back, `bricks/includes/elements/` and `bricks/includes/query.php` are the authoritative reference.
- **ACSS compiled stylesheet** — `wp-content/uploads/automatic-css/automatic-bricks.css` — grep here to understand what utility classes exist and their exact selectors/specificity before writing overrides.

---

## 9. Changelog

**v1.0 — April 2026**

- Captured from the ext-mem video site build session: Query Loop schema discovery, stretched-link failure modes, `stdClass` site-crash recovery, ACSS specificity overrides, custom query/tag registration patterns
- Proposes reconciliation patch for `wireframe-to-bricks-rules.md` Section 6.3
- References `wpgaurav/bricks-skills` as the complementary clipboard-paste resource

*Future versions: document new Bricks versions' schema changes, expand the schema library as more element types are used, add element-level visual-settings schema (`_typography`, `_padding`, etc.) once they're discovered via the Golden Rule.*
