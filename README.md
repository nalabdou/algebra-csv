# algebra-csv

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

CSV adapter for [algebra-php](https://github.com/nalabdou/algebra-php).  
Accepts file paths, `SplFileInfo`, raw CSV strings, and resource handles.  
Auto-detects delimiter. Encoding conversion. Type coercion.

---

## Installation

```bash
composer require nalabdou/algebra-csv
```

**Requirements:** PHP ≥ 8.2, `ext-mbstring`

---

## Quick start

Register adapters once at bootstrap, then use `Algebra::from()` anywhere.

### File path

```php
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

Algebra::adapters()->register(CsvFileAdapter::from(new CsvOptions(coerceTypes: true)), priority: 100);

$result = Algebra::from('/data/orders.csv')
    ->where("item['status'] == 'paid'")
    ->groupBy('region')
    ->aggregate(['revenue' => 'sum(amount)', 'orders' => 'count(*)'])
    ->orderBy('revenue', 'desc')
    ->toArray();
```

### Raw CSV string

```php
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

Algebra::adapters()->register(CsvStringAdapter::from(new CsvOptions()), priority: 90);

$csv = CsvString::from(
    "id,status,amount\n1,paid,100\n2,pending,200\n3,paid,300\n",
    new CsvOptions(coerceTypes: true),
);

$result = Algebra::from($csv)
    ->where("item['status'] == 'paid'")
    ->toArray();
```

### Resource handle

```php
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

Algebra::adapters()->register(CsvResourceAdapter::from(new CsvOptions()), priority: 80);

$handle = fopen('/data/exports.csv', 'rb');

$result = Algebra::from($handle)
    ->tally('status')
    ->toArray();

fclose($handle); // caller owns the handle lifecycle
```

---

## Bootstrap — register all adapters at once

A single setup call at application entry point covers all three input types:

```php
use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

Algebra::adapters()->register(CsvFileAdapter::from(new CsvOptions(coerceTypes: true)),  priority: 100);
Algebra::adapters()->register(CsvStringAdapter::from(new CsvOptions()),                  priority: 90);
Algebra::adapters()->register(CsvResourceAdapter::from(new CsvOptions()),                priority: 80);
```

After that, `Algebra::from()` accepts file paths, `SplFileInfo`, `CsvString`, and resource handles with no further configuration.

---

## Named constructors (`::from()`)

Every adapter exposes a `::from(CsvOptions)` static named constructor:

```php
$adapter = CsvFileAdapter::from(new CsvOptions(delimiter: ';', coerceTypes: true));

// Also valid — same result
$adapter = new CsvFileAdapter(new CsvOptions(delimiter: ';', coerceTypes: true));
```

`CsvString` follows the same pattern:

```php
$csv = CsvString::from("id,name\n1,Alice\n");

$csv = CsvString::from("id;name\n1;Alice\n", new CsvOptions(delimiter: ';'));
```

---

## Supported inputs

| Input | Adapter | Notes |
|---|---|---|
| `string` (file path) | `CsvFileAdapter` | Must exist and be readable |
| `SplFileInfo` / `SplFileObject` | `CsvFileAdapter` | Symfony's `File` works too |
| `CsvString` | `CsvStringAdapter` | Wraps raw CSV content |
| `resource` (stream) | `CsvResourceAdapter` | Caller owns `fclose()` |

---

## Configuration

All adapters accept a `CsvOptions` instance:

```php
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

$opts = new CsvOptions(
    delimiter:   null,         // null = auto-detect from [',', ';', "\t", '|']
    enclosure:   '"',          // field enclosure character
    escape:      '\\',         // escape character
    hasHeader:   true,         // first row = column names
    encoding:    null,         // null = UTF-8; set 'Windows-1252', 'ISO-8859-1', etc.
    skipEmpty:   false,        // skip rows where all cells are empty
    coerceTypes: false,        // convert '42' → 42, '1.5' → 1.5
);
```

### Fluent builder

```php
$opts = (new CsvOptions())
    ->withDelimiter(';')
    ->withEncoding('Windows-1252')
    ->withTypeCoercion()
    ->withSkipEmpty();

Algebra::adapters()->register(CsvFileAdapter::from($opts), priority: 100);
```

---

## Auto-delimiter detection

When `delimiter` is `null` (the default), `DelimiterDetector` samples the first 4 KB and picks the most consistently-occurring character from `[',', ';', "\t", '|']`:

```php
Algebra::from('orders.csv')   // comma — auto-detected
Algebra::from('exports.csv')  // semicolon — auto-detected
Algebra::from('data.tsv')     // tab — auto-detected
```

---

## Encoding conversion

Pass the source encoding — the parser converts everything to UTF-8 automatically:

```php
Algebra::adapters()->register(
    CsvFileAdapter::from(new CsvOptions(encoding: 'Windows-1252')),
    priority: 100,
);

$result = Algebra::from('/data/french_export.csv')->toArray();
// All strings arrive as UTF-8
```

---

## Type coercion

With `coerceTypes: true`, numeric strings are cast to `int` or `float`:

```php
Algebra::adapters()->register(
    CsvFileAdapter::from(new CsvOptions(coerceTypes: true)),
    priority: 100,
);

// Without coercion:  $row['amount'] === '500'  (string)
// With coercion:     $row['amount'] === 500    (int)
```

---

## Interfaces

The `src/Contract/` directory exposes stable interfaces for DI and extension:

| Interface | Purpose |
|-----------|---------|
| `CsvAdapterInterface` | All adapters; adds `::from()` and `getOptions()` on top of `AdapterInterface` |
| `CsvParserInterface` | Low-level parser — swap in a custom implementation via constructor injection |
| `DelimiterDetectorInterface` | Detector — swap in a custom strategy |

```php
use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;

function registerAdapters(CsvAdapterInterface ...$adapters): void {
    foreach ($adapters as $priority => $adapter) {
        Algebra::adapters()->register($adapter, priority: $priority);
    }
}
```

---

## With algebra-symfony

```php
use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

#[AsAlgebraAdapter(priority: 50)]
final class AppCsvAdapter extends CsvFileAdapter
{
    public function __construct()
    {
        parent::__construct(new CsvOptions(coerceTypes: true, skipEmpty: true));
    }
}
```

Registered automatically — no `services.yaml` needed. Use `Algebra::from()` directly throughout the application.

---

## Requirements

| | Version |
|---|---|
| PHP | ≥ 8.2 |
| ext-mbstring | * |
| nalabdou/algebra-php | ^1.0 |

---

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, coding standards, and how to add adapters or parser features.

## License

MIT — see [LICENSE](LICENSE).
