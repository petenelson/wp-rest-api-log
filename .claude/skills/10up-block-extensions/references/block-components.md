# @10up/block-components Reference

The `@10up/block-components` library provides utilities for extending WordPress blocks.

## Installation

```bash
npm install @10up/block-components
```

## registerBlockExtension

Simplified API for extending blocks with custom attributes and controls.

### Basic Usage

```javascript
import { registerBlockExtension } from '@10up/block-components';
import { ToggleControl, RangeControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
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

### API Reference

```typescript
registerBlockExtension(blockName: string, options: ExtensionOptions): void

interface ExtensionOptions {
    // Unique name for this extension
    extensionName: string;

    // Attributes to add to the block
    attributes: {
        [key: string]: {
            type: 'string' | 'boolean' | 'number' | 'object' | 'array';
            default?: any;
        };
    };

    // Generate CSS class names from attributes
    classNameGenerator?: (attributes: object) => string;

    // Generate inline styles from attributes
    inlineStyleGenerator?: (attributes: object) => object;

    // React component for inspector controls
    Edit: React.ComponentType<{
        attributes: object;
        setAttributes: (attrs: object) => void;
    }>;
}
```

### classNameGenerator

Generates CSS classes added to both editor and frontend:

```javascript
classNameGenerator: ({ isHighlighted, highlightColor, spacing }) => {
    const classes = [];

    if (isHighlighted) {
        classes.push('is-highlighted');
        if (highlightColor) {
            classes.push(`has-${highlightColor}-highlight`);
        }
    }

    if (spacing) {
        classes.push(`has-spacing-${spacing}`);
    }

    return classes.join(' ');
},
```

### inlineStyleGenerator

Generates inline styles:

```javascript
inlineStyleGenerator: ({ customPadding, customMargin }) => {
    const styles = {};

    if (customPadding) {
        styles.padding = `${customPadding}px`;
    }

    if (customMargin) {
        styles.margin = `${customMargin}px`;
    }

    return styles;
},
```

### Extending Multiple Blocks

```javascript
const BLOCKS = ['core/group', 'core/columns', 'core/cover'];

BLOCKS.forEach((blockName) => {
    registerBlockExtension(blockName, {
        extensionName: 'animation',
        attributes: {
            animationType: {
                type: 'string',
                default: '',
            },
            animationDuration: {
                type: 'number',
                default: 300,
            },
        },
        classNameGenerator: ({ animationType }) => {
            return animationType ? `animate-${animationType}` : '';
        },
        Edit: AnimationControls,
    });
});
```

## Other Utilities

### ContentPicker

Component for selecting posts/pages:

```javascript
import { ContentPicker } from '@10up/block-components';

<ContentPicker
    label="Select Post"
    contentTypes={['post', 'page']}
    content={selectedPosts}  // Array: [{id: 1, type: 'post', uuid: '...'}]
    onPickChange={(pickedContent) => setAttributes({ selectedPosts: pickedContent })}
    maxContentItems={3}
    isOrderable={true}
    mode="post"  // 'post', 'user', or 'term'
/>
```

**ContentPicker Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `onPickChange` | `function` | - | Callback when selection changes, receives array |
| `content` | `array` | `[]` | Pre-selected items: `[{id, type, uuid}]` |
| `contentTypes` | `array` | `['post', 'page']` | Post types or taxonomies to search |
| `maxContentItems` | `number` | `1` | Max items user can select |
| `mode` | `string` | `'post'` | `'post'`, `'user'`, or `'term'` |
| `isOrderable` | `bool` | `false` | Allow reordering (requires maxContentItems > 1) |
| `uniqueContentItems` | `bool` | `true` | Prevent duplicate selections |
| `excludeCurrentPost` | `bool` | `true` | Exclude current post from results |

### ContentSearch

Search component for content:

```javascript
import { ContentSearch } from '@10up/block-components';

<ContentSearch
    contentTypes={['post']}
    onSelectItem={(item) => console.log('Selected:', item)}
    placeholder="Search posts..."
    mode="post"
/>
```

### Image

Enhanced image component:

```javascript
import { Image } from '@10up/block-components';

<Image
    id={imageId}
    size="large"
    onSelect={(media) => setAttributes({ imageId: media.id })}
/>
```

### Link

Inline link component with two callbacks - one for text changes and one for URL changes:

```javascript
import { Link } from '@10up/block-components';
import { useBlockProps } from '@wordpress/block-editor';

const BlockEdit = ({ attributes, setAttributes }) => {
    const { linkText, linkUrl, opensInNewTab } = attributes;
    const blockProps = useBlockProps();

    const handleTextChange = (value) => setAttributes({ linkText: value });

    const handleLinkChange = (value) => setAttributes({
        linkUrl: value?.url,
        opensInNewTab: value?.opensInNewTab,
        linkText: value?.title ?? linkText,
    });

    const handleLinkRemove = () => setAttributes({
        linkUrl: null,
        opensInNewTab: null,
    });

    return (
        <div {...blockProps}>
            <Link
                value={linkText}
                url={linkUrl}
                opensInNewTab={opensInNewTab}
                onTextChange={handleTextChange}
                onLinkChange={handleLinkChange}
                onLinkRemove={handleLinkRemove}
                placeholder="Enter Link Text here..."
            />
        </div>
    );
};
```

**Link Props:**

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `value` | `string` | - | The text inside the link |
| `url` | `string` | - | The href URL |
| `opensInNewTab` | `bool` | `false` | Open in new tab |
| `onTextChange` | `function` | - | Callback when text changes |
| `onLinkChange` | `function` | - | Callback when URL changes (receives `{url, opensInNewTab, title}`) |
| `onLinkRemove` | `function` | - | Callback when link is removed |
| `placeholder` | `string` | `'Link text ...'` | Placeholder text |

## Modern Approach: Block Filters

While `classNameGenerator` and `inlineStyleGenerator` are still supported, the recommended approach is to use block filters for better deprecation safety.

### JavaScript Filter

Use the `blocks.getSaveContent.extraProps` filter to add attributes in the editor:

```javascript
import { addFilter } from '@wordpress/hooks';

function addCardStyleProps(props, blockType, attributes) {
    if (blockType.name !== 'core/group') {
        return props;
    }

    const { hasCardStyle, cardPadding } = attributes;

    if (hasCardStyle) {
        props.className = props.className || '';
        props.className += ` has-card-style has-padding-${cardPadding}`;
    }

    return props;
}

addFilter(
    'blocks.getSaveContent.extraProps',
    'theme/card-style-props',
    addCardStyleProps
);
```

### PHP Filter

Apply the same logic on the frontend using `render_block`:

```php
function add_card_style_props( $block_content, $block ) {
    if ( 'core/group' !== $block['blockName'] ) {
        return $block_content;
    }

    $has_card_style = $block['attrs']['hasCardStyle'] ?? false;
    $card_padding = $block['attrs']['cardPadding'] ?? 20;

    if ( ! $has_card_style ) {
        return $block_content;
    }

    $classes = sprintf(
        'has-card-style has-padding-%d',
        absint( $card_padding )
    );

    // Add classes to the block wrapper
    $processor = new WP_HTML_Tag_Processor( $block_content );
    if ( $processor->next_tag() ) {
        $processor->add_class( $classes );
        return $processor->get_updated_html();
    }

    return $block_content;
}

add_filter( 'render_block', 'add_card_style_props', 10, 2 );
```

### Why This Approach Is Better

This pattern is safer for block deprecations because:

1. **Markup parity**: The same logic runs in both JavaScript and PHP
2. **Deprecation safety**: Changes to how classes or styles are applied do not require block deprecations
3. **Server-side control**: You can modify the output without touching saved block markup
4. **Easier maintenance**: Logic is centralized in filters rather than saved in block content

### When to Use Generators vs Filters

**Use block filters for:**
- Inline styles (critical for deprecation safety)
- Dynamic class generation based on attributes
- Any output that might change over time

**Generators are acceptable for:**
- Simple, stable class names that will not change
- Rapid prototyping

In short, if there is any chance the styling logic might evolve, use block filters.

## Best Practices

1. **Use unique extension names** to avoid conflicts
2. **Prefer block filters** over generators for styles and dynamic classes
3. **Maintain markup parity** between JavaScript and PHP filters
4. **Provide defaults** for all attributes
5. **Keep Edit component focused** on controls only
6. **Use WP_HTML_Tag_Processor** for safe HTML manipulation in PHP
