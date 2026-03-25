# CSS Naming Conventions

Consistent naming improves code readability, maintainability, and collaboration across teams.

## Classes

### Format

- Use lowercase with hyphens (kebab-case)
- Be descriptive and semantic
- Name by purpose, not appearance
- Keep names reusable and context-agnostic

```css
/* GOOD - Semantic, descriptive */
.user-profile { }
.card-header { }
.navigation-primary { }
.search-form { }

/* BAD - Presentational names */
.blue-box { }
.left-column { }
.big-text { }

/* BAD - Abbreviations or unclear names */
.usr-prof { }
.nav-p { }
.sf { }
```

### Component Naming (BEM-like)

Use a consistent pattern for components and their parts:

```css
/* Block - The component itself */
.card { }

/* Element - Part of the component (use double underscore) */
.card__header { }
.card__body { }
.card__footer { }
.card__title { }
.card__image { }

/* Modifier - Variation of block or element (use double hyphen) */
.card--featured { }
.card--horizontal { }
.card__title--large { }
```

### State Classes

Prefix state classes with `is-` or `has-`:

```css
.is-active { }
.is-disabled { }
.is-loading { }
.is-hidden { }
.is-expanded { }

.has-error { }
.has-dropdown { }
.has-icon { }
```

### Utility Classes

Short, single-purpose classes:

```css
/* Spacing */
.mt-1 { }  /* margin-top level 1 */
.pb-2 { }  /* padding-bottom level 2 */
.gap-3 { } /* gap level 3 */

/* Text */
.text-center { }
.text-sm { }
.font-bold { }

/* Display */
.flex { }
.grid { }
.hidden { }
```

## IDs

### When to Use

- Use IDs sparingly
- Never use IDs for styling (specificity issues)
- Use for JavaScript hooks and anchor links

### Format

- Use camelCase for IDs

```html
<!-- GOOD -->
<div id="mainNavigation">
<section id="heroSection">
<form id="searchForm">

<!-- BAD - kebab-case for IDs -->
<div id="main-navigation">
```

### JavaScript-Specific IDs

Prefix with `js-` and use kebab-case for IDs that are JavaScript hooks only:

```html
<!-- JS hook - not for styling -->
<button id="js-toggle-menu">Menu</button>
<div id="js-modal-container"></div>

<!-- Styling via classes, JS via ID -->
<button class="button button--primary" id="js-submit-form">Submit</button>
```

```css
/* Never style js- prefixed IDs */
/* BAD */
#js-toggle-menu {
  background: blue;
}

/* GOOD - Use classes for styling */
.menu-toggle {
  background: blue;
}
```

## CSS Custom Properties

### Naming Structure

Name by usage/purpose, not by appearance:

```css
:root {
  /* GOOD - Semantic names */
  --color-primary: #3b82f6;
  --color-secondary: #6366f1;
  --color-text: #1f2937;
  --color-background: #ffffff;
  --color-border: #e5e7eb;
  --color-error: #ef4444;
  --color-success: #10b981;

  /* BAD - Appearance-based names */
  --blue: #3b82f6;
  --dark-gray: #1f2937;
  --light-border: #e5e7eb;
}
```

### Design Token Layers

Use two layers of abstraction:

```css
:root {
  /* Primitive tokens - Raw values */
  --color-blue-50: #eff6ff;
  --color-blue-100: #dbeafe;
  --color-blue-500: #3b82f6;
  --color-blue-600: #2563eb;
  --color-blue-900: #1e3a8a;

  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-500: #6b7280;
  --color-gray-900: #111827;

  /* Semantic tokens - Application */
  --color-primary: var(--color-blue-500);
  --color-primary-hover: var(--color-blue-600);
  --color-text: var(--color-gray-900);
  --color-text-muted: var(--color-gray-500);
  --color-background: var(--color-gray-50);
  --color-surface: white;
}
```

### Avoid Deep Nesting

Limit abstraction to 1-2 levels:

```css
/* GOOD - One level of abstraction */
:root {
  --color-blue-500: #3b82f6;
  --color-primary: var(--color-blue-500);
}

.button {
  background: var(--color-primary);
}

/* BAD - Too many levels */
:root {
  --raw-blue: #3b82f6;
  --palette-blue: var(--raw-blue);
  --theme-primary: var(--palette-blue);
  --component-button-bg: var(--theme-primary);
}
```

### Property Categories

Organize custom properties by category:

```css
:root {
  /* Colors */
  --color-primary: #3b82f6;
  --color-secondary: #6366f1;
  --color-text: #1f2937;
  --color-background: #ffffff;

  /* Typography */
  --font-family-base: system-ui, sans-serif;
  --font-family-heading: var(--font-family-base);
  --font-size-base: 1rem;
  --font-size-sm: 0.875rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --line-height-base: 1.5;
  --line-height-tight: 1.25;

  /* Spacing */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;
  --spacing-2xl: 3rem;

  /* Layout */
  --width-content: 800px;
  --width-wide: 1200px;
  --width-full: 100%;

  /* Borders */
  --border-radius-sm: 0.25rem;
  --border-radius-md: 0.5rem;
  --border-radius-lg: 1rem;
  --border-radius-full: 9999px;
  --border-width: 1px;

  /* Shadows */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);

  /* Transitions */
  --transition-fast: 150ms;
  --transition-base: 200ms;
  --transition-slow: 300ms;
  --easing-default: ease-out;
}
```

### Component-Scoped Properties

Define component-specific properties within the component:

```css
.card {
  /* Component defaults using global tokens */
  --card-padding: var(--spacing-lg);
  --card-border-radius: var(--border-radius-md);
  --card-background: var(--color-surface);

  padding: var(--card-padding);
  border-radius: var(--card-border-radius);
  background: var(--card-background);
}

/* Override for specific contexts */
.sidebar .card {
  --card-padding: var(--spacing-md);
}
```

## Animations

### Keyframe Names

- Use action-based names describing the animation behavior
- Use kebab-case
- Keep names short but descriptive

```css
/* GOOD - Action-based names */
@keyframes fade-in { }
@keyframes fade-out { }
@keyframes slide-up { }
@keyframes slide-down { }
@keyframes scale-in { }
@keyframes spin { }
@keyframes pulse { }
@keyframes bounce { }

/* BAD - Vague or presentational names */
@keyframes animation1 { }
@keyframes myAnimation { }
@keyframes moving-thing { }
```

### Component-Specific Animations

Prefix with component name when animation is component-specific:

```css
/* Modal animations */
@keyframes modal-enter {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes modal-exit {
  from {
    opacity: 1;
    transform: scale(1);
  }
  to {
    opacity: 0;
    transform: scale(0.95);
  }
}

/* Dropdown animations */
@keyframes dropdown-open {
  from {
    opacity: 0;
    transform: translateY(-0.5rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

### Animation Custom Properties

```css
:root {
  --animation-duration-fast: 150ms;
  --animation-duration-base: 200ms;
  --animation-duration-slow: 300ms;

  --animation-easing-default: ease-out;
  --animation-easing-bounce: cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.modal {
  animation: modal-enter var(--animation-duration-base) var(--animation-easing-default);
}
```

## Grid Elements

### Area Names

Name grid areas by content purpose:

```css
.page-layout {
  display: grid;
  grid-template-areas:
    "header  header  header"
    "sidebar content aside"
    "footer  footer  footer";
  grid-template-columns: 200px 1fr 200px;
  grid-template-rows: auto 1fr auto;
}

.site-header { grid-area: header; }
.site-sidebar { grid-area: sidebar; }
.site-content { grid-area: content; }
.site-aside { grid-area: aside; }
.site-footer { grid-area: footer; }
```

### Line Names

Use descriptive names with `-start` and `-end` suffixes:

```css
.grid-layout {
  display: grid;
  grid-template-columns:
    [full-start]
    minmax(1rem, 1fr)
    [content-start]
    minmax(0, 800px)
    [content-end]
    minmax(1rem, 1fr)
    [full-end];
}

.full-width {
  grid-column: full-start / full-end;
}

.content-width {
  grid-column: content-start / content-end;
}
```

### Complex Grid Example

```css
.article-layout {
  display: grid;
  grid-template-columns:
    [full-start gutter-left-start]
    minmax(var(--spacing-md), 1fr)
    [gutter-left-end content-start]
    min(100% - var(--spacing-md) * 2, var(--width-content))
    [content-end gutter-right-start]
    minmax(var(--spacing-md), 1fr)
    [gutter-right-end full-end];
}

/* Default content alignment */
.article-layout > * {
  grid-column: content;
}

/* Full-width elements */
.article-layout > .is-full-width {
  grid-column: full;
}

/* Elements that extend into gutter */
.article-layout > .alignwide {
  grid-column: gutter-left-end / gutter-right-start;
}
```

## File Naming

### CSS Files

- Use kebab-case for file names
- Name by content/purpose
- Use underscore prefix for partials (Sass convention)

```
assets/css/
├── style.css              # Main entry point
├── editor.css             # Editor styles
├── global/
│   ├── _fonts.css
│   ├── _custom-properties.css
│   └── _base.css
├── components/
│   ├── _button.css
│   ├── _card.css
│   └── _navigation.css
└── utilities/
    ├── _spacing.css
    └── _visibility.css
```

### Block-Specific CSS

```
blocks/
├── hero/
│   ├── style.css          # Frontend styles
│   └── editor.css         # Editor-only styles
├── card/
│   ├── style.css
│   └── editor.css
└── testimonial/
    ├── style.css
    └── editor.css
```

## Summary Table

| Element | Convention | Example |
|---------|------------|---------|
| Classes | kebab-case, semantic | `.user-profile`, `.card-header` |
| BEM elements | Double underscore | `.card__title` |
| BEM modifiers | Double hyphen | `.card--featured` |
| State classes | `is-` or `has-` prefix | `.is-active`, `.has-error` |
| IDs | camelCase | `#mainNavigation` |
| JS hook IDs | `js-` prefix, kebab-case | `#js-toggle-menu` |
| Custom properties | `--category-name` | `--color-primary` |
| Animations | Action-based, kebab-case | `fade-in`, `slide-up` |
| Grid areas | Content purpose | `header`, `sidebar`, `content` |
| Grid lines | With `-start`/`-end` | `content-start`, `full-end` |
| Files | kebab-case, `_` for partials | `_button.css`, `style.css` |
