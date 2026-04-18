# WordPress on Runcloud

You are helping with WordPress development on a Runcloud-hosted site.

## Environment Details

- **WordPress Root**: /home/runcloud/webapps/app-jbm26/
- **Error Logs**: `tail -f /home/runcloud/webapps/app-jbm26/logs/error_log`
- **Nginx Logs**: `/var/log/nginx/[domain].error.log`
- **SSH User**: runcloud

## Common Commands

- List plugins: `wp plugin list`
- Clear cache: `wp cache flush`
- Database queries: `wp db query "SELECT * FROM wp_posts LIMIT 5"`
- Export database: `wp db export backup.sql`
- Manage users: `wp user list`
- Check WP version: `wp core version`

## Best Practices

- Always work on staging sites first before touching live
- Back up the database before making schema changes
- Test ACF field updates on a few posts first
- Use WP-CLI for bulk operations when possible
- Check error logs after making changes: `tail -f logs/error_log`

## Directory Structure

- Themes: `wp-content/themes/`
- Plugins: `wp-content/plugins/`
- Uploads: `wp-content/uploads/`
