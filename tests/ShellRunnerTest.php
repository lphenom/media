<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Shell\ShellRunner;
use PHPUnit\Framework\TestCase;

final class ShellRunnerTest extends TestCase
{
    public function testRunSuccessfulCommand(): void
    {
        $runner = new ShellRunner();
        $result = $runner->run('echo hello');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(0, $result->getExitCode());
        $this->assertStringContainsString('hello', $result->getOutput());
    }

    public function testRunFailingCommand(): void
    {
        $runner = new ShellRunner();
        $result = $runner->run('ls /absolutely/nonexistent/directory/xyz123');

        $this->assertFalse($result->isSuccess());
        $this->assertNotSame(0, $result->getExitCode());
    }

    public function testRunCapturesMultipleOutputLines(): void
    {
        $runner = new ShellRunner();
        $result = $runner->run('printf "a\nb\nc"');

        $lines = $result->getOutputLines();
        $this->assertGreaterThanOrEqual(1, count($lines));
    }

    public function testIsAvailableForEcho(): void
    {
        $runner = new ShellRunner();
        // 'echo' is always available as a shell builtin / external binary
        $this->assertTrue($runner->isAvailable('echo') || $runner->isAvailable('true'));
    }

    public function testIsAvailableReturnsFalseForNonExistentBinary(): void
    {
        $runner = new ShellRunner();
        $this->assertFalse($runner->isAvailable('this_binary_does_not_exist_xyz_' . uniqid('', true)));
    }

    public function testEscapeArgWrapsInSingleQuotes(): void
    {
        $escaped = ShellRunner::escapeArg('hello world');
        $this->assertSame("'hello world'", $escaped);
    }

    public function testEscapeArgEscapesInternalSingleQuotes(): void
    {
        $escaped = ShellRunner::escapeArg("it's a test");
        // Should produce: 'it'\''s a test'
        $this->assertStringContainsString("'", $escaped);
        // Verify it actually works in shell
        $runner = new ShellRunner();
        $result = $runner->run('echo ' . ShellRunner::escapeArg("it's a test"));
        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString("it's a test", $result->getOutput());
    }

    public function testEscapeArgWithPathContainingSpaces(): void
    {
        $escaped = ShellRunner::escapeArg('/path/to/my file.mp4');
        $this->assertSame("'/path/to/my file.mp4'", $escaped);
    }
}
