<?php

/**
 * KPHP entrypoint for lphenom/media.
 *
 * Includes only KPHP-compatible classes:
 *   - Shell layer (ShellResult, ShellRunner)
 *   - ImageMagickProcessor  (no GD — uses exec via ShellRunner)
 *   - FfmpegVideoProcessor  (no GD — uses exec via ShellRunner)
 *   - Interfaces, DTOs, Exception
 *
 * Excluded (PHP-only): GdImageProcessor, ImageProcessorFactory (references GdImageProcessor).
 *
 * Usage:
 *   kphp -d /build/kphp-out -M cli /build/build/kphp-entrypoint.php
 */

declare(strict_types=1);

// Dependencies first
require_once __DIR__ . '/../src/Exception/MediaException.php';
require_once __DIR__ . '/../src/Dto/VideoInfo.php';
require_once __DIR__ . '/../src/Shell/ShellResult.php';
require_once __DIR__ . '/../src/Shell/ShellRunner.php';

// Interfaces
require_once __DIR__ . '/../src/ImageProcessorInterface.php';
require_once __DIR__ . '/../src/VideoProcessorInterface.php';

// Implementations (KPHP-compatible — use ShellRunner, not GD)
require_once __DIR__ . '/../src/ImageMagickProcessor.php';
require_once __DIR__ . '/../src/FfmpegVideoProcessor.php';

// Factory (KPHP-compatible — only references KPHP-safe classes)
require_once __DIR__ . '/../src/VideoProcessorFactory.php';

// ---------------------------------------------------------------------------
// Smoke: ShellRunner + ShellResult
// ---------------------------------------------------------------------------
$runner = new \LPhenom\Media\Shell\ShellRunner();
$result = $runner->run('echo kphp_test');

if ($result->isSuccess()) {
    echo 'kphp: shell runner ok, output: ' . $result->getOutput() . PHP_EOL;
}

$escaped = \LPhenom\Media\Shell\ShellRunner::escapeArg("it's a path");
echo 'kphp: escapeArg ok: ' . $escaped . PHP_EOL;

// ---------------------------------------------------------------------------
// Smoke: VideoInfo DTO
// ---------------------------------------------------------------------------
$info = new \LPhenom\Media\Dto\VideoInfo('/tmp/clip.mp4', 1048576, 60, 'video/mp4', 1920, 1080, 'h264', 4000000);

echo 'kphp: video info path='     . $info->getPath()            . PHP_EOL;
echo 'kphp: video info size='     . $info->getSizeBytes()       . PHP_EOL;
echo 'kphp: video info duration=' . $info->getDurationSeconds() . PHP_EOL;
echo 'kphp: video info mime='     . $info->getMimeType()        . PHP_EOL;
echo 'kphp: video info width='    . $info->getWidth()           . PHP_EOL;
echo 'kphp: video info codec='    . $info->getCodec()           . PHP_EOL;

// ---------------------------------------------------------------------------
// Smoke: MediaException
// ---------------------------------------------------------------------------
$caught = null;
try {
    throw new \LPhenom\Media\Exception\MediaException('kphp test error');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $caught = $e;
}

if ($caught !== null) {
    echo 'kphp: media exception ok: ' . $caught->getMessage() . PHP_EOL;
}

// ---------------------------------------------------------------------------
// Smoke: ImageMagickProcessor instantiation + missing-file error
// ---------------------------------------------------------------------------
$shell    = new \LPhenom\Media\Shell\ShellRunner();
$magick   = new \LPhenom\Media\ImageMagickProcessor($shell);
$imgError = null;

try {
    $magick->makeThumbnail('/nonexistent/file.jpg', '/tmp/out.jpg', 100, 100);
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $imgError = $e;
}

if ($imgError !== null) {
    echo 'kphp: imagick throws for missing file: ok' . PHP_EOL;
}

// ---------------------------------------------------------------------------
// Smoke: FfmpegVideoProcessor instantiation + missing-file error
// ---------------------------------------------------------------------------
$ffmpeg   = new \LPhenom\Media\FfmpegVideoProcessor($shell);
$vidError = null;

try {
    $ffmpeg->probe('/nonexistent/video.mp4');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $vidError = $e;
}

if ($vidError !== null) {
    echo 'kphp: ffmpeg throws for missing file: ok' . PHP_EOL;
}

echo '=== KPHP entrypoint: OK ===' . PHP_EOL;
