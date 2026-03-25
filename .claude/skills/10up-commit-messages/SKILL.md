---
name: 10up-commit-messages
description: Write clear, consistent commit messages following 10up conventions. Uses conventional commit style with lowercase formatting. Use when creating commits, writing PR descriptions, or generating changelogs.
license: MIT
compatibility: Works with any git-based project. Integrates with changesets for versioning.
alwaysApply: true
metadata:
  author: 10up
  version: "1.0"
---

# 10up Commit Messages

This skill guides you through writing clear, consistent commit messages that follow 10up conventions and conventional commit standards.

## When to Use

- Creating git commits
- Writing commit messages for the user
- Generating PR descriptions
- Documenting changes in changelogs
- Reviewing commit message quality

## Format

```
<type>: <description>

[optional body]

[optional footer]
```

## Commit Types

| Type | When to Use | Example |
|------|-------------|---------|
| `fix:` | Bug fixes and error corrections | `fix: resolve block validation error on save` |
| `feature:` | New features or functionality | `feature: add hero section block with background image support` |
| `chore:` | Maintenance, dependencies, build changes | `chore: update 10up-toolkit to v6.5.0` |
| `documentation:` | Documentation only changes | `documentation: add InnerBlocks usage examples to README` |
| `refactor:` | Code changes without functional impact | `refactor: extract media upload logic to custom hook` |
| `test:` | Adding or updating tests | `test: add unit tests for block registration` |
| `style:` | Formatting, whitespace (not CSS) | `style: apply prettier formatting to block files` |
| `performance:` | Performance improvements | `performance: lazy load carousel images below fold` |

## Rules

### 1. Use lowercase throughout

```
# Good
fix: ensure carousel icon settings fall back to defaults correctly

# Bad - capitalized
Fix: Ensure carousel icon settings fall back to defaults correctly
```

### 2. Keep subject under 72 characters

Short, scannable subjects are easier to read in git log.

```
# Good (58 chars)
feature: add responsive breakpoint controls to grid block

# Bad (93 chars)
feature: add responsive breakpoint controls to the grid block component for better mobile support
```

### 3. Use imperative mood

Write as if giving a command: "add", "fix", "update" — not "added", "fixed", "updated".

```
# Good - imperative
fix: resolve attribute type mismatch in hero block
feature: add toggle for showing post excerpt

# Bad - past tense
fix: resolved attribute type mismatch in hero block
feature: added toggle for showing post excerpt
```

### 4. No period at the end

```
# Good
documentation: update contributing guidelines

# Bad
documentation: update contributing guidelines.
```

### 5. Be specific but concise

The subject should explain WHAT changed. Use the body for WHY if needed.

```
# Good - specific
fix: prevent duplicate block registration on hot reload

# Bad - vague
fix: block issue
fix: stuff
```

## Examples

### Simple commits

```
fix: ensure carousel icon settings fall back to defaults correctly
feature: add timeline block support
chore: apply changesets
fix: remove beta label from navigation plugin
refactor: simplify release process
documentation: update contributing guidelines
test: add cypress tests for accordion interactions
performance: reduce bundle size by tree-shaking unused icons
```

### Commit with body

For complex changes, add a body explaining the context:

```
fix: prevent block validation errors after attribute changes

The hero block was showing "Invalid block" after we renamed
the 'title' attribute to 'heading'. Added a deprecation entry
with proper migration to handle existing content.

Fixes #123
```

### Commit with scope (optional)

For monorepos or large projects, you can add a scope:

```
fix(accordion): ensure content panel respects max-height
feature(theme): add dark mode style variation
chore(deps): update @wordpress/scripts to 27.0.0
```

### Breaking changes

Use `!` after type or add `BREAKING CHANGE:` footer:

```
feature!: change block API to use render.php instead of save.js

BREAKING CHANGE: All blocks must now use dynamic rendering.
Static save functions are no longer supported.
```

## Avoid These Patterns

```
# Missing type prefix
fixed the carousel bug

# Too vague
fix
update
changes

# Capitalized with period
Fix: Ensure carousel works.

# Past tense
Added new feature

# Too long
feature: add responsive breakpoint controls to the grid block component for better mobile support and tablet compatibility

# Describing files instead of changes
fix: update index.js
```

## Multi-file Commits

When a commit touches multiple files, describe the overall change:

```
# Good - describes the change
feature: add image lazy loading to all media blocks

# Bad - lists files
feature: update hero.php, gallery.php, and image.php
```

## Automated Commits

Some commits are generated automatically by CI/CD:

- `chore: apply changesets` — Generated when changesets are versioned
- `chore: release v1.2.3` — Generated by release automation
- Merge commits follow git's default format

## Working with Changesets

For projects using changesets (common in 10up monorepos):

1. **Don't duplicate info** — The changeset file describes the change for the changelog
2. **Commit message = what** — Brief description of the code change
3. **Changeset = why + impact** — User-facing description for release notes

```bash
# Create changeset
npx changeset

# Commit with simple message
git commit -m "feature: add lazy loading to media blocks"
```

## PR Titles

PR titles should follow the same format as commit messages:

```
fix: resolve block validation error after attribute rename
feature: add hero section block with parallax support
```

For squash-merge workflows, the PR title becomes the commit message.

## Verification

When reviewing commits:

1. Type matches the change (fix vs feature vs chore)
2. Subject is lowercase
3. Under 72 characters
4. Imperative mood
5. No trailing period
6. Specific enough to understand without reading diff

## Procedure

When asked to create a commit:

1. Identify the type of change (fix, feature, chore, etc.)
2. Write a concise subject in imperative mood
3. Keep it under 72 characters
4. Use lowercase throughout
5. Add body only if context is needed
6. Include footer for breaking changes or issue references
