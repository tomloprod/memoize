<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoizeManager;

test('memoize alias return instance of memoize', function (): void {
    expect(memoize())->toBeInstanceOf(MemoizeManager::class);
});
