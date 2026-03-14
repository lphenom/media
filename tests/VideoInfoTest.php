<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Dto\VideoInfo;
use PHPUnit\Framework\TestCase;

final class VideoInfoTest extends TestCase
{
    public function testGettersReturnConstructorValues(): void
    {
        $info = new VideoInfo('/var/video/clip.mp4', 2048576, 120, 'video/mp4', 1920, 1080, 'h264', 4000000);

        $this->assertSame('/var/video/clip.mp4', $info->getPath());
        $this->assertSame(2048576, $info->getSizeBytes());
        $this->assertSame(120, $info->getDurationSeconds());
        $this->assertSame('video/mp4', $info->getMimeType());
        $this->assertSame(1920, $info->getWidth());
        $this->assertSame(1080, $info->getHeight());
        $this->assertSame('h264', $info->getCodec());
        $this->assertSame(4000000, $info->getBitrate());
    }

    public function testDefaultFieldsAreZeroOrUnknown(): void
    {
        $info = new VideoInfo('/f', 0, 0, 'video/unknown');

        $this->assertSame(0, $info->getWidth());
        $this->assertSame(0, $info->getHeight());
        $this->assertSame('unknown', $info->getCodec());
        $this->assertSame(0, $info->getBitrate());
    }

    public function testZeroDurationIsAllowed(): void
    {
        $info = new VideoInfo('/tmp/x.avi', 0, 0, 'video/avi');
        $this->assertSame(0, $info->getDurationSeconds());
    }

    public function testMimeTypeIsPreservedVerbatim(): void
    {
        $info = new VideoInfo('/f', 1, 1, 'video/x-msvideo');
        $this->assertSame('video/x-msvideo', $info->getMimeType());
    }

    public function testPartialConstructorWithDefaults(): void
    {
        $info = new VideoInfo('/clip.mp4', 1024, 30, 'video/mp4');
        $this->assertSame('/clip.mp4', $info->getPath());
        $this->assertSame('unknown', $info->getCodec());
    }
}
