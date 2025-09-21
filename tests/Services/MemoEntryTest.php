<?php

declare(strict_types=1);

use Tomloprod\Memoize\Services\MemoEntry;

test('creates entry with correct key and value', function (): void {
    $entry = new MemoEntry(null, 'test_key', 'test_value');

    expect($entry->getNamespace())->toBeNull();
    expect($entry->getKey())->toBe('test_key');
    expect($entry->getValue())->toBe('test_value');
});

test('creates entry with namespace', function (): void {
    $entry = new MemoEntry('App\\Models\\User', 'test_key', 'test_value');

    expect($entry->getNamespace())->toBe('App\\Models\\User');
    expect($entry->getKey())->toBe('test_key');
    expect($entry->getValue())->toBe('test_value');
});

test('tracks hits correctly', function (): void {
    $entry = new MemoEntry(null, 'test_key', 'test_value');

    expect($entry->getHits())->toBe(0);

    $entry->markAsAccessed();
    expect($entry->getHits())->toBe(1);

    $entry->markAsAccessed();
    expect($entry->getHits())->toBe(2);

    $entry->markAsAccessed();
    expect($entry->getHits())->toBe(3);
});

test('tracks last access time', function (): void {
    $timeBefore = hrtime(true);
    $entry = new MemoEntry(null, 'test_key', 'test_value');
    $timeAfter = hrtime(true);

    $lastAccess = $entry->getLastAccess();
    expect($lastAccess)->toBeGreaterThanOrEqual($timeBefore);
    expect($lastAccess)->toBeLessThanOrEqual($timeAfter);

    usleep(1000);

    $entry->markAsAccessed();
    $newLastAccess = $entry->getLastAccess();
    expect($newLastAccess)->toBeGreaterThan($lastAccess);
});

test('detach works with single entry', function (): void {
    $entry = new MemoEntry(null, 'test_key', 'test_value');

    $entry->detach();

    expect($entry->previous)->toBeNull();
    expect($entry->next)->toBeNull();
});

test('detach works with linked list', function (): void {
    $entry1 = new MemoEntry(null, 'key1', 'value1');
    $entry2 = new MemoEntry(null, 'key2', 'value2');
    $entry3 = new MemoEntry(null, 'key3', 'value3');

    $entry1->next = $entry2;
    $entry2->previous = $entry1;
    $entry2->next = $entry3;
    $entry3->previous = $entry2;

    $entry2->detach();

    expect($entry2->previous)->toBeNull();
    expect($entry2->next)->toBeNull();

    expect($entry1->next)->toBe($entry3);
    expect($entry3->previous)->toBe($entry1);
});

test('detach works when entry is at the beginning', function (): void {
    $entry1 = new MemoEntry(null, 'key1', 'value1');
    $entry2 = new MemoEntry(null, 'key2', 'value2');

    $entry1->next = $entry2;
    $entry2->previous = $entry1;

    $entry1->detach();

    expect($entry1->previous)->toBeNull();
    expect($entry1->next)->toBeNull();
    expect($entry2->previous)->toBeNull();
});

test('detach works when entry is at the end', function (): void {
    $entry1 = new MemoEntry(null, 'key1', 'value1');
    $entry2 = new MemoEntry(null, 'key2', 'value2');

    $entry1->next = $entry2;
    $entry2->previous = $entry1;

    $entry2->detach();

    expect($entry2->previous)->toBeNull();
    expect($entry2->next)->toBeNull();
    expect($entry1->next)->toBeNull();
});

test('works with different value types', function (): void {
    $arrayEntry = new MemoEntry(null, 'array_key', ['data' => 'value']);
    expect($arrayEntry->getValue())->toBe(['data' => 'value']);

    $objectEntry = new MemoEntry(null, 'object_key', (object) ['prop' => 'value']);
    expect($objectEntry->getValue())->toEqual((object) ['prop' => 'value']);

    $nullEntry = new MemoEntry(null, 'null_key', null);
    expect($nullEntry->getValue())->toBeNull();

    $intEntry = new MemoEntry(null, 'int_key', 42);
    expect($intEntry->getValue())->toBe(42);

    $boolEntry = new MemoEntry(null, 'bool_key', true);
    expect($boolEntry->getValue())->toBe(true);
});
