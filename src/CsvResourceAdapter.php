<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv;

use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;
use Nalabdou\Algebra\Csv\Contract\CsvParserInterface;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

/**
 * Adapter for open resource handles (file handles from `fopen`, `tmpfile`, etc.).
 *
 * The handle must be an open, readable stream. It is **not** closed after reading —
 * the caller owns the handle lifecycle.
 *
 * ### Named constructor (recommended)
 * ```php
 * use Nalabdou\Algebra\Csv\CsvResourceAdapter;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
 *
 * $adapter = CsvResourceAdapter::from(new CsvOptions(coerceTypes: true));
 * ```
 *
 * ### Usage
 * ```php
 * use Nalabdou\Algebra\Csv\CsvResourceAdapter;
 * use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
 *
 * $handle = fopen('/data/orders.csv', 'rb');
 *
 * $factory = new CollectionFactory(..., adapters: [CsvResourceAdapter::from(new CsvOptions())]);
 *
 * $result = $factory->create($handle)
 *     ->where("item['status'] == 'paid'")
 *     ->toArray();
 *
 * fclose($handle); // caller closes
 * ```
 *
 * ### With algebra-php
 * ```php
 * use Nalabdou\Algebra\Algebra;
 * use Nalabdou\AlgebraCsv\CsvResourceAdapter;
 * use Nalabdou\AlgebraCsv\ValueObject\CsvOptions;
 *
 * Algebra::adapters()->register(CsvResourceAdapter::from(new CsvOptions()), priority: 80);
 *
 * $handle = fopen('/data/orders.csv', 'rb');
 *
 * $result = Algebra::from($handle)
 *     ->where("item['status'] == 'paid'")
 *     ->toArray();
 *
 * fclose($handle); // caller closes
 * ```
 *
 * ### With algebra-symfony
 * ```php
 * use Nalabdou\AlgebraSymfony\Attribute\AsAlgebraAdapter;
 *
 * #[AsAlgebraAdapter(priority: 60)]
 * final class MyCsvAdapter extends CsvResourceAdapter {}
 * ```
 */
class CsvResourceAdapter implements CsvAdapterInterface
{
    public function __construct(
        private readonly CsvOptions $options = new CsvOptions(),
        private readonly CsvParserInterface $parser = new CsvParser(),
    ) {
    }

    /**
     * Create a new adapter with the given options.
     *
     * ```php
     * $adapter = CsvResourceAdapter::from(
     *     new CsvOptions(delimiter: "\t", coerceTypes: true)
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
        return \is_resource($input) && 'stream' === \get_resource_type($input);
    }

    public function toArray(mixed $input): array
    {
        $meta = \stream_get_meta_data($input);

        // @phpstan-ignore-next-line
        if ($meta['eof'] ?? false) {
            \rewind($input);
        }

        return $this->parser->parse($input, $this->options);
    }
}
