---
name: 10up-performance
description: Optimize WordPress and web performance. Covers Core Web Vitals, image optimization, asset loading, caching, database queries, and third-party scripts. Use when investigating slowness, optimizing load times, or improving user experience.
license: MIT
compatibility: WordPress 6.4+, Query Monitor plugin recommended
metadata:
  author: 10up
  version: "2.0"
---

# 10up Performance

This skill guides you through optimizing web performance, from Core Web Vitals to database queries and asset delivery.

Website performance is essential for positive User Experience, Engineering, SEO, Revenue, and Design. Performance optimization requires continuous effort, strategic planning, and cross-disciplinary collaboration spanning front-end, back-end, systems, and design teams.

## When to Use

- Investigating slow page loads
- Optimizing Core Web Vitals (LCP, CLS, FID)
- Reducing asset bundle sizes
- Implementing caching strategies
- Optimizing images and media
- Managing third-party scripts
- Profiling WordPress execution

## Core Web Vitals

Three metrics deserve primary attention:

| Metric | Description | Target |
|--------|-------------|--------|
| **Largest Contentful Paint (LCP)** | Measures loading performance | < 2.5s |
| **Cumulative Layout Shift (CLS)** | Tracks visual stability | < 0.1 |
| **First Input Delay (FID)** | Monitors interactivity | < 100ms |

Maintaining healthy metrics demands commitment throughout development and maintenance phases.

## Image Optimization

### Serve Responsive Images

Use responsive image markup to deliver appropriate sizes for different devices. WordPress and NextJS provide built-in APIs.

```html
<img
  srcset="image-320w.jpg 320w,
          image-480w.jpg 480w,
          image-800w.jpg 800w"
  sizes="(max-width: 320px) 280px,
         (max-width: 480px) 440px,
         800px"
  src="image-800w.jpg"
  alt="Responsive image"
/>
```

### Use a CDN

Content Delivery Networks significantly enhance loading speeds and enable contemporary formats/compression. Recommended providers: Cloudinary, Cloudflare, Fastly, WordPress VIP.

### Modern Formats

WebP is the most widely used contemporary format for compression. Configure CDNs to serve WebP automatically or use WordPress Performance Lab plugin.

### Lazy-Loading and Decode

Use the `loading="lazy"` attribute for below-the-fold images and `decoding="async"` for parallel loading. Set `loading="eager"` and `decoding="sync"` for above-the-fold content.

```html
<!-- Above-the-fold image -->
<img
  alt="Logo"
  decoding="sync"
  loading="eager"
  src="logo-800w.jpg"
  width="160"
  height="90"
/>

<!-- Below-the-fold image -->
<img
  alt="Gallery image"
  decoding="async"
  loading="lazy"
  src="gallery-image.jpg"
  width="400"
  height="300"
/>
```

### Fetch Priority for LCP Images

Set a fetch priority on the resource to load the image faster. Use `fetchpriority="high"` for primary LCP candidates.

```html
<img
  alt="Hero image"
  fetchpriority="high"
  loading="eager"
  src="hero.jpg"
  width="1200"
  height="600"
/>
```

### Width and Height Attributes

Adding width and height attributes to all `<img />` elements on a page is essential to prevent CLS. This allows browsers to reserve space and calculate aspect ratios correctly.

```php
// WordPress: Ensure width/height on images
add_filter('wp_get_attachment_image_attributes', function($attr, $attachment) {
    if (empty($attr['width']) || empty($attr['height'])) {
        $meta = wp_get_attachment_metadata($attachment->ID);
        if ($meta) {
            $attr['width'] = $meta['width'];
            $attr['height'] = $meta['height'];
        }
    }
    return $attr;
}, 10, 2);
```

### Reduce Image Requests

Minimize image requests through thoughtful design decisions. Use SVGs for icons and decorative elements. Optimize SVGs with tools like SVGOMG.

## Rich Media Optimization

### Lazy-Load Iframes

Apply `loading="lazy"` to iframes containing YouTube, Vimeo, Spotify, and similar services to defer loading until after initial page render.

```html
<iframe
  src="https://www.youtube.com/embed/VIDEO_ID"
  loading="lazy"
  width="560"
  height="315"
  title="Video title"
></iframe>
```

### Facade Pattern

Render low-cost previews of heavy assets, then load actual content on user interaction. Recommended packages:
- Lite Youtube Embed
- Lite Vimeo Embed
- Intercom Chat Facade

```javascript
// Load third-party library on user interaction
const btn = document.querySelector("button");
btn.addEventListener("click", (e) => {
    e.preventDefault();
    import("third-party.library")
        .then((module) => module.default)
        .catch((err) => console.log(err));
});
```

### Load on Visibility

Use the Intersection Observer API to load assets only when they become visible:

```javascript
const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('img[data-src]').forEach((img) => {
    observer.observe(img);
});
```

## JavaScript Optimization

### Minify and Compress

Minimize and compress JavaScript payloads to reduce bandwidth usage. Use Webpack and apply Gzip or Brotli compression. It's far more performant to load many smaller JS files than fewer larger JS files.

### Defer Non-Critical JavaScript

Load only critical JavaScript during initial page load. Use `script_loader_tag` filter in WordPress to apply async/defer attributes.

```php
add_filter('script_loader_tag', function($tag, $handle) {
    $defer_scripts = ['analytics', 'social-share', 'comments'];
    $async_scripts = ['tracking'];

    if (in_array($handle, $defer_scripts, true)) {
        return str_replace(' src', ' defer src', $tag);
    }

    if (in_array($handle, $async_scripts, true)) {
        return str_replace(' src', ' async src', $tag);
    }

    return $tag;
}, 10, 2);
```

### Remove Unused Code

Audit JavaScript bloat by:
- Removing unnecessary libraries
- Using Chrome Coverage tool to identify unused code
- Leveraging BundleAnalyzer for payload insights

### Code Splitting

Code splitting is a powerful technique that can significantly improve performance. Break bundles into smaller chunks loadable on demand using Webpack's SplitChunksPlugin and dynamic imports.

**10up-toolkit.config.js:**
```javascript
module.exports = {
    entry: {
        // Split by page/feature
        'frontend': './src/frontend.js',
        'single': './src/single.js',  // Only on single posts
        'archive': './src/archive.js', // Only on archives
    },
};
```

**Conditional loading in WordPress:**
```php
function enqueue_conditional_assets() {
    $asset = require YOUR_PLUGIN_PATH . 'dist/frontend.asset.php';

    // Always load base styles
    wp_enqueue_style('plugin-frontend', YOUR_PLUGIN_URL . 'dist/frontend.css');

    // Conditionally load JS
    if (is_single()) {
        $single_asset = require YOUR_PLUGIN_PATH . 'dist/single.asset.php';
        wp_enqueue_script('plugin-single', YOUR_PLUGIN_URL . 'dist/single.js', $single_asset['dependencies']);
    }
}
add_action('wp_enqueue_scripts', 'enqueue_conditional_assets');
```

### Identify Long Tasks

A long task refers to any JavaScript task on the main thread that takes longer than 50ms to execute. Strategies to mitigate:

- **setTimeout**: Yield to main thread between tasks
- **async/await**: Create yield points in execution
- **isInputPending()**: Yield only on user interaction
- **Scheduler API**: Prioritize function execution (Chrome only)

```javascript
// Break up long tasks with setTimeout
async function processLargeArray(items) {
    const CHUNK_SIZE = 100;

    for (let i = 0; i < items.length; i += CHUNK_SIZE) {
        const chunk = items.slice(i, i + CHUNK_SIZE);
        processChunk(chunk);

        // Yield to main thread
        await new Promise(resolve => setTimeout(resolve, 0));
    }
}
```

## CSS Optimization

### Minify CSS

Minifying CSS is a common practice that reduces data transfer between the server and browser. Use Webpack or CDN query parameters. 10up Toolkit handles this automatically.

### Purge Unused CSS

PurgeCSS removes unused selectors by analyzing content and CSS files. Check coverage with Chrome DevTools CSS Coverage tool.

### Avoid Render-Blocking CSS

Three approaches:

**1. Embed critical CSS using `<style>` tags for above-the-fold content:**
```php
function inline_critical_css() {
    $critical_css = file_get_contents(get_template_directory() . '/dist/critical.css');
    echo '<style id="critical-css">' . $critical_css . '</style>';
}
add_action('wp_head', 'inline_critical_css', 1);
```

**2. Use asynchronous CSS loading for non-critical styles:**
```html
<link
  rel="preload"
  href="styles.css"
  as="style"
  onload="this.onload=null;this.rel='stylesheet'"
>
<noscript><link rel="stylesheet" href="styles.css"></noscript>
```

**3. Defer main stylesheet:**
```php
function defer_main_stylesheet() {
    wp_enqueue_style('theme-style', get_stylesheet_uri(), [], null, 'print');
    wp_style_add_data('theme-style', 'onload', "this.media='all'");
}
add_action('wp_enqueue_scripts', 'defer_main_stylesheet');
```

### CSS Best Practices

- Avoid base64-encoded images in CSS
- Don't use @import between CSS files
- Use animations cautiously
- Avoid animating margin, padding, width, height (use transform and opacity instead)
- Monitor repaints and reflows using CSSTrigggers.com

## Font Optimization

### Set Font-Display

Choosing the right font-display property can significantly affect how fast fonts are rendered. Use `font-display: swap` for fastest loading.

```css
@font-face {
    font-family: 'WebFont';
    font-display: swap;
    src: url('myfont.woff2') format('woff2');
}
```

### Preconnect and CDN Delivery

Establish early connections to font sources:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
```

### Serve Fonts Locally

Use @font-face with local() first to check user's operating system:

```css
@font-face {
    font-family: 'WebFont';
    src: local('WebFont'),
         url('myfont.woff2') format('woff2');
}
```

### Subset Fonts

Use unicode-range descriptor to exclude unnecessary glyphs:

```css
@font-face {
    font-family: 'WebFont';
    src: url('myfont-latin.woff2') format('woff2');
    unicode-range: U+0025-00FF;
}
```

### Leverage Size-Adjust

The size-adjust property can be helpful to reduce CLS by normalizing font sizes during fallback-to-custom font transitions.

```css
@font-face {
    font-family: 'WebFont';
    src: url('webfont.woff2') format('woff2');
    size-adjust: 105%;
}
```

### Font Delivery Considerations

- Use WOFF/WOFF2 instead of EOT/TTF
- Load maximum 2 fonts when possible
- Consider variable fonts for multiple weights/styles

## Resource Networking

### CDN for Static Assets

Serve CSS, JS, fonts, and media from CDN global data centers to reduce latency.

### Establish Connectivity Early

Add preconnect tags in `<head>` for essential third-party origins:

```html
<link rel="preconnect" href="https://cdn.example.com">
<link rel="dns-prefetch" href="https://cdn.example.com">
```

Resource hints can improve load times by 100-500ms. Only preconnect to critical origins (CDNs, critical scripts, consent managers, ad/tag scripts).

### Prioritize and Preload Critical Resources

Use browser prioritization hints:

| Hint | Use Case |
|------|----------|
| **preconnect** | Establish early connection to third-party origin |
| **prefetch** | Non-critical resource hint for future navigation |
| **preload** | Request critical resources ahead of time |

```html
<link rel="preload" as="style" href="/path/to/critical.css" />
<link rel="preload" as="font" href="/font.woff2" type="font/woff2" crossorigin>
<link rel="preload" as="image" href="/hero.webp" />
```

### Optimize TTFB (Time to First Byte)

Focus on server-side factors:
- Hosting infrastructure
- Platform-specific optimization
- Strategic caching implementation
- Minimizing redirects

### Adaptive Serving

Use Network Information API to optimize for poor conditions:

```javascript
if ('connection' in navigator) {
    navigator.connection.addEventListener("change", () => {
        if (navigator.connection.effectiveType === "2g") {
            document.body.classList.add("low-data-mode");
        }
    });
}
```

## Third-Party Script Management

### Identify Slow Scripts

Use Chrome DevTools:
- Lighthouse audits
- Network Request Blocking
- Coverage tool
- Third-party usage report

Failed audits include "Reduce JavaScript execution time" and "Avoid enormous network payloads."

### Prioritize Critical Scripts

Load only essential scripts during initial page render. Defer interactive chat, social embeds, and non-critical functionality. Ad-tech and consent libraries should load immediately.

### Lazy-Load on Interaction

Load scripts on UI interaction using Facade Pattern or visibility detection.

### Leverage Service Workers

Use Google Workbox for caching strategies. Service workers require HTTPS and CORS support. Use network-first approach for frequently updating scripts.

### Tag Manager Best Practices

Google Tag Manager performance depends on injected code. Image tags have minimal impact; custom HTML poses risks.

Best practices:
- Avoid injecting scripts with visual/functional side effects
- Inject scripts before closing `</body>` tag, not `<head>`
- Periodically audit for duplicates and unused tags

### Ad Script Best Practices

1. Reserve space above-the-fold to minimize CLS
2. Load ad script in `<head>` as early as possible, with preload if needed
3. Use async="true" attribute for asynchronous loading
4. Load scripts statically in `<head>` (never createElement)
5. Optimize critical rendering path and TTFB for better ad performance

## Profiling Tools

### Query Monitor

Install Query Monitor plugin for detailed performance insights:

```bash
wp plugin install query-monitor --activate
```

**What it shows:**
- Database queries (with duplicates highlighted)
- PHP errors and warnings
- HTTP API requests
- Hooks and actions fired
- Block rendering times
- Memory usage

### WP-CLI Profiling

```bash
# Profile a page load
wp profile hook --url=https://example.com/page/

# Profile specific hook
wp profile hook template_redirect --url=https://example.com/page/

# Stage breakdown
wp profile stage --url=https://example.com/page/
```

### Chrome DevTools

- **Lighthouse**: Comprehensive performance audit
- **Performance panel**: Record and analyze runtime performance
- **Network panel**: Analyze resource loading
- **Coverage tool**: Identify unused CSS/JS

## Database Optimization

### Identify Slow Queries

Look for queries in Query Monitor that:
- Take >50ms
- Are duplicated (N+1 problem)
- Scan many rows without indexes
- Use `meta_query` on large tables

### Fix N+1 Queries

**Problem:**
```php
$posts = get_posts(['numberposts' => 10]);
foreach ($posts as $post) {
    // Each iteration makes a new query
    $author = get_userdata($post->post_author);
    $thumbnail = get_post_thumbnail_id($post->ID);
}
```

**Solution - Eager load data:**
```php
$posts = get_posts([
    'numberposts' => 10,
    'update_post_meta_cache' => true,  // Pre-fetch post meta
    'update_post_term_cache' => true,  // Pre-fetch terms
]);

// Pre-load authors
$author_ids = wp_list_pluck($posts, 'post_author');
$authors = get_users(['include' => array_unique($author_ids)]);
$authors_by_id = [];
foreach ($authors as $author) {
    $authors_by_id[$author->ID] = $author;
}

// Now loop without extra queries
foreach ($posts as $post) {
    $author = $authors_by_id[$post->post_author] ?? null;
    $thumbnail = get_post_meta($post->ID, '_thumbnail_id', true); // Uses cache
}
```

### Optimize Meta Queries

**Slow:**
```php
$query = new WP_Query([
    'meta_query' => [
        [
            'key' => 'event_date',
            'value' => date('Y-m-d'),
            'compare' => '>=',
            'type' => 'DATE',
        ],
    ],
]);
```

**Faster - Use indexed columns:**
```php
// Store searchable data in posts table
$query = new WP_Query([
    'date_query' => [
        ['after' => 'today'],
    ],
]);

// Or use a custom table with proper indexes
```

### Cache Expensive Queries

```php
function get_featured_products() {
    $cache_key = 'featured_products_v1';
    $products = wp_cache_get($cache_key, 'your-plugin');

    if (false === $products) {
        $products = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 10,
            'meta_key' => 'is_featured',
            'meta_value' => '1',
        ]);

        wp_cache_set($cache_key, $products, 'your-plugin', HOUR_IN_SECONDS);
    }

    return $products;
}
```

### Transients for External Data

```php
function get_external_api_data() {
    $transient_key = 'external_api_data';
    $data = get_transient($transient_key);

    if (false === $data) {
        $response = wp_remote_get('https://api.example.com/data');

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            set_transient($transient_key, $data, 12 * HOUR_IN_SECONDS);
        }
    }

    return $data;
}
```

## Caching Strategies

### Object Cache

```php
// Use object cache for expensive operations
function get_processed_data($id) {
    $cache_key = "processed_data_{$id}";
    $data = wp_cache_get($cache_key, 'your-plugin');

    if (false === $data) {
        $data = expensive_operation($id);
        wp_cache_set($cache_key, $data, 'your-plugin');
    }

    return $data;
}

// Clear cache when data changes
function clear_processed_data_cache($id) {
    wp_cache_delete("processed_data_{$id}", 'your-plugin');
}
```

### Fragment Caching

```php
function render_expensive_widget() {
    $cache_key = 'expensive_widget_html';
    $html = get_transient($cache_key);

    if (false === $html) {
        ob_start();
        // Expensive rendering...
        $html = ob_get_clean();

        set_transient($cache_key, $html, HOUR_IN_SECONDS);
    }

    echo $html;
}
```

### Cache Invalidation

```php
// Clear caches when content updates
add_action('save_post', function($post_id) {
    // Clear specific transients
    delete_transient('featured_posts');
    delete_transient('sidebar_content');

    // Clear object cache group
    wp_cache_flush_group('your-plugin');
});
```

## Block Performance

### Optimize Block Rendering

```php
// Avoid queries in render callback
function render_posts_block($attributes) {
    // Bad: Query in every render
    // $posts = new WP_Query([...]);

    // Good: Use block context or pre-fetched data
    $post_ids = $attributes['selectedPosts'] ?? [];

    if (empty($post_ids)) {
        return '';
    }

    $posts = get_posts([
        'include' => $post_ids,
        'orderby' => 'post__in',
    ]);

    // Render...
}
```

### Minimize JavaScript Bundles

```javascript
// Only import what you need
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';

// Don't import entire libraries
// Bad: import _ from 'lodash';
// Good: import debounce from 'lodash/debounce';
```

## Server-Timing Header

Add performance metrics to response headers:

```php
function add_server_timing() {
    global $wpdb;

    $queries_time = 0;
    foreach ($wpdb->queries as $query) {
        $queries_time += $query[1];
    }

    header(sprintf(
        'Server-Timing: db;dur=%.2f, queries;desc="%d queries"',
        $queries_time * 1000,
        $wpdb->num_queries
    ));
}
add_action('shutdown', 'add_server_timing');
```

## Common Bottlenecks

| Issue | Symptom | Solution |
|-------|---------|----------|
| N+1 queries | Many similar queries | Eager load with caching |
| Slow meta queries | Long query times | Use indexed columns |
| Large autoload options | Slow option loading | Set autoload=no for large options |
| Unoptimized images | Slow page load, poor LCP | Use WebP, lazy load, proper sizes, fetchpriority |
| Blocking JS | Render blocking, poor FID | Defer/async scripts, code splitting |
| Blocking CSS | Render blocking | Critical CSS, async loading |
| No caching | Every request hits DB | Add object cache, transients |
| External API calls | Slow third-party requests | Cache responses, async loading |
| Layout shifts | Poor CLS | Width/height on images, font size-adjust |
| Slow fonts | Delayed text rendering | font-display: swap, preconnect |
| Heavy third-party scripts | Long tasks | Facade pattern, lazy loading |

## Verification

After optimization:

1. Test with Query Monitor for query counts
2. Run Lighthouse audit (target 90+ performance score)
3. Check Core Web Vitals in Chrome DevTools
4. Check Server-Timing headers
5. Test with caching disabled
6. Profile with wp-cli
7. Test on slow network (Chrome DevTools throttling)

## Measurement Commands

```bash
# Check autoloaded options size
wp db query "SELECT SUM(LENGTH(option_value)) FROM wp_options WHERE autoload='yes'"

# Find large options
wp db query "SELECT option_name, LENGTH(option_value) as size FROM wp_options WHERE autoload='yes' ORDER BY size DESC LIMIT 20"

# Check transients
wp transient list

# Profile specific URL
wp profile hook --url=https://example.com/ --spotlight
```

## Escalation

Ask the user when:
- Server-level caching configuration needed
- CDN setup questions
- Complex caching invalidation requirements
- Database server optimization
- Performance Lab plugin configuration
