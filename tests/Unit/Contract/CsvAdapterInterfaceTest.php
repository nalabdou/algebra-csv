<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\Contract;

use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvFileAdapter::class)]
#[CoversClass(CsvStringAdapter::class)]
#[CoversClass(CsvResourceAdapter::class)]
final class CsvAdapterInterfaceTest extends TestCase
{
    public static function adapterProvider(): array
    {
        return [
            'file' => [CsvFileAdapter::class],
            'string' => [CsvStringAdapter::class],
            'resource' => [CsvResourceAdapter::class],
        ];
    }

    #[DataProvider('adapterProvider')]
    public function testImplementsCsvAdapterInterface(string $class): void
    {
        self::assertInstanceOf(CsvAdapterInterface::class, new $class());
    }

    #[DataProvider('adapterProvider')]
    public function testFromReturnsCorrectType(string $class): void
    {
        $adapter = $class::from(new CsvOptions());
        self::assertInstanceOf($class, $adapter);
    }

    #[DataProvider('adapterProvider')]
    public function testFromPreservesOptions(string $class): void
    {
        $opts = new CsvOptions(delimiter: ';', coerceTypes: true, skipEmpty: true);
        $adapter = $class::from($opts);

        self::assertSame(';', $adapter->getOptions()->delimiter);
        self::assertTrue($adapter->getOptions()->coerceTypes);
        self::assertTrue($adapter->getOptions()->skipEmpty);
    }

    #[DataProvider('adapterProvider')]
    public function testFromCreatesNewInstanceEachTime(string $class): void
    {
        $opts = new CsvOptions();
        $a = $class::from($opts);
        $b = $class::from($opts);

        self::assertNotSame($a, $b);
    }

    #[DataProvider('adapterProvider')]
    public function testGetOptionsReturnsDefaultOptions(string $class): void
    {
        $adapter = new $class();
        $opts = $adapter->getOptions();

        self::assertInstanceOf(CsvOptions::class, $opts);
        self::assertNull($opts->delimiter);
        self::assertFalse($opts->coerceTypes);
    }
}
