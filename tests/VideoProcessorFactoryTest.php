<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\FfmpegVideoProcessor;
use LPhenom\Media\Shell\ShellRunner;
use LPhenom\Media\VideoProcessorFactory;
use LPhenom\Media\VideoProcessorInterface;
use PHPUnit\Framework\TestCase;

final class VideoProcessorFactoryTest extends TestCase
{
    public function testCreateReturnsFfmpegProcessorWhenAvailable(): void
    {
        $shell = new ShellRunner();
        if (!$shell->isAvailable('ffmpeg') || !$shell->isAvailable('ffprobe')) {
            $this->markTestSkipped('ffmpeg/ffprobe not available');
        }

        $processor = VideoProcessorFactory::create();
        $this->assertInstanceOf(VideoProcessorInterface::class, $processor);
        $this->assertInstanceOf(FfmpegVideoProcessor::class, $processor);
    }

    public function testCreateThrowsWhenFfmpegMissing(): void
    {
        $shell = new ShellRunner();
        if ($shell->isAvailable('ffmpeg')) {
            $this->markTestSkipped('ffmpeg is available — cannot test missing-ffmpeg path');
        }

        $this->expectException(MediaException::class);
        VideoProcessorFactory::create();
    }
}
