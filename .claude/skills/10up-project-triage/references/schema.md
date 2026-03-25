# Project Triage Output Schema

JSON schema definition for the project triage detection output.

## Full Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Project Triage Report",
  "type": "object",
  "properties": {
    "projectKind": {
      "type": "string",
      "enum": ["plugin", "theme", "block-theme", "scaffold", "monorepo", "mu-plugin", "unknown"],
      "description": "Primary project type detected"
    },
    "is10upProject": {
      "type": "boolean",
      "description": "Whether 10up tooling or conventions were detected"
    },
    "projectName": {
      "type": "string",
      "description": "Detected project name from package.json, composer.json, or file headers"
    },
    "signals": {
      "type": "object",
      "description": "Detection signals found in the project",
      "properties": {
        "hasThemeJson": { "type": "boolean" },
        "hasStyleCss": { "type": "boolean" },
        "hasPluginHeader": { "type": "boolean" },
        "hasBlocksDir": { "type": "boolean" },
        "hasPatternsDir": { "type": "boolean" },
        "hasTemplatesDir": { "type": "boolean" },
        "hasPartsDir": { "type": "boolean" },
        "hasSrcDir": { "type": "boolean" },
        "hasIncludesDir": { "type": "boolean" },
        "hasPackageJson": { "type": "boolean" },
        "hasComposerJson": { "type": "boolean" },
        "hasWorkspaces": { "type": "boolean" },
        "has10upToolkit": { "type": "boolean" },
        "has10upFramework": { "type": "boolean" },
        "hasWpScripts": { "type": "boolean" },
        "hasTenupYml": { "type": "boolean" }
      }
    },
    "tooling": {
      "type": "object",
      "description": "Build and development tooling detected",
      "properties": {
        "buildTool": {
          "type": "string",
          "enum": ["10up-toolkit", "wp-scripts", "webpack", "vite", "none"]
        },
        "packageManager": {
          "type": "string",
          "enum": ["npm", "yarn", "pnpm", "none"]
        },
        "phpDependencies": {
          "type": "string",
          "enum": ["composer", "none"]
        },
        "localEnv": {
          "type": "string",
          "enum": ["wp-env", "10up-docker", "local", "ddev", "lando", "none"]
        },
        "linter": {
          "type": "string",
          "enum": ["eslint", "phpcs", "both", "none"]
        }
      }
    },
    "testing": {
      "type": "object",
      "description": "Testing frameworks detected",
      "properties": {
        "phpunit": { "type": "boolean" },
        "jest": { "type": "boolean" },
        "cypress": { "type": "boolean" },
        "playwright": { "type": "boolean" },
        "wpUnit": { "type": "boolean" }
      }
    },
    "versions": {
      "type": "object",
      "description": "Required/recommended versions",
      "properties": {
        "wordpress": { "type": "string" },
        "php": { "type": "string" },
        "node": { "type": "string" }
      }
    },
    "paths": {
      "type": "object",
      "description": "Important directory paths relative to project root",
      "properties": {
        "blocks": { "type": "string" },
        "patterns": { "type": "string" },
        "templates": { "type": "string" },
        "parts": { "type": "string" },
        "src": { "type": "string" },
        "includes": { "type": "string" },
        "dist": { "type": "string" },
        "assets": { "type": "string" },
        "languages": { "type": "string" }
      }
    },
    "scripts": {
      "type": "object",
      "description": "Available npm scripts",
      "properties": {
        "build": { "type": "string" },
        "start": { "type": "string" },
        "watch": { "type": "string" },
        "lint": { "type": "string" },
        "test": { "type": "string" },
        "format": { "type": "string" }
      }
    },
    "entryPoints": {
      "type": "array",
      "description": "Main entry point files",
      "items": { "type": "string" }
    },
    "blocks": {
      "type": "array",
      "description": "Detected custom blocks",
      "items": {
        "type": "object",
        "properties": {
          "name": { "type": "string" },
          "path": { "type": "string" },
          "isDynamic": { "type": "boolean" }
        }
      }
    }
  },
  "required": ["projectKind", "is10upProject", "signals", "tooling"]
}
```

## Example Outputs

### Block Theme

```json
{
  "projectKind": "block-theme",
  "is10upProject": true,
  "projectName": "my-theme",
  "signals": {
    "hasThemeJson": true,
    "hasStyleCss": true,
    "hasPluginHeader": false,
    "hasBlocksDir": true,
    "hasPatternsDir": true,
    "hasTemplatesDir": true,
    "hasPartsDir": true,
    "hasSrcDir": true,
    "hasPackageJson": true,
    "hasComposerJson": true,
    "has10upToolkit": true,
    "has10upFramework": false,
    "hasWpScripts": false
  },
  "tooling": {
    "buildTool": "10up-toolkit",
    "packageManager": "npm",
    "phpDependencies": "composer",
    "localEnv": "wp-env",
    "linter": "both"
  },
  "testing": {
    "phpunit": true,
    "jest": true,
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
    "parts": "./parts",
    "src": "./src",
    "dist": "./dist"
  },
  "scripts": {
    "build": "10up-toolkit build",
    "start": "10up-toolkit start",
    "lint": "npm run lint:js && npm run lint:css && npm run lint:php"
  },
  "blocks": [
    { "name": "theme-name/hero", "path": "./blocks/hero", "isDynamic": true },
    { "name": "theme-name/card", "path": "./blocks/card", "isDynamic": true }
  ]
}
```

### Plugin

```json
{
  "projectKind": "plugin",
  "is10upProject": true,
  "projectName": "my-plugin",
  "signals": {
    "hasThemeJson": false,
    "hasStyleCss": false,
    "hasPluginHeader": true,
    "hasBlocksDir": true,
    "hasPatternsDir": false,
    "hasTemplatesDir": false,
    "hasSrcDir": true,
    "hasIncludesDir": true,
    "hasPackageJson": true,
    "hasComposerJson": true,
    "has10upToolkit": true,
    "has10upFramework": true
  },
  "tooling": {
    "buildTool": "10up-toolkit",
    "packageManager": "npm",
    "phpDependencies": "composer",
    "localEnv": "10up-docker",
    "linter": "both"
  },
  "testing": {
    "phpunit": true,
    "jest": false,
    "wpUnit": true
  },
  "versions": {
    "wordpress": "6.4+",
    "php": "8.1+",
    "node": "18+"
  },
  "paths": {
    "blocks": "./blocks",
    "src": "./src",
    "includes": "./includes",
    "dist": "./dist",
    "languages": "./languages"
  },
  "entryPoints": ["my-plugin.php"]
}
```

### Monorepo

```json
{
  "projectKind": "monorepo",
  "is10upProject": true,
  "projectName": "client-project",
  "signals": {
    "hasWorkspaces": true,
    "hasPackageJson": true,
    "hasComposerJson": true
  },
  "tooling": {
    "buildTool": "10up-toolkit",
    "packageManager": "npm",
    "phpDependencies": "composer",
    "localEnv": "wp-env"
  },
  "paths": {
    "themes": "./themes",
    "plugins": "./plugins",
    "muPlugins": "./mu-plugins"
  },
  "workspaces": [
    { "name": "theme", "path": "./themes/client-theme", "kind": "block-theme" },
    { "name": "plugin", "path": "./plugins/client-plugin", "kind": "plugin" }
  ]
}
```

## Field Definitions

### projectKind

| Value | Description |
|-------|-------------|
| `plugin` | WordPress plugin (single or multi-file) |
| `theme` | Classic PHP theme |
| `block-theme` | Block-based theme with theme.json |
| `scaffold` | 10up scaffold template |
| `monorepo` | Multi-package repository |
| `mu-plugin` | Must-use plugin |
| `unknown` | Could not determine |

### buildTool

| Value | Description |
|-------|-------------|
| `10up-toolkit` | 10up's webpack-based build system |
| `wp-scripts` | WordPress's official scripts package |
| `webpack` | Custom webpack configuration |
| `vite` | Vite build tool |
| `none` | No JavaScript build tooling |

### localEnv

| Value | Description |
|-------|-------------|
| `wp-env` | WordPress's official local environment |
| `10up-docker` | 10up's Docker configuration |
| `local` | Local by Flywheel / WP Local |
| `ddev` | DDEV local environment |
| `lando` | Lando local environment |
| `none` | No local environment detected |

## Using the Schema

### Validation

```javascript
import Ajv from 'ajv';
import schema from './triage-schema.json';

const ajv = new Ajv();
const validate = ajv.compile(schema);

const report = runTriage();

if (!validate(report)) {
    console.error('Invalid report:', validate.errors);
}
```

### TypeScript Types

```typescript
interface TriageReport {
    projectKind: 'plugin' | 'theme' | 'block-theme' | 'scaffold' | 'monorepo' | 'mu-plugin' | 'unknown';
    is10upProject: boolean;
    projectName?: string;
    signals: {
        hasThemeJson?: boolean;
        hasStyleCss?: boolean;
        hasPluginHeader?: boolean;
        hasBlocksDir?: boolean;
        hasPatternsDir?: boolean;
        hasTemplatesDir?: boolean;
        hasPartsDir?: boolean;
        hasSrcDir?: boolean;
        hasIncludesDir?: boolean;
        hasPackageJson?: boolean;
        hasComposerJson?: boolean;
        hasWorkspaces?: boolean;
        has10upToolkit?: boolean;
        has10upFramework?: boolean;
        hasWpScripts?: boolean;
        hasTenupYml?: boolean;
    };
    tooling: {
        buildTool: '10up-toolkit' | 'wp-scripts' | 'webpack' | 'vite' | 'none';
        packageManager: 'npm' | 'yarn' | 'pnpm' | 'none';
        phpDependencies: 'composer' | 'none';
        localEnv: 'wp-env' | '10up-docker' | 'local' | 'ddev' | 'lando' | 'none';
        linter?: 'eslint' | 'phpcs' | 'both' | 'none';
    };
    testing?: {
        phpunit?: boolean;
        jest?: boolean;
        cypress?: boolean;
        playwright?: boolean;
        wpUnit?: boolean;
    };
    versions?: {
        wordpress?: string;
        php?: string;
        node?: string;
    };
    paths?: Record<string, string>;
    scripts?: Record<string, string>;
    entryPoints?: string[];
    blocks?: Array<{
        name: string;
        path: string;
        isDynamic?: boolean;
    }>;
}
```
