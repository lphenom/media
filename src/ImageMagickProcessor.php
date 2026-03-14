<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\Shell\ShellRunner;

/**
 * ImageMagick-based image processor using the `convert` CLI tool.
 *
 * Requires: ImageMagick `convert` available in $PATH.
 * Supports all formats that ImageMagick understands (JPEG, PNG, GIF, WebP, TIFF, BMP, …).
 *
 * KPHP-compatible:
 *  - uses ShellRunner (exec()) — no GD extension required
 *  - no Reflection, no union types, no closures, no magic
 *
 * This is the preferred implementation in KPHP binaries where PHP extensions
 * are not available.
 */
final class ImageMagickProcessor implements ImageProcessorInterface
{
    /** @var ShellRunner */
    private ShellRunner $shell;

    public function __construct(ShellRunner $shell)
    {
        $this->shell = $shell;
    }

    /**
     * Create a thumbnail that fits within the given bounding box, preserving aspect ratio.
     * Uses ImageMagick's `-resize WxH>` operator (the `>` means "only shrink, never enlarge").
     */
    public function makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void
    {
        if ($maxW < 1 || $maxH < 1) {
            throw new MediaException('Max dimensions must be at least 1px');
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Image file not found: ' . $inputPath);
        }

        // ImageMagick geometry: WxH> — fit inside bounding box, preserve aspect ratio,
        // only shrink (never enlarge smaller images).
        $geometry = $maxW . 'x' . $maxH . '>';

        $cmd = 'convert'
            . ' ' . ShellRunner::escapeArg($inputPath)
            . ' -resize ' . ShellRunner::escapeArg($geometry)
            . ' -strip'
            . ' ' . ShellRunner::escapeArg($outputPath);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'convert makeThumbnail failed: ' . $result->getOutput()
            );
        }
    }

    /**
     * Re-encode a JPEG at the given quality level (0 = worst, 100 = best).
     * Also strips EXIF/metadata to reduce file size.
     */
    public function compressJpeg(string $inputPath, string $outputPath, int $quality): void
    {
        if ($quality < 0 || $quality > 100) {
            throw new MediaException('JPEG quality must be 0–100, got: ' . $quality);
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Image file not found: ' . $inputPath);
        }

        $cmd = 'convert'
            . ' ' . ShellRunner::escapeArg($inputPath)
            . ' -quality ' . $quality
            . ' -strip'
            . ' ' . ShellRunner::escapeArg($outputPath);

        $result = $this->shell->run($cmd);

        if (!$result->isSuccess()) {
            throw new MediaException(
                'convert compressJpeg failed: ' . $result->getOutput()
            );
        }
    }
}
