<?php

namespace Eusouomichel\PhpCommit;

class CommitMessage
{
    public static function generate($type, $context, $summary, $description = null, $breakingChange = null, $reference = null)
    {
        $parts = explode(":", $type);
        $type = trim($parts[0]);

        $context = empty($context) ? '' : '(' . trim($context) . ')';
        $summary = empty($summary) ? '' : trim($summary) . "\n";
        $description = empty($description) ? '' : "\n" . trim($description) . "\n";
        $reference = empty($reference) ? '' : "\nRefs: " . trim($reference);

        $isbreakChange = null;
        if (!empty($breakingChange)) {
            $breakingChange = "\nBREAKING CHANGE: " . trim($breakingChange) . "\n";
            $isbreakChange = '!';
        }

        return "$type$context$isbreakChange: $summary$description$breakingChange$reference";
    }
}
