---
name: ignite-wp
description: Build with Ignite WP, 10up's modular WordPress block-based UI kit. Covers 50+ Gutenberg blocks, plugin APIs, Interactivity API stores, theme.json configuration, CSS patterns, and design system tokens. Use when working with tenup/* blocks, Ignite plugins, or projects using the Ignite theme.
license: MIT
compatibility: WordPress 6.6+, PHP 8.2+, Node.js 20+
globs:
  - "**/ignite-wp-*/**"
  - "**/tenup/**"
  - "**/wp-block-tenup-*"
  - "**/theme.json"
  - "**/view-module.js"
  - "**/is-style-surface-*"
  - "**/is-typography-preset-*"
metadata:
  author: 10up
  version: "2.0"
---

# Ignite WP Development

Ignite WP is 10up's modular WordPress block-based UI kit providing 50+ enterprise-grade Gutenberg blocks across 18 plugins. Built on four pillars: **Block-First**, **Accessible** (WCAG 2.1 AA), **Performant**, and **Extensible**.

## When to Use

- Working with any `tenup/*` block (accordion, carousel, tabs, modal, etc.)
- Configuring Ignite plugins via theme.json
- Creating custom blocks that integrate with Ignite components
- Extending or customizing Ignite block behavior via filters/hooks
- Building a theme using Ignite design system tokens
- Implementing frontend interactivity with Ignite stores
- Installing or managing Ignite plugins via CLI or Composer
- Styling blocks using section styles or typography presets
- Applying CSS patterns that integrate with Ignite design tokens

## Core Concepts

### Block Namespace

All Ignite blocks use the `tenup/` namespace prefix:

- `tenup/accordion`, `tenup/accordion-group`, `tenup/accordion-header`, `tenup/accordion-content`
- `tenup/carousel`, `tenup/carousel-item`
- `tenup/modal`, `tenup/icon`, `tenup/tabs`
- `tenup/site-header`, `tenup/navigation`, `tenup/navigation-megamenu`

### Plugin Architecture

Each plugin follows a consistent structure:

```
ignite-wp-[name]/
├── ignite-wp-[name].php      # Main plugin file
├── blocks/                   # Gutenberg blocks
│   └── block-name/
│       ├── block.json        # Block metadata
│       ├── index.js          # Editor entry
│       ├── edit.js           # Editor component
│       ├── markup.php        # Server-side render
│       └── view-module.js    # Interactivity API
├── src/                      # PHP classes
│   ├── PluginCore.php        # Main class
│   └── Blocks.php            # Block registration
└── package.json              # Build config (10up-toolkit)
```

### Interactivity API Stores

Ignite uses WordPress Interactivity API for frontend behavior:

| Store | Plugin | Purpose |
|-------|--------|---------|
| `tenup/accordion` | Accordion | Expand/collapse panels |
| `tenup/carousel` | Carousel | Slide navigation, SplideJS |
| `tenup/modal` | Modal | Dialog show/hide, a11y-dialog |
| `tenup/site-header` | Navigation | Header state, regions, Headroom |
| `tenup/query-filter` | Query Filter | AJAX filtering, load more |
| `tenup/stats` | Stats | Count animation on scroll |

## Procedure

### Step 1: Identify the Plugin

Determine which Ignite plugin handles the block or feature:

| Block Pattern | Plugin |
|--------------|--------|
| `tenup/accordion*` | ignite-wp-accordion |
| `tenup/carousel*` | ignite-wp-carousel |
| `tenup/modal` | ignite-wp-modal |
| `tenup/tabs` | ignite-wp-tabs |
| `tenup/icon` | ignite-wp-icons |
| `tenup/timeline*` | ignite-wp-timeline |
| `tenup/site-header`, `tenup/navigation*` | ignite-wp-navigation |
| `tenup/post-picker`, `tenup/time-to-read` | ignite-wp-post-picker |
| `tenup/query-*-filter`, `tenup/query-load-more` | ignite-wp-query-filter |
| `tenup/post-meta*` | ignite-wp-post-meta-blocks |
| `tenup/stats` | ignite-wp-stats |
| `tenup/copyright` | ignite-wp-copyright |

### Step 2: Configure via theme.json

Most Ignite blocks are customized through theme.json, not code:

```json
{
  "settings": {
    "blocks": {
      "tenup/accordion-header": {
        "custom": {
          "tenup": {
            "icon": { "iconSet": "ignite-wp", "iconName": "chevron-down" },
            ":expanded": {
              "icon": { "iconSet": "ignite-wp", "iconName": "chevron-up" }
            },
            "iconPosition": "right"
          }
        }
      }
    }
  }
}
```

See [references/theme-json-config.md](references/theme-json-config.md) for all block configurations.

### Step 3: Extend with PHP Filters

Customize block output and behavior using WordPress filters:

```php
// Modify carousel state
add_filter('render_block_tenup/carousel', function($content) {
    $carousel_state = wp_interactivity_state('tenup/carousel');
    $carousel_state['default']['perPage'] = 2;
    wp_interactivity_state('tenup/carousel', $carousel_state);
    return $content;
});

// Add custom modal trigger blocks
add_filter('tenup_modal_supported_blocks', function($blocks) {
    $blocks[] = 'namespace/custom-block';
    return $blocks;
});
```

See [references/php-filters.md](references/php-filters.md) for complete filter reference.

### Step 4: Extend with JavaScript Filters

For editor-side customization:

```javascript
import { addFilter } from '@wordpress/hooks';

// Change carousel allowed blocks
addFilter('tenup.carousel.allowedBlocks', 'namespace/filter', (blocks) => {
    return [...blocks, 'namespace/custom-slide'];
});

// Change inner block template
addFilter('tenup.carousel.template', 'namespace/filter', () => {
    return [['tenup/carousel-item'], ['tenup/carousel-item']];
});
```

### Step 5: Use Interactivity API

For frontend behavior, extend existing stores:

```javascript
import { store, getContext } from '@wordpress/interactivity';

// Extend modal store with custom actions
store('tenup/modal', {
    actions: {
        onShow() {
            console.log('Modal opened');
        },
        onHide() {
            console.log('Modal closed');
        }
    }
});
```

See [references/interactivity-api.md](references/interactivity-api.md) for store details.

## Plugin Quick Reference

### Ignite Core (Required)

Foundation plugin providing shared utilities:

**PHP Helpers:**

```php
use function IgniteWPCore\Helpers\register_icons;
use function IgniteWPCore\Helpers\render_icon;
use function IgniteWPCore\Helpers\get_fluid_clamp_value;

// Register custom icons
register_icons([
    'name'  => 'my-icons',
    'label' => 'My Icons',
    'icons' => $icons,
]);

// Render an icon
render_icon('ignite-wp', 'chevron-down', ['class' => 'my-class']);

// Generate fluid clamp value
$clamp = get_fluid_clamp_value(16, 24, 375, 1440, 'rem');
```

**Default Icons:** `check`, `chevron-down/left/right/up`, `close`, `menu`, `minus`, `plus`, `search`, `pause`, `play`, `facebook`, `github`, `instagram`, `linkedin`, `twitter`, `youtube`

### Accordion

```json
{
  "settings": {
    "blocks": {
      "tenup/accordion-header": {
        "custom": {
          "tenup": {
            "icon": { "iconSet": "ignite-wp", "iconName": "plus" },
            ":expanded": { "icon": { "iconSet": "ignite-wp", "iconName": "minus" } },
            "iconPosition": "right"
          }
        }
      }
    }
  }
}
```

### Carousel (SplideJS)

```json
{
  "settings": {
    "blocks": {
      "tenup/carousel": {
        "custom": {
          "tenup": {
            "showDots": true,
            "showArrows": true,
            "perPage": 1,
            "slideType": "slide",
            "icons": {
              "arrowNext": { "iconSet": "ignite-wp", "iconName": "chevron-right" },
              "arrowPrevious": { "iconSet": "ignite-wp", "iconName": "chevron-left" }
            }
          }
        }
      }
    }
  }
}
```

### Modal (a11y-dialog)

```json
{
  "settings": {
    "blocks": {
      "tenup/modal": {
        "custom": {
          "tenup": {
            "supportsOverlayColorPalette": true
          }
        }
      }
    }
  }
}
```

**PHP Filters:**

```php
// Supported trigger blocks (default: button, image, media-text)
add_filter('tenup_modal_supported_blocks', fn($blocks) => [...$blocks, 'my/block']);

// Inner block template
add_filter('tenup_modal_inner_block_template', fn() => [
    ['core/heading'],
    ['core/embed']
]);
```

### Navigation

```json
{
  "settings": {
    "blocks": {
      "tenup/site-header": {
        "custom": {
          "tenup": {
            "enableBackdrop": true,
            "navigationBreakpoint": "768px",
            "enableHeadroom": true
          }
        }
      }
    }
  }
}
```

**Interactivity Store State:**

```javascript
const { state } = store('tenup/site-header');
// state.expandedRegion - currently expanded region
// state.isSearchExpanded - getter for search state
// state.headerHeight - also set as --header-height CSS var
```

See [references/plugins.md](references/plugins.md) for complete plugin documentation.

## CSS Patterns

### Core Philosophy

- **theme.json is for tokens** - Define design tokens, palettes, spacing scales
- **CSS is for styling** - Implement visual presentation, hover states, responsive behavior

### Specificity Pattern

Use `:root` prefix with `[class]` to override WordPress defaults:

```css
:root .wp-block-button__link[class] {
  background-color: var(--wp--custom--color--accent);
  transition: all var(--wp--custom--transition--duration-normal);
}

:root .wp-block-button__link[class]:hover {
  background-color: var(--wp--custom--color--accent-hover);
}
```

### Section Styles

Never set backgroundColor/textColor directly on Group/Columns. Use section styles:

| Style | Class | Use Case |
|-------|-------|----------|
| Surface Inverted | `is-style-surface-inverted` | Light bg with dark text |
| Surface Accent | `is-style-surface-accent` | Accent bg with accent borders |
| Surface Primary | `is-style-surface-primary` | Primary color scheme |

### Typography Presets

Bundle font-size, weight, line-height together:

```html
<!-- Requires both attribute and className -->
<h1 class="is-typography-preset-display-lg">Title</h1>
```

Available: `display-lg`, `display-md`, `heading-1/2/3/4`, `body-lg`, `body-base`, `body-sm`, `code`

### Block Class Convention

```css
.wp-block-tenup-[plugin-name]           /* Block container */
.wp-block-tenup-[plugin-name]__element  /* Child element (BEM) */
.wp-block-tenup-[plugin-name]--modifier /* State/variant (BEM) */
```

### Design System Tokens

Always use theme.json design tokens:

```css
/* Spacing */
var(--wp--custom--spacing--1)  /* 4px */
var(--wp--custom--spacing--4)  /* 16px */
var(--wp--custom--spacing--8)  /* 32px */

/* Colors */
var(--wp--custom--color--surface)
var(--wp--custom--color--text--primary)
var(--wp--custom--color--border)
var(--wp--custom--color--accent)

/* Radius */
var(--wp--custom--radius--sm)
var(--wp--custom--radius--md)

/* Transitions */
var(--wp--custom--transition--duration-normal)
var(--wp--custom--transition--ease-out)
```

See [references/design-tokens.md](references/design-tokens.md) for complete token reference.
See [references/css-patterns.md](references/css-patterns.md) for CSS best practices.

### Responsive Breakpoint

```css
/* Mobile-first, primary breakpoint at 768px */
@media (min-width: 768px) {
    /* Desktop styles */
}
```

### Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
    .animated-element {
        animation: none;
        transition: none;
    }
}
```

## Installation

### Ignite CLI (Recommended)

```bash
# Interactive selection
npx @10up/ignite-cli install

# Direct installation
npx @10up/ignite-cli install accordion carousel icons

# Download without Composer
npx @10up/ignite-cli download accordion
```

### Eject for Customization

```bash
npx @10up/ignite-cli eject accordion
```

See [references/installation.md](references/installation.md) for complete installation guide including GitHub authentication, CI/CD setup, and troubleshooting.

## Verification

After making changes:

1. **Build assets:** `pnpm build` (from wp-content root)
2. **Check console:** No JavaScript errors
3. **Test editor:** Block appears and functions correctly
4. **Test frontend:** Interactivity works (accordion opens, carousel slides)
5. **Mobile test:** Responsive at 375px, 768px, 1024px
6. **Accessibility:** Keyboard navigation, focus indicators, ARIA
7. **Dark/light mode:** Both work correctly
8. **Reduced motion:** Respects user preference

## Failure Modes

**Block not rendering:**
- Check if ignite-wp-core is active (required dependency)
- Verify plugin is activated
- Check browser console for JS errors

**Interactivity not working:**
- Ensure `data-wp-interactive` attribute is on wrapper
- Check store name matches (e.g., `tenup/accordion`)
- Verify view-module.js is built and enqueued

**theme.json config not applying:**
- JSON syntax error - validate with JSONLint
- Wrong nesting path (must be under `settings.blocks.tenup/block-name.custom.tenup`)
- Cache - clear browser and WordPress cache

**Icons not showing:**
- Icon set not registered (use Ignite Core's `register_icons`)
- Wrong iconSet/iconName combination
- Check available icons in Ignite Core

**Carousel not working:**
- SplideJS not loaded - check for script errors
- perPage attribute mismatch between editor and frontend
- Check `wp_interactivity_state()` in render filter

## Reference Files

| File | Content |
|------|---------|
| [references/plugins.md](references/plugins.md) | Complete plugin documentation |
| [references/theme-json-config.md](references/theme-json-config.md) | Block configuration via theme.json |
| [references/php-filters.md](references/php-filters.md) | PHP hooks and filters |
| [references/interactivity-api.md](references/interactivity-api.md) | Frontend store details |
| [references/design-tokens.md](references/design-tokens.md) | CSS custom properties |
| [references/css-patterns.md](references/css-patterns.md) | Styling best practices |
| [references/installation.md](references/installation.md) | Installation and CI/CD guide |

## Escalation

Ask the user when:
- Need to add a new block type to an Ignite plugin
- Custom interactivity behavior beyond extending existing stores
- Performance issues with Query Filter on large datasets
- Complex megamenu layouts with multiple template parts
- Editorial workflow customization beyond standard statuses
- GitHub authentication issues during installation
- Need to offboard plugins for client handoff
