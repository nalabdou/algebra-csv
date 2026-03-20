<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv;

use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;
use Nalabdou\Algebra\Csv\Contract\CsvParserInterface;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

/**
 * Adapter for raw CSV content wrapped in a {@see CsvString} value object.
 *
 * A `CsvString` is used instead of a plain PHP string to avoid ambiguity:
 * a bare string could be a file path or raw CSV data. Wrapping in `CsvString`
 * makes the intent explicit.
 *
 * ### Named constructor (recommended)
 * ```php
 * use Nalabdou\Algebra\Csv\CsvStringAdapter;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvString;
 *
 * $adapter = CsvStringAdapter::from(new CsvOptions(coerceTypes: true));
 * ```
 *
 * ### Usage
 * ```php
 * use Nalabdou\Algebra\Csv\ValueObject\CsvString;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
 * use Nalabdou\Algebra\Algebra;
 *
 * $csv = new CsvString("id,name,amount\n1,Alice,100\n2,Bob,200\n3,Carol,350\n");
 *
 * $result = Algebra::from($csv)
 *     ->where("item['amount'] > 150")
 *     ->toArray();
 * // [['id' => '2', 'name' => 'Bob', 'amount' => '200'], ['id' => '3', ...]]
 *
 * // With type coercion
 * $csv = new CsvString(
 *     content: "id,name,amount\n1,Alice,100\n",
 *     options: new CsvOptions(coerceTypes: true),
 * );
 * ```
 */
class CsvStringAdapter implements CsvAdapterInterface
{
    public function __construct(
        private readonly CsvOptions $options = new CsvOptions(),
        private readonly CsvParserInterface $parser = new CsvParser(),
    ) {
    }

    /**
     * Create a new adapter with the given options.
     *
     * Note: for `CsvStringAdapter`, options are typically embedded in the
     * `CsvString` value object itself. This named constructor is provided for
     * consistency with the `CsvAdapterInterface` contract.
     *
     * ```php
     * $adapter = CsvStringAdapter::from(new CsvOptions());
     * ```
     */
    public static function from(CsvOptions $options): static
    {
        return new static($options);
    }

    public function getOptions(): CsvOptions
    {
        return $this->options;
    }

    public function supports(mixed $input): bool
    {
        return $input instanceof CsvString;
    }

    public function toArray(mixed $input): array
    {
        if ($input->isEmpty()) {
            return [];
        }

        $handle = \fopen('php://temp', 'rb+');
        \fwrite($handle, $input->content);
        \rewind($handle);

        try {
            return $this->parser->parse($handle, $input->options);
        } finally {
            \fclose($handle);
        }
    }
}
