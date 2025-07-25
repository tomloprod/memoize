<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoizeManager;
use Tomloprod\Memoize\Support\Facades\Memoize;

test('facade returns the same instance', function (): void {
    $instance1 = MemoizeManager::instance();
    $instance2 = Memoize::instance();

    expect($instance1)->toBe($instance2);
});
