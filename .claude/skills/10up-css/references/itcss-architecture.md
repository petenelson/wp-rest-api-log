# ITCSS Architecture

Inverted Triangle CSS (ITCSS) organizes stylesheets from broad to specific, reducing specificity conflicts and improving maintainability.

## Layer Overview

```
┌─────────────────────────────────────┐
│            GLOBAL                   │  ← Widest reach, lowest specificity
│    (Custom properties, fonts)       │
├─────────────────────────────────────┤
│           ELEMENTS                  │
│      (Base HTML element styles)     │
├─────────────────────────────────────┤
│          COMPONENTS                 │
│       (Reusable UI patterns)        │
├─────────────────────────────────────┤
│          UTILITIES                  │  ← Narrowest reach, highest specificity
│    (Single-purpose overrides)       │
└─────────────────────────────────────┘
```

## File Structure

```
assets/css/
├── global/
│   ├── _fonts.css           # @font-face definitions
│   ├── _custom-media.css    # Breakpoint definitions
│   ├── _custom-properties.css # CSS variables
│   └── _base.css            # Box-sizing, body margin
├── elements/
│   ├── _headings.css        # h1-h6 base styles
│   ├── _lists.css           # ul, ol, dl base styles
│   ├── _links.css           # Anchor base styles
│   └── _forms.css           # Form element bases
├── components/
│   ├── _button.css
│   ├── _card.css
│   ├── _navigation.css
│   └── _modal.css
├── utilities/
│   ├── _spacing.css
│   ├── _visibility.css
│   └── _text.css
└── style.css                # Main entry point importing all layers
```

## Global Layer

Contains project-wide settings with no direct output. Keep this layer minimal.

### Custom Properties

```css
:root {
  /* Colors - Primitive tokens */
  --color-blue-500: #3b82f6;
  --color-gray-900: #111827;

  /* Colors - Semantic tokens */
  --color-primary: var(--color-blue-500);
  --color-text: var(--color-gray-900);

  /* Typography */
  --font-family-base: system-ui, sans-serif;
  --font-family-heading: var(--font-family-base);
  --font-size-base: 1rem;
  --line-height-base: 1.5;

  /* Spacing */
  --spacing-xs: 0.25rem;
  --spacing-sm: 0.5rem;
  --spacing-md: 1rem;
  --spacing-lg: 1.5rem;
  --spacing-xl: 2rem;

  /* Layout */
  --content-width: 800px;
  --wide-width: 1200px;
}
```

### Custom Media Queries

```css
@custom-media --viewport-sm (min-width: 640px);
@custom-media --viewport-md (min-width: 768px);
@custom-media --viewport-lg (min-width: 1024px);
@custom-media --viewport-xl (min-width: 1280px);
```

### Font Definitions

```css
@font-face {
  font-family: 'Brand Font';
  src: url('../fonts/brand-regular.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}
```

### Base Reset

```css
*,
*::before,
*::after {
  box-sizing: border-box;
}

:where(body) {
  margin: 0;
  font-family: var(--font-family-base);
  font-size: var(--font-size-base);
  line-height: var(--line-height-base);
  color: var(--color-text);
}
```

## Elements Layer

Target raw HTML tags without classes. Use `:where()` for zero specificity, allowing easy overrides.

### Headings

```css
:where(h1, h2, h3, h4, h5, h6) {
  font-family: var(--font-family-heading);
  font-weight: 700;
  line-height: 1.2;
  margin-block: 0;
}

:where(h1) { font-size: 2.5rem; }
:where(h2) { font-size: 2rem; }
:where(h3) { font-size: 1.75rem; }
:where(h4) { font-size: 1.5rem; }
:where(h5) { font-size: 1.25rem; }
:where(h6) { font-size: 1rem; }
```

### Lists

```css
:where(ul, ol) {
  padding-inline-start: 1.5em;
  margin-block: 0;
}

:where(li) {
  margin-block-end: 0.25em;
}

:where(dl) {
  margin-block: 0;
}

:where(dt) {
  font-weight: 700;
}

:where(dd) {
  margin-inline-start: 0;
  margin-block-end: 1em;
}
```

### Links

```css
:where(a) {
  color: var(--color-primary);
  text-decoration: underline;
  text-underline-offset: 0.2em;
}

:where(a:hover) {
  text-decoration: none;
}
```

### Forms

```css
:where(input, textarea, select) {
  font: inherit;
  color: inherit;
}

:where(button) {
  font: inherit;
  cursor: pointer;
}
```

### Guidelines

- Only style elements that apply in 99.9% of cases
- Avoid opinionated defaults that require constant overrides
- Use `:where()` to maintain zero specificity
- Let components override these base styles easily

## Components Layer

Build reusable UI elements with class selectors. This is where most project CSS lives.

### Button Component

```css
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5em;
  padding-block: 0.75em;
  padding-inline: 1.5em;
  font-weight: 600;
  text-decoration: none;
  border: 2px solid transparent;
  border-radius: 0.25em;
  cursor: pointer;
  transition: background-color 0.2s, border-color 0.2s;

  &:hover {
    /* hover styles */
  }

  &:focus-visible {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
  }
}

.button--primary {
  background-color: var(--color-primary);
  color: white;
}

.button--secondary {
  background-color: transparent;
  border-color: currentColor;
}

.button__icon {
  flex-shrink: 0;
  width: 1em;
  height: 1em;
}
```

### Card Component

```css
.card {
  display: flex;
  flex-direction: column;
  border-radius: 0.5rem;
  overflow: hidden;
  background-color: var(--color-surface);
}

.card__image {
  aspect-ratio: 16 / 9;
  object-fit: cover;
  width: 100%;
}

.card__content {
  padding: var(--spacing-lg);
}

.card__title {
  font-size: 1.25rem;
}

.card__description {
  color: var(--color-text-muted);
}
```

### Component Guidelines

1. **No margins on components** - Use container `gap` for spacing
2. **Focus on modularity** - Components should work in any context
3. **Leverage inherited styles** - Don't redeclare what elements layer provides
4. **Scope animations** - Define `@keyframes` alongside component code
5. **Keep specificity consistent** - Target `0,1,0` to `0,2,0`

### Component Animations

Define animations with their component:

```css
.modal {
  animation: modal-enter 0.3s ease-out;
}

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
```

## Utilities Layer

Single-purpose classes for quick styling adjustments. Use after component classes.

### Spacing Utilities

```css
.mt-0 { margin-block-start: 0; }
.mt-1 { margin-block-start: var(--spacing-xs); }
.mt-2 { margin-block-start: var(--spacing-sm); }
.mt-3 { margin-block-start: var(--spacing-md); }
.mt-4 { margin-block-start: var(--spacing-lg); }

.mb-0 { margin-block-end: 0; }
.mb-1 { margin-block-end: var(--spacing-xs); }
/* ... */

.gap-1 { gap: var(--spacing-xs); }
.gap-2 { gap: var(--spacing-sm); }
.gap-3 { gap: var(--spacing-md); }
/* ... */
```

### Visibility Utilities

```css
.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.hidden {
  display: none;
}

@media (--viewport-md) {
  .hidden-md {
    display: none;
  }
}
```

### Text Utilities

```css
.text-center { text-align: center; }
.text-start { text-align: start; }
.text-end { text-align: end; }

.text-sm { font-size: 0.875rem; }
.text-lg { font-size: 1.125rem; }

.font-bold { font-weight: 700; }
.font-normal { font-weight: 400; }

.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
```

### Utility Guidelines

1. **Keep specificity consistent with components** - Avoid `!important`
2. **Place utilities after component classes** in HTML
3. **Use sparingly** - Prefer component styles for repeated patterns
4. **Reusable `@keyframes`** - Place shared animations here

## Main Entry Point

Import layers in order:

```css
/* style.css */

/* Global */
@import 'global/_fonts.css';
@import 'global/_custom-media.css';
@import 'global/_custom-properties.css';
@import 'global/_base.css';

/* Elements */
@import 'elements/_headings.css';
@import 'elements/_lists.css';
@import 'elements/_links.css';
@import 'elements/_forms.css';

/* Components */
@import 'components/_button.css';
@import 'components/_card.css';
@import 'components/_navigation.css';
@import 'components/_modal.css';

/* Utilities */
@import 'utilities/_spacing.css';
@import 'utilities/_visibility.css';
@import 'utilities/_text.css';
```

## WordPress Block Theme Integration

For block themes, align ITCSS layers with WordPress conventions:

```
theme-name/
├── style.css              # Theme metadata
├── assets/
│   └── css/
│       ├── global/        # Custom properties, fonts
│       ├── elements/      # Base HTML styles
│       ├── components/    # Component styles
│       ├── utilities/     # Utility classes
│       └── blocks/        # Per-block overrides
│           ├── core/
│           │   └── group.css
│           └── theme/
│               └── hero.css
└── theme.json             # Design tokens source
```

Design tokens defined in `theme.json` generate CSS custom properties automatically. Avoid duplicating these in your global layer.
