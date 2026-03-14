<?php

/**
 * KPHP entrypoint for lphenom/media.
 *
 * This file is used exclusively for KPHP compilation verification.
 * It includes only KPHP-compatible classes (no GD extension dependencies).
 *
 * Usage:
 *   kphp -d /build/kphp-out -M cli /build/build/kphp-entrypoint.php
 *
 * Order matters: interfaces and exceptions before concrete classes.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Exception/MediaException.php';
require_once __DIR__ . '/../src/Dto/VideoInfo.php';
require_once __DIR__ . '/../src/ImageProcessorInterface.php';
require_once __DIR__ . '/../src/VideoProcessorInterface.php';
require_once __DIR__ . '/../src/NoopImageProcessor.php';
require_once __DIR__ . '/../src/StubVideoProcessor.php';

// ---------------------------------------------------------------------------
// Smoke: NoopImageProcessor
// ---------------------------------------------------------------------------
$noop = new \LPhenom\Media\NoopImageProcessor();
$noop->makeThumbnail('/tmp/in.jpg', '/tmp/out.jpg', 100, 100);
$noop->compressJpeg('/tmp/in.jpg', '/tmp/out.jpg', 85);

echo 'kphp: noop image processor ok' . PHP_EOL;

// ---------------------------------------------------------------------------
// Smoke: VideoInfo DTO
// ---------------------------------------------------------------------------
$info = new \LPhenom\Media\Dto\VideoInfo('/tmp/clip.mp4', 1048576, 60, 'video/mp4');

echo 'kphp: video info path='     . $info->getPath()            . PHP_EOL;
echo 'kphp: video info size='     . $info->getSizeBytes()       . PHP_EOL;
echo 'kphp: video info duration=' . $info->getDurationSeconds() . PHP_EOL;
echo 'kphp: video info mime='     . $info->getMimeType()        . PHP_EOL;

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
// Smoke: StubVideoProcessor (pure logic, no real file I/O required)
// ---------------------------------------------------------------------------
$stub       = new \LPhenom\Media\StubVideoProcessor();
$stubCaught = null;
try {
    $stub->probe('/definitely/not/existing/video.mp4');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $stubCaught = $e;
}

if ($stubCaught !== null) {
    echo 'kphp: stub video processor throws for missing file: ok' . PHP_EOL;
}

echo '=== KPHP entrypoint: OK ===' . PHP_EOL;

