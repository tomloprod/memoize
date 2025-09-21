<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoEntry;
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
    expect($manager->getMemoizedValues())->toHaveCount(3);

    $manager->flush();

    expect($manager->has('key1'))->toBe(false);
    expect($manager->has('key2'))->toBe(false);
    expect($manager->has('key3'))->toBe(false);
    expect($manager->getMemoizedValues())->toHaveCount(0);
});

test('has correctly checks for key existence', function (): void {
    $manager = MemoizeManager::instance();

    expect($manager->has('test_key'))->toBe(false);

    $manager->memo('test_key', fn (): string => 'test_value');
    expect($manager->has('test_key'))->toBe(true);

    $manager->forget('test_key');
    expect($manager->has('test_key'))->toBe(false);
});

test('getMemoizedValues can be used to get keys', function (): void {
    $manager = MemoizeManager::instance();

    expect($manager->getMemoizedValues())->toBe([]);

    $manager->memo('users', fn (): array => ['user1', 'user2']);
    $manager->memo('config', fn (): array => ['setting' => 'value']);
    $manager->memo('stats', fn (): array => ['count' => 100]);

    $entries = $manager->getMemoizedValues();
    $keys = array_keys($entries);
    expect($keys)->toHaveCount(3);
    expect($keys)->toContain('users');
    expect($keys)->toContain('config');
    expect($keys)->toContain('stats');

    $manager->forget('config');
    $entries = $manager->getMemoizedValues();
    $keys = array_keys($entries);
    expect($keys)->toHaveCount(2);
    expect($keys)->not()->toContain('config');
});

test('getMemoizedValues returns all cached entries with full information', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    expect($manager->getMemoizedValues())->toBe([]);

    $manager->memo('users', fn (): array => ['user1', 'user2']);
    $manager->memo('config', fn (): array => ['setting1' => 'value1']);
    $manager->for('App\\Models\\User')->memo('profile', fn (): string => 'user_profile');

    $entries = $manager->getMemoizedValues();
    expect($entries)->toHaveCount(3);

    expect($entries)->toHaveKey('users');
    expect($entries)->toHaveKey('config');
    expect($entries)->toHaveKey('App\\Models\\User::profile');

    expect($entries['users'])->toBeInstanceOf(MemoEntry::class);
    expect($entries['config'])->toBeInstanceOf(MemoEntry::class);
    expect($entries['App\\Models\\User::profile'])->toBeInstanceOf(MemoEntry::class);

    expect($entries['users']->getValue())->toBe(['user1', 'user2']);
    expect($entries['users']->getHits())->toBe(1);
    expect($entries['users']->getLastAccess())->toBeGreaterThan(0);
    expect($entries['users']->getNamespace())->toBeNull();

    expect($entries['App\\Models\\User::profile']->getValue())->toBe('user_profile');
    expect($entries['App\\Models\\User::profile']->getNamespace())->toBe('App\\Models\\User');
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
    expect($manager->getMemoizedValues())->toHaveCount(0);
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

test('setMaxSize and getMaxSize work correctly', function (): void {
    $manager = MemoizeManager::instance();

    expect($manager->getMaxSize())->toBeNull();

    $manager->setMaxSize(3);
    expect($manager->getMaxSize())->toBe(3);

    $manager->setMaxSize(null);
    expect($manager->getMaxSize())->toBeNull();

    $manager->setMaxSize(1);
    expect($manager->getMaxSize())->toBe(1);
});

test('LRU eviction works correctly', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(2);

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');

    expect($manager->has('key1'))->toBe(true);
    expect($manager->has('key2'))->toBe(true);
    expect(count($manager->getMemoizedValues()))->toBe(2);

    $manager->memo('key3', fn (): string => 'value3');

    expect($manager->has('key1'))->toBe(false);
    expect($manager->has('key2'))->toBe(true);
    expect($manager->has('key3'))->toBe(true);
    expect(count($manager->getMemoizedValues()))->toBe(2);
});

test('LRU respects access order', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(2);

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');

    $manager->memo('key1', fn (): string => 'different_value');

    $manager->memo('key3', fn (): string => 'value3');

    expect($manager->has('key1'))->toBe(true);
    expect($manager->has('key2'))->toBe(false);
    expect($manager->has('key3'))->toBe(true);
});

test('setMaxSize enforces LRU immediately', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(null);

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');
    $manager->memo('key3', fn (): string => 'value3');
    $manager->memo('key4', fn (): string => 'value4');

    expect(count($manager->getMemoizedValues()))->toBe(4);

    $manager->setMaxSize(2);

    expect(count($manager->getMemoizedValues()))->toBe(2);

    expect($manager->has('key1'))->toBe(false);
    expect($manager->has('key2'))->toBe(false);
    expect($manager->has('key3'))->toBe(true);
    expect($manager->has('key4'))->toBe(true);
});

test('getStats returns correct information', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(null);

    $stats = $manager->getStats();
    expect($stats['size'])->toBe(0);
    expect($stats['maxSize'])->toBeNull();
    expect($stats['head']['key'])->toBeNull();
    expect($stats['head']['time'])->toBeNull();
    expect($stats['head']['hits'])->toBe(0);
    expect($stats['tail']['key'])->toBeNull();
    expect($stats['tail']['time'])->toBeNull();
    expect($stats['tail']['hits'])->toBe(0);

    $manager->setMaxSize(5);

    $manager->memo('first_key', fn (): string => 'first_value');

    $stats = $manager->getStats();
    expect($stats['size'])->toBe(1);
    expect($stats['maxSize'])->toBe(5);
    expect($stats['head']['key'])->toBe('first_key');
    expect($stats['head']['hits'])->toBe(1);
    expect($stats['tail']['key'])->toBe('first_key');
    expect($stats['tail']['hits'])->toBe(1);

    $manager->memo('second_key', fn (): string => 'second_value');
    $manager->memo('third_key', fn (): string => 'third_value');

    $stats = $manager->getStats();
    expect($stats['size'])->toBe(3);
    expect($stats['head']['key'])->toBe('third_key');
    expect($stats['tail']['key'])->toBe('first_key');

    $manager->memo('first_key', fn (): string => 'different_value');

    $stats = $manager->getStats();
    expect($stats['head']['key'])->toBe('first_key');
    expect($stats['head']['hits'])->toBe(2);
    expect($stats['tail']['key'])->toBe('second_key');
});

test('getStats handles null maxSize correctly', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(null);

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');

    $stats = $manager->getStats();
    expect($stats['maxSize'])->toBeNull();
    expect($stats['size'])->toBe(2);
});

test('LRU works with single entry', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(1);

    $manager->memo('key1', fn (): string => 'value1');
    expect($manager->has('key1'))->toBe(true);

    $manager->memo('key2', fn (): string => 'value2');
    expect($manager->has('key1'))->toBe(false);
    expect($manager->has('key2'))->toBe(true);
    expect(count($manager->getMemoizedValues()))->toBe(1);
});

test('removeTail handles null tail directly', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $reflection = new ReflectionClass($manager);
    $removeTailMethod = $reflection->getMethod('removeTail');
    $removeTailMethod->setAccessible(true);

    $removeTailMethod->invoke($manager);

    expect($manager->getMemoizedValues())->toHaveCount(0);
});

test('LRU handles cache when head and tail are same entry', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(1);

    $manager->memo('single_key', fn (): string => 'single_value');

    $stats = $manager->getStats();
    expect($stats['head']['key'])->toBe('single_key');
    expect($stats['tail']['key'])->toBe('single_key');

    $manager->memo('single_key', fn (): string => 'different_value');

    expect($manager->getMemoizedValues())->toHaveCount(1);
});

test('enforce LRU with no limit returns early', function (): void {
    $manager = MemoizeManager::instance();
    $manager->setMaxSize(null);

    $manager->memo('key1', fn (): string => 'value1');
    $manager->memo('key2', fn (): string => 'value2');
    $manager->memo('key3', fn (): string => 'value3');

    expect($manager->getMemoizedValues())->toHaveCount(3);

    $manager->setMaxSize(3);
    expect($manager->getMemoizedValues())->toHaveCount(3);

    $manager->setMaxSize(5);
    expect($manager->getMemoizedValues())->toHaveCount(3);
});

test('for method returns fluent interface', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $result = $manager->for('App\\Models\\User');
    expect($result)->toBe($manager);
});

test('memo with namespace creates namespaced keys', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $manager->memo('user_123', fn (): string => 'without_namespace');

    $manager->for('App\\Models\\User');
    $manager->memo('user_123', fn (): string => 'with_namespace');

    expect($manager->getMemoizedValues())->toHaveCount(2);
    expect($manager->getMemoizedValues())->toHaveKey('user_123');
    expect($manager->getMemoizedValues())->toHaveKey('App\\Models\\User::user_123');
});

test('memo returns different values for same key with different namespaces', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $value1 = $manager->memo('data', fn (): string => 'no_namespace');

    $manager->for('App\\Models\\User');
    $value2 = $manager->memo('data', fn (): string => 'user_namespace');

    $manager->for('App\\Models\\Product');
    $value3 = $manager->memo('data', fn (): string => 'product_namespace');

    expect($value1)->toBe('no_namespace');
    expect($value2)->toBe('user_namespace');
    expect($value3)->toBe('product_namespace');
    expect($manager->getMemoizedValues())->toHaveCount(3);
});

test('memo with null key and namespace returns null without executing callback', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $callbackExecuted = false;
    $callback = function () use (&$callbackExecuted): string {
        $callbackExecuted = true;

        return 'should_not_execute';
    };

    $manager->for('App\\Models\\User');
    $result = $manager->memo(null, $callback);

    expect($result)->toBeNull();
    expect($callbackExecuted)->toBe(false);
    expect($manager->getMemoizedValues())->toHaveCount(0);
});

test('memo with null key and no namespace throws exception', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $callback = fn (): string => 'test';

    expect(fn (): mixed => $manager->memo(null, $callback))
        ->toThrow(InvalidArgumentException::class, 'Key cannot be null when no namespace is set');
});

test('has method works with namespace', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $manager->memo('test', fn (): string => 'no_namespace');
    $manager->for('App\\Models\\User');
    $manager->memo('test', fn (): string => 'with_namespace');

    expect($manager->has('test'))->toBe(true);

    $manager->for('App\\Models\\User');
    expect($manager->has('test'))->toBe(true);

    $manager->for('App\\Models\\Product');
    expect($manager->has('test'))->toBe(false);
});

test('forget method works with namespace', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $manager->memo('test', fn (): string => 'no_namespace');
    $manager->for('App\\Models\\User');
    $manager->memo('test', fn (): string => 'with_namespace');

    expect($manager->getMemoizedValues())->toHaveCount(2);

    $manager->for('App\\Models\\User');
    $result = $manager->forget('test');
    expect($result)->toBe(true);
    expect($manager->getMemoizedValues())->toHaveCount(1);

    expect($manager->has('test'))->toBe(true);
});

test('flush clears all data', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $manager->for('App\\Models\\User')->memo('test', fn (): string => 'data');
    expect($manager->getMemoizedValues())->toHaveCount(1);

    $manager->flush();
    expect($manager->getMemoizedValues())->toHaveCount(0);
});

test('each operation requires explicit namespace', function (): void {
    $manager = MemoizeManager::instance();
    $manager->flush();

    $manager->for('App\\Models\\User')->memo('key1', fn (): string => 'value1');
    $manager->for('App\\Models\\User')->memo('key2', fn (): string => 'value2');
    $manager->for('App\\Models\\User')->memo('key3', fn (): string => 'value3');

    $entries = $manager->getMemoizedValues();
    expect($entries)->toHaveCount(3);
    expect($entries)->toHaveKey('App\\Models\\User::key1');
    expect($entries)->toHaveKey('App\\Models\\User::key2');
    expect($entries)->toHaveKey('App\\Models\\User::key3');
});
