# Block Bindings API Reference

The Block Bindings API (WordPress 6.5+) allows binding block attributes to dynamic data sources.

## Core Binding Sources

WordPress provides built-in binding sources:

### core/post-meta

Bind to post meta fields:

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"subtitle"}}}}} -->
<p></p>
<!-- /wp:paragraph -->
```

**Supported blocks and attributes:**
- `core/paragraph` → `content`
- `core/heading` → `content`
- `core/image` → `url`, `alt`, `title`
- `core/button` → `url`, `text`, `linkTarget`, `rel`

### Register Meta for Binding

```php
register_post_meta('post', 'subtitle', [
    'show_in_rest' => true,
    'single'       => true,
    'type'         => 'string',
    'default'      => '',
]);
```

## Custom Binding Sources

### Register a Binding Source

```php
add_action('init', function() {
    register_block_bindings_source(
        'theme-name/site-options',
        [
            'label'              => __('Site Options', 'theme-name'),
            'get_value_callback' => 'theme_get_site_option_value',
            'uses_context'       => ['postId', 'postType'],
        ]
    );
});

function theme_get_site_option_value(array $source_args, $block_instance, string $attribute_name) {
    $key = $source_args['key'] ?? '';

    if (empty($key)) {
        return null;
    }

    switch ($key) {
        case 'phone':
            return get_option('theme_phone_number', '');
        case 'email':
            return get_option('theme_email_address', '');
        case 'address':
            return get_option('theme_address', '');
        default:
            return null;
    }
}
```

### Use in Patterns

```php
<?php
/**
 * Title: Contact Info
 * Slug: theme-name/contact-info
 */
?>
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"theme-name/site-options","args":{"key":"phone"}}}}} -->
<p></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"theme-name/site-options","args":{"key":"email"}}}}} -->
<p></p>
<!-- /wp:paragraph -->
```

## Using Block Context

Access contextual data in bindings:

```php
register_block_bindings_source(
    'theme-name/post-data',
    [
        'label'              => __('Post Data', 'theme-name'),
        'get_value_callback' => 'theme_get_post_data_value',
        'uses_context'       => ['postId', 'postType'],
    ]
);

function theme_get_post_data_value(array $source_args, $block_instance, string $attribute_name) {
    $key = $source_args['key'] ?? '';
    $post_id = $block_instance->context['postId'] ?? get_the_ID();

    switch ($key) {
        case 'author_name':
            $post = get_post($post_id);
            return get_the_author_meta('display_name', $post->post_author);

        case 'reading_time':
            $content = get_post_field('post_content', $post_id);
            $word_count = str_word_count(strip_tags($content));
            $minutes = ceil($word_count / 200);
            return sprintf(_n('%d min read', '%d min read', $minutes, 'theme-name'), $minutes);

        case 'category':
            $categories = get_the_category($post_id);
            return !empty($categories) ? $categories[0]->name : '';

        default:
            return null;
    }
}
```

## Binding in Block Markup

### Image with Dynamic Source

```html
<!-- wp:image {"metadata":{"bindings":{"url":{"source":"core/post-meta","args":{"key":"hero_image_url"}},"alt":{"source":"core/post-meta","args":{"key":"hero_image_alt"}}}}} -->
<figure class="wp-block-image"><img src="" alt=""/></figure>
<!-- /wp:image -->
```

### Button with Dynamic Link

```html
<!-- wp:button {"metadata":{"bindings":{"url":{"source":"core/post-meta","args":{"key":"cta_url"}},"text":{"source":"core/post-meta","args":{"key":"cta_text"}}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button"></a></div>
<!-- /wp:button -->
```

## Limitations

1. **Read-only** — Bindings are for display, not editing
2. **Supported blocks** — Only specific core blocks support bindings
3. **Attribute specific** — Each attribute must be bound separately
4. **No computed values** — Callbacks should return simple values

## Debugging

Check if bindings are registered:

```php
$sources = get_all_registered_block_bindings_sources();
var_dump(array_keys($sources));
```

Verify callback is working:

```php
function theme_get_site_option_value(array $source_args, $block_instance, string $attribute_name) {
    error_log('Binding called: ' . print_r($source_args, true));
    // ...
}
```
