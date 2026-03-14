<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Dto\VideoInfo;
use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\FfmpegVideoProcessor;
use LPhenom\Media\Shell\ShellRunner;
use LPhenom\Media\VideoProcessorInterface;
use PHPUnit\Framework\TestCase;

final class FfmpegVideoProcessorTest extends TestCase
{
    /** @var string */
    private string $tmpDir = '';

    /** @var ShellRunner */
    private ShellRunner $shell;

    protected function setUp(): void
    {
        $this->shell = new ShellRunner();

        if (!$this->shell->isAvailable('ffmpeg') || !$this->shell->isAvailable('ffprobe')) {
            $this->markTestSkipped('ffmpeg/ffprobe not available');
        }

        $dir = sys_get_temp_dir() . '/lphenom_ffmpeg_' . uniqid('', true);
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Could not create temp directory');
        }
        $this->tmpDir = $dir;

        // Create a 2-second test video (320x240 red, 1 fps) using lavfi source
        $testVideo = $this->tmpDir . '/input.mp4';
        $mkCmd     = 'ffmpeg -y -f lavfi -i color=c=red:size=320x240:rate=25 -t 2 '
            . ShellRunner::escapeArg($testVideo);
        $res = $this->shell->run($mkCmd);
        if (!$res->isSuccess()) {
            $this->markTestSkipped('Could not create test video: ' . $res->getOutput());
        }
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir === '' || !is_dir($this->tmpDir)) {
            return;
        }
        $files = glob($this->tmpDir . '/*');
        if ($files !== false) {
            foreach ($files as $f) {
                if (is_file($f)) {
                    unlink($f);
                }
            }
        }
        rmdir($this->tmpDir);
    }

    public function testImplementsInterface(): void
    {
        $processor = new FfmpegVideoProcessor($this->shell);
        $this->assertInstanceOf(VideoProcessorInterface::class, $processor);
    }

    public function testProbeReturnsVideoInfo(): void
    {
        $processor = new FfmpegVideoProcessor($this->shell);
        $info      = $processor->probe($this->tmpDir . '/input.mp4');

        $this->assertInstanceOf(VideoInfo::class, $info);
        $this->assertSame($this->tmpDir . '/input.mp4', $info->getPath());
        $this->assertGreaterThan(0, $info->getSizeBytes());
        $this->assertGreaterThanOrEqual(1, $info->getDurationSeconds());
        $this->assertSame(320, $info->getWidth());
        $this->assertSame(240, $info->getHeight());
        $this->assertNotSame('unknown', $info->getCodec());
    }

    public function testProbeThrowsForMissingFile(): void
    {
        $this->expectException(MediaException::class);
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->probe('/nonexistent/video.mp4');
    }

    public function testValidateSizePassesForSmallFile(): void
    {
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->validateSize($this->tmpDir . '/input.mp4', 50 * 1024 * 1024);
        $this->assertTrue(true);
    }

    public function testValidateSizeThrowsWhenExceedsLimit(): void
    {
        $this->expectException(MediaException::class);
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->validateSize($this->tmpDir . '/input.mp4', 1);
    }

    public function testCompressCreatesOutputFile(): void
    {
        $output    = $this->tmpDir . '/compressed.mp4';
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->compress($this->tmpDir . '/input.mp4', $output, 28);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testCompressThrowsForInvalidCrf(): void
    {
        $this->expectException(MediaException::class);
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->compress($this->tmpDir . '/input.mp4', $this->tmpDir . '/out.mp4', 99);
    }

    public function testResizeCreatesOutputWithSmallerDimensions(): void
    {
        $output    = $this->tmpDir . '/resized.mp4';
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->resize($this->tmpDir . '/input.mp4', $output, 160, 120);

        $this->assertFileExists($output);

        $info = $processor->probe($output);
        $this->assertLessThanOrEqual(160, $info->getWidth());
        $this->assertLessThanOrEqual(120, $info->getHeight());
    }

    public function testExtractThumbnailCreatesImage(): void
    {
        $output    = $this->tmpDir . '/thumb.jpg';
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->extractThumbnail($this->tmpDir . '/input.mp4', $output, 0);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testExtractThumbnailThrowsForNegativeSecond(): void
    {
        $this->expectException(MediaException::class);
        $processor = new FfmpegVideoProcessor($this->shell);
        $processor->extractThumbnail($this->tmpDir . '/input.mp4', $this->tmpDir . '/t.jpg', -1);
    }
}
