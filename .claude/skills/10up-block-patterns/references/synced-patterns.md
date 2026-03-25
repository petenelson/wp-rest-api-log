# Synced Patterns Reference

Synced patterns (formerly Reusable Blocks) are global block groups that update everywhere when edited.

## Creating Synced Patterns

### Via Editor

1. Select blocks in the editor
2. Click ⋮ menu → "Create pattern"
3. Choose "Synced" option
4. Give it a name

### Via Code

```php
add_action('init', function() {
    // Check if already exists
    $existing = get_posts([
        'post_type'   => 'wp_block',
        'name'        => 'site-announcement',
        'numberposts' => 1,
    ]);

    if (!empty($existing)) {
        return;
    }

    wp_insert_post([
        'post_title'   => 'Site Announcement',
        'post_name'    => 'site-announcement',
        'post_content' => '<!-- wp:group {"backgroundColor":"primary","textColor":"white","className":"announcement-banner"} -->
<div class="wp-block-group announcement-banner has-white-color has-primary-background-color has-text-color has-background">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Important announcement text here.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
        'post_status'  => 'publish',
        'post_type'    => 'wp_block',
    ]);
});
```

## Using Synced Patterns

### In Templates

Reference by ID:

```html
<!-- wp:block {"ref":123} /-->
```

### In PHP

```php
// Get synced pattern by slug
$pattern = get_page_by_path('site-announcement', OBJECT, 'wp_block');

if ($pattern) {
    echo apply_filters('the_content', $pattern->post_content);
}
```

### By Slug (Custom Function)

```php
function render_synced_pattern($slug) {
    $pattern = get_page_by_path($slug, OBJECT, 'wp_block');

    if (!$pattern) {
        return '';
    }

    return apply_filters('the_content', $pattern->post_content);
}

// Usage
echo render_synced_pattern('site-announcement');
```

## Synced Pattern with Overrides

WordPress 6.5+ supports content overrides in synced patterns.

### Enable Overrides

Add `"metadata": {"name": "field-name"}` to blocks:

```html
<!-- wp:group {"metadata":{"name":"announcement-group"}} -->
<div class="wp-block-group">
    <!-- wp:paragraph {"metadata":{"name":"announcement-text","bindings":{"__default":{"source":"core/pattern-overrides"}}}} -->
    <p>Default announcement text.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

### Override on Use

```html
<!-- wp:block {"ref":123,"content":{"announcement-text":{"content":"Custom text for this instance"}}} /-->
```

## Programmatic Updates

### Update Pattern Content

```php
function update_synced_pattern($slug, $new_content) {
    $pattern = get_page_by_path($slug, OBJECT, 'wp_block');

    if (!$pattern) {
        return false;
    }

    return wp_update_post([
        'ID'           => $pattern->ID,
        'post_content' => $new_content,
    ]);
}
```

### Update Pattern Title

```php
$pattern = get_page_by_path('site-announcement', OBJECT, 'wp_block');

if ($pattern) {
    wp_update_post([
        'ID'         => $pattern->ID,
        'post_title' => 'New Pattern Title',
    ]);
}
```

## Finding Synced Patterns

### All Synced Patterns

```php
$patterns = get_posts([
    'post_type'      => 'wp_block',
    'posts_per_page' => -1,
    'post_status'    => 'publish',
]);

foreach ($patterns as $pattern) {
    echo $pattern->post_title . ' (ID: ' . $pattern->ID . ')' . PHP_EOL;
}
```

### Search by Content

```php
$patterns = get_posts([
    'post_type'      => 'wp_block',
    's'              => 'announcement',
    'posts_per_page' => -1,
]);
```

## Export/Import

### Export Pattern

```php
function export_synced_pattern($slug) {
    $pattern = get_page_by_path($slug, OBJECT, 'wp_block');

    if (!$pattern) {
        return null;
    }

    return [
        'title'   => $pattern->post_title,
        'slug'    => $pattern->post_name,
        'content' => $pattern->post_content,
    ];
}
```

### Import Pattern

```php
function import_synced_pattern($data) {
    $existing = get_page_by_path($data['slug'], OBJECT, 'wp_block');

    if ($existing) {
        return wp_update_post([
            'ID'           => $existing->ID,
            'post_content' => $data['content'],
        ]);
    }

    return wp_insert_post([
        'post_title'   => $data['title'],
        'post_name'    => $data['slug'],
        'post_content' => $data['content'],
        'post_status'  => 'publish',
        'post_type'    => 'wp_block',
    ]);
}
```

## Best Practices

1. **Use meaningful slugs** for programmatic access
2. **Document pattern purpose** in the title
3. **Keep patterns focused** on single components
4. **Test updates** before deploying (affects all instances)
5. **Version control** pattern content when possible

## Synced Theme Patterns (Ignite WP)

Ignite WP Core provides a synced theme patterns feature that bridges database-stored synced patterns with version-controlled theme files.

### How It Works

1. **Theme File Source**: Store pattern content in `/synced-patterns/*.php` in your theme
2. **Auto-Sync to Database**: Files are synced to `wp_block` posts hourly (or manually)
3. **Version Controlled**: Pattern content lives in your theme repo, not the database
4. **Updates Propagate**: When theme files change, database patterns update automatically

### Creating a Synced Theme Pattern

Create a file in `themes/your-theme/synced-patterns/my-pattern.php`:

```php
<?php
/**
 * Title: My Custom Pattern
 * Slug: my-theme/my-custom-pattern
 * Description: A reusable announcement banner
 * Categories: theme, banners
 * Keywords: announcement, alert, banner
 * Viewport Width: 1200
 */
?>
<!-- wp:group {"backgroundColor":"primary","textColor":"white"} -->
<div class="wp-block-group has-white-color has-primary-background-color has-text-color has-background">
    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Important announcement text here.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

### Supported Header Fields

| Field | Description |
|-------|-------------|
| `Title` | Display name for the pattern |
| `Slug` | Unique identifier (required) |
| `Description` | Pattern description |
| `Categories` | Comma-separated category slugs |
| `Keywords` | Search keywords |
| `Viewport Width` | Preview width in editor |
| `Block Types` | Associated block types |
| `Post Types` | Restrict to specific post types |
| `Template Types` | Restrict to specific template types |

### Syncing Patterns

**Via WP-CLI:**

```bash
wp ignite-wp sync-theme-patterns
```

**Via Admin:**

Go to Tools → Synced Theme Patterns Sync and click "Sync Patterns Now".

**Automatic:**

Patterns sync automatically every hour via WordPress cron.

### Using Synced Theme Patterns

Once synced, patterns can be used like any synced pattern:

```html
<!-- wp:block {"ref":123} /-->
```

Or use the theme pattern slug directly:

```html
<!-- wp:pattern {"slug":"my-theme/my-custom-pattern"} /-->
```

### Dynamic Content in Patterns

Since pattern files are PHP, you can include dynamic content:

```php
<?php
/**
 * Title: Current Year Footer
 * Slug: my-theme/current-year-footer
 */

$year = date('Y');
?>
<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">© <?php echo esc_html($year); ?> My Company</p>
<!-- /wp:paragraph -->
```

### Transformation: Database to Theme

Ignite WP Development Mode can automatically transform `wp:block` references to `wp:pattern` references when saving templates:

**Before:**
```html
<!-- wp:block {"ref":123} /-->
```

**After:**
```html
<!-- wp:pattern {"slug":"my-theme/my-custom-pattern"} /-->
```

This allows patterns created in the database to be converted to version-controlled theme patterns.

### Benefits

| Feature | Database Synced | Synced Theme Patterns |
|---------|----------------|----------------------|
| Version Control | No | Yes |
| Deployment | Manual export/import | Theme deploy |
| Local Development | Requires DB sync | Works offline |
| Dynamic PHP | No | Yes |
| Multi-environment | Complex | Simple |

## Synced vs Regular Patterns

| Aspect | Synced Pattern | Regular Pattern | Synced Theme Pattern |
|--------|---------------|-----------------|---------------------|
| Storage | `wp_block` post | `/patterns/*.php` | `/synced-patterns/*.php` |
| Updates | Global, immediate | On deploy | On sync + deploy |
| Editing | Site Editor | Code only | Code + Site Editor |
| Use case | Dynamic content | Static layouts | Version-controlled dynamic |
| Version Control | Database | Theme repo | Theme repo |
