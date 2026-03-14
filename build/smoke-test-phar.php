#!/usr/bin/env php
<?php

/**
 * PHAR smoke-test for lphenom/media.
 *
 * Usage: php build/smoke-test-phar.php /path/to/lphenom-media.phar
 */

declare(strict_types=1);

$pharFile = $argv[1] ?? dirname(__DIR__) . '/lphenom-media.phar';

if (!file_exists($pharFile)) {
    fwrite(STDERR, 'PHAR not found: ' . $pharFile . PHP_EOL);
    exit(1);
}

require $pharFile;

// Test 1: ShellRunner escaping
$escaped = \LPhenom\Media\Shell\ShellRunner::escapeArg("it's fine");
assert(substr($escaped, 0, 1) === "'", 'escapeArg must start with single quote');
echo 'smoke-test: shell runner ok' . PHP_EOL;

// Test 2: VideoInfo DTO
$info = new \LPhenom\Media\Dto\VideoInfo('/clip.mp4', 2048, 30, 'video/mp4', 1280, 720, 'h264', 2000000);
assert($info->getPath() === '/clip.mp4', 'VideoInfo::getPath() failed');
assert($info->getSizeBytes() === 2048, 'VideoInfo::getSizeBytes() failed');
assert($info->getWidth() === 1280, 'VideoInfo::getWidth() failed');
assert($info->getCodec() === 'h264', 'VideoInfo::getCodec() failed');
echo 'smoke-test: video info dto ok' . PHP_EOL;

// Test 3: MediaException
$caught = null;
try {
    throw new \LPhenom\Media\Exception\MediaException('smoke test exception');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $caught = $e;
}
assert($caught !== null, 'MediaException was not caught');
echo 'smoke-test: media exception ok' . PHP_EOL;

// Test 4: ImageMagickProcessor — missing file must throw
$shell   = new \LPhenom\Media\Shell\ShellRunner();
$magick  = new \LPhenom\Media\ImageMagickProcessor($shell);
$imgErr  = null;
try {
    $magick->makeThumbnail('/nonexistent/img.jpg', '/tmp/out.jpg', 100, 100);
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $imgErr = $e;
}
assert($imgErr !== null, 'ImageMagickProcessor must throw for missing file');
echo 'smoke-test: imagick processor ok' . PHP_EOL;

// Test 5: FfmpegVideoProcessor — missing file must throw
$ffmpeg  = new \LPhenom\Media\FfmpegVideoProcessor($shell);
$vidErr  = null;
try {
    $ffmpeg->probe('/nonexistent/video.mp4');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $vidErr = $e;
}
assert($vidErr !== null, 'FfmpegVideoProcessor must throw for missing file');
echo 'smoke-test: ffmpeg processor ok' . PHP_EOL;

// Test 6: ImageProcessorFactory returns an interface implementation
$processor = \LPhenom\Media\ImageProcessorFactory::create();
assert($processor instanceof \LPhenom\Media\ImageProcessorInterface, 'factory must return interface');
echo 'smoke-test: image processor factory ok' . PHP_EOL;

echo '=== PHAR smoke-test: OK ===' . PHP_EOL;
