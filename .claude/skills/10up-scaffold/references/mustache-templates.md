# Mustache Templates Reference

Guide to customizing block scaffold templates using Mustache syntax.

## Template Location

Scaffold templates are stored in:

```
.scaffold/templates/block/
├── block.json.mustache
├── edit.js.mustache
├── index.js.mustache
├── markup.php.mustache
├── save.js.mustache
└── style.css.mustache
```

## Mustache Syntax

### Variables

```mustache
{{variableName}}      // Escaped output
{{{variableName}}}    // Unescaped output (raw HTML)
```

### Sections (Conditionals)

```mustache
{{#hasFeature}}
  This shows if hasFeature is truthy
{{/hasFeature}}

{{^hasFeature}}
  This shows if hasFeature is falsy
{{/hasFeature}}
```

### Loops

```mustache
{{#items}}
  <li>{{name}}</li>
{{/items}}
```

### Inverted Sections

```mustache
{{^items}}
  No items found
{{/items}}
```

## Available Variables

When scaffolding a block, these variables are available:

| Variable | Description | Example |
|----------|-------------|---------|
| `blockName` | Block slug (hyphenated) | `hero-section` |
| `blockTitle` | Human-readable title | `Hero Section` |
| `blockDescription` | Block description | `A hero banner block` |
| `blockCategory` | Block category | `theme` |
| `namespace` | Block namespace | `theme-name` |
| `textdomain` | Translation textdomain | `theme-name` |
| `isDynamic` | Is dynamic block | `true` |
| `className` | PHP class name | `HeroSection` |

## Template Examples

### block.json.mustache

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "{{namespace}}/{{blockName}}",
  "title": "{{blockTitle}}",
  "category": "{{blockCategory}}",
  "description": "{{blockDescription}}",
  "textdomain": "{{textdomain}}",
  "attributes": {
    "title": {
      "type": "string",
      "default": ""
    },
    "content": {
      "type": "string",
      "default": ""
    }
  },
  "supports": {
    "html": false,
    "align": ["wide", "full"],
    "color": {
      "background": true,
      "text": true
    },
    "spacing": {
      "padding": true,
      "margin": true
    }
  },
  "editorScript": "file:./index.js",
  "editorStyle": "file:./editor.css",
  "style": "file:./style.css"{{#isDynamic}},
  "render": "file:./markup.php"{{/isDynamic}}
}
```

### edit.js.mustache

```javascript
/**
 * {{blockTitle}} - Edit Component
 *
 * @package {{namespace}}
 */

import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
} from '@wordpress/components';

/**
 * Edit component for the {{blockTitle}} block.
 *
 * @param {Object} props Block props.
 * @returns {JSX.Element} Edit component.
 */
export const BlockEdit = (props) => {
    const { attributes, setAttributes } = props;
    const { title, content } = attributes;
    const blockProps = useBlockProps({
        className: '{{blockName}}',
    });

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', '{{textdomain}}')}>
                    <TextControl
                        label={__('Title', '{{textdomain}}')}
                        value={title}
                        onChange={(value) => setAttributes({ title: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...blockProps}>
                <RichText
                    tagName="h2"
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Enter title...', '{{textdomain}}')}
                />
                <RichText
                    tagName="p"
                    value={content}
                    onChange={(value) => setAttributes({ content: value })}
                    placeholder={__('Enter content...', '{{textdomain}}')}
                />
            </div>
        </>
    );
};
```

### index.js.mustache

```javascript
/**
 * {{blockTitle}} Block
 *
 * @package {{namespace}}
 */

import { registerBlockType } from '@wordpress/blocks';
import { BlockEdit } from './edit';
{{^isDynamic}}
import { BlockSave } from './save';
{{/isDynamic}}
import metadata from './block.json';

import './style.css';

/**
 * Register the block.
 */
registerBlockType(metadata, {
    edit: BlockEdit,
    {{^isDynamic}}
    save: BlockSave,
    {{/isDynamic}}
    {{#isDynamic}}
    save: () => null,
    {{/isDynamic}}
});
```

### save.js.mustache (Static Blocks)

```javascript
/**
 * {{blockTitle}} - Save Component
 *
 * @package {{namespace}}
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Save component for the {{blockTitle}} block.
 *
 * @param {Object} props Block props.
 * @returns {JSX.Element} Save component.
 */
export const BlockSave = (props) => {
    const { attributes } = props;
    const { title, content } = attributes;
    const blockProps = useBlockProps.save({
        className: '{{blockName}}',
    });

    return (
        <div {...blockProps}>
            {title && (
                <RichText.Content tagName="h2" value={title} />
            )}
            {content && (
                <RichText.Content tagName="p" value={content} />
            )}
        </div>
    );
};
```

### markup.php.mustache (Dynamic Blocks)

```php
<?php
/**
 * {{blockTitle}} Block Markup
 *
 * @package {{namespace}}
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content.
 * @var WP_Block $block      Block instance.
 */

$title   = $attributes['title'] ?? '';
$content = $attributes['content'] ?? '';

$wrapper_attributes = get_block_wrapper_attributes([
    'class' => '{{blockName}}',
]);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>>
    <?php if ($title) : ?>
        <h2 class="{{blockName}}__title">
            <?php echo wp_kses_post($title); ?>
        </h2>
    <?php endif; ?>

    <?php if ($content) : ?>
        <p class="{{blockName}}__content">
            <?php echo wp_kses_post($content); ?>
        </p>
    <?php endif; ?>
</div>
```

### style.css.mustache

```css
/**
 * {{blockTitle}} Styles
 *
 * @package {{namespace}}
 */

.wp-block-{{namespace}}-{{blockName}} {
    /* Block wrapper styles */
}

.{{blockName}}__title {
    margin-bottom: 1rem;
}

.{{blockName}}__content {
    /* Content styles */
}
```

## Custom Attributes Template

For blocks with custom attributes list:

```mustache
{{#attributes}}
    "{{name}}": {
        "type": "{{type}}"{{#hasDefault}},
        "default": {{default}}{{/hasDefault}}
    }{{^isLast}},{{/isLast}}
{{/attributes}}
```

## Advanced: Custom Template with All Imports

```javascript
/**
 * {{blockTitle}} Block
 *
 * @package {{namespace}}
 */

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    RichText,
    InspectorControls,
    {{#hasInnerBlocks}}
    useInnerBlocksProps,
    {{/hasInnerBlocks}}
    {{#hasMediaUpload}}
    MediaUpload,
    MediaUploadCheck,
    {{/hasMediaUpload}}
    {{#hasColorSettings}}
    PanelColorSettings,
    {{/hasColorSettings}}
} from '@wordpress/block-editor';
import {
    PanelBody,
    {{#hasToggle}}
    ToggleControl,
    {{/hasToggle}}
    {{#hasSelect}}
    SelectControl,
    {{/hasSelect}}
    {{#hasRange}}
    RangeControl,
    {{/hasRange}}
} from '@wordpress/components';

import metadata from './block.json';
import './style.css';

// ... rest of block registration
```

## Creating New Templates

To add a new template type:

1. Create the `.mustache` file in `.scaffold/templates/block/`
2. Update the scaffold configuration to include it
3. Add variables needed for the new template

Example new template `view.js.mustache` for Interactivity API:

```javascript
/**
 * {{blockTitle}} - View Script (Interactivity API)
 *
 * @package {{namespace}}
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store('{{namespace}}', {
    state: {
        get isActive() {
            const context = getContext();
            return context.isActive;
        },
    },
    actions: {
        toggle() {
            const context = getContext();
            context.isActive = !context.isActive;
        },
    },
});
```

## Debugging Templates

Check rendered output:

```bash
# Preview what would be generated
npm run scaffold:block -- --dry-run

# Verbose output
npm run scaffold:block -- --verbose
```

## Common Customizations

### Adding Icon

```json
{
  "icon": {
    "src": "star-filled",
    "foreground": "#1e88e5"
  }
}
```

### Adding Keywords

```json
{
  "keywords": ["{{blockName}}", "custom", "{{namespace}}"]
}
```

### Adding Example Preview

```json
{
  "example": {
    "attributes": {
      "title": "Example Title",
      "content": "Example content for preview."
    }
  }
}
```
