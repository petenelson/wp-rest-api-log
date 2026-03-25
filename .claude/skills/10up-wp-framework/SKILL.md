---
name: 10up-wp-framework
description: Use the 10up WordPress PHP framework for modular plugin and theme development. Covers ModuleInterface, Module trait, auto-loading, and asset management patterns. Use when building plugins or themes with the 10up framework.
license: MIT
compatibility: PHP 8.2+, Composer, 10up/wp-framework package
globs:
  - includes/**/*.php
  - src/**/*.php
  - composer.json
metadata:
  author: 10up
  version: "1.0"
---

# 10up WordPress Framework

This skill guides you through using the 10up WordPress PHP framework — a modular system for building maintainable plugins and themes.

## When to Use

- Building a new 10up plugin or theme
- Adding modules to an existing 10up project
- Understanding the ModuleInterface pattern
- Managing WordPress hooks in a structured way
- Creating custom post types and taxonomies

## Key Concepts

The 10up Framework provides:
- **ModuleInterface** — Standard interface for feature modules (with `load_order()`, `can_register()`, and `register()`)
- **Module trait** — Common functionality for modules
- **ModuleInitialization** — Auto-loading modules from directories
- **GetAssetInfo trait** — Helper for loading compiled asset info
- **AbstractPostType** — Base class for custom post types
- **AbstractTaxonomy** — Base class for custom taxonomies

## Installation

```bash
composer require 10up/wp-framework
```

## Procedure

### Step 1: Set Up Autoloading

**composer.json:**
```json
{
  "autoload": {
    "psr-4": {
      "YourNamespace\\": "src/"
    }
  }
}
```

Run `composer dump-autoload` after changes.

### Step 2: Create the Main Plugin/Theme Class

**src/PluginCore.php:**
```php
<?php
namespace YourNamespace;

use TenupFramework\ModuleInitialization;

class PluginCore {
    private static ?PluginCore $instance = null;

    public static function get_instance(): PluginCore {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_modules();
    }

    private function init_modules(): void {
        // Auto-load all modules from src/ directory
        // The framework auto-discovers namespaces from PSR-4 autoloading
        ModuleInitialization::instance()->init_classes(
            YOUR_PLUGIN_PATH . 'src'
        );
    }
}
```

### Step 3: Initialize in Main Plugin File

**your-plugin.php:**
```php
<?php
/**
 * Plugin Name: Your Plugin
 */

namespace YourNamespace;

define('YOUR_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('YOUR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/vendor/autoload.php';

add_action('plugins_loaded', function() {
    PluginCore::get_instance();
});
```

### Step 4: Create a Module

Modules must implement `ModuleInterface` with three methods:

**src/Blocks.php:**
```php
<?php
namespace YourNamespace;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;

class Blocks implements ModuleInterface {
    use Module;

    /**
     * Control initialization order (lower numbers load first).
     * The Module trait provides a default of 10.
     */
    public function load_order(): int {
        return 10; // Default value, can be overridden
    }

    /**
     * Determine if this module should be registered.
     */
    public function can_register(): bool {
        return true;
    }

    /**
     * Register hooks for this module.
     */
    public function register(): void {
        add_action('init', [$this, 'register_blocks']);
    }

    /**
     * Register custom blocks.
     */
    public function register_blocks(): void {
        $blocks_dir = YOUR_PLUGIN_PATH . 'blocks/';

        foreach (glob($blocks_dir . '*/block.json') as $block_json) {
            register_block_type(dirname($block_json));
        }
    }
}
```

**Note:** The `Module` trait provides a default `load_order()` returning 10. Override it only if you need specific initialization order.

### Step 5: Conditional Module Registration

Use `can_register()` to conditionally load modules:

**src/AdminSettings.php:**
```php
<?php
namespace YourNamespace;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;

class AdminSettings implements ModuleInterface {
    use Module;

    public function can_register(): bool {
        // Only load in admin
        return is_admin();
    }

    public function register(): void {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    // ... rest of the class
}
```

**Common conditions:**
```php
// Admin only
public function can_register(): bool {
    return is_admin();
}

// Frontend only
public function can_register(): bool {
    return !is_admin();
}

// When specific plugin is active
public function can_register(): bool {
    return class_exists('WooCommerce');
}

// When specific feature is enabled
public function can_register(): bool {
    return get_option('feature_enabled', false);
}
```

### Step 6: Asset Management

Use the `GetAssetInfo` trait for loading compiled assets. The trait requires calling `setup_asset_vars()` before use:

**src/Assets.php:**
```php
<?php
namespace YourNamespace;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;
use TenupFramework\Assets\GetAssetInfo;

class Assets implements ModuleInterface {
    use Module;
    use GetAssetInfo;

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        // REQUIRED: Set up asset variables before using get_asset_info()
        $this->setup_asset_vars(
            YOUR_PLUGIN_PATH . 'dist',  // Path to dist directory
            YOUR_PLUGIN_VERSION         // Fallback version
        );

        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor']);
    }

    public function enqueue_frontend(): void {
        // get_asset_info looks in dist/js/, dist/css/, or dist/blocks/
        $asset = $this->get_asset_info('frontend');

        wp_enqueue_style(
            'your-plugin-frontend',
            YOUR_PLUGIN_URL . 'dist/css/frontend.css',
            [],
            $asset['version']
        );

        wp_enqueue_script(
            'your-plugin-frontend',
            YOUR_PLUGIN_URL . 'dist/js/frontend.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    public function enqueue_editor(): void {
        $asset = $this->get_asset_info('editor');

        wp_enqueue_script(
            'your-plugin-editor',
            YOUR_PLUGIN_URL . 'dist/js/editor.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }
}
```

**GetAssetInfo API:**
```php
// Setup (required before using get_asset_info)
$this->setup_asset_vars(string $dist_path, string $fallback_version): void

// Get all asset info
$asset = $this->get_asset_info('frontend');
// Returns: ['version' => '...', 'dependencies' => [...]]

// Get specific attribute
$version = $this->get_asset_info('frontend', 'version');
$deps = $this->get_asset_info('frontend', 'dependencies');
```

### Step 7: Module with Dependencies

Modules can depend on services or other data:

**src/RestApi.php:**
```php
<?php
namespace YourNamespace;

use TenupFramework\Module;
use TenupFramework\ModuleInterface;

class RestApi implements ModuleInterface {
    use Module;

    private string $namespace = 'your-plugin/v1';

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route($this->namespace, '/items', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_items'],
            'permission_callback' => [$this, 'check_permission'],
        ]);
    }

    public function get_items(\WP_REST_Request $request): \WP_REST_Response {
        $items = []; // Fetch items
        return new \WP_REST_Response($items, 200);
    }

    public function check_permission(): bool {
        return current_user_can('read');
    }
}
```

## Module Organization

### By Feature

```
src/
├── PluginCore.php
├── Blocks.php
├── Assets.php
├── RestApi.php
├── Settings.php
└── Cron.php
```

### By Domain (larger projects)

```
src/
├── PluginCore.php
├── Blocks/
│   ├── BlockRegistration.php
│   └── BlockAssets.php
├── Admin/
│   ├── Settings.php
│   └── Dashboard.php
├── Api/
│   ├── RestRoutes.php
│   └── Authentication.php
└── Frontend/
    ├── Assets.php
    └── Shortcodes.php
```

The framework uses Spatie's structure-discoverer to auto-discover all classes in subdirectories. A single call is usually sufficient:

```php
private function init_modules(): void {
    // Auto-discovers all ModuleInterface classes recursively
    ModuleInitialization::instance()->init_classes(YOUR_PLUGIN_PATH . 'src');
}
```

For large projects, you can call multiple times if needed:

```php
private function init_modules(): void {
    $initializer = ModuleInitialization::instance();

    // Each call scans the directory recursively
    $initializer->init_classes(YOUR_PLUGIN_PATH . 'src/Core');
    $initializer->init_classes(YOUR_PLUGIN_PATH . 'src/Features');
}
```

## Common Patterns

### Singleton Module (with state)

```php
class Cache implements ModuleInterface {
    use Module;

    private static array $cache = [];

    public function can_register(): bool {
        return true;
    }

    public function register(): void {
        // Register hooks
    }

    public static function get(string $key): mixed {
        return self::$cache[$key] ?? null;
    }

    public static function set(string $key, mixed $value): void {
        self::$cache[$key] = $value;
    }
}
```

### Module with Options

```php
class FeatureToggle implements ModuleInterface {
    use Module;

    private array $options;

    public function can_register(): bool {
        $this->options = get_option('your_plugin_options', []);
        return !empty($this->options['feature_enabled']);
    }

    public function register(): void {
        // Only runs if feature_enabled is true
    }
}
```

## Verification

After creating modules:

1. Check module is in `src/` directory
2. Verify namespace matches directory structure
3. Run `composer dump-autoload`
4. Activate plugin and check for PHP errors
5. Verify hooks are firing (use Query Monitor)

## Failure Modes

**Module not loading:**
- Check class implements `ModuleInterface`
- Verify `can_register()` returns `true`
- Check namespace in file matches composer.json
- Run `composer dump-autoload`

**Hooks not firing:**
- Check `register()` method is called
- Verify hook names are correct
- Check hook priority and timing

**Asset info not found:**
- Run `npm run build` to generate .asset.php files
- Check file path in `get_asset_info()`

## Escalation

Ask the user when:
- Complex dependency injection needed
- Module needs to communicate with other modules
- Performance concerns with many modules
- Custom module initialization order required
