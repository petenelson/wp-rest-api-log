# Template Hierarchy Reference

WordPress follows a specific hierarchy when choosing which template to load. Block themes use the same hierarchy but with HTML files instead of PHP.

## Template Location

| Theme Type | Template Location | File Extension |
|------------|-------------------|----------------|
| Block theme | `/templates/` | `.html` |
| Classic theme | Root directory | `.php` |

## Hierarchy by Content Type

### Single Post

```
single-{post_type}-{slug}.html
single-{post_type}.html
single.html
singular.html
index.html
```

**Example:** For a `book` post with slug `my-book`:
1. `single-book-my-book.html`
2. `single-book.html`
3. `single.html`
4. `singular.html`
5. `index.html`

### Page

```
{custom-template}.html    (if assigned in editor)
page-{slug}.html
page-{id}.html
page.html
singular.html
index.html
```

### Archive

```
archive-{post_type}.html
archive.html
index.html
```

### Category

```
category-{slug}.html
category-{id}.html
category.html
archive.html
index.html
```

### Tag

```
tag-{slug}.html
tag-{id}.html
tag.html
archive.html
index.html
```

### Custom Taxonomy

```
taxonomy-{taxonomy}-{term}.html
taxonomy-{taxonomy}.html
taxonomy.html
archive.html
index.html
```

### Author

```
author-{nicename}.html
author-{id}.html
author.html
archive.html
index.html
```

### Date

```
date.html
archive.html
index.html
```

### Search Results

```
search.html
index.html
```

### 404 Not Found

```
404.html
index.html
```

### Home Page

**Front page (static):**
```
front-page.html
page.html
index.html
```

**Blog home (posts page):**
```
home.html
index.html
```

## Template Parts

Template parts are reusable sections stored in `/parts/`.

```html
<!-- Include a template part -->
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->
<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
<!-- wp:template-part {"slug":"sidebar","area":"sidebar"} /-->
```

### Template Part Areas

```
header   - Site header
footer   - Site footer
sidebar  - Sidebar
uncategorized - General parts
```

## Creating Custom Templates

### In Site Editor

1. Appearance → Editor → Templates
2. Add New → Choose template type
3. Build using blocks
4. Save

### As Files

Create HTML file in `/templates/` with block markup:

**templates/page-contact.html:**
```html
<!-- wp:template-part {"slug":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:post-title /-->
    <!-- wp:post-content /-->
    <!-- Custom contact form block here -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer"} /-->
```

### Custom Template Registration

Register in theme.json:

```json
{
  "customTemplates": [
    {
      "name": "page-contact",
      "title": "Contact Page",
      "postTypes": ["page"]
    },
    {
      "name": "blank",
      "title": "Blank Canvas",
      "postTypes": ["page", "post"]
    }
  ]
}
```

## Debugging Templates

### Find Current Template

```php
add_action('template_include', function($template) {
    error_log('Template loaded: ' . $template);
    return $template;
}, 999);
```

### Using Query Monitor

Install Query Monitor plugin - it shows which template is loaded and the full hierarchy.

### Check Block Theme Status

```php
// Is it a block theme?
if (wp_is_block_theme()) {
    // Block theme template handling
} else {
    // Classic theme handling
}
```

## Classic Theme Reference

For classic (PHP) themes, hierarchy is similar but uses PHP files:

```
single-{post_type}-{slug}.php
single-{post_type}.php
single.php
singular.php
index.php
```

### Hybrid Themes

Some themes support both:

```php
// Check and use appropriate method
if (wp_is_block_theme()) {
    // Let WordPress handle block template
} else {
    get_header();
    // Classic PHP template code
    get_footer();
}
```

## Common Template Queries

### Get Template Slug

```php
// In block themes
$template_slug = get_page_template_slug();

// Current template file
global $_wp_current_template_content;
```

### Force Specific Template

```php
add_filter('template_include', function($template) {
    if (is_page('special')) {
        $new_template = locate_template(['custom-special.html']);
        if ($new_template) {
            return $new_template;
        }
    }
    return $template;
});
```
