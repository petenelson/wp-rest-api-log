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

Uses WordPress Coding Standards (WPCS) via `wp-coding-standards/wpcs`.

```bash
./vendor/bin/phpcs --standard=WordPress .
```

## CI

CI is being migrated from Travis CI (`.travis.yml`) to GitHub Actions (`.github/workflows/`).
