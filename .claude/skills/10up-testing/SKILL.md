---
name: 10up-testing
description: Write tests for WordPress themes and plugins. Covers PHPUnit for PHP, Jest for JavaScript, and Cypress for E2E testing. Use when adding tests, fixing failing tests, or setting up test infrastructure.
license: MIT
compatibility: PHPUnit 9+, Jest 29+, Cypress 12+, wp-env recommended
globs:
  - tests/**/*
  - "**/*.test.js"
  - "**/*.spec.js"
  - "**/*Test.php"
  - phpunit.xml
  - phpunit.xml.dist
  - jest.config.js
  - cypress.config.js
metadata:
  author: 10up
  version: "1.0"
---

# 10up Testing

This skill guides you through writing tests for WordPress projects using PHPUnit, Jest, and Cypress.

## When to Use

- Adding tests to existing code
- Fixing failing tests
- Setting up test infrastructure
- Writing unit, integration, or E2E tests
- Understanding test patterns for WordPress

## Testing Types

| Type | Tool | What it Tests | Speed |
|------|------|---------------|-------|
| **Unit** | PHPUnit/Jest | Individual functions in isolation | Fast |
| **Integration** | PHPUnit | Functions with WordPress loaded | Medium |
| **E2E** | Cypress | Full user workflows in browser | Slow |

## PHPUnit (PHP Testing)

### Setup

**composer.json:**
```json
{
  "require-dev": {
    "phpunit/phpunit": "^9.0",
    "yoast/phpunit-polyfills": "^1.0",
    "10up/wp-phpunit-helpers": "^1.0"
  },
  "scripts": {
    "test": "phpunit",
    "test:coverage": "phpunit --coverage-html coverage"
  }
}
```

**phpunit.xml.dist:**
```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
>
    <testsuites>
        <testsuite name="unit">
            <directory suffix="Test.php">tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory suffix="Test.php">tests/integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <const name="WP_TESTS_DOMAIN" value="example.org"/>
        <const name="WP_TESTS_EMAIL" value="admin@example.org"/>
        <const name="WP_TESTS_TITLE" value="Test Blog"/>
    </php>
</phpunit>
```

**tests/bootstrap.php:**
```php
<?php
// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Load WordPress test library
$_tests_dir = getenv('WP_TESTS_DIR') ?: '/tmp/wordpress-tests-lib';
require_once $_tests_dir . '/includes/functions.php';

// Load plugin before WordPress
tests_add_filter('muplugins_loaded', function() {
    require dirname(__DIR__) . '/your-plugin.php';
});

// Start WordPress test environment
require $_tests_dir . '/includes/bootstrap.php';
```

### Unit Test Example

**tests/unit/HelpersTest.php:**
```php
<?php
namespace YourPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;
use YourPlugin\Helpers;

class HelpersTest extends TestCase {
    public function test_format_price_returns_formatted_string(): void {
        $result = Helpers::format_price(1999);

        $this->assertEquals('$19.99', $result);
    }

    public function test_format_price_handles_zero(): void {
        $result = Helpers::format_price(0);

        $this->assertEquals('$0.00', $result);
    }

    public function test_sanitize_slug_removes_special_chars(): void {
        $result = Helpers::sanitize_slug('Hello World!');

        $this->assertEquals('hello-world', $result);
    }
}
```

### Integration Test Example

**tests/integration/BlocksTest.php:**
```php
<?php
namespace YourPlugin\Tests\Integration;

use WP_UnitTestCase;

class BlocksTest extends WP_UnitTestCase {
    public function test_hero_block_is_registered(): void {
        $registered = \WP_Block_Type_Registry::get_instance()
            ->get_registered('your-plugin/hero');

        $this->assertNotNull($registered);
        $this->assertEquals('your-plugin/hero', $registered->name);
    }

    public function test_hero_block_renders_correctly(): void {
        $block = [
            'blockName' => 'your-plugin/hero',
            'attrs' => [
                'title' => 'Test Title',
                'subtitle' => 'Test Subtitle',
            ],
        ];

        $output = render_block($block);

        $this->assertStringContainsString('Test Title', $output);
        $this->assertStringContainsString('Test Subtitle', $output);
        $this->assertStringContainsString('wp-block-your-plugin-hero', $output);
    }

    public function test_settings_are_saved_correctly(): void {
        // Arrange
        $option_value = ['feature_enabled' => true];

        // Act
        update_option('your_plugin_settings', $option_value);
        $retrieved = get_option('your_plugin_settings');

        // Assert
        $this->assertEquals($option_value, $retrieved);
    }
}
```

### Testing with Factories

```php
class PostQueriesTest extends WP_UnitTestCase {
    public function test_get_featured_posts_returns_correct_count(): void {
        // Create test posts
        $this->factory->post->create_many(5, [
            'meta_input' => ['is_featured' => '1'],
        ]);

        $result = YourPlugin\Queries::get_featured_posts(3);

        $this->assertCount(3, $result);
    }

    public function test_get_user_posts_filters_by_author(): void {
        $user_id = $this->factory->user->create(['role' => 'author']);
        $this->factory->post->create_many(3, ['post_author' => $user_id]);
        $this->factory->post->create_many(2); // Different author

        $result = YourPlugin\Queries::get_user_posts($user_id);

        $this->assertCount(3, $result);
    }
}
```

## Jest (JavaScript Testing)

### Setup

**package.json:**
```json
{
  "scripts": {
    "test:js": "jest",
    "test:js:watch": "jest --watch",
    "test:js:coverage": "jest --coverage"
  },
  "devDependencies": {
    "@testing-library/react": "^14.0.0",
    "@wordpress/jest-preset-default": "^11.0.0",
    "jest": "^29.0.0"
  }
}
```

**jest.config.js:**
```javascript
module.exports = {
    preset: '@wordpress/jest-preset-default',
    testEnvironment: 'jsdom',
    setupFilesAfterEnv: ['<rootDir>/tests/js/setup.js'],
    testMatch: ['**/tests/js/**/*.test.js'],
    moduleNameMapper: {
        '\\.(css|scss)$': '<rootDir>/tests/js/__mocks__/styleMock.js',
    },
};
```

**tests/js/setup.js:**
```javascript
import '@testing-library/jest-dom';
```

### Component Test Example

**tests/js/components/BlockEdit.test.js:**
```javascript
import { render, screen, fireEvent } from '@testing-library/react';
import { BlockEdit } from '../../../src/blocks/hero/edit';

// Mock WordPress dependencies
jest.mock('@wordpress/block-editor', () => ({
    useBlockProps: () => ({ className: 'wp-block-hero' }),
    RichText: ({ value, onChange, placeholder }) => (
        <input
            value={value}
            onChange={(e) => onChange(e.target.value)}
            placeholder={placeholder}
            data-testid="rich-text"
        />
    ),
    InspectorControls: ({ children }) => <div data-testid="inspector">{children}</div>,
}));

jest.mock('@wordpress/components', () => ({
    PanelBody: ({ children, title }) => <div data-testid="panel">{title}{children}</div>,
    ToggleControl: ({ label, checked, onChange }) => (
        <label>
            <input type="checkbox" checked={checked} onChange={(e) => onChange(e.target.checked)} />
            {label}
        </label>
    ),
}));

describe('Hero BlockEdit', () => {
    const defaultProps = {
        attributes: {
            title: '',
            showSubtitle: true,
        },
        setAttributes: jest.fn(),
    };

    beforeEach(() => {
        jest.clearAllMocks();
    });

    it('renders with default attributes', () => {
        render(<BlockEdit {...defaultProps} />);

        expect(screen.getByTestId('rich-text')).toBeInTheDocument();
    });

    it('calls setAttributes when title changes', () => {
        render(<BlockEdit {...defaultProps} />);

        const input = screen.getByTestId('rich-text');
        fireEvent.change(input, { target: { value: 'New Title' } });

        expect(defaultProps.setAttributes).toHaveBeenCalledWith({ title: 'New Title' });
    });

    it('renders inspector controls', () => {
        render(<BlockEdit {...defaultProps} />);

        expect(screen.getByTestId('inspector')).toBeInTheDocument();
    });
});
```

### Utility Function Test

**tests/js/utils/helpers.test.js:**
```javascript
import { formatDate, slugify, truncate } from '../../../src/utils/helpers';

describe('helpers', () => {
    describe('formatDate', () => {
        it('formats date correctly', () => {
            const date = new Date('2024-01-15');
            expect(formatDate(date)).toBe('January 15, 2024');
        });

        it('returns empty string for invalid date', () => {
            expect(formatDate(null)).toBe('');
        });
    });

    describe('slugify', () => {
        it('converts string to slug', () => {
            expect(slugify('Hello World')).toBe('hello-world');
        });

        it('removes special characters', () => {
            expect(slugify('Hello! World?')).toBe('hello-world');
        });
    });

    describe('truncate', () => {
        it('truncates long strings', () => {
            const result = truncate('This is a long string', 10);
            expect(result).toBe('This is a...');
        });

        it('returns original if shorter than limit', () => {
            const result = truncate('Short', 10);
            expect(result).toBe('Short');
        });
    });
});
```

## Cypress (E2E Testing)

### Setup

**package.json:**
```json
{
  "scripts": {
    "test:e2e": "cypress run",
    "test:e2e:open": "cypress open"
  },
  "devDependencies": {
    "cypress": "^12.0.0",
    "@wordpress/e2e-test-utils-playwright": "^0.10.0"
  }
}
```

**cypress.config.js:**
```javascript
const { defineConfig } = require('cypress');

module.exports = defineConfig({
    e2e: {
        baseUrl: 'http://localhost:8889',
        supportFile: 'tests/e2e/support/e2e.js',
        specPattern: 'tests/e2e/**/*.cy.js',
        viewportWidth: 1280,
        viewportHeight: 720,
    },
    env: {
        wpUsername: 'admin',
        wpPassword: 'password',
    },
});
```

**tests/e2e/support/commands.js:**
```javascript
Cypress.Commands.add('login', () => {
    cy.visit('/wp-login.php');
    cy.get('#user_login').type(Cypress.env('wpUsername'));
    cy.get('#user_pass').type(Cypress.env('wpPassword'));
    cy.get('#wp-submit').click();
    cy.url().should('include', '/wp-admin');
});

Cypress.Commands.add('createPost', (title, content = '') => {
    cy.visit('/wp-admin/post-new.php');
    cy.get('.editor-post-title__input').type(title);
    if (content) {
        cy.get('.block-editor-default-block-appender__content').click();
        cy.get('.block-editor-rich-text__editable').type(content);
    }
});

Cypress.Commands.add('insertBlock', (blockName) => {
    cy.get('.block-editor-inserter__toggle').click();
    cy.get('.block-editor-inserter__search-input').type(blockName);
    cy.get('.block-editor-block-types-list__item').first().click();
});
```

### E2E Test Example

**tests/e2e/blocks/hero.cy.js:**
```javascript
describe('Hero Block', () => {
    beforeEach(() => {
        cy.login();
        cy.createPost('Test Hero Block');
    });

    it('can be inserted into a post', () => {
        cy.insertBlock('Hero');

        cy.get('.wp-block-your-plugin-hero').should('exist');
    });

    it('can edit title', () => {
        cy.insertBlock('Hero');

        cy.get('.wp-block-your-plugin-hero .hero-title')
            .click()
            .type('My Hero Title');

        cy.get('.wp-block-your-plugin-hero .hero-title')
            .should('contain', 'My Hero Title');
    });

    it('saves and displays on frontend', () => {
        cy.insertBlock('Hero');

        cy.get('.wp-block-your-plugin-hero .hero-title')
            .click()
            .type('Frontend Test');

        // Publish post
        cy.get('.editor-post-publish-button').click();
        cy.get('.editor-post-publish-button').click();

        // View on frontend
        cy.get('.post-publish-panel__postpublish-buttons a')
            .contains('View Post')
            .click();

        cy.get('.wp-block-your-plugin-hero')
            .should('contain', 'Frontend Test');
    });
});
```

## Running Tests

**With wp-env:**
```bash
# Start environment
npx wp-env start

# Run PHP tests
npx wp-env run tests-cli ./vendor/bin/phpunit

# Run JS tests
npm run test:js

# Run E2E tests
npm run test:e2e
```

## Verification

After writing tests:

1. Run `composer test` for PHP tests
2. Run `npm run test:js` for JavaScript tests
3. Run `npm run test:e2e` for E2E tests
4. Check code coverage meets requirements
5. Verify all tests pass in CI

## Failure Modes

**PHPUnit: "WordPress not loaded":**
- Check bootstrap.php path
- Verify WP_TESTS_DIR is set
- Use wp-env for consistent environment

**Jest: "Cannot find module":**
- Check moduleNameMapper in config
- Run npm install
- Verify import paths

**Cypress: "Element not found":**
- Increase timeout
- Check selector is correct
- Wait for element to be visible

## Escalation

Ask the user when:
- Complex mocking requirements
- Database state management issues
- CI/CD integration questions
- Performance testing needs
