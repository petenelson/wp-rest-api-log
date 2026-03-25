---
paths:
  - "*.php"
---

# PHP Coding Rules

* All PHP must follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
* Use short array syntax `[]` instead of `array()`.
* Every PHP class will start with `WP_REST_API_Log` without a namespace.
* PHP lives in `includes/`
* When reading `$_POST`, `$_GET`, or `$_REQUEST` variables, always use `filter_var_array()` with appropriate filter constants and validation rules.
* Refer to [10up's PHP Engineering Best Practices](https://10up.github.io/Engineering-Best-Practices/php/) for any other PHP-related decisions.
* New classes will be use the singleton pattern, with an `instance()` method to get the instance.
* Decode JSON to arrays, not objects by default.
