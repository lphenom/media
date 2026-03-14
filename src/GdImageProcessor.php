<?php

declare(strict_types=1);

namespace LPhenom\Media;

use LPhenom\Media\Exception\MediaException;

/**
 * GD-based image processor for PHP shared hosting environments.
 *
 * Requires the `gd` PHP extension.
 * Supports JPEG, PNG, GIF, WebP.
 *
 * NOTE: This class is NOT included in the KPHP entrypoint — GD functions
 *       are PHP-extension-specific and not needed in a compiled binary.
 *       Use ImageProcessorFactory::create() to get the appropriate implementation.
 */
final class GdImageProcessor implements ImageProcessorInterface
{
    public function makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void
    {
        if ($maxW < 1 || $maxH < 1) {
            throw new MediaException('Max dimensions must be at least 1px');
        }

        $inExt  = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
        $outExt = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
        if ($outExt === '') {
            $outExt = $inExt;
        }

        $src   = $this->loadFromPath($inputPath, $inExt);
        $origW = imagesx($src);
        $origH = imagesy($src);

        $ratio = min((float) $maxW / (float) $origW, (float) $maxH / (float) $origH);
        $newW  = max(1, (int) round((float) $origW * $ratio));
        $newH  = max(1, (int) round((float) $origH * $ratio));

        $dst = imagecreatetruecolor($newW, $newH);
        if ($dst === false) {
            imagedestroy($src);
            throw new MediaException('Failed to allocate output image canvas');
        }

        // Preserve transparency for PNG/GIF
        if ($outExt === 'png') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        $resampled = imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        if (!$resampled) {
            imagedestroy($src);
            imagedestroy($dst);
            throw new MediaException('imagecopyresampled failed for: ' . $inputPath);
        }

        $exception = null;
        try {
            $this->saveToPath($dst, $outputPath, $outExt, 85);
        } catch (MediaException $e) {
            $exception = $e;
        }

        imagedestroy($src);
        imagedestroy($dst);

        if ($exception !== null) {
            throw $exception;
        }
    }

    public function compressJpeg(string $inputPath, string $outputPath, int $quality): void
    {
        if ($quality < 0 || $quality > 100) {
            throw new MediaException('JPEG quality must be 0–100, got: ' . $quality);
        }

        if (!file_exists($inputPath)) {
            throw new MediaException('Image file not found: ' . $inputPath);
        }

        $src = imagecreatefromjpeg($inputPath);
        if ($src === false) {
            throw new MediaException('Failed to decode JPEG: ' . $inputPath);
        }

        $saved = imagejpeg($src, $outputPath, $quality);
        imagedestroy($src);

        if (!$saved) {
            throw new MediaException('Failed to write JPEG: ' . $outputPath);
        }
    }

    /**
     * Load a GdImage from a file path, detecting format by extension.
     *
     * @param string $path Absolute file path.
     * @param string $ext  Lowercased extension (jpg, jpeg, png, gif, webp).
     * @return \GdImage
     * @throws MediaException
     */
    private function loadFromPath(string $path, string $ext): \GdImage
    {
        if (!file_exists($path)) {
            throw new MediaException('Image file not found: ' . $path);
        }

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $img = imagecreatefromjpeg($path);
            if ($img === false) {
                throw new MediaException('Failed to decode JPEG: ' . $path);
            }
            return $img;
        }

        if ($ext === 'png') {
            $img = imagecreatefrompng($path);
            if ($img === false) {
                throw new MediaException('Failed to decode PNG: ' . $path);
            }
            return $img;
        }

        if ($ext === 'gif') {
            $img = imagecreatefromgif($path);
            if ($img === false) {
                throw new MediaException('Failed to decode GIF: ' . $path);
            }
            return $img;
        }

        if ($ext === 'webp') {
            $img = imagecreatefromwebp($path);
            if ($img === false) {
                throw new MediaException('Failed to decode WebP: ' . $path);
            }
            return $img;
        }

        throw new MediaException('Unsupported image format "' . $ext . '" for: ' . $path);
    }

    /**
     * Save a GdImage to a file path.
     *
     * @param \GdImage $img     Source image resource.
     * @param string   $path    Output file path.
     * @param string   $ext     Lowercased output extension.
     * @param int      $quality JPEG/WebP quality 0–100.
     * @throws MediaException
     */
    private function saveToPath(\GdImage $img, string $path, string $ext, int $quality): void
    {
        $saved = false;

        if ($ext === 'jpg' || $ext === 'jpeg') {
            $saved = imagejpeg($img, $path, $quality);
        } elseif ($ext === 'png') {
            // PNG compression: 0 (none) – 9 (max); invert JPEG scale
            $pngQuality = (int) round(9.0 - ((float) $quality / 100.0 * 9.0));
            $saved      = imagepng($img, $path, $pngQuality);
        } elseif ($ext === 'gif') {
            $saved = imagegif($img, $path);
        } elseif ($ext === 'webp') {
            $saved = imagewebp($img, $path, $quality);
        } else {
            throw new MediaException('Unsupported output format "' . $ext . '"');
        }

        if (!$saved) {
            throw new MediaException('Failed to write image to: ' . $path);
        }
    }
}
