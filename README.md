<div align="center">

# 🧠 Memoize

**High-performance memoization library for PHP**

<p align="center">
    <p align="center">
        <a href="https://github.com/tomloprod/memoize/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/tomloprod/memoize/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/tomloprod/memoize"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="Latest Version" src="https://img.shields.io/packagist/v/tomloprod/memoize"></a>
        <a href="https://packagist.org/packages/tomloprod/memoize"><img alt="License" src="https://img.shields.io/packagist/l/tomloprod/memoize"></a>
    </p>
</p>

---

</div>

## 🎯 **About Memoize**

**Memoize** is a lightweight PHP library designed to implement memoization and function caching techniques with ease.


Transform expensive function calls into lightning-fast cache lookups with zero configuration.

## ✨ **Features**

<table>
<tr>
<td width="50%">

🔑 **Key-based Memoization**  
Cache function results with custom keys

⚡ **Single Execution**  
Functions run only once, results cached forever

🏷️ **Namespaces**  
Organize cache by classes or contexts

</td>
<td width="50%">

🧠 **LRU Cache**  
Automatic memory management with size limits

📊 **Cache Analytics**  
Built-in statistics and monitoring

🏃 **Runtime Flags**  
Dynamic behavior control during execution

</td>
</tr>
</table>

## 🚀 **Quick Start**

### Installation

```bash
composer require tomloprod/memoize
```

### Basic Usage

#### 🏷️ **Namespace Organization**

If you want to get the most out of the package and better organize your memoization, we recommend using namespaces.

When using namespaces, if you use a `$key` with a null value, the callback won’t be executed (*especially useful in certain cases*).

```php
// Organize cache by context
$userSettings = memoize()
    ->for(UserSettings::class)
    ->memo($userId, fn() => UserSettings::where('user_id', $userId)->first());

$productCache = memoize()
    ->for(Product::class)  
    ->memo($productId, fn() => Product::with('variants')->find($productId));
```

#### 🔑 **Key-based Memoization**

You can also not use namespaces and just memoize keys.

```php
// Expensive API call cached by key
$weather = memoize()->memo(
    'weather_london', 
    fn() => Http::get('api.weather.com/london'->json()
);

// Database query with dynamic key
$user = memoize()->memo(
    "user_{$id}", 
    fn() => User::with('profile', 'orders')->find($id)
);
```

#### ⚡ **Single Execution Functions**

```php
// Initialize expensive resources only once
$services = memoize()->once(fn() => [
    'redis' => new Redis(),
    'elasticsearch' => new Client(),
    'logger' => new Logger(),
]);

$redis = $services()['redis']; // Initialized once
$same = $services()['redis'];  // Same instance
```



#### 🧠 **Memory Management**

The library uses an **LRU (Least Recently Used)** algorithm to automatically manage memory and prevent unlimited cache growth.

**How does LRU work?**
- Maintains a record of the access order for cache entries
- When the maximum limit (`maxSize`) is reached, automatically removes the **least recently used** entry
- Every time you access an entry (read or write), it moves to the front of the queue
- Older entries remain at the end and are candidates for removal

This ensures that the most relevant and frequently used data remains in memory, while obsolete data is automatically removed.

```php
// Set LRU cache limit (by default, there is no max size)
memoize()->setMaxSize(1000);

// Cache statistics
$stats = memoize()->getStats();
// ['size' => 150, 'maxSize' => 1000, 'head' => [...], 'tail' => [...]]

// Clear specific or all cache
memoize()->forget('user_123');
memoize()->for('App\\Model\\User')->forget('123');

// Or clear all cache
memoize()->flush();
```

#### 🏃 **Runtime Flags**

Control memoization behavior dynamically during execution with runtime flags. These flags exist only in memory and reset between requests/processes.

**How do runtime flags work?**
- Flags are stored in memory during the current execution
- They allow conditional behavior without external configuration
- Perfect for debug modes, logging control, and dynamic optimizations
- Automatically cleared when the process ends

```php
// Skip cache during testing
memoize()->enableFlag('bypass_cache');

$userData = memoize()->memo("user_{$id}", function() use ($id) {
    if (memoize()->hasFlag('bypass_cache')) {
        return User::fresh()->find($id); // Always fetch from DB in tests
    }
    return User::find($id);
});


// Feature toggles without external dependencies
memoize()->enableFlag('new_algorithm');

$result = memoize()->memo($cacheKey, function() {
    if (memoize()->hasFlag('new_algorithm')) {
        return $this->calculateWithNewAlgorithm();
    }
    return $this->calculateWithOldAlgorithm();
});

// Development vs Production behavior
if (app()->environment('local') && memoize()->hasFlag('dev_mode')) {
    memoize()->enableFlags(['verbose_logging', 'bypass_cache']);
}

// Model boot method with conditional service calls
class Product extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        static::updated(function ($product) {
            // Only call external stock service if flag is not set
            if (! memoize()->hasFlag('disableStockService')) {
                app(StockService::class)->updateInventory($product);
            }
        });
    }
}
```

**Runtime Flag Methods:**

<table>
<tr><td width="40%"><strong>Method</strong></td><td><strong>Description</strong></td></tr>
<tr><td>

**enableFlag(string $flag)**

</td><td>Enable a specific runtime flag</td></tr>
<tr><td>

**disableFlag(string $flag)**

</td><td>Disable a specific runtime flag</td></tr>
<tr><td>

**toggleFlag(string $flag)**

</td><td>Toggle flag state (enabled/disabled)</td></tr>
<tr><td>

**hasFlag(string $flag): bool**

</td><td>Check if a specific flag is enabled</td></tr>
<tr><td>

**enableFlags(array $flags)**

</td><td>Enable multiple flags at once</td></tr>
<tr><td>

**disableFlags(array $flags)**

</td><td>Disable multiple flags at once</td></tr>
<tr><td>

**hasAnyFlag(array $flags): bool**

</td><td>Check if at least one flag is enabled</td></tr>
<tr><td>

**hasAllFlags(array $flags): bool**

</td><td>Check if all specified flags are enabled</td></tr>
<tr><td>

**getFlags(): array**

</td><td>Get all currently enabled flags</td></tr>
<tr><td>

**clearFlags()**

</td><td>Clear all enabled flags</td></tr>
</table>

## 💡 **Advanced Examples**

### 🏃‍♂️ **Performance Optimization**

```php
// Fibonacci with memoization - O(n) instead of O(2^n)
function fibonacci(int $n): int {
    return memoize()->memo(
        "fib_{$n}", 
        fn() => $n <= 1 ? $n : fibonacci($n - 1) + fibonacci($n - 2)
    );
}

// Complex data aggregation
$salesReport = memoize()->memo(
    "sales_report_{$month}", 
    fn() => Order::whereMonth('created_at', $month)
        ->with('items.product')
        ->get()
        ->groupBy('status')
        ->map(fn($orders) => $orders->sum('total'))
);
```

## 📖 **API Reference**

### Core Methods

<table>
<tr><td width="30%"><strong>Method</strong></td><td><strong>Description</strong></td></tr>
<tr><td>

**memo(?string $key, callable $callback)**

</td><td>

**Key-based memoization** - Execute callback and cache result by key. Returns cached value on subsequent calls.

</td></tr>
<tr><td>

**once(callable $callback)**

</td><td>

**Single execution** - Returns a wrapper function that executes the callback only once, caching the result forever.

</td></tr>
<tr><td>

**for(string $class)**

</td><td>

**Namespace organization** - Set namespace to organize cache by class/context. Automatically cleared after use.

</td></tr>
</table>

### Cache Management

<table>
<tr><td width="30%"><strong>Method</strong></td><td><strong>Description</strong></td></tr>
<tr><td>

**has(string $key): bool**

</td><td>Check if a key exists in cache</td></tr>
<tr><td>

**forget(string $key): bool**

</td><td>Remove specific key from cache</td></tr>
<tr><td>

**flush(): void**

</td><td>Clear all cached values</td></tr>
<tr><td>

**setMaxSize(?int $maxSize): void**

</td><td>Set maximum entries (LRU eviction)</td></tr>
<tr><td>

**getStats(): array**

</td><td>Get detailed cache statistics</td></tr>
</table>

## ⚙️ **Requirements & Installation**

- **PHP 8.2+**
- **Composer**

```bash
composer require tomloprod/memoize
```

## **🧑‍🤝‍🧑 Contributing**

Contributions are welcome, and are accepted via pull requests.
Please [review these guidelines](./CONTRIBUTING.md) before submitting any pull requests.

------

**Memoize** was created by **[Tomás López](https://twitter.com/tomloprod)** and open-sourced under the **[MIT license](https://opensource.org/licenses/MIT)**.
