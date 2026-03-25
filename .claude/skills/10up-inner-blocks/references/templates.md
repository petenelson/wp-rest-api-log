# InnerBlocks Templates Reference

Templates define the default block structure when an InnerBlocks container is first created.

## Template Syntax

A template is an array of block definitions:

```javascript
const TEMPLATE = [
    ['block/name', { attributes }, [innerBlocks]],
    ['block/name', { attributes }],
    ['block/name'],
];
```

**Structure:**
1. Block name (required)
2. Attributes object (optional)
3. Inner blocks array (optional, for nested templates)

## Basic Examples

### Simple Template

```javascript
const TEMPLATE = [
    ['core/heading', { level: 2 }],
    ['core/paragraph'],
];
```

### With Placeholders

```javascript
const TEMPLATE = [
    ['core/heading', {
        level: 2,
        placeholder: 'Enter title...'
    }],
    ['core/paragraph', {
        placeholder: 'Add description here...'
    }],
];
```

### With Default Content

```javascript
const TEMPLATE = [
    ['core/heading', {
        level: 2,
        content: 'Default Title'
    }],
    ['core/paragraph', {
        content: 'Default paragraph text.'
    }],
];
```

## Nested Templates

Templates can include nested InnerBlocks:

### Columns with Content

```javascript
const TEMPLATE = [
    ['core/columns', {}, [
        ['core/column', {}, [
            ['core/heading', { level: 3 }],
            ['core/paragraph'],
        ]],
        ['core/column', {}, [
            ['core/heading', { level: 3 }],
            ['core/paragraph'],
        ]],
    ]],
];
```

### Group with Layout

```javascript
const TEMPLATE = [
    ['core/group', {
        layout: { type: 'constrained' }
    }, [
        ['core/heading'],
        ['core/paragraph'],
    ]],
];
```

## Template Locking

Control how users can modify the template structure.

### Lock Modes

```javascript
const innerBlocksProps = useInnerBlocksProps(blockProps, {
    template: TEMPLATE,
    templateLock: 'all', // or false, 'insert', 'contentOnly'
});
```

| Mode | Add Blocks | Remove Blocks | Move Blocks | Edit Content |
|------|------------|---------------|-------------|--------------|
| `false` | ✅ | ✅ | ✅ | ✅ |
| `'insert'` | ❌ | ❌ | ✅ | ✅ |
| `'all'` | ❌ | ❌ | ❌ | ✅ |
| `'contentOnly'` | ❌ | ❌ | ❌ | ✅ (limited) |

### Dynamic Locking

```javascript
export const BlockEdit = ({ attributes }) => {
    const { isLocked } = attributes;

    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        template: TEMPLATE,
        templateLock: isLocked ? 'all' : false,
    });

    return <div {...innerBlocksProps} />;
};
```

### Per-Block Locking

Individual blocks in the template can have their own lock settings:

```javascript
const TEMPLATE = [
    ['core/heading', {
        level: 2,
        lock: { move: false, remove: false }
    }],
    ['core/paragraph', {}], // Can be removed/moved
];
```

## Common Template Patterns

### Card Template

```javascript
const CARD_TEMPLATE = [
    ['core/image', {
        sizeSlug: 'medium',
    }],
    ['core/heading', {
        level: 3,
        placeholder: 'Card title'
    }],
    ['core/paragraph', {
        placeholder: 'Card description...'
    }],
    ['core/buttons', {}, [
        ['core/button', {
            text: 'Learn More',
            className: 'is-style-outline',
        }],
    ]],
];
```

### Hero Section Template

```javascript
const HERO_TEMPLATE = [
    ['core/heading', {
        level: 1,
        textAlign: 'center',
        placeholder: 'Hero Title',
    }],
    ['core/paragraph', {
        align: 'center',
        placeholder: 'Subtitle text...',
    }],
    ['core/buttons', {
        layout: { type: 'flex', justifyContent: 'center' }
    }, [
        ['core/button', { text: 'Primary Action' }],
        ['core/button', {
            text: 'Secondary',
            className: 'is-style-outline'
        }],
    ]],
];
```

### FAQ/Accordion Template

```javascript
const FAQ_TEMPLATE = [
    ['tenup/accordion-item', {}, [
        ['core/paragraph', { placeholder: 'Answer text...' }],
    ]],
    ['tenup/accordion-item', {}, [
        ['core/paragraph', { placeholder: 'Answer text...' }],
    ]],
];
```

### Grid Template

```javascript
const GRID_TEMPLATE = [
    ['core/columns', { isStackedOnMobile: true }, [
        ['core/column', {}, [
            ['tenup/card'],
        ]],
        ['core/column', {}, [
            ['tenup/card'],
        ]],
        ['core/column', {}, [
            ['tenup/card'],
        ]],
    ]],
];
```

## Dynamic Templates

Generate templates based on attributes:

```javascript
export const BlockEdit = ({ attributes }) => {
    const { columnCount } = attributes;

    // Generate columns dynamically
    const columns = Array(columnCount).fill(null).map(() => (
        ['core/column', {}, [
            ['core/paragraph'],
        ]]
    ));

    const template = [
        ['core/columns', {}, columns],
    ];

    const innerBlocksProps = useInnerBlocksProps(blockProps, {
        template,
        // Note: Dynamic templates should not use templateLock
    });

    return <div {...innerBlocksProps} />;
};
```

## Template vs Default Content

Templates only apply when:
- A new block is inserted
- The block has no existing inner blocks

For existing blocks with content:
- Template is ignored
- Existing inner blocks are preserved

### Forcing Template Reapplication

If you need to reset to template:

```javascript
import { useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const { replaceInnerBlocks } = useDispatch(blockEditorStore);
const { createBlocksFromInnerBlocksTemplate } = useSelect(
    (select) => select(blockEditorStore)
);

// Reset to template
const resetToTemplate = () => {
    const blocks = createBlocksFromInnerBlocksTemplate(TEMPLATE);
    replaceInnerBlocks(clientId, blocks);
};
```

## Template Validation

Validate template structure before use:

```javascript
const isValidTemplate = (template) => {
    return template.every(item => {
        if (!Array.isArray(item)) return false;
        if (typeof item[0] !== 'string') return false;
        if (item[1] && typeof item[1] !== 'object') return false;
        if (item[2] && !Array.isArray(item[2])) return false;
        return true;
    });
};
```

## Best Practices

1. **Use placeholders** over default content for editable fields
2. **Keep templates simple** - avoid deep nesting
3. **Don't lock unnecessarily** - users appreciate flexibility
4. **Test template changes** - existing blocks keep old content
5. **Document templates** - explain the expected structure
