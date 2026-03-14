<?php

declare(strict_types=1);

namespace LPhenom\Media;

/**
 * Contract for image processing operations.
 *
 * KPHP-compatible interface — no callable types, no magic.
 */
interface ImageProcessorInterface
{
    /**
     * Create a thumbnail that fits within the given bounding box, preserving aspect ratio.
     *
     * @param string $inputPath  Absolute path to the source image.
     * @param string $outputPath Absolute path for the output thumbnail.
     * @param int    $maxW       Maximum width in pixels.
     * @param int    $maxH       Maximum height in pixels.
     */
    public function makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void;

    /**
     * Re-encode a JPEG at the given quality level (0 = worst, 100 = best).
     *
     * @param string $inputPath  Absolute path to the source JPEG.
     * @param string $outputPath Absolute path for the compressed output.
     * @param int    $quality    0–100.
     */
    public function compressJpeg(string $inputPath, string $outputPath, int $quality): void;
}
