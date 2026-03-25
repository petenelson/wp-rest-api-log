# block.json Reference

Complete reference for block.json metadata fields.

## Required Fields

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Unique block identifier (namespace/block-name) |
| `title` | string | Human-readable block title |

## Recommended Fields

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "namespace/block-name",
  "version": "1.0.0",
  "title": "Block Title",
  "category": "common",
  "description": "Block description for inserter",
  "textdomain": "text-domain",
  "keywords": ["keyword1", "keyword2"],
  "icon": "dashicons-icon-name"
}
```

## Attributes

Define the data your block stores:

```json
{
  "attributes": {
    "stringAttr": {
      "type": "string",
      "default": ""
    },
    "boolAttr": {
      "type": "boolean",
      "default": false
    },
    "numberAttr": {
      "type": "number",
      "default": 0
    },
    "arrayAttr": {
      "type": "array",
      "default": [],
      "items": {
        "type": "object"
      }
    },
    "objectAttr": {
      "type": "object",
      "default": {}
    }
  }
}
```

### Attribute Sources

For static blocks (not 10up recommended), attributes can be sourced from saved HTML:

```json
{
  "attributes": {
    "content": {
      "type": "string",
      "source": "html",
      "selector": "p"
    },
    "url": {
      "type": "string",
      "source": "attribute",
      "selector": "a",
      "attribute": "href"
    }
  }
}
```

## Supports

Configure block capabilities:

```json
{
  "supports": {
    "align": true,
    "align": ["wide", "full"],
    "anchor": true,
    "className": true,
    "color": {
      "background": true,
      "text": true,
      "link": true,
      "gradients": true
    },
    "spacing": {
      "margin": true,
      "padding": true,
      "blockGap": true
    },
    "typography": {
      "fontSize": true,
      "lineHeight": true,
      "fontFamily": true
    },
    "html": false,
    "multiple": true,
    "reusable": true,
    "inserter": true
  }
}
```

## Scripts and Styles

```json
{
  "editorScript": "file:./index.js",
  "editorStyle": "file:./editor.css",
  "style": "file:./style.css",
  "viewScript": "file:./view.js",
  "viewScriptModule": "file:./view-module.js",
  "render": "file:./markup.php"
}
```

| Field | When Loaded | Purpose |
|-------|-------------|---------|
| `editorScript` | Editor only | Block registration and edit component |
| `editorStyle` | Editor only | Editor-specific styles |
| `style` | Both | Styles for frontend and editor |
| `viewScript` | Frontend only | Classic script for frontend |
| `viewScriptModule` | Frontend only | ES module for frontend (Interactivity API) |
| `render` | Frontend only | PHP template for dynamic blocks |

## Example Data

Provide example attributes for block preview:

```json
{
  "example": {
    "attributes": {
      "title": "Example Title",
      "columns": 3
    }
  }
}
```

## Parent/Ancestor Constraints

Limit where block can be inserted:

```json
{
  "parent": ["namespace/parent-block"],
  "ancestor": ["namespace/ancestor-block"],
  "allowedBlocks": ["core/paragraph", "core/heading"]
}
```

## Categories

Built-in categories:
- `text` - Text blocks
- `media` - Media blocks
- `design` - Design/layout blocks
- `widgets` - Widget blocks
- `theme` - Theme blocks
- `embed` - Embed blocks

Register custom category in PHP:
```php
add_filter('block_categories_all', function($categories) {
    return array_merge($categories, [
        [
            'slug'  => 'custom-category',
            'title' => 'Custom Category',
        ],
    ]);
});
```

## Complete Example

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "tenup/hero-section",
  "version": "1.0.0",
  "title": "Hero Section",
  "category": "design",
  "description": "A hero section with heading, text, and call-to-action",
  "textdomain": "theme-name",
  "keywords": ["hero", "banner", "header"],
  "icon": "cover-image",
  "attributes": {
    "heading": {
      "type": "string",
      "default": ""
    },
    "subheading": {
      "type": "string",
      "default": ""
    },
    "backgroundImage": {
      "type": "object",
      "default": {}
    },
    "overlayOpacity": {
      "type": "number",
      "default": 50
    }
  },
  "supports": {
    "align": ["wide", "full"],
    "color": {
      "background": true,
      "text": true,
      "gradients": true
    },
    "spacing": {
      "padding": true
    }
  },
  "editorScript": "file:./index.js",
  "style": "file:./style.css",
  "render": "file:./markup.php",
  "example": {
    "attributes": {
      "heading": "Welcome to Our Site",
      "subheading": "Discover what we have to offer"
    }
  }
}
```
