<?php

declare(strict_types=1);

namespace LPhenom\Media\Exception;

/**
 * Base exception for lphenom/media package.
 *
 * KPHP-compatible: extends RuntimeException directly (no union types, no magic).
 *
 * @lphenom-build shared, kphp
 */
final class MediaException extends \RuntimeException
{
}
