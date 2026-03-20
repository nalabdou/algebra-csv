# CsvOptions

`CsvOptions` is an immutable value object. Every property is `readonly`.
Use the `with*()` builder methods to create modified copies.

## Constructor

```php
new CsvOptions(
    delimiter:   null,       // string|null  — null = auto-detect
    enclosure:   '"',        // string
    escape:      '\\',       // string
    hasHeader:   true,       // bool         — first row = column names
    encoding:    null,       // string|null  — null = UTF-8
    skipEmpty:   false,      // bool         — skip all-empty rows
    coerceTypes: false,      // bool         — '42' → 42, '1.5' → 1.5
)
```

## Builder methods

```php
$opts = (new CsvOptions())
    ->withDelimiter(';')
    ->withEncoding('Windows-1252')
    ->withTypeCoercion()
    ->withSkipEmpty()
    ->withoutHeader();
```

Each method returns a new `CsvOptions` with that field changed and
all others preserved.

## Auto-delimiter detection

When `delimiter` is `null`, `DelimiterDetector` reads the first 4KB and
tests `[',', ';', "\t", '|']`. It picks the delimiter with the highest
consistent frequency across the first 5 rows.

## Encoding conversion

Set `encoding` to the source charset — the parser uses `mb_convert_encoding`
to convert every cell to UTF-8 before returning rows.

Commonly needed values: `'Windows-1252'`, `'ISO-8859-1'`, `'ISO-8859-15'`.

## Type coercion rules

With `coerceTypes: true`:

| Input string | Output |
|---|---|
| `'42'` | `(int) 42` |
| `'-5'` | `(int) -5` |
| `'1.5'` | `(float) 1.5` |
| `'0042'` | `'0042'` (leading zero → kept as string) |
| `'Alice'` | `'Alice'` (non-numeric → unchanged) |
| `''` | `''` (empty → unchanged) |
