---
name: 10up-toolkit
description: Configure and use 10up-toolkit for building WordPress themes and plugins. Covers webpack configuration, entry points, build optimization, and common issues. Use when setting up builds, troubleshooting compilation errors, or optimizing output.
license: MIT
compatibility: Node.js 18+, 10up-toolkit 6.x
globs:
  - 10up-toolkit.config.js
  - package.json
  - webpack.config.js
  - postcss.config.js
  - assets/**/*.js
  - assets/**/*.css
metadata:
  author: 10up
  version: "1.0"
---

# 10up Toolkit

This skill guides you through using 10up-toolkit — the official build system for 10up WordPress projects.

## When to Use

- Setting up a new 10up project build
- Adding new entry points for scripts or styles
- Troubleshooting build errors
- Optimizing bundle size
- Configuring PostCSS or Sass
- Working with block compilation

## Key Concepts

10up-toolkit is a zero-config build tool that:
- Compiles JavaScript (ES6+, JSX, TypeScript)
- Processes CSS (PostCSS, Sass)
- Handles WordPress block compilation
- Generates asset files (.asset.php) for dependencies
- Provides development server with hot reload

## Procedure

### Step 1: Installation

```bash
npm install 10up-toolkit --save-dev
```

Add scripts to package.json:

```json
{
  "scripts": {
    "start": "10up-toolkit start --hot",
    "watch": "10up-toolkit start",
    "build": "10up-toolkit build",
    "format-js": "10up-toolkit format-js",
    "lint-js": "10up-toolkit lint-js",
    "lint-style": "10up-toolkit lint-style"
  }
}
```

### Step 2: Default Directory Structure

Without configuration, 10up-toolkit uses this default structure:

```
assets/
├── js/
│   ├── admin/admin.js       → dist/admin.js
│   ├── frontend/frontend.js → dist/frontend.js
│   └── shared/shared.js     → dist/shared.js
├── css/
│   ├── admin/admin-style.css    → dist/admin-style.css
│   ├── frontend/style.css       → dist/style.css
│   └── frontend/editor-style.css → dist/editor-style.css
includes/
└── blocks/                  → Blocks are auto-detected here
```

### Step 3: Custom Configuration

Configuration is set in `package.json` under the `"10up-toolkit"` key:

```json
{
  "name": "my-theme",
  "scripts": {
    "start": "10up-toolkit start --hot",
    "build": "10up-toolkit build"
  },
  "devDependencies": {
    "10up-toolkit": "^6.5.0"
  },
  "10up-toolkit": {
    "entry": {
      "frontend": "./assets/js/frontend/frontend.js",
      "admin": "./assets/js/admin/admin.js",
      "style": "./assets/css/frontend/style.css",
      "admin-style": "./assets/css/admin/admin-style.css"
    }
  }
}
```

### Step 4: Multiple Entry Points

```json
{
  "10up-toolkit": {
    "entry": {
      "admin": "./assets/js/admin/admin.js",
      "frontend": "./assets/js/frontend/frontend.js",
      "shared": "./assets/js/shared/shared.js",
      "admin-style": "./assets/css/admin/admin-style.css",
      "editor-style": "./assets/css/frontend/editor-style.css",
      "style": "./assets/css/frontend/style.css",
      "core-block-overrides": "./includes/core-block-overrides.js"
    }
  }
}
```

### Step 5: Custom Paths

```json
{
  "10up-toolkit": {
    "paths": {
      "srcDir": "./src/",
      "blocksDir": "./src/blocks/",
      "copyAssetsDir": "./src/",
      "cssLoaderPaths": ["./src/css", "./src/blocks"]
    },
    "entry": {
      "frontend": "./src/js/frontend.js"
    }
  }
}
```

### Step 6: Block Compilation

10up-toolkit automatically detects and compiles blocks from `includes/blocks/`:

**Auto-detection structure:**

```
includes/blocks/
├── hero/
│   ├── block.json
│   ├── index.js
│   ├── edit.js
│   └── style.css
└── card/
    ├── block.json
    ├── index.js
    └── edit.js
```

**Manual entry (if blocks are elsewhere):**

```json
{
  "10up-toolkit": {
    "paths": {
      "blocksDir": "./src/blocks/"
    },
    "entry": {
      "blocks/hero/index": "./src/blocks/hero/index.js",
      "blocks/card/index": "./src/blocks/card/index.js"
    }
  }
}
```

### Step 7: WordPress Dependencies

10up-toolkit extracts WordPress package dependencies and generates `.asset.php` files:

```javascript
// assets/js/editor/editor.js
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
```

Generates `dist/editor.asset.php`:

```php
<?php return array(
    'dependencies' => array('wp-blocks', 'wp-block-editor', 'wp-element'),
    'version' => 'abc123...'
);
```

### Step 8: PostCSS Configuration

Create `postcss.config.js`:

```javascript
module.exports = {
    plugins: [
        require('postcss-import'),
        require('postcss-preset-env')({
            stage: 2,
            features: {
                'nesting-rules': true,
                'custom-media-queries': true,
            },
        }),
        require('autoprefixer'),
    ],
};
```

For CSS authoring guidelines including ITCSS architecture, specificity management, and naming conventions, see [10up-css](../10up-css/SKILL.md).

### Step 9: Sass Support

Install Sass:

```bash
npm install sass --save-dev
```

Use `.scss` files:

```
assets/
├── css/
│   └── frontend/
│       └── style.scss
└── components/
    ├── _buttons.scss
    └── _cards.scss
```

### Step 10: TypeScript Support

Install TypeScript:

```bash
npm install typescript --save-dev
```

Create `tsconfig.json`:

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "node",
    "jsx": "react-jsx",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true
  },
  "include": ["assets/**/*", "includes/**/*"]
}
```

## Common Commands

| Command | Description |
|---------|-------------|
| `npm run build` | Production build |
| `npm run start` or `npm run watch` | Watch mode |
| `npm run start -- --hot` | Watch with HMR |
| `npm run format-js` | Format JavaScript files |
| `npm run lint-js` | Lint JavaScript |
| `npm run lint-style` | Lint CSS/Sass |

## CLI Flags

| Flag | Description |
|------|-------------|
| `--hot` | Enable hot module replacement |
| `--analyze` | Generate bundle analyzer report |
| `--sourcemap` | Enable source maps |
| `--wp=false` | Disable WordPress externals |
| `--port=8000` | Set dev server port |
| `--include=path1,path2` | Additional paths to include |
| `--block-modules` | Enable script modules for blocks |

## Advanced Configuration

### Additional Configuration Options

```json
{
  "10up-toolkit": {
    "useBlockAssets": true,
    "useScriptModules": false,
    "loadBlockSpecificStyles": true,
    "sourcemap": true,
    "publicPath": "/wp-content/themes/my-theme/dist/",
    "filenames": {
      "blockCSS": "blocks/[name]/editor.css"
    }
  }
}
```

### Custom Webpack Config

Create a `webpack.config.js` file in your project root to extend the default config:

```javascript
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve.alias,
            '@components': path.resolve(__dirname, 'assets/js/components'),
        },
    },
};
```

### External Dependencies

Exclude packages from bundle via CLI or package.json:

```json
{
  "10up-toolkit": {
    "wpDependencyExternals": true
  }
}
```

Or use environment variable:

```bash
TENUP_NO_EXTERNALS=true npm run build
```

## Verification

After building:

1. Check `dist/` folder contains expected files
2. Verify `.asset.php` files have correct dependencies
3. Test in WordPress with `SCRIPT_DEBUG` enabled
4. Check browser console for errors
5. Verify styles are applied

## Failure Modes

**Build fails with module not found:**
- Check import paths are correct
- Run `npm install` to ensure dependencies
- Verify file extensions

**Styles not compiling:**
- Check entry point includes CSS/SCSS file
- Verify PostCSS config syntax
- Check for Sass syntax errors

**WordPress dependencies wrong:**
- Imports must be from `@wordpress/*` packages
- Check package is installed: `npm install @wordpress/blocks`
- Rebuild after adding imports

**Watch mode not detecting changes:**
- Check file is within `paths.srcDir` (default: `./assets/`)
- Restart watch mode
- Clear node_modules/.cache

**Asset file missing:**
- JavaScript entry must exist
- Check output path in config
- Verify build completed without errors

## Environment Variables

```bash
# Disable WordPress externals (bundle all deps)
TENUP_NO_EXTERNALS=true npm run build

# Set custom public path for assets
ASSET_PATH=/custom/path/ npm run build
```

## Migration from @wordpress/scripts

```json
// @wordpress/scripts
{
  "scripts": {
    "build": "wp-scripts build",
    "start": "wp-scripts start"
  }
}

// 10up-toolkit
{
  "scripts": {
    "build": "10up-toolkit build",
    "start": "10up-toolkit start --hot",
    "watch": "10up-toolkit start"
  }
}
```

Main differences:
- Config is in `package.json` under `"10up-toolkit"` key (not a separate config file)
- `start` command for watch mode, not `build --watch`
- Default source directory is `./assets/` not `./src/`
- Default blocks directory is `./includes/blocks/` not `./src/blocks/`

## Escalation

Ask the user when:
- Complex multi-bundle configurations needed
- Custom webpack loaders required
- Build performance issues with large projects
- Integration with other build tools
