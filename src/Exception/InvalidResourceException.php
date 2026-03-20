<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Exception;

final class InvalidResourceException extends CsvException
{
    public static function create(): self
    {
        return new self('CSV resource handle is not a valid open stream.');
    }
}
