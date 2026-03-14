<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\ImageMagickProcessor;
use LPhenom\Media\ImageProcessorInterface;
use LPhenom\Media\Shell\ShellRunner;
use PHPUnit\Framework\TestCase;

final class ImageMagickProcessorTest extends TestCase
{
    /** @var string */
    private string $tmpDir = '';

    /** @var ShellRunner */
    private ShellRunner $shell;

    protected function setUp(): void
    {
        $this->shell = new ShellRunner();

        if (!$this->shell->isAvailable('convert')) {
            $this->markTestSkipped('ImageMagick (convert) not available');
        }

        $dir = sys_get_temp_dir() . '/lphenom_im_' . uniqid('', true);
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Could not create temp directory');
        }
        $this->tmpDir = $dir;

        // Create test images using ImageMagick itself
        $this->createTestJpeg($this->tmpDir . '/source.jpg', 400, 300);
        $this->createTestPng($this->tmpDir . '/source.png', 200, 200);
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
        $processor = new ImageMagickProcessor($this->shell);
        $this->assertInstanceOf(ImageProcessorInterface::class, $processor);
    }

    public function testMakeThumbnailCreatesOutputFile(): void
    {
        $output    = $this->tmpDir . '/thumb.jpg';
        $processor = new ImageMagickProcessor($this->shell);
        $processor->makeThumbnail($this->tmpDir . '/source.jpg', $output, 100, 100);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testMakeThumbnailRespectsBoundingBox(): void
    {
        $output    = $this->tmpDir . '/thumb_small.jpg';
        $processor = new ImageMagickProcessor($this->shell);
        // source is 400x300, fit in 100x100 → should be 100x75
        $processor->makeThumbnail($this->tmpDir . '/source.jpg', $output, 100, 100);

        $this->assertFileExists($output);

        // Verify with identify
        $res = $this->shell->run('identify -format "%wx%h" ' . ShellRunner::escapeArg($output));
        if ($res->isSuccess()) {
            $dimensions = trim($res->getOutput());
            // e.g. "100x75"
            $parts = explode('x', $dimensions);
            if (count($parts) === 2) {
                $this->assertLessThanOrEqual(100, (int) $parts[0]);
                $this->assertLessThanOrEqual(100, (int) $parts[1]);
            }
        }
    }

    public function testMakeThumbnailSupportsPng(): void
    {
        $output    = $this->tmpDir . '/thumb.png';
        $processor = new ImageMagickProcessor($this->shell);
        $processor->makeThumbnail($this->tmpDir . '/source.png', $output, 80, 80);

        $this->assertFileExists($output);
    }

    public function testMakeThumbnailThrowsForMissingInput(): void
    {
        $this->expectException(MediaException::class);
        $processor = new ImageMagickProcessor($this->shell);
        $processor->makeThumbnail('/nonexistent/image.jpg', $this->tmpDir . '/out.jpg', 100, 100);
    }

    public function testMakeThumbnailThrowsForInvalidDimensions(): void
    {
        $this->expectException(MediaException::class);
        $processor = new ImageMagickProcessor($this->shell);
        $processor->makeThumbnail($this->tmpDir . '/source.jpg', $this->tmpDir . '/out.jpg', 0, 0);
    }

    public function testCompressJpegCreatesOutputFile(): void
    {
        $output    = $this->tmpDir . '/compressed.jpg';
        $processor = new ImageMagickProcessor($this->shell);
        $processor->compressJpeg($this->tmpDir . '/source.jpg', $output, 60);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testCompressJpegThrowsForInvalidQuality(): void
    {
        $this->expectException(MediaException::class);
        $processor = new ImageMagickProcessor($this->shell);
        $processor->compressJpeg($this->tmpDir . '/source.jpg', $this->tmpDir . '/out.jpg', 150);
    }

    public function testCompressJpegThrowsForMissingInput(): void
    {
        $this->expectException(MediaException::class);
        $processor = new ImageMagickProcessor($this->shell);
        $processor->compressJpeg('/nonexistent/file.jpg', $this->tmpDir . '/out.jpg', 85);
    }

    // ------------------------------------------------------------------ helpers

    private function createTestJpeg(string $path, int $width, int $height): void
    {
        $cmd = 'convert -size ' . $width . 'x' . $height . ' xc:red ' . ShellRunner::escapeArg($path);
        $res = $this->shell->run($cmd);
        if (!$res->isSuccess()) {
            $this->fail('Could not create test JPEG: ' . $res->getOutput());
        }
    }

    private function createTestPng(string $path, int $width, int $height): void
    {
        $cmd = 'convert -size ' . $width . 'x' . $height . ' xc:blue ' . ShellRunner::escapeArg($path);
        $res = $this->shell->run($cmd);
        if (!$res->isSuccess()) {
            $this->fail('Could not create test PNG: ' . $res->getOutput());
        }
    }
}
