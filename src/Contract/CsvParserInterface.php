<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Contract;

use Nalabdou\Algebra\Csv\Exception\InvalidResourceException;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

/**
 * Contract for low-level CSV parsers.
 *
 * A parser reads an open resource handle and returns an array of rows.
 * Rows are associative arrays when a header is present, or zero-indexed
 * arrays when `hasHeader` is false.
 *
 * ```php
 * $parser = new CsvParser();
 * $handle = fopen('/data/orders.csv', 'rb');
 * $rows   = $parser->parse($handle, new CsvOptions());
 * fclose($handle);
 * ```
 */
interface CsvParserInterface
{
    /**
     * Parse an open, readable stream handle into an array of rows.
     *
     * @param resource   $handle  Open, readable file or stream handle
     * @param CsvOptions $options Parsing configuration
     *
     * @return array<int, array<string|int, mixed>>
     *
     * @throws InvalidResourceException When $handle is not a valid open stream
     */
    public function parse(mixed $handle, CsvOptions $options): array;
}
