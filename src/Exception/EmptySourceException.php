<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Exception;

final class EmptySourceException extends CsvException
{
    public static function create(): self
    {
        return new self('CSV source is empty — no rows to read.');
    }
}
