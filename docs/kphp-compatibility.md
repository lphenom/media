# KPHP Compatibility — lphenom/media

Этот документ описывает все ограничения KPHP, которые применимы к пакету `lphenom/media`,
и объясняет архитектурные решения, принятые для обеспечения совместимости.

---

## Что компилируется с KPHP

KPHP entrypoint (`build/kphp-entrypoint.php`) включает только KPHP-совместимые классы:

| Файл | Статус |
|------|--------|
| `src/Exception/MediaException.php` | ✅ |
| `src/Dto/VideoInfo.php` | ✅ |
| `src/ImageProcessorInterface.php` | ✅ |
| `src/VideoProcessorInterface.php` | ✅ |
| `src/NoopImageProcessor.php` | ✅ |
| `src/StubVideoProcessor.php` | ✅ |
| `src/GdImageProcessor.php` | ❌ PHP only |
| `src/ImageProcessorFactory.php` | ❌ PHP only |

---

## Правила, соблюдённые в этом пакете

### 1. Нет constructor property promotion

```php
// ❌ НЕ РАБОТАЕТ в KPHP
final class VideoInfo {
    public function __construct(
        private readonly string $path,
        private int $sizeBytes,
    ) {}
}

// ✅ ПРАВИЛЬНО — как сделано в пакете
final class VideoInfo {
    /** @var string */
    private string $path;
    /** @var int */
    private int $sizeBytes;

    public function __construct(string $path, int $sizeBytes, int $durationSeconds, string $mimeType) {
        $this->path      = $path;
        $this->sizeBytes = $sizeBytes;
        // ...
    }
}
```

### 2. Нет trailing commas в вызовах функций

```php
// ❌ KPHP не поддерживает trailing comma
$processor->makeThumbnail($in, $out, 100, 100,);

// ✅ ПРАВИЛЬНО
$processor->makeThumbnail($in, $out, 100, 100);
```

### 3. Нет union типов в свойствах классов

```php
// ❌ ЗАПРЕЩЕНО в KPHP
private int|string $size;

// ✅ ПРАВИЛЬНО — отдельные типизированные свойства
private int $sizeBytes;
```

### 4. `filesize()` — обработка false без union типа

`filesize()` возвращает `int|false`. KPHP обрабатывает встроенные функции особым образом.
Используем `is_int()` для явного сужения типа:

```php
// ✅ KPHP-friendly паттерн
$rawSize   = filesize($path);
$sizeBytes = 0;
if (is_int($rawSize)) {
    $sizeBytes = $rawSize;
}
```

### 5. `try/catch` — всегда с хотя бы одним catch

```php
// ❌ ЗАПРЕЩЕНО
try {
    $this->saveToPath($dst, $outputPath, $outExt, 85);
} finally {
    imagedestroy($src);
}

// ✅ ПРАВИЛЬНО — как сделано в GdImageProcessor::makeThumbnail
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
```

### 6. Нет __destruct()

Ресурсы GD освобождаются явно через `imagedestroy()` — `__destruct()` не используется.

### 7. Нет str_starts_with / str_ends_with / str_contains

Используются `substr()` и `strpos()` напрямую, либо явные сравнения через `===`.

### 8. GdImageProcessor — PHP only

GD-функции (`imagecreatefromjpeg`, `imagecopyresampled`, `\GdImage` и т.д.) не поддерживаются
как расширение в KPHP binary. `GdImageProcessor` и `ImageProcessorFactory` исключены из
KPHP entrypoint — это осознанное архитектурное решение.

В KPHP binary для обработки изображений необходимо использовать `NoopImageProcessor`
или подключить кастомную реализацию через KPHP-совместимый FFI/C++ код.

---

## Проверка совместимости

```bash
# Собрать KPHP binary + PHAR
make kphp-check

# Или напрямую
docker build -f Dockerfile.check -t lphenom-media-check .
```

Обе стадии (`kphp-build`, `phar-build`) должны завершиться с кодом 0.

---

## Ссылки

- [KPHP vs PHP differences](https://vkcom.github.io/kphp/kphp-language/kphp-vs-php/whats-the-difference.html)
- [vkcom/kphp Docker image](https://hub.docker.com/r/vkcom/kphp)
- [docs/media.md](./media.md) — API reference

