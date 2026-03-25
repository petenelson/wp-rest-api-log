# Block Deprecations

How to handle block changes without breaking existing content.

## When Deprecations Are Needed

Add a deprecation when you change:
- Attribute names or types
- Default attribute values that affect saved content
- The save function output (static blocks only)
- Block markup structure

**Not needed for dynamic blocks when:**
- Only changing PHP render template
- Adding new attributes with defaults
- Changing editor-only behavior

## Deprecation Structure

```javascript
const deprecated = [
    {
        // Old attribute schema
        attributes: {
            oldAttrName: {
                type: 'string',
            },
        },

        // Transform old attributes to new format
        migrate(attributes) {
            return {
                newAttrName: attributes.oldAttrName,
            };
        },

        // Old save function (for static blocks)
        save({ attributes }) {
            return null; // For dynamic blocks
        },

        // Optional: Only match specific saved content
        isEligible(attributes) {
            return attributes.hasOwnProperty('oldAttrName');
        },
    },
];
```

## Common Migration Patterns

### Renaming an Attribute

```javascript
{
    attributes: {
        title: { type: 'string' }, // old name
    },
    migrate(attributes) {
        return {
            ...attributes,
            heading: attributes.title, // new name
            title: undefined,
        };
    },
    save: () => null,
}
```

### Changing Attribute Type

```javascript
{
    attributes: {
        columns: { type: 'string' }, // was string
    },
    migrate(attributes) {
        return {
            ...attributes,
            columns: parseInt(attributes.columns, 10) || 3, // now number
        };
    },
    save: () => null,
}
```

### Adding Required Attribute

```javascript
{
    attributes: {
        // Old schema without newRequired
    },
    migrate(attributes) {
        return {
            ...attributes,
            newRequired: 'default-value',
        };
    },
    isEligible(attributes) {
        return !attributes.hasOwnProperty('newRequired');
    },
    save: () => null,
}
```

### Restructuring Object Attributes

```javascript
{
    attributes: {
        imageUrl: { type: 'string' },
        imageAlt: { type: 'string' },
    },
    migrate(attributes) {
        return {
            ...attributes,
            image: {
                url: attributes.imageUrl,
                alt: attributes.imageAlt,
            },
            imageUrl: undefined,
            imageAlt: undefined,
        };
    },
    save: () => null,
}
```

## Multiple Deprecations

Stack deprecations newest-to-oldest. WordPress tries each until one matches:

```javascript
const deprecated = [
    // Most recent (v3 -> v4)
    {
        attributes: { /* v3 schema */ },
        migrate: (attrs) => ({ /* transform to v4 */ }),
        save: () => null,
    },
    // Older (v2 -> v3)
    {
        attributes: { /* v2 schema */ },
        migrate: (attrs) => ({ /* transform to v3 */ }),
        save: () => null,
    },
    // Oldest (v1 -> v2)
    {
        attributes: { /* v1 schema */ },
        migrate: (attrs) => ({ /* transform to v2 */ }),
        save: () => null,
    },
];
```

**Important:** Each deprecation migrates to the NEXT version, not directly to current. WordPress chains migrations automatically.

## Debugging Deprecations

### Check if Deprecation Matches

```javascript
isEligible(attributes, innerBlocks) {
    console.log('Checking eligibility:', attributes);
    return true; // or your condition
},
```

### Validate Migration Output

```javascript
migrate(attributes) {
    const migrated = {
        // your migration
    };
    console.log('Migrated from:', attributes, 'to:', migrated);
    return migrated;
},
```

### Common Issues

**"Invalid block" still appears:**
- Deprecation not matching (check isEligible)
- Migration returning wrong shape
- Multiple deprecations conflicting
- Missing attribute in old schema

**Migration runs repeatedly:**
- Not returning all required attributes
- Type mismatch after migration
- Check browser console for migration loops

## Best Practices

1. **Always test with existing content** before deploying
2. **Keep old deprecations** indefinitely
3. **Document why** each deprecation exists
4. **Use isEligible** to target specific content
5. **For dynamic blocks**, deprecations are simpler (save returns null)

## Example: Full Deprecation File

```javascript
// deprecations.js
export const deprecated = [
    // v1.1.0: Renamed 'title' to 'heading'
    {
        attributes: {
            title: {
                type: 'string',
                default: '',
            },
            content: {
                type: 'string',
                default: '',
            },
        },
        migrate(attributes) {
            return {
                heading: attributes.title,
                content: attributes.content,
            };
        },
        save() {
            return null;
        },
    },
];
```

```javascript
// index.js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import { BlockEdit } from './edit';
import { BlockSave } from './save';
import { deprecated } from './deprecations';

registerBlockType(metadata.name, {
    edit: BlockEdit,
    save: BlockSave,
    deprecated,
});
```
