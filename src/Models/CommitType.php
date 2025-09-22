<?php

namespace Eusouomichel\PhpCommit\Models;

class CommitType
{
    public const FEAT = 'feat';
    public const FIX = 'fix';
    public const DOCS = 'docs';
    public const STYLE = 'style';
    public const REFACTOR = 'refactor';
    public const TEST = 'test';
    public const CHORE = 'chore';
    public const BUILD = 'build';
    public const PERF = 'perf';
    public const CI = 'ci';
    public const REVERT = 'revert';

    private static array $types = [
        self::FEAT => 'A new feature',
        self::FIX => 'Bug fix',
        self::DOCS => 'Documentation changes',
        self::STYLE => 'Style changes (formatting, spacing, etc.)',
        self::REFACTOR => 'Code refactoring without behavior changes',
        self::TEST => 'Adding or modifying tests',
        self::CHORE => 'Tasks like dependency maintenance',
        self::BUILD => 'Changes in build scripts or dependencies',
        self::PERF => 'Performance improvements',
        self::CI => 'Changes to CI configuration files',
        self::REVERT => 'Revert a previous commit',
    ];

    public static function getAll(): array
    {
        return self::$types;
    }

    public static function getDescription(string $type): string
    {
        return self::$types[$type] ?? 'Unknown type';
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::$types);
    }

    public static function getKeys(): array
    {
        return array_keys(self::$types);
    }
}