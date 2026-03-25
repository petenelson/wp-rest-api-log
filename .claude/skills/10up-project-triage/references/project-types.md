# WordPress Project Types Reference

Guide to identifying and understanding different WordPress project structures.

## Project Type Detection

### Block Theme

**Identifying Files:**
- `theme.json` (required)
- `style.css` with `Theme Name:` header
- `/templates/*.html` directory
- `/parts/*.html` directory
- `/patterns/*.php` directory

**Structure:**
```
theme-name/
├── theme.json           # Theme configuration
├── style.css            # Theme metadata
├── functions.php        # PHP functions (optional)
├── templates/           # Block templates
│   ├── index.html
│   ├── single.html
│   └── archive.html
├── parts/               # Template parts
│   ├── header.html
│   └── footer.html
├── patterns/            # Block patterns
│   └── hero.php
├── blocks/              # Custom blocks
│   └── hero/
└── src/                 # Source files
```

**Key Characteristics:**
- HTML-based templates using block markup
- Theme styles defined in `theme.json`
- Full Site Editing (FSE) compatible
- Minimal PHP required

### Classic Theme

**Identifying Files:**
- `style.css` with `Theme Name:` header
- `index.php` (required)
- Template files: `single.php`, `archive.php`, etc.
- NO `theme.json` or limited use

**Structure:**
```
theme-name/
├── style.css
├── functions.php
├── index.php
├── header.php
├── footer.php
├── sidebar.php
├── single.php
├── page.php
├── archive.php
├── template-parts/
└── inc/
```

**Key Characteristics:**
- PHP-based templates
- Uses template hierarchy
- WordPress Loop for content
- May have blocks but not block-first

### Plugin

**Identifying Files:**
- Main PHP file with `Plugin Name:` header
- `composer.json` (usually)
- `package.json` (if has JS)

**Structure:**
```
plugin-name/
├── plugin-name.php      # Main plugin file
├── composer.json
├── package.json
├── src/                 # PHP classes
│   └── PluginCore.php
├── includes/            # Legacy PHP includes
├── blocks/              # Custom blocks
├── admin/               # Admin-specific code
├── public/              # Frontend code
├── languages/
└── dist/                # Compiled assets
```

**Key Characteristics:**
- Self-contained functionality
- Hooks into WordPress via actions/filters
- May register custom post types, blocks, etc.

### Must-Use Plugin (MU-Plugin)

**Identifying Files:**
- Located in `wp-content/mu-plugins/`
- No activation required
- Single file or folder with loader

**Structure:**
```
mu-plugins/
├── load-custom-plugins.php    # Loader
└── custom-plugin/
    ├── custom-plugin.php
    └── src/
```

**Key Characteristics:**
- Loads automatically
- Cannot be deactivated via admin
- Loads before regular plugins
- Used for critical functionality

### Monorepo

**Identifying Files:**
- `package.json` with `workspaces` field
- Multiple themes/plugins in subdirectories
- Root-level tooling configuration

**Structure:**
```
project-name/
├── package.json         # With workspaces
├── composer.json
├── .wp-env.json
├── themes/
│   └── client-theme/
├── plugins/
│   ├── client-plugin/
│   └── another-plugin/
├── mu-plugins/
│   └── site-config/
└── config/
```

**Key Characteristics:**
- Single repo for multiple packages
- Shared tooling and configuration
- Usually client-specific project
- Managed dependencies across packages

### 10up Scaffold

**Identifying Files:**
- `.tenup.yml` configuration
- Specific directory structure
- 10up-toolkit in package.json
- 10up/wp-framework in composer.json

**Detection:**
```bash
# Check for scaffold markers
test -f .tenup.yml && echo "10up Scaffold detected"
grep -q "10up-toolkit" package.json && echo "Has 10up-toolkit"
grep -q "10up/wp-framework" composer.json && echo "Has 10up framework"
```

## Detection Logic

### Project Kind Determination

```javascript
function detectProjectKind(files) {
    // Check for theme.json (block theme)
    if (files.includes('theme.json') && files.includes('templates/index.html')) {
        return 'block-theme';
    }

    // Check for classic theme
    if (files.includes('style.css') && files.includes('index.php')) {
        const styleCss = readFile('style.css');
        if (styleCss.includes('Theme Name:')) {
            return 'theme';
        }
    }

    // Check for plugin
    const phpFiles = files.filter(f => f.endsWith('.php'));
    for (const file of phpFiles) {
        const content = readFile(file);
        if (content.includes('Plugin Name:')) {
            return 'plugin';
        }
    }

    // Check for monorepo
    const packageJson = readJson('package.json');
    if (packageJson?.workspaces) {
        return 'monorepo';
    }

    return 'unknown';
}
```

### 10up Project Detection

```javascript
function is10upProject(files) {
    // Check package.json for 10up-toolkit
    const packageJson = readJson('package.json');
    if (packageJson?.devDependencies?.['10up-toolkit']) {
        return true;
    }

    // Check composer.json for 10up/wp-framework
    const composerJson = readJson('composer.json');
    if (composerJson?.require?.['10up/wp-framework']) {
        return true;
    }

    // Check for .tenup.yml
    if (files.includes('.tenup.yml')) {
        return true;
    }

    return false;
}
```

## Common Patterns

### Hybrid Projects

Some projects combine multiple types:

**Theme with Companion Plugin:**
```
project/
├── themes/
│   └── main-theme/
└── plugins/
    └── theme-functionality/
```

**Plugin with Blocks:**
```
plugin-name/
├── plugin-name.php
├── blocks/
│   ├── block-one/
│   └── block-two/
└── src/
```

### Version Requirements Detection

```javascript
function detectVersions(files) {
    const versions = {};

    // Check plugin/theme header
    const mainFile = findMainFile(files);
    if (mainFile) {
        const content = readFile(mainFile);
        const wpMatch = content.match(/Requires at least:\s*([0-9.]+)/);
        const phpMatch = content.match(/Requires PHP:\s*([0-9.]+)/);

        if (wpMatch) versions.wordpress = wpMatch[1] + '+';
        if (phpMatch) versions.php = phpMatch[1] + '+';
    }

    // Check composer.json
    const composer = readJson('composer.json');
    if (composer?.require?.php) {
        versions.php = composer.require.php;
    }

    // Check package.json engines
    const packageJson = readJson('package.json');
    if (packageJson?.engines?.node) {
        versions.node = packageJson.engines.node;
    }

    return versions;
}
```

## Recommended Actions by Project Type

### Block Theme

1. Check `theme.json` for design tokens
2. Review templates in `/templates/`
3. Examine patterns in `/patterns/`
4. Build assets: `npm run build`

### Classic Theme

1. Review `functions.php` for customizations
2. Check template hierarchy usage
3. Look for custom page templates
4. Verify hook usage

### Plugin

1. Find main plugin file
2. Check for ModuleInterface usage
3. Review hook registrations
4. Examine block registrations

### Monorepo

1. Identify all workspaces
2. Check root vs package configs
3. Understand build orchestration
4. Review shared dependencies
