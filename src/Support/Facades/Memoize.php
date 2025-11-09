<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Support\Facades;

use Tomloprod\Memoize\Services\MemoEntry;
use Tomloprod\Memoize\Services\MemoizeManager;

/**
 * @method static mixed memo(string|int|float|null $key, callable $callback)
 * @method static bool forget(string|int|float $key)
 * @method static void flush()
 * @method static bool has(string|int|float $key)
 * @method static array<string, MemoEntry> getMemoizedValues()
 * @method static callable once(callable $fn)
 * @method static void setMaxSize(?int $maxSize)
 * @method static ?int getMaxSize()
 * @method static array getStats()
 * @method static self for(string $class)
 * @method static self enableFlag(string $flag)
 * @method static self disableFlag(string $flag)
 * @method static self toggleFlag(string $flag)
 * @method static bool hasFlag(string $flag)
 * @method static array<string, bool> getFlags()
 * @method static self enableFlags(array $flags)
 * @method static self disableFlags(array $flags)
 * @method static self clearFlags()
 * @method static bool hasAnyFlag(array $flags)
 * @method static bool hasAllFlags(array $flags)
 * @method static void disable()
 * @method static void enable()
 * @method static bool isEnabled()
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
