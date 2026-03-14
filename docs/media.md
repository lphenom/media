# LPhenom Media

`lphenom/media` — пакет обработки изображений и видео для экосистемы LPhenom.

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

#### `makeThumbnail`

Создаёт миниатюру, вписывая изображение в ограничивающий прямоугольник `$maxW × $maxH`
с сохранением пропорций.

```php
// 1600×900 → fit 200×200 → output 200×112
$processor->makeThumbnail('/uploads/banner.jpg', '/cache/banner_thumb.jpg', 200, 200);

// Input 400×400 → fit 100×100 → output 100×100
$processor->makeThumbnail('/uploads/avatar.png', '/cache/avatar_thumb.png', 100, 100);
```

#### `compressJpeg`

Перекодирует JPEG с заданным качеством (0 — максимальное сжатие, 100 — без потерь).

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
}
```

#### `probe`

Возвращает `VideoInfo` с базовыми метаданными файла.
В stub-реализации (`StubVideoProcessor`) duration всегда 0, mime — `video/unknown`.

#### `validateSize`

Бросает `MediaException`, если файл превышает лимит.

```php
// Максимум 50 МБ
$video->validateSize('/uploads/clip.mp4', 50 * 1024 * 1024);
```

---

## Реализации

### `GdImageProcessor` (PHP shared hosting)

Полная реализация на основе расширения **GD**. Поддерживает JPEG, PNG, GIF, WebP.

```php
use LPhenom\Media\GdImageProcessor;

$processor = new GdImageProcessor();
$processor->makeThumbnail('/uploads/photo.jpg', '/cache/thumb.jpg', 300, 300);
$processor->compressJpeg('/uploads/photo.jpg', '/uploads/photo_opt.jpg', 80);
```

**Требования:** расширение `gd` с поддержкой JPEG, WebP, Freetype.

**Форматы:**
| Расширение | Чтение | Запись |
|-----------|--------|--------|
| `.jpg`, `.jpeg` | ✅ | ✅ |
| `.png` | ✅ | ✅ |
| `.gif` | ✅ | ✅ |
| `.webp` | ✅ | ✅ |

### `NoopImageProcessor` (graceful fallback)

Реализация-заглушка, которая **ничего не делает** и не бросает исключений.
Используется автоматически через `ImageProcessorFactory::create()`, когда GD недоступен.

```php
use LPhenom\Media\NoopImageProcessor;

$processor = new NoopImageProcessor();
$processor->makeThumbnail('/any/path', '/any/out', 100, 100); // тихий no-op
```

### `ImageProcessorFactory` (авто-выбор)

```php
use LPhenom\Media\ImageProcessorFactory;

// GdImageProcessor если gd загружен, иначе NoopImageProcessor
$processor = ImageProcessorFactory::create();
```

> ⚠️ `ImageProcessorFactory` и `GdImageProcessor` **не включаются** в KPHP entrypoint —
> они используют функции GD-расширения, недоступные в скомпилированном бинарнике.

### `StubVideoProcessor` (MVP, KPHP-совместим)

```php
use LPhenom\Media\StubVideoProcessor;

$video = new StubVideoProcessor();

// Validate size
$video->validateSize('/uploads/video.mp4', 100 * 1024 * 1024);

// Probe metadata
$info = $video->probe('/uploads/video.mp4');
echo $info->getPath();            // '/uploads/video.mp4'
echo $info->getSizeBytes();       // реальный размер файла
echo $info->getDurationSeconds(); // всегда 0 (stub)
echo $info->getMimeType();        // всегда 'video/unknown' (stub)
```

---

## DTO: `VideoInfo`

```php
use LPhenom\Media\Dto\VideoInfo;

$info = new VideoInfo(
    path:            '/path/to/file.mp4',
    sizeBytes:       4096000,
    durationSeconds: 120,
    mimeType:        'video/mp4'
);

$info->getPath();            // string
$info->getSizeBytes();       // int
$info->getDurationSeconds(); // int
$info->getMimeType();        // string
```

---

## Исключения

### `MediaException`

Бросается при любой ошибке обработки медиа:

```php
use LPhenom\Media\Exception\MediaException;

try {
    $processor->makeThumbnail('/missing.jpg', '/out.jpg', 100, 100);
} catch (MediaException $e) {
    echo $e->getMessage();
}
```

Наследует `\RuntimeException` — можно ловить и базовым `\Exception`.

---

## Ограничения shared hosting

При использовании на shared hosting учитывайте:

1. **GD вместо ImageMagick** — GD обычно предустановлен на shared hosting.
   ImageMagick (`exec()`, `shell_exec()`) часто заблокирован.

2. **Нет FFmpeg** — полноценная обработка видео (перекодирование, извлечение кадров)
   невозможна без доступа к shell. `StubVideoProcessor` ограничен только проверкой размера.

3. **Лимит памяти** — декодирование больших изображений через GD загружает
   весь пиксельный буфер в RAM. Рекомендуется проверять `memory_limit` и
   размер файла перед обработкой.

4. **Лимит времени выполнения** — `max_execution_time` на shared hosting обычно 30–60 с.
   Обработка большого количества изображений должна выполняться в очереди.

5. **WebP** — поддерживается только если GD скомпилирован с `--with-webp`.
   Проверяйте через `gd_info()['WebP Support']`.

---

## KPHP-совместимость

| Компонент | Статус |
|-----------|--------|
| `NoopImageProcessor` | ✅ KPHP-совместим |
| `StubVideoProcessor` | ✅ KPHP-совместим |
| `VideoInfo` DTO | ✅ KPHP-совместим |
| `MediaException` | ✅ KPHP-совместим |
| `GdImageProcessor` | ❌ PHP only (GD extension) |
| `ImageProcessorFactory` | ❌ PHP only (ссылается на GdImageProcessor) |

Подробнее — в [kphp-compatibility.md](./kphp-compatibility.md).

