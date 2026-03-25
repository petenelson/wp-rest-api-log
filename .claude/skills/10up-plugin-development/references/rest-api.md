# WordPress REST API Reference

Guide to creating REST API endpoints in WordPress plugins.

## Registering Routes

### Basic Route

```php
add_action('rest_api_init', function() {
    register_rest_route('plugin-name/v1', '/items', [
        'methods'  => 'GET',
        'callback' => 'get_items',
        'permission_callback' => '__return_true', // Public endpoint
    ]);
});
```

### Route with Parameters

```php
register_rest_route('plugin-name/v1', '/items/(?P<id>\d+)', [
    'methods' => 'GET',
    'callback' => [$this, 'get_item'],
    'permission_callback' => [$this, 'check_permission'],
    'args' => [
        'id' => [
            'description' => 'Unique identifier for the item.',
            'type' => 'integer',
            'required' => true,
            'validate_callback' => function($param) {
                return is_numeric($param) && $param > 0;
            },
            'sanitize_callback' => 'absint',
        ],
    ],
]);
```

### Multiple Methods

```php
register_rest_route('plugin-name/v1', '/items', [
    [
        'methods' => WP_REST_Server::READABLE, // GET
        'callback' => [$this, 'get_items'],
        'permission_callback' => '__return_true',
    ],
    [
        'methods' => WP_REST_Server::CREATABLE, // POST
        'callback' => [$this, 'create_item'],
        'permission_callback' => [$this, 'create_permission'],
        'args' => $this->get_create_args(),
    ],
]);
```

### HTTP Method Constants

| Constant | Methods |
|----------|---------|
| `WP_REST_Server::READABLE` | GET |
| `WP_REST_Server::CREATABLE` | POST |
| `WP_REST_Server::EDITABLE` | POST, PUT, PATCH |
| `WP_REST_Server::DELETABLE` | DELETE |
| `WP_REST_Server::ALLMETHODS` | All methods |

## Callbacks

### GET Callback

```php
public function get_items(WP_REST_Request $request): WP_REST_Response {
    $page = $request->get_param('page') ?? 1;
    $per_page = $request->get_param('per_page') ?? 10;

    $items = $this->fetch_items($page, $per_page);

    return new WP_REST_Response($items, 200);
}

public function get_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $id = $request->get_param('id');
    $item = $this->fetch_item($id);

    if (!$item) {
        return new WP_Error(
            'not_found',
            __('Item not found.', 'plugin-name'),
            ['status' => 404]
        );
    }

    return new WP_REST_Response($item, 200);
}
```

### POST Callback

```php
public function create_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $params = $request->get_json_params();

    $title = sanitize_text_field($params['title']);
    $content = wp_kses_post($params['content']);

    $item_id = $this->insert_item([
        'title' => $title,
        'content' => $content,
    ]);

    if (is_wp_error($item_id)) {
        return $item_id;
    }

    $item = $this->fetch_item($item_id);

    return new WP_REST_Response($item, 201);
}
```

### PUT/PATCH Callback

```php
public function update_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $id = $request->get_param('id');
    $params = $request->get_json_params();

    $item = $this->fetch_item($id);

    if (!$item) {
        return new WP_Error('not_found', 'Item not found.', ['status' => 404]);
    }

    $updated = $this->do_update($id, $params);

    if (is_wp_error($updated)) {
        return $updated;
    }

    return new WP_REST_Response($this->fetch_item($id), 200);
}
```

### DELETE Callback

```php
public function delete_item(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $id = $request->get_param('id');

    $item = $this->fetch_item($id);

    if (!$item) {
        return new WP_Error('not_found', 'Item not found.', ['status' => 404]);
    }

    $deleted = $this->do_delete($id);

    if (is_wp_error($deleted)) {
        return $deleted;
    }

    return new WP_REST_Response(null, 204);
}
```

## Permission Callbacks

### Always Required

Every endpoint MUST have a permission_callback:

```php
// Public endpoint
'permission_callback' => '__return_true',

// Logged-in users only
'permission_callback' => function() {
    return is_user_logged_in();
},

// Specific capability
'permission_callback' => function() {
    return current_user_can('manage_options');
},

// Resource-specific permission
'permission_callback' => function(WP_REST_Request $request) {
    $post_id = $request->get_param('id');
    return current_user_can('edit_post', $post_id);
},
```

### Class Method Permission

```php
public function create_permission(WP_REST_Request $request): bool {
    return current_user_can('publish_posts');
}

public function update_permission(WP_REST_Request $request): bool {
    $id = $request->get_param('id');
    return current_user_can('edit_post', $id);
}

public function delete_permission(WP_REST_Request $request): bool {
    $id = $request->get_param('id');
    return current_user_can('delete_post', $id);
}
```

## Argument Validation

### Argument Schema

```php
private function get_item_args(): array {
    return [
        'title' => [
            'description' => 'The title of the item.',
            'type' => 'string',
            'required' => true,
            'minLength' => 1,
            'maxLength' => 200,
            'sanitize_callback' => 'sanitize_text_field',
        ],
        'status' => [
            'description' => 'The status of the item.',
            'type' => 'string',
            'enum' => ['draft', 'publish', 'pending'],
            'default' => 'draft',
        ],
        'priority' => [
            'description' => 'Priority level.',
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => 10,
            'default' => 5,
        ],
        'tags' => [
            'description' => 'Associated tags.',
            'type' => 'array',
            'items' => [
                'type' => 'string',
            ],
            'default' => [],
        ],
        'meta' => [
            'description' => 'Additional metadata.',
            'type' => 'object',
            'properties' => [
                'color' => ['type' => 'string'],
                'size' => ['type' => 'string'],
            ],
        ],
    ];
}
```

### Custom Validation

```php
'args' => [
    'email' => [
        'type' => 'string',
        'required' => true,
        'validate_callback' => function($value, $request, $key) {
            if (!is_email($value)) {
                return new WP_Error(
                    'invalid_email',
                    'Please provide a valid email address.'
                );
            }
            return true;
        },
        'sanitize_callback' => 'sanitize_email',
    ],
    'date' => [
        'type' => 'string',
        'format' => 'date-time',
        'validate_callback' => function($value) {
            $date = DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
            return $date !== false;
        },
    ],
],
```

## Response Formatting

### Standard Response

```php
public function get_item(WP_REST_Request $request): WP_REST_Response {
    $item = $this->fetch_item($request->get_param('id'));

    $response = new WP_REST_Response([
        'id' => $item->id,
        'title' => $item->title,
        'content' => $item->content,
        'status' => $item->status,
        'created_at' => mysql_to_rfc3339($item->created_at),
    ], 200);

    // Add headers
    $response->header('X-Custom-Header', 'value');

    return $response;
}
```

### Collection Response with Pagination

```php
public function get_items(WP_REST_Request $request): WP_REST_Response {
    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');

    $items = $this->fetch_items($page, $per_page);
    $total = $this->count_items();

    $response = new WP_REST_Response(
        array_map([$this, 'prepare_item'], $items),
        200
    );

    // Pagination headers
    $response->header('X-WP-Total', $total);
    $response->header('X-WP-TotalPages', ceil($total / $per_page));

    return $response;
}
```

### Error Response

```php
return new WP_Error(
    'error_code',           // Error code
    'Human readable message', // Message
    ['status' => 400]       // Additional data with HTTP status
);

// Common status codes
// 400 - Bad Request (validation error)
// 401 - Unauthorized (not logged in)
// 403 - Forbidden (no permission)
// 404 - Not Found
// 500 - Internal Server Error
```

## Controller Class Pattern

### Full Controller Example

```php
<?php
namespace PluginName\Rest;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class Items_Controller extends WP_REST_Controller {

    protected $namespace = 'plugin-name/v1';
    protected $rest_base = 'items';

    public function register_routes(): void {
        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'create_item'],
                'permission_callback' => [$this, 'create_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_item'],
                'permission_callback' => [$this, 'get_item_permissions_check'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                    ],
                ],
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [$this, 'update_item'],
                'permission_callback' => [$this, 'update_item_permissions_check'],
                'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::EDITABLE),
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [$this, 'delete_item'],
                'permission_callback' => [$this, 'delete_item_permissions_check'],
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);
    }

    public function get_items_permissions_check($request): bool {
        return true;
    }

    public function get_item_permissions_check($request): bool {
        return true;
    }

    public function create_item_permissions_check($request): bool {
        return current_user_can('publish_posts');
    }

    public function update_item_permissions_check($request): bool {
        return current_user_can('edit_posts');
    }

    public function delete_item_permissions_check($request): bool {
        return current_user_can('delete_posts');
    }

    public function get_item_schema(): array {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'title' => 'item',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'description' => 'Unique identifier.',
                    'type' => 'integer',
                    'readonly' => true,
                ],
                'title' => [
                    'description' => 'The title.',
                    'type' => 'string',
                    'required' => true,
                ],
                'content' => [
                    'description' => 'The content.',
                    'type' => 'string',
                ],
                'status' => [
                    'description' => 'Publication status.',
                    'type' => 'string',
                    'enum' => ['draft', 'publish'],
                ],
            ],
        ];
    }
}
```

### Initialize Controller

```php
add_action('rest_api_init', function() {
    $controller = new Items_Controller();
    $controller->register_routes();
});
```

## Testing Endpoints

### WP-CLI

```bash
# GET request
wp rest get /plugin-name/v1/items --user=admin

# POST request
wp rest post /plugin-name/v1/items --user=admin title="New Item" status="publish"

# With JSON body
wp rest post /plugin-name/v1/items --user=admin --json='{"title":"New Item"}'
```

### cURL

```bash
# GET
curl https://example.com/wp-json/plugin-name/v1/items

# POST with authentication
curl -X POST https://example.com/wp-json/plugin-name/v1/items \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: NONCE_VALUE" \
  -d '{"title":"New Item"}' \
  --cookie "wordpress_logged_in_xxx=..."
```

### JavaScript (Fetch API)

```javascript
// GET
const response = await fetch('/wp-json/plugin-name/v1/items');
const items = await response.json();

// POST
const response = await fetch('/wp-json/plugin-name/v1/items', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce,
    },
    body: JSON.stringify({ title: 'New Item' }),
});
```

### @wordpress/api-fetch

```javascript
import apiFetch from '@wordpress/api-fetch';

// GET
const items = await apiFetch({ path: '/plugin-name/v1/items' });

// POST
const newItem = await apiFetch({
    path: '/plugin-name/v1/items',
    method: 'POST',
    data: { title: 'New Item' },
});
```
