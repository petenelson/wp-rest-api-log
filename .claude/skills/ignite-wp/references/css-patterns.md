# CSS Patterns and Styling Reference

Best practices for styling Ignite WP blocks and themes.

## Core Philosophy

- **theme.json is for tokens** - Define design tokens, palettes, spacing scales
- **CSS is for styling** - Implement visual presentation, hover states, responsive behavior
- Reference theme.json tokens in CSS via custom properties

## CSS Custom Property Naming

### Preset Variables (from palette/spacing arrays)

```css
--wp--preset--color--accent
--wp--preset--color--bg
--wp--preset--spacing--4
--wp--preset--font-size--xl
```

### Custom Variables (from settings.custom)

```css
--wp--custom--radius--sm
--wp--custom--shadow--md
--wp--custom--color--border
--wp--custom--transition--duration-normal
```

## Specificity Pattern

Use `:root` prefix with `[class]` to override WordPress defaults:

```css
/* This pattern ensures your styles override WordPress defaults */
:root .wp-block-button__link[class] {
  background-color: var(--wp--custom--color--accent);
  border-radius: var(--wp--custom--radius--lg);
  padding: var(--wp--custom--spacing--3) var(--wp--custom--spacing--5);
}

:root .wp-block-button__link[class]:hover {
  background-color: var(--wp--custom--color--accent-hover);
  box-shadow: var(--wp--custom--shadow--glow);
}
```

Why this works:
- `:root` prefix matches WordPress pattern
- `[class]` attribute selector adds specificity
- Higher specificity than `:root :where()` from theme.json

## Section Styles

Section styles apply coordinated color schemes to containers. They override CSS variables within their scope.

### Available Section Styles

| Style | Class | Use Case |
|-------|-------|----------|
| Surface Contrast | `is-style-surface-contrast` | Dark surface background |
| Surface Inverted | `is-style-surface-inverted` | Light bg with dark text |
| Surface Accent | `is-style-surface-accent` | Subtle accent bg with accent borders |
| Surface Primary | `is-style-surface-primary` | Primary color scheme |
| Surface Secondary | `is-style-surface-secondary` | Secondary color scheme |

### Usage

Never set backgroundColor/textColor directly on Group/Columns. Always use section styles:

```html
<!-- Good -->
<div class="wp-block-group is-style-surface-accent">

<!-- Bad -->
<div class="wp-block-group has-accent-background-color has-text-color">
```

### Creating Section Styles

Define in `styles/` directory as JSON files:

```json
{
  "version": 3,
  "title": "Surface Accent",
  "slug": "surface-accent",
  "blockTypes": ["core/group", "core/columns"],
  "settings": {
    "custom": {
      "color": {
        "border": "var(--wp--custom--color--border-accent)"
      }
    }
  },
  "styles": {
    "color": {
      "background": "var(--wp--custom--color--accent-subtle)",
      "text": "var(--wp--custom--color--text--primary)"
    }
  }
}
```

## Typography Presets

Typography presets bundle font-size, weight, line-height, and letter-spacing:

### Usage

Requires both attribute and className:

```html
<!-- wp:heading {"typographyPreset":"display-lg","className":"is-typography-preset-display-lg"} -->
<h1 class="wp-block-heading is-typography-preset-display-lg">Title</h1>
<!-- /wp:heading -->
```

### Available Presets

| Preset | Use Case | Size |
|--------|----------|------|
| `display-lg` | Hero headings | 7xl (fluid) |
| `display-md` | Page titles | 6xl (fluid) |
| `heading-1` | Section headings | 5xl (fluid) |
| `heading-2` | Subsection headings | 4xl (fluid) |
| `heading-3` | Component headings | 3xl (fluid) |
| `heading-4` | Small headings | 2xl |
| `body-lg` | Lead paragraphs | lg |
| `body-base` | Body text | base |
| `body-sm` | Small text | sm |
| `code` | Code snippets | sm (mono) |

## Conditional Loading

Use `wp_enqueue_block_style()` to load CSS only when blocks are used:

```php
wp_enqueue_block_style('core/button', [
    'handle' => 'my-theme-button-styles',
    'src'    => get_theme_file_uri('assets/css/blocks/button.css'),
    'path'   => get_theme_file_path('assets/css/blocks/button.css'),
]);
```

### Auto-Enqueue Pattern

Place CSS in convention-based directories:

```
assets/css/blocks/autoenqueue/
  core/
    button.css      → Loads for core/button
    image.css       → Loads for core/image
  tenup/
    accordion.css   → Loads for tenup/accordion
```

## Responsive Design

### Primary Breakpoint

```css
/* Mobile-first, primary breakpoint at 768px */
@media (min-width: 768px) {
    /* Desktop styles */
}
```

### Additional Breakpoints

```css
@media (min-width: 375px) { /* Mobile small */ }
@media (min-width: 768px) { /* Tablet */ }
@media (min-width: 1024px) { /* Desktop */ }
@media (min-width: 1280px) { /* Wide */ }
```

## Accessibility

### Reduced Motion

Always include reduced motion support:

```css
@media (prefers-reduced-motion: reduce) {
    .animated-element {
        animation: none;
        transition: none;
    }
}
```

### Focus States

```css
.wp-block-button__link:focus-visible {
    outline: 2px solid var(--wp--custom--color--accent);
    outline-offset: 2px;
}
```

## Block Class Convention

```css
.wp-block-tenup-[plugin-name]           /* Block container */
.wp-block-tenup-[plugin-name]__element  /* Child element (BEM) */
.wp-block-tenup-[plugin-name]--modifier /* State/variant (BEM) */
```

## Example: Complete Button Styling

```css
:root .wp-block-button__link[class] {
  /* Colors from palette */
  background-color: var(--wp--custom--color--accent);
  border-color: var(--wp--custom--color--accent);
  color: var(--wp--custom--color--white);

  /* Border radius from custom tokens */
  border-radius: var(--wp--custom--radius--lg);

  /* Spacing from custom tokens */
  padding: var(--wp--custom--spacing--3) var(--wp--custom--spacing--5);
  gap: var(--wp--custom--spacing--2);

  /* Typography from custom tokens */
  font-size: var(--wp--custom--font--size--sm);

  /* Transitions from custom tokens */
  transition: all var(--wp--custom--transition--duration-normal)
              var(--wp--custom--transition--ease-out);
}

:root .wp-block-button__link[class]:hover {
  background-color: var(--wp--custom--color--accent-hover);
  box-shadow: var(--wp--custom--shadow--glow);
  transform: translateY(-1px);
}

:root .wp-block-button__link[class]:focus-visible {
  outline: 2px solid var(--wp--custom--color--accent);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  :root .wp-block-button__link[class] {
    transition: none;
    transform: none;
  }
}
```

## What NOT to Put in theme.json

For custom builds, keep these in CSS:

- Complex component styling (buttons, cards, navigation)
- Hover and focus states (requires pseudo-classes)
- Responsive adjustments (media queries not supported)
- Pseudo-elements (::before, ::after, ::marker)
- Advanced selectors (child combinators, :has())
