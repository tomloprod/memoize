<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoizeManager;

beforeEach(function (): void {
    $this->manager = MemoizeManager::instance();
    $this->manager->flush();
    $this->manager->clearFlags();
});

test('enableFlag enables a specific flag', function (): void {
    $result = $this->manager->enableFlag('test_flag');

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('test_flag'))->toBeTrue()
        ->and($this->manager->getFlags())->toBe(['test_flag' => true]);
});

test('disableFlag disables a specific flag', function (): void {
    $this->manager->enableFlag('test_flag');
    $result = $this->manager->disableFlag('test_flag');

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('test_flag'))->toBeFalse()
        ->and($this->manager->getFlags())->toBe([]);
});

test('disableFlag returns instance when flag does not exist', function (): void {
    $result = $this->manager->disableFlag('non_existent_flag');

    expect($result)->toBe($this->manager)
        ->and($this->manager->getFlags())->toBe([]);
});

test('toggleFlag enables flag when disabled', function (): void {
    $result = $this->manager->toggleFlag('test_flag');

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('test_flag'))->toBeTrue()
        ->and($this->manager->getFlags())->toBe(['test_flag' => true]);
});

test('toggleFlag disables flag when enabled', function (): void {
    $this->manager->enableFlag('test_flag');
    $result = $this->manager->toggleFlag('test_flag');

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('test_flag'))->toBeFalse()
        ->and($this->manager->getFlags())->toBe([]);
});

test('hasFlag returns true when flag is enabled', function (): void {
    $this->manager->enableFlag('test_flag');

    expect($this->manager->hasFlag('test_flag'))->toBeTrue();
});

test('hasFlag returns false when flag is disabled', function (): void {
    expect($this->manager->hasFlag('test_flag'))->toBeFalse();
});

test('getFlags returns all enabled flags', function (): void {
    $this->manager->enableFlag('flag1')
        ->enableFlag('flag2')
        ->enableFlag('flag3');

    $expected = [
        'flag1' => true,
        'flag2' => true,
        'flag3' => true,
    ];

    expect($this->manager->getFlags())->toBe($expected);
});

test('getFlags returns empty array when no flags are enabled', function (): void {
    expect($this->manager->getFlags())->toBe([]);
});

test('enableFlags enables multiple flags at once', function (): void {
    $flags = ['flag1', 'flag2', 'flag3'];
    $result = $this->manager->enableFlags($flags);

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('flag1'))->toBeTrue()
        ->and($this->manager->hasFlag('flag2'))->toBeTrue()
        ->and($this->manager->hasFlag('flag3'))->toBeTrue()
        ->and($this->manager->getFlags())->toBe([
            'flag1' => true,
            'flag2' => true,
            'flag3' => true,
        ]);
});

test('enableFlags works with empty array', function (): void {
    $result = $this->manager->enableFlags([]);

    expect($result)->toBe($this->manager)
        ->and($this->manager->getFlags())->toBe([]);
});

test('disableFlags disables multiple flags at once', function (): void {
    $this->manager->enableFlag('flag1')
        ->enableFlag('flag2')
        ->enableFlag('flag3')
        ->enableFlag('flag4');

    $result = $this->manager->disableFlags(['flag1', 'flag3']);

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('flag1'))->toBeFalse()
        ->and($this->manager->hasFlag('flag2'))->toBeTrue()
        ->and($this->manager->hasFlag('flag3'))->toBeFalse()
        ->and($this->manager->hasFlag('flag4'))->toBeTrue()
        ->and($this->manager->getFlags())->toBe([
            'flag2' => true,
            'flag4' => true,
        ]);
});

test('disableFlags works with empty array', function (): void {
    $this->manager->enableFlag('flag1');
    $result = $this->manager->disableFlags([]);

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('flag1'))->toBeTrue();
});

test('disableFlags works with non-existent flags', function (): void {
    $this->manager->enableFlag('flag1');
    $result = $this->manager->disableFlags(['flag1', 'non_existent']);

    expect($result)->toBe($this->manager)
        ->and($this->manager->hasFlag('flag1'))->toBeFalse()
        ->and($this->manager->getFlags())->toBe([]);
});

test('clearFlags removes all enabled flags', function (): void {
    $this->manager->enableFlag('flag1')
        ->enableFlag('flag2')
        ->enableFlag('flag3');

    $result = $this->manager->clearFlags();

    expect($result)->toBe($this->manager)
        ->and($this->manager->getFlags())->toBe([])
        ->and($this->manager->hasFlag('flag1'))->toBeFalse()
        ->and($this->manager->hasFlag('flag2'))->toBeFalse()
        ->and($this->manager->hasFlag('flag3'))->toBeFalse();
});

test('clearFlags works when no flags are enabled', function (): void {
    $result = $this->manager->clearFlags();

    expect($result)->toBe($this->manager)
        ->and($this->manager->getFlags())->toBe([]);
});

test('hasAnyFlag returns true when at least one flag is enabled', function (): void {
    $this->manager->enableFlag('flag2');

    expect($this->manager->hasAnyFlag(['flag1', 'flag2', 'flag3']))->toBeTrue();
});

test('hasAnyFlag returns false when no flags are enabled', function (): void {
    expect($this->manager->hasAnyFlag(['flag1', 'flag2', 'flag3']))->toBeFalse();
});

test('hasAnyFlag returns false with empty array', function (): void {
    $this->manager->enableFlag('flag1');

    expect($this->manager->hasAnyFlag([]))->toBeFalse();
});

test('hasAnyFlag returns true when first flag is enabled', function (): void {
    $this->manager->enableFlag('flag1');

    expect($this->manager->hasAnyFlag(['flag1', 'flag2']))->toBeTrue();
});

test('hasAllFlags returns true when all specified flags are enabled', function (): void {
    $this->manager->enableFlag('flag1')
        ->enableFlag('flag2')
        ->enableFlag('flag3');

    expect($this->manager->hasAllFlags(['flag1', 'flag2']))->toBeTrue();
});

test('hasAllFlags returns false when some flags are missing', function (): void {
    $this->manager->enableFlag('flag1');

    expect($this->manager->hasAllFlags(['flag1', 'flag2']))->toBeFalse();
});

test('hasAllFlags returns false when no flags are enabled', function (): void {
    expect($this->manager->hasAllFlags(['flag1', 'flag2']))->toBeFalse();
});

test('hasAllFlags returns true with empty array', function (): void {
    expect($this->manager->hasAllFlags([]))->toBeTrue();
});

test('hasAllFlags returns false when first flag is missing', function (): void {
    $this->manager->enableFlag('flag2');

    expect($this->manager->hasAllFlags(['flag1', 'flag2']))->toBeFalse();
});

test('complex flag operations work together', function (): void {
    // Test a complex scenario combining multiple operations
    $this->manager
        ->enableFlags(['flag1', 'flag2', 'flag3'])
        ->disableFlag('flag2')
        ->toggleFlag('flag4')
        ->toggleFlag('flag1');

    expect($this->manager->getFlags())->toBe([
        'flag3' => true,
        'flag4' => true,
    ])
        ->and($this->manager->hasAnyFlag(['flag1', 'flag3']))->toBeTrue()
        ->and($this->manager->hasAllFlags(['flag3', 'flag4']))->toBeTrue()
        ->and($this->manager->hasAllFlags(['flag1', 'flag3']))->toBeFalse();
});
