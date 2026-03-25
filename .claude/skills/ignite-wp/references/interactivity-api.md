# Interactivity API Reference

Complete reference for Ignite's WordPress Interactivity API stores.

## Overview

Ignite plugins use WordPress Interactivity API for frontend behavior. Each plugin registers its own store that can be extended.

## Store Registration

Ignite stores are registered in `view-module.js` files:

```javascript
import { store, getContext } from '@wordpress/interactivity';

store('tenup/block-name', {
    state: {
        // Global state
    },
    actions: {
        // Methods that modify state
    },
    callbacks: {
        // Lifecycle methods
    },
});
```

## Accordion Store

**Store name:** `tenup/accordion`

### State

```javascript
{
    state: {
        expandedPanels: [], // Array of expanded panel IDs
    }
}
```

### Context (per accordion)

```javascript
{
    isExpanded: false,
    panelId: 'unique-id',
    allowMultiple: false,
}
```

### Actions

```javascript
{
    actions: {
        toggle: () => {
            const context = getContext();
            context.isExpanded = !context.isExpanded;
        }
    }
}
```

### Markup Attributes

```html
<div
    data-wp-interactive="tenup/accordion"
    data-wp-context='{"isExpanded":false,"panelId":"accordion-1"}'
>
    <button data-wp-on--click="actions.toggle">
        Toggle
    </button>
    <div data-wp-bind--hidden="!context.isExpanded">
        Content
    </div>
</div>
```

## Carousel Store

**Store name:** `tenup/carousel`

### State

```javascript
{
    state: {
        default: {
            perPage: 1,
            gap: '1rem',
            type: 'slide',
            speed: 400,
            autoplay: false,
            interval: 3000,
            breakpoints: {},
        }
    }
}
```

### Context (per carousel)

```javascript
{
    splideInstance: null,
    currentSlide: 0,
    totalSlides: 0,
}
```

### Actions

```javascript
{
    actions: {
        init: () => {
            // Initialize SplideJS instance
        },
        goTo: (index) => {
            // Navigate to specific slide
        },
        next: () => {
            // Go to next slide
        },
        prev: () => {
            // Go to previous slide
        },
    }
}
```

### PHP State Modification

```php
add_filter('render_block_tenup/carousel', function($content) {
    $state = wp_interactivity_state('tenup/carousel');

    $state['default']['perPage'] = 3;
    $state['default']['breakpoints'] = [
        768 => ['perPage' => 2],
        480 => ['perPage' => 1],
    ];

    wp_interactivity_state('tenup/carousel', $state);
    return $content;
});
```

## Modal Store

**Store name:** `tenup/modal`

### State

```javascript
{
    state: {
        activeModal: null,
    }
}
```

### Context (per modal)

```javascript
{
    modalId: 'modal-unique-id',
    isOpen: false,
}
```

### Actions

```javascript
{
    actions: {
        show: () => {
            const context = getContext();
            context.isOpen = true;
            // Uses a11y-dialog for accessibility
        },
        hide: () => {
            const context = getContext();
            context.isOpen = false;
        },
        onShow: () => {
            // Hook for custom behavior on show
            console.log('Modal opened');
        },
        onHide: () => {
            // Hook for custom behavior on hide
            console.log('Modal closed');
        },
        stopEmbeds: () => {
            // Stops playing embeds when modal closes
        },
    }
}
```

### Extending Modal Store

```javascript
import { store } from '@wordpress/interactivity';

store('tenup/modal', {
    actions: {
        onShow() {
            // Custom logic when modal opens
            document.body.classList.add('modal-open');
        },
        onHide() {
            // Custom logic when modal closes
            document.body.classList.remove('modal-open');
        },
    }
});
```

## Site Header Store

**Store name:** `tenup/site-header`

### State

```javascript
{
    state: {
        expandedRegion: null,      // Currently expanded region name
        isInitialized: false,
        hasSearch: false,
        triggerElement: null,      // Element that triggered expansion
        isBackdropVisible: false,
        headerHeight: 0,           // Also sets --header-height CSS var
        headroomInstance: null,

        // Computed getters
        get isSearchExpanded() {
            return state.expandedRegion === 'search';
        },
        get isNavExpanded() {
            return state.expandedRegion === 'navigation';
        },
    }
}
```

### Actions

```javascript
{
    actions: {
        init: () => {
            // Initialize header, set CSS var
        },
        expandRegion: (regionName) => {
            // Expand a named region
        },
        collapseRegion: () => {
            // Collapse current region
        },
        toggleRegion: (regionName) => {
            // Toggle region expansion
        },
        handleBackdropClick: () => {
            // Close region on backdrop click
        },
    }
}
```

### Child Regions

Navigation supports nested interactive elements:

```html
<div
    data-wp-interactive="tenup/site-header"
    data-wp-context='{"regionName":"navigation"}'
>
    <nav data-wp-class--expanded="state.isNavExpanded">
        <!-- Navigation content -->
    </nav>
</div>
```

## Query Filter Store

**Store name:** `tenup/query-filter`

### State

```javascript
{
    state: {
        filters: {},        // Active filters
        isLoading: false,
        hasMore: true,
    }
}
```

### Context (per filter form)

```javascript
{
    queryId: 1,
    taxonomy: 'category',
    selectedTerms: [],
}
```

### Actions

```javascript
{
    actions: {
        updateFilter: () => {
            // Update filter value and trigger query
        },
        clearFilter: () => {
            // Clear specific filter
        },
        clearAll: () => {
            // Clear all filters
        },
        loadMore: () => {
            // Load more posts via AJAX
        },
    }
}
```

## Stats Store

**Store name:** `tenup/stats`

### Context (per stats block)

```javascript
{
    targetValue: 1000,
    currentValue: 0,
    prefix: '$',
    suffix: '+',
    hasAnimated: false,
}
```

### Actions

```javascript
{
    actions: {
        init: () => {
            // Set up IntersectionObserver
        },
        animate: () => {
            // Trigger count animation
        },
    },
    callbacks: {
        onIntersect: () => {
            // Called when element enters viewport
        },
    },
}
```

## Common Patterns

### Accessing State

```javascript
import { store, getContext, getElement } from '@wordpress/interactivity';

const { state, actions } = store('tenup/modal');

// Access global state
console.log(state.activeModal);

// Access context
const context = getContext();
console.log(context.modalId);

// Access DOM element
const { ref } = getElement();
console.log(ref.dataset.modalId);
```

### Extending Stores

```javascript
// Add custom actions to existing store
store('tenup/accordion', {
    actions: {
        customAction() {
            const context = getContext();
            // Custom logic
        },
    },
});
```

### Markup Directives

Common directives used with Ignite stores:

```html
<!-- Bind attributes -->
<div data-wp-bind--hidden="!context.isExpanded"></div>

<!-- Event handlers -->
<button data-wp-on--click="actions.toggle"></button>

<!-- Class binding -->
<div data-wp-class--active="context.isActive"></div>

<!-- Initialize on mount -->
<div data-wp-init="callbacks.init"></div>

<!-- Watch for changes -->
<div data-wp-watch="callbacks.onStateChange"></div>

<!-- Text content -->
<span data-wp-text="context.currentValue"></span>
```

### Context JSON

Context is passed via `data-wp-context` as JSON:

```html
<div data-wp-context='{"isOpen":false,"modalId":"modal-1"}'>
```

In PHP:

```php
$context = wp_json_encode([
    'isOpen' => false,
    'modalId' => 'modal-1',
]);
?>
<div data-wp-context="<?php echo esc_attr($context); ?>">
```
