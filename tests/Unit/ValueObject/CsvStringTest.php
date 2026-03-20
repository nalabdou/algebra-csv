<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\ValueObject;

use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvString::class)]
final class CsvStringTest extends TestCase
{
    public function testStoresContent(): void
    {
        $csv = new CsvString("id,name\n1,Alice\n");
        self::assertSame("id,name\n1,Alice\n", $csv->content);
    }

    public function testDefaultOptions(): void
    {
        $csv = new CsvString("id,name\n1,Alice\n");
        self::assertInstanceOf(CsvOptions::class, $csv->options);
        self::assertNull($csv->options->delimiter);
        self::assertFalse($csv->options->coerceTypes);
    }

    public function testCustomOptions(): void
    {
        $opts = new CsvOptions(delimiter: ';');
        $csv = new CsvString("id;name\n1;Alice\n", $opts);
        self::assertSame(';', $csv->options->delimiter);
    }

    public function testOptionsAreImmutable(): void
    {
        $opts = new CsvOptions(coerceTypes: true);
        $csv = new CsvString('content', $opts);
        self::assertSame($opts, $csv->options);
    }

    public function testIsEmptyTrueForEmptyString(): void
    {
        self::assertTrue((new CsvString(''))->isEmpty());
    }

    public function testIsEmptyTrueForWhitespaceOnly(): void
    {
        self::assertTrue((new CsvString("   \n  "))->isEmpty());
    }

    public function testIsEmptyTrueForTabsAndNewlines(): void
    {
        self::assertTrue((new CsvString("\t\n\t"))->isEmpty());
    }

    public function testIsEmptyFalseForContent(): void
    {
        self::assertFalse((new CsvString("id,name\n1,Alice\n"))->isEmpty());
    }

    public function testIsEmptyFalseForSingleChar(): void
    {
        self::assertFalse((new CsvString('a'))->isEmpty());
    }

    public function testIsEmptyFalseForHeaderOnly(): void
    {
        self::assertFalse((new CsvString("id,name\n"))->isEmpty());
    }

    public function testFromStaticConstructor(): void
    {
        $csv = CsvString::from("id,name\n1,Alice\n");
        self::assertInstanceOf(CsvString::class, $csv);
        self::assertSame("id,name\n1,Alice\n", $csv->content);
    }

    public function testFromWithOptions(): void
    {
        $opts = new CsvOptions(delimiter: ';', coerceTypes: true);
        $csv = CsvString::from("id;name\n1;Alice\n", $opts);
        self::assertSame(';', $csv->options->delimiter);
        self::assertTrue($csv->options->coerceTypes);
    }

    public function testFromDefaultOptionsMatchConstructor(): void
    {
        $via_new = new CsvString("a,b\n");
        $via_from = CsvString::from("a,b\n");

        self::assertSame($via_new->content, $via_from->content);
        self::assertEquals($via_new->options, $via_from->options);
    }
}
