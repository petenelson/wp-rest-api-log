---
name: 10up-project-triage
description: Inspects WordPress repositories to determine project structure, available tooling, and 10up conventions. Outputs a structured JSON report with project signals. Use before making changes to understand the codebase structure and available tools.
license: MIT
compatibility: Requires Node.js 18+. Works with any WordPress project.
alwaysApply: true
metadata:
  author: 10up
  version: "1.0"
---

# 10up Project Triage

This skill provides deterministic detection of WordPress project structure and tooling. Run this before making changes to understand what you're working with.

## When to Use

- Starting work in an unfamiliar repository
- Before the router skill makes routing decisions
- When build commands fail and you need to understand tooling
- To detect available test frameworks
- To find WordPress/PHP version requirements

## Procedure

### Step 1: Run Detection Script

Execute the triage script from the project root:

```bash
node skills/10up-project-triage/scripts/detect_project.mjs
```

This outputs a JSON report with all detected signals.

### Step 2: Interpret the Output

The script produces a structured report:

```json
{
  "projectKind": "plugin|theme|block-theme|scaffold|monorepo|unknown",
  "is10upProject": true,
  "signals": {
    "hasThemeJson": true,
    "hasStyleCss": true,
    "hasPluginHeader": false,
    "hasBlocksDir": true,
    "hasPatternsDir": true
  },
  "tooling": {
    "buildTool": "10up-toolkit|wp-scripts|webpack|none",
    "packageManager": "npm|yarn|pnpm|none",
    "phpDependencies": "composer|none",
    "localEnv": "wp-env|10up-docker|none"
  },
  "testing": {
    "phpunit": true,
    "jest": false,
    "cypress": false,
    "playwright": false
  },
  "versions": {
    "wordpress": "6.4+",
    "php": "8.0+",
    "node": "18+"
  },
  "paths": {
    "blocks": "./blocks",
    "patterns": "./patterns",
    "templates": "./templates",
    "src": "./src"
  }
}
```

### Step 3: Manual Fallback

If the script is unavailable, check these files manually:

**Project Kind Detection:**

| Check | File/Pattern | Indicates |
|-------|--------------|-----------|
| Block Theme | `theme.json` + `/templates/*.html` | Block-based theme |
| Classic Theme | `style.css` with `Theme Name:` | Classic PHP theme |
| Plugin | `*.php` with `Plugin Name:` in header | WordPress plugin |
| Monorepo | `workspaces` in package.json | Multi-package repo |

**10up Detection:**

| Check | File/Pattern | Indicates |
|-------|--------------|-----------|
| 10up Toolkit | `10up-toolkit` in package.json | 10up build system |
| 10up Framework | `10up/wp-framework` in composer.json | 10up PHP framework |
| 10up Scaffold | `.tenup.yml` or scaffold structure | 10up project template |

**Tooling Detection:**

| Tool | Check |
|------|-------|
| Build | `10up-toolkit` → `npm run build`, `@wordpress/scripts` → `npm run build` |
| Watch | `npm run start` or `npm run watch` |
| Tests | `phpunit.xml` → PHPUnit, `jest.config` → Jest, `cypress.config` → Cypress |
| Lint | `npm run lint` or `composer run lint` |

### Step 4: Extract Version Requirements

**WordPress Version:**
- Check `Requires at least:` in plugin/theme header
- Check `composer.json` for `wordpress/core` requirement
- Default assumption: WordPress 6.4+

**PHP Version:**
- Check `Requires PHP:` in plugin/theme header
- Check `composer.json` for `php` requirement
- Default assumption: PHP 8.0+

**Node Version:**
- Check `engines.node` in package.json
- Default assumption: Node 18+

## Output Schema

See [references/schema.md](references/schema.md) for the complete JSON schema.

## Verification

After running triage:

1. Confirm detected project kind matches reality
2. Verify build commands work: `npm run build`
3. Check test commands: `npm test` or `composer test`
4. Validate version requirements match your environment

## Failure Modes

**Script not found:**
- Clone the agent-skills repo
- Or run manual detection (Step 3)

**Empty/invalid output:**
- Not in a WordPress project directory
- Missing expected files (package.json, composer.json)
- Run from project root, not subdirectory

**Wrong project kind detected:**
- Hybrid projects may have multiple signals
- Ask user to clarify primary project type
- Check for monorepo structure

## Escalation

Ask the user when:
- Project kind cannot be determined
- Multiple conflicting signals detected
- Custom build tooling not recognized
- Version requirements unclear
