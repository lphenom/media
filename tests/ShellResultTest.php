<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Shell\ShellResult;
use PHPUnit\Framework\TestCase;

final class ShellResultTest extends TestCase
{
    public function testGetOutputJoinsLines(): void
    {
        $result = new ShellResult(['line one', 'line two', 'line three'], 0);
        $this->assertSame("line one\nline two\nline three", $result->getOutput());
    }

    public function testGetOutputLinesReturnsArray(): void
    {
        $lines  = ['a', 'b'];
        $result = new ShellResult($lines, 0);
        $this->assertSame($lines, $result->getOutputLines());
    }

    public function testIsSuccessForExitCodeZero(): void
    {
        $result = new ShellResult([], 0);
        $this->assertTrue($result->isSuccess());
    }

    public function testIsSuccessFailsForNonZeroExitCode(): void
    {
        $result = new ShellResult([], 1);
        $this->assertFalse($result->isSuccess());
    }

    public function testGetExitCode(): void
    {
        $result = new ShellResult([], 42);
        $this->assertSame(42, $result->getExitCode());
    }

    public function testEmptyOutputLines(): void
    {
        $result = new ShellResult([], 0);
        $this->assertSame('', $result->getOutput());
        $this->assertSame([], $result->getOutputLines());
    }
}
