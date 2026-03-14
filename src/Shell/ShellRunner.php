<?php

declare(strict_types=1);

namespace LPhenom\Media\Shell;

/**
 * Executes shell commands and captures output + exit code.
 *
 * This is the single point of shell interaction in lphenom/media.
 * All processors (FfmpegVideoProcessor, ImageMagickProcessor) use this class
 * instead of calling exec() / shell_exec() directly.
 *
 * KPHP-compatible:
 *  - exec() is supported in KPHP
 *  - escapeArg() is a pure PHP fallback for escapeshellarg()
 *    (escapeshellarg is not in KPHP's standard library)
 *  - No union types, no magic, no closures
 */
final class ShellRunner
{
    /**
     * Execute a shell command and return its combined stdout+stderr output
     * together with the process exit code.
     *
     * The command string must already have all arguments properly escaped
     * using ShellRunner::escapeArg().
     */
    public function run(string $command): ShellResult
    {
        /** @var string[] $lines */
        $lines = [];
        $code  = 0;
        exec($command . ' 2>&1', $lines, $code);
        return new ShellResult($lines, $code);
    }

    /**
     * Check whether a binary is available in $PATH.
     *
     * @param string $binary e.g. 'ffmpeg', 'ffprobe', 'convert'
     */
    public function isAvailable(string $binary): bool
    {
        /** @var string[] $out */
        $out  = [];
        $code = 0;
        exec('which ' . self::escapeArg($binary) . ' 2>/dev/null', $out, $code);
        return $code === 0 && count($out) > 0;
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
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }
}
