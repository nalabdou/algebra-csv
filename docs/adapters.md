# Adapters

## CsvFileAdapter

Accepts file paths (`string`) and `SplFileInfo` / `SplFileObject`.

```php
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

// Default options — comma delimiter auto-detected, UTF-8
$adapter = new CsvFileAdapter();

// Custom options
$adapter = new CsvFileAdapter(new CsvOptions(
    delimiter:   ';',
    encoding:    'Windows-1252',
    coerceTypes: true,
));

Algebra::adapters()->register($adapter, priority: 50);

Algebra::from('/data/orders.csv');
Algebra::from(new \SplFileInfo('/data/orders.csv'));
```

**Throws** `FileNotFoundException` or `FileNotReadableException`
when the file is inaccessible.

## CsvStringAdapter

Accepts `CsvString` value objects. Use when the CSV content is already in memory.

```php
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

$csv = new CsvString(
    content: "id,name,amount\n1,Alice,100\n2,Bob,200\n",
    options: new CsvOptions(coerceTypes: true),
);

$result = Algebra::from($csv)->where("item['amount'] > 100")->toArray();
```

An empty or whitespace-only `CsvString` returns `[]` without parsing.

## CsvResourceAdapter

Accepts open stream resources — file handles, `tmpfile()`, `php://temp`, etc.
The handle is **not** closed after reading. The caller owns `fclose()`.

```php
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Adapter\AdapterRegistry;

$adapter = new CsvResourceAdapter(new CsvOptions(coerceTypes: true));
Algebra::adapters()->register($adapter, priority: 50);


$handle = fopen('/data/orders.csv', 'rb');

$result = Algebra::from($handle)->toArray();
fclose($handle);
```

If the handle is exhausted (at EOF), the adapter rewinds it automatically
before parsing.
