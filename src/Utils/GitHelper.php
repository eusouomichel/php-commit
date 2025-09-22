<?php

namespace Eusouomichel\PhpCommit\Utils;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;

class GitHelper
{
    /**
     * Check if current directory is a Git repository
     */
    public static function isGitRepository(): bool
    {
        $process = new Process(['git', 'rev-parse', '--git-dir']);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Check if there are changes to commit
     */
    public static function hasChanges(): bool
    {
        self::ensureGitRepository();
        
        $process = new Process(['git', 'status', '--porcelain']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Failed to check Git status: ' . $process->getErrorOutput());
        }
        
        return !empty(trim($process->getOutput()));
    }

    /**
     * Ensure we're in a Git repository
     */
    private static function ensureGitRepository(): void
    {
        if (!self::isGitRepository()) {
            throw new RuntimeException('Not a Git repository. Please initialize Git first.');
        }
    }

    /**
     * Get list of files to be committed
     */
    public static function getFilesToCommit(): array
    {
        $process = new Process(['git', 'status', '--porcelain']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            return [];
        }
        
        $files = [];
        foreach (explode("\n", trim($process->getOutput())) as $line) {
            if (!empty($line)) {
                $file = preg_replace('/^[A-Z?]+\s+/', '', $line);
                if ($file) {
                    $files[] = $file;
                }
            }
        }
        
        return $files;
    }

    /**
     * Get current branch name
     */
    public static function getCurrentBranch(): string
    {
        $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        
        return trim($process->getOutput());
    }

    /**
     * Add all files to staging
     */
    public static function addAllFiles(): bool
    {
        $process = new Process(['git', 'add', '-A']);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Create a commit with the given message
     */
    public static function commit(string $message): bool
    {
        $process = new Process(['git', 'commit', '-m', $message]);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Push current branch to remote
     */
    public static function pushBranch(): bool
    {
        $branch = self::getCurrentBranch();
        $process = new Process(['git', 'push', '--set-upstream', 'origin', $branch]);
        $process->run();
        
        return $process->isSuccessful();
    }

    /**
     * Run a command and return the output
     */
    public static function runCommand(string $command): array
    {
        $process = Process::fromShellCommandline($command);
        $process->run();
        
        return [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput()
        ];
    }
}
