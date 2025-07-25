<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoizeManager;

if (! function_exists('memoize')) {
    function memoize(): MemoizeManager
    {
        return MemoizeManager::instance();
    }
}
