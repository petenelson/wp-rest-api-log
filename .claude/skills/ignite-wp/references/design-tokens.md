# Design System Tokens Reference

Complete reference for Ignite design tokens defined in theme.json.

## Usage

Always use CSS custom properties from theme.json instead of hardcoded values:

```css
/* Good */
.my-element {
    padding: var(--wp--custom--spacing--4);
    color: var(--wp--custom--color--text--primary);
}

/* Bad */
.my-element {
    padding: 16px;
    color: #333;
}
```

## Spacing

```css
var(--wp--custom--spacing--1)   /* 4px */
var(--wp--custom--spacing--2)   /* 8px */
var(--wp--custom--spacing--3)   /* 12px */
var(--wp--custom--spacing--4)   /* 16px */
var(--wp--custom--spacing--5)   /* 20px */
var(--wp--custom--spacing--6)   /* 24px */
var(--wp--custom--spacing--8)   /* 32px */
var(--wp--custom--spacing--10)  /* 40px */
var(--wp--custom--spacing--12)  /* 48px */
var(--wp--custom--spacing--16)  /* 64px */
var(--wp--custom--spacing--20)  /* 80px */
```

### Fluid Spacing

Fluid spacing scales between viewport sizes:

```css
var(--wp--custom--spacing--sm)  /* Fluid small spacing */
var(--wp--custom--spacing--md)  /* Fluid medium spacing */
var(--wp--custom--spacing--lg)  /* Fluid large spacing */
var(--wp--custom--spacing--xl)  /* Fluid extra large spacing */
```

Defined in theme.json:

```json
{
  "settings": {
    "custom": {
      "spacing": {
        "lg": {
          "fluid": "true",
          "max": "var(--wp--custom--spacing--80)",
          "min": "var(--wp--custom--spacing--40)"
        }
      }
    }
  }
}
```

## Colors

### Surface Colors

```css
var(--wp--custom--color--surface)           /* Main background */
var(--wp--custom--color--surface-hover)     /* Background on hover */
var(--wp--custom--color--surface--primary)  /* Primary surface */
var(--wp--custom--color--surface--inverted) /* Inverted surface */
```

### Text Colors

```css
var(--wp--custom--color--text--primary)     /* Main text */
var(--wp--custom--color--text--secondary)   /* Secondary text */
var(--wp--custom--color--text--muted)       /* Muted/disabled text */
var(--wp--custom--color--text--inverted)    /* Text on dark backgrounds */
```

### Border Colors

```css
var(--wp--custom--color--border)            /* Default border */
var(--wp--custom--color--border-hover)      /* Border on hover */
var(--wp--custom--color--border-accent)     /* Accent border */
```

### Accent Colors

```css
var(--wp--custom--color--accent)            /* Primary accent */
var(--wp--custom--color--accent-muted)      /* Muted accent */
var(--wp--custom--color--accent-hover)      /* Accent on hover */
```

### Neutral Colors

```css
var(--wp--custom--color--neutrals--100)     /* Lightest neutral */
var(--wp--custom--color--neutrals--200)
var(--wp--custom--color--neutrals--300)
var(--wp--custom--color--neutrals--400)
var(--wp--custom--color--neutrals--500)
var(--wp--custom--color--neutrals--600)
var(--wp--custom--color--neutrals--700)
var(--wp--custom--color--neutrals--800)
var(--wp--custom--color--neutrals--900)     /* Darkest neutral */
```

## Border Radius

```css
var(--wp--custom--radius--sm)    /* Small radius (4px) */
var(--wp--custom--radius--md)    /* Medium radius (8px) */
var(--wp--custom--radius--lg)    /* Large radius (12px) */
var(--wp--custom--radius--xl)    /* Extra large radius (16px) */
var(--wp--custom--radius--full)  /* Full/pill radius (9999px) */
```

## Shadows

```css
var(--wp--custom--shadow--sm)    /* Small shadow */
var(--wp--custom--shadow--md)    /* Medium shadow */
var(--wp--custom--shadow--lg)    /* Large shadow */
var(--wp--custom--shadow--xl)    /* Extra large shadow */
```

## Transitions

### Duration

```css
var(--wp--custom--transition--duration-fast)    /* Fast (150ms) */
var(--wp--custom--transition--duration-normal)  /* Normal (300ms) */
var(--wp--custom--transition--duration-slow)    /* Slow (500ms) */
```

### Easing

```css
var(--wp--custom--transition--ease-in)          /* Ease in */
var(--wp--custom--transition--ease-out)         /* Ease out */
var(--wp--custom--transition--ease-in-out)      /* Ease in-out */
```

### Combined Usage

```css
.my-element {
    transition:
        background-color var(--wp--custom--transition--duration-normal) var(--wp--custom--transition--ease-out),
        transform var(--wp--custom--transition--duration-fast) var(--wp--custom--transition--ease-out);
}
```

## Typography

### Font Sizes (Preset)

```css
var(--wp--preset--font-size--small)
var(--wp--preset--font-size--medium)
var(--wp--preset--font-size--large)
var(--wp--preset--font-size--x-large)
var(--wp--preset--font-size--xx-large)
```

### Font Weights

```css
var(--wp--custom--font--weight--regular)   /* 400 */
var(--wp--custom--font--weight--medium)    /* 500 */
var(--wp--custom--font--weight--semibold)  /* 600 */
var(--wp--custom--font--weight--bold)      /* 700 */
```

### Font Families

```css
var(--wp--preset--font-family--body)       /* Body text font */
var(--wp--preset--font-family--headings)   /* Heading font */
var(--wp--preset--font-family--monospace)  /* Code font */
```

### Line Heights

```css
var(--wp--custom--line-height--tight)      /* 1.2 */
var(--wp--custom--line-height--normal)     /* 1.5 */
var(--wp--custom--line-height--relaxed)    /* 1.75 */
```

### Fluid Typography

Defined in theme.json for automatic scaling:

```json
{
  "settings": {
    "custom": {
      "font": {
        "size": {
          "display": {
            "fluid": "true",
            "max": "var(--wp--custom--font--size--90)",
            "min": "var(--wp--custom--font--size--58)"
          }
        }
      }
    }
  }
}
```

## Responsive Breakpoints

### Primary Breakpoint

```css
@media (min-width: 768px) {
    /* Desktop styles */
}
```

### Additional Breakpoints

```css
/* Mobile small */
@media (min-width: 375px) { }

/* Tablet */
@media (min-width: 768px) { }

/* Desktop */
@media (min-width: 1024px) { }

/* Wide */
@media (min-width: 1280px) { }
```

## Block-Specific Tokens

### Header Height

Set by navigation plugin:

```css
var(--header-height)  /* Dynamic header height */
```

Usage:

```css
.main-content {
    padding-top: var(--header-height);
}
```

### Timeline

```css
.wp-block-tenup-timeline {
    --c-timeline-line-color: var(--wp--custom--color--neutrals--700, #5f6368);
}
```

## Dark/Light Mode

### Default (Dark Mode)

```css
.wp-block-tenup-example {
    background: var(--wp--custom--color--surface);
    color: var(--wp--custom--color--text--primary);
}
```

### Light Mode Override

```css
.theme-light .wp-block-tenup-example {
    background: var(--wp--custom--color--surface);
    /* Tokens automatically adjust in light mode context */
}
```

## Reduced Motion

Always include reduced motion support:

```css
@media (prefers-reduced-motion: reduce) {
    .animated-element {
        animation: none;
        transition: none;
    }
}
```

## CSS Class Naming

### Block Naming Convention

```css
.wp-block-tenup-[plugin-name]           /* Block container */
.wp-block-tenup-[plugin-name]__element  /* Child element (BEM) */
.wp-block-tenup-[plugin-name]--modifier /* State/variant (BEM) */
```

### Examples

```css
.wp-block-tenup-accordion
.wp-block-tenup-accordion__header
.wp-block-tenup-accordion__content
.wp-block-tenup-accordion--expanded

.wp-block-tenup-carousel
.wp-block-tenup-carousel__track
.wp-block-tenup-carousel__slide
.wp-block-tenup-carousel--has-dots
```
