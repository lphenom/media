<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\Shell\ShellRunner;

/**
 * Factory that returns the best available ImageProcessorInterface implementation.
 *
 * Priority:
 *   1. GdImageProcessor  — when the `gd` PHP extension is loaded (fast, in-process)
 *   2. ImageMagickProcessor — when `convert` (ImageMagick) is in $PATH (KPHP-compatible)
 *
 * Throws MediaException if neither is available.
 *
 * NOTE: This factory itself is PHP-only because it references GdImageProcessor
 *       which uses GD-specific types. In KPHP, instantiate ImageMagickProcessor directly.
 *
 * @lphenom-build shared
 */
final class ImageProcessorFactory
{
    public static function create(): ImageProcessorInterface
    {
        if (extension_loaded('gd')) {
            return new GdImageProcessor();
        }

        $shell = new ShellRunner();

        if ($shell->isAvailable('convert')) {
            return new ImageMagickProcessor($shell);
        }

        throw new MediaException(
            'Image processing requires either the GD PHP extension or ImageMagick (convert command). '
            . 'Install GD: apt-get install php-gd  or  install ImageMagick: apt-get install imagemagick'
        );
    }
}
