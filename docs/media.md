# LPhenom Media

`lphenom/media` — пакет обработки изображений и видео для экосистемы LPhenom.  
Работает в двух режимах: **PHP shared hosting** (GD + shell) и **KPHP compiled binary** (shell via `exec()`).

---

## Системные требования

### Минимальные (обязательно)

| Требование | Минимум | Рекомендуется |
|---|---|---|
| PHP | 8.1 | 8.2+ |
| Расширение `gd` | — | Рекомендуется для shared hosting |
| `convert` (ImageMagick) | — | Нужен если GD недоступен |
| `ffmpeg` + `ffprobe` | Нужен для видео | 4.x + |
| ОС | Linux / macOS | Linux x86-64 |

### Для работы с изображениями

Нужно **хотя бы одно** из двух:

- **PHP GD extension** — быстро, без shell. Включается через `extension=gd` или `apt-get install php-gd`.  
  Для поддержки WebP GD должен быть скомпилирован с `--with-webp`.
- **ImageMagick** (`convert`) — universal fallback. `apt-get install imagemagick` / `brew install imagemagick`.

Если ни GD, ни ImageMagick не доступны — `ImageProcessorFactory::create()` бросит `MediaException`.

### Для работы с видео

- **FFmpeg + ffprobe** (обязательно) — `apt-get install ffmpeg` / `brew install ffmpeg`.  
  Если `ffmpeg`/`ffprobe` не найдены в `$PATH` — `VideoProcessorFactory::create()` бросит `MediaException`.

### KPHP binary

Дополнительных PHP-расширений не нужно. Обработка ведётся через shell:
- Изображения → `convert` (ImageMagick)
- Видео → `ffmpeg` / `ffprobe`

Для компиляции используйте `make kphp-check` (см. [kphp-compatibility.md](./kphp-compatibility.md)).

---

## Быстрый старт

```php
use LPhenom\Media\ImageProcessorFactory;
use LPhenom\Media\VideoProcessorFactory;

// Авто-выбор: GdImageProcessor (если GD) или ImageMagickProcessor (если convert)
$images = ImageProcessorFactory::create();
$images->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 300, 300);
$images->compressJpeg('/uploads/photo.jpg', '/uploads/photo_75.jpg', 75);

// FFmpeg-процессор
$video = VideoProcessorFactory::create();
$info  = $video->probe('/uploads/clip.mp4');
$video->validateSize('/uploads/clip.mp4', 100 * 1024 * 1024);
$video->compress('/uploads/clip.mp4', '/uploads/clip_small.mp4', 28);
$video->resize('/uploads/clip.mp4', '/uploads/clip_720p.mp4', 1280, 720);
$video->extractThumbnail('/uploads/clip.mp4', '/cache/clip_thumb.jpg', 5);
```

---

## Интерфейсы

### `ImageProcessorInterface`

```php
interface ImageProcessorInterface
{
    public function makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void;
    public function compressJpeg(string $inputPath, string $outputPath, int $quality): void;
}
```

#### `makeThumbnail(string $inputPath, string $outputPath, int $maxW, int $maxH): void`

Создаёт миниатюру, вписывая изображение в ограничивающий прямоугольник `$maxW × $maxH`
с **сохранением пропорций** (не растягивает).

```php
// 1600×900 → fit 200×200 → output 200×112
$processor->makeThumbnail('/uploads/banner.jpg', '/cache/banner_thumb.jpg', 200, 200);

// 400×400 → fit 100×100 → output 100×100
$processor->makeThumbnail('/uploads/avatar.png', '/cache/avatar_thumb.png', 100, 100);
```

Бросает `MediaException` если:
- файл не найден
- формат не поддерживается
- `$maxW < 1` или `$maxH < 1`

#### `compressJpeg(string $inputPath, string $outputPath, int $quality): void`

Перекодирует JPEG с заданным качеством (0 — максимальное сжатие, 100 — без потерь).
Также удаляет EXIF-метаданные (уменьшает размер).

```php
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_75.jpg', 75);
```

---

### `VideoProcessorInterface`

```php
interface VideoProcessorInterface
{
    public function probe(string $path): VideoInfo;
    public function validateSize(string $path, int $maxBytes): void;
    public function compress(string $inputPath, string $outputPath, int $crf): void;
    public function resize(string $inputPath, string $outputPath, int $maxWidth, int $maxHeight): void;
    public function extractThumbnail(string $inputPath, string $outputPath, int $atSecond): void;
}
```

#### `probe(string $path): VideoInfo`

Читает метаданные через `ffprobe`. Возвращает размер файла, длительность, кодек, разрешение.

```php
$info = $video->probe('/uploads/clip.mp4');
echo $info->getWidth();           // 1920
echo $info->getHeight();          // 1080
echo $info->getCodec();           // 'h264'
echo $info->getDurationSeconds(); // 120
echo $info->getBitrate();         // 4000000 (bps)
echo $info->getMimeType();        // 'video/mp4'
```

#### `validateSize(string $path, int $maxBytes): void`

Бросает `MediaException` если файл превышает лимит.

```php
$video->validateSize('/uploads/clip.mp4', 50 * 1024 * 1024); // 50 MB max
```

#### `compress(string $inputPath, string $outputPath, int $crf): void`

Перекодирует видео с заданным CRF (Constant Rate Factor).  
**Меньший CRF = лучшее качество, больший файл.** Диапазон: 0–51.

| CRF | Качество |
|-----|---------|
| 18  | Visually lossless |
| 23  | Default (FFmpeg) |
| 28  | Good (рекомендуется для сжатия) |
| 51  | Худшее |

```php
$video->compress('/uploads/raw.mp4', '/uploads/compressed.mp4', 28);
```

#### `resize(string $inputPath, string $outputPath, int $maxWidth, int $maxHeight): void`

Масштабирует видео до ограничивающего прямоугольника с **сохранением пропорций**.

```php
// 1920×1080 → fit 1280×720 → output 1280×720
$video->resize('/uploads/4k.mp4', '/uploads/720p.mp4', 1280, 720);
```

#### `extractThumbnail(string $inputPath, string $outputPath, int $atSecond): void`

Извлекает один кадр из видео как JPEG-изображение.

```php
$video->extractThumbnail('/uploads/clip.mp4', '/cache/thumb.jpg', 5); // кадр на 5-й секунде
```

---

## Реализации

### `GdImageProcessor` — PHP shared hosting

Использует расширение **PHP GD**. Быстро, без shell, in-process.  
Поддерживает: JPEG, PNG, GIF, WebP (при наличии libwebp в GD).

```php
use LPhenom\Media\GdImageProcessor;

$processor = new GdImageProcessor();
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 300, 300);
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_opt.jpg', 80);
```

**Форматы:**

| Расширение | Чтение | Запись | Примечание |
|-----------|--------|--------|------------|
| `.jpg`, `.jpeg` | ✅ | ✅ | — |
| `.png` | ✅ | ✅ | Прозрачность сохраняется |
| `.gif` | ✅ | ✅ | Анимация не поддерживается |
| `.webp` | ✅ | ✅ | Требует GD с `--with-webp` |

> ❌ Не включается в KPHP entrypoint (GD-специфичные типы `\GdImage`).

---

### `ImageMagickProcessor` — Shell / KPHP

Использует `convert` (ImageMagick) через `ShellRunner`.  
Поддерживает все форматы, которые поддерживает ImageMagick (JPEG, PNG, GIF, WebP, TIFF, BMP, …).

```php
use LPhenom\Media\ImageMagickProcessor;
use LPhenom\Media\Shell\ShellRunner;

$processor = new ImageMagickProcessor(new ShellRunner());
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 300, 300);
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_opt.jpg', 80);
```

> ✅ KPHP-совместим — только `exec()` и файловые функции.

---

### `ImageProcessorFactory` — авто-выбор

```php
use LPhenom\Media\ImageProcessorFactory;

$processor = ImageProcessorFactory::create();
// Возвращает GdImageProcessor если gd загружен
// Иначе — ImageMagickProcessor если convert доступен
// Иначе — бросает MediaException
```

> ❌ Не включается в KPHP entrypoint (ссылается на GdImageProcessor).  
> В KPHP используйте `new ImageMagickProcessor(new ShellRunner())` напрямую.

---

### `FfmpegVideoProcessor` — FFmpeg / KPHP

Использует `ffmpeg` + `ffprobe` через `ShellRunner`.  
Реализует полный набор методов `VideoProcessorInterface`.

```php
use LPhenom\Media\FfmpegVideoProcessor;
use LPhenom\Media\Shell\ShellRunner;

$shell = new ShellRunner();
$video = new FfmpegVideoProcessor($shell);

$info = $video->probe('/uploads/clip.mp4');
$video->compress('/uploads/clip.mp4', '/uploads/small.mp4', 28);
$video->resize('/uploads/clip.mp4', '/uploads/720p.mp4', 1280, 720);
$video->extractThumbnail('/uploads/clip.mp4', '/cache/thumb.jpg', 0);
```

> ✅ KPHP-совместим — только `exec()` (1 аргумент), `file()` (1 аргумент), `strpos()`, `substr()`.

---

### `VideoProcessorFactory` — авто-создание

```php
use LPhenom\Media\VideoProcessorFactory;

$video = VideoProcessorFactory::create();
// Возвращает FfmpegVideoProcessor если ffmpeg + ffprobe в $PATH
// Иначе — бросает MediaException
```

---

### `Shell\ShellRunner` — исполнение команд

Единственная точка взаимодействия с shell. Используется всеми shell-based процессорами.

```php
use LPhenom\Media\Shell\ShellRunner;

$shell  = new ShellRunner();
$result = $shell->run('echo hello');

$result->isSuccess();      // true
$result->getExitCode();    // 0
$result->getOutput();      // 'hello'
$result->getOutputLines(); // ['hello']

// Проверить наличие утилиты
$shell->isAvailable('ffmpeg');  // true / false

// Экранирование аргумента (POSIX single-quote)
ShellRunner::escapeArg('/path/to/my file.mp4'); // '/path/to/my file.mp4'
```

**KPHP-совместимость:**  
`exec()` вызывается с **одним аргументом** — обход ограничения KPHP где `&$output` типизирован как `mixed`.  
Вывод команды перехватывается через **temp-файл** (`/tmp/lphenom_*.out`).

---

## DTO: `VideoInfo`

```php
use LPhenom\Media\Dto\VideoInfo;

$info = new VideoInfo(
    '/path/to/clip.mp4',  // path
    4096000,              // sizeBytes
    120,                  // durationSeconds
    'video/mp4',          // mimeType
    1920,                 // width  (optional, default 0)
    1080,                 // height (optional, default 0)
    'h264',               // codec  (optional, default 'unknown')
    4000000               // bitrate bps (optional, default 0)
);

$info->getPath();            // string
$info->getSizeBytes();       // int — размер файла в байтах
$info->getDurationSeconds(); // int — длительность в секундах
$info->getMimeType();        // string — MIME-тип
$info->getWidth();           // int — ширина кадра в пикселях
$info->getHeight();          // int — высота кадра в пикселях
$info->getCodec();           // string — кодек видеопотока ('h264', 'vp9', …)
$info->getBitrate();         // int — битрейт видео в bps
```

---

## Исключения

### `MediaException`

Единственный тип исключения в пакете. Бросается при любой ошибке обработки медиа.

```php
use LPhenom\Media\Exception\MediaException;

try {
    $processor->makeThumbnail('/missing.jpg', '/out.jpg', 100, 100);
} catch (MediaException $e) {
    // файл не найден, формат не поддерживается, convert/ffmpeg вернул ошибку и т.д.
    echo $e->getMessage();
}
```

Наследует `\RuntimeException`.

---

## Ограничения shared hosting

1. **GD вместо shell** — на большинстве shared hosting `exec()` / `shell_exec()` заблокированы.  
   Используйте `GdImageProcessor` (нет shell-вызовов).

2. **Нет FFmpeg** — `FfmpegVideoProcessor` требует shell. На shared hosting видео-обработка  
   обычно недоступна. Используйте только `validateSize()` через прямую проверку `filesize()`.

3. **Лимит памяти** — GD загружает весь пиксельный буфер в RAM.  
   Для изображения 4000×3000 px требуется ≈ 46 MB RAM (`4000 × 3000 × 4 bytes`).  
   Проверяйте `memory_limit` и используйте `validateSize()` перед обработкой.

4. **Лимит времени** — `max_execution_time` на shared hosting 30–60 с.  
   Видео-перекодирование занимает минуты — только для VPS/dedicated серверов.

5. **WebP в GD** — поддерживается только если PHP GD скомпилирован с `--with-webp`.  
   Проверка: `gd_info()['WebP Support'] === true`.

---

## KPHP-совместимость

| Компонент | Статус | Примечание |
|-----------|--------|------------|
| `Shell\ShellResult` | ✅ | KPHP-compatible |
| `Shell\ShellRunner` | ✅ | exec() 1 аргумент + temp-file |
| `ImageMagickProcessor` | ✅ | через ShellRunner |
| `FfmpegVideoProcessor` | ✅ | через ShellRunner |
| `VideoProcessorFactory` | ✅ | только KPHP-safe классы |
| `VideoInfo` DTO | ✅ | явные свойства, нет union types |
| `MediaException` | ✅ | extends RuntimeException |
| `GdImageProcessor` | ❌ PHP only | GD-специфичные типы `\GdImage` |
| `ImageProcessorFactory` | ❌ PHP only | ссылается на GdImageProcessor |

Подробнее о всех ограничениях и обходных решениях — в [kphp-compatibility.md](./kphp-compatibility.md).
