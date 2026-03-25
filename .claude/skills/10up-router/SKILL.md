---
name: 10up-router
description: Entry point for WordPress development tasks. Classifies repository type (plugin, theme, block theme, 10up scaffold project) and routes to the correct 10up skill workflows. Use this skill first when starting any WordPress task or when unsure which skill applies.
license: MIT
compatibility: Designed for Claude Code and similar AI assistants. Works with any WordPress project.
alwaysApply: true
metadata:
  author: 10up
  version: "1.0"
---

# 10up Router

This skill is the **entry point** for all WordPress development tasks. It classifies your project and routes you to the correct specialized skills.

## When to Use

- Starting any WordPress development task
- Unsure which skill to apply
- Working in an unfamiliar repository
- Before making any code changes

## Procedure

### Step 0: Verify Environment

Before making changes, confirm:
1. You have appropriate permissions (read/write to the repository)
2. You know if this is development, staging, or production
3. The user has consented to modifications

**If production:** Require explicit confirmation before any changes.

### Step 1: Run Project Triage

Execute the triage script to detect project structure:

```bash
node skills/10up-project-triage/scripts/detect_project.mjs
```

If the script is unavailable, manually inspect these files:
- `style.css` in theme root (classic theme)
- `theme.json` (block theme)
- `*.php` with `Plugin Name:` header (plugin)
- `package.json` with `10up-toolkit` (10up project)
- `composer.json` with `10up/wp-framework` (10up framework)

### Step 2: Classify Project Kind

Based on triage output:

| Signal | Project Kind |
|--------|--------------|
| `theme.json` + HTML templates in `/templates/` | Block Theme |
| `style.css` with `Theme Name:` + PHP templates | Classic Theme |
| `*.php` with `Plugin Name:` header | Plugin |
| `package.json` with `10up-toolkit` | 10up Scaffold Project |
| `composer.json` with `10up/wp-framework` | 10up Framework Project |
| `/blocks/` directory with `block.json` files | Contains Custom Blocks |
| `/patterns/` directory with PHP files | Contains Block Patterns |

### Step 3: Detect 10up Conventions

Check for 10up-specific patterns:

**10up Scaffold Indicators:**
- `10up-toolkit` in package.json dependencies
- `/includes/` or `/src/` with namespaced PHP classes
- `10up-toolkit.config.js` configuration file
- `.tenup.yml` or similar config files

**10up Framework Indicators:**
- `10up/wp-framework` in composer.json
- Classes implementing `ModuleInterface`
- `ModuleInitialization::instance()->init_classes()` pattern

### Step 4: Route to Domain Skills

Based on classification, apply these skills:

**For Block Development Tasks:**
→ `10up-block-development` - Creating/modifying blocks
→ `10up-inner-blocks` - Nested block composition
→ `10up-block-extensions` - Extending core blocks
→ `10up-interactivity-api` - Frontend interactivity

**For Theme Tasks:**
→ `10up-block-themes` - Block theme development
→ `10up-block-patterns` - Pattern creation

**For Plugin Tasks:**
→ `10up-plugin-development` - Plugin architecture
→ `10up-wp-framework` - Framework patterns

**For Build/Tooling Tasks:**
→ `10up-toolkit` - Build configuration
→ `10up-scaffold` - Scaffolding new components

**For Quality Tasks:**
→ `10up-testing` - Writing tests
→ `10up-performance` - Optimization

### Step 5: Apply Guardrails

Before proceeding with any skill:

1. **Confirm file paths** - Don't assume directory structure
2. **Check existing patterns** - Match the project's conventions
3. **Preserve working code** - Don't refactor unnecessarily
4. **Use dynamic blocks** - 10up preference for client projects
5. **Follow security baseline** - Nonces, capabilities, escaping

## Decision Tree

See [references/decision-tree.md](references/decision-tree.md) for the complete routing logic.

## Failure Modes

**"Can't determine project type"**
- Look for `wp-config.php` to confirm WordPress
- Check parent directories for monorepo structure
- Ask user to clarify project type

**"Multiple project types detected"**
- Monorepos may contain both themes and plugins
- Ask user which component they want to work on
- Scope changes to the specific directory

**"No 10up conventions found"**
- Project may be vanilla WordPress
- Apply skills but note conventions may differ
- Recommend 10up patterns where appropriate

## Escalation

Ask the user when:
- Project type cannot be determined
- Working in production environment
- Significant architectural decisions needed
- Conflicting patterns detected
