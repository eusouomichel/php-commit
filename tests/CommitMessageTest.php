<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Eusouomichel\PhpCommit\CommitMessage;

class CommitMessageTest extends TestCase
{
    public function testGenerateSimpleCommitMessage()
    {
        $message = CommitMessage::generate('feat', 'auth', 'add user login', null, null, null);
        
        $this->assertStringContainsString('feat(auth): add user login', $message);
    }

    public function testGenerateCommitMessageWithDescription()
    {
        $message = CommitMessage::generate(
            'fix',
            'api',
            'resolve authentication issue',
            'Fixed JWT token validation that was causing 401 errors',
            null,
            null
        );
        
        $this->assertStringContainsString('fix(api): resolve authentication issue', $message);
        $this->assertStringContainsString('Fixed JWT token validation', $message);
    }

    public function testGenerateCommitMessageWithBreakingChange()
    {
        $message = CommitMessage::generate(
            'feat',
            'api',
            'update user endpoints',
            'Changed user API structure',
            'User endpoints now require authentication',
            '#123'
        );
        
        $this->assertStringContainsString('feat(api)!: update user endpoints', $message);
        $this->assertStringContainsString('BREAKING CHANGE: User endpoints now require authentication', $message);
        $this->assertStringContainsString('Refs: #123', $message);
    }

    public function testGenerateCommitMessageWithoutContext()
    {
        $message = CommitMessage::generate('docs', '', 'update README', null, null, null);
        
        $this->assertStringContainsString('docs: update README', $message);
        $this->assertStringNotContainsString('():', $message);
    }
}
