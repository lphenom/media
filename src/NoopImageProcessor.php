<?php

declare(strict_types=1);

namespace LPhenom\Media;

/**
 * No-operation fallback image processor.
 *
 * Used when the GD extension is not available (graceful degradation).
 * All methods silently do nothing — no exception is thrown.
 *
 * KPHP-compatible: no GD calls, no magic, pure PHP.
 */
final class NoopImageProcessor implements ImageProcessorInterface
{
    public function makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void
    {
        // GD extension not available — graceful no-op.
    }

    public function compressJpeg(string $inputPath, string $outputPath, int $quality): void
    {
        // GD extension not available — graceful no-op.
    }
}

