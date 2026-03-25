#!/usr/bin/env node

/**
 * 10up Project Triage Script
 *
 * Detects WordPress project structure, tooling, and 10up conventions.
 * Outputs a JSON report for AI assistants to use in routing decisions.
 *
 * Usage: node detect_project.mjs [path]
 */

import { readFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import { join, resolve, basename } from 'node:path';

const cwd = process.argv[2] ? resolve(process.argv[2]) : process.cwd();

/**
 * Safely read and parse JSON file
 */
function readJson(filePath) {
    try {
        if (!existsSync(filePath)) return null;
        return JSON.parse(readFileSync(filePath, 'utf-8'));
    } catch {
        return null;
    }
}

/**
 * Safely read file as text
 */
function readText(filePath) {
    try {
        if (!existsSync(filePath)) return null;
        return readFileSync(filePath, 'utf-8');
    } catch {
        return null;
    }
}

/**
 * Check if directory exists and has files
 */
function hasDirectory(dirPath) {
    try {
        return existsSync(dirPath) && statSync(dirPath).isDirectory();
    } catch {
        return false;
    }
}

/**
 * Find files matching a pattern in a directory
 */
function findFiles(dirPath, pattern) {
    try {
        if (!hasDirectory(dirPath)) return [];
        return readdirSync(dirPath).filter((f) => pattern.test(f));
    } catch {
        return [];
    }
}

/**
 * Extract header value from PHP file (Plugin Name, Theme Name, etc.)
 */
function extractPhpHeader(content, headerName) {
    if (!content) return null;
    const regex = new RegExp(`${headerName}:\\s*(.+)`, 'i');
    const match = content.match(regex);
    return match ? match[1].trim() : null;
}

/**
 * Detect project signals
 */
function detectSignals() {
    const signals = {
        // Theme signals
        hasThemeJson: existsSync(join(cwd, 'theme.json')),
        hasStyleCss: existsSync(join(cwd, 'style.css')),
        hasTemplatesDir: hasDirectory(join(cwd, 'templates')),
        hasPartsDir: hasDirectory(join(cwd, 'parts')),
        hasFunctionsPhp: existsSync(join(cwd, 'functions.php')),

        // Plugin signals
        hasPluginHeader: false,
        pluginFile: null,

        // Block signals
        hasBlocksDir: hasDirectory(join(cwd, 'blocks')),
        hasIncludesBlocksDir: hasDirectory(join(cwd, 'includes', 'blocks')),
        hasPatternsDir: hasDirectory(join(cwd, 'patterns')),

        // Source structure
        hasSrcDir: hasDirectory(join(cwd, 'src')),
        hasIncludesDir: hasDirectory(join(cwd, 'includes')),

        // Config files
        hasPackageJson: existsSync(join(cwd, 'package.json')),
        hasComposerJson: existsSync(join(cwd, 'composer.json')),
        hasWpEnvJson: existsSync(join(cwd, '.wp-env.json')),
    };

    // Check for plugin header in PHP files at root
    const phpFiles = findFiles(cwd, /\.php$/);
    for (const file of phpFiles) {
        const content = readText(join(cwd, file));
        if (content && extractPhpHeader(content, 'Plugin Name')) {
            signals.hasPluginHeader = true;
            signals.pluginFile = file;
            break;
        }
    }

    // Check for theme name in style.css
    if (signals.hasStyleCss) {
        const styleCss = readText(join(cwd, 'style.css'));
        signals.themeName = extractPhpHeader(styleCss, 'Theme Name');
    }

    // Check for HTML templates (block theme indicator)
    if (signals.hasTemplatesDir) {
        const htmlTemplates = findFiles(join(cwd, 'templates'), /\.html$/);
        signals.hasHtmlTemplates = htmlTemplates.length > 0;
        signals.templateCount = htmlTemplates.length;
    }

    return signals;
}

/**
 * Detect 10up-specific patterns
 */
function detect10upPatterns() {
    const patterns = {
        is10upProject: false,
        has10upToolkit: false,
        has10upFramework: false,
        has10upScaffold: false,
        hasModulePattern: false,
    };

    // Check package.json for 10up-toolkit
    const packageJson = readJson(join(cwd, 'package.json'));
    if (packageJson) {
        const deps = {
            ...packageJson.dependencies,
            ...packageJson.devDependencies,
        };
        patterns.has10upToolkit = '10up-toolkit' in deps;
        patterns.has10upBlockComponents = '@10up/block-components' in deps;
    }

    // Check composer.json for 10up/wp-framework
    const composerJson = readJson(join(cwd, 'composer.json'));
    if (composerJson) {
        const require = {
            ...composerJson.require,
            ...composerJson['require-dev'],
        };
        patterns.has10upFramework = '10up/wp-framework' in require;
    }

    // Check for 10up scaffold indicators
    patterns.has10upScaffold =
        existsSync(join(cwd, '.tenup.yml')) ||
        existsSync(join(cwd, '10up-toolkit.config.js')) ||
        existsSync(join(cwd, '10up-toolkit.config.mjs'));

    // Check for ModuleInterface pattern in PHP files
    if (hasDirectory(join(cwd, 'src'))) {
        const srcFiles = findFiles(join(cwd, 'src'), /\.php$/);
        for (const file of srcFiles.slice(0, 5)) {
            // Check first 5 files
            const content = readText(join(cwd, 'src', file));
            if (content && content.includes('ModuleInterface')) {
                patterns.hasModulePattern = true;
                break;
            }
        }
    }

    patterns.is10upProject =
        patterns.has10upToolkit ||
        patterns.has10upFramework ||
        patterns.has10upScaffold;

    return patterns;
}

/**
 * Detect tooling
 */
function detectTooling() {
    const tooling = {
        buildTool: 'none',
        packageManager: 'none',
        phpDependencies: 'none',
        localEnv: 'none',
    };

    const packageJson = readJson(join(cwd, 'package.json'));
    if (packageJson) {
        const deps = {
            ...packageJson.dependencies,
            ...packageJson.devDependencies,
        };

        // Detect build tool
        if ('10up-toolkit' in deps) {
            tooling.buildTool = '10up-toolkit';
        } else if ('@wordpress/scripts' in deps) {
            tooling.buildTool = 'wp-scripts';
        } else if ('webpack' in deps) {
            tooling.buildTool = 'webpack';
        }

        // Detect package manager
        if (existsSync(join(cwd, 'pnpm-lock.yaml'))) {
            tooling.packageManager = 'pnpm';
        } else if (existsSync(join(cwd, 'yarn.lock'))) {
            tooling.packageManager = 'yarn';
        } else if (existsSync(join(cwd, 'package-lock.json'))) {
            tooling.packageManager = 'npm';
        }
    }

    // Detect PHP dependencies
    if (existsSync(join(cwd, 'composer.json'))) {
        tooling.phpDependencies = 'composer';
    }

    // Detect local environment
    if (existsSync(join(cwd, '.wp-env.json'))) {
        tooling.localEnv = 'wp-env';
    } else if (
        existsSync(join(cwd, 'docker-compose.yml')) ||
        existsSync(join(cwd, '.ddev'))
    ) {
        tooling.localEnv = 'docker';
    }

    return tooling;
}

/**
 * Detect testing frameworks
 */
function detectTesting() {
    const testing = {
        phpunit: existsSync(join(cwd, 'phpunit.xml')) || existsSync(join(cwd, 'phpunit.xml.dist')),
        jest:
            existsSync(join(cwd, 'jest.config.js')) ||
            existsSync(join(cwd, 'jest.config.mjs')) ||
            existsSync(join(cwd, 'jest.config.ts')),
        cypress:
            existsSync(join(cwd, 'cypress.config.js')) ||
            existsSync(join(cwd, 'cypress.config.ts')),
        playwright: existsSync(join(cwd, 'playwright.config.js')) || existsSync(join(cwd, 'playwright.config.ts')),
    };

    // Check package.json for test scripts
    const packageJson = readJson(join(cwd, 'package.json'));
    if (packageJson?.scripts) {
        testing.hasTestScript = 'test' in packageJson.scripts;
    }

    return testing;
}

/**
 * Extract version requirements
 */
function detectVersions() {
    const versions = {
        wordpress: null,
        php: null,
        node: null,
    };

    // From style.css (theme)
    const styleCss = readText(join(cwd, 'style.css'));
    if (styleCss) {
        versions.wordpress = extractPhpHeader(styleCss, 'Requires at least');
        versions.php = extractPhpHeader(styleCss, 'Requires PHP');
    }

    // From main plugin file
    const signals = detectSignals();
    if (signals.pluginFile) {
        const pluginContent = readText(join(cwd, signals.pluginFile));
        if (pluginContent) {
            versions.wordpress =
                versions.wordpress || extractPhpHeader(pluginContent, 'Requires at least');
            versions.php = versions.php || extractPhpHeader(pluginContent, 'Requires PHP');
        }
    }

    // From composer.json
    const composerJson = readJson(join(cwd, 'composer.json'));
    if (composerJson?.require?.php) {
        versions.php = versions.php || composerJson.require.php;
    }

    // From package.json
    const packageJson = readJson(join(cwd, 'package.json'));
    if (packageJson?.engines?.node) {
        versions.node = packageJson.engines.node;
    }

    return versions;
}

/**
 * Detect common paths
 */
function detectPaths() {
    const paths = {};

    if (hasDirectory(join(cwd, 'blocks'))) {
        paths.blocks = './blocks';
    } else if (hasDirectory(join(cwd, 'includes', 'blocks'))) {
        paths.blocks = './includes/blocks';
    }

    if (hasDirectory(join(cwd, 'patterns'))) {
        paths.patterns = './patterns';
    }

    if (hasDirectory(join(cwd, 'templates'))) {
        paths.templates = './templates';
    }

    if (hasDirectory(join(cwd, 'src'))) {
        paths.src = './src';
    } else if (hasDirectory(join(cwd, 'includes'))) {
        paths.src = './includes';
    }

    if (hasDirectory(join(cwd, 'dist'))) {
        paths.dist = './dist';
    } else if (hasDirectory(join(cwd, 'build'))) {
        paths.dist = './build';
    }

    return paths;
}

/**
 * Determine project kind
 */
function determineProjectKind(signals, tenupPatterns) {
    // Check for monorepo first
    const packageJson = readJson(join(cwd, 'package.json'));
    if (packageJson?.workspaces) {
        return 'monorepo';
    }

    // Block theme: has theme.json + HTML templates
    if (signals.hasThemeJson && signals.hasHtmlTemplates) {
        return 'block-theme';
    }

    // Classic theme: has style.css with Theme Name but no HTML templates
    if (signals.themeName && !signals.hasHtmlTemplates) {
        return 'classic-theme';
    }

    // Plugin: has plugin header
    if (signals.hasPluginHeader) {
        return 'plugin';
    }

    // 10up scaffold: has 10up patterns but unclear if theme or plugin
    if (tenupPatterns.has10upScaffold) {
        return 'scaffold';
    }

    return 'unknown';
}

/**
 * Main execution
 */
function main() {
    const signals = detectSignals();
    const tenupPatterns = detect10upPatterns();
    const tooling = detectTooling();
    const testing = detectTesting();
    const versions = detectVersions();
    const paths = detectPaths();
    const projectKind = determineProjectKind(signals, tenupPatterns);

    const report = {
        projectKind,
        is10upProject: tenupPatterns.is10upProject,
        signals,
        tenup: tenupPatterns,
        tooling,
        testing,
        versions,
        paths,
        cwd,
    };

    console.log(JSON.stringify(report, null, 2));
}

main();
