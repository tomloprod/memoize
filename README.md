<p align="center">
    <p align="center">
        <a href="https://github.com/tomloprod/memoize/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/tomloprod/memoize/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/tomloprod/memoize"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="Latest Version" src="https://img.shields.io/packagist/v/tomloprod/memoize"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="License" src="https://img.shields.io/packagist/l/tomloprod/memoize"></a>
    </p>
</p>

------
## 🚀 **About Memoize**

Memoize is a lightweight PHP library designed to implement memoization and function caching techniques with ease.

It allows you to optimize your application's performance by caching the results of expensive functions, avoiding repeated calculations when called with the same arguments.

Additionally, it provides specialized functionalities such as:

- **Key-based memoization** - Cache function results using custom keys for efficient retrieval
- **Single execution functions** - Execute functions only once and reuse the result in subsequent calls  
- **Simple and intuitive API** - Facade and helper function for easy integration
- **Cache management** - Methods to check, remove, and clear cached values

## **✨ Getting Started**

### Basic memoization with memo()

You can memoize any callable function using the `memo()` method with a custom key. This will execute the callback only once and return the cached result on subsequent calls with the same key.

```php
// Using the helper function
$result1 = memoize()->memo('expensive_calc', function () {
    sleep(2); // Simulates expensive operation
    return 42 * 1.5 + rand(1, 100);
});

// Second call with same key: returns cached result (fast)
$result2 = memoize()->memo('expensive_calc', function () {
    sleep(2); // This won't execute
    return 99999; // This value is ignored, cached value is returned
});

// $result1 === $result2 (same cached value)

// Different key: executes the function again
$result3 = memoize()->memo('another_calc', function () {
    return 'different calculation';
});
```

### Single execution with once()

For functions that should only be executed once regardless of how many times they are called, use the `once()` method.

```php
// Expensive initialization function
$initializeDatabase = function () {
    echo "Initializing database...\n";
    sleep(3);
    return "Connection established";
};

// Create function that executes only once
$memoizedInit = memoize()->once($initializeDatabase);

// First call: executes the function
$connection1 = $memoizedInit(); // "Initializing database..." + 3 seconds

// Subsequent calls: return cached result
$connection2 = $memoizedInit(); // Instant, no output
$connection3 = $memoizedInit(); // Instant, no output

// All variables contain the same value: "Connection established"
```

### Cache management

The library provides methods to manage the memoized values:

```php
// Check if a key exists
if (memoize()->has('my_key')) {
    echo "Value is cached";
}

// Remove a specific cached value
$removed = memoize()->forget('my_key'); // Returns true if existed

// Get all cached keys
$keys = memoize()->keys(); // Returns array of keys

// Clear all cached values
memoize()->flush();
```

### Practical use cases

#### User data caching

```php
$getUserData = function ($userId) {
    // Expensive database query or API call
    return database()->query("SELECT * FROM users WHERE id = ?", [$userId]);
};

// Cache user data by ID
$user = memoize()->memo("user_$userId", $getUserData);
$sameUser = memoize()->memo("user_$userId", $getUserData); // From cache
```

#### Configuration loading

```php
$loadConfig = function () {
    $config = [];
    foreach (glob('config/*.php') as $file) {
        $config = array_merge($config, require $file);
    }
    return $config;
};

// Load configuration only once
$config = memoize()->memo('app_config', $loadConfig);
$configAgain = memoize()->memo('app_config', $loadConfig); // From cache
```

#### Expensive calculations

```php
// Complex calculation that takes time
$complexCalculation = function ($data) {
    sleep(5); // Simulate heavy processing
    return array_sum(array_map(fn($x) => $x ** 2, $data));
};

$data = range(1, 1000);
$result = memoize()->memo('calculation_' . md5(serialize($data)), function () use ($complexCalculation, $data) {
    return $complexCalculation($data);
});
```

#### One-time initialization

```php
// Initialize third-party services only once
$initializeServices = memoize()->once(function () {
    // Heavy initialization
    return [
        'logger' => new Logger(),
        'cache' => new CacheManager(),
        'mailer' => new MailService()
    ];
});

$services = $initializeServices(); // Initializes
$sameServices = $initializeServices(); // From cache
```

### Performance and optimization

Memoization is especially useful for:

- **Expensive database queries** - Cache results for repeated queries
- **API calls** - Reduce HTTP requests for the same data
- **File processing** - Cache parsing or transformation results
- **Complex calculations** - CPU-intensive operations
- **Configuration loading** - Load settings once per request

#### Performance example

```php
// Without memoization - executes every time
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = expensiveFunction(); // Takes 100ms each
}
$timeWithoutMemo = microtime(true) - $start; // ~10 seconds

// With memoization - executes only once
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    $result = memoize()->memo('expensive_key', fn() => expensiveFunction());
}
$timeWithMemo = microtime(true) - $start; // ~100ms

echo "Improvement: " . round($timeWithoutMemo / $timeWithMemo) . "x faster";
```

### Ways of using Memoize

You can use Memoize either with the helper function `memoize()`:

```php
// Using the helper function
$result = memoize()->memo('key', $callback);
$onceFunction = memoize()->once($callback);
memoize()->flush();
```

or by directly invoking the static methods of the `Memoize` facade:

```php
use Tomloprod\Memoize\Support\Facades\Memoize;

// Using the facade
$result = Memoize::memo('key', $callback);
$onceFunction = Memoize::once($callback);
Memoize::flush();
```

### Available Methods

| Method | Description |
|--------|-------------|
| `memo(string $key, callable $callback)` | Execute callback and cache result by key |
| `once(callable $callback)` | Return function that executes only once |
| `has(string $key)` | Check if key exists in cache |
| `forget(string $key)` | Remove specific key from cache |
| `keys()` | Get array of all cached keys |
| `flush()` | Clear all cached values |

## **🚀 Installation & Requirements**

> **Requires [PHP 8.2+](https://php.net/releases/)**

You may use [Composer](https://getcomposer.org) to install Memoize into your PHP project:

```bash
composer require tomloprod/memoize
```

## **🧑‍🤝‍🧑 Contributing**

Contributions are welcome, and are accepted via pull requests.
Please [review these guidelines](./CONTRIBUTING.md) before submitting any pull requests.

------

**Memoize** was created by **[Tomás López](https://twitter.com/tomloprod)** and open-sourced under the **[MIT license](https://opensource.org/licenses/MIT)**.
