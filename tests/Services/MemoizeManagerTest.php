<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoizeManager;

beforeEach(function (): void {
    MemoizeManager::instance()->flush();
});

test('throws exception on clone', function (): void {
    $instance = MemoizeManager::instance();

    $closure = fn (): mixed => clone $instance;

    expect($closure)->toThrow(Exception::class, 'Cannot clone singleton');
});

test('throws exception on unserialize', function (): void {
    $instance = MemoizeManager::instance();

    $closure = fn (): mixed => unserialize(serialize($instance));

    expect($closure)->toThrow(Exception::class, 'Cannot unserialize singleton');
});

test('returns the same instance', function (): void {
    $instance1 = MemoizeManager::instance();
    $instance2 = MemoizeManager::instance();

    expect($instance1)->toBe($instance2);
});

test('memo executes callback on first call', function (): void {
    $manager = MemoizeManager::instance();

    $callCount = 0;
    $callback = function () use (&$callCount): string {
        $callCount++;

        return 'callback_result_'.$callCount;
    };

    $result = $manager->memo('test_key', $callback);

    expect($result)->toBe('callback_result_1');
    expect($callCount)->toBe(1);
});

test('memo returns memoized value on subsequent calls', function (): void {
    $manager = MemoizeManager::instance();

    $callCount = 0;
    $callback = function () use (&$callCount): string {
        $callCount++;

        return 'callback_result_'.$callCount;
    };

    $result1 = $manager->memo('test_key_2', $callback);
    expect($result1)->toBe('callback_result_1');
    expect($callCount)->toBe(1);

    $result2 = $manager->memo('test_key_2', $callback);
    expect($result2)->toBe('callback_result_1');
    expect($callCount)->toBe(1);

    $result3 = $manager->memo('test_key_2', $callback);
    expect($result3)->toBe('callback_result_1');
    expect($callCount)->toBe(1);
});

test('memo with different keys are independent', function (): void {
    $manager = MemoizeManager::instance();

    $counter1 = 0;
    $callback1 = function () use (&$counter1): string {
        $counter1++;

        return 'result_key1_'.$counter1;
    };

    $counter2 = 0;
    $callback2 = function () use (&$counter2): string {
        $counter2++;

        return 'result_key2_'.$counter2;
    };

    $result1 = $manager->memo('key1', $callback1);
    $result2 = $manager->memo('key2', $callback2);

    expect($result1)->toBe('result_key1_1');
    expect($result2)->toBe('result_key2_1');
    expect($counter1)->toBe(1);
    expect($counter2)->toBe(1);

    $result1_repeat = $manager->memo('key1', $callback1);
    $result2_repeat = $manager->memo('key2', $callback2);

    expect($result1_repeat)->toBe('result_key1_1');
    expect($result2_repeat)->toBe('result_key2_1');
    expect($counter1)->toBe(1);
    expect($counter2)->toBe(1);
});

test('memo works with different return types', function (): void {
    $manager = MemoizeManager::instance();

    $arrayResult = $manager->memo('array_key', fn (): array => ['key' => 'value']);
    expect($arrayResult)->toBe(['key' => 'value']);
    expect($manager->memo('array_key', fn (): array => ['different' => 'value']))->toBe(['key' => 'value']);

    $obj = (object) ['prop' => 'value'];
    $objectResult = $manager->memo('object_key', fn () => $obj);
    expect($objectResult)->toBe($obj);
    expect($manager->memo('object_key', fn () => (object) ['different' => 'prop']))->toBe($obj);

    $nullResult = $manager->memo('null_key', fn (): null => null);
    expect($nullResult)->toBeNull();
    expect($manager->memo('null_key', fn (): string => 'not_null'))->toBeNull();

    $boolResult = $manager->memo('bool_key', fn (): true => true);
    expect($boolResult)->toBe(true);
    expect($manager->memo('bool_key', fn (): false => false))->toBe(true);
});

test('forget removes specific memoized value', function (): void {
    $manager = MemoizeManager::instance();

    $result1 = $manager->memo('user_123', fn (): string => 'User 123 data');
    $result2 = $manager->memo('user_456', fn (): string => 'User 456 data');

    expect($manager->has('user_123'))->toBe(true);
    expect($manager->has('user_456'))->toBe(true);

    $forgot = $manager->forget('user_123');
    expect($forgot)->toBe(true);

    expect($manager->has('user_123'))->toBe(false);
    expect($manager->has('user_456'))->toBe(true);

    $forgotNonExistent = $manager->forget('non_existent');
    expect($forgotNonExistent)->toBe(false);
});

test('flush clears all memoized values', function (): void {
    $manager = MemoizeManager::instance();

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');
    $manager->memo('key3', fn (): string => 'value3');

    expect($manager->has('key1'))->toBe(true);
    expect($manager->has('key2'))->toBe(true);
    expect($manager->has('key3'))->toBe(true);
    expect($manager->keys())->toHaveCount(3);

    $manager->flush();

    expect($manager->has('key1'))->toBe(false);
    expect($manager->has('key2'))->toBe(false);
    expect($manager->has('key3'))->toBe(false);
    expect($manager->keys())->toHaveCount(0);
});

test('has correctly checks for key existence', function (): void {
    $manager = MemoizeManager::instance();

    expect($manager->has('test_key'))->toBe(false);

    $manager->memo('test_key', fn (): string => 'test_value');
    expect($manager->has('test_key'))->toBe(true);

    $manager->forget('test_key');
    expect($manager->has('test_key'))->toBe(false);
});

test('keys returns all cached keys', function (): void {
    $manager = MemoizeManager::instance();

    expect($manager->keys())->toBe([]);

    $manager->memo('users', fn (): array => ['user1', 'user2']);
    $manager->memo('config', fn (): array => ['setting' => 'value']);
    $manager->memo('stats', fn (): array => ['count' => 100]);

    $keys = $manager->keys();
    expect($keys)->toHaveCount(3);
    expect($keys)->toContain('users');
    expect($keys)->toContain('config');
    expect($keys)->toContain('stats');

    $manager->forget('config');
    $keys = $manager->keys();
    expect($keys)->toHaveCount(2);
    expect($keys)->not()->toContain('config');
});

test('memo cache operations work together', function (): void {
    $manager = MemoizeManager::instance();

    $callCount = 0;
    $callback = function () use (&$callCount): string {
        $callCount++;

        return 'result_'.$callCount;
    };

    $result1 = $manager->memo('test_key', $callback);
    expect($result1)->toBe('result_1');
    expect($callCount)->toBe(1);
    expect($manager->has('test_key'))->toBe(true);

    $result2 = $manager->memo('test_key', $callback);
    expect($result2)->toBe('result_1');
    expect($callCount)->toBe(1);

    $manager->forget('test_key');
    expect($manager->has('test_key'))->toBe(false);

    $result3 = $manager->memo('test_key', $callback);
    expect($result3)->toBe('result_2');
    expect($callCount)->toBe(2);

    $manager->flush();
    expect($manager->has('test_key'))->toBe(false);
    expect($manager->keys())->toHaveCount(0);
});

test('once executes callback only on first call', function (): void {
    $manager = MemoizeManager::instance();

    $callCount = 0;
    $callback = function () use (&$callCount): string {
        $callCount++;

        return 'execution_'.$callCount;
    };

    $onceFn = $manager->once($callback);

    $result1 = $onceFn();
    expect($result1)->toBe('execution_1');
    expect($callCount)->toBe(1);

    $result2 = $onceFn();
    expect($result2)->toBe('execution_1');
    expect($callCount)->toBe(1);

    $result3 = $onceFn();
    expect($result3)->toBe('execution_1');
    expect($callCount)->toBe(1);
});

test('once functions are independent from each other', function (): void {
    $manager = MemoizeManager::instance();

    $counter1 = 0;
    $callback1 = function () use (&$counter1): string {
        $counter1++;

        return 'func1_call_'.$counter1;
    };

    $counter2 = 0;
    $callback2 = function () use (&$counter2): string {
        $counter2++;

        return 'func2_call_'.$counter2;
    };

    $onceFn1 = $manager->once($callback1);
    $onceFn2 = $manager->once($callback2);

    $result1 = $onceFn1();
    $result2 = $onceFn2();

    expect($result1)->toBe('func1_call_1');
    expect($result2)->toBe('func2_call_1');
    expect($counter1)->toBe(1);
    expect($counter2)->toBe(1);

    $result1_repeat = $onceFn1();
    $result2_repeat = $onceFn2();

    expect($result1_repeat)->toBe('func1_call_1');
    expect($result2_repeat)->toBe('func2_call_1');
    expect($counter1)->toBe(1);
    expect($counter2)->toBe(1);
});

test('once works with different return types', function (): void {
    $manager = MemoizeManager::instance();

    $stringFn = $manager->once(fn (): string => 'hello world');
    expect($stringFn())->toBe('hello world');
    expect($stringFn())->toBe('hello world');

    $intFn = $manager->once(fn (): int => 42);
    expect($intFn())->toBe(42);
    expect($intFn())->toBe(42);

    $arrayData = ['name' => 'John', 'age' => 30];
    $arrayFn = $manager->once(fn (): array => $arrayData);
    expect($arrayFn())->toBe($arrayData);
    expect($arrayFn())->toBe($arrayData);

    $obj = (object) ['id' => 1, 'title' => 'Test'];
    $objectFn = $manager->once(fn () => $obj);
    $obj1 = $objectFn();
    $obj2 = $objectFn();
    expect($obj1)->toBe($obj);
    expect($obj2)->toBe($obj);
    expect($obj1)->toBe($obj2);

    $trueFn = $manager->once(fn (): true => true);
    expect($trueFn())->toBe(true);
    expect($trueFn())->toBe(true);

    $falseFn = $manager->once(fn (): false => false);
    expect($falseFn())->toBe(false);
    expect($falseFn())->toBe(false);

    $nullFn = $manager->once(fn (): null => null);
    expect($nullFn())->toBeNull();
    expect($nullFn())->toBeNull();

    $floatFn = $manager->once(fn (): float => 3.14159);
    expect($floatFn())->toBe(3.14159);
    expect($floatFn())->toBe(3.14159);
});

test('once handles expensive operations correctly', function (): void {
    $manager = MemoizeManager::instance();

    $expensiveCallCount = 0;
    $expensiveFn = $manager->once(function () use (&$expensiveCallCount): string {
        $expensiveCallCount++;
        usleep(1000);

        return 'expensive_result_'.$expensiveCallCount;
    });

    $startTime = microtime(true);
    $result1 = $expensiveFn();
    $firstCallTime = microtime(true) - $startTime;

    expect($result1)->toBe('expensive_result_1');
    expect($expensiveCallCount)->toBe(1);

    $startTime = microtime(true);
    $result2 = $expensiveFn();
    $secondCallTime = microtime(true) - $startTime;

    expect($result2)->toBe('expensive_result_1');
    expect($expensiveCallCount)->toBe(1);
    expect($secondCallTime)->toBeLessThan($firstCallTime);
});

test('once works with closures that capture variables', function (): void {
    $manager = MemoizeManager::instance();

    $baseValue = 'captured_';
    $suffix = 'variable';
    $callCount = 0;

    $capturingFn = $manager->once(function () use ($baseValue, $suffix, &$callCount): string {
        $callCount++;

        return $baseValue.$suffix.'_'.$callCount;
    });

    $result1 = $capturingFn();
    expect($result1)->toBe('captured_variable_1');
    expect($callCount)->toBe(1);

    $baseValue = 'different_';
    $suffix = 'value';

    $result2 = $capturingFn();
    expect($result2)->toBe('captured_variable_1');
    expect($callCount)->toBe(1);
});

test('once with complex data structures', function (): void {
    $manager = MemoizeManager::instance();

    $complexFn = $manager->once(fn (): array => [
        'users' => [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ],
        'meta' => [
            'total' => 2,
            'timestamp' => time(),
        ],
    ]);

    $result1 = $complexFn();
    $result2 = $complexFn();

    expect($result2)->toBe($result1);
    expect($result2['users'])->toBe($result1['users']);
    expect($result2['meta']['timestamp'])->toBe($result1['meta']['timestamp']);
});

test('once functions maintain scope isolation', function (): void {
    $manager = MemoizeManager::instance();

    $createCounter = function (string $prefix) use ($manager): callable {
        $count = 0;

        return $manager->once(function () use ($prefix, &$count): string {
            $count++;

            return $prefix.'_'.$count;
        });
    };

    $counter1 = $createCounter('counter1');
    $counter2 = $createCounter('counter2');

    expect($counter1())->toBe('counter1_1');
    expect($counter2())->toBe('counter2_1');

    expect($counter1())->toBe('counter1_1');
    expect($counter2())->toBe('counter2_1');

    expect($counter1())->not()->toBe($counter2());
});
