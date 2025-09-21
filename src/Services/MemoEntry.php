<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Services;

final class MemoEntry
{
    public ?MemoEntry $previous = null;

    public ?MemoEntry $next = null;

    private int $hits = 0;

    private int $lastAccess;

    public function __construct(
        private readonly ?string $namespace,
        private readonly string $key,
        private readonly mixed $value
    ) {
        $this->lastAccess = hrtime(true);
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function getLastAccess(): int
    {
        return $this->lastAccess;
    }

    public function markAsAccessed(): void
    {
        $this->hits++;
        $this->lastAccess = hrtime(true);
    }

    /**
     * Detach this entry from the doubly linked list.
     */
    public function detach(): void
    {
        if ($this->previous instanceof self) {
            $this->previous->next = $this->next;
        }

        if ($this->next instanceof self) {
            $this->next->previous = $this->previous;
        }

        $this->previous = null;
        $this->next = null;
    }
}
