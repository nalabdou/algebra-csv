<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Tests\Unit\Parser;

use Nalabdou\Algebra\Csv\Contract\DelimiterDetectorInterface;
use Nalabdou\Algebra\Csv\Parser\DelimiterDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DelimiterDetector::class)]
final class DelimiterDetectorTest extends TestCase
{
    private DelimiterDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new DelimiterDetector();
    }

    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(DelimiterDetectorInterface::class, $this->detector);
    }

    public static function delimiterProvider(): array
    {
        return [
            'comma' => ["id,name,amount\n1,Alice,100\n2,Bob,200\n",     ','],
            'semicolon' => ["id;name;amount\n1;Alice;100\n2;Bob;200\n",     ';'],
            'tab' => ["id\tname\tamount\n1\tAlice\t100\n2\tBob\t200\n", "\t"],
            'pipe' => ["id|name|amount\n1|Alice|100\n2|Bob|200\n",     '|'],
        ];
    }

    #[DataProvider('delimiterProvider')]
    public function testDetectsCommonDelimiters(string $sample, string $expected): void
    {
        self::assertSame($expected, $this->detector->detect($sample));
    }

    public function testDefaultsToCommaOnEmptyInput(): void
    {
        self::assertSame(',', $this->detector->detect(''));
    }

    public function testDefaultsToCommaOnWhitespaceOnly(): void
    {
        self::assertSame(',', $this->detector->detect("   \n   \n"));
    }

    public function testDefaultsToCommaWhenNoDelimiterFound(): void
    {
        self::assertSame(',', $this->detector->detect("hello\nworld\n"));
    }

    public function testConsistentAcrossMultipleRows(): void
    {
        $sample = "id,name,status,amount,region\n"
            ."1,Alice,paid,100,Nord\n"
            ."2,Bob,pending,200,Sud\n"
            ."3,Carol,paid,300,Nord\n";

        self::assertSame(',', $this->detector->detect($sample));
    }

    public function testPrefersHigherMeanOverLowerVariance(): void
    {
        // Semicolon appears more consistently than comma
        $sample = "id;name;amount\n1;Alice;100\n2;Bob;200\n";
        self::assertSame(';', $this->detector->detect($sample));
    }

    public function testSingleLineSample(): void
    {
        self::assertSame(',', $this->detector->detect("id,name,amount\n"));
    }

    public function testHandlesSampleWithoutTrailingNewline(): void
    {
        $result = $this->detector->detect("id,name,amount\n1,Alice,100");
        self::assertSame(',', $result);
    }

    public function testReturnsSingleCharacter(): void
    {
        $result = $this->detector->detect("a,b,c\n1,2,3\n");
        self::assertSame(1, \mb_strlen($result));
    }
}
