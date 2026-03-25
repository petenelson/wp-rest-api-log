# Webpack Customization Reference

Advanced webpack configuration for 10up-toolkit.

## Configuration Location

10up-toolkit configuration is set in `package.json` under the `"10up-toolkit"` key:

```json
{
  "10up-toolkit": {
    "entry": {
      "frontend": "./assets/js/frontend/frontend.js"
    },
    "paths": {
      "srcDir": "./assets/"
    }
  }
}
```

## Extending Webpack Config

To customize webpack, create a `webpack.config.js` file in your project root that extends the default config:

### Basic Extension

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const path = require('path');

module.exports = {
    ...defaultConfig,
    // Your customizations here
};
```

### Extending With Modifications

```javascript
// webpack.config.js
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

## Adding Aliases

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const path = require('path');

module.exports = {
    ...defaultConfig,
    resolve: {
        ...defaultConfig.resolve,
        alias: {
            ...defaultConfig.resolve.alias,
            '@components': path.resolve(__dirname, 'assets/js/components'),
            '@utils': path.resolve(__dirname, 'assets/js/utils'),
            '@blocks': path.resolve(__dirname, 'includes/blocks'),
            '@styles': path.resolve(__dirname, 'assets/css'),
        },
    },
};
```

Usage:
```javascript
import Button from '@components/Button';
import { formatDate } from '@utils/helpers';
```

## Adding Loaders

### SVG as React Component

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules.map(rule => {
                // Exclude SVGs from existing asset rule
                if (rule.test && rule.test.toString().includes('svg')) {
                    return { ...rule, exclude: /\.svg$/ };
                }
                return rule;
            }),
            // Add SVGR loader
            {
                test: /\.svg$/,
                use: ['@svgr/webpack'],
            },
        ],
    },
};
```

Install:
```bash
npm install @svgr/webpack --save-dev
```

### GraphQL Files

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.(graphql|gql)$/,
                exclude: /node_modules/,
                loader: 'graphql-tag/loader',
            },
        ],
    },
};
```

### Raw File Import

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    module: {
        ...defaultConfig.module,
        rules: [
            ...defaultConfig.module.rules,
            {
                test: /\.txt$/,
                type: 'asset/source',
            },
        ],
    },
};
```

## Adding Plugins

### Copy Static Files

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const CopyPlugin = require('copy-webpack-plugin');

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        new CopyPlugin({
            patterns: [
                {
                    from: 'assets/static',
                    to: 'static',
                },
            ],
        }),
    ],
};
```

### Define Global Constants

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const webpack = require('webpack');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        new webpack.DefinePlugin({
            'process.env.API_URL': JSON.stringify(
                isProduction
                    ? 'https://api.example.com'
                    : 'http://localhost:3000'
            ),
            __DEV__: !isProduction,
        }),
    ],
};
```

### Banner Plugin (Add Comments to Output)

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const webpack = require('webpack');

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        new webpack.BannerPlugin({
            banner: `/**
 * @license
 * Theme Name - v1.0.0
 * Copyright (c) 2024
 */`,
            raw: true,
        }),
    ],
};
```

## Optimization

### Split Chunks

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    optimization: {
        ...defaultConfig.optimization,
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    name: 'vendors',
                    chunks: 'all',
                },
                common: {
                    minChunks: 2,
                    priority: -10,
                    reuseExistingChunk: true,
                },
            },
        },
    },
};
```

### Minimize Options

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const TerserPlugin = require('terser-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    ...defaultConfig,
    optimization: {
        ...defaultConfig.optimization,
        ...(isProduction && {
            minimizer: [
                new TerserPlugin({
                    terserOptions: {
                        compress: {
                            drop_console: true,
                        },
                        mangle: true,
                    },
                    extractComments: false,
                }),
            ],
        }),
    },
};
```

### Tree Shaking

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    optimization: {
        ...defaultConfig.optimization,
        usedExports: true,
        sideEffects: true,
    },
};
```

## Source Maps

Enable source maps via CLI flag or package.json config:

```bash
# CLI flag
10up-toolkit build --sourcemap
```

```json
{
  "10up-toolkit": {
    "sourcemap": true
  }
}
```

Or customize in webpack.config.js:

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    ...defaultConfig,
    devtool: isProduction ? false : 'eval-cheap-module-source-map',
};
```

| devtool | Build | Rebuild | Quality |
|---------|-------|---------|---------|
| `eval` | Fast | Fast | Generated |
| `eval-cheap-module-source-map` | Medium | Fast | Original |
| `source-map` | Slow | Slow | Original |
| `hidden-source-map` | Slow | Slow | Original (no reference) |

## External Dependencies

### WordPress Packages

10up-toolkit automatically externalizes WordPress packages. Control via environment variable:

```bash
# Disable externals (bundle all deps)
TENUP_NO_EXTERNALS=true npm run build
```

Or via package.json:

```json
{
  "10up-toolkit": {
    "wpDependencyExternals": false
  }
}
```

### Custom Externals

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    externals: {
        ...defaultConfig.externals,
        'gsap': 'gsap',
        'three': 'THREE',
    },
};
```

Then in PHP:
```php
wp_enqueue_script('gsap', 'https://cdn.example.com/gsap.min.js', [], '3.12.0', true);
wp_enqueue_script('my-script', ..., ['gsap'], ...);
```

## Development Server

Configure dev server via CLI flags:

```bash
# With HMR on custom port
10up-toolkit start --hot --port=3000
```

Or customize in webpack.config.js:

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    devServer: {
        ...defaultConfig.devServer,
        port: 3000,
        hot: true,
        liveReload: true,
        client: {
            overlay: {
                errors: true,
                warnings: false,
            },
        },
    },
};
```

## Watch Options

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

module.exports = {
    ...defaultConfig,
    watchOptions: {
        ignored: /node_modules/,
        aggregateTimeout: 300,
        poll: 1000, // Use if watch doesn't work
    },
};
```

## Output Configuration

### Custom Filenames

Configure via package.json:

```json
{
  "10up-toolkit": {
    "filenames": {
      "block": "blocks/[name]/index.js",
      "blockCSS": "blocks/[name]/style.css",
      "blockEditorCSS": "blocks/[name]/editor.css"
    }
  }
}
```

Or in webpack.config.js:

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    ...defaultConfig,
    output: {
        ...defaultConfig.output,
        filename: isProduction
            ? '[name].[contenthash:8].js'
            : '[name].js',
        chunkFilename: isProduction
            ? '[name].[contenthash:8].chunk.js'
            : '[name].chunk.js',
    },
};
```

### Public Path

Configure via environment variable:

```bash
ASSET_PATH=/wp-content/themes/my-theme/dist/ npm run build
```

Or in package.json:

```json
{
  "10up-toolkit": {
    "publicPath": "/wp-content/themes/my-theme/dist/"
  }
}
```

## Debugging Webpack Config

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const fs = require('fs');

// Log the config to file for inspection
fs.writeFileSync(
    'webpack-config-debug.json',
    JSON.stringify(defaultConfig, null, 2)
);

module.exports = defaultConfig;
```

Or use the analyze flag:

```bash
10up-toolkit build --analyze
```

## Common Webpack Patterns

### Conditional Config

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const { BundleAnalyzerPlugin } = require('webpack-bundle-analyzer');

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        ...(process.env.ANALYZE ? [new BundleAnalyzerPlugin()] : []),
    ],
};
```

### Merge Configs

```javascript
// webpack.config.js
const defaultConfig = require('10up-toolkit/config/webpack.config.js');
const { merge } = require('webpack-merge');
const path = require('path');

const customConfig = {
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'assets'),
        },
    },
};

module.exports = merge(defaultConfig, customConfig);
```
