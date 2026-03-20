<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\Exception;

use Nalabdou\Algebra\Csv\Exception\ColumnCountMismatchException;
use Nalabdou\Algebra\Csv\Exception\CsvException;
use Nalabdou\Algebra\Csv\Exception\EmptySourceException;
use Nalabdou\Algebra\Csv\Exception\FileNotFoundException;
use Nalabdou\Algebra\Csv\Exception\FileNotReadableException;
use Nalabdou\Algebra\Csv\Exception\HeaderMissingException;
use Nalabdou\Algebra\Csv\Exception\InvalidResourceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvException::class)]
#[CoversClass(FileNotFoundException::class)]
#[CoversClass(FileNotReadableException::class)]
#[CoversClass(InvalidResourceException::class)]
#[CoversClass(ColumnCountMismatchException::class)]
#[CoversClass(EmptySourceException::class)]
#[CoversClass(HeaderMissingException::class)]
final class ExceptionTest extends TestCase
{
    public function testFileNotFoundExceptionMessage(): void
    {
        $e = FileNotFoundException::fromPath('/some/file.csv');
        self::assertStringContainsString('/some/file.csv', $e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
        self::assertInstanceOf(\RuntimeException::class, $e);
    }

    public function testFileNotReadableExceptionMessage(): void
    {
        $e = FileNotReadableException::fromPath('/locked/file.csv');
        self::assertStringContainsString('/locked/file.csv', $e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
    }

    public function testInvalidResourceExceptionMessage(): void
    {
        $e = InvalidResourceException::create();
        self::assertNotEmpty($e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
    }

    public function testColumnCountMismatchExceptionMessage(): void
    {
        $e = ColumnCountMismatchException::create(3, 2, 5);
        self::assertStringContainsString('3', $e->getMessage());
        self::assertStringContainsString('2', $e->getMessage());
        self::assertStringContainsString('5', $e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
    }

    public function testEmptySourceExceptionMessage(): void
    {
        $e = EmptySourceException::create();
        self::assertNotEmpty($e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
    }

    public function testHeaderMissingExceptionMessage(): void
    {
        $e = HeaderMissingException::create();
        self::assertNotEmpty($e->getMessage());
        self::assertInstanceOf(CsvException::class, $e);
    }

    public function testAllExceptionsExtendCsvException(): void
    {
        $exceptions = [
            FileNotFoundException::fromPath('/x'),
            FileNotReadableException::fromPath('/x'),
            InvalidResourceException::create(),
            ColumnCountMismatchException::create(1, 2, 3),
            EmptySourceException::create(),
            HeaderMissingException::create(),
        ];

        foreach ($exceptions as $e) {
            self::assertInstanceOf(CsvException::class, $e);
        }
    }

    public function testExceptionsAreThrowable(): void
    {
        $this->expectException(FileNotFoundException::class);
        throw FileNotFoundException::fromPath('/test.csv');
    }
}
