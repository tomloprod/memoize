<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Support\Facades;

use Tomloprod\Memoize\Services\MemoizeManager;

/**
 * @method static mixed memo(string $key, callable $callback)
 * @method static bool forget(string $key)
 * @method static void flush()
 * @method static bool has(string $key)
 * @method static array<string> keys()
 * @method static callable once(callable $fn)
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
