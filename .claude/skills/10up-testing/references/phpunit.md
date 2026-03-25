# PHPUnit Reference for WordPress

Comprehensive guide to PHPUnit testing patterns in WordPress projects.

## Test Base Classes

### Unit Tests (No WordPress)

```php
<?php
namespace YourPlugin\Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase {
    public function test_example(): void {
        $this->assertTrue(true);
    }
}
```

### Integration Tests (With WordPress)

```php
<?php
namespace YourPlugin\Tests\Integration;

use WP_UnitTestCase;

class MyIntegrationTest extends WP_UnitTestCase {
    public function test_wordpress_is_loaded(): void {
        $this->assertTrue(function_exists('wp_insert_post'));
    }
}
```

## Test Lifecycle

### setUp and tearDown

```php
class MyTest extends WP_UnitTestCase {
    private $test_post_id;

    public function set_up(): void {
        parent::set_up();

        // Runs before each test
        $this->test_post_id = $this->factory->post->create();
    }

    public function tear_down(): void {
        // Runs after each test
        wp_delete_post($this->test_post_id, true);

        parent::tear_down();
    }

    public function test_something(): void {
        // $this->test_post_id is available here
    }
}
```

### setUpBeforeClass (Once per Class)

```php
class MyTest extends WP_UnitTestCase {
    private static $shared_resource;

    public static function set_up_before_class(): void {
        parent::set_up_before_class();

        // Runs once before all tests in class
        self::$shared_resource = expensive_setup();
    }

    public static function tear_down_after_class(): void {
        // Runs once after all tests in class
        cleanup(self::$shared_resource);

        parent::tear_down_after_class();
    }
}
```

## WordPress Test Factories

### Post Factory

```php
// Create single post
$post_id = $this->factory->post->create();

// Create with attributes
$post_id = $this->factory->post->create([
    'post_title'   => 'Test Post',
    'post_content' => 'Content here',
    'post_status'  => 'publish',
    'post_type'    => 'page',
    'post_author'  => 1,
    'meta_input'   => [
        'custom_key' => 'custom_value',
    ],
]);

// Create multiple posts
$post_ids = $this->factory->post->create_many(5);

// Create and get post object
$post = $this->factory->post->create_and_get();
```

### User Factory

```php
// Create user
$user_id = $this->factory->user->create();

// Create with role
$user_id = $this->factory->user->create([
    'role' => 'editor',
    'user_login' => 'testuser',
    'user_email' => 'test@example.com',
]);

// Create admin
$admin_id = $this->factory->user->create([
    'role' => 'administrator',
]);

// Get user object
$user = $this->factory->user->create_and_get();
```

### Term Factory

```php
// Create term
$term_id = $this->factory->term->create([
    'taxonomy' => 'category',
    'name' => 'Test Category',
]);

// Create with parent
$child_id = $this->factory->term->create([
    'taxonomy' => 'category',
    'parent' => $parent_id,
]);

// Create and assign to post
$term_id = $this->factory->term->create(['taxonomy' => 'post_tag']);
wp_set_post_terms($post_id, [$term_id], 'post_tag');
```

### Comment Factory

```php
// Create comment
$comment_id = $this->factory->comment->create([
    'comment_post_ID' => $post_id,
    'comment_content' => 'Test comment',
]);

// Create multiple comments
$comment_ids = $this->factory->comment->create_many(10, [
    'comment_post_ID' => $post_id,
]);
```

### Attachment Factory

```php
// Create attachment
$attachment_id = $this->factory->attachment->create([
    'post_title' => 'Test Image',
    'post_mime_type' => 'image/jpeg',
]);

// With file
$attachment_id = $this->factory->attachment->create_upload_object(
    DIR_TESTDATA . '/images/test-image.jpg',
    $parent_post_id
);
```

## Assertions

### WordPress-Specific Assertions

```php
// Assert WP_Error
$result = some_function();
$this->assertWPError($result);
$this->assertWPError($result, 'error_code');

// Assert not WP_Error
$this->assertNotWPError($result);

// Assert query result count
$this->assertQueryCount(5, $query_result);

// Assert post exists
$this->assertPostExists($post_id);
```

### Common PHPUnit Assertions

```php
// Equality
$this->assertEquals($expected, $actual);
$this->assertSame($expected, $actual); // Type-strict
$this->assertNotEquals($expected, $actual);

// Boolean
$this->assertTrue($condition);
$this->assertFalse($condition);

// Null
$this->assertNull($value);
$this->assertNotNull($value);

// String
$this->assertStringContainsString('needle', $haystack);
$this->assertStringStartsWith('prefix', $string);
$this->assertStringEndsWith('suffix', $string);
$this->assertMatchesRegularExpression('/pattern/', $string);

// Array
$this->assertIsArray($value);
$this->assertCount(3, $array);
$this->assertArrayHasKey('key', $array);
$this->assertContains($needle, $array);
$this->assertEmpty($array);

// Object
$this->assertInstanceOf(MyClass::class, $object);
$this->assertObjectHasProperty('property', $object);

// Numeric
$this->assertGreaterThan(5, $value);
$this->assertLessThan(10, $value);
$this->assertGreaterThanOrEqual(5, $value);
```

## Testing Hooks

### Test Action Firing

```php
public function test_action_is_fired(): void {
    $called = false;

    add_action('my_custom_action', function() use (&$called) {
        $called = true;
    });

    my_function_that_fires_action();

    $this->assertTrue($called);
}
```

### Test Filter Application

```php
public function test_filter_modifies_value(): void {
    add_filter('my_custom_filter', function($value) {
        return $value . '_modified';
    });

    $result = apply_filters('my_custom_filter', 'original');

    $this->assertEquals('original_modified', $result);
}
```

### Count Hook Calls

```php
public function test_action_fires_correct_times(): void {
    $count = 0;

    add_action('my_action', function() use (&$count) {
        $count++;
    });

    my_function(); // Should fire action twice

    $this->assertEquals(2, $count);
}
```

## Testing with Database

### Transaction Rollback

WordPress test suite automatically rolls back database changes after each test:

```php
public function test_post_creation(): void {
    // This post will be cleaned up automatically
    $post_id = wp_insert_post([
        'post_title' => 'Test',
        'post_status' => 'publish',
    ]);

    $this->assertIsInt($post_id);
}
```

### Testing Options

```php
public function test_option_is_saved(): void {
    update_option('my_plugin_setting', 'test_value');

    $this->assertEquals('test_value', get_option('my_plugin_setting'));
}

public function test_option_with_default(): void {
    // Option doesn't exist yet
    $value = get_option('nonexistent', 'default');

    $this->assertEquals('default', $value);
}
```

### Testing Transients

```php
public function test_transient_caching(): void {
    set_transient('my_cache_key', 'cached_value', HOUR_IN_SECONDS);

    $this->assertEquals('cached_value', get_transient('my_cache_key'));

    delete_transient('my_cache_key');

    $this->assertFalse(get_transient('my_cache_key'));
}
```

## Testing REST API

```php
class RestApiTest extends WP_UnitTestCase {
    private $server;

    public function set_up(): void {
        parent::set_up();

        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server();
        do_action('rest_api_init');
    }

    public function test_endpoint_is_registered(): void {
        $routes = $this->server->get_routes();

        $this->assertArrayHasKey('/my-plugin/v1/items', $routes);
    }

    public function test_get_items_returns_data(): void {
        // Create test data
        $this->factory->post->create_many(3, ['post_type' => 'item']);

        // Make request
        $request = new \WP_REST_Request('GET', '/my-plugin/v1/items');
        $response = $this->server->dispatch($request);

        $this->assertEquals(200, $response->get_status());
        $this->assertCount(3, $response->get_data());
    }

    public function test_create_item_requires_auth(): void {
        $request = new \WP_REST_Request('POST', '/my-plugin/v1/items');
        $request->set_body_params(['title' => 'Test']);

        $response = $this->server->dispatch($request);

        $this->assertEquals(401, $response->get_status());
    }

    public function test_create_item_as_admin(): void {
        $admin = $this->factory->user->create(['role' => 'administrator']);
        wp_set_current_user($admin);

        $request = new \WP_REST_Request('POST', '/my-plugin/v1/items');
        $request->set_body_params(['title' => 'Test Item']);

        $response = $this->server->dispatch($request);

        $this->assertEquals(201, $response->get_status());
    }
}
```

## Testing Blocks

```php
class BlocksTest extends WP_UnitTestCase {
    public function test_block_is_registered(): void {
        $registry = \WP_Block_Type_Registry::get_instance();
        $block = $registry->get_registered('my-plugin/my-block');

        $this->assertNotNull($block);
    }

    public function test_block_renders_output(): void {
        $block_content = render_block([
            'blockName' => 'my-plugin/my-block',
            'attrs' => [
                'title' => 'Hello',
            ],
        ]);

        $this->assertStringContainsString('Hello', $block_content);
        $this->assertStringContainsString('wp-block-my-plugin-my-block', $block_content);
    }

    public function test_block_output_is_escaped(): void {
        $block_content = render_block([
            'blockName' => 'my-plugin/my-block',
            'attrs' => [
                'title' => '<script>alert("xss")</script>',
            ],
        ]);

        $this->assertStringNotContainsString('<script>', $block_content);
    }
}
```

## Data Providers

```php
/**
 * @dataProvider priceProvider
 */
public function test_format_price(int $cents, string $expected): void {
    $result = format_price($cents);
    $this->assertEquals($expected, $result);
}

public function priceProvider(): array {
    return [
        'zero' => [0, '$0.00'],
        'cents only' => [99, '$0.99'],
        'whole dollar' => [100, '$1.00'],
        'typical price' => [1999, '$19.99'],
        'large amount' => [99999, '$999.99'],
    ];
}
```

## Testing Exceptions

```php
public function test_throws_exception_for_invalid_input(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('ID must be positive');

    my_function(-1);
}

public function test_wp_die_is_called(): void {
    $this->expectException(\WPDieException::class);

    my_function_that_calls_wp_die();
}
```

## Running Specific Tests

```bash
# Run single test file
./vendor/bin/phpunit tests/unit/HelpersTest.php

# Run single test method
./vendor/bin/phpunit --filter test_format_price

# Run test suite
./vendor/bin/phpunit --testsuite unit

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run in verbose mode
./vendor/bin/phpunit -v
```
