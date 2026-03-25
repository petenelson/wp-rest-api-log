---
name: 10up-block-patterns
description: Create reusable block patterns and synced patterns following 10up standards. Covers PHP file-based registration, pattern metadata, categories, and the block bindings API. Use when creating reusable layout components or content templates.
license: MIT
compatibility: WordPress 6.0+, PHP 8.0+
globs:
  - patterns/**/*.php
  - includes/patterns/**/*
metadata:
  author: 10up
  version: "1.0"
---

# 10up Block Patterns

This skill guides you through creating block patterns — reusable arrangements of blocks that users can insert with a single click.

## When to Use

- Creating reusable layout components (cards, heroes, CTAs)
- Building content templates for editors
- Setting up starter content for new pages
- Implementing synced patterns for global content
- Using block bindings for dynamic data

## Key Concepts

| Pattern Type | Description | Use Case |
|--------------|-------------|----------|
| **Regular Pattern** | Static template, copied on insert | Cards, layouts, sections |
| **Synced Pattern** | Global content, updates everywhere | Headers, footers, announcements |
| **Pattern with Bindings** | Dynamic data from post meta or other sources | Personalized content |

## Procedure

### Step 1: Create Pattern Directory

Patterns live in the `/patterns/` directory of your theme or plugin:

```
theme-name/
└── patterns/
    ├── card.php
    ├── hero-section.php
    └── call-to-action.php
```

### Step 2: Write Pattern File

**patterns/card.php:**

```php
<?php
/**
 * Title: Card
 * Slug: theme-name/card
 * Description: A card with image, heading, text, and button
 * Categories: components
 * Keywords: card, box, container, cta
 * Viewport Width: 600
 * Block Types: core/group
 */
?>
<!-- wp:group {"className":"card","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
<div class="wp-block-group card">
    <!-- wp:image {"sizeSlug":"medium","linkDestination":"none"} -->
    <figure class="wp-block-image size-medium">
        <img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder.jpg' ) ); ?>" alt=""/>
    </figure>
    <!-- /wp:image -->

    <!-- wp:heading {"level":3} -->
    <h3 class="wp-block-heading">Card Title</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Card description text goes here. Keep it brief and informative.</p>
    <!-- /wp:paragraph -->

    <!-- wp:buttons -->
    <div class="wp-block-buttons">
        <!-- wp:button -->
        <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Learn More</a></div>
        <!-- /wp:button -->
    </div>
    <!-- /wp:buttons -->
</div>
<!-- /wp:group -->
```

### Step 3: Pattern Header Fields

| Field | Required | Description |
|-------|----------|-------------|
| `Title` | Yes | Display name in the inserter |
| `Slug` | Yes | Unique identifier (theme-name/pattern-name) |
| `Description` | No | Shown in pattern browser tooltip |
| `Categories` | No | Comma-separated list of categories |
| `Keywords` | No | Search terms for discoverability |
| `Viewport Width` | No | Preview width in inserter (default: 1200) |
| `Block Types` | No | Blocks this pattern is suggested for |
| `Post Types` | No | Limit to specific post types |
| `Template Types` | No | Limit to specific template types |
| `Inserter` | No | `false` to hide from inserter |

### Step 4: Register Custom Categories

```php
add_action( 'init', function() {
    register_block_pattern_category(
        'theme-name-components',
        [
            'label'       => __( 'Components', 'theme-name' ),
            'description' => __( 'Reusable component patterns', 'theme-name' ),
        ]
    );

    register_block_pattern_category(
        'theme-name-pages',
        [
            'label' => __( 'Page Layouts', 'theme-name' ),
        ]
    );
});
```

### Step 5: Dynamic Content in Patterns

Use PHP to inject dynamic values:

```php
<?php
/**
 * Title: Recent Posts
 * Slug: theme-name/recent-posts
 * Categories: posts
 */

$recent_posts = get_posts( [
    'numberposts' => 3,
    'post_status' => 'publish',
] );
?>
<!-- wp:group {"className":"recent-posts"} -->
<div class="wp-block-group recent-posts">
    <?php foreach ( $recent_posts as $post ) : ?>
    <!-- wp:group {"className":"post-card"} -->
    <div class="wp-block-group post-card">
        <!-- wp:heading {"level":3} -->
        <h3><?php echo esc_html( $post->post_title ); ?></h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p><?php echo esc_html( wp_trim_words( $post->post_content, 20 ) ); ?></p>
        <!-- /wp:paragraph -->
    </div>
    <!-- /wp:group -->
    <?php endforeach; ?>
</div>
<!-- /wp:group -->
```

### Step 6: Create Synced Patterns

Synced patterns (formerly Reusable Blocks) update everywhere when edited.

**Register via code:**

```php
add_action( 'init', function() {
    // Check if pattern already exists
    $existing = get_posts( [
        'post_type'  => 'wp_block',
        'name'       => 'site-announcement',
        'numberposts' => 1,
    ] );

    if ( empty( $existing ) ) {
        wp_insert_post( [
            'post_title'   => 'Site Announcement',
            'post_name'    => 'site-announcement',
            'post_content' => '<!-- wp:paragraph -->
<p>Important announcement text here.</p>
<!-- /wp:paragraph -->',
            'post_status'  => 'publish',
            'post_type'    => 'wp_block',
        ] );
    }
});
```

**Reference in templates:**

```html
<!-- wp:block {"ref":123} /-->
```

### Step 7: Block Bindings API (WordPress 6.5+)

Bind block attributes to dynamic data sources:

```php
<?php
/**
 * Title: Post Hero
 * Slug: theme-name/post-hero
 * Categories: featured
 */
?>
<!-- wp:cover {"useFeaturedImage":true,"dimRatio":50} -->
<div class="wp-block-cover">
    <span class="wp-block-cover__background has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:post-title {"level":1} /-->
        <!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"core/post-meta","args":{"key":"subtitle"}}}}} -->
        <p></p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->
```

**Register custom binding source:**

```php
add_action( 'init', function() {
    register_block_bindings_source(
        'theme-name/site-data',
        [
            'label'              => __( 'Site Data', 'theme-name' ),
            'get_value_callback' => function( $source_args ) {
                if ( 'phone' === $source_args['key'] ) {
                    return get_option( 'theme_phone_number', '' );
                }
                return '';
            },
        ]
    );
});
```

## Common Patterns

### Hero Section

```php
<?php
/**
 * Title: Hero Section
 * Slug: theme-name/hero
 * Categories: featured
 * Keywords: hero, banner, header
 * Viewport Width: 1200
 */
?>
<!-- wp:cover {"overlayColor":"contrast","minHeight":500,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:500px">
    <span class="wp-block-cover__background has-contrast-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1} -->
        <h1 class="wp-block-heading has-text-align-center">Welcome to Our Site</h1>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"align":"center"} -->
        <p class="has-text-align-center">Your compelling subtitle goes here.</p>
        <!-- /wp:paragraph -->
        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Get Started</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->
```

### Two Column Feature

```php
<?php
/**
 * Title: Two Column Feature
 * Slug: theme-name/two-column-feature
 * Categories: featured
 */
?>
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide">
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- wp:image {"sizeSlug":"large"} -->
        <figure class="wp-block-image size-large"><img src="" alt=""/></figure>
        <!-- /wp:image -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"verticalAlignment":"center"} -->
    <div class="wp-block-column is-vertically-aligned-center">
        <!-- wp:heading -->
        <h2>Feature Title</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph -->
        <p>Feature description with compelling copy that explains the value proposition.</p>
        <!-- /wp:paragraph -->
        <!-- wp:buttons -->
        <div class="wp-block-buttons">
            <!-- wp:button -->
            <div class="wp-block-button"><a class="wp-block-button__link">Learn More</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->
```

## Verification

After creating a pattern:

1. Check pattern appears in inserter (+ button → Patterns)
2. Verify it's in the correct category
3. Insert and check block structure is valid
4. Test responsiveness at different viewport widths
5. For synced patterns, verify changes propagate

## Failure Modes

**Pattern not appearing:**
- Check file is in `/patterns/` directory
- Verify `Title` and `Slug` headers are present
- Check for PHP syntax errors
- Ensure slug is unique

**Invalid block markup:**
- Block comments must be properly closed
- JSON attributes must be valid
- Check for unescaped characters

**Category not showing:**
- Register category before patterns load (priority)
- Check category slug matches in pattern

**Synced pattern not updating:**
- Clear object cache
- Verify post type is `wp_block`
- Check pattern ref ID is correct

## Escalation

Ask the user when:
- Complex dynamic data requirements
- Need pattern variations for different contexts
- Integration with custom post types
- Multi-site pattern sharing
