# lphenom/media

[![CI](https://github.com/lphenom/media/actions/workflows/ci.yml/badge.svg)](https://github.com/lphenom/media/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Image and video processing utilities for the **LPhenom** ecosystem.

Designed to work in two modes:
- 🏠 **PHP shared hosting** — classic PHP runtime with optional GD extension
- ⚡ **KPHP compiled binary** — KPHP-compatible subset (no GD, no reflection)

---

## Installation

```bash
composer require lphenom/media
```

Requires PHP ≥ 8.1. The GD extension is optional but recommended for image processing.

---

## Quick Start

### Image processing

```php
use LPhenom\Media\ImageProcessorFactory;

// Returns GdImageProcessor when GD is available, NoopImageProcessor otherwise
$processor = ImageProcessorFactory::create();

// Resize to fit within 200×200, preserving aspect ratio
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/photo_thumb.jpg', 200, 200);

// Re-encode JPEG at 75% quality
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_compressed.jpg', 75);
```

### Video validation (shared hosting MVP)

```php
use LPhenom\Media\StubVideoProcessor;
use LPhenom\Media\Exception\MediaException;

$video = new StubVideoProcessor();

try {
    // Assert file ≤ 50 MB
    $video->validateSize('/uploads/clip.mp4', 50 * 1024 * 1024);

    $info = $video->probe('/uploads/clip.mp4');
    echo $info->getSizeBytes();   // file size in bytes
    echo $info->getMimeType();    // 'video/unknown' (stub)
} catch (MediaException $e) {
    echo 'Media error: ' . $e->getMessage();
}
```

---

## Interfaces

| Interface | Methods |
|-----------|---------|
| `ImageProcessorInterface` | `makeThumbnail()`, `compressJpeg()` |
| `VideoProcessorInterface` | `probe()`, `validateSize()` |

## Implementations

| Class | Description | KPHP |
|-------|-------------|------|
| `GdImageProcessor` | Full GD-based processor (JPEG, PNG, GIF, WebP) | ❌ PHP only |
| `NoopImageProcessor` | Silent no-op fallback when GD is absent | ✅ |
| `StubVideoProcessor` | Validates file size, returns stub metadata | ✅ |
| `ImageProcessorFactory` | Returns best available implementation | ❌ PHP only |

---

## Development

```bash
make up          # Start Docker dev container
make install     # Install dependencies
make test        # Run PHPUnit tests
make lint        # Check code style
make phpstan     # Run static analysis
make kphp-check  # Verify KPHP compilation + PHAR build
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for full development guide.

---

## Documentation

- [docs/media.md](docs/media.md) — API reference and shared hosting notes
- [docs/kphp-compatibility.md](docs/kphp-compatibility.md) — KPHP compatibility rules

---

## License

MIT — see [LICENSE](LICENSE).
