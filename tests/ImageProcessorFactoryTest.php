<?php

declare(strict_types=1);

namespace LPhenom\Media\Tests;

use LPhenom\Media\GdImageProcessor;
use LPhenom\Media\ImageMagickProcessor;
use LPhenom\Media\ImageProcessorFactory;
use LPhenom\Media\ImageProcessorInterface;
use LPhenom\Media\Shell\ShellRunner;
use PHPUnit\Framework\TestCase;

final class ImageProcessorFactoryTest extends TestCase
{
    public function testCreateReturnsImageProcessorInterface(): void
    {
        $shell = new ShellRunner();
        if (!extension_loaded('gd') && !$shell->isAvailable('convert')) {
            $this->markTestSkipped('Neither GD nor ImageMagick available');
        }

        $processor = ImageProcessorFactory::create();
        $this->assertInstanceOf(ImageProcessorInterface::class, $processor);
    }

    public function testCreateReturnsGdProcessorWhenGdAvailable(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available');
        }

        $processor = ImageProcessorFactory::create();
        $this->assertInstanceOf(GdImageProcessor::class, $processor);
    }

    public function testCreateReturnsImageMagickWhenGdMissingButConvertAvailable(): void
    {
        if (extension_loaded('gd')) {
            $this->markTestSkipped('GD is loaded — factory prefers GD over ImageMagick');
        }
        $shell = new ShellRunner();
        if (!$shell->isAvailable('convert')) {
            $this->markTestSkipped('ImageMagick (convert) not available');
        }

        $processor = ImageProcessorFactory::create();
        $this->assertInstanceOf(ImageMagickProcessor::class, $processor);
    }
}
