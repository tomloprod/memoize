<?php

declare(strict_types=1);

namespace Tomloprod\Memoize\Concerns;

/**
 * Trait for handling flag-related functionalities.
 */
trait HasFlags
{
    /** @var array<string, bool> */
    private array $enabledFlags = [];

    /**
     * Enable a specific flag.
     */
    public function enableFlag(string $flag): self
    {
        $this->enabledFlags[$flag] = true;

        return $this;
    }

    /**
     * Disable a specific flag.
     */
    public function disableFlag(string $flag): self
    {
        unset($this->enabledFlags[$flag]);

        return $this;
    }

    /**
     * Toggle the state of a flag (enabled/disabled).
     */
    public function toggleFlag(string $flag): self
    {
        if (isset($this->enabledFlags[$flag])) {
            unset($this->enabledFlags[$flag]);
        } else {
            $this->enabledFlags[$flag] = true;
        }

        return $this;
    }

    /**
     * Check if a specific flag is enabled.
     */
    public function hasFlag(string $flag): bool
    {
        return isset($this->enabledFlags[$flag]);
    }

    /**
     * Get all currently enabled flags.
     *
     * @return array<string, bool> Associative array with enabled flags
     */
    public function getFlags(): array
    {
        return $this->enabledFlags;
    }

    /**
     * Enable multiple flags at once.
     *
     * @param  array<string>  $flags  Array of flag names to enable
     */
    public function enableFlags(array $flags): self
    {
        foreach ($flags as $flag) {
            $this->enabledFlags[$flag] = true;
        }

        return $this;
    }

    /**
     * Disable multiple flags at once.
     *
     * @param  array<string>  $flags  Array of flag names to disable
     */
    public function disableFlags(array $flags): self
    {
        foreach ($flags as $flag) {
            unset($this->enabledFlags[$flag]);
        }

        return $this;
    }

    /**
     * Clear all enabled flags.
     */
    public function clearFlags(): self
    {
        $this->enabledFlags = [];

        return $this;
    }

    /**
     * Check if at least one of the specified flags is enabled.
     *
     * @param  array<string>  $flags  Array of flag names to check
     */
    public function hasAnyFlag(array $flags): bool
    {
        foreach ($flags as $flag) {
            if (isset($this->enabledFlags[$flag])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all specified flags are enabled.
     *
     * @param  array<string>  $flags  Array of flag names to check
     */
    public function hasAllFlags(array $flags): bool
    {
        foreach ($flags as $flag) {
            if (! isset($this->enabledFlags[$flag])) {
                return false;
            }
        }

        return true;
    }
}
