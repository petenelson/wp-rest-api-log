# 10up-toolkit Troubleshooting Reference

Common issues and solutions when using 10up-toolkit.

## Build Errors

### Module Not Found

**Error:**
```
Module not found: Error: Can't resolve './components/Button' in '/path/to/src'
```

**Solutions:**
1. Check the file exists at the specified path
2. Verify file extension matches import (`.js`, `.jsx`, `.ts`, `.tsx`)
3. Check for case-sensitivity issues (Linux is case-sensitive)
4. Clear cache: `rm -rf node_modules/.cache`

```javascript
// Wrong
import Button from './components/button';  // File is Button.js

// Correct
import Button from './components/Button';
```

### Cannot Find Module @wordpress/*

**Error:**
```
Module not found: Error: Can't resolve '@wordpress/blocks'
```

**Solutions:**
1. Install the package:
```bash
npm install @wordpress/blocks
```

2. Check if it's a peer dependency:
```bash
npm ls @wordpress/blocks
```

3. For common packages, install the meta-package:
```bash
npm install @wordpress/scripts
```

### Syntax Error in CSS/SCSS

**Error:**
```
SassError: expected "{".
```

**Solutions:**
1. Check for missing semicolons or brackets
2. Verify nested selector syntax
3. Check variable declarations

```scss
// Wrong
.button
  color: red

// Correct
.button {
  color: red;
}
```

### PostCSS Plugin Error

**Error:**
```
Error: Your current PostCSS version is 8.x, but postcss-preset-env uses 7.x
```

**Solution:**
Update PostCSS and plugins:
```bash
npm install postcss@latest postcss-preset-env@latest postcss-import@latest
```

## Runtime Errors

### WordPress Dependencies Not Loading

**Symptom:** Block doesn't register, console shows undefined WordPress packages

**Solutions:**
1. Check `.asset.php` file exists and contains correct dependencies
2. Verify script is enqueued with dependencies:

```php
$asset = require PLUGIN_PATH . 'dist/editor.asset.php';

wp_enqueue_script(
    'my-editor',
    PLUGIN_URL . 'dist/editor.js',
    $asset['dependencies'], // Must pass this array
    $asset['version'],
    true
);
```

3. Ensure WordPress version supports the package

### Styles Not Applied

**Symptom:** CSS compiles but doesn't show in browser

**Solutions:**
1. Check style is enqueued:
```php
wp_enqueue_style(
    'my-style',
    PLUGIN_URL . 'dist/style.css',
    [],
    $asset['version']
);
```

2. Check for CSS specificity issues
3. Clear browser cache
4. Check `SCRIPT_DEBUG` constant:
```php
define('SCRIPT_DEBUG', true);
```

### Hot Reload Not Working

**Symptom:** Changes don't appear without manual refresh

**Solutions:**
1. Ensure using `--watch` flag:
```bash
10up-toolkit build --watch
```

2. Check file is in watched directory
3. Restart watch mode
4. Check for file permission issues
5. Verify browser isn't caching aggressively

## Configuration Issues

### Entry Point Not Found

**Error:**
```
Entry module not found: Error: Can't resolve './src/index.js'
```

**Solutions:**
1. Create the entry file
2. Update config to match actual file location:

```javascript
module.exports = {
    entry: {
        main: './assets/src/index.js', // Match actual path
    },
};
```

### Output in Wrong Directory

**Symptom:** Files output to unexpected location

**Solution:**
Explicitly set paths:

```javascript
const path = require('path');

module.exports = {
    entry: {
        main: './src/index.js',
    },
    // Explicitly set output directory
    output: path.resolve(__dirname, 'dist'),
};
```

### Config File Not Read

**Symptom:** Custom configuration ignored

**Solutions:**
1. Ensure file is named `10up-toolkit.config.js` (not `.mjs`)
2. Place in project root (same level as package.json)
3. Check for syntax errors in config:

```bash
node -c 10up-toolkit.config.js
```

## Block Compilation Issues

### Block Not Detected

**Symptom:** Block in `src/blocks/` not compiled

**Solutions:**
1. Ensure `block.json` exists in block directory
2. Check `block.json` has valid JSON:
```bash
cat src/blocks/my-block/block.json | jq .
```

3. Verify directory structure:
```
src/blocks/my-block/
├── block.json      # Required
├── index.js        # Entry point
└── edit.js
```

### Block Script/Style Paths Wrong

**Error:** Block assets 404 in browser

**Solution:**
Use relative paths in `block.json`:

```json
{
  "editorScript": "file:./index.js",
  "editorStyle": "file:./editor.css",
  "style": "file:./style.css",
  "render": "file:./render.php"
}
```

## Performance Issues

### Slow Build Times

**Solutions:**
1. Use cache:
```javascript
module.exports = {
    cache: true,
};
```

2. Reduce number of entry points
3. Exclude unnecessary files:
```javascript
module.exports = {
    webpack: (config) => {
        config.watchOptions = {
            ignored: /node_modules/,
        };
        return config;
    },
};
```

4. Use faster source maps in development:
```javascript
module.exports = {
    webpack: (config, { isProduction }) => {
        if (!isProduction) {
            config.devtool = 'eval-cheap-module-source-map';
        }
        return config;
    },
};
```

### Large Bundle Size

**Diagnosis:**
```bash
ANALYZE=true npm run build
```

**Solutions:**
1. Check for duplicate dependencies:
```bash
npm dedupe
```

2. Use dynamic imports for large features:
```javascript
// Instead of
import HeavyComponent from './HeavyComponent';

// Use dynamic import
const HeavyComponent = lazy(() => import('./HeavyComponent'));
```

3. Tree shake unused exports:
```javascript
// Import only what you need
import { debounce } from 'lodash'; // Not: import _ from 'lodash'
```

4. Externalize large dependencies:
```javascript
module.exports = {
    externals: {
        'lodash': 'lodash',
        'moment': 'moment',
    },
};
```

## Environment Issues

### Node Version Mismatch

**Error:**
```
error your-project@1.0.0: The engine "node" is incompatible with this module.
```

**Solution:**
Use correct Node version:
```bash
nvm use 18
# or
nvm install 18
```

Add `.nvmrc`:
```
18
```

### Permission Denied

**Error:**
```
EACCES: permission denied
```

**Solutions:**
1. Fix npm permissions:
```bash
sudo chown -R $(whoami) ~/.npm
```

2. Use npm prefix:
```bash
npm config set prefix ~/.npm-global
```

3. Don't use sudo with npm

### Out of Memory

**Error:**
```
FATAL ERROR: CALL_AND_RETRY_LAST Allocation failed - JavaScript heap out of memory
```

**Solution:**
Increase Node memory:
```bash
NODE_OPTIONS=--max_old_space_size=4096 npm run build
```

Or in package.json:
```json
{
  "scripts": {
    "build": "NODE_OPTIONS=--max_old_space_size=4096 10up-toolkit build"
  }
}
```

## Debugging Commands

```bash
# Verbose build output
DEBUG=* npm run build

# Check webpack config
npm run build -- --json > webpack-stats.json

# Analyze bundle
ANALYZE=true npm run build

# Clear all caches
rm -rf node_modules/.cache
rm -rf dist/

# Reinstall dependencies
rm -rf node_modules
rm package-lock.json
npm install
```

## Quick Fixes Checklist

- [ ] Ran `npm install` recently?
- [ ] Cleared `.cache` directory?
- [ ] Using correct Node version?
- [ ] Config file named correctly?
- [ ] Entry files exist at specified paths?
- [ ] No syntax errors in config?
- [ ] Browser cache cleared?
- [ ] `SCRIPT_DEBUG` enabled in WordPress?
