<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv;

use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;
use Nalabdou\Algebra\Csv\Contract\CsvParserInterface;
use Nalabdou\Algebra\Csv\Exception\FileNotFoundException;
use Nalabdou\Algebra\Csv\Exception\FileNotReadableException;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

/**
 * Adapter for CSV files given as a file path string or `SplFileInfo`.
 *
 * Accepts:
 * - `string` — absolute or relative file path (`/data/orders.csv`, `exports/q3.tsv`)
 * - `\SplFileInfo` — including `\SplFileObject` and Symfony's `File`
 *
 * All file extensions are accepted. Delimiter is auto-detected if not set in options.
 *
 * ### Named constructor (recommended)
 * ```php
 * use Nalabdou\AlgebraCsv\CsvFileAdapter;
 * use Nalabdou\AlgebraCsv\ValueObject\CsvOptions;
 *
 * $adapter = CsvFileAdapter::from(new CsvOptions(coerceTypes: true, skipEmpty: true));
 * ```
 *
 * ### Default instance
 * ```php
 * $adapter = new CsvFileAdapter();
 * ```
 *
 * ### With algebra-php
 * ```php
 * use Nalabdou\Algebra\Algebra;
 * use Nalabdou\AlgebraCsv\CsvFileAdapter;
 * use Nalabdou\AlgebraCsv\ValueObject\CsvOptions;
 *
 * Algebra::adapters()->register(CsvFileAdapter::from(new CsvOptions(coerceTypes: true)), priority: 100);
 *
 * $result = Algebra::from('/data/orders.csv')
 *     ->where("item['status'] == 'paid'")
 *     ->orderBy('amount', 'desc')
 *     ->toArray();
 * ```
 *
 * ### With algebra-symfony
 * ```php
 * use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;
 *
 * #[AsAlgebraAdapter(priority: 50)]
 * final class MyCsvAdapter extends CsvFileAdapter {}
 * ```
 */
class CsvFileAdapter implements CsvAdapterInterface
{
    final public function __construct(
        private readonly CsvOptions $options = new CsvOptions(),
        private readonly CsvParserInterface $parser = new CsvParser(),
    ) {
    }

    /**
     * Create a new adapter with the given options.
     *
     * ```php
     * $adapter = CsvFileAdapter::from(
     *     new CsvOptions(delimiter: ';', encoding: 'Windows-1252', coerceTypes: true)
     * );
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
        if (\is_string($input)) {
            return \file_exists($input) && \is_readable($input);
        }

        if ($input instanceof \SplFileInfo) {
            return $input->isFile() && $input->isReadable();
        }

        return false;
    }

    public function toArray(mixed $input): array
    {
        $path = $input instanceof \SplFileInfo ? $input->getPathname() : $input;

        if (!\file_exists($path)) {
            throw FileNotFoundException::fromPath($path);
        }

        if (!\is_readable($path)) {
            throw FileNotReadableException::fromPath($path);
        }

        $handle = \fopen($path, 'r');

        try {
            return $this->parser->parse($handle, $this->options);
        } finally {
            \fclose($handle);
        }
    }
}
