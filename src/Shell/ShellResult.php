<?php

declare(strict_types=1);

namespace LPhenom\Media\Shell;

/**
 * Value object representing the result of a shell command execution.
 *
 * KPHP-compatible: explicit property types, no union types, no magic.
 */
final class ShellResult
{
    /** @var string[] */
    private array $outputLines;

    /** @var int */
    private int $exitCode;

    /**
     * @param string[] $outputLines Lines of captured stdout+stderr.
     * @param int      $exitCode    Process exit code (0 = success).
     */
    public function __construct(array $outputLines, int $exitCode)
    {
        $this->outputLines = $outputLines;
        $this->exitCode    = $exitCode;
    }

    /** Join all output lines into a single string. */
    public function getOutput(): string
    {
        return implode("\n", $this->outputLines);
    }

    /**
     * @return string[]
     */
    public function getOutputLines(): array
    {
        return $this->outputLines;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function isSuccess(): bool
    {
        return $this->exitCode === 0;
    }
}
