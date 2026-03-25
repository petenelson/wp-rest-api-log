---
name: 10up-block-development
description: Create and modify Gutenberg blocks following 10up engineering standards. Covers block.json metadata, dynamic rendering with PHP, attributes, deprecations, editor components, and the useBlockProps pattern. Use when creating new blocks, fixing block errors, or modifying existing block code.
license: MIT
compatibility: WordPress 6.4+, PHP 8.0+, 10up-toolkit or @wordpress/scripts
globs:
  - includes/blocks/**/*
  - src/blocks/**/*
  - blocks/**/*
  - "**/block.json"
  - "**/edit.js"
  - "**/save.js"
  - "**/markup.php"
metadata:
  author: 10up
  version: "1.0"
---

# 10up Block Development

This skill guides you through creating and modifying Gutenberg blocks following 10up engineering standards.

## When to Use

- Creating a new custom block
- Modifying an existing block's attributes or behavior
- Fixing "Invalid block" errors
- Adding block settings or controls
- Migrating blocks (deprecations)

## Key Principle: Dynamic Blocks

**10up Standard:** Always use dynamic blocks with PHP rendering for client projects.

Why:
- Markup stored in database stays minimal (just attributes)
- Site-wide updates without re-editing posts
- Complex logic handled server-side
- Better long-term maintainability

## Procedure

### Step 0: Verify Prerequisites

Before creating a block:
1. Confirm build tools are available (10up-toolkit or @wordpress/scripts)
2. Identify the target directory (usually `/blocks/` or `/includes/blocks/`)
3. Check existing blocks for naming conventions

### Step 1: Define Block Structure

Create the block directory with these files:

```
blocks/block-name/
├── block.json        # Block metadata (required)
├── index.js          # Block registration
├── edit.js           # Editor component
├── markup.php        # Server-side rendering (10up standard)
├── save.js           # Save function (returns null for dynamic)
└── style.css         # Block styles (optional)
```

### Step 2: Configure block.json

**Required fields:**

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "namespace/block-name",
  "version": "1.0.0",
  "title": "Block Title",
  "category": "common",
  "description": "What this block does",
  "textdomain": "theme-or-plugin",
  "attributes": {},
  "supports": {},
  "editorScript": "file:./index.js",
  "render": "file:./markup.php"
}
```

**Attributes definition:**

```json
{
  "attributes": {
    "title": {
      "type": "string",
      "default": ""
    },
    "showImage": {
      "type": "boolean",
      "default": true
    },
    "columns": {
      "type": "number",
      "default": 3
    },
    "items": {
      "type": "array",
      "default": []
    }
  }
}
```

See [references/block-json.md](references/block-json.md) for complete field reference.

### Step 3: Create the Editor Component (edit.js)

**Standard pattern using useBlockProps:**

```javascript
import { useBlockProps, RichText, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export const BlockEdit = ({ attributes, setAttributes }) => {
    const { title, showImage, columns } = attributes;
    const blockProps = useBlockProps({
        className: `columns-${columns}`
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'textdomain')}>
                    <ToggleControl
                        label={__('Show Image', 'textdomain')}
                        checked={showImage}
                        onChange={(value) => setAttributes({ showImage: value })}
                    />
                    <RangeControl
                        label={__('Columns', 'textdomain')}
                        value={columns}
                        onChange={(value) => setAttributes({ columns: value })}
                        min={1}
                        max={6}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <RichText
                    tagName="h2"
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Enter title...', 'textdomain')}
                />
            </div>
        </>
    );
};
```

**10up Guidelines:**
- Content (text, images) → Main block area with inline editing
- Settings (toggles, selects) → Inspector sidebar
- Always provide default values
- Use placeholder text for empty RichText

### Step 4: Create the Save Function (save.js)

**For dynamic blocks (10up standard):**

```javascript
export const BlockSave = () => {
    return null;
};
```

Returning `null` tells WordPress to use server-side rendering.

### Step 5: Create PHP Render Template (markup.php)

```php
<?php
/**
 * Block markup for namespace/block-name
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$title      = $attributes['title'] ?? '';
$show_image = $attributes['showImage'] ?? true;
$columns    = $attributes['columns'] ?? 3;

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => sprintf('columns-%d', $columns),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($title) : ?>
        <h2><?php echo wp_kses_post($title); ?></h2>
    <?php endif; ?>

    <?php if ($show_image) : ?>
        <!-- Image markup -->
    <?php endif; ?>

    <?php
    // Inner blocks content (if applicable)
    echo $content; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
    ?>
</div>
```

**Key points:**
- Use `get_block_wrapper_attributes()` for proper block wrapper
- Escape output: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- `$content` contains inner blocks (already escaped by WordPress)

### Step 6: Register the Block (index.js)

```javascript
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import { BlockEdit } from './edit';
import { BlockSave } from './save';

registerBlockType(metadata.name, {
    edit: BlockEdit,
    save: BlockSave,
});
```

### Step 7: Handle Block Deprecations

When changing attributes or markup, add deprecations to prevent "Invalid block" errors:

```javascript
// In index.js or separate deprecations.js
const deprecated = [
    {
        attributes: {
            // Old attribute schema
            oldAttribute: {
                type: 'string',
            },
        },
        migrate(attributes) {
            return {
                ...attributes,
                newAttribute: attributes.oldAttribute,
            };
        },
        save() {
            return null; // For dynamic blocks
        },
    },
];

registerBlockType(metadata.name, {
    edit: BlockEdit,
    save: BlockSave,
    deprecated,
});
```

See [references/deprecations.md](references/deprecations.md) for migration strategies.

## Verification

After creating/modifying a block:

1. Build assets: `npm run build`
2. Clear browser cache
3. Add block in editor - should appear without errors
4. Save post and view frontend
5. Check browser console for JavaScript errors
6. Verify existing blocks still validate (no "Invalid block")

## Failure Modes

**"Invalid block" error:**
- Attributes changed without deprecation
- Save function output doesn't match stored content
- Solution: Add deprecation entry with migrate function

**Block not appearing in inserter:**
- Category doesn't exist
- Block not registered (check console)
- Build not complete

**Styles not applying:**
- Missing `useBlockProps` in edit.js
- Missing `get_block_wrapper_attributes()` in PHP
- CSS not enqueued (check block.json paths)

**Attributes not saving:**
- Type mismatch in block.json
- Missing setAttributes call
- Invalid attribute name

See [references/debugging.md](references/debugging.md) for more troubleshooting.

## Escalation

Ask the user when:
- Unsure about attribute types for complex data
- Block requires integration with external APIs
- Existing deprecation chain is complex
- Performance concerns with rendering
