# WordPress Plugin Security Reference

Comprehensive security checklist and best practices for WordPress plugin development.

## Input Validation

### Sanitization Functions

Always sanitize user input before use:

| Function | Use Case |
|----------|----------|
| `sanitize_text_field()` | Single-line text |
| `sanitize_textarea_field()` | Multi-line text |
| `sanitize_email()` | Email addresses |
| `sanitize_file_name()` | File names |
| `sanitize_title()` | Slugs and titles |
| `sanitize_key()` | Keys, lowercase with dashes |
| `absint()` | Positive integers |
| `intval()` | Integers (can be negative) |
| `floatval()` | Floating point numbers |
| `esc_url_raw()` | URLs for database storage |
| `wp_kses_post()` | HTML with allowed post tags |
| `wp_kses()` | HTML with custom allowed tags |

### Examples

```php
// Text field
$title = sanitize_text_field($_POST['title']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$url = esc_url_raw($_POST['website']);

// Integer
$count = absint($_POST['count']);

// Array of integers
$ids = array_map('absint', (array) $_POST['ids']);

// Rich content
$content = wp_kses_post($_POST['content']);

// Custom allowed HTML
$custom = wp_kses($_POST['custom'], [
    'a' => ['href' => [], 'title' => []],
    'br' => [],
    'em' => [],
    'strong' => [],
]);
```

## Output Escaping

### Escaping Functions

Always escape output before display:

| Function | Use Case |
|----------|----------|
| `esc_html()` | HTML content (text nodes) |
| `esc_attr()` | HTML attributes |
| `esc_url()` | URLs in href/src |
| `esc_js()` | Inline JavaScript strings |
| `esc_textarea()` | Textarea content |
| `wp_kses_post()` | Safe HTML content |

### Examples

```php
// In HTML text
<p><?php echo esc_html($title); ?></p>

// In attributes
<div class="<?php echo esc_attr($class); ?>">

// In URLs
<a href="<?php echo esc_url($link); ?>">

// In JavaScript
<script>
var name = '<?php echo esc_js($name); ?>';
</script>

// Allowing safe HTML
<div><?php echo wp_kses_post($content); ?></div>

// Combined escaping
printf(
    '<a href="%s" class="%s">%s</a>',
    esc_url($url),
    esc_attr($class),
    esc_html($text)
);
```

### Translation with Escaping

```php
// Escaped translation
echo esc_html__('Hello World', 'plugin-name');
echo esc_attr__('Click here', 'plugin-name');

// Translation with escaping printf
printf(
    /* translators: %s: user name */
    esc_html__('Hello, %s!', 'plugin-name'),
    esc_html($user_name)
);

// With placeholders
echo wp_kses(
    sprintf(
        /* translators: %s: link to settings */
        __('Go to <a href="%s">settings</a>', 'plugin-name'),
        esc_url($settings_url)
    ),
    ['a' => ['href' => []]]
);
```

## Nonces

### Creating Nonces

```php
// In forms
wp_nonce_field('plugin_action_name', 'plugin_nonce');

// In URLs
$url = wp_nonce_url(admin_url('admin.php?action=do_thing'), 'plugin_action_name');

// For AJAX
wp_create_nonce('plugin_ajax_action');
```

### Verifying Nonces

```php
// Form submission
if (!isset($_POST['plugin_nonce']) ||
    !wp_verify_nonce($_POST['plugin_nonce'], 'plugin_action_name')) {
    wp_die(__('Security check failed.', 'plugin-name'));
}

// AJAX request
check_ajax_referer('plugin_ajax_action', 'nonce');

// URL action
if (!wp_verify_nonce($_GET['_wpnonce'], 'plugin_action_name')) {
    wp_die(__('Invalid request.', 'plugin-name'));
}
```

### Nonce Lifetime

```php
// Default: 24 hours (can be verified up to 24 hours after creation)
// wp_verify_nonce returns:
// 1 = nonce is valid and less than 12 hours old
// 2 = nonce is valid but 12-24 hours old
// false = nonce is invalid

// Customize lifetime (not recommended)
add_filter('nonce_life', function() {
    return 12 * HOUR_IN_SECONDS;
});
```

## Capability Checks

### Common Capabilities

| Capability | Description |
|------------|-------------|
| `manage_options` | Admin settings access |
| `edit_posts` | Can create/edit own posts |
| `edit_others_posts` | Can edit others' posts |
| `publish_posts` | Can publish posts |
| `edit_pages` | Can edit pages |
| `upload_files` | Can upload media |
| `edit_theme_options` | Can use Customizer |

### Checking Capabilities

```php
// Check current user
if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized access.', 'plugin-name'));
}

// Check specific user
if (!user_can($user_id, 'edit_posts')) {
    return new WP_Error('unauthorized', 'User cannot edit posts.');
}

// Check against specific post
if (!current_user_can('edit_post', $post_id)) {
    wp_die(__('You cannot edit this post.', 'plugin-name'));
}

// In REST API
'permission_callback' => function() {
    return current_user_can('manage_options');
}
```

### Custom Capabilities

```php
// Register custom capability
add_action('admin_init', function() {
    $role = get_role('administrator');
    $role->add_cap('manage_plugin_name');
});

// Use custom capability
if (!current_user_can('manage_plugin_name')) {
    wp_die(__('Unauthorized.', 'plugin-name'));
}
```

## Database Security

### Prepared Statements

```php
global $wpdb;

// Single value
$result = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
        $option_name
    )
);

// Multiple values
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_author = %d AND post_status = %s",
        $author_id,
        'publish'
    )
);

// Insert
$wpdb->insert(
    $wpdb->prefix . 'custom_table',
    [
        'name'  => $name,
        'email' => $email,
        'count' => $count,
    ],
    ['%s', '%s', '%d']
);

// Update
$wpdb->update(
    $wpdb->prefix . 'custom_table',
    ['name' => $new_name],
    ['id' => $id],
    ['%s'],
    ['%d']
);

// Delete
$wpdb->delete(
    $wpdb->prefix . 'custom_table',
    ['id' => $id],
    ['%d']
);
```

### Format Specifiers

| Specifier | Type |
|-----------|------|
| `%d` | Integer |
| `%f` | Float |
| `%s` | String |

### NEVER Do This

```php
// DANGEROUS - SQL injection vulnerable
$wpdb->query("SELECT * FROM {$wpdb->posts} WHERE ID = " . $_GET['id']);

// DANGEROUS - Unsanitized in LIKE
$wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_title LIKE '%{$search}%'");

// SAFE - Use prepare with LIKE
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts} WHERE post_title LIKE %s",
        '%' . $wpdb->esc_like($search) . '%'
    )
);
```

## File Operations

### Uploads

```php
// Check file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$file_type = wp_check_filetype($file['name']);

if (!in_array($file_type['type'], $allowed_types, true)) {
    return new WP_Error('invalid_type', 'File type not allowed.');
}

// Use WordPress upload handling
$upload = wp_handle_upload($file, ['test_form' => false]);

if (isset($upload['error'])) {
    return new WP_Error('upload_error', $upload['error']);
}

// Use uploaded file path
$file_path = $upload['file'];
```

### File Paths

```php
// SAFE - Use WordPress functions
$upload_dir = wp_upload_dir();
$path = $upload_dir['basedir'] . '/plugin-name/file.txt';

// Validate path is within allowed directory
$real_path = realpath($path);
$allowed_base = realpath($upload_dir['basedir']);

if (strpos($real_path, $allowed_base) !== 0) {
    wp_die('Invalid file path.');
}

// Use wp_filesystem
global $wp_filesystem;
WP_Filesystem();
$wp_filesystem->put_contents($path, $content);
```

## AJAX Security

### Register AJAX Handler

```php
// For logged-in users
add_action('wp_ajax_plugin_action', [$this, 'handle_ajax']);

// For non-logged-in users (be very careful)
add_action('wp_ajax_nopriv_plugin_action', [$this, 'handle_public_ajax']);
```

### AJAX Handler with Security

```php
public function handle_ajax(): void {
    // Verify nonce
    check_ajax_referer('plugin_ajax_nonce', 'nonce');

    // Check capability
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }

    // Sanitize input
    $post_id = absint($_POST['post_id']);
    $title = sanitize_text_field($_POST['title']);

    // Do work...
    $result = $this->update_item($post_id, $title);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'Updated successfully']);
}
```

### JavaScript Side

```javascript
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'plugin_action',
        nonce: pluginData.nonce,
        post_id: postId,
        title: title,
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.message);
        } else {
            console.error(response.data.message);
        }
    }
});
```

## REST API Security

### Register Endpoint with Permission

```php
register_rest_route('plugin-name/v1', '/items/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => [$this, 'get_item'],
    'permission_callback' => [$this, 'get_item_permission'],
    'args' => [
        'id' => [
            'validate_callback' => function($param) {
                return is_numeric($param);
            },
            'sanitize_callback' => 'absint',
        ],
    ],
]);
```

### Permission Callback

```php
public function get_item_permission(WP_REST_Request $request): bool {
    $post_id = $request->get_param('id');
    return current_user_can('edit_post', $post_id);
}
```

### Sanitize and Validate Arguments

```php
'args' => [
    'title' => [
        'required' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => function($param) {
            return !empty($param) && strlen($param) <= 200;
        },
    ],
    'status' => [
        'type' => 'string',
        'enum' => ['draft', 'publish', 'pending'],
        'default' => 'draft',
    ],
],
```

## SSRF Prevention (Server-Side Request Forgery)

SSRF allows attackers to make requests from your server to internal resources.

### Validate URLs

```php
// DANGEROUS - User controls the URL
$url = $_POST['url'];
$response = wp_remote_get($url);

// SAFE - Validate URL before fetching
function safe_remote_get($url) {
    // Only allow http/https
    if (!wp_http_validate_url($url)) {
        return new WP_Error('invalid_url', 'Invalid URL provided.');
    }

    // Block internal IPs
    $host = parse_url($url, PHP_URL_HOST);
    $ip = gethostbyname($host);

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return new WP_Error('blocked_ip', 'URL resolves to blocked IP range.');
    }

    return wp_remote_get($url);
}
```

### Use Allowlists

```php
// Restrict to known domains
$allowed_domains = ['api.example.com', 'cdn.example.com'];
$host = parse_url($url, PHP_URL_HOST);

if (!in_array($host, $allowed_domains, true)) {
    return new WP_Error('domain_not_allowed', 'Domain not in allowlist.');
}
```

### wp_safe_remote_get

WordPress provides safer alternatives:

```php
// wp_safe_remote_get blocks redirects to local IPs
$response = wp_safe_remote_get($url);

// Disable redirects entirely
$response = wp_remote_get($url, [
    'redirection' => 0,
]);
```

## The Golden Rule

> **Sanitize input, escape output.**

- **Sanitization**: Clean data when receiving (before storage)
- **Escaping**: Make safe when displaying (before output)

```php
// Input: sanitize
$name = sanitize_text_field($_POST['name']);

// Output: escape
echo esc_html($name);
```

Both are required. Sanitization doesn't protect against XSS; escaping does.

## Context-Specific Escaping

Choose escape function based on output context:

| Context | Function | Example |
|---------|----------|---------|
| HTML text | `esc_html()` | `<p><?php echo esc_html($text); ?></p>` |
| HTML attribute | `esc_attr()` | `<div class="<?php echo esc_attr($class); ?>">` |
| URL | `esc_url()` | `<a href="<?php echo esc_url($url); ?>">` |
| JavaScript | `esc_js()` | `<script>var x = '<?php echo esc_js($val); ?>';</script>` |
| SQL | `$wpdb->prepare()` | See prepared statements section |

### Wrong Escape = Vulnerability

```php
// WRONG - esc_html in href allows javascript: URLs
<a href="<?php echo esc_html($url); ?>">

// CORRECT
<a href="<?php echo esc_url($url); ?>">
```

## Principle of Least Privilege

Always check the minimum capability needed:

```php
// Don't use manage_options when edit_posts suffices
if (!current_user_can('edit_posts')) {  // Not manage_options
    wp_die('Unauthorized');
}

// Check capability against specific object
if (!current_user_can('edit_post', $post_id)) {
    wp_die('Cannot edit this post');
}
```

### Meta Capabilities

Some capabilities are "meta" - they translate to primitive capabilities based on context:

```php
// 'edit_post' is meta - checks if user can edit THAT specific post
current_user_can('edit_post', $post_id);

// Takes into account:
// - Post author
// - Post status
// - Post type
// - User role
```

## Security Checklist

### Before Release

- [ ] All user input is sanitized
- [ ] All output is escaped with correct function
- [ ] Nonces used for all forms and actions
- [ ] Capability checks on all admin functions
- [ ] Prepared statements for all database queries
- [ ] File uploads validate type and path
- [ ] AJAX handlers verify nonce and capability
- [ ] REST endpoints have permission callbacks
- [ ] No direct file access (ABSPATH check)
- [ ] Debug mode disabled
- [ ] Error messages don't reveal system info
- [ ] PHPCS security sniffs pass
- [ ] External URLs validated for SSRF
- [ ] Redirects use wp_safe_redirect()

### ABSPATH Check

Every PHP file should prevent direct access:

```php
<?php
// At the top of every PHP file
if (!defined('ABSPATH')) {
    exit;
}
```

## Common Vulnerabilities Quick Reference

| Vulnerability | Prevention |
|---------------|------------|
| XSS | Escape all output with correct function |
| SQL Injection | Use $wpdb->prepare() |
| CSRF | Verify nonces |
| Privilege Escalation | Check capabilities |
| SSRF | Validate URLs, use allowlists |
| File Upload | Validate type, use wp_handle_upload() |
| Path Traversal | Use realpath(), validate paths |
| Object Injection | Don't unserialize user input |
