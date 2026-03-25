# PHP Filters and Actions Reference

Complete reference for customizing Ignite blocks via PHP.

## Ignite Core

### Synced Theme Patterns

```php
// Disable synced theme pattern registration
add_filter('tenup_ignite_wp_register_synced_theme_patterns', '__return_false');
```

### Fallback Featured Image

```php
// Customize fallback featured image
add_filter('tenup_ignite_wp_fallback_featured_image_src', function($src) {
    return 'https://example.com/fallback.jpg';
});
```

### Video Cover Controls

```php
// Customize video cover controls text
add_filter('ignite_wp_video_cover_controls_text', function($text, $video_id) {
    return __('Custom play/pause text', 'textdomain');
}, 10, 2);
```

### Plugin Initialization

```php
// Change initialization priority
add_filter('ignite_wp_core_init_priority', function($priority) {
    return 5; // Default is 8
});

// Action when core is loaded
add_action('ignite_wp_core_loaded', function() {
    // Plugin is ready
});
```

## Carousel

### Modify Carousel State

```php
add_filter('render_block_tenup/carousel', function($content) {
    $carousel_state = wp_interactivity_state('tenup/carousel');

    // Modify default options
    $carousel_state['default']['perPage'] = 2;
    $carousel_state['default']['breakpoints'] = [
        640 => ['perPage' => 1],
    ];

    wp_interactivity_state('tenup/carousel', $carousel_state);
    return $content;
});
```

### Carousel Options

Available options to modify:

| Option | Type | Description |
|--------|------|-------------|
| `perPage` | number | Slides visible at once |
| `gap` | string | Space between slides |
| `type` | string | "slide", "fade", "loop" |
| `speed` | number | Transition speed in ms |
| `autoplay` | boolean | Enable autoplay |
| `interval` | number | Autoplay interval in ms |
| `breakpoints` | object | Responsive breakpoints |

## Modal

### Supported Trigger Blocks

```php
// Add or remove supported trigger blocks
add_filter('tenup_modal_supported_blocks', function($blocks) {
    // Remove media-text
    $blocks = array_diff($blocks, ['core/media-text']);

    // Add custom block
    $blocks[] = 'namespace/custom-block';

    return $blocks;
});
```

Default supported blocks:
- `core/button`
- `core/image`
- `core/media-text`

### Inner Block Template

```php
// Customize modal inner block template
add_filter('tenup_modal_inner_block_template', function($template) {
    return [
        ['core/heading', ['level' => 2]],
        ['core/paragraph'],
        ['core/embed'],
    ];
});
```

## Post Picker

### Time to Read String

```php
// Customize time-to-read translation string
add_filter('tenup_ui_kit_post_time_to_read_translation_string', function($str) {
    return 'Estimated reading time: %s minutes';
});
```

### Reading Speed

```php
// Adjust words per minute (default: 200)
add_filter('tenup_post_picker_words_per_minute', function($wpm) {
    return 250;
});
```

## Post Meta Blocks

### Image Size

```php
// Control post meta image size
add_filter('tenup_post_meta_image_size', function($size) {
    return 'medium'; // Default: 'full'
});
```

### Conditional Block Template

```php
// Customize conditional block inner template
add_filter('ignite-wp.postMetaConditional.template', function($template) {
    return [
        ['core/heading', ['level' => 4]],
        ['tenup/post-meta']
    ];
});
```

## Editorial

### Custom Post Statuses

Editorial registers these statuses:
- `ready_for_review`
- `in_review`
- `changes_requested`
- `ready_for_publishing`

### Block Note Permissions

```php
// Customize who can add block notes
add_filter('ignite_wp_editorial_can_add_notes', function($can_add, $post_id, $user_id) {
    return current_user_can('edit_post', $post_id);
}, 10, 3);
```

## Navigation

### Navigation Menu Slug

The navigation block uses menu slug instead of ID for portability:

```php
// Register navigation menu location
register_nav_menus([
    'primary' => __('Primary Navigation', 'theme'),
]);
```

### Megamenu Template Parts

Megamenu content is managed via template parts:

```
themes/theme-name/parts/megamenu-[slug].html
```

## Icons

### Register Custom Icon Set

```php
use function IgniteWPCore\Helpers\register_icons;

add_action('init', function() {
    register_icons([
        'name'  => 'custom-icons',
        'label' => 'Custom Icons',
        'icons' => [
            new \IgniteWPCore\Icon(
                name: 'custom-icon',
                label: 'Custom Icon',
                svg: '<svg>...</svg>'
            ),
        ],
    ]);
});
```

### Unregister Icons

```php
use function IgniteWPCore\Helpers\unregister_icon_set;
use function IgniteWPCore\Helpers\unregister_icon;

// Remove entire icon set
unregister_icon_set('icon-set-name');

// Remove single icon
unregister_icon('icon-set-name', 'icon-name');
```

## Block Render Filters

All blocks support standard WordPress render filters:

```php
// Modify any Ignite block output
add_filter('render_block_tenup/accordion', function($content, $block) {
    // $content - rendered HTML
    // $block - block data
    return $content;
}, 10, 2);

// Pre-render filter
add_filter('pre_render_block', function($content, $block) {
    if ($block['blockName'] === 'tenup/modal') {
        // Return non-null to short-circuit rendering
    }
    return $content;
}, 10, 2);
```

## Helper Functions

### Render Icon

```php
use function IgniteWPCore\Helpers\render_icon;
use function IgniteWPCore\Helpers\get_rendered_icon;

// Echo icon directly
render_icon('ignite-wp', 'chevron-down', [
    'class' => 'my-icon-class',
    'aria-hidden' => 'true',
]);

// Get icon as string
$svg = get_rendered_icon('ignite-wp', 'search', [
    'class' => 'search-icon',
]);
```

### Add Block Inline Styles

```php
use function IgniteWPCore\Helpers\add_block_inline_styles;

// Add styles to global block styles
add_block_inline_styles('.my-class { color: red; }');
```

### Append Content Attributes

```php
use function IgniteWPCore\Helpers\append_content_attributes;

// Add data attributes to block content
$content = append_content_attributes($block_content, [
    'data-custom' => ['value'],
    'data-another' => ['another-value'],
]);
```

### Fluid Clamp Value

```php
use function IgniteWPCore\Helpers\get_fluid_clamp_value;

// Generate CSS clamp() for fluid sizing
$clamp = get_fluid_clamp_value(
    min: 16,      // Minimum value
    max: 24,      // Maximum value
    minVp: 375,   // Minimum viewport
    maxVp: 1440,  // Maximum viewport
    unit: 'rem'   // Output unit
);
// Returns: clamp(1rem, calc(...), 1.5rem)
```

### Synced Pattern Rendering

```php
use function IgniteWPCore\Helpers\render_synced_pattern;

// Render a synced pattern file
$pattern = render_synced_pattern(
    get_template_directory() . '/synced-patterns/my-pattern.html'
);
```
