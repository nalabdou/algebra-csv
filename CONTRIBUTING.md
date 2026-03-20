# Contributing to algebra-csv

Thank you for taking the time to contribute! This document covers everything you need to get up and running.

## Table of contents

- [Development setup](#development-setup)
- [Running the suite](#running-the-suite)
- [Project structure](#project-structure)
- [Adding a new adapter](#adding-a-new-adapter)
- [Adding a parser feature](#adding-a-parser-feature)
- [Interfaces and contracts](#interfaces-and-contracts)
- [Code style](#code-style)
- [Pull requests](#pull-requests)

---

## Development setup

```bash
git clone https://github.com/nalabdou/algebra-csv
cd algebra-csv
composer install
```

PHP **8.2 or higher** is required. The `ext-mbstring` extension must be enabled.

---

## Running the suite

```bash
make test        # all tests
make unit        # unit tests only
make integration # integration tests only
make coverage    # HTML coverage report → build/coverage/
make stan        # PHPStan level 5
make cs          # code style check
make cs-fix      # auto-fix code style
make ci          # full local CI (cs + stan + test)
```

All tests must pass and coverage must remain at 100 % before a PR is merged.

---

## Project structure

```
src/
  Contract/
    CsvAdapterInterface.php       Common contract for all CSV adapters
    CsvParserInterface.php        Contract for the low-level CSV parser
    DelimiterDetectorInterface.php  Contract for delimiter detection
  Exception/                     Typed errors for file / resource problems
  ValueObject/
    CsvOptions.php                Immutable parse configuration
    CsvString.php                 Value object wrapping raw CSV content
  Parser/
    DelimiterDetector.php         Auto-detects delimiter from a sample
    CsvParser.php                 Core row-by-row parser
  CsvFileAdapter.php              Handles file paths and SplFileInfo
  CsvStringAdapter.php            Handles CsvString value objects
  CsvResourceAdapter.php          Handles open resource handles

tests/
  Fixtures/                       Sample CSV/TSV files used by tests
  Unit/
    Contract/                     Cross-adapter contract assertions
    Exception/                    Exception class tests
    Parser/                       CsvParser and DelimiterDetector tests
    ValueObject/                  CsvOptions and CsvString tests
    CsvFileAdapterTest.php
    CsvStringAdapterTest.php
    CsvResourceAdapterTest.php
  Integration/
    CsvPipelineTest.php           Full pipeline tests against real fixtures

docs/                             Markdown documentation
demo/                             Runnable examples
.github/
  workflows/ci.yml                Matrix CI (PHP 8.2 / 8.3 / 8.4)
  ISSUE_TEMPLATE/
  PULL_REQUEST_TEMPLATE.md
```

---

## Adding a new adapter

1. Implement `Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface` (which extends `AdapterInterface`)
2. Add the static named constructor `::from(CsvOptions): static`
3. Implement `getOptions(): CsvOptions`
4. Place the class in `src/`
5. Add a unit test in `tests/Unit/` mirroring the existing adapter tests
6. Add a fixture file under `tests/Fixtures/` if the adapter needs one
7. Document it in `docs/adapters.md`

```php
final class MyCsvAdapter implements CsvAdapterInterface
{
    public function __construct(
        private readonly CsvOptions $options = new CsvOptions(),
        private readonly CsvParserInterface $parser = new CsvParser(),
    ) {}

    public static function from(CsvOptions $options): static
    {
        return new static($options);
    }

    public function getOptions(): CsvOptions
    {
        return $this->options;
    }

    public function supports(mixed $input): bool { /* ... */ }
    public function toArray(mixed $input): array  { /* ... */ }
}
```

---

## Adding a parser feature

All parsing runs through `CsvParser::parse()`. The steps to add a new option are:

1. Add a `readonly` constructor parameter to `CsvOptions` with a sensible default
2. Add a `with*()` builder method to `CsvOptions` that returns a new instance
3. Handle the option inside `CsvParser`
4. Add tests in `tests/Unit/Parser/CsvParserTest.php` and `tests/Unit/ValueObject/CsvOptionsTest.php`

---

## Interfaces and contracts

The `src/Contract/` directory holds the three core interfaces:

| Interface | Purpose |
|-----------|---------|
| `CsvAdapterInterface` | Common contract for all adapters; adds `::from()` and `getOptions()` |
| `CsvParserInterface` | Makes the parser swappable via constructor injection |
| `DelimiterDetectorInterface` | Makes the detector swappable |

When writing new code, depend on interfaces rather than concrete classes wherever possible.

---

## Code style

This project follows **PSR-12** with `declare(strict_types=1)` at the top of every file.

```bash
make cs-fix   # auto-fix before committing
make cs       # dry-run check
```

---

## Pull requests

- One feature or fix per PR
- All tests must pass and coverage must stay at 100 % — `make ci`
- New or changed functionality must include tests
- Update `CHANGELOG.md` under `[Unreleased]`
- Prefer `::from()` over `new` in examples and docs
