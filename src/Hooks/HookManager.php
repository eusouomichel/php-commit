<?php

namespace Eusouomichel\PhpCommit\Hooks;

class HookManager
{
    private array $hooks = [];

    public function addHook(string $event, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$event])) {
            $this->hooks[$event] = [];
        }

        if (!isset($this->hooks[$event][$priority])) {
            $this->hooks[$event][$priority] = [];
        }

        $this->hooks[$event][$priority][] = $callback;
    }

    public function runHook(string $event, array $args = []): array
    {
        if (!isset($this->hooks[$event])) {
            return $args;
        }

        $results = [];
        
        // Sort by priority (lower numbers = higher priority)
        ksort($this->hooks[$event]);

        foreach ($this->hooks[$event] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $result = call_user_func_array($callback, $args);
                if ($result !== null) {
                    $results[] = $result;
                    // If callback returns false, stop execution
                    if ($result === false) {
                        break 2;
                    }
                }
            }
        }

        return $results;
    }

    public function hasHook(string $event): bool
    {
        return isset($this->hooks[$event]) && !empty($this->hooks[$event]);
    }

    public function removeHook(string $event, callable $callback): bool
    {
        if (!isset($this->hooks[$event])) {
            return false;
        }

        foreach ($this->hooks[$event] as $priority => $callbacks) {
            $key = array_search($callback, $callbacks, true);
            if ($key !== false) {
                unset($this->hooks[$event][$priority][$key]);
                
                // Clean up empty arrays
                if (empty($this->hooks[$event][$priority])) {
                    unset($this->hooks[$event][$priority]);
                }
                
                if (empty($this->hooks[$event])) {
                    unset($this->hooks[$event]);
                }
                
                return true;
            }
        }

        return false;
    }

    public function clearHooks(?string $event = null): void
    {
        if ($event === null) {
            $this->hooks = [];
        } else {
            unset($this->hooks[$event]);
        }
    }
}