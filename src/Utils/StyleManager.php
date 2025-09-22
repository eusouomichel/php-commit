<?php

namespace Eusouomichel\PhpCommit\Utils;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class StyleManager
{
    /**
     * Setup all custom styles for the output
     */
    public static function setupStyles(OutputInterface $output): void
    {
        // Question style - blue and bold
        $questionStyle = new OutputFormatterStyle('blue', 'default', ['bold']);
        $output->getFormatter()->setStyle('question', $questionStyle);

        // Danger style - red
        $dangerStyle = new OutputFormatterStyle('red', 'default');
        $output->getFormatter()->setStyle('danger', $dangerStyle);

        // Warning style - yellow
        $warningStyle = new OutputFormatterStyle('yellow', 'default');
        $output->getFormatter()->setStyle('warning', $warningStyle);

        // Success style - green
        $successStyle = new OutputFormatterStyle('green', 'default');
        $output->getFormatter()->setStyle('success', $successStyle);

        // Highlight style - cyan
        $highlightStyle = new OutputFormatterStyle('cyan', 'default');
        $output->getFormatter()->setStyle('highlight', $highlightStyle);

        // Muted style - gray (compatible with older Symfony versions)
        $mutedStyle = new OutputFormatterStyle('default', 'default');
        $output->getFormatter()->setStyle('muted', $mutedStyle);
    }

    /**
     * Get formatted title with emoji
     */
    public static function getFormattedTitle(string $title, string $emoji = '🚀'): string
    {
        return "<question>$emoji $title</question>";
    }

    /**
     * Get formatted success message
     */
    public static function getSuccessMessage(string $message, string $emoji = '✅'): string
    {
        return "<success>$emoji $message</success>";
    }

    /**
     * Get formatted error message
     */
    public static function getErrorMessage(string $message, string $emoji = '❌'): string
    {
        return "<danger>$emoji $message</danger>";
    }

    /**
     * Get formatted warning message
     */
    public static function getWarningMessage(string $message, string $emoji = '⚠️'): string
    {
        return "<warning>$emoji $message</warning>";
    }

    /**
     * Get formatted info message
     */
    public static function getInfoMessage(string $message, string $emoji = 'ℹ️'): string
    {
        return "<info>$emoji $message</info>";
    }

    /**
     * Get formatted step message
     */
    public static function getStepMessage(int $step, string $message): string
    {
        return "<highlight>[$step/6]</highlight> <question>$message</question>";
    }

    /**
     * Display a beautiful header
     */
    public static function displayHeader(OutputInterface $output): void
    {
        $output->writeln([
            '',
            '<question>╔══════════════════════════════════════════════════════════════════════════════════════╗</question>',
            '<question>║                                   🚀 PHP Commit                                      ║</question>',
            '<question>║                        Create beautiful commit messages with ease                    ║</question>',
            '<question>╚══════════════════════════════════════════════════════════════════════════════════════╝</question>',
            ''
        ]);
    }

    /**
     * Display commit type choices with better formatting
     */
    public static function displayCommitTypes(OutputInterface $output, array $choices): void
    {
        $output->writeln('<question>🎯 Choose your commit type:</question>');
        $output->writeln('');
        
        $count = 1;
        foreach ($choices as $key => $description) {
            $paddedNumber = str_pad("[$count]", 4, ' ', STR_PAD_LEFT);
            $output->writeln("  <highlight>$paddedNumber</highlight> <info>$key:</info> $description");
            $count++;
        }
        
        $output->writeln('');
    }

    /**
     * Display character count with color coding
     */
    public static function displayCharacterCount(OutputInterface $output, int $current, int $max): void
    {
        $percentage = ($current / $max) * 100;
        
        if ($percentage >= 90) {
            $style = 'danger';
            $emoji = '🔴';
        } elseif ($percentage >= 70) {
            $style = 'warning';
            $emoji = '🟡';
        } else {
            $style = 'success';
            $emoji = '🟢';
        }
        
        $output->write("  <$style>$emoji $current/$max characters</$style>");
    }
}
