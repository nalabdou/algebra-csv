<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Exception;

final class FileNotFoundException extends CsvException
{
    public static function fromPath(string $path): self
    {
        return new self("CSV file not found: {$path}");
    }
}
