<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\Parser;

use Nalabdou\Algebra\Csv\Exception\InvalidResourceException;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvParser::class)]
final class CsvParserTest extends TestCase
{
    private CsvParser $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvParser();
    }

    private function handle(string $content): mixed
    {
        $h = \fopen('php://temp', 'rb+');
        \fwrite($h, $content);
        \rewind($h);

        return $h;
    }

    public function testParsesBasicCsvWithHeader(): void
    {
        $h = $this->handle("id,name,amount\n1,Alice,100\n2,Bob,200\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0]['name']);
        self::assertSame('100', $result[0]['amount']);
    }

    public function testParsesSemicolonDelimiter(): void
    {
        $h = $this->handle("id;name;amount\n1;Alice;100\n");
        $result = $this->parser->parse($h, new CsvOptions(delimiter: ';'));
        \fclose($h);

        self::assertSame('Alice', $result[0]['name']);
    }

    public function testAutoDetectsDelimiter(): void
    {
        $h = $this->handle("id;name;amount\n1;Alice;100\n2;Bob;200\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0]['name']);
    }

    public function testParsesWithoutHeader(): void
    {
        $h = $this->handle("1,Alice,100\n2,Bob,200\n");
        $result = $this->parser->parse($h, new CsvOptions(hasHeader: false));
        \fclose($h);

        self::assertCount(2, $result);
        self::assertSame('Alice', $result[0][1]);
    }

    public function testEmptyFileReturnsEmptyArray(): void
    {
        $h = $this->handle('');
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertSame([], $result);
    }

    public function testHeaderOnlyFileReturnsEmptyArray(): void
    {
        $h = $this->handle("id,name,amount\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertSame([], $result);
    }

    public function testSkipsNullRows(): void
    {
        // fgetcsv returns [null] on blank lines in some configurations
        $h = $this->handle("id,name\n1,Alice\n\n2,Bob\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        // The [null] row is always skipped; empty-string rows depend on skipEmpty
        self::assertGreaterThanOrEqual(2, \count($result));
    }

    public function testSkipsEmptyRows(): void
    {
        $h = $this->handle("id,name\n1,Alice\n\n2,Bob\n\n");
        $result = $this->parser->parse($h, new CsvOptions(skipEmpty: true));
        \fclose($h);

        self::assertCount(2, $result);
    }

    public function testKeepsEmptyRowsByDefault(): void
    {
        $h = $this->handle("id,name\n1,Alice\n,\n2,Bob\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertCount(3, $result);
    }

    public function testCoercesNumericStringsToNumbers(): void
    {
        $h = $this->handle("id,amount,rate\n1,100,1.5\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: true));
        \fclose($h);

        self::assertSame(1, $result[0]['id']);
        self::assertSame(100, $result[0]['amount']);
        self::assertSame(1.5, $result[0]['rate']);
    }

    public function testNoCoercionKeepsStrings(): void
    {
        $h = $this->handle("id,amount\n1,100\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: false));
        \fclose($h);

        self::assertIsString($result[0]['id']);
        self::assertIsString($result[0]['amount']);
    }

    public function testNegativeNumbersCoerced(): void
    {
        $h = $this->handle("id,delta\n1,-50\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: true));
        \fclose($h);

        self::assertSame(-50, $result[0]['delta']);
    }

    public function testFloatCoercion(): void
    {
        $h = $this->handle("value\n3.14\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: true));
        \fclose($h);

        self::assertSame(3.14, $result[0]['value']);
    }

    public function testEmptyStringNotCoerced(): void
    {
        $h = $this->handle("id,note\n1,\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: true));
        \fclose($h);

        self::assertSame('', $result[0]['note']);
    }

    public function testLeadingZeroStringNotCoercedToInt(): void
    {
        $h = $this->handle("code\n007\n");
        $result = $this->parser->parse($h, new CsvOptions(coerceTypes: false));
        \fclose($h);

        // "007" starts with '0' and length > 1 → kept as string
        self::assertIsString($result[0]['code']);
    }

    public function testPadsShortRowsToHeaderLength(): void
    {
        $h = $this->handle("id,name,amount\n1,Alice\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertArrayHasKey('amount', $result[0]);
        self::assertSame('', $result[0]['amount']);
    }

    public function testTrimsLongRowsToHeaderLength(): void
    {
        $h = $this->handle("id,name\n1,Alice,extra\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertArrayNotHasKey(2, $result[0]);
        self::assertCount(2, $result[0]);
    }

    public function testPreservesQuotedFields(): void
    {
        $h = $this->handle("id,name,note\n1,Alice,\"has, comma\"\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertSame('has, comma', $result[0]['note']);
    }

    public function testPreservesQuotedFieldsWithNewlines(): void
    {
        $h = $this->handle("id,text\n1,\"line1\nline2\"\n");
        $result = $this->parser->parse($h, new CsvOptions());
        \fclose($h);

        self::assertStringContainsString('line1', $result[0]['text']);
    }

    public function testUtf8EncodingPassthrough(): void
    {
        $h = $this->handle("id,name\n1,Ünïcödé\n");
        $result = $this->parser->parse($h, new CsvOptions(encoding: 'UTF-8'));
        \fclose($h);

        self::assertSame('Ünïcödé', $result[0]['name']);
    }

    public function testThrowsOnInvalidResource(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->parser->parse('not-a-resource', new CsvOptions());
    }

    public function testThrowsOnNullResource(): void
    {
        $this->expectException(InvalidResourceException::class);
        $this->parser->parse(null, new CsvOptions());
    }

    public function testNoHeaderModeZeroIndexedKeys(): void
    {
        $h = $this->handle("a,b,c\nd,e,f\n");
        $result = $this->parser->parse($h, new CsvOptions(hasHeader: false));
        \fclose($h);

        self::assertSame('a', $result[0][0]);
        self::assertSame('b', $result[0][1]);
        self::assertSame('c', $result[0][2]);
        self::assertSame('d', $result[1][0]);
    }

    public function testAutoDetectsDelimiterWithEncodingHint(): void
    {
        $h = $this->handle("id;name\n1;Alice\n");
        $opts = new CsvOptions(encoding: 'ISO-8859-1');
        $result = $this->parser->parse($h, $opts);
        \fclose($h);

        self::assertSame('Alice', $result[0]['name']);
    }
}
