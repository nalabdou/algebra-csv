<?php

declare(strict_types=1);

/**
 * Demo: Custom parser via CsvParserInterface.
 *
 * Shows how to swap in a custom CsvParser or DelimiterDetector
 * by depending on the Contract interfaces rather than concrete classes.
 * Useful for preprocessing rows, injecting observability, or enforcing
 * strict column counts.
 *
 * Run:  php demo/custom_parser.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\Contract\CsvParserInterface;
use Nalabdou\Algebra\Csv\Contract\DelimiterDetectorInterface;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\Parser\DelimiterDetector;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

echo "\n\033[1;36mCustom DelimiterDetector — always returns pipe\033[0m\n";

$alwaysPipe = new class implements DelimiterDetectorInterface {
    public function detect(string $sample): string
    {
        return '|';
    }
};

$parser = new CsvParser($alwaysPipe);
$handle = fopen('php://temp', 'rb+');
fwrite($handle, "id|name|score\n1|Alice|95\n2|Bob|82\n");
rewind($handle);

$rows = $parser->parse($handle, new CsvOptions(delimiter: null));
fclose($handle);

foreach ($rows as $row) {
    echo '  '.json_encode($row)."\n";
}

echo "\n\033[1;36mLogging parser — wraps CsvParser and logs each row\033[0m\n";

$log = [];

$loggingParser = new class($log) implements CsvParserInterface {
    private CsvParser $inner;

    public function __construct(private array &$logRef)
    {
        $this->inner = new CsvParser();
    }

    public function parse(mixed $handle, CsvOptions $options): array
    {
        $rows = $this->inner->parse($handle, $options);

        foreach ($rows as $i => $row) {
            $this->logRef[] = "row {$i}: ".implode(', ', array_values($row));
        }

        return $rows;
    }
};

$adapter = new CsvStringAdapter(new CsvOptions(), $loggingParser);
$csv = CsvString::from("id,name\n1,Alice\n2,Bob\n3,Carol\n");
$rows = $adapter->toArray($csv);

echo '  Parsed '.count($rows)." rows.\n";
echo "  Log entries:\n";
foreach ($log as $entry) {
    echo "    {$entry}\n";
}

echo "\n\033[1;36mStrict parser — rejects rows with wrong column count\033[0m\n";

$strictParser = new class implements CsvParserInterface {
    private CsvParser $inner;

    public function __construct()
    {
        $this->inner = new CsvParser();
    }

    public function parse(mixed $handle, CsvOptions $options): array
    {
        $rows = $this->inner->parse($handle, $options);

        if (empty($rows)) {
            return $rows;
        }

        $expectedCount = count(reset($rows));

        foreach ($rows as $i => $row) {
            if (count($row) !== $expectedCount) {
                throw new RuntimeException("Strict parser: row {$i} has ".count($row)." columns, expected {$expectedCount}.");
            }
        }

        return $rows;
    }
};

$strictAdapter = new CsvStringAdapter(new CsvOptions(), $strictParser);

echo "\n  Valid CSV (all rows match header width):\n";
$valid = $strictAdapter->toArray(CsvString::from("a,b,c\n1,2,3\n4,5,6\n"));
foreach ($valid as $row) {
    echo '  '.json_encode($row)."\n";
}

echo "\n  After injecting custom parser, type still satisfies CsvParserInterface:\n";
echo '  implements CsvParserInterface: '.var_export($strictParser instanceof CsvParserInterface, true)."\n";

echo "\n\033[1;36mDelimiterDetector is also injectable\033[0m\n";

$realDetector = new DelimiterDetector();
echo '  Default detector on semicolon data:  '.$realDetector->detect("id;name;amount\n1;Alice;100\n")."\n";
echo '  Default detector on comma data:      '.$realDetector->detect("id,name,amount\n1,Alice,100\n")."\n";
echo '  Default detector on empty string:    '.$realDetector->detect('')."\n";

echo "\n\033[1;32mDone.\033[0m\n\n";
