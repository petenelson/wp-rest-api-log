# Mocking Strategies Reference

Guide to mocking in PHP and JavaScript tests for WordPress projects.

## PHP Mocking with PHPUnit

### Creating Mock Objects

```php
public function test_with_mock(): void {
    // Create mock
    $mock = $this->createMock(MyClass::class);

    // Configure method to return value
    $mock->method('getData')
         ->willReturn(['key' => 'value']);

    // Use mock in test
    $result = $mock->getData();
    $this->assertEquals(['key' => 'value'], $result);
}
```

### Method Expectations

```php
public function test_method_is_called(): void {
    $mock = $this->createMock(EmailService::class);

    // Expect method to be called exactly once
    $mock->expects($this->once())
         ->method('send')
         ->with('test@example.com', 'Subject', 'Body');

    // Code that should call the method
    $notifier = new Notifier($mock);
    $notifier->notify('test@example.com', 'Subject', 'Body');
}
```

### Expectation Matchers

```php
// Call count
$mock->expects($this->never())->method('foo');
$mock->expects($this->once())->method('foo');
$mock->expects($this->exactly(3))->method('foo');
$mock->expects($this->atLeast(2))->method('foo');
$mock->expects($this->atMost(5))->method('foo');
$mock->expects($this->any())->method('foo');

// Parameter matchers
$mock->method('foo')
     ->with(
         $this->equalTo('value'),
         $this->anything(),
         $this->stringContains('partial')
     );
```

### Consecutive Returns

```php
$mock->method('getNext')
     ->willReturnOnConsecutiveCalls(1, 2, 3);

// First call returns 1, second returns 2, third returns 3
```

### Return Value Map

```php
$mock->method('translate')
     ->willReturnMap([
         ['hello', 'es', 'hola'],
         ['hello', 'fr', 'bonjour'],
         ['goodbye', 'es', 'adios'],
     ]);

// translate('hello', 'es') returns 'hola'
```

### Throwing Exceptions

```php
$mock->method('riskyOperation')
     ->willThrowException(new \RuntimeException('Error!'));
```

### Callback Returns

```php
$mock->method('process')
     ->willReturnCallback(function($input) {
         return strtoupper($input);
     });
```

## Mocking WordPress Functions

### Function Mocking with Brain Monkey

```php
use Brain\Monkey\Functions;

public function set_up(): void {
    parent::set_up();
    Brain\Monkey\setUp();
}

public function tear_down(): void {
    Brain\Monkey\tearDown();
    parent::tear_down();
}

public function test_uses_wp_function(): void {
    // Mock WordPress function
    Functions\when('get_option')
        ->justReturn('mocked_value');

    $result = my_function_using_get_option();

    $this->assertEquals('mocked_value', $result);
}

// With specific arguments
public function test_get_option_with_args(): void {
    Functions\expect('get_option')
        ->once()
        ->with('my_option', 'default')
        ->andReturn('stored_value');

    $result = get_option('my_option', 'default');
    $this->assertEquals('stored_value', $result);
}
```

### Mocking Hooks

```php
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

public function test_action_is_added(): void {
    Actions\expectAdded('init')
        ->once()
        ->with('my_init_callback');

    my_plugin_setup(); // Should add action
}

public function test_filter_is_applied(): void {
    Filters\expectApplied('my_filter')
        ->once()
        ->with('original_value')
        ->andReturn('filtered_value');

    $result = apply_filters('my_filter', 'original_value');
    $this->assertEquals('filtered_value', $result);
}
```

## JavaScript Mocking with Jest

### Basic Mocks

```javascript
// Mock a module
jest.mock('./api');

import { fetchData } from './api';

test('uses mocked function', async () => {
    fetchData.mockResolvedValue({ data: 'mocked' });

    const result = await fetchData();
    expect(result).toEqual({ data: 'mocked' });
});
```

### Mock Functions (Spies)

```javascript
test('callback is called', () => {
    const callback = jest.fn();

    myFunction(callback);

    expect(callback).toHaveBeenCalled();
    expect(callback).toHaveBeenCalledWith('expected', 'args');
    expect(callback).toHaveBeenCalledTimes(1);
});

// With implementation
const mockFn = jest.fn((x) => x * 2);
expect(mockFn(5)).toBe(10);
```

### Mock Return Values

```javascript
const mock = jest.fn();

// Return once
mock.mockReturnValueOnce(1).mockReturnValueOnce(2).mockReturnValue(3);

expect(mock()).toBe(1);
expect(mock()).toBe(2);
expect(mock()).toBe(3);
expect(mock()).toBe(3); // Default

// Async returns
mock.mockResolvedValue({ data: 'async' });
mock.mockRejectedValue(new Error('Failed'));
```

### Mocking WordPress Packages

```javascript
// Mock @wordpress/data
jest.mock('@wordpress/data', () => ({
    useSelect: jest.fn(),
    useDispatch: jest.fn(),
    select: jest.fn(),
    dispatch: jest.fn(),
}));

import { useSelect, useDispatch } from '@wordpress/data';

test('uses select', () => {
    useSelect.mockReturnValue({
        posts: [{ id: 1, title: 'Test' }],
    });

    // Component using useSelect
});
```

### Mocking @wordpress/block-editor

```javascript
jest.mock('@wordpress/block-editor', () => ({
    useBlockProps: jest.fn(() => ({
        className: 'mock-class',
    })),
    RichText: jest.fn(({ value, onChange }) => (
        <input
            value={value}
            onChange={(e) => onChange(e.target.value)}
            data-testid="rich-text"
        />
    )),
    InspectorControls: jest.fn(({ children }) => (
        <div data-testid="inspector">{children}</div>
    )),
    MediaUpload: jest.fn(({ onSelect, render }) => (
        <div data-testid="media-upload">
            {render({ open: jest.fn() })}
        </div>
    )),
}));
```

### Mocking @wordpress/components

```javascript
jest.mock('@wordpress/components', () => ({
    PanelBody: ({ children, title }) => (
        <div data-testid="panel-body" data-title={title}>
            {children}
        </div>
    ),
    ToggleControl: ({ label, checked, onChange }) => (
        <label>
            <input
                type="checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
            />
            {label}
        </label>
    ),
    TextControl: ({ label, value, onChange }) => (
        <label>
            {label}
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
            />
        </label>
    ),
    SelectControl: ({ label, value, options, onChange }) => (
        <label>
            {label}
            <select value={value} onChange={(e) => onChange(e.target.value)}>
                {options.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                        {opt.label}
                    </option>
                ))}
            </select>
        </label>
    ),
}));
```

### Mocking apiFetch

```javascript
jest.mock('@wordpress/api-fetch', () => jest.fn());

import apiFetch from '@wordpress/api-fetch';

beforeEach(() => {
    apiFetch.mockClear();
});

test('fetches data from API', async () => {
    apiFetch.mockResolvedValue([{ id: 1, title: 'Post' }]);

    const result = await fetchPosts();

    expect(apiFetch).toHaveBeenCalledWith({
        path: '/wp/v2/posts',
    });
    expect(result).toHaveLength(1);
});
```

### Spying on Methods

```javascript
test('spy on object method', () => {
    const obj = {
        method: (x) => x + 1,
    };

    const spy = jest.spyOn(obj, 'method');

    obj.method(5);

    expect(spy).toHaveBeenCalledWith(5);

    spy.mockRestore();
});
```

### Timer Mocks

```javascript
jest.useFakeTimers();

test('debounced function', () => {
    const callback = jest.fn();
    const debounced = debounce(callback, 1000);

    debounced();
    debounced();
    debounced();

    expect(callback).not.toHaveBeenCalled();

    jest.advanceTimersByTime(1000);

    expect(callback).toHaveBeenCalledTimes(1);
});
```

## Common Mock Patterns

### Mock HTTP Requests (PHP)

```php
// Using WP_Mock or Brain\Monkey
Functions\expect('wp_remote_get')
    ->once()
    ->with('https://api.example.com/data')
    ->andReturn([
        'response' => ['code' => 200],
        'body' => json_encode(['key' => 'value']),
    ]);

Functions\expect('wp_remote_retrieve_body')
    ->once()
    ->andReturn(json_encode(['key' => 'value']));
```

### Mock HTTP Requests (JavaScript)

```javascript
// Using fetch mock
global.fetch = jest.fn(() =>
    Promise.resolve({
        ok: true,
        json: () => Promise.resolve({ data: 'mocked' }),
    })
);

// Or use msw (Mock Service Worker) for more realistic mocking
import { rest } from 'msw';
import { setupServer } from 'msw/node';

const server = setupServer(
    rest.get('/api/data', (req, res, ctx) => {
        return res(ctx.json({ key: 'value' }));
    })
);

beforeAll(() => server.listen());
afterEach(() => server.resetHandlers());
afterAll(() => server.close());
```

### Mock Date/Time

```javascript
// Jest
jest.useFakeTimers().setSystemTime(new Date('2024-01-15'));

test('uses mocked date', () => {
    expect(new Date().getFullYear()).toBe(2024);
});

jest.useRealTimers();
```

```php
// PHP - use a wrapper function
public function test_with_mocked_time(): void {
    // Mock current_time function
    Functions\when('current_time')
        ->justReturn('2024-01-15 10:00:00');

    $result = my_function_using_time();
    $this->assertStringContainsString('2024', $result);
}
```

### Mock localStorage/sessionStorage

```javascript
const localStorageMock = {
    getItem: jest.fn(),
    setItem: jest.fn(),
    removeItem: jest.fn(),
    clear: jest.fn(),
};

Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
});

test('stores value', () => {
    savePreference('theme', 'dark');
    expect(localStorage.setItem).toHaveBeenCalledWith('theme', 'dark');
});
```

## Testing Tips

### Reset Mocks Between Tests

```javascript
beforeEach(() => {
    jest.clearAllMocks();
    // or
    myMock.mockClear();
});

afterEach(() => {
    jest.restoreAllMocks();
});
```

### Verify Mock Was Used

```javascript
test('API was called correctly', async () => {
    apiFetch.mockResolvedValue([]);

    await component();

    expect(apiFetch).toHaveBeenCalledTimes(1);
    expect(apiFetch).toHaveBeenCalledWith(
        expect.objectContaining({
            path: expect.stringContaining('/posts'),
        })
    );
});
```

### Partial Matching

```javascript
expect(fn).toHaveBeenCalledWith(
    expect.objectContaining({ id: 1 }),
    expect.any(Function),
    expect.stringMatching(/pattern/)
);
```
