# WordPress Core Patterns Reference

Essential WordPress patterns for plugin development: hooks, options, transients, cron, and rewrite rules.

## Hooks (Actions & Filters)

### Actions (Execute Code)

```php
// Register action
add_action('init', 'my_function', 10, 1);

function my_function() {
    register_post_type('book', $args);
}

// Trigger custom action
do_action('my_plugin_event', $arg1, $arg2);
```

### Filters (Modify Data)

```php
// Register filter - MUST return a value
add_filter('the_content', 'modify_content', 10, 1);

function modify_content($content) {
    return $content . '<p>Added</p>';
}

// Apply filter
$value = apply_filters('my_plugin_filter', $original);
```

### Priority

Lower = earlier execution. Default is 10.

```php
add_action('init', 'runs_first', 5);
add_action('init', 'runs_default', 10);
add_action('init', 'runs_last', 99);
```

### Key Hook Order

```
plugins_loaded → after_setup_theme → init → wp_loaded → template_redirect → wp_head → the_content → wp_footer → shutdown
```

### Removing Hooks

```php
remove_action('init', 'function_name', 10);
remove_filter('the_content', [$instance, 'method'], 10);
```

## Options API

Store persistent key-value data in `wp_options` table.

### Basic Operations

```php
add_option('my_option', $value, '', $autoload);
update_option('my_option', $value, $autoload);
$value = get_option('my_option', $default);
delete_option('my_option');
```

### Autoload Consideration

All autoloaded options load on every request. Disable for large or infrequent data:

```php
// Large data - disable autoload
update_option('my_large_data', $data, false);

// Check autoloaded size
$wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload='yes'");
```

## Transients API

Temporary cached data with expiration.

```php
// Set with expiration
set_transient('my_cache', $data, HOUR_IN_SECONDS);

// Get (returns false if expired)
$data = get_transient('my_cache');

if (false === $data) {
    $data = expensive_operation();
    set_transient('my_cache', $data, HOUR_IN_SECONDS);
}

// Delete
delete_transient('my_cache');
```

### Options vs Transients

| Use Case | Options | Transients |
|----------|---------|------------|
| Plugin settings | Yes | No |
| API response cache | No | Yes |
| Computed data (expires) | No | Yes |

### Time Constants

```php
MINUTE_IN_SECONDS  // 60
HOUR_IN_SECONDS    // 3600
DAY_IN_SECONDS     // 86400
WEEK_IN_SECONDS    // 604800
```

## WP-Cron

### Schedule Events Properly

```php
// BAD - Creates duplicates
add_action('init', function() {
    wp_schedule_event(time(), 'hourly', 'my_task');
});

// GOOD - Check first, schedule on activation
register_activation_hook(__FILE__, function() {
    if (!wp_next_scheduled('my_task')) {
        wp_schedule_event(time(), 'hourly', 'my_task');
    }
});

register_deactivation_hook(__FILE__, function() {
    wp_clear_scheduled_hook('my_task');
});

// Add task handler
add_action('my_task', function() {
    // Do work
});
```

### Custom Intervals

```php
add_filter('cron_schedules', function($schedules) {
    $schedules['fifteen_minutes'] = [
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display'  => 'Every 15 minutes',
    ];
    return $schedules;
});
```

### High-Traffic Sites

Disable WP-Cron and use system cron:

```php
// wp-config.php
define('DISABLE_WP_CRON', true);
```

```bash
# System crontab
* * * * * cd /path/to/wordpress && wp cron event run --due-now
```

### Prevent Parallel Execution

```php
add_action('my_heavy_task', function() {
    if (get_transient('my_task_lock')) {
        return;
    }
    set_transient('my_task_lock', true, 5 * MINUTE_IN_SECONDS);

    try {
        // Do work
    } finally {
        delete_transient('my_task_lock');
    }
});
```

## Rewrite Rules

Convert URLs to query vars: `/products/shoes/` → `index.php?product_type=shoes`

### Add Custom Rules

```php
add_action('init', function() {
    add_rewrite_rule(
        '^products/([^/]+)/?$',
        'index.php?product_type=$matches[1]',
        'top'
    );
});

// Register query var
add_filter('query_vars', function($vars) {
    $vars[] = 'product_type';
    return $vars;
});
```

### Flush Rules Properly

Never flush on every request. Only on activation:

```php
register_activation_hook(__FILE__, function() {
    my_plugin_register_rewrites();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});
```

```bash
# Or via WP-CLI
wp rewrite flush
```

### Custom Endpoints

```php
add_action('init', function() {
    add_rewrite_endpoint('downloads', EP_PAGES);
});

// Check endpoint
if (get_query_var('downloads') !== '') {
    // On /page/downloads/ endpoint
}
```

## Debugging Commands

```bash
# List cron events
wp cron event list

# Run due cron events
wp cron event run --due-now

# Check rewrite rules
wp rewrite list

# Flush rewrites
wp rewrite flush

# Check option
wp option get my_option

# List transients
wp transient list

# Delete expired transients
wp transient delete --expired
```
