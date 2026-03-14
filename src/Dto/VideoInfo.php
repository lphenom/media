<?php

declare(strict_types=1);

namespace LPhenom\Media\Dto;

/**
 * Immutable value object containing video file metadata.
 *
 * KPHP-compatible:
 *  - no constructor property promotion
 *  - no readonly
 *  - no union property types
 *  - explicit property declarations with defaults
 */
final class VideoInfo
{
    /** @var string */
    private string $path;

    /** @var int */
    private int $sizeBytes;

    /** @var int */
    private int $durationSeconds;

    /** @var string */
    private string $mimeType;

    /** @var int */
    private int $width;

    /** @var int */
    private int $height;

    /** @var string */
    private string $codec;

    /** @var int */
    private int $bitrate;

    public function __construct(
        string $path,
        int $sizeBytes,
        int $durationSeconds,
        string $mimeType,
        int $width = 0,
        int $height = 0,
        string $codec = 'unknown',
        int $bitrate = 0
    ) {
        $this->path            = $path;
        $this->sizeBytes       = $sizeBytes;
        $this->durationSeconds = $durationSeconds;
        $this->mimeType        = $mimeType;
        $this->width           = $width;
        $this->height          = $height;
        $this->codec           = $codec;
        $this->bitrate         = $bitrate;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function getDurationSeconds(): int
    {
        return $this->durationSeconds;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getCodec(): string
    {
        return $this->codec;
    }

    public function getBitrate(): int
    {
        return $this->bitrate;
    }
}
