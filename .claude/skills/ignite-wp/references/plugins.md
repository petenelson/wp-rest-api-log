# Ignite Plugins Reference

Complete documentation for all Ignite WP plugins.

## Plugin Dependency

All Ignite plugins depend on **ignite-wp-core**. Always ensure core is active.

## ignite-wp-core

**Purpose:** Foundation plugin providing shared utilities.

### Features

- Icon registration and rendering system
- Synced Theme Patterns
- Enhanced Section Styles
- Fluid typography and spacing with CSS clamp()
- Typography Design Presets
- Video Cover Controls
- Fallback featured image support
- Normalized `wp-block-*` class names
- Screen Reader Only rich text format
- Adjustable separator block height

### Default Icons

```
check, chevron-down, chevron-left, chevron-right, chevron-up,
chevrons-down, chevrons-left, chevrons-right, chevrons-up,
close, menu, minus, plus, search,
pause, play, loading-indicator,
facebook, github, instagram, linkedin, twitter, youtube
```

### WP-CLI Commands

```bash
# Sync theme patterns to database
wp ignite-wp sync-theme-patterns
```

### Admin Tools

- Tools > Synced Theme Patterns Sync

---

## ignite-wp-accordion

**Purpose:** Accessible collapsible accordion sections.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/accordion-group` | Container for multiple accordions |
| `tenup/accordion` | Single accordion item |
| `tenup/accordion-header` | Trigger/title |
| `tenup/accordion-content` | Content area |

### Features

- Single or multiple panels open at once
- Customizable expand/collapse icons
- Icon position control (left/right)
- ARIA attributes and keyboard navigation
- WordPress Interactivity API

---

## ignite-wp-animate-blocks

**Purpose:** Scroll-triggered entrance animations.

### Features

- Inspector panel on every block's styles tab
- Animation type, duration, delay, easing controls
- Per-block-type enable/disable via theme.json
- Individual block opt-out via supports

### Block Supports Opt-Out

```json
{
  "name": "core/paragraph",
  "supports": {
    "ignite-wp": {
      "animation": false
    }
  }
}
```

---

## ignite-wp-carousel

**Purpose:** Flexible carousel/slider powered by SplideJS.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/carousel` | Carousel container |
| `tenup/carousel-item` | Individual slide |

### Features

- Configurable slides per page with breakpoints
- Slide types: slide, fade, loop
- Optional pagination and arrows
- Customizable arrow icons
- Block variations support
- Interactivity API integration

### JavaScript Filters

```javascript
// Allowed inner blocks
addFilter('tenup.carousel.allowedBlocks', 'ns/filter', blocks => [...]);

// Carousel template
addFilter('tenup.carousel.template', 'ns/filter', template => [...]);

// Carousel item template
addFilter('ignite-wp.carouselItem.template', 'ns/filter', template => [...]);
```

---

## ignite-wp-copyright

**Purpose:** Display current year automatically.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/copyright` | Current year with prefix/suffix |

### Features

- Dynamic year rendering
- Custom prefix and suffix text
- Full paragraph styling options

> **Note:** WordPress 6.5+ Block Bindings API can achieve similar results.

---

## ignite-wp-description-list

**Purpose:** Definition list block structure.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/description-list` | DL container |
| `tenup/description-term` | DT element |
| `tenup/description-details` | DD element |

---

## ignite-wp-editorial

**Purpose:** Enterprise editorial workflow system.

### Features

- Required Attributes with validation
- Character Limits with real-time feedback
- Write Mode for distraction-free editing
- Block Identifier for targeting
- Block Notes (REST API comments)
- Review Mode for stakeholder feedback
- Custom Post Statuses
- Editorial Roles and capabilities
- Post Revisions workflow
- Editor Locking during review

### Custom Capabilities

| Capability | Description |
|------------|-------------|
| `submit_for_review` | Submit posts for review |
| `resubmit_for_review` | Resubmit after changes |
| `review_posts` | Review submitted posts |
| `request_changes` | Request changes with feedback |
| `approve_for_publishing` | Approve for publication |

### Custom Post Statuses

- `ready_for_review`
- `in_review`
- `changes_requested`
- `ready_for_publishing`

---

## ignite-wp-icons

**Purpose:** UI for inserting and managing SVG icons.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/icon` | Standalone icon block |
| Rich text format | Inline icons in text |

### Features

- Icon color inherits from text color
- Icon size scales with font size (inline)
- Full styling controls
- Uses Ignite Core icon system

### React Components

```javascript
import { Icon, IconPicker } from '@10up/block-components';

<Icon iconSet="ignite-wp" name="search" className="my-icon" />
```

---

## ignite-wp-infinite-scroll

**Purpose:** Load more pagination without page refresh.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/infinite-scroll` | Load more button for queries |

---

## ignite-wp-modal

**Purpose:** Accessible modal dialogs (a11y-dialog).

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/modal` | Modal dialog container |

### Features

- WAI-ARIA accessibility
- Multiple triggers per modal
- Triggers and modals can live separately
- Customizable overlay color
- Automatic embed pause on close
- Interactivity API hooks

### Supported Trigger Blocks (Default)

- `core/button`
- `core/image`
- `core/media-text`

---

## ignite-wp-navigation

**Purpose:** Complete site header and navigation system.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/site-header` | Header container with state |
| `tenup/navigation` | Menu using slug (portable) |
| `tenup/navigation-megamenu` | Megamenu via template parts |
| `tenup/navigation-portal` | Move elements between viewports |
| `tenup/search-button` | Expandable search |

### Features

- Centralized Interactivity API store
- Menu slug instead of ID
- Megamenu with template parts
- Navigation Portal for responsive
- Optional Headroom.js integration
- Backdrop support
- CSS `--header-height` variable
- Debug mode

---

## ignite-wp-post-meta-blocks

**Purpose:** Display custom field values.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/post-meta` | Display string meta field |
| `tenup/post-meta-image` | Display image from meta |
| `tenup/post-meta-conditional` | Render only if meta exists |

### Features

- Works in Query Loops
- Optional label display
- Configurable HTML element
- Compatible with Block Bindings API

---

## ignite-wp-post-picker

**Purpose:** Manual post selection and Query Loop enhancements.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/post-picker` | Select and display specific posts |
| `tenup/query-featured-post-template` | Highlight first X items |
| `tenup/time-to-read` | Reading time (200 wpm) |
| `tenup/primary-term` | Primary term (Yoast SEO) |
| `tenup/post-type-label` | Post type name display |

### Features

- Same templating as Query Loop
- Starter patterns support
- Duplicate removal from queries
- Entire card clickable option

> **Warning:** Duplicate removal uses `posts__not_in` which can impact performance. Best with ElasticPress.

---

## ignite-wp-query-filter

**Purpose:** Interactive Query block filtering.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/query-checkbox-filter` | Multi-select checkboxes |
| `tenup/query-select-filter` | Dropdown single-select |
| `tenup/query-keyword-search` | Text search input |
| `tenup/query-load-more` | AJAX load more button |

### Features

- Real-time AJAX filtering
- WordPress enhanced pagination compatible
- Loading state indicators
- Interactivity API integration

> **Note:** Currently in BETA.

---

## ignite-wp-stats

**Purpose:** Animated counting statistics.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/stats` | Animated number display |

### Features

- Scroll-triggered count animation
- IntersectionObserver-based
- Prefix and suffix text support
- Smooth ease-out easing
- Configurable HTML element

---

## ignite-wp-tabs

**Purpose:** Accessible tabbed content interfaces.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/tabs` | Tabbed interface |

### Features

- Horizontal or vertical orientation
- Freeform inner block content
- Configurable max tabs
- WAI-ARIA keyboard navigation
- Interactivity API

---

## ignite-wp-timeline

**Purpose:** Chronological milestone visualization.

### Blocks

| Block | Description |
|-------|-------------|
| `tenup/timeline` | Main container |
| `tenup/timeline-section` | Groups related milestones |
| `tenup/timeline-milestone` | Individual milestone |

### Features

- Responsive alternating layout
- Section labels with heading levels
- Flexible milestone content
- Vertical line with connecting dots

### Responsive Behavior

- **Mobile (<1024px):** Full width, centered line
- **Desktop (≥1024px):** 50% width, alternating left/right

---

## ignite-wp-theme

**Purpose:** Lightweight block-based parent theme.

### Features

- Fully block-based (no classic PHP templates)
- Comprehensive theme.json design tokens
- Pre-built patterns
- Minimal custom CSS/JS
- Publisher/editorial focused

### Best Practices

1. Always use as parent theme with child theme
2. Minimize custom CSS/JS
3. Scope styles via `wp_enqueue_block_style()`
4. Add frontend JS via block `viewScript`
5. Leverage theme.json for design tokens
