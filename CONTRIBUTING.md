# Contributing to lphenom/media

Thank you for your interest in contributing! This document outlines the process
and standards for contributing to this package.

## Development Setup

All tooling runs inside Docker — you do **not** need PHP, Composer, or any extension
installed locally.

```bash
# Clone the repo
git clone git@github.com:lphenom/media.git
cd media

# Start the dev container and install dependencies
make up
make install

# Run tests
make test

# Run code style check
make lint

# Run static analysis
make phpstan

# Verify KPHP + PHAR compatibility
make kphp-check
```

## Code Standards

### PHP version
- Minimum: **PHP 8.1**
- Syntax: no features that break KPHP (see [docs/kphp-compatibility.md](docs/kphp-compatibility.md))

### Required in every file
```php
declare(strict_types=1);
```

### KPHP rules (mandatory)
- No `match()` expression
- No constructor property promotion
- No `readonly` properties
- No `str_starts_with()` / `str_ends_with()` / `str_contains()`
- No trailing commas in function/method calls
- No `__destruct()`
- No union types in class properties (`int|string` etc.)
- No `Reflection*` API
- No `eval()`
- No variable variables (`$$var`)
- `try/finally` must always have at least one `catch`

See [docs/kphp-compatibility.md](docs/kphp-compatibility.md) for the full list.

### Style
- PSR-12 code style enforced via `php-cs-fixer`.
- PHPDoc on all public methods and typed arrays (`@var array<K, V>`).

## Commit Messages

Use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat(media): add webp support to GdImageProcessor
fix(media): handle zero-byte files in StubVideoProcessor
test(media): add edge-case tests for compressJpeg
docs(media): update shared hosting limitations
chore: bump phpunit to 10.5
```

Keep commits **small and focused** — one logical change per commit.

## Pull Request Process

1. Fork the repository and create a feature branch from `main`.
2. Write tests for any new functionality.
3. Ensure all checks pass: `make test && make lint && make phpstan && make kphp-check`.
4. Open a Pull Request against `main` with a clear description.
5. At least one maintainer review is required before merging.

## Adding a New Implementation

When adding a new `ImageProcessorInterface` or `VideoProcessorInterface` implementation:

1. Create the class in `src/` with full PHPDoc.
2. Add unit tests in `tests/`.
3. **If KPHP-compatible**, add `require_once` to `build/kphp-entrypoint.php`.
4. Update `docs/media.md` with usage examples.

## License

By contributing, you agree that your contributions will be licensed under the
[MIT License](LICENSE).

