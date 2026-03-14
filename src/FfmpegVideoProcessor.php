<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Dto\VideoInfo;
use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\Shell\ShellRunner;

/**
 * FFmpeg-based video processor.
 *
 * Requires: ffmpeg + ffprobe available in $PATH.
 *
 * KPHP-compatible:
 *  - uses ShellRunner (exec()) instead of proc_open / shell_exec
 *  - no GD, no Reflection, no union types, no closures
 *  - ffprobe output parsed with strpos/substr/explode
 */
final class FfmpegVideoProcessor implements VideoProcessorInterface
{
    /** @var ShellRunner */
    private ShellRunner $shell;

    public function __construct(ShellRunner $shell)
    {
        $this->shell = $shell;
    }

    public function probe(string $path): VideoInfo
    {
        if (!file_exists($path)) {
            throw new MediaException('Video file not found: ' . $path);
        }

        $rawSize   = filesize($path);
        $sizeBytes = 0;
        if (is_int($rawSize)) {
            $sizeBytes = $rawSize;
        }

        // ffprobe -v error: suppress banner; -of default: key=value format
        $cmd = 'ffprobe -v error'
            . ' -select_streams v:0'
            . ' -show_entries stream=width,height,codec_name,bit_rate'
            . ' -show_entries format=duration,format_name'
            . ' -of default'
            . ' -i ' . ShellRunner::escapeArg($path);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'ffprobe failed for "' . $path . '": ' . $result->getOutput()
            );
        }

        return $this->parseProbeOutput($result->getOutput(), $path, $sizeBytes);
    }

    public function validateSize(string $path, int $maxBytes): void
    {
        if (!file_exists($path)) {
            throw new MediaException('Video file not found: ' . $path);
        }

        $rawSize   = filesize($path);
        $sizeBytes = 0;
        if (is_int($rawSize)) {
            $sizeBytes = $rawSize;
        }

        if ($sizeBytes > $maxBytes) {
            throw new MediaException(
                'File size ' . $sizeBytes . ' bytes exceeds limit of ' . $maxBytes . ' bytes: ' . $path
            );
        }
    }

    public function compress(string $inputPath, string $outputPath, int $crf): void
    {
        if ($crf < 0 || $crf > 51) {
            throw new MediaException('CRF must be 0–51, got: ' . $crf);
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Input video not found: ' . $inputPath);
        }

        $cmd = 'ffmpeg -y'
            . ' -i ' . ShellRunner::escapeArg($inputPath)
            . ' -c:v libx264'
            . ' -crf ' . $crf
            . ' -preset medium'
            . ' -c:a copy'
            . ' ' . ShellRunner::escapeArg($outputPath);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'ffmpeg compress failed: ' . $result->getOutput()
            );
        }
    }

    public function resize(string $inputPath, string $outputPath, int $maxWidth, int $maxHeight): void
    {
        if ($maxWidth < 1 || $maxHeight < 1) {
            throw new MediaException('Max dimensions must be at least 1px');
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Input video not found: ' . $inputPath);
        }

        // scale filter: fit within maxWidth x maxHeight, preserve aspect ratio
        // -2 rounds to nearest even number (required for libx264)
        $scaleFilter = 'scale='
            . 'iw*min(' . $maxWidth . '/iw\\,' . $maxHeight . '/ih)'
            . ':ih*min(' . $maxWidth . '/iw\\,' . $maxHeight . '/ih)'
            . ':flags=lanczos';

        $cmd = 'ffmpeg -y'
            . ' -i ' . ShellRunner::escapeArg($inputPath)
            . ' -vf ' . ShellRunner::escapeArg($scaleFilter)
            . ' -c:v libx264'
            . ' -crf 23'
            . ' -preset medium'
            . ' -c:a copy'
            . ' ' . ShellRunner::escapeArg($outputPath);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'ffmpeg resize failed: ' . $result->getOutput()
            );
        }
    }

    public function extractThumbnail(string $inputPath, string $outputPath, int $atSecond): void
    {
        if ($atSecond < 0) {
            throw new MediaException('atSecond must be >= 0, got: ' . $atSecond);
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Input video not found: ' . $inputPath);
        }

        $cmd = 'ffmpeg -y'
            . ' -ss ' . $atSecond
            . ' -i ' . ShellRunner::escapeArg($inputPath)
            . ' -frames:v 1'
            . ' -q:v 2'
            . ' ' . ShellRunner::escapeArg($outputPath);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'ffmpeg extractThumbnail failed: ' . $result->getOutput()
            );
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse ffprobe's default key=value output into a VideoInfo object.
     *
     * ffprobe -of default outputs lines like:
     *   width=1920
     *   height=1080
     *   codec_name=h264
     *   bit_rate=4000000
     *   duration=120.500000
     *   format_name=mov,mp4,m4a,3gp,3g2,mj2
     *
     * Lines wrapped in [STREAM] / [FORMAT] / etc. are skipped.
     * KPHP-compatible: uses only strpos/substr/explode/trim.
     */
    private function parseProbeOutput(string $output, string $path, int $sizeBytes): VideoInfo
    {
        $width    = 0;
        $height   = 0;
        $codec    = 'unknown';
        $bitrate  = 0;
        $duration = 0;
        $format   = 'unknown';

        $lines = explode("\n", $output);
        foreach ($lines as $rawLine) {
            $line = trim($rawLine);

            if ($line === '') {
                continue;
            }

            // Skip section markers like [STREAM], [/STREAM], [FORMAT], [/FORMAT]
            if ((string) substr($line, 0, 1) === '[') {
                continue;
            }

            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key = (string) substr($line, 0, $eqPos);
            $val = (string) substr($line, $eqPos + 1);

            if ($key === 'width') {
                $width = (int) $val;
            } elseif ($key === 'height') {
                $height = (int) $val;
            } elseif ($key === 'codec_name') {
                $codec = $val;
            } elseif ($key === 'bit_rate') {
                // ffprobe may return 'N/A'
                if ($val !== 'N/A' && $val !== '') {
                    $bitrate = (int) $val;
                }
            } elseif ($key === 'duration') {
                if ($val !== 'N/A' && $val !== '') {
                    $duration = (int) round((float) $val);
                }
            } elseif ($key === 'format_name') {
                $format = $this->formatToMime($val);
            }
        }

        return new VideoInfo($path, $sizeBytes, $duration, $format, $width, $height, $codec, $bitrate);
    }

    /**
     * Convert ffprobe format_name to a MIME type string.
     * format_name may be a comma-separated list (e.g. "mov,mp4,m4a,3gp,3g2,mj2").
     */
    private function formatToMime(string $formatName): string
    {
        // Take the first entry from the comma-separated list
        $commaPos = strpos($formatName, ',');
        $first    = $commaPos === false ? $formatName : (string) substr($formatName, 0, $commaPos);

        if ($first === 'mp4' || $first === 'mov' || $first === 'm4a') {
            return 'video/mp4';
        }
        if ($first === 'avi') {
            return 'video/avi';
        }
        if ($first === 'webm') {
            return 'video/webm';
        }
        if ($first === 'matroska') {
            return 'video/x-matroska';
        }
        if ($first === 'flv') {
            return 'video/x-flv';
        }

        return 'video/' . $first;
    }
}
