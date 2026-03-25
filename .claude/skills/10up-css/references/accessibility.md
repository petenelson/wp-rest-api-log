# CSS Accessibility Requirements

CSS plays a critical role in web accessibility. These guidelines ensure styles support all users, including those using assistive technologies.

## Keyboard Navigation

All interactive elements must be accessible via keyboard (Tab, Enter, Space keys).

### Focus States

Always provide visible focus indicators:

```css
/* Use :focus-visible for keyboard-only focus */
.button:focus-visible {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
}

/* Avoid removing focus outlines without replacement */
/* BAD */
:focus {
  outline: none;
}

/* GOOD - Replace with visible alternative */
:focus-visible {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
}
```

### Focus Management

```css
/* Ensure focus is visible against any background */
.button:focus-visible {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
  box-shadow: 0 0 0 4px var(--color-focus-ring);
}

/* High contrast focus for dark backgrounds */
.dark-section .button:focus-visible {
  outline-color: white;
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
}
```

### Skip Links

Provide skip navigation for keyboard users:

```css
.skip-link {
  position: absolute;
  top: -100%;
  left: 0;
  z-index: 9999;
  padding: 1rem;
  background: var(--color-background);
  color: var(--color-text);
}

.skip-link:focus {
  top: 0;
}
```

## Color Contrast

Maintain WCAG compliance for text readability.

### Contrast Ratios

| Text Type | Minimum Ratio | Enhanced Ratio |
|-----------|--------------|----------------|
| Normal text (< 18pt) | 4.5:1 | 7:1 |
| Large text (≥ 18pt or 14pt bold) | 3:1 | 4.5:1 |
| UI components & graphics | 3:1 | — |

### Implementation

```css
:root {
  /* Ensure sufficient contrast */
  --color-text: #1f2937;        /* Dark gray on white: ~15:1 */
  --color-text-muted: #6b7280;  /* Muted on white: ~5.5:1 */
  --color-background: #ffffff;

  /* Link colors need contrast against text AND background */
  --color-link: #2563eb;        /* Blue on white: ~4.5:1 */
}

/* Never rely on color alone for information */
.error {
  color: var(--color-error);
  /* Also use icon, text, or other indicator */
}

.error::before {
  content: '⚠ ';
}
```

### Text Over Images

Avoid placing text directly over images without sufficient contrast:

```css
/* Ensure readable text over images */
.hero-overlay {
  position: relative;
}

.hero-overlay::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(0, 0, 0, 0.7) 0%,
    rgba(0, 0, 0, 0.3) 50%,
    transparent 100%
  );
}

.hero-overlay__text {
  position: relative;
  z-index: 1;
  color: white;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}
```

## Zoom and Reflow

Sites must support zoom without loss of content or functionality.

### 400% Zoom Requirement

At 400% zoom (1280px viewport → equivalent of 320px), content must:
- Reflow to single column
- Not require horizontal scrolling
- Remain fully functional

```css
/* Use relative units for scalability */
.container {
  max-width: 80rem; /* Scales with root font size */
  padding-inline: clamp(1rem, 5vw, 3rem);
}

/* Avoid fixed widths */
/* BAD */
.sidebar {
  width: 300px;
}

/* GOOD */
.sidebar {
  width: min(300px, 100%);
}
```

### 200% Text Scaling

Text must scale to at least 200% without breaking layouts:

```css
/* Use rem for font sizes */
.heading {
  font-size: clamp(1.5rem, 4vw, 3rem);
}

/* Avoid fixed heights on text containers */
/* BAD */
.card__title {
  height: 48px;
  overflow: hidden;
}

/* GOOD */
.card__title {
  min-height: 3rem;
}
```

### Responsive Testing

```css
/* Test these scenarios */
@media (max-width: 320px) {
  /* 400% zoom equivalent */
  /* Ensure single-column layout */
}

@media (min-resolution: 2dppx) {
  /* High DPI displays */
  /* Ensure crisp graphics */
}
```

## Motion and Animation

Respect user preferences for reduced motion.

### Reduced Motion

```css
/* Default animations */
.modal {
  animation: fade-in 0.3s ease-out;
}

@keyframes fade-in {
  from { opacity: 0; }
  to { opacity: 1; }
}

/* Respect reduced motion preference */
@media (prefers-reduced-motion: reduce) {
  .modal {
    animation: none;
  }

  /* Or use instant transitions */
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}
```

### Safe Animation Patterns

```css
/* Avoid animations that can trigger vestibular disorders */
/* BAD - Large movement, parallax */
.hero {
  animation: parallax-scroll 1s ease-out;
}

/* GOOD - Subtle, optional animations */
.button {
  transition: background-color 0.2s, transform 0.1s;
}

.button:hover {
  transform: translateY(-1px);
}

@media (prefers-reduced-motion: reduce) {
  .button {
    transition: none;
    transform: none;
  }
}
```

## RTL and Logical Properties

Support right-to-left languages with logical properties.

### Physical vs Logical Properties

| Physical Property | Logical Property |
|-------------------|------------------|
| `margin-left` | `margin-inline-start` |
| `margin-right` | `margin-inline-end` |
| `padding-left` | `padding-inline-start` |
| `padding-right` | `padding-inline-end` |
| `left` | `inset-inline-start` |
| `right` | `inset-inline-end` |
| `text-align: left` | `text-align: start` |
| `text-align: right` | `text-align: end` |
| `border-left` | `border-inline-start` |
| `float: left` | `float: inline-start` |

### Implementation

```css
/* Use logical properties throughout */
.card {
  padding-inline: 1.5rem;
  padding-block: 1rem;
  margin-block-end: 1rem;
}

.icon-text {
  display: flex;
  gap: 0.5rem;
}

.icon-text__icon {
  margin-inline-end: 0.5rem; /* Auto-flips in RTL */
}

/* Text alignment */
.nav-item {
  text-align: start; /* Left in LTR, right in RTL */
}

/* Positioning */
.dropdown {
  position: absolute;
  inset-inline-start: 0;
  inset-block-start: 100%;
}
```

### Directional Exceptions

Some elements should NOT flip in RTL:

```css
/* Phone numbers, code, and other non-directional content */
.phone-number,
.code-block {
  direction: ltr;
  unicode-bidi: isolate;
}

/* Icons that have inherent direction (play, arrows) may need manual handling */
[dir="rtl"] .icon--arrow-right {
  transform: scaleX(-1);
}
```

### Testing RTL

```html
<!-- Test by adding dir attribute -->
<html dir="rtl" lang="ar">

<!-- Or on specific containers -->
<div dir="rtl">
  <!-- Content to test in RTL -->
</div>
```

## Screen Reader Support

### Visually Hidden Content

Provide content for screen readers that's visually hidden:

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

/* Allow focus for skip links */
.visually-hidden:focus {
  position: static;
  width: auto;
  height: auto;
  margin: 0;
  overflow: visible;
  clip: auto;
  white-space: normal;
}
```

### Hidden from Screen Readers

Hide decorative content from assistive technology:

```css
/* Decorative elements */
.decorative-icon {
  /* Use aria-hidden="true" in HTML */
}

/* Content hidden visually and from AT */
.hidden {
  display: none;
}

/* Or */
[hidden] {
  display: none;
}
```

## Form Accessibility

### Error States

```css
/* Clear error indication */
.input--error {
  border-color: var(--color-error);
  background-color: var(--color-error-bg);
}

/* Error message styling */
.error-message {
  color: var(--color-error);
  font-size: 0.875rem;
  margin-block-start: 0.25rem;
}

/* Include icon for non-color indication */
.error-message::before {
  content: '';
  display: inline-block;
  width: 1em;
  height: 1em;
  margin-inline-end: 0.25em;
  background: url('error-icon.svg') no-repeat center;
  vertical-align: middle;
}
```

### Required Field Indication

```css
.label--required::after {
  content: ' *';
  color: var(--color-error);
}

/* Or use an icon */
.label--required::after {
  content: '';
  display: inline-block;
  width: 0.5em;
  height: 0.5em;
  margin-inline-start: 0.25em;
  background: var(--color-error);
  border-radius: 50%;
  vertical-align: super;
}
```

### Disabled States

```css
.input:disabled,
.button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Maintain contrast ratio for disabled text */
.input:disabled {
  color: var(--color-text-disabled); /* Still meets 4.5:1 */
  background-color: var(--color-background-disabled);
}
```

## Accessibility Checklist

Before deploying CSS changes, verify:

- [ ] All interactive elements have visible focus states
- [ ] Focus states use `:focus-visible` where appropriate
- [ ] Color contrast meets WCAG AA (4.5:1 normal text, 3:1 large text)
- [ ] Information is not conveyed by color alone
- [ ] Site works at 400% zoom without horizontal scrolling
- [ ] Text scales to 200% without breaking layout
- [ ] `prefers-reduced-motion` is respected
- [ ] Logical properties are used for RTL support
- [ ] Skip links are provided and functional
- [ ] Form errors are clearly indicated beyond color
- [ ] Visually hidden content is available for screen readers

## Testing Tools

- [WAVE Browser Extension](https://wave.webaim.org/extension/)
- [axe DevTools](https://www.deque.com/axe/devtools/)
- [Contrast Checker](https://webaim.org/resources/contrastchecker/)
- Browser DevTools accessibility panel
- Screen readers (VoiceOver, NVDA, JAWS)
