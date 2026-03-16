# lphenom/media

[![CI](https://github.com/lphenom/media/actions/workflows/ci.yml/badge.svg)](https://github.com/lphenom/media/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

Утилиты для обработки изображений и видео в экосистеме **LPhenom**.

Работает в двух режимах:
- 🏠 **PHP shared hosting** — расширение GD (быстро, in-process) или ImageMagick через shell
- ⚡ **KPHP compiled binary** — только через shell via `ShellRunner` (GD не нужен)

---

## Системные требования

| Требование | Минимум | Рекомендуется |
|---|---|---|
| PHP | 8.1 | 8.2+ |
| Расширение GD | — | Для обработки изображений на shared hosting |
| ImageMagick (`convert`) | — | Нужен если GD недоступен; обязателен в KPHP |
| FFmpeg + ffprobe | Обязателен для видео | 4.x+ |
| ОС | Linux / macOS | Linux x86-64 |

```bash
# Установка системных зависимостей (Debian/Ubuntu)
apt-get install php-gd imagemagick ffmpeg

# macOS
brew install imagemagick ffmpeg
```

---

## Установка

```bash
composer require lphenom/media
```

---

## Быстрый старт

### Обработка изображений

```php
use LPhenom\Media\ImageProcessorFactory;

// Авто-выбор: GdImageProcessor (если GD доступен) → ImageMagickProcessor (если доступен convert)
// Бросает MediaException если ни одно из двух недоступно
$processor = ImageProcessorFactory::create();

// Уменьшить до 200×200 с сохранением пропорций
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 200, 200);

// Пережать JPEG с качеством 75% (убирает EXIF)
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_75.jpg', 75);
```

### Обработка видео

```php
use LPhenom\Media\VideoProcessorFactory;
use LPhenom\Media\Exception\MediaException;

// Требует ffmpeg + ffprobe в $PATH; бросает MediaException если не найдены
$video = VideoProcessorFactory::create();

// Читаем метаданные
$info = $video->probe('/uploads/clip.mp4');
echo $info->getWidth() . 'x' . $info->getHeight(); // напр. 1920x1080
echo $info->getDurationSeconds();                   // напр. 120
echo $info->getCodec();                             // напр. h264

// Проверить размер файла перед обработкой
$video->validateSize('/uploads/clip.mp4', 100 * 1024 * 1024); // макс. 100 МБ

// Сжать (CRF 28 = хорошее качество, меньший файл)
$video->compress('/uploads/raw.mp4', '/uploads/compressed.mp4', 28);

// Ресайз до 1280×720
$video->resize('/uploads/4k.mp4', '/uploads/720p.mp4', 1280, 720);

// Извлечь превью на 5-й секунде
$video->extractThumbnail('/uploads/clip.mp4', '/cache/thumb.jpg', 5);
```

### KPHP-режим (без GD)

```php
use LPhenom\Media\ImageMagickProcessor;
use LPhenom\Media\FfmpegVideoProcessor;
use LPhenom\Media\Shell\ShellRunner;

$shell  = new ShellRunner();
$images = new ImageMagickProcessor($shell);
$video  = new FfmpegVideoProcessor($shell);
```

---

## Реализации

| Класс | Backend | KPHP | Описание |
|-------|---------|------|----------|
| `GdImageProcessor` | PHP GD ext | ❌ | Быстрая in-process обработка изображений |
| `ImageMagickProcessor` | `convert` cmd | ✅ | Через shell, все форматы |
| `FfmpegVideoProcessor` | `ffmpeg`/`ffprobe` | ✅ | Полный пайплайн обработки видео |
| `ImageProcessorFactory` | Авто | ❌ | GD → ImageMagick авто-выбор |
| `VideoProcessorFactory` | FFmpeg | ✅ | Создаёт FfmpegVideoProcessor |
| `Shell\ShellRunner` | `exec()` | ✅ | Единственная точка запуска shell-команд |

---

## Разработка

```bash
make up          # Запустить Docker dev-контейнер (PHP 8.1-alpine + GD + ffmpeg + imagemagick)
make install     # Установить Composer зависимости
make test        # Запустить PHPUnit тесты
make lint        # Проверить стиль кода (php-cs-fixer)
make phpstan     # Запустить статический анализ
make kphp-check  # Проверить компиляцию KPHP + сборку PHAR
```

Подробное руководство для разработчиков — в [CONTRIBUTING.md](CONTRIBUTING.md).

---

## Документация

- [docs/media.md](docs/media.md) — Полный API reference, системные требования, особенности shared hosting
- [docs/kphp-compatibility.md](docs/kphp-compatibility.md) — Найденные KPHP-проблемы и их решения

---

## Лицензия

MIT — см. [LICENSE](LICENSE).
