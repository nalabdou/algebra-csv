<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit;

use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\Exception\FileNotFoundException;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvFileAdapter::class)]
final class CsvFileAdapterTest extends TestCase
{
    private string $fixtures;
    private CsvFileAdapter $adapter;

    protected function setUp(): void
    {
        $this->fixtures = \dirname(__DIR__).'/Fixtures';
        $this->adapter = new CsvFileAdapter();
    }

    public function testSupportsExistingCsvPath(): void
    {
        self::assertTrue($this->adapter->supports("{$this->fixtures}/orders.csv"));
    }

    public function testSupportsSplFileInfo(): void
    {
        self::assertTrue($this->adapter->supports(new \SplFileInfo("{$this->fixtures}/orders.csv")));
    }

    public function testSupportsSplFileObject(): void
    {
        self::assertTrue($this->adapter->supports(new \SplFileObject("{$this->fixtures}/orders.csv")));
    }

    public function testDoesNotSupportNonexistentPath(): void
    {
        self::assertFalse($this->adapter->supports('/nonexistent/file.csv'));
    }

    public function testDoesNotSupportPlainArray(): void
    {
        self::assertFalse($this->adapter->supports([]));
    }

    public function testDoesNotSupportInteger(): void
    {
        self::assertFalse($this->adapter->supports(42));
    }

    public function testDoesNotSupportNull(): void
    {
        self::assertFalse($this->adapter->supports(null));
    }

    public function testDoesNotSupportResource(): void
    {
        $h = \fopen('php://temp', 'rb+');
        self::assertFalse($this->adapter->supports($h));
        \fclose($h);
    }

    public function testFromReturnsInstance(): void
    {
        $adapter = CsvFileAdapter::from(new CsvOptions(coerceTypes: true));
        self::assertInstanceOf(CsvFileAdapter::class, $adapter);
    }

    public function testFromPreservesOptions(): void
    {
        $opts = new CsvOptions(delimiter: ';', coerceTypes: true);
        $adapter = CsvFileAdapter::from($opts);
        self::assertSame(';', $adapter->getOptions()->delimiter);
        self::assertTrue($adapter->getOptions()->coerceTypes);
    }

    public function testFromSubclassReturnsSubclass(): void
    {
        $sub = new class(new CsvOptions()) extends CsvFileAdapter {};
        $adapter = $sub::from(new CsvOptions());
        self::assertInstanceOf($sub::class, $adapter);
    }

    public function testGetOptionsReturnsConstructorOptions(): void
    {
        $opts = new CsvOptions(delimiter: ',', hasHeader: false);
        $adapter = new CsvFileAdapter($opts);
        self::assertSame($opts, $adapter->getOptions());
    }

    public function testParsesCommaCsv(): void
    {
        $result = $this->adapter->toArray("{$this->fixtures}/orders.csv");

        self::assertCount(7, $result);
        self::assertSame('1', $result[0]['id']);
        self::assertSame('paid', $result[0]['status']);
        self::assertSame('500', $result[0]['amount']);
        self::assertSame('Nord', $result[0]['region']);
    }

    public function testParsesFromSplFileInfo(): void
    {
        $file = new \SplFileInfo("{$this->fixtures}/orders.csv");
        $result = $this->adapter->toArray($file);

        self::assertCount(7, $result);
    }

    public function testParsesSemicolonCsvWithExplicitDelimiter(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(delimiter: ';'));
        $result = $adapter->toArray("{$this->fixtures}/semicolon.csv");

        self::assertCount(3, $result);
        self::assertSame('Alice', $result[0]['name']);
    }

    public function testAutoDetectsSemicolonDelimiter(): void
    {
        $result = $this->adapter->toArray("{$this->fixtures}/semicolon.csv");

        self::assertCount(3, $result);
        self::assertSame('Alice', $result[0]['name']);
    }

    public function testParsesTsvFile(): void
    {
        $result = $this->adapter->toArray("{$this->fixtures}/tab.tsv");

        self::assertCount(3, $result);
        self::assertSame('Laptop', $result[0]['product']);
    }

    public function testCoercesTypesWhenOptionSet(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(coerceTypes: true));
        $result = $adapter->toArray("{$this->fixtures}/orders.csv");

        self::assertSame(1, $result[0]['id']);
        self::assertSame(500, $result[0]['amount']);
    }

    public function testSkipsEmptyRowsWhenOptionSet(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(skipEmpty: true));
        $result = $adapter->toArray("{$this->fixtures}/empty_rows.csv");

        self::assertCount(3, $result);
    }

    public function testParsesNoHeaderCsv(): void
    {
        $adapter = new CsvFileAdapter(new CsvOptions(hasHeader: false));
        $result = $adapter->toArray("{$this->fixtures}/no_header.csv");

        self::assertCount(3, $result);
        self::assertSame('Alice', $result[0][1]);
    }

    public function testThrowsOnNonexistentFile(): void
    {
        $this->expectException(FileNotFoundException::class);
        $this->adapter->toArray('/nonexistent/file.csv');
    }

    public function testResultIsZeroIndexed(): void
    {
        $result = $this->adapter->toArray("{$this->fixtures}/orders.csv");
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(6, $result);
        self::assertArrayNotHasKey(7, $result);
    }

    public function testRowsAreAssociativeArrays(): void
    {
        $result = $this->adapter->toArray("{$this->fixtures}/orders.csv");
        self::assertArrayHasKey('id', $result[0]);
        self::assertArrayHasKey('status', $result[0]);
        self::assertArrayHasKey('amount', $result[0]);
        self::assertArrayHasKey('region', $result[0]);
    }

    public function testFromWithFluentOptions(): void
    {
        $opts = (new CsvOptions())->withDelimiter(';')->withTypeCoercion();
        $adapter = CsvFileAdapter::from($opts);
        $result = $adapter->toArray("{$this->fixtures}/semicolon.csv");

        self::assertIsInt($result[0]['amount']);
    }
}
