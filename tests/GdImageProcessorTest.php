<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\GdImageProcessor;
use LPhenom\Media\ImageProcessorInterface;
use PHPUnit\Framework\TestCase;

final class GdImageProcessorTest extends TestCase
{
    /** @var string */
    private string $tmpDir = '';

    protected function setUp(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $dir = sys_get_temp_dir() . '/lphenom_media_' . uniqid('', true);
        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            $this->fail('Could not create temp directory');
        }
        $this->tmpDir = $dir;
    }

    protected function tearDown(): void
    {
        if ($this->tmpDir === '' || !is_dir($this->tmpDir)) {
            return;
        }
        $files = glob($this->tmpDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        rmdir($this->tmpDir);
    }

    public function testImplementsInterface(): void
    {
        $processor = new GdImageProcessor();
        $this->assertInstanceOf(ImageProcessorInterface::class, $processor);
    }

    public function testMakeThumbnailCreatesOutputFile(): void
    {
        $input  = $this->tmpDir . '/source.jpg';
        $output = $this->tmpDir . '/thumb.jpg';

        $this->createTestJpeg($input, 400, 300);

        $processor = new GdImageProcessor();
        $processor->makeThumbnail($input, $output, 100, 100);

        $this->assertFileExists($output);
    }

    public function testMakeThumbnailRespectsBoundingBox(): void
    {
        $input  = $this->tmpDir . '/wide.jpg';
        $output = $this->tmpDir . '/wide_thumb.jpg';

        $this->createTestJpeg($input, 800, 200);

        $processor = new GdImageProcessor();
        $processor->makeThumbnail($input, $output, 200, 200);

        $img = imagecreatefromjpeg($output);
        $this->assertNotFalse($img);
        $this->assertLessThanOrEqual(200, imagesx($img));
        $this->assertLessThanOrEqual(200, imagesy($img));
        imagedestroy($img);
    }

    public function testMakeThumbnailPreservesAspectRatio(): void
    {
        $input  = $this->tmpDir . '/landscape.jpg';
        $output = $this->tmpDir . '/landscape_thumb.jpg';

        // 400x200 → maxW=100, maxH=100 → should be 100x50
        $this->createTestJpeg($input, 400, 200);

        $processor = new GdImageProcessor();
        $processor->makeThumbnail($input, $output, 100, 100);

        $img = imagecreatefromjpeg($output);
        $this->assertNotFalse($img);
        $w = imagesx($img);
        $h = imagesy($img);
        imagedestroy($img);

        $this->assertSame(100, $w, 'Width should be 100');
        $this->assertSame(50, $h, 'Height should be 50');
    }

    public function testMakeThumbnailThrowsForMissingInput(): void
    {
        $this->expectException(MediaException::class);
        $processor = new GdImageProcessor();
        $processor->makeThumbnail('/nonexistent/file.jpg', $this->tmpDir . '/out.jpg', 100, 100);
    }

    public function testMakeThumbnailThrowsForInvalidDimensions(): void
    {
        $this->expectException(MediaException::class);
        $processor = new GdImageProcessor();
        $processor->makeThumbnail('/fake', '/out', 0, 0);
    }

    public function testCompressJpegCreatesOutputFile(): void
    {
        $input  = $this->tmpDir . '/hq.jpg';
        $output = $this->tmpDir . '/compressed.jpg';

        $this->createTestJpeg($input, 200, 200);

        $processor = new GdImageProcessor();
        $processor->compressJpeg($input, $output, 50);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
    }

    public function testCompressJpegThrowsForInvalidQuality(): void
    {
        $this->expectException(MediaException::class);
        $processor = new GdImageProcessor();
        $processor->compressJpeg('/fake.jpg', '/out.jpg', 150);
    }

    public function testCompressJpegThrowsForMissingInput(): void
    {
        $this->expectException(MediaException::class);
        $processor = new GdImageProcessor();
        $processor->compressJpeg('/nonexistent/file.jpg', $this->tmpDir . '/out.jpg', 85);
    }

    public function testMakeThumbnailPng(): void
    {
        $input  = $this->tmpDir . '/source.png';
        $output = $this->tmpDir . '/thumb.png';

        $this->createTestPng($input, 300, 300);

        $processor = new GdImageProcessor();
        $processor->makeThumbnail($input, $output, 80, 80);

        $this->assertFileExists($output);
        $img = imagecreatefrompng($output);
        $this->assertNotFalse($img);
        $this->assertLessThanOrEqual(80, imagesx($img));
        $this->assertLessThanOrEqual(80, imagesy($img));
        imagedestroy($img);
    }

    // ------------------------------------------------------------------ helpers

    private function createTestJpeg(string $path, int $width, int $height): void
    {
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            $this->fail('imagecreatetruecolor failed');
        }
        $red = imagecolorallocate($img, 220, 50, 50);
        if ($red !== false) {
            imagefill($img, 0, 0, $red);
        }
        imagejpeg($img, $path, 90);
        imagedestroy($img);
    }

    private function createTestPng(string $path, int $width, int $height): void
    {
        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            $this->fail('imagecreatetruecolor failed');
        }
        imagealphablending($img, false);
        imagesavealpha($img, true);
        $blue = imagecolorallocatealpha($img, 50, 100, 200, 0);
        if ($blue !== false) {
            imagefill($img, 0, 0, $blue);
        }
        imagepng($img, $path, 6);
        imagedestroy($img);
    }
}
