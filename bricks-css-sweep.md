# Bricks Pre-Launch CSS Sweep

**Michael Parks Design — Claude Code slash command**

Version 1.0 — Generated April 2026

---

## Purpose

Consolidates all page-level and element-level Custom CSS from a Bricks Builder page into the project's core functionality plugin, then empties the Bricks Custom CSS fields. Runs once per page at the Staging → Live boundary, after the page is visually locked and approved.

This is not a build-time operation. Do not run during active development.

---

## Prerequisites

Before invoking this prompt, confirm:

1. The page is on Staging and visually locked (client-approved, no pending revisions)
2. `CLAUDE.md` exists at the project root and documents:
   - The WordPress installation path
   - The core functionality plugin name and path
   - Database access method (WP-CLI availability, direct DB access, etc.)
3. The plugin has an `assets/css/` directory
4. The plugin's main PHP file enqueues `sections.css` with ACSS as a dependency (if not, Claude Code flags this and halts until added)
5. A backup of the page exists (Bricks template export, database backup, or site snapshot)

If any prerequisite fails, halt and report. Do not proceed with partial state.

---

## Invocation

```
/bricks-css-sweep
```

Claude Code prompts for:
- **Page ID or slug** (required)
- **Plugin path override** (optional — defaults to the plugin named in CLAUDE.md)
- **Confirmation the page is Staging-locked** (required, interactive y/n)

---

## Operation

The sweep runs in five phases with a verification gate between Phase 2 and Phase 3.

### Phase 1: Discovery and Extraction

1. Resolve the post ID from the slug if a slug was provided. Confirm the post exists and is a Bricks-authored page (`_bricks_page_content_2` meta key present).

2. Query `_bricks_page_settings` post meta. Extract any `customCss` value. Store as `page_level_css`.

3. Query `_bricks_page_content_2` post meta. Parse the element tree (it is a JSON array of element objects). Walk the tree and collect:
   - Every element's `settings._cssCustom` value, if non-empty
   - The element's `label` (if set) or `name` (element type)
   - The element's class list (from `settings._cssGlobalClasses` and `settings._cssClasses`)
   
   Store as `element_level_css` — an array of `{element_id, element_label, classes, css}` records.

4. **Do not touch** `bricks_global_classes` option. Global class CSS is out of scope for this sweep.

5. Present a discovery summary:

```
Page: {page_title} (ID: {post_id}, slug: {slug})
  Page-level Custom CSS: {X} bytes, {Y} selectors
  Element-level Custom CSS: {N} elements, total {Z} bytes
  Element breakdown:
    - {element_label_1} ({classes}): {bytes} bytes
    - {element_label_2} ({classes}): {bytes} bytes
    ...
  
  Proceed with consolidation? (y/n)
```

Wait for confirmation before Phase 2.

### Phase 2: Consolidation and File Generation

1. Organize extracted CSS by BEM block name. Block name is derived from the primary custom class on each element (the first class that is not an ACSS utility and not a Bricks internal).

2. For each block, consolidate the CSS rules. If the same selector appears in both page-level CSS and element-level CSS for the same block, merge them, with element-level rules winning on conflicts (since they are scoped to the specific instance). Flag any conflicts in the sweep report.

3. Read `{plugin_path}/assets/css/sections.css` if it exists.

4. For each block about to be written, check if the block's selector already exists in `sections.css`:
   - **No collision**: queue the block for append.
   - **Collision detected**: halt and prompt the developer:

```
COLLISION: Block '.service-hero' already exists in sections.css.
  
  Existing rules ({count} selectors):
    {preview of existing CSS}
  
  New rules from this page ({count} selectors):
    {preview of new CSS}
  
  Options:
    (1) Skip this block (keep existing sections.css content, do not sweep this block)
    (2) Append anyway (may create duplicate rules — only choose if blocks are genuinely different despite sharing a name)
    (3) Replace existing (overwrite the block in sections.css with the new rules)
    (4) Abort the sweep entirely
  
  Choice:
```

Wait for the developer's choice. Apply it. Continue for other blocks.

5. If no collisions or all collisions are resolved, write the consolidated CSS to `sections.css`. Format:

```css
/* ============================================
   Block: service-hero
   Added from page: service-landscaping
   Swept: 2026-04-18
   ============================================ */

.service-hero { ... }
.service-hero__header { ... }
.service-hero__col { ... }
/* ... rest of block ... */


/* ============================================
   Block: capabilities
   Added from page: service-landscaping
   Swept: 2026-04-18
   ============================================ */

.capabilities { ... }
/* ... */
```

If `sections.css` does not exist, create it with a file header:

```css
/*
 * sections.css
 * Core functionality plugin — {plugin_name}
 * Project section CSS, consolidated from Bricks Custom CSS at pre-launch.
 * Generated and maintained by Claude Code via /bricks-css-sweep.
 */
```

6. Report the write:

```
sections.css updated:
  Path: {full path}
  Blocks written: {N}
  Total size: {bytes}
  
  Collisions resolved: {count}
  Collisions skipped: {count}
  Collisions replaced: {count}
```

### Verification Gate (manual)

**Claude Code halts here.** Output to the developer:

```
Phase 2 complete. sections.css has been written.
Bricks Custom CSS has NOT been cleared yet.

Before proceeding:
  1. Clear any caching (object cache, page cache, CDN if applicable)
  2. Load the page on Staging in an incognito window
  3. Visually confirm the page renders identically to before the sweep
  4. If anything is broken, roll back sections.css (git revert or manual) and abort

Ready to clear Bricks Custom CSS? (yes/no/abort)
```

- `yes` → proceed to Phase 3
- `no` → pause, developer investigates, resume when ready (same answer prompt)
- `abort` → halt entirely. sections.css is already written; the developer decides whether to revert it manually. Bricks is untouched.

### Phase 3: Cleanup

1. Update `_bricks_page_settings` — set `customCss` to an empty string.

2. Walk `_bricks_page_content_2` — for every element that had a `_cssCustom` value extracted in Phase 1, set `_cssCustom` to an empty string. Write the modified element tree back to post meta.

3. Run `wp bricks regenerate_assets` if WP-CLI is available. If not, note that the developer should regenerate Bricks CSS manually via the Bricks settings page.

4. Report the cleanup:

```
Cleanup complete:
  Page-level Custom CSS: cleared
  Element-level Custom CSS: cleared from {N} elements
  Bricks assets regenerated: {yes/no/manual required}
```

### Phase 4: Enqueue Verification

1. Read the plugin's main PHP file. Check for the presence of an `wp_enqueue_style` call that:
   - Registers a handle (any handle name, but typically `{plugin-slug}-sections`)
   - Points to `assets/css/sections.css`
   - Has ACSS (`automatic-css` or similar) as a dependency
   - Uses `filemtime()` for the version parameter

2. If the enqueue exists and looks correct, confirm:

```
Enqueue verified:
  Handle: {handle}
  File: {path}
  Dependency: {acss_handle}
  Version: filemtime() (cache-busting active)
```

3. If the enqueue is missing or malformed, output the correct snippet and halt:

```
Enqueue missing or incorrect. Add this to {plugin_main_file}:

add_action('wp_enqueue_scripts', function() {
    $file = plugin_dir_path(__FILE__) . 'assets/css/sections.css';
    if (file_exists($file)) {
        wp_enqueue_style(
            '{plugin-slug}-sections',
            plugin_dir_url(__FILE__) . 'assets/css/sections.css',
            ['automatic-css'],
            filemtime($file)
        );
    }
}, 20);

The sweep is otherwise complete. Re-run to verify once the enqueue is added.
```

### Phase 5: Final Report

```
/bricks-css-sweep COMPLETE

Page: {page_title} ({slug})
sections.css: {path}
  Size: {bytes}
  Blocks: {list of block names added}
  
Bricks state: Custom CSS cleared at page and element levels
Enqueue: verified

Next steps:
  - Commit sections.css and plugin changes to version control
  - Promote Staging to Live
```

---

## Error Handling

- **Database access fails**: halt, report the failure, suggest running WP-CLI from inside the project directory or confirming database credentials.
- **Post not found or not a Bricks page**: halt, report.
- **sections.css write fails** (permissions, disk full): halt, report. Do not proceed to Phase 3.
- **Element tree parse fails** (malformed JSON in post meta): halt, report, recommend database backup restore.
- **Developer aborts at verification gate**: Phase 3 does not run. sections.css remains written. Developer handles rollback.

At every halt point, Claude Code reports clearly and does not attempt "smart" recovery. The sweep is a data migration — consistency matters more than completion.

---

## Notes on Scope

**What this sweep does NOT do:**

- Does not touch `bricks_global_classes` option (global class CSS stays in Bricks)
- Does not touch `bricks_theme_styles` option
- Does not touch `bricks_global_settings` (site-wide Custom CSS panel)
- Does not modify the enqueue PHP if it's missing (reports the snippet for manual addition)
- Does not handle CSS on multiple pages in a single invocation (run once per page)
- Does not minify CSS (Perfmatters handles production minification)

**What it assumes:**

- The developer is running this at pre-launch with a page in locked state
- A backup exists
- The plugin structure is standard for Michael Parks Design projects

---

## Changelog

**v1.0 — April 2026**
- Initial version
- Single `sections.css` per project, organized by block-name comment headers
- Per-page invocation with verification gate between write and cleanup
- Collision detection with developer-resolved handling
- Global classes out of scope; element and page-level Custom CSS in scope
