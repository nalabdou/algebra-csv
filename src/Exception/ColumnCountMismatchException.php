<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Exception;

final class ColumnCountMismatchException extends CsvException
{
    public static function create(int $expected, int $actual, int $line): self
    {
        return new self(
            "CSV column count mismatch on line {$line}: expected {$expected} columns, got {$actual}."
        );
    }
}
