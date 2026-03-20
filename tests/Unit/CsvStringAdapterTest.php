<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit;

use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvStringAdapter::class)]
final class CsvStringAdapterTest extends TestCase
{
    private CsvStringAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new CsvStringAdapter();
    }

    public function testSupportsCsvString(): void
    {
        self::assertTrue($this->adapter->supports(new CsvString("id,name\n1,Alice\n")));
    }

    public function testDoesNotSupportPlainString(): void
    {
        self::assertFalse($this->adapter->supports("id,name\n1,Alice\n"));
    }

    public function testDoesNotSupportArray(): void
    {
        self::assertFalse($this->adapter->supports([]));
    }

    public function testDoesNotSupportNull(): void
    {
        self::assertFalse($this->adapter->supports(null));
    }

    public function testDoesNotSupportInteger(): void
    {
        self::assertFalse($this->adapter->supports(42));
    }

    public function testFromReturnsInstance(): void
    {
        $adapter = CsvStringAdapter::from(new CsvOptions());
        self::assertInstanceOf(CsvStringAdapter::class, $adapter);
    }

    public function testFromPreservesOptions(): void
    {
        $opts = new CsvOptions(coerceTypes: true, skipEmpty: true);
        $adapter = CsvStringAdapter::from($opts);
        self::assertTrue($adapter->getOptions()->coerceTypes);
        self::assertTrue($adapter->getOptions()->skipEmpty);
    }

    public function testGetOptionsReturnsDefault(): void
    {
        $opts = $this->adapter->getOptions();
        self::assertNull($opts->delimiter);
        self::assertFalse($opts->coerceTypes);
    }

    public function testParsesBasicCsvString(): void
    {
        $csv = new CsvString("id,name,amount\n1,Alice,100\n2,Bob,200\n");
        $result = $this->adapter->toArray($csv);

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0]['name']);
        self::assertSame('100', $result[0]['amount']);
    }

    public function testParsesWithOptions(): void
    {
        $csv = new CsvString(
            "id;name;amount\n1;Alice;100\n",
            new CsvOptions(delimiter: ';', coerceTypes: true)
        );
        $result = $this->adapter->toArray($csv);

        self::assertSame('Alice', $result[0]['name']);
        self::assertSame(100, $result[0]['amount']);
    }

    public function testEmptyCsvStringReturnsEmptyArray(): void
    {
        $result = $this->adapter->toArray(new CsvString(''));
        self::assertSame([], $result);
    }

    public function testWhitespaceOnlyReturnsEmptyArray(): void
    {
        $result = $this->adapter->toArray(new CsvString("  \n  "));
        self::assertSame([], $result);
    }

    public function testHeaderOnlyReturnsEmptyArray(): void
    {
        $result = $this->adapter->toArray(new CsvString("id,name,amount\n"));
        self::assertSame([], $result);
    }

    public function testParsesMultipleRows(): void
    {
        $csv = new CsvString("a,b,c\n1,2,3\n4,5,6\n7,8,9\n");
        $result = $this->adapter->toArray($csv);

        self::assertCount(3, $result);
        self::assertSame('7', $result[2]['a']);
    }

    public function testOptionsEmbeddedInCsvStringTakePrecedence(): void
    {
        $csv = new CsvString("x;y\n1;2\n", new CsvOptions(delimiter: ';'));
        $result = $this->adapter->toArray($csv);

        self::assertArrayHasKey('x', $result[0]);
        self::assertArrayHasKey('y', $result[0]);
    }
}
