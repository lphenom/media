<?php

declare(strict_types=1);

namespace LPhenom\Media\Shell;

/**
 * Executes shell commands and captures output + exit code.
 *
 * KPHP-compatible implementation notes:
 *  - exec() with ALL 3 parameters conflicts with KPHP's type system
 *    (output array is typed as `mixed` in KPHP stubs → can't assign to string[])
 *  - Solution: exec() with 1 argument only; capture output via temp file;
 *    capture exit code via `echo $?` in a sub-shell wrapper
 *  - file() called with 1 argument (KPHP restriction)
 *  - str_replace cast to (string) — KPHP stubs may return mixed for array overloads
 *  - No union types, no magic, no closures
 */
final class ShellRunner
{
    /**
     * Execute a shell command and return its combined stdout+stderr output
     * together with the process exit code.
     *
     * Uses a temp-file strategy to avoid KPHP's exec(&$output ::: mixed) issue:
     *   1. Wraps command in a subshell, redirects stdout+stderr to a temp file
     *   2. Appends `echo $?` so exec() captures the exit code as its return value
     *   3. Reads the temp file with file() (1-arg KPHP-safe form)
     */
    public function run(string $command): ShellResult
    {
        $tmpFile = '/tmp/lphenom_' . (string) mt_rand(100000, 999999) . '.out';

        // Wrap: redirect output to file, echo exit code to stdout (captured by exec)
        $wrapper  = '(' . $command . ') >' . $tmpFile . ' 2>&1; echo $?';
        $lastLine = exec('/bin/sh -c ' . self::escapeArg($wrapper));

        $exitCode = 1;
        if ($lastLine !== false) {
            $exitCode = (int) trim((string) $lastLine);
        }

        $rawLines = file($tmpFile);

        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }

        /** @var string[] $lines */
        $lines = [];

        if ($rawLines !== false) {
            foreach ($rawLines as $rawLine) {
                $lines[] = rtrim((string) $rawLine, "\r\n");
            }
        }

        return new ShellResult($lines, $exitCode);
    }

    /**
     * Check whether a binary is available in $PATH.
     *
     * @param string $binary e.g. 'ffmpeg', 'ffprobe', 'convert'
     */
    public function isAvailable(string $binary): bool
    {
        $result = $this->run('which ' . self::escapeArg($binary));
        return $result->isSuccess() && count($result->getOutputLines()) > 0;
    }

    /**
     * POSIX-safe single-quote argument escaping.
     *
     * Equivalent to escapeshellarg() on POSIX systems.
     * Implemented manually because escapeshellarg() is not in KPHP's standard library.
     *
     * @param string $arg The argument to escape.
     */
    public static function escapeArg(string $arg): string
    {
        return "'" . (string) str_replace("'", "'\\''", $arg) . "'";
    }
}
