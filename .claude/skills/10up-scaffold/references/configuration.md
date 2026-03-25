# Scaffold Configuration Reference

Guide to configuring 10up WordPress Scaffold projects.

## Package Configuration

### package.json

```json
{
  "name": "theme-name",
  "version": "1.0.0",
  "description": "Theme description",
  "main": "index.js",
  "scripts": {
    "start": "10up-toolkit start",
    "build": "10up-toolkit build",
    "watch": "10up-toolkit watch",
    "format:js": "10up-toolkit format:js",
    "lint:js": "10up-toolkit lint:js",
    "lint:js:fix": "10up-toolkit lint:js:fix",
    "lint:style": "10up-toolkit lint:style",
    "lint:style:fix": "10up-toolkit lint:style:fix",
    "lint": "npm run lint:js && npm run lint:style",
    "test": "10up-toolkit test:unit",
    "scaffold:block": "10up-toolkit create-block"
  },
  "devDependencies": {
    "10up-toolkit": "^6.0.0"
  },
  "dependencies": {
    "@wordpress/block-editor": "^12.0.0",
    "@wordpress/blocks": "^12.0.0",
    "@wordpress/components": "^25.0.0",
    "@wordpress/i18n": "^4.0.0"
  },
  "engines": {
    "node": ">=18.0.0"
  }
}
```

### Key Scripts

| Script | Purpose |
|--------|---------|
| `start` | Development mode with hot reload |
| `build` | Production build |
| `watch` | Watch for changes without hot reload |
| `lint` | Run all linters |
| `lint:js` | Lint JavaScript |
| `lint:style` | Lint CSS/SCSS |
| `test` | Run unit tests |
| `scaffold:block` | Create new block |

## Build Configuration

### 10up-toolkit.config.js

```javascript
module.exports = {
    // Entry points
    entry: {
        admin: './assets/js/admin/admin.js',
        frontend: './assets/js/frontend/frontend.js',
        'admin-style': './assets/css/admin/admin-style.css',
        'frontend-style': './assets/css/frontend/frontend-style.css',
        'shared-style': './assets/css/shared/shared-style.css',
    },

    // File path configurations
    filenames: {
        js: '[name].js',
        css: '[name].css',
    },

    // Output paths
    paths: {
        blocksDir: './includes/blocks/',
        srcDir: './assets/',
        distDir: './dist/',
    },

    // WordPress externals (don't bundle these)
    useBlockAssets: true,

    // Development server
    devServer: {
        hot: true,
    },

    // Source maps
    devtool: process.env.NODE_ENV === 'development' ? 'source-map' : false,

    // Custom webpack configuration
    webpack: (config) => {
        // Modify webpack config here
        return config;
    },
};
```

### Multiple Entry Points

```javascript
module.exports = {
    entry: {
        // Frontend assets
        frontend: './assets/js/frontend/index.js',
        'frontend-style': './assets/css/frontend/style.css',

        // Admin assets
        admin: './assets/js/admin/index.js',
        'admin-style': './assets/css/admin/style.css',

        // Editor assets
        editor: './assets/js/editor/index.js',
        'editor-style': './assets/css/editor/style.css',

        // Specific feature bundles
        'single-post': './assets/js/frontend/single-post.js',
        'archive': './assets/js/frontend/archive.js',
    },
};
```

### Block-Specific Configuration

```javascript
module.exports = {
    // Auto-discover blocks in directory
    paths: {
        blocksDir: './includes/blocks/',
    },

    // Or specify blocks explicitly
    blocks: [
        './includes/blocks/hero',
        './includes/blocks/card',
        './includes/blocks/cta',
    ],

    // Block assets configuration
    useBlockAssets: true,
};
```

## Composer Configuration

### composer.json

```json
{
  "name": "10up/theme-name",
  "description": "Theme description",
  "type": "wordpress-theme",
  "license": "GPL-2.0-or-later",
  "require": {
    "php": ">=8.0"
  },
  "require-dev": {
    "10up/phpcs-composer": "^2.0",
    "phpunit/phpunit": "^9.5",
    "yoast/phpunit-polyfills": "^1.0",
    "wp-coding-standards/wpcs": "^3.0"
  },
  "autoload": {
    "psr-4": {
      "ThemeName\\": "includes/classes/"
    },
    "files": [
      "includes/core.php"
    ]
  },
  "scripts": {
    "lint": "phpcs",
    "lint:fix": "phpcbf",
    "test": "phpunit"
  },
  "config": {
    "allow-plugins": {
      "10up/phpcs-composer": true,
      "dealerdirect/phpcodesniffer-composer-installer": true
    }
  }
}
```

### PHPCS Configuration (phpcs.xml)

```xml
<?xml version="1.0"?>
<ruleset name="Theme Coding Standards">
    <description>PHP Coding Standards for the theme</description>

    <!-- What to scan -->
    <file>.</file>

    <!-- Exclude paths -->
    <exclude-pattern>/vendor/*</exclude-pattern>
    <exclude-pattern>/node_modules/*</exclude-pattern>
    <exclude-pattern>/dist/*</exclude-pattern>
    <exclude-pattern>/tests/*</exclude-pattern>

    <!-- Show progress -->
    <arg value="sp"/>
    <arg name="colors"/>

    <!-- Use 10up standards -->
    <rule ref="10up-Default"/>

    <!-- WordPress configuration -->
    <config name="minimum_supported_wp_version" value="6.4"/>

    <!-- Text domain -->
    <rule ref="WordPress.WP.I18n">
        <properties>
            <property name="text_domain" type="array">
                <element value="theme-name"/>
            </property>
        </properties>
    </rule>

    <!-- Prefixes -->
    <rule ref="WordPress.NamingConventions.PrefixAllGlobals">
        <properties>
            <property name="prefixes" type="array">
                <element value="theme_name"/>
                <element value="ThemeName"/>
            </property>
        </properties>
    </rule>
</ruleset>
```

## ESLint Configuration

### .eslintrc.js

```javascript
module.exports = {
    extends: ['@10up/eslint-config'],
    globals: {
        wp: 'readonly',
        jQuery: 'readonly',
    },
    rules: {
        // Custom rule overrides
        'no-console': 'warn',
    },
    overrides: [
        {
            files: ['*.js', '*.jsx'],
            rules: {
                // JS-specific rules
            },
        },
    ],
};
```

## StyleLint Configuration

### .stylelintrc.js

```javascript
module.exports = {
    extends: ['@10up/stylelint-config'],
    rules: {
        // Custom rule overrides
        'selector-class-pattern': null,
    },
};
```

## Environment Configuration

### .wp-env.json (Local Development)

```json
{
  "core": "WordPress/WordPress#6.4",
  "phpVersion": "8.0",
  "plugins": [
    "."
  ],
  "themes": [
    "./themes/theme-name"
  ],
  "mappings": {
    "wp-content/mu-plugins": "./mu-plugins"
  },
  "config": {
    "WP_DEBUG": true,
    "WP_DEBUG_LOG": true,
    "SCRIPT_DEBUG": true
  }
}
```

### .nvmrc

```
18
```

### .editorconfig

```ini
root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = tab
insert_final_newline = true
trim_trailing_whitespace = true

[*.{json,yml,yaml}]
indent_size = 2
indent_style = space

[*.md]
trim_trailing_whitespace = false
```

## Scaffold Templates Configuration

### .scaffold/config.json

```json
{
  "templates": {
    "block": {
      "path": ".scaffold/templates/block",
      "files": [
        "block.json.mustache",
        "edit.js.mustache",
        "index.js.mustache",
        "markup.php.mustache",
        "save.js.mustache",
        "style.css.mustache"
      ]
    }
  },
  "defaults": {
    "namespace": "theme-name",
    "textdomain": "theme-name",
    "category": "theme",
    "isDynamic": true
  },
  "prompts": {
    "blockName": {
      "message": "Block name (lowercase, hyphens):",
      "validate": "^[a-z][a-z0-9-]*$"
    },
    "blockTitle": {
      "message": "Block title:"
    },
    "blockDescription": {
      "message": "Block description:"
    }
  }
}
```

## Directory Structure Configuration

### Recommended Structure

```
project/
├── .scaffold/
│   ├── config.json
│   └── templates/
│       └── block/
├── assets/
│   ├── css/
│   │   ├── admin/
│   │   ├── frontend/
│   │   └── shared/
│   ├── js/
│   │   ├── admin/
│   │   └── frontend/
│   └── images/
├── includes/
│   ├── blocks/
│   ├── classes/
│   └── core.php
├── dist/
├── languages/
├── patterns/
├── parts/
├── templates/
├── .editorconfig
├── .eslintrc.js
├── .nvmrc
├── .stylelintrc.js
├── .wp-env.json
├── 10up-toolkit.config.js
├── composer.json
├── functions.php
├── package.json
├── phpcs.xml
├── style.css
└── theme.json
```

## Git Configuration

### .gitignore

```gitignore
# Dependencies
node_modules/
vendor/

# Build output
dist/

# Environment
.wp-env/
*.local

# IDE
.idea/
.vscode/
*.sublime-*

# System
.DS_Store
Thumbs.db

# Logs
*.log
debug.log

# Package manager locks (choose one)
# package-lock.json
# yarn.lock
```

### .gitattributes

```gitattributes
# Auto detect text files
* text=auto

# Ensure shell scripts use LF
*.sh text eol=lf

# Ensure batch scripts use CRLF
*.bat text eol=crlf

# Mark binary files
*.png binary
*.jpg binary
*.gif binary
*.ico binary
*.woff binary
*.woff2 binary
*.ttf binary
*.eot binary
```
