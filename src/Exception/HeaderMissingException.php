<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Exception;

final class HeaderMissingException extends CsvException
{
    public static function create(): self
    {
        return new self('CSV source has no header row.');
    }
}
