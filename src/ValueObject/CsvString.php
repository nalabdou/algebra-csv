<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\ValueObject;

/**
 * Wraps a raw CSV string to distinguish it from a file path.
 *
 * Because a plain PHP `string` is ambiguous — it could be a file path or
 * raw CSV content — this value object signals that the string should be
 * treated as CSV data directly.
 *
 * ### Named constructor (recommended)
 * ```php
 * use Nalabdou\Algebra\Csv\ValueObject\CsvString;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
 *
 * $csv = CsvString::from("id,name,amount\n1,Alice,100\n2,Bob,200\n");
 *
 * // With custom options
 * $csv = CsvString::from(
 *     "id;name;amount\n1;Alice;100\n",
 *     new CsvOptions(delimiter: ';', coerceTypes: true),
 * );
 * ```
 *
 * ### Constructor
 * ```php
 * // Plain string → treated as a file path
 * Algebra::from('/path/to/orders.csv');
 *
 * // CsvString → treated as raw CSV content
 * $csv = new CsvString("id,name,amount\n1,Alice,100\n2,Bob,200\n");
 * Algebra::from($csv)->where("item['amount'] > 150")->toArray();
 * ```
 */
final class CsvString
{
    public function __construct(
        public readonly string $content,
        public readonly CsvOptions $options = new CsvOptions(),
    ) {
    }

    /**
     * Create a `CsvString` from raw CSV content and optional parse options.
     *
     * ```php
     * $csv = CsvString::from("id,name\n1,Alice\n");
     *
     * $csv = CsvString::from(
     *     "id;name\n1;Alice\n",
     *     new CsvOptions(delimiter: ';'),
     * );
     * ```
     */
    public static function from(string $content, CsvOptions $options = new CsvOptions()): self
    {
        return new self($content, $options);
    }

    public function isEmpty(): bool
    {
        return '' === \trim($this->content);
    }
}
