---
name: 10up-inner-blocks
description: Implement nested blocks and block composition using InnerBlocks. Covers parent/child relationships, templates, allowed blocks, and template locking. Use when building blocks that contain other blocks like cards, sections, or grid layouts.
license: MIT
compatibility: WordPress 6.4+, requires @wordpress/block-editor
globs:
  - blocks/**/*
  - includes/blocks/**/*
  - "**/edit.js"
  - "**/block.json"
metadata:
  author: 10up
  version: "1.0"
---

# 10up Inner Blocks

This skill guides you through implementing block composition using InnerBlocks - the pattern for creating blocks that contain other blocks.

## When to Use

- Building container blocks (cards, sections, accordions)
- Creating grid/column layouts
- Making blocks that wrap content
- Implementing parent/child block relationships
- Adding "insert area" functionality to blocks

## Key Concepts

InnerBlocks enables:
- **Nested content** - Blocks inside blocks
- **Flexible layouts** - Users choose what goes inside
- **Constrained structures** - Control what blocks are allowed
- **Pre-defined templates** - Default block arrangements

## Procedure

### Step 1: Basic InnerBlocks Setup

**edit.js:**

```javascript
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export const BlockEdit = () => {
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps(blockProps);

    return <div {...innerBlocksProps} />;
};
```

**save.js (for static blocks):**

```javascript
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export const BlockSave = () => {
    const blockProps = useBlockProps.save();
    const innerBlocksProps = useInnerBlocksProps.save(blockProps);

    return <div {...innerBlocksProps} />;
};
```

**markup.php (for dynamic blocks - 10up preferred):**

```php
<?php
$wrapper_attributes = get_block_wrapper_attributes();
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>
</div>
```

### Step 2: Configure Allowed Blocks

Restrict which blocks can be inserted:

```javascript
const ALLOWED_BLOCKS = [
    'core/paragraph',
    'core/heading',
    'core/image',
    'core/list',
];

export const BlockEdit = () => {
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        allowedBlocks: ALLOWED_BLOCKS,
    });

    return <div {...innerBlocksProps} />;
};
```

### Step 3: Define Templates

Pre-populate with default blocks:

```javascript
const TEMPLATE = [
    ['core/heading', { level: 3, placeholder: 'Card Title' }],
    ['core/paragraph', { placeholder: 'Card description...' }],
    ['core/button', { text: 'Learn More' }],
];

export const BlockEdit = () => {
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        template: TEMPLATE,
    });

    return <div {...innerBlocksProps} />;
};
```

### Step 4: Apply Template Locking

Control how users can modify the template:

```javascript
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    template: TEMPLATE,
    templateLock: 'all',  // No modifications allowed
});
```

**Lock modes:**

| Mode | Behavior |
|------|----------|
| `false` | No locking (default) - full flexibility |
| `'all'` | Blocks cannot be added, removed, or moved |
| `'insert'` | Blocks cannot be added or removed, but can be moved |
| `'contentOnly'` | Only content can be edited, not structure |

### Step 5: Implement Parent/Child Blocks

**Parent block (block.json):**

```json
{
  "name": "tenup/card-grid",
  "title": "Card Grid"
}
```

**Child block (block.json):**

```json
{
  "name": "tenup/card-grid-item",
  "title": "Card Item",
  "parent": ["tenup/card-grid"]
}
```

The `parent` attribute restricts where the child can be inserted.

**Parent edit.js:**

```javascript
const ALLOWED_BLOCKS = ['tenup/card-grid-item'];
const TEMPLATE = [
    ['tenup/card-grid-item'],
    ['tenup/card-grid-item'],
    ['tenup/card-grid-item'],
];

export const BlockEdit = () => {
    const blockProps = useBlockProps({ className: 'card-grid' });
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        allowedBlocks: ALLOWED_BLOCKS,
        template: TEMPLATE,
        orientation: 'horizontal',
    });

    return <div {...innerBlocksProps} />;
};
```

### Step 6: Separate Wrapper from Content

For complex layouts, separate the wrapper element from the inner blocks content:

```javascript
export const BlockEdit = ({ attributes }) => {
    const { title } = attributes;
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'card-content' },
        { allowedBlocks: ALLOWED_BLOCKS }
    );

    return (
        <div {...blockProps}>
            <header className="card-header">
                <RichText
                    tagName="h3"
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                />
            </header>
            <div {...innerBlocksProps} />
        </div>
    );
};
```

### Step 7: Add Appender Customization

Control the block inserter appearance:

```javascript
import { useInnerBlocksProps, ButtonBlockAppender } from '@wordpress/block-editor';

// Custom appender
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    renderAppender: () => <ButtonBlockAppender />,
});

// No appender (when templateLock is 'all')
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    renderAppender: false,
});
```

## Common Patterns

### Card with Fixed Header

```javascript
const TEMPLATE = [
    ['core/heading', { level: 3, placeholder: 'Title' }],
    ['core/paragraph', { placeholder: 'Content...' }],
];

export const BlockEdit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps({ className: 'card' });
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'card-body' },
        { template: TEMPLATE }
    );

    return (
        <div {...blockProps}>
            <MediaUpload
                onSelect={(media) => setAttributes({ imageId: media.id })}
                render={({ open }) => (
                    <div className="card-image" onClick={open}>
                        {/* Image display */}
                    </div>
                )}
            />
            <div {...innerBlocksProps} />
        </div>
    );
};
```

### Accordion Group

```javascript
// Parent: accordion-group
const ALLOWED_BLOCKS = ['tenup/accordion-item'];

// Child: accordion-item
{
  "parent": ["tenup/accordion-group"]
}

// Child allows flexible content
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    template: [['core/paragraph']],
    templateLock: false,
});
```

### Section with Constrained Width

```javascript
export const BlockEdit = () => {
    const blockProps = useBlockProps({ className: 'section' });
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'section-content' },
        {
            template: [['core/group', { layout: { type: 'constrained' } }]],
        }
    );

    return (
        <section {...blockProps}>
            <div {...innerBlocksProps} />
        </section>
    );
};
```

## PHP Rendering

For dynamic blocks, `$content` contains the rendered inner blocks:

```php
<?php
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'card',
]);

$title = $attributes['title'] ?? '';
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($title) : ?>
        <h3 class="card-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>

    <div class="card-content">
        <?php
        // Inner blocks are already rendered and escaped by WordPress
        echo $content; // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
        ?>
    </div>
</div>
```

## Verification

After implementing InnerBlocks:

1. Test adding/removing inner blocks (if allowed)
2. Verify template defaults appear for new blocks
3. Check that allowed blocks restriction works
4. Test save/reload - inner blocks should persist
5. Verify frontend rendering includes inner content

## Failure Modes

**Inner blocks don't save:**
- Missing `echo $content` in PHP template
- Static save function not using `useInnerBlocksProps.save()`
- Check for JavaScript errors

**Wrong blocks appear in inserter:**
- `allowedBlocks` not configured
- Child block missing `parent` attribute
- Namespace mismatch

**Template not applying:**
- Template only applies to new blocks
- Existing blocks keep their content
- Check template syntax

**Content not rendering:**
- For dynamic blocks, ensure `$content` is output
- Don't escape `$content` - it's already safe

## Escalation

Ask the user when:
- Complex nested structures with multiple levels
- Dynamic allowed blocks based on context
- Content migration from existing blocks needed
- Template versioning requirements
