<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\ValueObject;

use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvOptions::class)]
final class CsvOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $opts = new CsvOptions();
        self::assertNull($opts->delimiter);
        self::assertSame('"', $opts->enclosure);
        self::assertSame('\\', $opts->escape);
        self::assertTrue($opts->hasHeader);
        self::assertNull($opts->encoding);
        self::assertFalse($opts->skipEmpty);
        self::assertFalse($opts->coerceTypes);
    }

    public function testConstructorAllArgs(): void
    {
        $opts = new CsvOptions(
            delimiter: ';',
            enclosure: "'",
            escape: '/',
            hasHeader: false,
            encoding: 'ISO-8859-1',
            skipEmpty: true,
            coerceTypes: true,
        );

        self::assertSame(';', $opts->delimiter);
        self::assertSame("'", $opts->enclosure);
        self::assertSame('/', $opts->escape);
        self::assertFalse($opts->hasHeader);
        self::assertSame('ISO-8859-1', $opts->encoding);
        self::assertTrue($opts->skipEmpty);
        self::assertTrue($opts->coerceTypes);
    }

    public function testWithDelimiterReturnsNewInstance(): void
    {
        $a = new CsvOptions();
        $b = $a->withDelimiter(';');

        self::assertNull($a->delimiter);
        self::assertSame(';', $b->delimiter);
        self::assertNotSame($a, $b);
    }

    public function testWithDelimiterPreservesOtherFields(): void
    {
        $a = new CsvOptions(enclosure: "'", coerceTypes: true);
        $b = $a->withDelimiter(';');

        self::assertSame("'", $b->enclosure);
        self::assertTrue($b->coerceTypes);
    }

    public function testWithEncoding(): void
    {
        $opts = (new CsvOptions())->withEncoding('Windows-1252');
        self::assertSame('Windows-1252', $opts->encoding);
    }

    public function testWithEncodingPreservesOtherFields(): void
    {
        $a = new CsvOptions(delimiter: ',', coerceTypes: true);
        $b = $a->withEncoding('ISO-8859-1');

        self::assertSame(',', $b->delimiter);
        self::assertTrue($b->coerceTypes);
    }

    public function testWithoutHeader(): void
    {
        $opts = (new CsvOptions())->withoutHeader();
        self::assertFalse($opts->hasHeader);
    }

    public function testWithoutHeaderPreservesOtherFields(): void
    {
        $a = new CsvOptions(delimiter: ';', coerceTypes: true);
        $b = $a->withoutHeader();

        self::assertSame(';', $b->delimiter);
        self::assertTrue($b->coerceTypes);
    }

    public function testWithTypeCoercion(): void
    {
        $opts = (new CsvOptions())->withTypeCoercion();
        self::assertTrue($opts->coerceTypes);
    }

    public function testWithTypeCoercionPreservesOtherFields(): void
    {
        $a = new CsvOptions(delimiter: '|', skipEmpty: true);
        $b = $a->withTypeCoercion();

        self::assertSame('|', $b->delimiter);
        self::assertTrue($b->skipEmpty);
    }

    public function testWithSkipEmpty(): void
    {
        $opts = (new CsvOptions())->withSkipEmpty();
        self::assertTrue($opts->skipEmpty);
    }

    public function testWithSkipEmptyPreservesOtherFields(): void
    {
        $a = new CsvOptions(encoding: 'UTF-8', coerceTypes: true);
        $b = $a->withSkipEmpty();

        self::assertSame('UTF-8', $b->encoding);
        self::assertTrue($b->coerceTypes);
    }

    public function testChainingPreservesOtherFields(): void
    {
        $opts = (new CsvOptions(enclosure: "'"))
            ->withDelimiter(';')
            ->withTypeCoercion()
            ->withSkipEmpty();

        self::assertSame(';', $opts->delimiter);
        self::assertSame("'", $opts->enclosure);
        self::assertTrue($opts->coerceTypes);
        self::assertTrue($opts->skipEmpty);
    }

    public function testFullChain(): void
    {
        $opts = (new CsvOptions())
            ->withDelimiter(',')
            ->withEncoding('UTF-8')
            ->withoutHeader()
            ->withTypeCoercion()
            ->withSkipEmpty();

        self::assertSame(',', $opts->delimiter);
        self::assertSame('UTF-8', $opts->encoding);
        self::assertFalse($opts->hasHeader);
        self::assertTrue($opts->coerceTypes);
        self::assertTrue($opts->skipEmpty);
    }

    public function testImmutability(): void
    {
        $original = new CsvOptions();
        $original->withDelimiter(';');
        $original->withEncoding('ISO-8859-1');

        self::assertNull($original->delimiter);
        self::assertNull($original->encoding);
    }

    public function testEachWithMethodReturnsDistinctInstance(): void
    {
        $base = new CsvOptions();
        $a = $base->withDelimiter(';');
        $b = $base->withDelimiter(',');

        self::assertNotSame($a, $b);
        self::assertSame(';', $a->delimiter);
        self::assertSame(',', $b->delimiter);
    }
}
