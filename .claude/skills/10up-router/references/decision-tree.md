# Routing Decision Tree

This document provides the complete decision logic for routing WordPress development tasks to the appropriate skills.

## Primary Classification

```
START
│
├─ Is this a WordPress project?
│   ├─ NO → Not applicable, inform user
│   └─ YES ↓
│
├─ Does it contain theme.json + /templates/*.html?
│   ├─ YES → BLOCK THEME
│   └─ NO ↓
│
├─ Does it contain style.css with "Theme Name:" header?
│   ├─ YES → CLASSIC THEME
│   └─ NO ↓
│
├─ Does it contain *.php with "Plugin Name:" header?
│   ├─ YES → PLUGIN
│   └─ NO ↓
│
├─ Is it a WordPress core checkout?
│   ├─ YES → WP CORE (special handling)
│   └─ NO ↓
│
└─ Does it contain wp-content with multiple themes/plugins?
    ├─ YES → FULL SITE / MONOREPO
    └─ NO → UNKNOWN (ask user)
```

## Secondary Classification (10up Conventions)

After determining project kind, check for 10up patterns:

```
PROJECT IDENTIFIED
│
├─ package.json contains "10up-toolkit"?
│   ├─ YES → 10UP SCAFFOLD PROJECT
│   └─ NO ↓
│
├─ composer.json contains "10up/wp-framework"?
│   ├─ YES → 10UP FRAMEWORK PROJECT
│   └─ NO ↓
│
├─ PHP classes implement ModuleInterface?
│   ├─ YES → 10UP FRAMEWORK PATTERN
│   └─ NO ↓
│
└─ Standard WordPress conventions apply
```

## Task-Based Routing

Based on what the user wants to accomplish:

### "Create a block" / "Add a block"
1. Check if `/blocks/` directory exists
2. Determine if 10up scaffold is available
3. Route to: `10up-block-development`
4. If needs nesting: also `10up-inner-blocks`

### "Add interactivity" / "Make it interactive"
1. Confirm WordPress 6.5+
2. Route to: `10up-interactivity-api`
3. May also need: `10up-block-development`

### "Create a pattern" / "Add a pattern"
1. Check if `/patterns/` directory exists
2. Route to: `10up-block-patterns`

### "Extend a core block" / "Modify core block"
1. Route to: `10up-block-extensions`
2. NOT block styles if multiple controls needed

### "Fix build" / "Build not working"
1. Check for 10up-toolkit
2. Route to: `10up-toolkit`

### "Add tests" / "Fix tests"
1. Detect test framework (PHPUnit, Jest, Cypress)
2. Route to: `10up-testing`

### "Performance issue" / "Too slow"
1. Route to: `10up-performance`
2. May also need: `10up-toolkit` for build optimization

## Skill Combinations

Many tasks require multiple skills working together:

| Task | Primary Skill | Supporting Skills |
|------|--------------|-------------------|
| New interactive block | `10up-block-development` | `10up-interactivity-api`, `10up-inner-blocks` |
| New theme with patterns | `10up-block-themes` | `10up-block-patterns` |
| Plugin with blocks | `10up-plugin-development` | `10up-block-development`, `10up-wp-framework` |
| Build troubleshooting | `10up-toolkit` | `10up-scaffold` |
| Full test coverage | `10up-testing` | `10up-performance` |

## Guardrail Checks

Before routing, always verify:

1. **Environment Safety**
   - Development vs production
   - User has appropriate permissions
   - Backup exists if needed

2. **Existing Patterns**
   - Match the project's existing style
   - Don't introduce conflicting patterns
   - Preserve working functionality

3. **Version Compatibility**
   - WordPress version (check wp-includes/version.php)
   - PHP version (check composer.json or ask)
   - Node.js version (check package.json engines)
