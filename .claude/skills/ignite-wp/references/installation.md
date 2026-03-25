# Installation Guide

Complete reference for installing Ignite WP plugins.

## Overview

All Ignite plugins depend on **ignite-wp-core**. Always install core first or let the CLI handle dependencies.

## Installation Methods

### 1. Ignite CLI (Recommended)

Interactive command-line tool that handles configuration automatically.

#### Interactive Selection

```bash
npx @10up/ignite-cli install
```

Displays categorized plugin list. Use arrows to navigate, space to select, enter to confirm.

#### Direct Installation

```bash
# Single plugin
npx @10up/ignite-cli install accordion

# Multiple plugins
npx @10up/ignite-cli install accordion carousel icons navigation

# Short slugs work (without ignite-wp- prefix)
npx @10up/ignite-cli install tabs modal stats
```

The CLI automatically:
- Resolves dependencies (includes ignite-wp-core)
- Adds GitHub VCS repositories to composer.json
- Adds packages to require section
- Runs `composer update`

### 2. Download Without Composer

For quick testing without Composer setup:

```bash
# Interactive - select plugin and version
npx @10up/ignite-cli download

# Direct - download latest release
npx @10up/ignite-cli download accordion

# Specific version
npx @10up/ignite-cli download accordion --tag v3.0.0
```

### 3. Manual Composer

Direct control over composer.json:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:10up/ignite-wp-core.git"
    },
    {
      "type": "vcs",
      "url": "git@github.com:10up/ignite-wp-accordion.git"
    }
  ],
  "require": {
    "10up/ignite-wp-core": "^3.0",
    "10up/ignite-wp-accordion": "^3.0"
  }
}
```

Then run: `composer update`

## Ejecting Plugins

For custom modifications, eject from Composer management:

```bash
# Interactive - select from installed plugins
npx @10up/ignite-cli eject

# Direct
npx @10up/ignite-cli eject accordion
```

Ejecting will:
- Remove plugin from composer.json
- Update .gitignore to track plugin directory
- Keep plugin files in place
- Allow custom modifications

**Warning:** Ejected plugins won't receive automatic Composer updates.

## GitHub Authentication

### For Composer (Private Repos)

#### Method 1: Global Auth File (Recommended)

Create/edit `~/.composer/auth.json`:

```json
{
    "github-oauth": {
        "github.com": "YOUR_GITHUB_TOKEN_HERE"
    }
}
```

#### Method 2: Composer Config Command

```bash
# Global (recommended for local dev)
composer config --global github-oauth.github.com YOUR_GITHUB_TOKEN

# Project-specific
composer config github-oauth.github.com YOUR_GITHUB_TOKEN
```

#### Method 3: Environment Variable

```bash
# Current session
export COMPOSER_AUTH='{"github-oauth": {"github.com": "YOUR_TOKEN"}}'

# Inline with command
COMPOSER_AUTH='{"github-oauth": {"github.com": "YOUR_TOKEN"}}' composer install
```

### Creating GitHub Tokens

#### Fine-Grained Token (Recommended)

1. Visit: `https://github.com/settings/personal-access-tokens/new`
2. Token name: "Ignite WP Composer Access - [Project]"
3. Expiration: 365 days
4. Resource owner: Select "10up"
5. Repository access: "Only select repositories"
6. Select: `10up/ignite-wp-core` + any plugins needed
7. Permissions: Contents → Read-only
8. Generate and copy token

**Note:** Fine-grained tokens require 10up org admin approval.

#### Classic Token (Alternative)

1. Visit: `https://github.com/settings/tokens/new`
2. Note: "Ignite WP Composer"
3. Expiration: 90 days
4. Select `repo` scope
5. Generate and copy token

### For CLI Downloads

Set environment variable to avoid rate limits:

```bash
export GITHUB_TOKEN="your_token"
npx @10up/ignite-cli download accordion
```

## CI/CD Configuration

### GitHub Actions

```yaml
steps:
  - uses: actions/checkout@v4

  - name: Setup PHP
    uses: shivammathur/setup-php@v2
    with:
      php-version: '8.2'

  - name: Setup Composer authentication
    run: composer config --global github-oauth.github.com ${{ secrets.GITHUB_TOKEN }}

  - name: Install Composer dependencies
    run: composer install --no-interaction --prefer-dist
```

### CircleCI

```yaml
jobs:
  build:
    docker:
      - image: cimg/php:8.2
    steps:
      - checkout
      - run:
          name: Setup Composer authentication
          command: composer config --global github-oauth.github.com $GITHUB_TOKEN
      - run:
          name: Install dependencies
          command: composer install --no-interaction
```

### GitLab CI

```yaml
build:
  image: php:8.2
  before_script:
    - composer config --global github-oauth.github.com $GITHUB_TOKEN
  script:
    - composer install --no-interaction
```

## Project Offboarding

When ending a client relationship, remove Composer dependencies while keeping code:

```bash
npx @10up/ignite-cli offboard
```

This will:
- Remove all Ignite plugins from composer.json
- Remove all Ignite VCS repositories
- Update .gitignore to track plugin directories
- Include dist/ and vendor/ so built assets are committed
- Keep all plugin files in place

After offboarding:
1. Review changes to composer.json and .gitignore
2. Run `git add -A` to stage plugin files
3. Commit: `git commit -m "Offboard Ignite plugins for client handoff"`
4. Push to repository

## Troubleshooting

### Could Not Authenticate

- Token expired - generate new token
- Wrong permissions - verify Contents read access
- Token not configured - check `composer config` or `COMPOSER_AUTH`
- Typo in token - verify no extra spaces

### Repository Not Found

- For fine-grained tokens: verify specific repository selected
- Check resource owner is "10up"
- For classic tokens: verify `repo` scope

### Rate Limiting

- Use authenticated requests (5,000/hour vs 60/hour unauthenticated)
- Set `GITHUB_TOKEN` environment variable for CLI

### Composer Update Fails

- Verify authentication: `composer config --global github-oauth.github.com`
- Clear cache: `composer clear-cache`
- Check PHP/WordPress version requirements
- Ensure ignite-wp-core is included

### Debug Authentication

```bash
# Check configured token
composer config --global github-oauth.github.com

# Test with verbose output
composer update --dry-run -vvv
```
