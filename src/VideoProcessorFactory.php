<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Exception\MediaException;
use LPhenom\Media\Shell\ShellRunner;

/**
 * Factory that returns the best available VideoProcessorInterface implementation.
 *
 * Requires FFmpeg to be installed. Throws MediaException if ffmpeg is not found.
 *
 * KPHP-compatible: no reflection, no dynamic class loading.
 *
 * @lphenom-build shared, kphp
 */
final class VideoProcessorFactory
{
    public static function create(): VideoProcessorInterface
    {
        $shell = new ShellRunner();

        if (!$shell->isAvailable('ffmpeg')) {
            throw new MediaException(
                'Video processing requires FFmpeg. '
                . 'Install it with: apt-get install ffmpeg  or  brew install ffmpeg'
            );
        }

        if (!$shell->isAvailable('ffprobe')) {
            throw new MediaException(
                'Video probing requires ffprobe (usually bundled with FFmpeg).'
            );
        }

        return new FfmpegVideoProcessor($shell);
    }
}
