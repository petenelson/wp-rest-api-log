---
name: 10up-interactivity-api
description: Build interactive blocks using WordPress Interactivity API. Covers data-wp-* directives, stores, context management, and server-side rendering patterns. Use when adding client-side interactivity to blocks without a full JavaScript framework.
license: MIT
compatibility: WordPress 6.5+, PHP 8.0+, requires viewScriptModule support
globs:
  - "**/view.js"
  - "**/view.ts"
  - blocks/**/*
  - includes/blocks/**/*
metadata:
  author: 10up
  version: "1.0"
---

# 10up Interactivity API

This skill guides you through building interactive blocks using the WordPress Interactivity API, a lightweight reactive system for block-based interactivity.

## When to Use

- Adding click handlers, toggles, or state changes to blocks
- Building accordions, tabs, modals, or carousels
- Creating interactive navigation or menus
- Replacing jQuery-based interactions with modern patterns
- Any client-side interactivity that doesn't need a full SPA framework

## Key Concepts

The Interactivity API provides:
- **Directives** - HTML attributes that bind behavior (`data-wp-*`)
- **Stores** - State management with actions and callbacks
- **Context** - Scoped state for component instances
- **Server rendering** - Initial state from PHP

## Procedure

### Step 1: Enable Interactivity in block.json

```json
{
  "name": "tenup/accordion",
  "supports": {
    "interactivity": true
  },
  "viewScriptModule": "file:./view.js"
}
```

**Important:** Use `viewScriptModule` (ES module), not `viewScript` (classic script).

### Step 2: Set Up the Store (view.js)

Create the store with state, actions, and callbacks:

```javascript
import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store('tenup/accordion', {
    state: {
        // Global state (shared across all instances)
        get isAnyOpen() {
            return Object.values(state.items).some(item => item.isOpen);
        }
    },
    actions: {
        toggle() {
            const context = getContext();
            context.isOpen = !context.isOpen;
        },
        open() {
            const context = getContext();
            context.isOpen = true;
        },
        close() {
            const context = getContext();
            context.isOpen = false;
        }
    },
    callbacks: {
        onInit() {
            const context = getContext();
            // Runs when element with data-wp-init mounts
            console.log('Accordion initialized:', context.id);
        }
    }
});
```

### Step 3: Add Directives to PHP Template (markup.php)

```php
<?php
/**
 * Accordion block markup
 */

$accordion_id = wp_unique_id('accordion-');
$is_open = $attributes['defaultOpen'] ?? false;

// Set up interactivity context
$context = [
    'id' => $accordion_id,
    'isOpen' => $is_open,
];

$wrapper_attributes = get_block_wrapper_attributes([
    'data-wp-interactive' => 'tenup/accordion',
    'data-wp-context' => wp_json_encode($context),
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <button
        data-wp-on--click="actions.toggle"
        data-wp-bind--aria-expanded="context.isOpen"
        aria-controls="<?php echo esc_attr($accordion_id); ?>-content"
    >
        <?php echo esc_html($attributes['title'] ?? 'Toggle'); ?>
    </button>

    <div
        id="<?php echo esc_attr($accordion_id); ?>-content"
        data-wp-bind--hidden="!context.isOpen"
        data-wp-class--is-open="context.isOpen"
    >
        <?php echo $content; // phpcs:ignore ?>
    </div>
</div>
```

### Step 4: Understand Core Directives

| Directive | Purpose | Example |
|-----------|---------|---------|
| `data-wp-interactive` | Mark interactive region and namespace | `data-wp-interactive="tenup/accordion"` |
| `data-wp-context` | Provide scoped state (JSON) | `data-wp-context='{"isOpen":false}'` |
| `data-wp-on--{event}` | Event handler | `data-wp-on--click="actions.toggle"` |
| `data-wp-bind--{attr}` | Bind attribute to state | `data-wp-bind--hidden="!context.isOpen"` |
| `data-wp-class--{name}` | Toggle CSS class | `data-wp-class--is-active="context.isActive"` |
| `data-wp-text` | Set text content | `data-wp-text="context.label"` |
| `data-wp-init` | Run on mount | `data-wp-init="callbacks.onInit"` |
| `data-wp-watch` | React to state changes | `data-wp-watch="callbacks.onStateChange"` |

See [references/directives.md](references/directives.md) for complete reference.

### Step 5: Server-Side State Initialization

For complex initial state, use `wp_interactivity_state()`:

```php
// In your block's render callback or plugin
wp_interactivity_state('tenup/accordion', [
    'items' => [],
    'allowMultiple' => true,
]);
```

This merges with client-side store state.

### Step 6: Handle Context Inheritance

Context flows down the DOM tree:

```html
<div data-wp-interactive="tenup/tabs" data-wp-context='{"activeTab":0}'>
    <!-- Child elements can access context.activeTab -->
    <button data-wp-on--click="actions.setTab" data-wp-context='{"tabIndex":0}'>
        <!-- This element has both activeTab and tabIndex in context -->
    </button>
</div>
```

Access nested context in actions:

```javascript
actions: {
    setTab() {
        const context = getContext();
        // context.tabIndex is from the button
        // context.activeTab is from the parent
        context.activeTab = context.tabIndex;
    }
}
```

## Common Patterns

### Toggle Pattern

```php
<button
    data-wp-on--click="actions.toggle"
    data-wp-bind--aria-pressed="context.isActive"
>
    Toggle
</button>
```

```javascript
actions: {
    toggle() {
        const context = getContext();
        context.isActive = !context.isActive;
    }
}
```

### Show/Hide Pattern

```php
<div data-wp-bind--hidden="!context.isVisible">
    Content
</div>
```

### CSS Class Toggle

```php
<div
    data-wp-class--is-open="context.isOpen"
    data-wp-class--is-animating="state.isAnimating"
>
```

### Multiple Event Handlers

```php
<button
    data-wp-on--click="actions.handleClick"
    data-wp-on--keydown="actions.handleKeydown"
    data-wp-on--focus="actions.handleFocus"
>
```

### Derived State (Getters)

```javascript
state: {
    items: [],
    get count() {
        return state.items.length;
    },
    get isEmpty() {
        return state.items.length === 0;
    }
}
```

## Verification

After implementing interactivity:

1. Build assets: `npm run build`
2. Clear caches
3. Test in browser (check console for errors)
4. Verify initial state from server renders correctly
5. Test all interactive behaviors
6. Check accessibility (keyboard navigation, ARIA attributes)

## Failure Modes

**"Nothing happens on click":**
- Check `data-wp-interactive` is on ancestor element
- Verify store namespace matches
- Check browser console for errors
- Ensure `viewScriptModule` is in block.json

**"Context is undefined":**
- Missing `data-wp-context` on element or ancestor
- JSON syntax error in context attribute
- Namespace mismatch

**"State doesn't update":**
- Not using `getContext()` for instance state
- Mutating state incorrectly
- Missing reactive binding (`data-wp-bind--*`)

**"Works in dev, fails in production":**
- Build not run
- Module not enqueued
- Caching issue

See [references/debugging.md](references/debugging.md) for troubleshooting.

## Escalation

Ask the user when:
- Complex state management across multiple blocks needed
- Integration with external APIs or data sources
- Animation/transition requirements
- Accessibility patterns unclear
