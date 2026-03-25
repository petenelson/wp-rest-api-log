# ModuleInterface Reference

Complete reference for the 10up Framework ModuleInterface pattern.

## Interface Definition

```php
namespace TenupFramework;

interface ModuleInterface {
    /**
     * Used to alter the order in which classes are initialized.
     * Lower number will be initialized first.
     *
     * @return int
     */
    public function load_order(): int;

    /**
     * Determine if this module should be registered.
     *
     * @return bool
     */
    public function can_register(): bool;

    /**
     * Register hooks for this module.
     *
     * @return void
     */
    public function register(): void;
}
```

## Required Methods

### load_order()

Controls the initialization order of modules. Lower numbers are initialized first.

**Return:** `int` — Default is 10 (provided by Module trait)

```php
public function load_order(): int {
    return 10; // Default
}

public function load_order(): int {
    return 1; // Load early (before default modules)
}

public function load_order(): int {
    return 100; // Load late (after default modules)
}
```

**Note:** This has no correlation to WordPress hook priority. It only controls the order in which `register()` is called on modules.

### can_register()

Determines if the module should be initialized. Called before `register()`.

**Return:** `bool` — `true` to load module, `false` to skip

```php
public function can_register(): bool {
    // Always load
    return true;
}

public function can_register(): bool {
    // Admin only
    return is_admin();
}

public function can_register(): bool {
    // Frontend only
    return !is_admin() && !wp_doing_ajax();
}

public function can_register(): bool {
    // When dependency exists
    return class_exists('WooCommerce');
}

public function can_register(): bool {
    // When feature enabled
    return (bool) get_option('my_feature_enabled', false);
}

public function can_register(): bool {
    // Check capability
    return current_user_can('manage_options');
}

public function can_register(): bool {
    // Check theme support
    return current_theme_supports('my-feature');
}

public function can_register(): bool {
    // Check WordPress version
    global $wp_version;
    return version_compare($wp_version, '6.4', '>=');
}
```

### register()

Registers WordPress hooks for the module. Called only if `can_register()` returns `true`.

```php
public function register(): void {
    // Register actions
    add_action('init', [$this, 'init_callback']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

    // Register filters
    add_filter('the_content', [$this, 'filter_content']);

    // Register shortcodes
    add_shortcode('my_shortcode', [$this, 'render_shortcode']);
}
```

## Module Trait

The `Module` trait provides the default implementation of `load_order()`:

```php
use TenupFramework\Module;
use TenupFramework\ModuleInterface;

class MyModule implements ModuleInterface {
    use Module;

    // Module trait provides:
    // - load_order() returning 10 (default)
    // - can_register() and register() are abstract (you must implement them)
}
```

The trait definition:

```php
trait Module {
    /**
     * Default load order of 10.
     */
    public function load_order(): int {
        return 10;
    }

    abstract public function can_register(): bool;
    abstract public function register(): void;
}
```

## Complete Module Example

```php
<?php
namespace MyPlugin;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;

class CustomPostTypes implements ModuleInterface {
    use Module;

    /**
     * Post type slug.
     */
    private const POST_TYPE = 'product';

    /**
     * Load early to register post types before other modules.
     */
    public function load_order(): int {
        return 5; // Lower than default 10
    }

    /**
     * Check if module can be registered.
     */
    public function can_register(): bool {
        return true;
    }

    /**
     * Register hooks.
     */
    public function register(): void {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomies']);
        add_filter('enter_title_here', [$this, 'change_title_placeholder'], 10, 2);
    }

    /**
     * Register custom post type.
     */
    public function register_post_type(): void {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('Products', 'my-plugin'),
                'singular_name' => __('Product', 'my-plugin'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'show_in_rest' => true,
        ]);
    }

    /**
     * Register taxonomies.
     */
    public function register_taxonomies(): void {
        register_taxonomy('product_category', self::POST_TYPE, [
            'labels' => [
                'name' => __('Categories', 'my-plugin'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
    }

    /**
     * Change title placeholder for products.
     */
    public function change_title_placeholder(string $placeholder, \WP_Post $post): string {
        if (self::POST_TYPE === $post->post_type) {
            return __('Enter product name', 'my-plugin');
        }
        return $placeholder;
    }
}
```

## Module Initialization

### Basic Initialization

```php
use TenupFramework\ModuleInitialization;

class PluginCore {
    private function init_modules(): void {
        // init_classes takes ONE parameter: the directory to scan
        // Namespaces are auto-discovered from PSR-4 autoloading
        ModuleInitialization::instance()->init_classes(
            PLUGIN_PATH . 'src'
        );
    }
}
```

### Multiple Directories

The framework recursively scans subdirectories, so a single call is usually sufficient:

```php
private function init_modules(): void {
    // Scans src/ and all subdirectories
    ModuleInitialization::instance()->init_classes(PLUGIN_PATH . 'src');
}
```

For large projects with separate module directories:

```php
private function init_modules(): void {
    $init = ModuleInitialization::instance();

    // Scan multiple directories if needed
    $init->init_classes(PLUGIN_PATH . 'src/Core');
    $init->init_classes(PLUGIN_PATH . 'src/Features');
}
```

### Getting Initialized Modules

```php
// Get a specific module by class name
$module = ModuleInitialization::get_module('MyPlugin\\Blocks');

// Get all initialized modules
$modules = ModuleInitialization::instance()->get_all_classes();
```

### Auto-Discovery Behavior

The initializer uses Spatie's structure-discoverer and:
- Recursively scans all subdirectories
- Auto-discovers all classes implementing ModuleInterface
- Automatically skips:
  - Abstract classes
  - Interfaces
  - Traits
  - Classes not implementing ModuleInterface
- Caches results in production/staging for performance

## Module Patterns

### Admin Module

```php
class AdminSettings implements ModuleInterface {
    use Module;

    private const OPTION_NAME = 'my_plugin_settings';

    public function can_register(): bool {
        return is_admin();
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function add_menu(): void {
        add_options_page(
            __('My Plugin Settings', 'my-plugin'),
            __('My Plugin', 'my-plugin'),
            'manage_options',
            'my-plugin-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings(): void {
        register_setting('my_plugin_group', self::OPTION_NAME);

        add_settings_section(
            'general_section',
            __('General Settings', 'my-plugin'),
            '__return_null',
            'my-plugin-settings'
        );
    }

    public function render_settings_page(): void {
        // Render settings form
    }

    public function enqueue_admin_assets(string $hook): void {
        if ('settings_page_my-plugin-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('my-plugin-admin', PLUGIN_URL . 'dist/admin.css');
    }
}
```

### REST API Module

```php
class RestApi implements ModuleInterface {
    use Module;

    private const NAMESPACE = 'my-plugin/v1';

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/items', [
            [
                'methods' => \WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods' => \WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'can_create'],
                'args' => $this->get_item_schema(),
            ],
        ]);
    }

    public function get_items(\WP_REST_Request $request): \WP_REST_Response {
        // Implementation
    }

    public function create_item(\WP_REST_Request $request): \WP_REST_Response {
        // Implementation
    }

    public function can_create(): bool {
        return current_user_can('publish_posts');
    }

    private function get_item_schema(): array {
        return [
            'title' => [
                'type' => 'string',
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ];
    }
}
```

### Cron Module

```php
class CronTasks implements ModuleInterface {
    use Module;

    private const HOOK_NAME = 'my_plugin_daily_task';

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        add_action(self::HOOK_NAME, [$this, 'run_daily_task']);

        // Schedule if not already scheduled
        if (!wp_next_scheduled(self::HOOK_NAME)) {
            wp_schedule_event(time(), 'daily', self::HOOK_NAME);
        }

        // Clear schedule on deactivation
        register_deactivation_hook(PLUGIN_FILE, [$this, 'clear_schedule']);
    }

    public function run_daily_task(): void {
        // Perform daily maintenance
    }

    public function clear_schedule(): void {
        wp_clear_scheduled_hook(self::HOOK_NAME);
    }
}
```

### Block Registration Module

```php
class Blocks implements ModuleInterface {
    use Module;

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        add_action('init', [$this, 'register_blocks']);
        add_filter('block_categories_all', [$this, 'add_block_category'], 10, 2);
    }

    public function register_blocks(): void {
        $blocks_dir = PLUGIN_PATH . 'blocks/';

        if (!is_dir($blocks_dir)) {
            return;
        }

        foreach (glob($blocks_dir . '*/block.json') as $block_json) {
            register_block_type(dirname($block_json));
        }
    }

    public function add_block_category(array $categories, \WP_Block_Editor_Context $context): array {
        array_unshift($categories, [
            'slug' => 'my-plugin',
            'title' => __('My Plugin', 'my-plugin'),
            'icon' => 'star-filled',
        ]);

        return $categories;
    }
}
```

## Best Practices

1. **Single Responsibility**: Each module should handle one feature
2. **Meaningful Names**: Name modules after their feature (Blocks, Assets, RestApi)
3. **Use Constants**: Define repeated strings as class constants
4. **Type Hints**: Use PHP type declarations for parameters and returns
5. **Hook Timing**: Register hooks in `register()`, not constructor
6. **Conditional Loading**: Use `can_register()` to avoid unnecessary code execution
