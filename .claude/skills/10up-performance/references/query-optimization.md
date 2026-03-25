# Database Query Optimization Reference

Strategies for optimizing WordPress database queries.

## Understanding WP_Query

### Query Parameters That Affect Performance

```php
$query = new WP_Query([
    'posts_per_page' => 10,          // Limit results (use -1 with caution)
    'no_found_rows' => true,         // Skip pagination count query
    'update_post_meta_cache' => true, // Pre-fetch post meta
    'update_post_term_cache' => true, // Pre-fetch terms
    'fields' => 'ids',               // Return only IDs if that's all you need
]);
```

### no_found_rows

Skip the expensive `SQL_CALC_FOUND_ROWS` query when pagination isn't needed:

```php
// When you DON'T need pagination
$query = new WP_Query([
    'posts_per_page' => 5,
    'no_found_rows' => true, // Saves one query
]);

// When you DO need pagination (default)
$query = new WP_Query([
    'posts_per_page' => 10,
    'paged' => $page,
    // no_found_rows defaults to false
]);
```

### fields Parameter

Return only what you need:

```php
// Return only post IDs
$query = new WP_Query([
    'fields' => 'ids',
    'posts_per_page' => 100,
]);
// $query->posts = [1, 2, 3, ...]

// Return ID => parent pairs
$query = new WP_Query([
    'fields' => 'id=>parent',
]);
// $query->posts = [1 => 0, 2 => 1, ...]
```

### Cache Parameters

```php
$query = new WP_Query([
    // Pre-fetch related data in single query
    'update_post_meta_cache' => true,  // Default: true
    'update_post_term_cache' => true,  // Default: true

    // Disable if you won't use meta/terms
    'update_post_meta_cache' => false,
    'update_post_term_cache' => false,
]);
```

## Avoiding N+1 Queries

### The Problem

```php
// BAD: N+1 queries
$posts = get_posts(['numberposts' => 10]);
foreach ($posts as $post) {
    // Each of these triggers a query
    $author = get_the_author_meta('display_name', $post->post_author);
    $thumbnail = get_post_thumbnail_id($post->ID);
    $categories = get_the_category($post->ID);
}
// Total: 1 + (10 * 3) = 31 queries
```

### The Solution

```php
// GOOD: Eager loading
$posts = get_posts([
    'numberposts' => 10,
    'update_post_meta_cache' => true,  // Pre-loads all meta
    'update_post_term_cache' => true,  // Pre-loads all terms
]);

// Pre-load authors
$author_ids = array_unique(wp_list_pluck($posts, 'post_author'));
if (!empty($author_ids)) {
    // This caches user data for later calls
    get_users(['include' => $author_ids]);
}

foreach ($posts as $post) {
    // These now use cached data
    $author = get_the_author_meta('display_name', $post->post_author);
    $thumbnail = get_post_thumbnail_id($post->ID); // Uses meta cache
    $categories = get_the_category($post->ID);     // Uses term cache
}
// Total: 3 queries
```

### Batch Loading Pattern

```php
function get_posts_with_data($args) {
    $query = new WP_Query(array_merge($args, [
        'update_post_meta_cache' => true,
        'update_post_term_cache' => true,
    ]));

    if (empty($query->posts)) {
        return $query;
    }

    // Batch load authors
    $author_ids = array_unique(wp_list_pluck($query->posts, 'post_author'));
    cache_users($author_ids);

    // Batch load any additional meta
    $post_ids = wp_list_pluck($query->posts, 'ID');
    update_meta_cache('post', $post_ids);

    return $query;
}
```

## Meta Query Optimization

### Slow Meta Queries

```php
// SLOW: Meta queries don't use indexes well
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

// SLOW: Multiple meta conditions
$query = new WP_Query([
    'meta_query' => [
        'relation' => 'AND',
        ['key' => 'price', 'value' => 100, 'compare' => '>=', 'type' => 'NUMERIC'],
        ['key' => 'color', 'value' => 'red'],
        ['key' => 'size', 'value' => 'large'],
    ],
]);
```

### Faster Alternatives

**Use taxonomy instead of meta for filterable data:**

```php
// FAST: Taxonomy queries are indexed
$query = new WP_Query([
    'tax_query' => [
        [
            'taxonomy' => 'product_color',
            'field' => 'slug',
            'terms' => 'red',
        ],
    ],
]);
```

**Store dates in indexed columns:**

```php
// Register post type with date-based sorting
register_post_type('event', [
    'supports' => ['title', 'editor', 'custom-fields'],
]);

// Use post_date for event date
wp_insert_post([
    'post_type' => 'event',
    'post_title' => 'My Event',
    'post_date' => '2025-06-15 10:00:00', // Event date
]);

// FAST: Use date_query on indexed post_date
$query = new WP_Query([
    'post_type' => 'event',
    'date_query' => [
        ['after' => 'today'],
    ],
]);
```

**Use EXISTS for boolean checks:**

```php
// FASTER: EXISTS instead of value comparison
$query = new WP_Query([
    'meta_query' => [
        [
            'key' => 'is_featured',
            'compare' => 'EXISTS',
        ],
    ],
]);
```

### Index Important Meta Keys

For frequently queried meta, consider custom tables or at minimum, add index:

```sql
-- Add index to postmeta for specific key (use cautiously)
ALTER TABLE wp_postmeta ADD INDEX meta_key_value (meta_key(191), meta_value(100));
```

## Direct Database Queries

### When to Use Direct Queries

Use `$wpdb` when WP_Query is too slow or inflexible:

```php
global $wpdb;

// Get specific data efficiently
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT p.ID, p.post_title, pm.meta_value as price
     FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'price'
     WHERE p.post_type = %s AND p.post_status = 'publish'
     ORDER BY pm.meta_value + 0 ASC
     LIMIT %d",
    'product',
    10
));
```

### Always Use Prepared Statements

```php
// SAFE: Use prepare()
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} WHERE post_author = %d AND post_status = %s",
    $author_id,
    'publish'
));

// DANGEROUS: Never concatenate user input
// $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID = " . $_GET['id']);
```

### Batch Operations

```php
// Delete in batches to avoid timeouts
function delete_old_data_batch($batch_size = 1000) {
    global $wpdb;

    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta}
         WHERE meta_key = %s
         LIMIT %d",
        '_old_meta_key',
        $batch_size
    ));

    return $deleted;
}

// Run until complete
while (delete_old_data_batch() > 0) {
    // Continue deleting
    sleep(1); // Prevent server overload
}
```

## Autoloaded Options

### The Problem

WordPress loads all autoloaded options on every page load.

```php
// Check autoloaded options size
global $wpdb;
$size = $wpdb->get_var(
    "SELECT SUM(LENGTH(option_value))
     FROM {$wpdb->options}
     WHERE autoload = 'yes'"
);
// Should be < 1MB
```

### Fix Large Autoloaded Options

```php
// Don't autoload large data
update_option('my_large_data', $data, false); // false = no autoload

// Or update existing option's autoload status
global $wpdb;
$wpdb->update(
    $wpdb->options,
    ['autoload' => 'no'],
    ['option_name' => 'my_large_option']
);
```

### Split Large Options

```php
// BAD: One huge option
update_option('plugin_all_settings', $huge_array);

// GOOD: Split into logical pieces
update_option('plugin_general_settings', $general, true);  // Autoload
update_option('plugin_logs', $logs, false);                 // Don't autoload
update_option('plugin_cache_data', $cache, false);          // Don't autoload
```

## Query Monitoring

### Using Query Monitor

Key things to check:
- Queries > 50ms
- Duplicate queries (N+1)
- Queries without indexes
- Total query count per page

### Manual Query Logging

```php
// Enable query logging (development only)
define('SAVEQUERIES', true);

// At end of page
add_action('shutdown', function() {
    global $wpdb;

    if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
        return;
    }

    $slow_queries = array_filter($wpdb->queries, function($q) {
        return $q[1] > 0.05; // > 50ms
    });

    error_log("Total queries: " . count($wpdb->queries));
    error_log("Slow queries: " . print_r($slow_queries, true));
});
```

### EXPLAIN Queries

```php
// Check query execution plan
global $wpdb;
$explain = $wpdb->get_results(
    "EXPLAIN SELECT * FROM {$wpdb->posts}
     WHERE post_type = 'post' AND post_status = 'publish'"
);
error_log(print_r($explain, true));
```

Look for:
- `type: ALL` (full table scan - bad)
- `type: index` or `type: ref` (using index - good)
- `rows: large number` (scanning many rows - optimize needed)

## Performance Checklist

1. Use `no_found_rows => true` when pagination isn't needed
2. Use `fields => 'ids'` when you only need post IDs
3. Set `update_post_meta_cache` and `update_post_term_cache` appropriately
4. Avoid meta queries on large datasets - use taxonomies instead
5. Batch load related data instead of N+1 queries
6. Don't autoload large options
7. Use prepared statements for direct queries
8. Monitor queries with Query Monitor
9. Cache expensive query results
10. Consider custom tables for complex data structures
