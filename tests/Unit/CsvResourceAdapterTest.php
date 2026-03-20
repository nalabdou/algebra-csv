<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit;

use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvResourceAdapter::class)]
final class CsvResourceAdapterTest extends TestCase
{
    private CsvResourceAdapter $adapter;

    protected function setUp(): void
    {
        $this->adapter = new CsvResourceAdapter();
    }

    private function handle(string $content): mixed
    {
        $h = \fopen('php://temp', 'rb+');
        \fwrite($h, $content);
        \rewind($h);

        return $h;
    }

    public function testSupportsStreamResource(): void
    {
        $h = $this->handle('');
        self::assertTrue($this->adapter->supports($h));
        \fclose($h);
    }

    public function testDoesNotSupportString(): void
    {
        self::assertFalse($this->adapter->supports('/path/to/file.csv'));
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
        $adapter = CsvResourceAdapter::from(new CsvOptions());
        self::assertInstanceOf(CsvResourceAdapter::class, $adapter);
    }

    public function testFromPreservesOptions(): void
    {
        $opts = new CsvOptions(delimiter: "\t", coerceTypes: true);
        $adapter = CsvResourceAdapter::from($opts);
        self::assertSame("\t", $adapter->getOptions()->delimiter);
        self::assertTrue($adapter->getOptions()->coerceTypes);
    }

    public function testGetOptionsReturnsDefault(): void
    {
        $opts = $this->adapter->getOptions();
        self::assertNull($opts->delimiter);
        self::assertFalse($opts->coerceTypes);
    }

    public function testParsesCsvResource(): void
    {
        $h = $this->handle("id,name,amount\n1,Alice,100\n2,Bob,200\n");
        $result = $this->adapter->toArray($h);
        \fclose($h);

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0]['name']);
    }

    public function testParsesWithCustomOptions(): void
    {
        $adapter = new CsvResourceAdapter(new CsvOptions(coerceTypes: true));
        $h = $this->handle("id,amount\n1,100\n");
        $result = $adapter->toArray($h);
        \fclose($h);

        self::assertSame(1, $result[0]['id']);
        self::assertSame(100, $result[0]['amount']);
    }

    public function testRewindsExhaustedHandle(): void
    {
        $h = $this->handle("id,name\n1,Alice\n");
        while (false !== \fgets($h)) {
        } // exhaust

        $result = $this->adapter->toArray($h);
        \fclose($h);

        self::assertCount(1, $result);
    }

    public function testEmptyResourceReturnsEmptyArray(): void
    {
        $h = $this->handle('');
        $result = $this->adapter->toArray($h);
        \fclose($h);

        self::assertSame([], $result);
    }

    public function testParsesTabSeparated(): void
    {
        $adapter = CsvResourceAdapter::from(new CsvOptions(delimiter: "\t"));
        $h = $this->handle("id\tname\n1\tAlice\n");
        $result = $adapter->toArray($h);
        \fclose($h);

        self::assertSame('Alice', $result[0]['name']);
    }

    public function testParsesMultipleRowsFromHandle(): void
    {
        $h = $this->handle("col1,col2\nA,B\nC,D\nE,F\n");
        $result = $this->adapter->toArray($h);
        \fclose($h);

        self::assertCount(3, $result);
        self::assertSame('E', $result[2]['col1']);
    }
}
