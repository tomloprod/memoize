<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Support\Facades;

use Tomloprod\Memoize\Services\MemoEntry;
use Tomloprod\Memoize\Services\MemoizeManager;

/**
 * @method static mixed memo(?string $key, callable $callback)
 * @method static bool forget(string $key)
 * @method static void flush()
 * @method static bool has(string $key)
 * @method static array<string, MemoEntry> getMemoizedValues()
 * @method static callable once(callable $fn)
 * @method static void setMaxSize(?int $maxSize)
 * @method static ?int getMaxSize()
 * @method static array getStats()
 * @method static self for(string $class)
 */
final class Memoize
{
    /**
     * @param  array<mixed>  $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = MemoizeManager::instance();

        return $instance->$method(...$args);
    }
}
