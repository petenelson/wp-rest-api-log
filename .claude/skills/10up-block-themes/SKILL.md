---
name: 10up-block-themes
description: Build and modify WordPress block themes following 10up standards. Covers theme.json configuration, templates, template parts, patterns, style variations, and CSS organization. Use when working on block-based themes or Site Editor customizations.
license: MIT
compatibility: WordPress 6.4+, PHP 8.0+, requires block theme structure
globs:
  - theme.json
  - style.css
  - templates/**/*
  - parts/**/*
  - partials/**/*
  - patterns/**/*.php
  - styles/**/*.json
metadata:
  author: 10up
  version: "1.0"
---

# 10up Block Themes

This skill guides you through block theme development following 10up standards and WordPress Site Editor best practices.

## When to Use

- Creating a new block theme
- Modifying theme.json settings
- Adding or editing templates
- Creating template parts
- Registering block patterns
- Setting up style variations
- Troubleshooting styles not applying

## Key Concepts

### Block Theme vs Classic Theme

| Aspect | Block Theme | Classic Theme |
|--------|-------------|---------------|
| Templates | HTML files in `/templates/` | PHP files in root |
| Styling | theme.json + CSS | style.css + PHP |
| Editing | Site Editor | Theme Customizer |
| Patterns | PHP files in `/patterns/` | Optional |

### Theme Structure

```
theme-name/
├── style.css                 # Theme metadata (required)
├── theme.json                # Design tokens and settings
├── templates/                # Full page templates
│   ├── index.html            # Required fallback
│   ├── single.html           # Single post
│   ├── page.html             # Single page
│   ├── archive.html          # Archive pages
│   └── 404.html              # Not found
├── parts/                    # Reusable template parts
│   ├── header.html
│   └── footer.html
├── patterns/                 # Block patterns
│   └── card.php
├── styles/                   # Style variations
│   └── dark.json
└── assets/
    └── css/
        └── blocks/           # Per-block CSS
```

## Procedure

### Step 1: Configure theme.json

The theme.json file is the **design token source** for block themes.

**Minimal structure:**

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "settings": {
    "color": {
      "palette": []
    },
    "typography": {
      "fontSizes": [],
      "fontFamilies": []
    },
    "spacing": {
      "units": ["px", "em", "rem", "%"],
      "spacingSizes": []
    },
    "layout": {
      "contentSize": "800px",
      "wideSize": "1200px"
    }
  },
  "styles": {
    "color": {
      "background": "var(--wp--preset--color--base)",
      "text": "var(--wp--preset--color--contrast)"
    }
  }
}
```

See [references/theme-json.md](references/theme-json.md) for complete configuration.

See [references/template-hierarchy.md](references/template-hierarchy.md) for template loading order.

### Step 2: Create Templates

Templates are HTML files containing block markup.

**templates/index.html (required):**

```html
<!-- wp:template-part {"slug":"header","tagName":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
    <!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post"}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
            <!-- wp:post-title {"isLink":true} /-->
            <!-- wp:post-excerpt /-->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination -->
            <!-- wp:query-pagination-previous /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next /-->
        <!-- /wp:query-pagination -->
    </div>
    <!-- /wp:query -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
```

**Key points:**
- Use block comments for markup
- Reference template parts with `wp:template-part`
- Use proper semantic HTML tags via `tagName`

### Step 3: Create Template Parts

Template parts are reusable sections.

**parts/header.html:**

```html
<!-- wp:group {"tagName":"header","className":"site-header","layout":{"type":"constrained"}} -->
<header class="wp-block-group site-header">
    <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
    <div class="wp-block-group">
        <!-- wp:site-logo /-->
        <!-- wp:site-title /-->
        <!-- wp:navigation /-->
    </div>
    <!-- /wp:group -->
</header>
<!-- /wp:group -->
```

### Step 4: Register Patterns

Patterns are PHP files with block markup.

**patterns/card.php:**

```php
<?php
/**
 * Title: Card
 * Slug: theme-name/card
 * Description: A card component with image and text
 * Categories: components
 * Keywords: card, box, container
 * Viewport Width: 600
 */
?>
<!-- wp:group {"className":"card","style":{"border":{"radius":"8px"}}} -->
<div class="wp-block-group card">
    <!-- wp:image {"sizeSlug":"medium"} /-->
    <!-- wp:heading {"level":3} -->
    <h3>Card Title</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph -->
    <p>Card description goes here.</p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

**Pattern header fields:**
- `Title` - Display name in editor
- `Slug` - Unique identifier (theme-name/pattern-name)
- `Description` - Shown in pattern browser
- `Categories` - Group patterns (can be custom)
- `Keywords` - Improve searchability
- `Viewport Width` - Preview width in inserter

### Step 5: Organize CSS

**10up CSS organization pattern:**

```
assets/css/
├── style.css              # Main stylesheet
├── editor.css             # Editor-specific styles
└── blocks/
    ├── core/
    │   └── group.css      # Core block overrides
    └── theme-name/
        └── hero.css       # Custom block styles
```

**Per-block CSS in theme.json:**

```json
{
  "styles": {
    "blocks": {
      "core/group": {
        "css": "& .custom-class { padding: 2rem; }"
      }
    }
  }
}
```

**10up guideline:** Write actual CSS in separate files, use theme.json for design tokens only.

For comprehensive CSS authoring guidelines including specificity management, naming conventions, and accessibility requirements, see [10up-css](../10up-css/SKILL.md).

### Step 6: Add Style Variations

Style variations offer alternative designs.

**styles/dark.json:**

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "Dark",
  "settings": {},
  "styles": {
    "color": {
      "background": "#1a1a1a",
      "text": "#ffffff"
    }
  }
}
```

## Verification

After making theme changes:

1. Clear browser cache and any server caches
2. Check Site Editor (Appearance → Editor)
3. Verify styles apply to frontend
4. Test in different browsers
5. Check for PHP errors in debug.log

## Failure Modes

**Styles not applying:**
- theme.json syntax error (validate JSON)
- Cache not cleared
- CSS specificity issue
- Wrong preset name

**Template not recognized:**
- Incorrect file location
- Invalid block markup
- Missing closing tags

**Pattern not showing:**
- Missing required header fields
- Incorrect slug format
- PHP syntax error

**Site Editor not loading:**
- Block theme requirements not met
- theme.json parsing error
- JavaScript console errors

See [references/debugging.md](references/debugging.md) for troubleshooting.

## Escalation

Ask the user when:
- Complex layout requirements
- Custom block integration needed
- Performance concerns
- Migration from classic theme
