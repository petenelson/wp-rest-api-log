# Interactivity API Directives Reference

Complete reference for all WordPress Interactivity API directives.

## Core Directives

### data-wp-interactive

Marks an element as the root of an interactive region and sets the namespace.

```html
<div data-wp-interactive="namespace/store-name">
    <!-- All child elements can use this store -->
</div>
```

**Rules:**
- Required on an ancestor of any element using directives
- Namespace should match your store registration
- Can be nested for different stores

### data-wp-context

Provides scoped state for an element and its descendants.

```html
<div data-wp-context='{"isOpen":false,"id":"item-1"}'>
    <!-- context.isOpen and context.id available here -->
</div>
```

**Rules:**
- Value must be valid JSON
- Context inherits and merges down the tree
- Use `getContext()` in actions to access

### data-wp-on--{event}

Attaches event handlers.

```html
<button data-wp-on--click="actions.handleClick">Click me</button>
<input data-wp-on--input="actions.handleInput" />
<div data-wp-on--keydown="actions.handleKeydown"></div>
```

**Supported events:**
- Mouse: `click`, `dblclick`, `mouseenter`, `mouseleave`, `mouseover`, `mouseout`
- Keyboard: `keydown`, `keyup`, `keypress`
- Form: `input`, `change`, `submit`, `focus`, `blur`
- Touch: `touchstart`, `touchend`, `touchmove`
- Other: `scroll`, `resize`, `load`

**With modifiers:**

```html
<!-- Window-level event -->
<div data-wp-on-window--resize="actions.handleResize"></div>

<!-- Document-level event -->
<div data-wp-on-document--keydown="actions.handleGlobalKey"></div>
```

### data-wp-bind--{attribute}

Binds an HTML attribute to a reactive value.

```html
<button data-wp-bind--disabled="context.isLoading">Submit</button>
<a data-wp-bind--href="context.url">Link</a>
<img data-wp-bind--src="state.imageUrl" />
<div data-wp-bind--hidden="!context.isVisible"></div>
<input data-wp-bind--value="context.inputValue" />
<button data-wp-bind--aria-expanded="context.isOpen"></button>
```

**Common bindings:**
- `hidden` - Show/hide element
- `disabled` - Disable form elements
- `aria-*` - Accessibility attributes
- `class` - Full class attribute (prefer `data-wp-class--*`)
- `style` - Inline styles (prefer CSS classes)

### data-wp-class--{classname}

Toggles a CSS class based on a boolean value.

```html
<div
    data-wp-class--is-active="context.isActive"
    data-wp-class--is-loading="state.isLoading"
    data-wp-class--has-error="context.hasError"
>
```

**Multiple classes:**

```html
<div
    data-wp-class--is-open="context.isOpen"
    data-wp-class--is-animating="state.isAnimating"
    data-wp-class--is-first="context.index === 0"
>
```

### data-wp-style--{property}

Sets inline CSS properties.

```html
<div
    data-wp-style--opacity="context.opacity"
    data-wp-style--transform="state.transform"
    data-wp-style--background-color="context.bgColor"
>
```

**Note:** Use CSS custom properties for better performance:

```html
<div data-wp-style----custom-height="context.height">
```

### data-wp-text

Sets the text content of an element.

```html
<span data-wp-text="context.label"></span>
<p data-wp-text="state.message"></p>
<button data-wp-text="context.isOpen ? 'Close' : 'Open'"></button>
```

### data-wp-html

Sets the inner HTML of an element (use carefully).

```html
<div data-wp-html="state.htmlContent"></div>
```

**Warning:** Only use with trusted content. Avoid user-generated HTML.

## Lifecycle Directives

### data-wp-init

Runs a callback when the element mounts.

```html
<div data-wp-init="callbacks.onInit">
    <!-- callbacks.onInit runs once when this element enters the DOM -->
</div>
```

```javascript
callbacks: {
    onInit() {
        const context = getContext();
        console.log('Mounted:', context.id);
    }
}
```

### data-wp-watch

Runs a callback whenever referenced state changes.

```html
<div data-wp-watch="callbacks.onStateChange">
    <!-- Reacts to any state/context referenced in the callback -->
</div>
```

```javascript
callbacks: {
    onStateChange() {
        const context = getContext();
        // This runs whenever context.value changes
        console.log('Value changed:', context.value);
    }
}
```

### data-wp-run

Runs a callback on every render (less common).

```html
<div data-wp-run="callbacks.onRender">
```

## Special Directives

### data-wp-each

Iterates over an array (experimental).

```html
<ul data-wp-interactive="tenup/list">
    <template data-wp-each="state.items">
        <li data-wp-text="context.item.name"></li>
    </template>
</ul>
```

### data-wp-key

Provides a unique key for list items.

```html
<template data-wp-each="state.items" data-wp-each-key="context.item.id">
```

## Value Expressions

Directives accept limited JavaScript expressions:

```html
<!-- Direct reference -->
data-wp-bind--hidden="context.isHidden"

<!-- Negation -->
data-wp-bind--hidden="!context.isVisible"

<!-- Object property access (dot notation only) -->
data-wp-text="context.user.name"

<!-- Nested property access -->
data-wp-text="state.settings.theme.color"
```

**Limitations:**

The Interactivity API only supports simple property paths. You cannot use:

- Comparison operators (`===`, `!==`, `>`, `<`)
- Ternary operators (`? :`)
- Array bracket notation (`[0]`, `[index]`)
- Function calls
- Template literals
- Arithmetic operators (`+`, `-`, `*`, `/`)
- Logical operators (`&&`, `||`) except negation (`!`)

**Instead, use derived state:**

```javascript
const { state } = store('tenup/store', {
    state: {
        items: [],
        activeIndex: 0,
        
        // Derived state for comparisons
        get isActive() {
            return (index) => index === state.activeIndex;
        },
        
        // Derived state for conditionals
        get displayCount() {
            return state.items.length > 0 ? state.items.length : 'Empty';
        },
        
        // Derived state for array access
        get firstItem() {
            return state.items[0]?.label || '';
        }
    }
});
```

```html
<!-- Use the derived state in directives -->
<div data-wp-class--active="state.isActive"></div>
<span data-wp-text="state.displayCount"></span>
<p data-wp-text="state.firstItem"></p>
```

## Store Reference

### Store Registration

```javascript
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions, callbacks } = store('namespace/name', {
    state: {
        // Global state
        globalValue: 'default',

        // Derived state (getters)
        get computedValue() {
            return state.globalValue.toUpperCase();
        }
    },
    actions: {
        // Functions that modify state
        setValue(event) {
            state.globalValue = event.target.value;
        },
        updateContext() {
            const context = getContext();
            context.localValue = 'updated';
        }
    },
    callbacks: {
        // Lifecycle callbacks
        onInit() {
            const context = getContext();
            const { ref } = getElement();
            // ref is the DOM element
        }
    }
});
```

### getContext()

Returns the merged context for the current element.

```javascript
actions: {
    handleClick() {
        const context = getContext();
        // Access all context properties from ancestors
        context.isOpen = !context.isOpen;
    }
}
```

### getElement()

Returns information about the current DOM element.

```javascript
callbacks: {
    onInit() {
        const { ref, attributes } = getElement();
        // ref: the actual DOM element
        // attributes: reactive attribute getters
    }
}
```

## Server-Side Integration

### wp_interactivity_state()

Set initial state from PHP:

```php
wp_interactivity_state('tenup/store', [
    'items' => get_posts(['numberposts' => 10]),
    'userId' => get_current_user_id(),
]);
```

### wp_interactivity_config()

Pass configuration to the client:

```php
wp_interactivity_config('tenup/store', [
    'apiUrl' => rest_url('tenup/v1/'),
    'nonce' => wp_create_nonce('wp_rest'),
]);
```

Access in JavaScript:

```javascript
import { getConfig } from '@wordpress/interactivity';

const config = getConfig('tenup/store');
// config.apiUrl, config.nonce
```

## Best Practices

1. **Keep stores focused** - One store per block or feature
2. **Use context for instance state** - Global state for shared data
3. **Derive computed values** - Use getters instead of storing computed state
4. **Minimize DOM updates** - Batch related state changes
5. **Use semantic HTML** - Directives enhance, not replace, HTML
6. **Test accessibility** - Ensure keyboard navigation works
7. **Handle loading states** - Show feedback during async operations
