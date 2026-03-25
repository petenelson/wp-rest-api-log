---
name: 10up-plugin-development
description: Create WordPress plugins following 10up architecture patterns. Covers plugin structure, the 10up PHP framework module system, hooks, settings API, and security. Use when building new plugins or refactoring existing ones.
license: MIT
compatibility: WordPress 6.4+, PHP 8.0+, Composer recommended
globs:
  - "*.php"
  - includes/**/*.php
  - src/**/*.php
  - plugin.php
  - functions.php
metadata:
  author: 10up
  version: "1.0"
---

# 10up Plugin Development

This skill guides you through creating WordPress plugins following 10up architecture patterns and the 10up PHP framework.

## When to Use

- Creating a new WordPress plugin
- Refactoring an existing plugin to 10up standards
- Adding features to a 10up scaffold plugin
- Implementing settings, admin pages, or REST endpoints
- Ensuring security best practices

## Key Concepts

10up plugin architecture emphasizes:
- **Modular design** - Features as separate modules
- **Auto-loading** - Classes loaded from `src/` directory
- **Namespaces** - PSR-4 autoloading
- **Framework patterns** - Standardized initialization

## Procedure

### Step 1: Plugin File Structure

**Standard 10up plugin structure:**

```
plugin-name/
├── plugin-name.php           # Main entry point
├── composer.json             # PHP dependencies
├── package.json              # JS dependencies
├── src/                      # PHP source files
│   ├── PluginCore.php        # Module initialization
│   ├── Blocks.php            # Block registration
│   ├── Assets.php            # Asset enqueueing
│   └── Admin/
│       └── Settings.php      # Settings page
├── blocks/                   # Custom blocks
│   └── block-name/
├── dist/                     # Compiled assets
├── languages/                # Translation files
└── tests/                    # Test files
```

### Step 2: Main Plugin File

**plugin-name.php:**

```php
<?php
/**
 * Plugin Name: Plugin Name
 * Plugin URI: https://github.com/10up/plugin-name
 * Description: Plugin description.
 * Version: 1.0.0
 * Author: 10up
 * Author URI: https://10up.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plugin-name
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 *
 * @package PluginName
 */

namespace PluginName;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('PLUGIN_NAME_VERSION', '1.0.0');
define('PLUGIN_NAME_PATH', plugin_dir_path(__FILE__));
define('PLUGIN_NAME_URL', plugin_dir_url(__FILE__));

// Autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize plugin.
add_action('plugins_loaded', function () {
    PluginCore::get_instance();
});
```

### Step 3: Module Initialization (10up Framework Pattern)

**src/PluginCore.php:**

```php
<?php
/**
 * Plugin core class.
 *
 * @package PluginName
 */

namespace PluginName;

use TenupFramework\ModuleInitialization;

/**
 * Plugin core class.
 */
class PluginCore {

    /**
     * Instance.
     *
     * @var PluginCore|null
     */
    private static ?PluginCore $instance = null;

    /**
     * Get instance.
     *
     * @return PluginCore
     */
    public static function get_instance(): PluginCore {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize modules.
     */
    private function init(): void {
        // Auto-load all modules from src/ directory.
        ModuleInitialization::instance()->init_classes(
            PLUGIN_NAME_PATH . 'src',
            __NAMESPACE__
        );
    }
}
```

### Step 4: Create a Module

Modules implement `ModuleInterface` and use the `Module` trait:

**src/Blocks.php:**

```php
<?php
/**
 * Blocks module.
 *
 * @package PluginName
 */

namespace PluginName;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;

/**
 * Blocks registration class.
 */
class Blocks implements ModuleInterface {
    use Module;

    /**
     * Check if module can be registered.
     *
     * @return bool
     */
    public function can_register(): bool {
        return true;
    }

    /**
     * Register hooks.
     */
    public function register(): void {
        add_action('init', [$this, 'register_blocks']);
    }

    /**
     * Register blocks.
     */
    public function register_blocks(): void {
        $blocks_dir = PLUGIN_NAME_PATH . 'blocks/';

        if (!is_dir($blocks_dir)) {
            return;
        }

        $block_folders = glob($blocks_dir . '*/block.json');

        foreach ($block_folders as $block_json) {
            register_block_type(dirname($block_json));
        }
    }
}
```

### Step 5: Asset Management

**src/Assets.php:**

```php
<?php
/**
 * Assets module.
 *
 * @package PluginName
 */

namespace PluginName;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;
use TenupFramework\Traits\GetAssetInfo;

/**
 * Asset management class.
 */
class Assets implements ModuleInterface {
    use Module;
    use GetAssetInfo;

    /**
     * Check if module can be registered.
     *
     * @return bool
     */
    public function can_register(): bool {
        return true;
    }

    /**
     * Register hooks.
     */
    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
    }

    /**
     * Enqueue frontend assets.
     */
    public function enqueue_frontend_assets(): void {
        $asset_info = $this->get_asset_info('frontend');

        wp_enqueue_style(
            'plugin-name-frontend',
            PLUGIN_NAME_URL . 'dist/frontend.css',
            [],
            $asset_info['version']
        );

        wp_enqueue_script(
            'plugin-name-frontend',
            PLUGIN_NAME_URL . 'dist/frontend.js',
            $asset_info['dependencies'],
            $asset_info['version'],
            true
        );
    }

    /**
     * Enqueue editor assets.
     */
    public function enqueue_editor_assets(): void {
        $asset_info = $this->get_asset_info('editor');

        wp_enqueue_script(
            'plugin-name-editor',
            PLUGIN_NAME_URL . 'dist/editor.js',
            $asset_info['dependencies'],
            $asset_info['version'],
            true
        );
    }

    /**
     * Get asset info from compiled file.
     *
     * @param string $name Asset name.
     * @return array
     */
    private function get_asset_info(string $name): array {
        $asset_file = PLUGIN_NAME_PATH . "dist/{$name}.asset.php";

        if (file_exists($asset_file)) {
            return require $asset_file;
        }

        return [
            'dependencies' => [],
            'version'      => PLUGIN_NAME_VERSION,
        ];
    }
}
```

### Step 6: Settings Page (Without Framework)

For plugins without 10up framework:

**src/Admin/Settings.php:**

```php
<?php
/**
 * Settings page.
 *
 * @package PluginName
 */

namespace PluginName\Admin;

/**
 * Settings class.
 */
class Settings {

    /**
     * Option name.
     */
    private const OPTION_NAME = 'plugin_name_settings';

    /**
     * Initialize.
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add menu page.
     */
    public function add_menu_page(): void {
        add_options_page(
            __('Plugin Name Settings', 'plugin-name'),
            __('Plugin Name', 'plugin-name'),
            'manage_options',
            'plugin-name-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings.
     */
    public function register_settings(): void {
        register_setting(
            'plugin_name_settings_group',
            self::OPTION_NAME,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default'           => $this->get_defaults(),
            ]
        );

        add_settings_section(
            'plugin_name_general',
            __('General Settings', 'plugin-name'),
            '__return_null',
            'plugin-name-settings'
        );

        add_settings_field(
            'enable_feature',
            __('Enable Feature', 'plugin-name'),
            [$this, 'render_checkbox_field'],
            'plugin-name-settings',
            'plugin_name_general',
            [
                'name'  => 'enable_feature',
                'label' => __('Enable the main feature', 'plugin-name'),
            ]
        );
    }

    /**
     * Render settings page.
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('plugin_name_settings_group');
                do_settings_sections('plugin-name-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render checkbox field.
     *
     * @param array $args Field arguments.
     */
    public function render_checkbox_field(array $args): void {
        $options = get_option(self::OPTION_NAME, $this->get_defaults());
        $value   = $options[$args['name']] ?? false;
        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr(self::OPTION_NAME . '[' . $args['name'] . ']'); ?>"
                value="1"
                <?php checked($value, true); ?>
            />
            <?php echo esc_html($args['label']); ?>
        </label>
        <?php
    }

    /**
     * Sanitize settings.
     *
     * @param array $input Input values.
     * @return array
     */
    public function sanitize_settings(array $input): array {
        $sanitized = [];

        $sanitized['enable_feature'] = !empty($input['enable_feature']);

        return $sanitized;
    }

    /**
     * Get default values.
     *
     * @return array
     */
    private function get_defaults(): array {
        return [
            'enable_feature' => false,
        ];
    }
}
```

### Step 7: Security Best Practices

**Always follow these security rules:**

```php
// 1. Verify nonces for form submissions
if (!wp_verify_nonce($_POST['_wpnonce'], 'plugin_name_action')) {
    wp_die(__('Security check failed', 'plugin-name'));
}

// 2. Check capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized access', 'plugin-name'));
}

// 3. Sanitize input
$title = sanitize_text_field($_POST['title']);
$email = sanitize_email($_POST['email']);
$url   = esc_url_raw($_POST['url']);
$html  = wp_kses_post($_POST['content']);
$int   = absint($_POST['count']);

// 4. Escape output
echo esc_html($title);
echo esc_attr($class);
echo esc_url($link);
echo wp_kses_post($content);

// 5. Prepare SQL queries
global $wpdb;
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}custom_table WHERE id = %d",
        $id
    )
);
```

See [references/security.md](references/security.md) for complete security checklist.

See [references/wordpress-patterns.md](references/wordpress-patterns.md) for hooks, options, transients, cron, and rewrite rules.

## Composer Configuration

**composer.json:**

```json
{
    "name": "10up/plugin-name",
    "description": "Plugin description",
    "type": "wordpress-plugin",
    "license": "GPL-2.0-or-later",
    "require": {
        "php": ">=8.0",
        "10up/wp-framework": "^1.3"
    },
    "require-dev": {
        "10up/phpcs-composer": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "PluginName\\": "src/"
        }
    }
}
```

## Verification

After creating a plugin:

1. Activate plugin without errors
2. Check for PHP notices/warnings (enable WP_DEBUG)
3. Verify blocks register correctly
4. Test settings save and load
5. Run PHPCS: `composer run lint`

## Failure Modes

**Plugin doesn't activate:**
- PHP syntax error
- Missing dependency
- Namespace mismatch

**Modules not loading:**
- Missing `ModuleInterface` implementation
- `can_register()` returning false
- Autoloader not configured

**Settings not saving:**
- Missing nonce verification
- Incorrect option name
- Sanitization returning empty

## Escalation

Ask the user when:
- Custom database tables needed
- Complex activation/deactivation logic
- Multi-site considerations
- REST API design decisions
