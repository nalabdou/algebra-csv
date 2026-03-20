<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Parser;

use Nalabdou\Algebra\Csv\Contract\CsvParserInterface;
use Nalabdou\Algebra\Csv\Contract\DelimiterDetectorInterface;
use Nalabdou\Algebra\Csv\Exception\InvalidResourceException;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

/**
 * Low-level CSV parser: reads a resource handle and yields associative-array rows.
 *
 * Handles:
 * - Delimiter auto-detection via {@see DelimiterDetectorInterface}
 * - Encoding conversion (anything → UTF-8) via `mb_convert_encoding`
 * - Header row extraction and column key assignment
 * - Optional empty-row skipping
 * - Optional numeric-string type coercion
 */
final class CsvParser implements CsvParserInterface
{
    private const DETECT_SAMPLE_BYTES = 4096;

    public function __construct(
        private readonly DelimiterDetectorInterface $detector = new DelimiterDetector(),
    ) {
    }

    /**
     * Parse a resource handle into an array of associative-array rows.
     *
     * @param resource   $handle  Open, readable file/stream handle (rewound to start)
     * @param CsvOptions $options Parsing configuration
     *
     * @return array<int, array<string|int, mixed>>
     *
     * @throws InvalidResourceException When the resource is invalid or the file is empty
     */
    public function parse(mixed $handle, CsvOptions $options): array
    {
        if (!\is_resource($handle)) {
            throw InvalidResourceException::create();
        }

        $delimiter = $options->delimiter ?? $this->detectDelimiter($handle, $options);

        $header = null;
        $rows = [];

        while (($raw = \fgetcsv($handle, 0, $delimiter, $options->enclosure, $options->escape)) !== false) {
            if ($raw === [null]) {
                continue;
            }

            $row = $this->convertEncoding($raw, $options->encoding);

            if ($options->skipEmpty && $this->isAllEmpty($row)) {
                continue;
            }

            if ($options->hasHeader && null === $header) {
                $header = $row;
                continue;
            }

            if (null !== $header) {
                if (\count($row) !== \count($header)) {
                    // Pad or trim to header count — lenient by default
                    $row = \array_slice(
                        \array_pad($row, \count($header), ''),
                        0,
                        \count($header)
                    );
                }
                $row = \array_combine($header, $row);
            } else {
                // No header mode — use zero-indexed keys
                $row = \array_values($row);
            }

            if ($options->coerceTypes) {
                $row = $this->coerce($row);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    private function detectDelimiter(mixed $handle, CsvOptions $options): string
    {
        $sample = \fread($handle, self::DETECT_SAMPLE_BYTES);
        \rewind($handle);

        if (null !== $options->encoding && 'UTF-8' !== \strtoupper($options->encoding)) {
            $sample = \mb_convert_encoding($sample, 'UTF-8', $options->encoding);
        }

        return $this->detector->detect($sample);
    }

    /**
     * @param string[] $row
     *
     * @return string[]
     */
    private function convertEncoding(array $row, ?string $encoding): array
    {
        if (null === $encoding || 'UTF-8' === \strtoupper($encoding)) {
            return $row;
        }

        return \array_map(
            static fn (mixed $v): string => \mb_convert_encoding($v, 'UTF-8', $encoding),
            $row
        );
    }

    private function isAllEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ('' !== $cell && null !== $cell) {
                return false;
            }
        }

        return true;
    }

    private function coerce(array $row): array
    {
        foreach ($row as $key => $value) {
            if (!\is_string($value) || '' === $value) {
                continue;
            }
            if (\ctype_digit(\ltrim($value, '-')) && (1 === \strlen($value) || '0' !== $value[0])) {
                $row[$key] = (int) $value;
            } elseif (\is_numeric($value)) {
                $row[$key] = (float) $value;
            }
        }

        return $row;
    }
}
