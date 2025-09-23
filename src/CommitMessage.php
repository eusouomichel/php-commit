<?php

namespace Eusouomichel\PhpCommit;

use Eusouomichel\PhpCommit\Models\CommitType;
use InvalidArgumentException;

class CommitMessage
{
    private string $type;
    private ?string $scope;
    private string $subject;
    private ?string $body;
    private ?string $breakingChange;
    private ?string $footer;
    private bool $isBreaking = false;

    public function __construct(
        string $type,
        ?string $scope,
        string $subject,
        ?string $body = null,
        ?string $breakingChange = null,
        ?string $footer = null,
        int $summaryLimit = 50
    ) {
        $this->setType($type);
        $this->setScope($scope);
        $this->setSubject($subject, $summaryLimit);
        $this->body = $body;
        $this->setBreakingChange($breakingChange);
        $this->footer = $footer;
    }

    public static function generate($type, $context, $summary, $description = null, $breakingChange = null, $reference = null, int $summaryLimit = 50): string
    {
        // Backward compatibility - extract type from string if needed
        if (strpos($type, ':') !== false) {
            $parts = explode(':', $type, 2);
            $type = trim($parts[0]);
        }

        $commitMessage = new self($type, $context, $summary, $description, $breakingChange, $reference, $summaryLimit);
        return $commitMessage->toString();
    }

    private function setType(string $type): void
    {
        $type = trim($type);
        if (!CommitType::isValid($type)) {
            throw new InvalidArgumentException("Invalid commit type: {$type}");
        }
        $this->type = $type;
    }

    private function setScope(?string $scope): void
    {
        $this->scope = !empty($scope) ? trim($scope) : null;
    }

    private function setSubject(string $subject, int $limit = 50): void
    {
        $subject = trim($subject);
        if (empty($subject)) {
            throw new InvalidArgumentException('Commit subject cannot be empty');
        }
        if (strlen($subject) > $limit) {
            throw new InvalidArgumentException("Commit subject cannot exceed {$limit} characters");
        }
        $this->subject = $subject;
    }

    private function setBreakingChange(?string $breakingChange): void
    {
        if (!empty($breakingChange)) {
            $this->breakingChange = trim($breakingChange);
            $this->isBreaking = true;
        } else {
            $this->breakingChange = null;
        }
    }

    public function toString(): string
    {
        $header = $this->buildHeader();
        $body = $this->buildBody();
        $footer = $this->buildFooter();

        $parts = array_filter([$header, $body, $footer]);
        return implode("\n\n", $parts);
    }

    private function buildHeader(): string
    {
        $scopePart = $this->scope ? "({$this->scope})" : '';
        $breakingPart = $this->isBreaking ? '!' : '';
        
        return "{$this->type}{$scopePart}{$breakingPart}: {$this->subject}";
    }

    private function buildBody(): ?string
    {
        $bodyParts = [];
        
        if ($this->body) {
            $bodyParts[] = trim($this->body);
        }
        
        if ($this->breakingChange) {
            $bodyParts[] = "BREAKING CHANGE: {$this->breakingChange}";
        }
        
        return !empty($bodyParts) ? implode("\n\n", $bodyParts) : null;
    }

    private function buildFooter(): ?string
    {
        return $this->footer ? "Refs: {$this->footer}" : null;
    }

    // Getters for testing
    public function getType(): string { return $this->type; }
    public function getScope(): ?string { return $this->scope; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): ?string { return $this->body; }
    public function getBreakingChange(): ?string { return $this->breakingChange; }
    public function getFooter(): ?string { return $this->footer; }
    public function isBreaking(): bool { return $this->isBreaking; }
}
