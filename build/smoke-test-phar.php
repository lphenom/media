#!/usr/bin/env php
<?php

/**
 * PHAR smoke-test for lphenom/media.
 *
 * Verifies that the PHAR can be loaded and that core classes are autoloaded.
 *
 * Usage:
 *   php build/smoke-test-phar.php /path/to/lphenom-media.phar
 */

declare(strict_types=1);

$pharFile = $argv[1] ?? dirname(__DIR__) . '/lphenom-media.phar';

if (!file_exists($pharFile)) {
    fwrite(STDERR, 'PHAR not found: ' . $pharFile . PHP_EOL);
    exit(1);
}

require $pharFile;

// Test 1: NoopImageProcessor — must not throw for any input
$noop = new \LPhenom\Media\NoopImageProcessor();
$noop->makeThumbnail('/fake/input.jpg', '/fake/output.jpg', 100, 100);
$noop->compressJpeg('/fake/input.jpg', '/fake/output.jpg', 85);
echo 'smoke-test: noop image processor ok' . PHP_EOL;

// Test 2: VideoInfo DTO
$info = new \LPhenom\Media\Dto\VideoInfo('/tmp/test.mp4', 2048, 30, 'video/mp4');
assert($info->getPath() === '/tmp/test.mp4', 'VideoInfo::getPath() failed');
assert($info->getSizeBytes() === 2048, 'VideoInfo::getSizeBytes() failed');
assert($info->getDurationSeconds() === 30, 'VideoInfo::getDurationSeconds() failed');
assert($info->getMimeType() === 'video/mp4', 'VideoInfo::getMimeType() failed');
echo 'smoke-test: video info dto ok' . PHP_EOL;

// Test 3: MediaException
$caught = null;
try {
    throw new \LPhenom\Media\Exception\MediaException('smoke test exception');
} catch (\LPhenom\Media\Exception\MediaException $e) {
    $caught = $e;
}
assert($caught !== null, 'MediaException was not caught');
assert($caught->getMessage() === 'smoke test exception', 'MediaException message mismatch');
echo 'smoke-test: media exception ok' . PHP_EOL;

// Test 4: StubVideoProcessor with a real temp file
$tmpFile = tempnam(sys_get_temp_dir(), 'lphenom_smoke_');
if ($tmpFile === false) {
    fwrite(STDERR, 'Could not create temp file' . PHP_EOL);
    exit(1);
}
file_put_contents($tmpFile, str_repeat('v', 512));

$stub     = new \LPhenom\Media\StubVideoProcessor();
$probeInfo = $stub->probe($tmpFile);
assert($probeInfo instanceof \LPhenom\Media\Dto\VideoInfo, 'probe() must return VideoInfo');
assert($probeInfo->getSizeBytes() === 512, 'probe() size mismatch');

$stub->validateSize($tmpFile, 1024);

unlink($tmpFile);
echo 'smoke-test: stub video processor ok' . PHP_EOL;

// Test 5: ImageProcessorFactory
$processor = \LPhenom\Media\ImageProcessorFactory::create();
assert($processor instanceof \LPhenom\Media\ImageProcessorInterface, 'factory must return interface');
echo 'smoke-test: image processor factory ok' . PHP_EOL;

echo '=== PHAR smoke-test: OK ===' . PHP_EOL;

