---
name: 10up-block-extensions
description: Extend core WordPress blocks with custom attributes and controls using the 10up block extension pattern. Alternative to block styles when multiple controls are needed. Use when adding functionality to existing blocks rather than creating new ones.
license: MIT
compatibility: WordPress 6.4+, @10up/block-components recommended
globs:
  - assets/js/block-editor/**/*
  - src/block-editor/**/*
  - "**/block-filters/**/*"
  - "**/block-variations/**/*"
  - "**/block-styles/**/*"
metadata:
  author: 10up
  version: "1.0"
---

# 10up Block Extensions

This skill guides you through extending core WordPress blocks with custom functionality using the 10up block extension pattern.

## When to Use

- Adding custom controls to core blocks (Group, Image, etc.)
- Need more than visual variations (block styles limited to 4)
- Adding attributes that affect rendering
- Applying the same extension to multiple blocks
- Modifying block behavior without creating a fork

## Block Styles vs Block Extensions

| Feature | Block Styles | Block Extensions |
|---------|--------------|------------------|
| Complexity | Single CSS class toggle | Custom attributes + controls |
| Limit | Max 4 per block (10up guideline) | Unlimited |
| Controls | None (click to select) | Inspector controls |
| Combinations | Can't combine | Multiple extensions |
| Use case | Visual variations | Functional modifications |

**Rule:** If you need more than a CSS class or want combinable options, use extensions.

## Procedure

### Step 1: Choose Implementation Method

**Option A: @10up/block-components (Recommended)**

Simplest approach using 10up's library:

```bash
npm install @10up/block-components
```

**Option B: WordPress Filters (Manual)**

For projects without the 10up library.

### Step 2: Using @10up/block-components

**Register the extension:**

```javascript
import { registerBlockExtension } from '@10up/block-components';
import { ToggleControl, RangeControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

registerBlockExtension('core/group', {
    extensionName: 'card-style',
    attributes: {
        hasCardStyle: {
            type: 'boolean',
            default: false,
        },
        cardPadding: {
            type: 'number',
            default: 20,
        },
    },
    classNameGenerator: ({ hasCardStyle, cardPadding }) => {
        if (!hasCardStyle) return '';
        return `has-card-style has-padding-${cardPadding}`;
    },
    Edit: ({ attributes, setAttributes }) => {
        const { hasCardStyle, cardPadding } = attributes;

        return (
            <InspectorControls>
                <PanelBody title={__('Card Options', 'theme')}>
                    <ToggleControl
                        label={__('Enable card style', 'theme')}
                        checked={hasCardStyle}
                        onChange={(value) => setAttributes({ hasCardStyle: value })}
                    />
                    {hasCardStyle && (
                        <RangeControl
                            label={__('Padding', 'theme')}
                            value={cardPadding}
                            onChange={(value) => setAttributes({ cardPadding: value })}
                            min={0}
                            max={60}
                            step={10}
                        />
                    )}
                </PanelBody>
            </InspectorControls>
        );
    },
});
```

**Apply to multiple blocks:**

```javascript
const BLOCKS_TO_EXTEND = ['core/group', 'core/columns', 'core/cover'];

BLOCKS_TO_EXTEND.forEach((blockName) => {
    registerBlockExtension(blockName, {
        extensionName: 'card-style',
        // ... same configuration
    });
});
```

### Step 3: Manual WordPress Filter Implementation

If not using @10up/block-components:

**Add attributes (blocks.registerBlockType):**

```javascript
import { addFilter } from '@wordpress/hooks';

addFilter(
    'blocks.registerBlockType',
    'theme/group-extension-attributes',
    (settings, name) => {
        if (name !== 'core/group') {
            return settings;
        }

        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                hasCardStyle: {
                    type: 'boolean',
                    default: false,
                },
            },
        };
    }
);
```

**Add controls (editor.BlockEdit):**

```javascript
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

const withCardControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        if (props.name !== 'core/group') {
            return <BlockEdit {...props} />;
        }

        const { attributes, setAttributes } = props;

        return (
            <>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody title="Card Options">
                        <ToggleControl
                            label="Enable card style"
                            checked={attributes.hasCardStyle}
                            onChange={(value) => setAttributes({ hasCardStyle: value })}
                        />
                    </PanelBody>
                </InspectorControls>
            </>
        );
    };
}, 'withCardControls');

addFilter(
    'editor.BlockEdit',
    'theme/group-card-controls',
    withCardControls
);
```

**Add editor class (editor.BlockListBlock):**

```javascript
const withCardClass = createHigherOrderComponent((BlockListBlock) => {
    return (props) => {
        if (props.name !== 'core/group') {
            return <BlockListBlock {...props} />;
        }

        const { attributes } = props;
        const wrapperProps = {
            ...props.wrapperProps,
            className: attributes.hasCardStyle
                ? `${props.wrapperProps?.className || ''} has-card-style`
                : props.wrapperProps?.className,
        };

        return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
    };
}, 'withCardClass');

addFilter(
    'editor.BlockListBlock',
    'theme/group-card-editor-class',
    withCardClass
);
```

**Add save props (blocks.getSaveContent.extraProps):**

```javascript
addFilter(
    'blocks.getSaveContent.extraProps',
    'theme/group-card-save-props',
    (extraProps, blockType, attributes) => {
        if (blockType.name !== 'core/group') {
            return extraProps;
        }

        if (attributes.hasCardStyle) {
            extraProps.className = `${extraProps.className || ''} has-card-style`;
        }

        return extraProps;
    }
);
```

### Step 4: Server-Side Rendering (For Dynamic Blocks)

For blocks rendered server-side, use `render_block` filter:

```php
add_filter('render_block_core/group', function ($block_content, $block) {
    $has_card_style = $block['attrs']['hasCardStyle'] ?? false;

    if (!$has_card_style) {
        return $block_content;
    }

    // Use HTML tag processor for safe DOM manipulation
    $processor = new WP_HTML_Tag_Processor($block_content);

    if ($processor->next_tag()) {
        $existing_class = $processor->get_attribute('class') ?? '';
        $processor->set_attribute('class', $existing_class . ' has-card-style');
    }

    return $processor->get_updated_html();
}, 10, 2);
```

### Step 5: Add CSS

```css
/* style.css */
.has-card-style {
    background: var(--wp--preset--color--base);
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: var(--wp--preset--spacing--30);
}

.has-card-style.has-padding-0 { padding: 0; }
.has-card-style.has-padding-10 { padding: 0.625rem; }
.has-card-style.has-padding-20 { padding: 1.25rem; }
.has-card-style.has-padding-30 { padding: 1.875rem; }
```

## Common Extension Patterns

### Animation Extension

```javascript
registerBlockExtension('core/group', {
    extensionName: 'animate-on-scroll',
    attributes: {
        animation: {
            type: 'string',
            default: '',
        },
        animationDelay: {
            type: 'number',
            default: 0,
        },
    },
    classNameGenerator: ({ animation }) => animation ? `animate-${animation}` : '',
    Edit: AnimationControls,
});
```

### Visibility Extension

```javascript
registerBlockExtension('core/group', {
    extensionName: 'responsive-visibility',
    attributes: {
        hideOnMobile: { type: 'boolean', default: false },
        hideOnTablet: { type: 'boolean', default: false },
        hideOnDesktop: { type: 'boolean', default: false },
    },
    classNameGenerator: ({ hideOnMobile, hideOnTablet, hideOnDesktop }) => {
        const classes = [];
        if (hideOnMobile) classes.push('hide-on-mobile');
        if (hideOnTablet) classes.push('hide-on-tablet');
        if (hideOnDesktop) classes.push('hide-on-desktop');
        return classes.join(' ');
    },
    Edit: VisibilityControls,
});
```

### Icon Extension for Buttons

```javascript
registerBlockExtension('core/button', {
    extensionName: 'button-icon',
    attributes: {
        icon: { type: 'string', default: '' },
        iconPosition: { type: 'string', default: 'left' },
    },
    classNameGenerator: ({ icon, iconPosition }) => {
        if (!icon) return '';
        return `has-icon icon-${iconPosition}`;
    },
    Edit: IconControls,
});
```

## Verification

After implementing an extension:

1. Build assets: `npm run build`
2. Add the target block to a post
3. Check Inspector panel for new controls
4. Toggle options and verify classes apply
5. Save and check frontend rendering
6. Test with existing content (shouldn't break)

## Failure Modes

**Controls not appearing:**
- Filter not registered (check console)
- Wrong block name (use full name like `core/group`)
- Filter priority conflict

**Classes not saving:**
- Missing `blocks.getSaveContent.extraProps` filter
- Attribute not defined in `blocks.registerBlockType`
- Validation error from incorrect attribute type

**Server-side classes missing:**
- Need `render_block` filter for server-rendered blocks
- Check if block is dynamic or static

**Breaks existing blocks:**
- New attribute should have default value
- Check for attribute type mismatches

## Escalation

Ask the user when:
- Extension needs complex state management
- Should affect multiple related blocks
- Needs to interact with other extensions
- Performance concerns with many extensions
