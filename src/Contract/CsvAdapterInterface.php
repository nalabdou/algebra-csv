<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Contract;

use Nalabdou\Algebra\Contract\AdapterInterface;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

/**
 * Contract for all CSV adapters in algebra-csv.
 *
 * Every CSV adapter must be capable of reporting its options and
 * exposing a named constructor via `::from()` for fluent instantiation.
 *
 * ```php
 * $adapter = CsvFileAdapter::from(new CsvOptions(coerceTypes: true));
 * ```
 */
interface CsvAdapterInterface extends AdapterInterface
{
    /**
     * Create a new adapter instance with the given options.
     *
     * This static named constructor replaces factory objects and allows
     * concise one-liner setup without depending on a factory class.
     *
     * @param CsvOptions $options Parsing configuration
     */
    public static function from(CsvOptions $options): static;

    /**
     * Return the options this adapter was constructed with.
     */
    public function getOptions(): CsvOptions;
}
