---
name: 10up-css
description: CSS architecture, best practices, and patterns for WordPress projects. Covers ITCSS methodology, accessibility, specificity management, naming conventions, and modern CSS features.
license: MIT
compatibility: Modern browsers, WordPress 6.0+
globs:
  - "**/*.css"
  - "**/*.scss"
  - "**/*.sass"
  - "**/*.pcss"
  - postcss.config.js
metadata:
  author: 10up
  version: "1.0"
---

# 10up CSS Best Practices

This skill provides comprehensive CSS authoring guidelines following 10up standards for WordPress development.

## When to Use

- Writing new CSS for themes or plugins
- Reviewing CSS code for best practices
- Setting up CSS architecture for a project
- Troubleshooting specificity or styling issues
- Ensuring accessibility compliance in styles
- Organizing stylesheets with ITCSS methodology

## Core Principles

### Follow Established Systems First

Assume existing conventions exist for valid reasons. Learn the codebase before suggesting changes. Only propose modifications when current systems create genuine problems—inconsistency, scaling issues, or errors.

### Document Custom Architectures

When designing new CSS systems, capture folder layout, naming conventions, tooling decisions, and rationales. Store documentation in `/decisions/` directories within repositories.

### Content-Agnostic Component Design

Build components assuming no control over content. Test against extreme scenarios:
- Navigation with 20+ items instead of 5
- Missing images in image-based cards
- Multi-line titles designed for single lines
- Exceptionally long or empty excerpts

## Mobile-First Approach

Prioritize mobile layouts in implementation. Style projects as if desktop doesn't exist initially.

**Implementation strategy:**
1. Use modern CSS features (Grid `auto-fit`, `clamp()`) to create inherently responsive layouts
2. Reach for media queries last as fallbacks rather than primary tools
3. Prefer `scrolling` over carousels or dropdowns when appropriate

**Watch for:**
- Different image aspect ratios across breakpoints
- Reordered elements requiring CSS Grid/Flexbox with accessibility implications
- Markup differences between mobile/desktop views

## CSS Foundations

Set `box-sizing: border-box` globally at project start:

```css
*,
*::before,
*::after {
  box-sizing: border-box;
}

:where(body) {
  margin: 0;
}
```

Establish foundational styles using `:where()` for zero specificity. Avoid opinionated defaults like `text-align: center` that require constant overrides.

## Specificity Management

**Target range:** `0,1,0` to `0,2,1` maximum.

**Rules:**
- Avoid combining tag selectors with classes (`a.button` → `.button`)
- Use `:where()` for bare element styles
- Limit nesting to 2 levels; 3 only when necessary
- Avoid IDs for styling (use attribute selectors like `[id="drawer"]` if needed)
- Reserve `!important` for exceptional cases only

```css
/* Use :where() for zero specificity on base elements */
:where(h1, h2, h3, h4, h5, h6) {
  margin-block: 0;
}

/* Nesting pseudo-classes for state is acceptable */
.button {
  &:hover {
    /* styles here */
  }
}

/* Keep child selectors flat, not nested */
.button__icon {
  /* styles here */
}
```

## Writing Minimal CSS

Use logical properties instead of broad shorthands:

```css
/* Do this */
.element {
  margin-inline: auto;
  background-color: var(--color-primary);
}

/* Avoid */
.element {
  margin: 0 auto; /* Sets unintended values */
  background: var(--color-primary); /* Resets other background properties */
}
```

Target elements precisely. Instead of global selectors affecting all elements:

```css
/* Avoid broad selectors */
a { color: blue; }

/* Use scoped selectors */
.entry-content :is(h1, h2, h3, p, li) > a {
  color: var(--color-link);
}
```

## Component Margin Strategy

**Never add margins to reusable components.** Margins lock components into specific contexts.

```css
/* Avoid */
.card {
  margin-block: 1.5rem;
}

/* Do this - manage spacing at container level */
.card-grid {
  display: grid;
  row-gap: 1.5rem;
}
```

**Apply margins in one direction only** (preferably upward):

```css
.component > * + * {
  margin-top: 1.5em;
}
```

## Responsive Design Without Media Queries

Prioritize intrinsic solutions:

```css
/* Use clamp() for fluid values */
.heading {
  font-size: clamp(1.5rem, 4vw, 3rem);
}

/* Use CSS Grid for flexible layouts */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(300px, 100%), 1fr));
  gap: 1.5rem;
}
```

Use `rem`, `em`, `%` over fixed pixels. Media queries should be fallbacks for cases where intrinsic solutions won't work.

## Modern CSS Feature Adoption

### Adoption Process

1. **Check compatibility** via Can I Use or Google Baseline
2. **Provide fallbacks** with supported declaration first:

```css
.element {
  height: 100vh;    /* widely supported */
  height: 100dvh;   /* newer unit */
}
```

3. **Use `@supports`** for feature detection:

```css
.site {
  min-height: 100dvh;
}

@supports not (min-height: 100dvh) {
  .site {
    min-height: 100vh;
  }
}
```

4. **Consider polyfills cautiously**—they add dependencies and bundle size

## Quick Reference

| Topic | Guideline |
|-------|-----------|
| Specificity | Keep between `0,1,0` and `0,2,1` |
| Nesting | Maximum 2 levels, 3 for exceptional cases |
| Component margins | None—use container `gap` |
| Margin direction | One direction only (prefer top) |
| IDs for styling | Avoid—use `[id="name"]` if needed |
| `!important` | Exceptional cases only |
| Media queries | Last resort after intrinsic solutions |

## References

- [ITCSS Architecture](references/itcss-architecture.md) - Layer organization and file structure
- [Accessibility](references/accessibility.md) - CSS accessibility requirements
- [Naming Conventions](references/naming-conventions.md) - Classes, IDs, custom properties, animations

## External Resources

- [Specificity Calculator](https://specificity.keegan.st)
- [Defensive CSS](https://defensivecss.dev)
- [MDN Logical Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_logical_properties_and_values)
- [RTL Styling 101](https://rtlstyling.com)

## Verification

After writing CSS:

1. Validate specificity stays within `0,1,0` to `0,2,1` range
2. Test at 400% zoom for reflow compliance
3. Verify focus states are visible with keyboard navigation
4. Check RTL support with `dir="rtl"` on container
5. Test responsive behavior without relying solely on media queries

## Failure Modes

**Styles not applying:**
- Check specificity conflicts
- Verify CSS custom property fallbacks
- Confirm correct selector scope

**Layout breaking at certain widths:**
- Review content-agnostic design principles
- Check for fixed widths instead of relative units
- Verify `clamp()` or Grid intrinsic sizing

**Accessibility issues:**
- Missing focus states
- Insufficient color contrast
- Content inaccessible at zoom levels

## Escalation

Ask the user when:
- Complex animation requirements
- Cross-browser compatibility issues
- Performance optimization for large stylesheets
- Integration with third-party CSS frameworks
