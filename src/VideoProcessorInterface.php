<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Dto\VideoInfo;

/**
 * Contract for video processing operations.
 *
 * KPHP-compatible interface — no callable types, no magic.
 */
interface VideoProcessorInterface
{
    /**
     * Probe a video file and return its metadata (dimensions, codec, duration, etc.).
     *
     * @param string $path Absolute path to the video file.
     * @throws \LPhenom\Media\Exception\MediaException On read failure or missing file.
     */
    public function probe(string $path): VideoInfo;

    /**
     * Assert that the video file does not exceed a size limit.
     *
     * @param string $path     Absolute path to the video file.
     * @param int    $maxBytes Maximum allowed file size in bytes.
     * @throws \LPhenom\Media\Exception\MediaException When file exceeds limit or is not found.
     */
    public function validateSize(string $path, int $maxBytes): void;

    /**
     * Re-encode a video file with the given CRF quality level.
     *
     * Lower CRF = higher quality (larger file). Typical range: 18 (visually lossless) to 28 (good).
     *
     * @param string $inputPath  Absolute path to the source video.
     * @param string $outputPath Absolute path for the compressed output.
     * @param int    $crf        Constant Rate Factor: 0 (lossless) – 51 (worst). Default range: 18–28.
     * @throws \LPhenom\Media\Exception\MediaException On encoding failure.
     */
    public function compress(string $inputPath, string $outputPath, int $crf): void;

    /**
     * Scale a video to fit within the given bounding box, preserving aspect ratio.
     *
     * @param string $inputPath  Absolute path to the source video.
     * @param string $outputPath Absolute path for the resized output.
     * @param int    $maxWidth   Maximum width in pixels.
     * @param int    $maxHeight  Maximum height in pixels.
     * @throws \LPhenom\Media\Exception\MediaException On encoding failure.
     */
    public function resize(string $inputPath, string $outputPath, int $maxWidth, int $maxHeight): void;

    /**
     * Extract a single frame from a video as an image.
     *
     * @param string $inputPath  Absolute path to the source video.
     * @param string $outputPath Absolute path for the output image (JPEG recommended).
     * @param int    $atSecond   Time offset in seconds at which to capture the frame.
     * @throws \LPhenom\Media\Exception\MediaException On extraction failure.
     */
    public function extractThumbnail(string $inputPath, string $outputPath, int $atSecond): void;
}
