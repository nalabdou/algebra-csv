<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\ValueObject;

/**
 * Immutable configuration for CSV parsing.
 *
 * All options have sensible defaults — a `new CsvOptions()` with no arguments
 * handles the vast majority of real-world CSV files.
 *
 * ```php
 * // Auto-detect delimiter, UTF-8, first row is header
 * $opts = new CsvOptions();
 *
 * // Semicolon-separated, Windows encoding, skip empty rows
 * $opts = new CsvOptions(
 *     delimiter:  ';',
 *     encoding:   'Windows-1252',
 *     skipEmpty:  true,
 * );
 *
 * // Tab-separated, coerce numeric strings
 * $opts = new CsvOptions(delimiter: "\t", coerceTypes: true);
 * ```
 */
final class CsvOptions
{
    /**
     * @param string|null $delimiter   Field separator. null = auto-detect from [',', ';', "\t", '|']
     * @param string      $enclosure   Field enclosure character (default: double-quote)
     * @param string      $escape      Escape character (default: backslash)
     * @param bool        $hasHeader   First row contains column names (default: true)
     * @param string|null $encoding    Source encoding. null = assume UTF-8. Auto-converted to UTF-8.
     * @param bool        $skipEmpty   Skip rows where all cells are empty strings (default: false)
     * @param bool        $coerceTypes Convert numeric strings to int/float (default: false)
     */
    public function __construct(
        public readonly ?string $delimiter = null,
        public readonly string $enclosure = '"',
        public readonly string $escape = '\\',
        public readonly bool $hasHeader = true,
        public readonly ?string $encoding = null,
        public readonly bool $skipEmpty = false,
        public readonly bool $coerceTypes = false,
    ) {
    }

    public function withDelimiter(string $delimiter): self
    {
        return new self(
            delimiter: $delimiter,
            enclosure: $this->enclosure,
            escape: $this->escape,
            hasHeader: $this->hasHeader,
            encoding: $this->encoding,
            skipEmpty: $this->skipEmpty,
            coerceTypes: $this->coerceTypes,
        );
    }

    public function withEncoding(string $encoding): self
    {
        return new self(
            delimiter: $this->delimiter,
            enclosure: $this->enclosure,
            escape: $this->escape,
            hasHeader: $this->hasHeader,
            encoding: $encoding,
            skipEmpty: $this->skipEmpty,
            coerceTypes: $this->coerceTypes,
        );
    }

    public function withoutHeader(): self
    {
        return new self(
            delimiter: $this->delimiter,
            enclosure: $this->enclosure,
            escape: $this->escape,
            hasHeader: false,
            encoding: $this->encoding,
            skipEmpty: $this->skipEmpty,
            coerceTypes: $this->coerceTypes,
        );
    }

    public function withTypeCoercion(): self
    {
        return new self(
            delimiter: $this->delimiter,
            enclosure: $this->enclosure,
            escape: $this->escape,
            hasHeader: $this->hasHeader,
            encoding: $this->encoding,
            skipEmpty: $this->skipEmpty,
            coerceTypes: true,
        );
    }

    public function withSkipEmpty(): self
    {
        return new self(
            delimiter: $this->delimiter,
            enclosure: $this->enclosure,
            escape: $this->escape,
            hasHeader: $this->hasHeader,
            encoding: $this->encoding,
            skipEmpty: true,
            coerceTypes: $this->coerceTypes,
        );
    }
}
