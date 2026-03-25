---
name: 10up-scaffold
description: Use 10up WP Scaffold for theme and plugin development. Covers scaffold commands, block templates, and project conventions. Use when starting new projects, adding blocks to existing projects, or understanding 10up project structure.
license: MIT
compatibility: Node.js 18+, 10up WP Scaffold
globs:
  - .scaffold/**/*
  - package.json
  - composer.json
metadata:
  author: 10up
  version: "1.0"
---

# 10up Scaffold

This skill guides you through using the 10up WordPress Scaffold — the official project template for 10up WordPress projects.

## When to Use

- Starting a new 10up theme or plugin project
- Adding new blocks to an existing scaffold project
- Understanding 10up project conventions
- Scaffolding components using CLI commands

## Key Concepts

The 10up Scaffold provides:
- Pre-configured build system (10up-toolkit)
- Block scaffolding with Mustache templates
- Consistent project structure
- WordPress coding standards integration
- Testing setup (PHPUnit, Cypress)

## Project Types

### Theme Scaffold

```
theme-name/
├── assets/
│   ├── css/
│   │   ├── frontend/
│   │   ├── shared/
│   │   └── admin/
│   ├── js/
│   │   ├── frontend/
│   │   └── admin/
│   └── images/
├── includes/
│   ├── classes/
│   ├── blocks/
│   └── core.php
├── templates/
├── patterns/
├── parts/
├── languages/
├── dist/
├── style.css
├── functions.php
├── theme.json
├── package.json
├── composer.json
└── 10up-toolkit.config.js
```

### Plugin Scaffold

```
plugin-name/
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   ├── classes/
│   └── blocks/
├── dist/
├── languages/
├── plugin-name.php
├── package.json
├── composer.json
└── 10up-toolkit.config.js
```

## Procedure

### Step 1: Clone the Scaffold

**For themes:**
```bash
npx degit 10up/theme-scaffold my-theme
cd my-theme
npm install
composer install
```

**For plugins:**
```bash
npx degit 10up/plugin-scaffold my-plugin
cd my-plugin
npm install
composer install
```

### Step 2: Configure the Project

Update these files with your project details:

**package.json:**
```json
{
  "name": "your-project-name",
  "description": "Your project description"
}
```

**composer.json:**
```json
{
  "name": "your-org/project-name",
  "description": "Your project description"
}
```

**For themes - style.css:**
```css
/**
 * Theme Name: Your Theme Name
 * Theme URI: https://your-site.com
 * Description: Your theme description
 * Version: 1.0.0
 * Author: Your Name
 */
```

**For plugins - plugin-name.php:**
```php
/**
 * Plugin Name: Your Plugin Name
 * Plugin URI: https://your-site.com
 * Description: Your plugin description
 * Version: 1.0.0
 * Author: Your Name
 */
```

### Step 3: Scaffold a New Block

Use the scaffold command to generate a new block:

```bash
npm run scaffold:block
```

You'll be prompted for:
- Block name (lowercase, hyphens)
- Block title (human readable)
- Block description
- Block category
- Block variant (static/dynamic)

This creates:

```
includes/blocks/block-name/
├── block.json
├── index.js
├── edit.js
├── save.js (or markup.php for dynamic)
└── style.css
```

### Step 4: Block Variants

**Static Block (stores HTML in database):**
```bash
npm run scaffold:block -- --variant=static
```

**Dynamic Block (PHP rendering - 10up recommended):**
```bash
npm run scaffold:block -- --variant=dynamic
```

### Step 5: Understanding Generated Files

**block.json:**
```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "theme-name/block-name",
  "title": "Block Name",
  "category": "common",
  "description": "Block description",
  "textdomain": "theme-name",
  "attributes": {},
  "supports": {},
  "editorScript": "file:./index.js",
  "render": "file:./markup.php"
}
```

**edit.js:**
```javascript
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const BlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            <p>{__('Block content here', 'theme-name')}</p>
        </div>
    );
};
```

**markup.php (dynamic blocks):**
```php
<?php
/**
 * Block markup
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper_attributes; ?>>
    <!-- Block content here -->
</div>
```

### Step 6: Customize Block Templates

Block templates use Mustache syntax. Customize in:

```
.scaffold/templates/block/
├── block.json.mustache
├── edit.js.mustache
├── index.js.mustache
├── markup.php.mustache
├── save.js.mustache
└── style.css.mustache
```

**Example customization (edit.js.mustache):**
```javascript
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const BlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { {{#attributes}}{{name}}{{#unless @last}}, {{/unless}}{{/attributes}} } = attributes;
    const blockProps = useBlockProps();

    return (
        <div {...blockProps}>
            {/* Custom template content */}
        </div>
    );
};
```

## Common Commands

| Command | Description |
|---------|-------------|
| `npm run start` | Watch mode for development |
| `npm run build` | Production build |
| `npm run scaffold:block` | Create new block |
| `npm run lint:js` | Lint JavaScript |
| `npm run lint:css` | Lint CSS |
| `npm run lint:php` | Lint PHP (via Composer) |
| `npm run test` | Run tests |

## File Organization

### CSS Files

```
assets/css/
├── frontend/          # Frontend-only styles
│   └── blocks/
│       └── block-name.css
├── admin/             # Admin-only styles
└── shared/            # Styles for both
    └── variables.css
```

### JavaScript Files

```
assets/js/
├── frontend/          # Frontend scripts
│   └── blocks/
│       └── block-name/
│           └── index.js
└── admin/             # Admin scripts
```

### PHP Classes

```
includes/classes/
├── Blocks.php         # Block registration
├── Assets.php         # Asset enqueueing
└── Core.php           # Plugin/theme initialization
```

## Block Registration

Blocks are auto-registered from their `block.json` files:

**includes/classes/Blocks.php:**
```php
<?php
namespace ThemeName;

class Blocks {
    public function register_hooks() {
        add_action('init', [$this, 'register_blocks']);
    }

    public function register_blocks() {
        $blocks_dir = get_template_directory() . '/includes/blocks/';

        foreach (glob($blocks_dir . '*/block.json') as $block_json) {
            register_block_type(dirname($block_json));
        }
    }
}
```

## Verification

After scaffolding:

1. Run `npm install && composer install`
2. Run `npm run build` to compile assets
3. Activate theme/plugin in WordPress
4. Check blocks appear in editor inserter
5. Verify no console errors

## Failure Modes

**Scaffold command fails:**
- Check Node.js version (18+ required)
- Run `npm install` first
- Verify scaffold templates exist

**Block not appearing:**
- Run `npm run build`
- Check block.json syntax
- Verify registration in Blocks.php

**Styles not loading:**
- Check file paths in block.json
- Ensure CSS is in correct directory
- Clear browser cache

## Naming Conventions

- **Block names:** lowercase with hyphens (`hero-section`)
- **PHP classes:** PascalCase (`HeroSection`)
- **CSS files:** match block name (`hero-section.css`)
- **JS files:** `index.js` for entry, `edit.js` for editor

## Escalation

Ask the user when:
- Custom scaffold template modifications needed
- Non-standard project structure required
- Integration with existing non-scaffold project
- Complex block variant requirements
