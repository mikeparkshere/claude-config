# WordPress on Runcloud
You are helping with WordPress development on a Runcloud-hosted server via VSCode Remote-SSH.

## CRITICAL — Session Startup (Do This First, Every Time)

When this slash command loads, immediately run:

```bash
ls /home/runcloud/webapps/
```

Then say:

> "This server has the following webapps: [list them]. Which one are we working on today?"

Wait for confirmation. Once confirmed:

**1. Set the working directory for this session:**
```bash
cd /home/runcloud/webapps/[confirmed-app-name]/
```

**2. Confirm you are now in the correct location:**
```bash
pwd
```

**3. Check for a CLAUDE.md project context file:**
```bash
cat CLAUDE.md
```

If `CLAUDE.md` exists, read it and acknowledge the project context before proceeding. If it does not exist, flag this to the user:

> "No CLAUDE.md found for this webapp. I have environment context but no project context. You may want to create one before we proceed — or tell me about the project now."

Do not proceed with any tasks until all three steps above are complete.

---

## Environment
- Connected via **Remote-SSH** as the `runcloud` webapp user
- WordPress root: `/home/runcloud/webapps/[APP_NAME]/`
- **WP-CLI runs directly from the WordPress root** — no subshell needed (unlike LocalWP)
- All file paths and WP-CLI commands are relative to the confirmed webapp root

---

## Directory Structure

```
/home/runcloud/webapps/[APP_NAME]/
├── wp-config.php
├── wp-content/
│   ├── plugins/
│   │   └── [prefix]-functionality/     ← Project core plugin
│   ├── themes/
│   │   └── bricks-child/               ← Child theme (lean — logic in core plugin)
│   └── uploads/
│       └── automatic-css/
│           ├── automatic-custom-css.css
│           └── automatic-variables.css
```

---

## WP-CLI

WP-CLI runs directly from the WordPress root as the `runcloud` user. No subshell required.

```bash
# Navigate to WordPress root first
cd /home/runcloud/webapps/[APP_NAME]/

# Then run WP-CLI directly
wp plugin list --status=active
wp cache flush
wp core version
wp db export backup.sql
wp user list
wp db query "SELECT * FROM wp_posts LIMIT 5"
```

---

## Logs & Debugging

```bash
# PHP / application errors
tail -f /home/runcloud/webapps/[APP_NAME]/logs/error_log

# Nginx errors
/var/log/nginx/[domain].error.log

# Always check error log after making changes
tail -50 /home/runcloud/webapps/[APP_NAME]/logs/error_log
```

---

## Development Preferences

- **ACF Pro** for all custom fields — ALWAYS use `acf_add_local_field_group()` for field registration, never UI-only
- **Bricks Builder** for page building with dynamic data from helper functions
- **ACF Local JSON** enabled for field sync and version control
- **ACSS v3.3.6** is the CSS framework — never work around it or replace it
- All custom post types, taxonomies, ACF fields, and options pages go in the **project core plugin**
- WPCodeBox snippets approved on staging must be migrated to the core plugin before go-live

---

## Site Functionality Plugin

Every project has a dedicated core plugin. All custom PHP lives here — never in functions.php.

```
wp-content/plugins/[prefix]-functionality/
├── [prefix]-functionality.php       ← Main plugin file, headers, constants
├── inc/
│   ├── post-types.php               ← CPT registrations
│   ├── taxonomies.php               ← Custom taxonomy registrations
│   ├── acf-fields.php               ← ACF field groups for CPTs
│   ├── options-pages.php            ← ACF options page registrations
│   ├── options-fields.php           ← ACF field groups for options pages
│   └── helper-functions.php        ← Helper functions for accessing options data
└── acf-json/                        ← ACF local JSON sync directory
```

Plugin header template:
```php
<?php
defined('ABSPATH') || exit;
/**
 * Plugin Name: [CLIENT NAME] — Core Functionality
 * Description: Site-specific functionality for [CLIENT NAME]
 * Version: 1.0.0
 * Author: Michael Parks Design
 */
```

---

## CSS / Style Locations (Bricks + ACSS + WPCodeBox)

When looking for CSS styles, check these locations in order:

```bash
# 1. Bricks Global Classes (most common for component styles)
wp option get bricks_global_classes --format=json | grep -i "class_name"

# 2. Bricks Template Styles
wp post list --post_type=bricks_template --format=table
wp post meta get [ID] _bricks_page_header_2

# 3. ACSS Custom CSS
cat wp-content/uploads/automatic-css/automatic-custom-css.css

# 4. ACSS Variables
cat wp-content/uploads/automatic-css/automatic-variables.css

# 5. WPCodeBox Snippets
wp post list --post_type=wpcodebox --format=table
wp post get [ID] --field=post_content

# 6. Child Theme
cat wp-content/themes/bricks-child/style.css

# 7. Bricks Page Settings (page-specific CSS)
wp post meta get [PAGE_ID] _bricks_page_settings
```

---

## Common Workflows

- **Custom Post Types** — Register in core plugin `inc/post-types.php`
- **Custom Taxonomies** — Register in core plugin alongside CPTs
- **Custom Fields** — Use ACF Pro with `acf_add_local_field_group()` in `inc/acf-fields.php` or `inc/options-fields.php`
- **Options Pages** — Register in `inc/options-pages.php` with fields in `inc/options-fields.php`
- **Helper Functions** — Create in `inc/helper-functions.php` for easy access to options data
- **Page Building** — Bricks Builder with dynamic data from helper functions

---

## ACF Field Group Template

Use this template when creating ACF field groups programmatically:

```php
add_action( 'acf/include_fields', function() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key'    => 'group_unique_key',
        'title'  => 'Field Group Title',
        'fields' => array(

            // Text
            array(
                'key'         => 'field_unique_key_text',
                'label'       => 'Text Field',
                'name'        => 'text_field',
                'type'        => 'text',
                'placeholder' => '',
            ),
            // Textarea
            array(
                'key'  => 'field_unique_key_textarea',
                'label' => 'Textarea',
                'name'  => 'textarea',
                'type'  => 'textarea',
                'rows'  => 4,
            ),
            // WYSIWYG
            array(
                'key'          => 'field_unique_key_wysiwyg',
                'label'        => 'Content',
                'name'         => 'content',
                'type'         => 'wysiwyg',
                'tabs'         => 'all',
                'toolbar'      => 'full',
                'media_upload' => 1,
            ),
            // Image
            array(
                'key'           => 'field_unique_key_image',
                'label'         => 'Image',
                'name'          => 'image',
                'type'          => 'image',
                'return_format' => 'array',
                'preview_size'  => 'medium',
            ),
            // Gallery
            array(
                'key'           => 'field_unique_key_gallery',
                'label'         => 'Gallery',
                'name'          => 'gallery',
                'type'          => 'gallery',
                'return_format' => 'array',
                'preview_size'  => 'medium',
            ),
            // URL
            array(
                'key'         => 'field_unique_key_url',
                'label'       => 'URL Field',
                'name'        => 'url_field',
                'type'        => 'url',
                'placeholder' => 'https://',
            ),
            // True/False
            array(
                'key'   => 'field_unique_key_bool',
                'label' => 'Boolean Field',
                'name'  => 'boolean_field',
                'type'  => 'true_false',
                'ui'    => 1,
            ),
            // Date picker
            array(
                'key'            => 'field_unique_key_date',
                'label'          => 'Date Field',
                'name'           => 'date_field',
                'type'           => 'date_picker',
                'display_format' => 'F j, Y',
                'return_format'  => 'Y-m-d',
            ),
            // Select
            array(
                'key'     => 'field_unique_key_select',
                'label'   => 'Select',
                'name'    => 'select_field',
                'type'    => 'select',
                'choices' => array(
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                ),
            ),
            // Relationship
            array(
                'key'           => 'field_unique_key_relationship',
                'label'         => 'Related Posts',
                'name'          => 'related_posts',
                'type'          => 'relationship',
                'post_type'     => array( 'post' ),
                'return_format' => 'object',
            ),
            // Repeater
            array(
                'key'        => 'field_unique_key_repeater',
                'label'      => 'Repeater',
                'name'       => 'repeater',
                'type'       => 'repeater',
                'layout'     => 'block',
                'sub_fields' => array(
                    array(
                        'key'   => 'field_unique_key_repeater_title',
                        'label' => 'Title',
                        'name'  => 'title',
                        'type'  => 'text',
                    ),
                ),
            ),

        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'post_type_slug',
                ),
            ),
        ),
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'active'          => true,
    ) );
} );
```

---

## Audit Mode

When this is an existing site audit session, operate as a **detective — read and document only**.
Do not modify any files without explicit instruction.

```bash
# Inventory: active plugins
wp plugin list --status=active --format=table

# Inventory: custom post types
wp post-type list --format=table

# Inventory: WPCodeBox snippets
wp post list --post_type=wpcodebox --format=table

# Inventory: ACF field groups (UI-registered)
wp post list --post_type=acf-field-group --format=table

# Scan functions.php for custom code
cat wp-content/themes/bricks-child/functions.php

# Search for custom functions across theme files
grep -r "function " wp-content/themes/ --include="*.php" -l

# Export ACF field groups to acf-json
wp acf export --all
```

---

## PHP Coding Standards

- Every PHP file begins with `defined('ABSPATH') || exit;`
- Prefix all functions, hooks, and globals with `[prefix]_`
- Sanitize and escape all inputs/outputs
- Apply capability checks where needed
- No logic in `functions.php` — child theme stays lean
- No custom code outside the core plugin on production

---

## Best Practices

- Always work on staging before touching live
- Back up the database before schema changes: `wp db export backup.sql`
- Check error logs after every change
- Test ACF field updates on a few posts before running bulk operations
- Use WP-CLI for all bulk operations
- WPCodeBox snippets must be migrated to the core plugin before go-live
