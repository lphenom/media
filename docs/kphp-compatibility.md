# KPHP Compatibility — lphenom/media

Этот документ описывает все ограничения KPHP, которые применимы к пакету `lphenom/media`,
и объясняет конкретные архитектурные решения, принятые в процессе разработки.

---

## Как работает KPHP-сборка

KPHP (`vkcom/kphp`) компилирует PHP-код в **статический C++ бинарник**:

- KPHP не использует PHP runtime — он сам парсит PHP-код
- выходной бинарник **не зависит от PHP** вообще
- KPHP имеет строгий type inference и откажет компилировать неоднозначный код
- базовый образ — `vkcom/kphp` (Ubuntu 20.04 + PHP 7.4 как tooling, не как runtime)

```bash
# Проверить компиляцию + PHAR за один шаг
make kphp-check
# или
docker build -f Dockerfile.check -t lphenom-media-check .
```

---

## Аннотация `@lphenom-build`

Каждый класс/интерфейс пакета помечен аннотацией в PHPDoc-блоке класса:

```php
/**
 * ...
 * @lphenom-build shared, kphp   ← входит и в PHP shared hosting, и в KPHP
 * @lphenom-build shared          ← только PHP shared hosting (GD и пр.)
 * @lphenom-build none             ← исключить из обоих билдов
 */
```

| Значение | Смысл |
|----------|-------|
| `shared` | Только PHP shared hosting (GD extension, расширения PHP) |
| `kphp` | Только KPHP binary |
| `shared, kphp` | Работает в обоих окружениях |
| `none` | Исключить из всех билдов |

Аннотация предназначена для инструментов, которые генерируют KPHP entrypoint:  
файлы без `kphp` в значении не должны попадать в `kphp-entrypoint.php`.

---

## Что компилируется с KPHP

`build/kphp-entrypoint.php` включает только файлы с `@lphenom-build shared, kphp`:

| Файл | `@lphenom-build` | Включён в KPHP |
|------|-----------------|---------------|
| `src/Exception/MediaException.php` | `shared, kphp` | ✅ pure PHP class |
| `src/Dto/VideoInfo.php` | `shared, kphp` | ✅ only scalar types |
| `src/Shell/ShellResult.php` | `shared, kphp` | ✅ scalar types + string[] |
| `src/Shell/ShellRunner.php` | `shared, kphp` | ✅ exec() 1-arg + file() |
| `src/ImageProcessorInterface.php` | `shared, kphp` | ✅ interface only |
| `src/VideoProcessorInterface.php` | `shared, kphp` | ✅ interface only |
| `src/ImageMagickProcessor.php` | `shared, kphp` | ✅ uses ShellRunner only |
| `src/FfmpegVideoProcessor.php` | `shared, kphp` | ✅ uses ShellRunner only |
| `src/VideoProcessorFactory.php` | `shared, kphp` | ✅ KPHP-safe refs only |
| `src/GdImageProcessor.php` | `shared` | ❌ требует `gd` PHP extension |
| `src/ImageProcessorFactory.php` | `shared` | ❌ ссылается на GdImageProcessor |

---

## Проблемы, найденные при сборке, и их решения

### 1. `exec()` с `&$output` — конфликт типов

**Проблема:** KPHP объявляет `exec()` как:
```
function exec($command ::: string, &$output ::: mixed = [], int &$result_code = 0): stringfalse
```
Параметр `&$output` типизирован как `mixed`. Присвоение `mixed` к `/** @var string[] */` переменной — ошибка компиляции.

**Попытка:**
```php
// ❌ KPHP compilation error: assign mixed to $lines but it's declared as @var string[]
/** @var string[] $lines */
$lines = [];
$code  = 0;
exec($command . ' 2>&1', $lines, $code);
```

**Решение** — обойти `&$output` полностью через **temp-файл**:
```php
// ✅ KPHP-compatible в ShellRunner::run()
$tmpFile  = '/tmp/lphenom_' . (string) mt_rand(100000, 999999) . '.out';
$wrapper  = '(' . $command . ') >' . $tmpFile . ' 2>&1; echo $?';
$lastLine = exec('/bin/sh -c ' . self::escapeArg($wrapper));  // 1 аргумент

$exitCode = 1;
if ($lastLine !== false) {
    $exitCode = (int) trim((string) $lastLine);
}

$rawLines = file($tmpFile);  // 1 аргумент (KPHP требует)
// ...
```

`exec()` вызывается с **одним аргументом** — в этом случае нет reference-параметров и конфликта нет.

---

### 2. `substr()` возвращает `string|false` в KPHP

**Проблема:** В KPHP-стабах `substr` объявлен как возвращающий `string|false` (поведение PHP 7). PHP 8 вернул бы пустую строку вместо `false`, но KPHP использует старые стабы.

**Ошибка:**
```
$val = substr($line, $eqPos + 1);
substr returns stringfalse
```

**Решение** — явный cast `(string)`:
```php
// ✅ Везде где substr присваивается переменной:
$key   = (string) substr($line, 0, $eqPos);
$val   = (string) substr($line, $eqPos + 1);
$first = $commaPos === false ? $formatName : (string) substr($formatName, 0, $commaPos);

// И для проверки первого символа:
if ((string) substr($line, 0, 1) === '[') { ... }
```

---

### 3. `sys_get_temp_dir()` — функция не поддерживается

**Проблема:**
```
Unknown function sys_get_temp_dir
```

**Решение** — хардкод `/tmp`:
```php
// ❌
$tmpFile = sys_get_temp_dir() . '/lphenom_' . mt_rand() . '.out';

// ✅
$tmpFile = '/tmp/lphenom_' . (string) mt_rand(100000, 999999) . '.out';
```

---

### 4. `str_replace()` — cast результата

`str_replace(string, string, string)` в KPHP-стабах может быть typed как `mixed` (overload для array).

```php
// ✅ Всегда кастуем:
return "'" . (string) str_replace("'", "'\\''", $arg) . "'";
```

---

## Общие правила KPHP для этого пакета

### Объявление классов — без constructor property promotion

```php
// ❌ НЕ РАБОТАЕТ в KPHP
final class VideoInfo {
    public function __construct(
        private readonly string $path,
        private int $sizeBytes,
    ) {}
}

// ✅ ПРАВИЛЬНО — как сделано в VideoInfo
final class VideoInfo {
    /** @var string */
    private string $path;
    /** @var int */
    private int $sizeBytes;

    public function __construct(string $path, int $sizeBytes, ...) {
        $this->path      = $path;
        $this->sizeBytes = $sizeBytes;
    }
}
```

### `try/catch` — всегда с хотя бы одним catch

```php
// ❌ ЗАПРЕЩЕНО
try {
    $this->saveToPath($dst, $outputPath, $outExt, 85);
} finally {
    imagedestroy($src);
    imagedestroy($dst);
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

### Без trailing commas, без `__destruct()`

```php
// ❌ trailing comma
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH,);

// ✅
imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
```

### `file()` — только 1 аргумент

```php
// ❌ ЗАПРЕЩЕНО
$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// ✅ ПРАВИЛЬНО
$rawLines = file($path);
if ($rawLines !== false) {
    foreach ($rawLines as $rawLine) {
        $line = rtrim((string) $rawLine, "\r\n");
        if ($line !== '') {
            $lines[] = $line;
        }
    }
}
```

### Обработка `filesize()` возвращающего `int|false`

```php
// ✅ KPHP-friendly — сужение типа через is_int()
$rawSize   = filesize($path);
$sizeBytes = 0;
if (is_int($rawSize)) {
    $sizeBytes = $rawSize;
}
```

### GdImageProcessor — только `@lphenom-build shared`

GD функции (`imagecreatefromjpeg`, `imagecopyresampled`, тип `\GdImage`) не поддерживаются
в KPHP binary. `GdImageProcessor` помечен `@lphenom-build shared` и исключён из `kphp-entrypoint.php`.
`ImageProcessorFactory` тоже исключён, так как ссылается на `GdImageProcessor`.

В KPHP используйте напрямую:
```php
$processor = new ImageMagickProcessor(new ShellRunner());
```

---

## Запрещённые конструкции (общий список для пакета)

| Запрещено | Замена |
|-----------|--------|
| `exec($cmd, $output, $code)` — 3 аргумента | `exec($cmd)` 1 аргумент + temp-file |
| `substr(...)` без cast | `(string) substr(...)` |
| `sys_get_temp_dir()` | `/tmp` хардкод |
| `str_replace(...)` без cast | `(string) str_replace(...)` |
| `\GdImage` тип | Только в PHP-only классах |
| `file($path, FLAGS)` | `file($path)` 1 аргумент |
| `try/finally` без `catch` | Добавить `catch (\Throwable $e)` |
| `readonly`, constructor promotion | Явные свойства + `__construct` body |
| Trailing commas в вызовах | Убрать последнюю запятую |
| `__destruct()` | Явная очистка ресурсов |
| `str_starts_with/ends_with/contains` | `substr()` / `strpos()` |
| `match()` expression | `if/elseif/else` |

---

## Ссылки

- [KPHP vs PHP differences](https://vkcom.github.io/kphp/kphp-language/kphp-vs-php/whats-the-difference.html)
- [KPHP built-in functions](https://vkcom.github.io/kphp/kphp-language/kphp-vs-php/whats-the-difference.html)
- [vkcom/kphp Docker image](https://hub.docker.com/r/vkcom/kphp)
- [docs/media.md](./media.md) — API reference и системные требования
