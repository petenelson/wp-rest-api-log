# WP REST API Log

WordPress plugin that logs REST API requests and responses.

- **Main branch**: `develop` (use for PRs)

## Project Structure

- `wp-rest-api-log.php` — plugin entry point, bootstraps all classes
- `includes/` — core classes (DB, controller, request/response models, filters, settings)
- `admin/` — admin UI classes and partials
- `tests/` — PHPUnit tests

## Running Tests

Tests require a WordPress develop checkout and a MySQL database.

```bash
# Set the path to your wordpress-develop clone
export WP_DEVELOP_DIR="/path/to/wordpress-develop"

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit
```

The test database (`wordpress_test`) must exist before running tests.

## Coding Standards

Uses WordPress Coding Standards (WPCS) via `wp-coding-standards/wpcs`. The
project ruleset lives in `phpcs.xml.dist`, so no arguments are needed.

```bash
# Check
./vendor/bin/phpcs

# Auto-fix what can be fixed
./vendor/bin/phpcbf
```

The codebase is currently clean with zero errors and zero warnings. Deviations
from the standard are annotated inline with `phpcs:ignore` plus a reason.

## CI

CI is being migrated from Travis CI (`.travis.yml`) to GitHub Actions (`.github/workflows/`).
