<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\ImageProcessorInterface;
use LPhenom\Media\NoopImageProcessor;
use PHPUnit\Framework\TestCase;

final class NoopImageProcessorTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $processor = new NoopImageProcessor();
        $this->assertInstanceOf(ImageProcessorInterface::class, $processor);
    }

    public function testMakeThumbnailDoesNotThrow(): void
    {
        $processor = new NoopImageProcessor();
        // Must not throw even for non-existent paths — no-op by design
        $processor->makeThumbnail('/nonexistent/input.jpg', '/nonexistent/output.jpg', 100, 100);
        $this->assertTrue(true);
    }

    public function testCompressJpegDoesNotThrow(): void
    {
        $processor = new NoopImageProcessor();
        $processor->compressJpeg('/nonexistent/input.jpg', '/nonexistent/output.jpg', 85);
        $this->assertTrue(true);
    }

    public function testMakeThumbnailWithZeroDimensions(): void
    {
        $processor = new NoopImageProcessor();
        // Noop should silently accept any parameters
        $processor->makeThumbnail('/a', '/b', 0, 0);
        $this->assertTrue(true);
    }
}

