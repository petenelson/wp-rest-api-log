# Block Extension Hooks Reference

Complete reference for WordPress filter hooks used in block extensions.

## Registration Hooks

### blocks.registerBlockType

Modify block settings during registration.

```javascript
import { addFilter } from '@wordpress/hooks';

addFilter(
    'blocks.registerBlockType',
    'your-namespace/extension-name',
    (settings, name) => {
        // Only modify specific blocks
        if (name !== 'core/group') {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                // Add new attributes
                customAttribute: {
                    type: 'boolean',
                    default: false,
                },
            },
            // Add supports
            supports: {
                ...settings.supports,
                customFeature: true,
            },
        };
    }
);
```

**Parameters:**
- `settings` (Object) - Block settings object
- `name` (string) - Block name (e.g., 'core/group')

**Returns:** Modified settings object

**Common uses:**
- Adding custom attributes
- Modifying supports
- Changing default values
- Adding transforms

## Editor Hooks

### editor.BlockEdit

Wrap or extend the block edit component.

```javascript
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

const withCustomControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        // Only extend specific blocks
        if (props.name !== 'core/group') {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes } = props;

        return (
            <>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody title="Custom Options">
                        <ToggleControl
                            label="Enable Feature"
                            checked={attributes.customAttribute}
                            onChange={(value) => setAttributes({ customAttribute: value })}
                        />
                    </PanelBody>
                </InspectorControls>
            </>
        );
    };
}, 'withCustomControls');

addFilter(
    'editor.BlockEdit',
    'your-namespace/custom-controls',
    withCustomControls
);
```

**Parameters:**
- `BlockEdit` (Component) - Original block edit component

**Returns:** Wrapped component

**Common uses:**
- Adding inspector controls
- Adding toolbar buttons
- Wrapping with context providers
- Adding side effects

### editor.BlockListBlock

Modify the block wrapper in the editor.

```javascript
const withCustomClassName = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        if (props.name !== 'core/group') {
            return <BlockListBlock {...props} />;
        }

        const { attributes } = props;

        // Add custom class based on attributes
        const customClass = attributes.customAttribute ? 'has-custom-feature' : '';

        return (
            <BlockListBlock
                {...props}
                className={`${props.className || ''} ${customClass}`.trim()}
            />
        );
    };
}, 'withCustomClassName');

addFilter(
    'editor.BlockListBlock',
    'your-namespace/custom-class',
    withCustomClassName
);
```

**Parameters:**
- `BlockListBlock` (Component) - Block wrapper component

**Returns:** Wrapped component

**Common uses:**
- Adding editor-only classes
- Modifying wrapper props
- Adding data attributes
- Conditional styling

## Save Hooks

### blocks.getSaveElement

Modify the saved element (static blocks only).

```javascript
addFilter(
    'blocks.getSaveElement',
    'your-namespace/save-element',
    (element, blockType, attributes) => {
        if (blockType.name !== 'core/group') {
            return element;
        }

        // Clone and modify the element
        return cloneElement(element, {
            'data-custom': attributes.customAttribute ? 'true' : 'false',
        });
    }
);
```

**Note:** This doesn't work for dynamic blocks (which return null from save).

### blocks.getSaveContent.extraProps

Add extra props to the save wrapper element.

```javascript
addFilter(
    'blocks.getSaveContent.extraProps',
    'your-namespace/save-props',
    (extraProps, blockType, attributes) => {
        if (blockType.name !== 'core/group') {
            return extraProps;
        }

        if (attributes.customAttribute) {
            extraProps.className = `${extraProps.className || ''} has-custom-feature`.trim();
            extraProps['data-custom'] = 'true';
        }

        return extraProps;
    }
);
```

**Parameters:**
- `extraProps` (Object) - Existing extra props
- `blockType` (Object) - Block type definition
- `attributes` (Object) - Block attributes

**Returns:** Modified extraProps object

**Common uses:**
- Adding CSS classes
- Adding data attributes
- Adding ARIA attributes

## Hook Priority

Control execution order with priority (default: 10).

```javascript
// Run early (before other filters)
addFilter(
    'blocks.registerBlockType',
    'your-namespace/early',
    callback,
    5  // Lower = earlier
);

// Run late (after other filters)
addFilter(
    'blocks.registerBlockType',
    'your-namespace/late',
    callback,
    20  // Higher = later
);
```

## Removing Filters

```javascript
import { removeFilter } from '@wordpress/hooks';

removeFilter(
    'blocks.registerBlockType',
    'your-namespace/extension-name'
);
```

## Multiple Blocks

Apply extension to multiple blocks:

```javascript
const BLOCKS_TO_EXTEND = [
    'core/group',
    'core/columns',
    'core/cover',
];

addFilter(
    'blocks.registerBlockType',
    'your-namespace/multi-block',
    (settings, name) => {
        if (!BLOCKS_TO_EXTEND.includes(name)) {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                sharedAttribute: {
                    type: 'boolean',
                    default: false,
                },
            },
        };
    }
);
```

## Debugging

Log filter execution:

```javascript
addFilter(
    'blocks.registerBlockType',
    'debug/log-registration',
    (settings, name) => {
        console.log('Registering block:', name, settings);
        return settings;
    },
    1  // Run first
);
```
