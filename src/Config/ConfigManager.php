<?php

namespace Eusouomichel\PhpCommit\Config;

use InvalidArgumentException;
use RuntimeException;

class ConfigManager
{
    private const CONFIG_FILE = 'php-commit.json';
    
    private array $config;
    private array $defaults = [
        'language' => 'en',
        'auto_add_files' => false,
        'auto_push' => false,
        'pre_commit_commands' => [],
        'no_commit_strings' => [],
        'message_limits' => [
            'context' => 20,
            'summary' => 50,
            'description' => 500,
            'breaking_change' => 50,
            'reference' => 50
        ]
    ];

    public function __construct(?string $configPath = null)
    {
        $this->loadConfig($configPath ?? self::CONFIG_FILE);
    }

    public function loadConfig(string $configPath): void
    {
        if (!file_exists($configPath)) {
            throw new RuntimeException("Configuration file '{$configPath}' not found. Run 'php vendor/bin/commit init' first.");
        }

        $content = file_get_contents($configPath);
        if ($content === false) {
            throw new RuntimeException("Failed to read configuration file '{$configPath}'.");
        }

        $config = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException("Invalid JSON in configuration file: " . json_last_error_msg());
        }

        $this->config = array_merge($this->defaults, $config);
        $this->validateConfig();
    }

    private function validateConfig(): void
    {
        // Validate language
        if (!is_string($this->config['language'])) {
            throw new InvalidArgumentException('Language must be a string');
        }

        // Validate boolean settings
        foreach (['auto_add_files', 'auto_push'] as $key) {
            if (!is_bool($this->config[$key])) {
                throw new InvalidArgumentException("{$key} must be a boolean");
            }
        }

        // Validate array settings
        foreach (['pre_commit_commands', 'no_commit_strings'] as $key) {
            if (!is_array($this->config[$key])) {
                throw new InvalidArgumentException("{$key} must be an array");
            }
        }

        // Validate message limits
        if (isset($this->config['message_limits']) && !is_array($this->config['message_limits'])) {
            throw new InvalidArgumentException('message_limits must be an array');
        }
        
        // Validate each limit is a positive integer
        $limits = $this->config['message_limits'] ?? [];
        foreach ($limits as $key => $value) {
            if (!is_int($value) || $value <= 0) {
                throw new InvalidArgumentException("message_limits.{$key} must be a positive integer");
            }
        }
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getLanguage(): string
    {
        return $this->config['language'];
    }

    public function shouldAutoAddFiles(): bool
    {
        return $this->config['auto_add_files'];
    }

    public function shouldAutoPush(): bool
    {
        return $this->config['auto_push'];
    }

    public function getPreCommitCommands(): array
    {
        return $this->config['pre_commit_commands'];
    }

    public function getProhibitedStrings(): array
    {
        return $this->config['no_commit_strings'];
    }

    public function getMessageLimits(): array
    {
        return $this->config['message_limits'] ?? $this->defaults['message_limits'];
    }

    public function getContextLimit(): int
    {
        return $this->getMessageLimits()['context'] ?? $this->defaults['message_limits']['context'];
    }

    public function getSummaryLimit(): int
    {
        return $this->getMessageLimits()['summary'] ?? $this->defaults['message_limits']['summary'];
    }

    public function getDescriptionLimit(): int
    {
        return $this->getMessageLimits()['description'] ?? $this->defaults['message_limits']['description'];
    }

    public function getBreakingChangeLimit(): int
    {
        return $this->getMessageLimits()['breaking_change'] ?? $this->defaults['message_limits']['breaking_change'];
    }

    public function getReferenceLimit(): int
    {
        return $this->getMessageLimits()['reference'] ?? $this->defaults['message_limits']['reference'];
    }

    public function toArray(): array
    {
        return $this->config;
    }

    public static function createDefault(string $configPath = self::CONFIG_FILE): self
    {
        $manager = new self();
        $manager->config = $manager->defaults;
        return $manager;
    }

    public function save(string $configPath = self::CONFIG_FILE): bool
    {
        $json = json_encode($this->config, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Failed to encode configuration to JSON');
        }

        return file_put_contents($configPath, $json) !== false;
    }
}