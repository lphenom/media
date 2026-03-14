# lphenom/media

[![CI](https://github.com/lphenom/media/actions/workflows/ci.yml/badge.svg)](https://github.com/lphenom/media/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Image and video processing utilities for the **LPhenom** ecosystem.

Designed to work in two modes:
- 🏠 **PHP shared hosting** — GD extension (fast, in-process) or ImageMagick via shell
- ⚡ **KPHP compiled binary** — shell-only via `ShellRunner` (no GD required)

---

## Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| PHP | 8.1 | 8.2+ |
| GD extension | — | For shared hosting image processing |
| ImageMagick (`convert`) | — | Required if GD unavailable; required in KPHP |
| FFmpeg + ffprobe | Required for video | 4.x+ |
| OS | Linux / macOS | Linux x86-64 |

```bash
# Install system dependencies (Debian/Ubuntu)
apt-get install php-gd imagemagick ffmpeg

# macOS
brew install imagemagick ffmpeg
```

---

## Installation

```bash
composer require lphenom/media
```

---

## Quick Start

### Image processing

```php
use LPhenom\Media\ImageProcessorFactory;

// Auto-selects: GdImageProcessor (GD available) → ImageMagickProcessor (convert available)
// Throws MediaException if neither is available
$processor = ImageProcessorFactory::create();

// Resize to fit within 200×200, preserving aspect ratio
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 200, 200);

// Re-encode JPEG at 75% quality (strips EXIF)
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_75.jpg', 75);
```

### Video processing

```php
use LPhenom\Media\VideoProcessorFactory;
use LPhenom\Media\Exception\MediaException;

// Requires ffmpeg + ffprobe in $PATH; throws MediaException if not found
$video = VideoProcessorFactory::create();

// Probe metadata
$info = $video->probe('/uploads/clip.mp4');
echo $info->getWidth() . 'x' . $info->getHeight(); // e.g. 1920x1080
echo $info->getDurationSeconds();                   // e.g. 120
echo $info->getCodec();                             // e.g. h264

// Validate upload size before processing
$video->validateSize('/uploads/clip.mp4', 100 * 1024 * 1024); // 100 MB max

// Compress (CRF 28 = good quality, smaller file)
$video->compress('/uploads/raw.mp4', '/uploads/compressed.mp4', 28);

// Resize to fit within 1280×720
$video->resize('/uploads/4k.mp4', '/uploads/720p.mp4', 1280, 720);

// Extract thumbnail at 5 seconds
$video->extractThumbnail('/uploads/clip.mp4', '/cache/thumb.jpg', 5);
```

### KPHP mode (no GD)

```php
use LPhenom\Media\ImageMagickProcessor;
use LPhenom\Media\FfmpegVideoProcessor;
use LPhenom\Media\Shell\ShellRunner;

$shell  = new ShellRunner();
$images = new ImageMagickProcessor($shell);
$video  = new FfmpegVideoProcessor($shell);
```

---

## Implementations

| Class | Backend | KPHP | Description |
|-------|---------|------|-------------|
| `GdImageProcessor` | PHP GD ext | ❌ | Fast in-process image processing |
| `ImageMagickProcessor` | `convert` cmd | ✅ | Shell-based, all formats |
| `FfmpegVideoProcessor` | `ffmpeg`/`ffprobe` | ✅ | Full video processing pipeline |
| `ImageProcessorFactory` | Auto | ❌ | GD → ImageMagick auto-selection |
| `VideoProcessorFactory` | FFmpeg | ✅ | Creates FfmpegVideoProcessor |
| `Shell\ShellRunner` | `exec()` | ✅ | Single shell execution point |

---

## Development

```bash
make up          # Start Docker dev container (PHP 8.1-alpine + GD + ffmpeg + imagemagick)
make install     # Install Composer dependencies
make test        # Run PHPUnit tests
make lint        # Check code style (php-cs-fixer)
make phpstan     # Run static analysis
make kphp-check  # Verify KPHP compilation + PHAR build
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for full development guide.

---

## Documentation

- [docs/media.md](docs/media.md) — Full API reference, system requirements, shared hosting notes
- [docs/kphp-compatibility.md](docs/kphp-compatibility.md) — KPHP issues found & solutions

---

## License

MIT — see [LICENSE](LICENSE).
