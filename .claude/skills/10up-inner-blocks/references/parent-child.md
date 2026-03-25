# Parent-Child Block Relationships Reference

Parent-child relationships restrict where blocks can be inserted and create cohesive block groups.

## Defining Relationships

### Child Block Configuration

Use the `parent` attribute in the child's block.json:

```json
{
    "name": "tenup/card-item",
    "title": "Card Item",
    "parent": ["tenup/card-grid"]
}
```

The child block:
- Only appears in inserter when inside the parent
- Cannot be inserted at root level
- Cannot be dragged outside the parent

### Multiple Parents

A child can have multiple valid parents:

```json
{
    "name": "tenup/list-item",
    "parent": ["tenup/feature-list", "tenup/checklist"]
}
```

## Restricting Children

### allowedBlocks

Control which blocks can be inserted as children:

```javascript
const ALLOWED_BLOCKS = ['tenup/card-item'];

const innerBlocksProps = useInnerBlocksProps(blockProps, {
    allowedBlocks: ALLOWED_BLOCKS,
});
```

### Combining Both

Use `parent` and `allowedBlocks` together:

```json
// child block.json
{
    "name": "tenup/tab-panel",
    "parent": ["tenup/tabs"]
}
```

```javascript
// parent edit.js
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    allowedBlocks: ['tenup/tab-panel'],
});
```

## Common Patterns

### Grid + Items

**Grid Parent (tenup/card-grid):**

```json
{
    "name": "tenup/card-grid",
    "title": "Card Grid",
    "supports": {
        "html": false
    }
}
```

```javascript
// edit.js
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

**Grid Child (tenup/card-grid-item):**

```json
{
    "name": "tenup/card-grid-item",
    "title": "Card",
    "parent": ["tenup/card-grid"]
}
```

```javascript
// edit.js - child has its own inner blocks
const TEMPLATE = [
    ['core/image'],
    ['core/heading', { level: 3 }],
    ['core/paragraph'],
];

export const BlockEdit = () => {
    const blockProps = useBlockProps({ className: 'card' });
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        template: TEMPLATE,
    });

    return <div {...innerBlocksProps} />;
};
```

### Tabs + Panels

**Tabs Container:**

```json
{
    "name": "tenup/tabs",
    "title": "Tabs"
}
```

```javascript
export const BlockEdit = () => {
    const blockProps = useBlockProps();
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'tabs-panels' },
        {
            allowedBlocks: ['tenup/tab-panel'],
            template: [
                ['tenup/tab-panel', { title: 'Tab 1' }],
                ['tenup/tab-panel', { title: 'Tab 2' }],
            ],
            orientation: 'horizontal',
        }
    );

    return (
        <div {...blockProps}>
            <TabNavigation />
            <div {...innerBlocksProps} />
        </div>
    );
};
```

**Tab Panel:**

```json
{
    "name": "tenup/tab-panel",
    "title": "Tab Panel",
    "parent": ["tenup/tabs"],
    "attributes": {
        "title": {
            "type": "string",
            "default": "Tab"
        }
    }
}
```

### Accordion + Items

**Accordion Container:**

```javascript
export const BlockEdit = () => {
    const blockProps = useBlockProps({ className: 'accordion' });
    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        allowedBlocks: ['tenup/accordion-item'],
        template: [
            ['tenup/accordion-item'],
        ],
    });

    return <div {...innerBlocksProps} />;
};
```

**Accordion Item:**

```json
{
    "name": "tenup/accordion-item",
    "parent": ["tenup/accordion"],
    "attributes": {
        "title": {
            "type": "string",
            "default": ""
        },
        "isOpen": {
            "type": "boolean",
            "default": false
        }
    }
}
```

```javascript
export const BlockEdit = ({ attributes, setAttributes }) => {
    const { title, isOpen } = attributes;
    const blockProps = useBlockProps({ className: 'accordion-item' });
    const innerBlocksProps = useInnerBlocksProps(
        { className: 'accordion-content' },
        { template: [['core/paragraph']] }
    );

    return (
        <div {...blockProps}>
            <div className="accordion-header">
                <RichText
                    tagName="span"
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder="Accordion title..."
                />
            </div>
            <div {...innerBlocksProps} />
        </div>
    );
};
```

## Accessing Parent/Child Data

### From Child: Access Parent

```javascript
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const BlockEdit = ({ clientId }) => {
    const parentBlock = useSelect(
        (select) => {
            const { getBlockParents, getBlock } = select(blockEditorStore);
            const parents = getBlockParents(clientId);
            return parents.length > 0 ? getBlock(parents[0]) : null;
        },
        [clientId]
    );

    const parentAttributes = parentBlock?.attributes;
    // Use parent attributes...
};
```

### From Parent: Access Children

```javascript
export const BlockEdit = ({ clientId }) => {
    const childBlocks = useSelect(
        (select) => {
            const { getBlocks } = select(blockEditorStore);
            return getBlocks(clientId);
        },
        [clientId]
    );

    const childCount = childBlocks.length;
    // Use child data...
};
```

### Using Block Context

Share data via context (preferred method):

**Parent block.json:**

```json
{
    "name": "tenup/card-grid",
    "providesContext": {
        "tenup/gridColumns": "columns"
    },
    "attributes": {
        "columns": {
            "type": "number",
            "default": 3
        }
    }
}
```

**Child block.json:**

```json
{
    "name": "tenup/card-grid-item",
    "parent": ["tenup/card-grid"],
    "usesContext": ["tenup/gridColumns"]
}
```

**Child edit.js:**

```javascript
export const BlockEdit = ({ context }) => {
    const columns = context['tenup/gridColumns'];
    // Use context value...
};
```

## Dynamic Child Count

Manage children programmatically:

```javascript
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { createBlock } from '@wordpress/blocks';

export const BlockEdit = ({ clientId, attributes, setAttributes }) => {
    const { insertBlock, removeBlock } = useDispatch(blockEditorStore);

    const childBlocks = useSelect(
        (select) => select(blockEditorStore).getBlocks(clientId),
        [clientId]
    );

    const addChild = () => {
        const block = createBlock('tenup/card-grid-item');
        insertBlock(block, childBlocks.length, clientId);
    };

    const removeLastChild = () => {
        if (childBlocks.length > 0) {
            removeBlock(childBlocks[childBlocks.length - 1].clientId);
        }
    };

    return (
        <>
            <BlockControls>
                <ToolbarButton onClick={addChild}>Add Card</ToolbarButton>
                <ToolbarButton onClick={removeLastChild}>Remove Card</ToolbarButton>
            </BlockControls>
            {/* ... */}
        </>
    );
};
```

## PHP Rendering

### Parent Template

```php
<?php
// card-grid/markup.php
$columns = $attributes['columns'] ?? 3;
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'card-grid',
    'style' => '--columns: ' . esc_attr($columns),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; // phpcs:ignore ?>
</div>
```

### Child Template

```php
<?php
// card-grid-item/markup.php
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'card-grid-item',
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo $content; // phpcs:ignore ?>
</div>
```

## Best Practices

1. **Always use `parent` for children** - prevents orphaned blocks
2. **Use `allowedBlocks` for parents** - controls inserter
3. **Provide context** - share data without prop drilling
4. **Template new instances** - provide good defaults
5. **Consider ordering** - use `orientation` prop for horizontal layouts
6. **Handle empty states** - show helpful message when no children
