<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Services;

use Exception;

final class MemoizeManager
{
    private static MemoizeManager $instance;

    /** @var array<string, mixed> */
    private array $memoizedValues = [];

    private function __construct() {}

    public function __clone()
    {
        throw new Exception('Cannot clone singleton');
    }

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }

    /**
     * Get the singleton instance of Memoize.
     */
    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Memoize cache values in memory during a single request or job execution.
     * This prevents repeated cache hits within the same execution, significantly
     * improving performance.
     *
     * @param  string  $key  Cache key
     * @param  callable  $callback  Function to execute if value is not memoized
     */
    public function memo(string $key, callable $callback): mixed
    {
        // Return memoized value if exists
        if (array_key_exists($key, $this->memoizedValues)) {
            return $this->memoizedValues[$key];
        }

        // Execute callback and memoize the result
        $value = $callback();
        $this->memoizedValues[$key] = $value;

        return $value;
    }

    /**
     * Remove a specific memoized value by key.
     *
     * @param  string  $key  Cache key to remove
     * @return bool True if the key existed and was removed, false otherwise
     */
    public function forget(string $key): bool
    {
        if (array_key_exists($key, $this->memoizedValues)) {
            unset($this->memoizedValues[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clear all memoized values.
     */
    public function flush(): void
    {
        $this->memoizedValues = [];
    }

    /**
     * Check if a key exists in the memoized cache.
     *
     * @param  string  $key  Cache key to check
     * @return bool True if the key exists, false otherwise
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->memoizedValues);
    }

    /**
     * Get all memoized keys.
     *
     * @return array<string> Array of all cached keys
     */
    public function keys(): array
    {
        return array_keys($this->memoizedValues);
    }

    /**
     * Execute $fn only the first time and reuse the first result.
     *
     * @template T
     *
     * @param  callable():T  $fn  Function without arguments
     * @return callable():T Memoized wrapper
     */
    public function once(callable $fn): callable
    {
        $called = false;
        $result = null;

        return static function () use (&$called, &$result, $fn) {
            if (! $called) {
                $result = $fn();
                $called = true;
            }

            return $result;
        };
    }
}
