<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Eusouomichel\PhpCommit\CommitMessage;
use InvalidArgumentException;

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

    public function testInvalidCommitType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid commit type: invalid');
        
        CommitMessage::generate('invalid', 'scope', 'subject');
    }

    public function testEmptySubject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commit subject cannot be empty');
        
        CommitMessage::generate('feat', 'scope', '');
    }

    public function testSubjectTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Commit subject cannot exceed 50 characters');
        
        $longSubject = str_repeat('a', 51);
        CommitMessage::generate('feat', 'scope', $longSubject);
    }

    public function testBackwardCompatibilityWithTypeString()
    {
        $message = CommitMessage::generate('feat: A new feature', 'auth', 'add login');
        
        $this->assertStringContainsString('feat(auth): add login', $message);
    }

    public function testCommitMessageObject()
    {
        $commit = new CommitMessage('feat', 'auth', 'add login', 'Added user authentication', 'API changed', '#123');
        
        $this->assertEquals('feat', $commit->getType());
        $this->assertEquals('auth', $commit->getScope());
        $this->assertEquals('add login', $commit->getSubject());
        $this->assertEquals('Added user authentication', $commit->getBody());
        $this->assertEquals('API changed', $commit->getBreakingChange());
        $this->assertEquals('#123', $commit->getFooter());
        $this->assertTrue($commit->isBreaking());
    }
}
