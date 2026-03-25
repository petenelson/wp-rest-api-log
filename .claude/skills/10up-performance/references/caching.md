# WordPress Caching Reference

Comprehensive guide to WordPress caching strategies.

## Caching Layers

### Object Cache

Stores computed values in memory (Redis, Memcached).

```php
// Get from cache
$value = wp_cache_get('my_key', 'my_group');

// Set in cache
wp_cache_set('my_key', $value, 'my_group', 3600);

// Delete from cache
wp_cache_delete('my_key', 'my_group');

// Add only if not exists
wp_cache_add('my_key', $value, 'my_group', 3600);

// Replace only if exists
wp_cache_replace('my_key', $new_value, 'my_group', 3600);
```

**Groups:**

```php
// Flush entire group
wp_cache_flush_group('my_group');

// Get multiple keys
$values = wp_cache_get_multiple(['key1', 'key2'], 'my_group');

// Set multiple keys
wp_cache_set_multiple([
    'key1' => $value1,
    'key2' => $value2,
], 'my_group', 3600);
```

### Transients

Database-backed cache (uses object cache when available).

```php
// Get transient
$value = get_transient('my_transient');

// Set transient
set_transient('my_transient', $value, HOUR_IN_SECONDS);

// Delete transient
delete_transient('my_transient');

// Site transient (network-wide)
get_site_transient('network_data');
set_site_transient('network_data', $value, DAY_IN_SECONDS);
```

**Time Constants:**

| Constant | Value |
|----------|-------|
| `MINUTE_IN_SECONDS` | 60 |
| `HOUR_IN_SECONDS` | 3600 |
| `DAY_IN_SECONDS` | 86400 |
| `WEEK_IN_SECONDS` | 604800 |
| `MONTH_IN_SECONDS` | 2592000 |
| `YEAR_IN_SECONDS` | 31536000 |

## Caching Patterns

### Compute and Cache

```php
function get_expensive_result($id) {
    $cache_key = "expensive_result_{$id}";
    $result = wp_cache_get($cache_key, 'my-plugin');

    if (false === $result) {
        $result = perform_expensive_computation($id);
        wp_cache_set($cache_key, $result, 'my-plugin', HOUR_IN_SECONDS);
    }

    return $result;
}
```

### Stale-While-Revalidate

Serve stale content while refreshing in background:

```php
function get_data_with_swr($key, $ttl = 300, $stale_ttl = 3600) {
    $cached = wp_cache_get($key, 'my-plugin');

    if (false !== $cached) {
        // Check if stale but usable
        if (isset($cached['expires']) && time() > $cached['expires']) {
            // Trigger background refresh
            wp_schedule_single_event(time(), 'refresh_cache_' . $key);
        }
        return $cached['data'];
    }

    // No cache, fetch fresh
    $data = fetch_fresh_data();
    $cached = [
        'data' => $data,
        'expires' => time() + $ttl,
    ];
    wp_cache_set($key, $cached, 'my-plugin', $stale_ttl);

    return $data;
}
```

### Fragment Caching

Cache HTML output:

```php
function render_sidebar_widget() {
    $cache_key = 'sidebar_widget_html';
    $html = get_transient($cache_key);

    if (false === $html) {
        ob_start();
        ?>
        <div class="widget">
            <?php
            // Expensive queries/rendering
            $items = get_recent_items();
            foreach ($items as $item) {
                echo render_item($item);
            }
            ?>
        </div>
        <?php
        $html = ob_get_clean();
        set_transient($cache_key, $html, 15 * MINUTE_IN_SECONDS);
    }

    echo $html;
}
```

### Query Result Caching

```php
function get_featured_posts() {
    $cache_key = 'featured_posts';
    $posts = get_transient($cache_key);

    if (false === $posts) {
        $posts = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'meta_key' => 'is_featured',
            'meta_value' => '1',
        ]);

        // Store just the post IDs
        $post_ids = wp_list_pluck($posts->posts, 'ID');
        set_transient($cache_key, $post_ids, HOUR_IN_SECONDS);

        return $posts;
    }

    // Reconstruct from IDs
    return new WP_Query([
        'post__in' => $posts,
        'orderby' => 'post__in',
        'posts_per_page' => count($posts),
    ]);
}
```

### Versioned Cache Keys

```php
function get_settings_data() {
    // Version changes when settings update
    $version = get_option('my_settings_version', '1');
    $cache_key = "settings_data_v{$version}";

    $data = wp_cache_get($cache_key, 'my-plugin');

    if (false === $data) {
        $data = compute_settings_data();
        wp_cache_set($cache_key, $data, 'my-plugin');
    }

    return $data;
}

// On settings update
add_action('update_option_my_settings', function() {
    $version = (int) get_option('my_settings_version', '1');
    update_option('my_settings_version', $version + 1);
});
```

## Cache Invalidation

### Hook-Based Invalidation

```php
// Clear cache when posts change
add_action('save_post', function($post_id, $post) {
    // Clear specific caches
    delete_transient('recent_posts');
    delete_transient('featured_posts');

    // Clear post-specific cache
    wp_cache_delete("post_meta_{$post_id}", 'my-plugin');

    // Clear group
    wp_cache_flush_group('my-plugin-posts');
}, 10, 2);

// Clear cache when terms change
add_action('edited_term', function($term_id, $tt_id, $taxonomy) {
    delete_transient("term_posts_{$term_id}");
}, 10, 3);

// Clear cache when options change
add_action('update_option', function($option_name, $old_value, $new_value) {
    if (strpos($option_name, 'my_plugin_') === 0) {
        wp_cache_flush_group('my-plugin-settings');
    }
}, 10, 3);
```

### Scheduled Invalidation

```php
// Schedule cache refresh
if (!wp_next_scheduled('my_plugin_cache_refresh')) {
    wp_schedule_event(time(), 'hourly', 'my_plugin_cache_refresh');
}

add_action('my_plugin_cache_refresh', function() {
    // Refresh expensive cache
    delete_transient('expensive_computation');
    get_expensive_computation(); // Re-populate
});
```

### Manual Invalidation UI

```php
add_action('admin_bar_menu', function($admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }

    $admin_bar->add_node([
        'id' => 'clear-cache',
        'title' => 'Clear Cache',
        'href' => wp_nonce_url(admin_url('admin-post.php?action=clear_my_cache'), 'clear_cache'),
    ]);
}, 100);

add_action('admin_post_clear_my_cache', function() {
    check_admin_referer('clear_cache');

    wp_cache_flush_group('my-plugin');
    delete_transient('my_transient');

    wp_redirect(wp_get_referer());
    exit;
});
```

## External Data Caching

### API Response Caching

```php
function fetch_external_api_data($endpoint) {
    $cache_key = 'api_' . md5($endpoint);
    $data = get_transient($cache_key);

    if (false === $data) {
        $response = wp_remote_get($endpoint, [
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            // Return stale data if available
            $stale = get_option("stale_{$cache_key}");
            return $stale ?: null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Cache fresh data
        set_transient($cache_key, $data, HOUR_IN_SECONDS);

        // Keep stale copy for fallback
        update_option("stale_{$cache_key}", $data, false);
    }

    return $data;
}
```

### Rate-Limited Caching

```php
function get_rate_limited_data() {
    $cache_key = 'rate_limited_api';
    $data = get_transient($cache_key);

    if (false === $data) {
        // Check rate limit
        $rate_key = 'api_rate_limit';
        $calls = (int) get_transient($rate_key) ?: 0;

        if ($calls >= 100) {
            // Rate limited, return cached or empty
            return get_option("backup_{$cache_key}", []);
        }

        // Make API call
        $data = make_api_call();

        // Update rate counter
        set_transient($rate_key, $calls + 1, HOUR_IN_SECONDS);

        // Cache result
        set_transient($cache_key, $data, 15 * MINUTE_IN_SECONDS);

        // Keep backup
        update_option("backup_{$cache_key}", $data, false);
    }

    return $data;
}
```

## Performance Considerations

### Cache Key Best Practices

```php
// Good: Descriptive, namespaced
$cache_key = 'my_plugin_featured_posts_v2';

// Good: Include relevant parameters
$cache_key = sprintf('my_plugin_posts_page_%d_per_%d', $page, $per_page);

// Good: Use md5 for long/complex keys
$cache_key = 'my_plugin_query_' . md5(serialize($query_args));

// Bad: Too generic
$cache_key = 'posts';

// Bad: User-specific without user ID
$cache_key = 'user_favorites'; // Should include user ID
```

### Avoid Over-Caching

```php
// Bad: Caching simple operations
$cache_key = 'site_name';
$name = wp_cache_get($cache_key);
if (false === $name) {
    $name = get_bloginfo('name'); // Already cached by WP
    wp_cache_set($cache_key, $name);
}

// Good: Only cache expensive operations
function get_complex_menu_structure() {
    $cache_key = 'complex_menu_structure';
    $menu = wp_cache_get($cache_key, 'my-theme');

    if (false === $menu) {
        // This involves multiple queries and processing
        $menu = build_complex_menu_structure();
        wp_cache_set($cache_key, $menu, 'my-theme', HOUR_IN_SECONDS);
    }

    return $menu;
}
```

### Cache Size Management

```php
// Store only necessary data
function cache_post_data($post_id) {
    // Bad: Store entire WP_Post object
    wp_cache_set("post_{$post_id}", get_post($post_id));

    // Good: Store only needed fields
    $post = get_post($post_id);
    wp_cache_set("post_{$post_id}", [
        'ID' => $post->ID,
        'title' => $post->post_title,
        'date' => $post->post_date,
    ], 'my-plugin');
}
```

## Debugging Cache

```php
// Check if object cache is available
if (wp_using_ext_object_cache()) {
    // Redis/Memcached available
}

// Check cache statistics (if available)
if (function_exists('wp_cache_get_stats')) {
    $stats = wp_cache_get_stats();
    error_log(print_r($stats, true));
}

// Debug transient
$value = get_transient('my_key');
$timeout = get_option('_transient_timeout_my_key');
error_log("Value: " . print_r($value, true));
error_log("Expires: " . date('Y-m-d H:i:s', $timeout));
```
