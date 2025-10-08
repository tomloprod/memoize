<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Services;

use Exception;
use InvalidArgumentException;
use Tomloprod\Memoize\Concerns\HasFlags;

final class MemoizeManager
{
    use HasFlags;

    private static MemoizeManager $instance;

    /** @var array<string, MemoEntry> */
    private array $memoizedValues = [];

    private ?int $maxSize = null;

    private ?MemoEntry $head = null;

    private ?MemoEntry $tail = null;

    private ?string $namespace = null;

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
     * Set a namespace for the next memoization operation.
     *
     * @param  string  $class  The class name to use as namespace
     */
    public function for(string $class): self
    {
        $this->namespace = $class;

        return $this;
    }

    /**
     * Memoize cache values in memory during a single request or job execution.
     * This prevents repeated cache hits within the same execution, significantly
     * improving performance.
     *
     * @param  string|int|float|null  $key  Cache key (null returns null when namespace is set)
     * @param  callable  $callback  Function to execute if value is not memoized
     */
    public function memo(string|int|float|null $key, callable $callback): mixed
    {
        // Handle null key with namespace: return null without executing callback
        if (($key === null || $key === '') && $this->namespace !== null) {
            $this->namespace = null;

            return null;
        }

        // Handle null key without namespace: throw exception (backward compatibility)
        if ($key === null || $key === '') {
            throw new InvalidArgumentException('Key cannot be null when no namespace is set');
        }

        // Convert key to string for consistent handling
        $key = (string) $key;

        // Build namespaced key if namespace is set
        $namespacedKey = $this->buildNamespacedKey($this->namespace, $key);

        // Return memoized value if exists
        if (array_key_exists($namespacedKey, $this->memoizedValues)) {

            $entry = $this->memoizedValues[$namespacedKey];

            $entry->markAsAccessed();

            $this->moveToHead($entry);

            $this->namespace = null;

            return $entry->getValue();
        }

        // Execute callback and memoize the result
        $value = $callback();

        $entry = new MemoEntry($this->namespace, $key, $value);
        $entry->markAsAccessed();

        $this->memoizedValues[$namespacedKey] = $entry;

        $this->addToHead($entry);

        $this->enforceLRU();

        $this->namespace = null;

        return $value;
    }

    /**
     * Remove a specific memoized value by key.
     *
     * @param  string|int|float  $key  Cache key to remove
     * @return bool True if the key existed and was removed, false otherwise
     */
    public function forget(string|int|float $key): bool
    {
        $namespacedKey = $this->buildNamespacedKey($this->namespace, (string) $key);

        if (array_key_exists($namespacedKey, $this->memoizedValues)) {

            $entry = $this->memoizedValues[$namespacedKey];

            unset($this->memoizedValues[$namespacedKey]);

            $this->removeFromList($entry);

            $this->namespace = null;

            return true;
        }

        $this->namespace = null;

        return false;
    }

    /**
     * Clear all memoized values.
     */
    public function flush(): void
    {
        $this->memoizedValues = [];
        $this->head = null;
        $this->tail = null;
        $this->namespace = null;
    }

    /**
     * Check if a key exists in the memoized cache.
     *
     * @param  string|int|float  $key  Cache key to check
     * @return bool True if the key exists, false otherwise
     */
    public function has(string|int|float $key): bool
    {
        $namespacedKey = $this->buildNamespacedKey($this->namespace, (string) $key);

        $result = array_key_exists($namespacedKey, $this->memoizedValues);

        $this->namespace = null;

        return $result;
    }

    /**
     * Get all memoized entries.
     *
     * @return array<string, MemoEntry> Array of namespacedKey => MemoEntry
     */
    public function getMemoizedValues(): array
    {
        return $this->memoizedValues;
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

    /**
     * Set the maximum size of entries (LRU).
     */
    public function setMaxSize(?int $maxSize): void
    {
        $this->maxSize = $maxSize;

        $this->enforceLRU();
    }

    /**
     * Get the maximum size currently.
     */
    public function getMaxSize(): ?int
    {
        return $this->maxSize;
    }

    /**
     * Get stats of the cache.
     *
     * @return array{
     *     size: int,
     *     maxSize: int|null,
     *     head: array{
     *         key: string|null,
     *         time: int|null,
     *         hits: int,
     *     },
     *     tail: array{
     *         key: string|null,
     *         time: int|null,
     *         hits: int,
     *     },
     * }
     */
    public function getStats(): array
    {
        $headKey = null;
        if ($this->head instanceof MemoEntry) {
            $namespace = $this->head->getNamespace();
            $key = $this->head->getKey();
            $headKey = $this->buildNamespacedKey($namespace, $key);
        }

        $tailKey = null;
        if ($this->tail instanceof MemoEntry) {
            $namespace = $this->tail->getNamespace();
            $key = $this->tail->getKey();
            $tailKey = $this->buildNamespacedKey($namespace, $key);
        }

        return [
            'size' => count($this->memoizedValues),
            'maxSize' => $this->maxSize,
            'head' => [
                'key' => $headKey,
                'time' => $this->head?->getLastAccess(),
                'hits' => $this->head?->getHits() ?? 0,
            ],
            'tail' => [
                'key' => $tailKey,
                'time' => $this->tail?->getLastAccess(),
                'hits' => $this->tail?->getHits() ?? 0,
            ],
        ];
    }

    /**
     * Apply the LRU strategy if there is a limit.
     */
    private function enforceLRU(): void
    {
        if ($this->maxSize === null || count($this->memoizedValues) <= $this->maxSize) {
            return;
        }

        // Remove from the end (least recently used) until the limit is met
        while (count($this->memoizedValues) > $this->maxSize) {
            $this->removeTail();
        }
    }

    /**
     * Add an entry to the beginning of the list (most recently used).
     */
    private function addToHead(MemoEntry $entry): void
    {
        $entry->detach();

        $entry->next = $this->head;
        $entry->previous = null;

        if ($this->head instanceof MemoEntry) {
            $this->head->previous = $entry;
        }

        $this->head = $entry;

        if (! $this->tail instanceof MemoEntry) {
            $this->tail = $entry;
        }
    }

    /**
     * Move an existing entry to the beginning of the list.
     */
    private function moveToHead(MemoEntry $entry): void
    {
        $this->removeFromList($entry);
        $this->addToHead($entry);
    }

    /**
     * Remove an entry from the list.
     */
    private function removeFromList(MemoEntry $entry): void
    {
        if ($entry === $this->head) {
            $this->head = $entry->next;
        }

        if ($entry === $this->tail) {
            $this->tail = $entry->previous;
        }

        $entry->detach();
    }

    /**
     * Remove and return the entry from the end of the list (least recently used).
     */
    private function removeTail(): void
    {
        if (! $this->tail instanceof MemoEntry) {
            return;
        }

        $lastEntry = $this->tail;

        unset($this->memoizedValues[$lastEntry->getKey()]);

        $this->removeFromList($lastEntry);
    }

    /**
     * Build a namespaced key from namespace and key components.
     */
    private function buildNamespacedKey(?string $namespace, string $key): string
    {
        return $namespace !== null ? $namespace.'::'.$key : $key;
    }
}
