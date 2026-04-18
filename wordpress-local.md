I'm working on a WordPress site using Local (by Flywheel). The environment is fully configured with WP-CLI available.

CRITICAL - CURRENT SETUP:
- You are at the PROJECT ROOT: not in the WordPress directory
- WordPress installation is in: ./app/public/
- PHP error logs are in: ./logs/php/error.log
- To run WP-CLI commands, you MUST EITHER:
  a) cd app/public && wp [command]
  b) Use: (cd app/public && wp [command])

BUILD ISSUES:
- NODE_ENV is already set to: development
- For npm builds, always ensure NODE_ENV=development

DIRECTORY STRUCTURE:
.
├── app/
│   └── public/     # WordPress root (WHERE WP-CLI WORKS)
│       ├── wp-content/
│       ├── wp-config.php
│       └── ...
├── logs/
│   ├── nginx/
│   └── php/
│       └── error.log    # PHP errors
└── conf/    # Local configuration

WORKING WITH WP-CLI FROM PROJECT ROOT:
Since we're at project root, use subshell for WP-CLI:
- (cd app/public && wp plugin list)
- (cd app/public && wp cache flush)
- (cd app/public && wp db query "SELECT * FROM wp_options LIMIT 5")

EXAMPLES:
# Check logs from project root
cat logs/php/error.log

# Run WP-CLI from project root
(cd app/public && wp plugin list --status=active)

# Work on a plugin
cd app/public/wp-content/plugins/my-plugin
npm install

DEVELOPMENT PREFERENCES:
- I use ACF Pro for custom fields - ALWAYS use acf_add_local_field_group() for field registration
- I use Bricks Builder for page building
- Register field groups in PHP (not via UI) for version control
- I prefer ACF Local JSON enabled for field sync when applicable
- For custom post types and taxonomies, register them in the Site Functionality plugin (wp-content/plugins/site-functionality/)
- For options pages, organize fields using ACF Group fields for better admin organization
- Group related fields together: Company Info, Brand Assets, Contact Info, Social Links, etc.

For custom fields, ALWAYS use ACF Pro unless I specifically request custom meta boxes or WordPress native functions.

CSS/STYLE LOCATIONS (Bricks + ACSS + WPCodeBox):
When looking for CSS styles, check these locations in order:

1. Bricks Global Classes (most common for component styles):
   wp option get bricks_global_classes --format=json | grep -i "class_name"

2. Bricks Template Styles (inline styles on elements):
   - List templates: wp post list --post_type=bricks_template --format=table
   - Get template content: wp post meta get [ID] _bricks_page_header_2

3. ACSS Custom CSS:
   wp-content/uploads/automatic-css/automatic-custom-css.css

4. ACSS Variables:
   wp-content/uploads/automatic-css/automatic-variables.css

5. WPCodeBox Snippets (check for CSS snippets):
   wp post list --post_type=wpcodebox --format=table
   wp post get [ID] --field=post_content

6. Child Theme:
   wp-content/themes/bricks child theme/style.css

7. Bricks Page Settings (page-specific CSS):
   wp post meta get [PAGE_ID] _bricks_page_settings

QUICK CSS LOOKUP:
- For Bricks global class by name: wp option get bricks_global_classes --format=json | grep -i "class_name"
- For ACSS variables: grep "variable_name" wp-content/uploads/automatic-css/automatic-variables.css

SITE FUNCTIONALITY PLUGIN:
- All custom post types, ACF field groups, and options pages go in the Site Functionality plugin
- Plugin location: wp-content/plugins/site-functionality/
- Organized structure:
  - inc/post-types.php - Custom post type registrations
  - inc/acf-fields.php - ACF field groups for CPTs
  - inc/options-pages.php - ACF options page registrations
  - inc/options-fields.php - ACF field groups for options pages
  - inc/helper-functions.php - Helper functions for accessing options data

COMMON WORKFLOWS:
- Custom Post Types: Register in Site Functionality plugin (inc/post-types.php)
- Custom Taxonomies: Register in Site Functionality plugin alongside CPTs
- Custom Fields: Use ACF Pro with acf_add_local_field_group() in plugin (inc/acf-fields.php or inc/options-fields.php)
- Options Pages: Register in plugin (inc/options-pages.php) with fields in (inc/options-fields.php)
- Helper Functions: Create in plugin (inc/helper-functions.php) for easy access to options data
- Page Building: Bricks Builder with dynamic data from helper functions
- WooCommerce customizations: Custom code with hooks/filters

ACF FIELD GROUP TEMPLATE:
When creating ACF field groups, use this template in functions.php or a custom plugin:

```php
add_action( 'acf/include_fields', function() {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    acf_add_local_field_group( array(
        'key' => 'group_unique_key',
        'title' => 'Field Group Title',
        'fields' => array(
            // Text field
            array(
                'key' => 'field_unique_key_text',
                'label' => 'Text Field',
                'name' => 'text_field',
                'type' => 'text',
                'placeholder' => '',
            ),
            // Date picker
            array(
                'key' => 'field_unique_key_date',
                'label' => 'Date Field',
                'name' => 'date_field',
                'type' => 'date_picker',
                'display_format' => 'F j, Y',
                'return_format' => 'Y-m-d',
            ),
            // URL
            array(
                'key' => 'field_unique_key_url',
                'label' => 'URL Field',
                'name' => 'url_field',
                'type' => 'url',
                'placeholder' => 'https://',
            ),
            // True/False
            array(
                'key' => 'field_unique_key_bool',
                'label' => 'Boolean Field',
                'name' => 'boolean_field',
                'type' => 'true_false',
                'ui' => 1,
            ),
            // Gallery
            array(
                'key' => 'field_unique_key_gallery',
                'label' => 'Gallery',
                'name' => 'gallery',
                'type' => 'gallery',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ),
            // Image
            array(
                'key' => 'field_unique_key_image',
                'label' => 'Image',
                'name' => 'image',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
            ),
            // Textarea
            array(
                'key' => 'field_unique_key_textarea',
                'label' => 'Textarea',
                'name' => 'textarea',
                'type' => 'textarea',
                'rows' => 4,
            ),
            // WYSIWYG
            array(
                'key' => 'field_unique_key_wysiwyg',
                'label' => 'Content',
                'name' => 'content',
                'type' => 'wysiwyg',
                'tabs' => 'all',
                'toolbar' => 'full',
                'media_upload' => 1,
            ),
            // Select
            array(
                'key' => 'field_unique_key_select',
                'label' => 'Select',
                'name' => 'select_field',
                'type' => 'select',
                'choices' => array(
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                ),
            ),
            // Relationship
            array(
                'key' => 'field_unique_key_relationship',
                'label' => 'Related Posts',
                'name' => 'related_posts',
                'type' => 'relationship',
                'post_type' => array('post'),
                'return_format' => 'object',
            ),
            // Repeater
            array(
                'key' => 'field_unique_key_repeater',
                'label' => 'Repeater',
                'name' => 'repeater',
                'type' => 'repeater',
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_unique_key_repeater_title',
                        'label' => 'Title',
                        'name' => 'title',
                        'type' => 'text',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post_type_slug',
                ),
            ),
        ),
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'active' => true,
    ) );
} );
```

Please acknowledge that you understand you're at the project root and must run WP-CLI commands from app/public.
