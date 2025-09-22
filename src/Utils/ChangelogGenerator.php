<?php

namespace Eusouomichel\PhpCommit\Utils;

use Symfony\Component\Process\Process;

class ChangelogGenerator
{
    private const CHANGELOG_FILE = 'CHANGELOG.md';

    public static function generate(string $fromTag = '', string $toTag = 'HEAD'): string
    {
        $commits = self::getCommitsSince($fromTag, $toTag);
        $grouped = self::groupCommitsByType($commits);
        
        return self::formatChangelog($grouped, $toTag);
    }

    private static function getCommitsSince(string $fromTag, string $toTag): array
    {
        $range = empty($fromTag) ? $toTag : "{$fromTag}..{$toTag}";
        
        $process = new Process([
            'git', 'log', $range, 
            '--pretty=format:%H|%s|%b|%an|%ad', 
            '--date=short',
            '--no-merges'
        ]);
        
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Failed to get git log: ' . $process->getErrorOutput());
        }
        
        $commits = [];
        $lines = array_filter(explode("\n", $process->getOutput()));
        
        foreach ($lines as $line) {
            $parts = explode('|', $line, 5);
            if (count($parts) >= 4) {
                $commits[] = [
                    'hash' => $parts[0],
                    'subject' => $parts[1],
                    'body' => $parts[2] ?? '',
                    'author' => $parts[3],
                    'date' => $parts[4] ?? ''
                ];
            }
        }
        
        return $commits;
    }

    private static function groupCommitsByType(array $commits): array
    {
        $groups = [
            'feat' => [],
            'fix' => [],
            'docs' => [],
            'style' => [],
            'refactor' => [],
            'test' => [],
            'chore' => [],
            'perf' => [],
            'build' => [],
            'ci' => [],
            'revert' => [],
            'other' => []
        ];

        foreach ($commits as $commit) {
            $type = self::extractCommitType($commit['subject']);
            $group = $groups[$type] ?? $groups['other'];
            $group[] = $commit;
            $groups[$type] = $group;
        }

        return array_filter($groups); // Remove empty groups
    }

    private static function extractCommitType(string $subject): string
    {
        if (preg_match('/^(\w+)(?:\(.+\))?!?:\s*(.+)$/', $subject, $matches)) {
            return strtolower($matches[1]);
        }
        
        return 'other';
    }

    private static function formatChangelog(array $grouped, string $version): string
    {
        $changelog = "# Changelog\n\n";
        $changelog .= "## [{$version}] - " . date('Y-m-d') . "\n\n";

        $typeLabels = [
            'feat' => '🚀 Features',
            'fix' => '🐛 Bug Fixes',
            'docs' => '📚 Documentation',
            'style' => '💄 Styles',
            'refactor' => '♻️ Code Refactoring',
            'test' => '🧪 Tests',
            'chore' => '🏠 Chores',
            'perf' => '⚡ Performance Improvements',
            'build' => '🏗️ Build System',
            'ci' => '👷 CI',
            'revert' => '⏪ Reverts',
            'other' => '📦 Other Changes'
        ];

        foreach ($grouped as $type => $commits) {
            if (empty($commits)) {
                continue;
            }

            $label = $typeLabels[$type] ?? ucfirst($type);
            $changelog .= "### {$label}\n\n";

            foreach ($commits as $commit) {
                $subject = self::formatCommitSubject($commit['subject']);
                $hash = substr($commit['hash'], 0, 7);
                $changelog .= "- {$subject} ([{$hash}](#{$hash}))\n";
            }

            $changelog .= "\n";
        }

        return $changelog;
    }

    private static function formatCommitSubject(string $subject): string
    {
        // Remove type prefix for cleaner changelog
        return preg_replace('/^(\w+)(?:\(.+\))?!?:\s*/', '', $subject);
    }

    public static function updateChangelogFile(string $content, string $file = self::CHANGELOG_FILE): bool
    {
        $existingContent = '';
        if (file_exists($file)) {
            $existingContent = file_get_contents($file);
        }

        // Merge new content with existing
        if (!empty($existingContent) && strpos($existingContent, '# Changelog') === 0) {
            // Replace the header and add new content
            $lines = explode("\n", $existingContent);
            $newLines = explode("\n", $content);
            
            // Keep everything after the first version section
            $keepFromIndex = 0;
            for ($i = 3; $i < count($lines); $i++) {
                if (preg_match('/^## \[.*?\]/', $lines[$i])) {
                    $keepFromIndex = $i;
                    break;
                }
            }
            
            if ($keepFromIndex > 0) {
                $content = implode("\n", $newLines) . implode("\n", array_slice($lines, $keepFromIndex));
            }
        }

        return file_put_contents($file, $content) !== false;
    }
}