<?php

declare(strict_types=1);

/**
 * Demo: Adapters Showcase — all three adapters and their ::from() constructors.
 *
 * Shows how CsvFileAdapter, CsvStringAdapter, and CsvResourceAdapter each
 * behave and how to switch between them using the shared interface.
 *
 * Run:  php demo/adapters_showcase.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\Contract\CsvAdapterInterface;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

function printRows(array $rows, int $limit = 3): void
{
    foreach (array_slice($rows, 0, $limit) as $row) {
        echo '  '.json_encode($row, \JSON_UNESCAPED_UNICODE)."\n";
    }
    if (count($rows) > $limit) {
        echo '  ... +'.(count($rows) - $limit)." more\n";
    }
}

function printAdapter(CsvAdapterInterface $adapter): void
{
    $class = (new ReflectionClass($adapter))->getShortName();
    $opts = $adapter->getOptions();
    echo "  Adapter:   {$class}\n";
    echo '  Options:   '.json_encode([
        'delimiter' => $opts->delimiter ?? 'auto',
        'hasHeader' => $opts->hasHeader,
        'coerceTypes' => $opts->coerceTypes,
        'skipEmpty' => $opts->skipEmpty,
    ])."\n";
}

$ordersPath = __DIR__.'/../tests/Fixtures/orders.csv';
$semicolonPath = __DIR__.'/../tests/Fixtures/semicolon.csv';
$tabPath = __DIR__.'/../tests/Fixtures/tab.tsv';

echo "\n\033[1;33m— CsvFileAdapter —\033[0m\n";

$fileAdapter = CsvFileAdapter::from(new CsvOptions(coerceTypes: true));
printAdapter($fileAdapter);

echo "\n  File: orders.csv (comma, auto-detected)\n";
printRows($fileAdapter->toArray($ordersPath));

echo "\n  File: semicolon.csv (semicolon, auto-detected)\n";
$semiAdapter = CsvFileAdapter::from(new CsvOptions());
printRows($semiAdapter->toArray($semicolonPath));

echo "\n  File: tab.tsv (tab, auto-detected)\n";
printRows($semiAdapter->toArray($tabPath));

echo "\n  File: orders.csv — SplFileInfo input\n";
$spl = new SplFileInfo($ordersPath);
$rows = $fileAdapter->toArray($spl);
printRows($rows);

echo "\n  supports() checks\n";
echo '  string path exists: '.var_export($fileAdapter->supports($ordersPath), true)."\n";
echo '  SplFileInfo:        '.var_export($fileAdapter->supports(new SplFileInfo($ordersPath)), true)."\n";
echo '  missing path:       '.var_export($fileAdapter->supports('/tmp/no_such_file.csv'), true)."\n";

echo "\n\033[1;33m— CsvStringAdapter —\033[0m\n";

$stringAdapter = CsvStringAdapter::from(new CsvOptions());
printAdapter($stringAdapter);

$rawCsv = CsvString::from("id,name,score\n1,Alice,95\n2,Bob,82\n3,Carol,91\n");
echo "\n  CsvString (comma, header)\n";
printRows($stringAdapter->toArray($rawCsv));

$rawSemicolon = CsvString::from(
    "id;name;score\n1;Alice;95\n2;Bob;82\n",
    new CsvOptions(delimiter: ';', coerceTypes: true),
);
echo "\n  CsvString (semicolon, coerced)\n";
printRows($stringAdapter->toArray($rawSemicolon));

echo "\n  Empty CsvString returns []\n";
$empty = $stringAdapter->toArray(CsvString::from(''));
echo '  '.json_encode($empty)."\n";

echo "\n  supports() checks\n";
echo '  CsvString:    '.var_export($stringAdapter->supports($rawCsv), true)."\n";
echo '  plain string: '.var_export($stringAdapter->supports('id,name'), true)."\n";

echo "\n\033[1;33m— CsvResourceAdapter —\033[0m\n";

$resourceAdapter = CsvResourceAdapter::from(new CsvOptions(coerceTypes: true));
printAdapter($resourceAdapter);

$handle = fopen($ordersPath, 'r');
echo "\n  Resource handle from fopen()\n";
printRows($resourceAdapter->toArray($handle));
fclose($handle);

$tmp = fopen('php://temp', 'rb+');
fwrite($tmp, "col1,col2,col3\nA,B,C\nD,E,F\n");
rewind($tmp);
echo "\n  php://temp handle\n";
printRows($resourceAdapter->toArray($tmp));
fclose($tmp);

echo "\n  Exhausted handle is rewound automatically\n";
$h = fopen($ordersPath, 'r');
while (false !== fgets($h)) {
}
$rows = $resourceAdapter->toArray($h);
fclose($h);
echo '  rows after exhaust+rewind: '.count($rows)."\n";

echo "\n  supports() checks\n";
$h = fopen('php://temp', 'rb+');
echo '  open stream:  '.var_export($resourceAdapter->supports($h), true)."\n";
fclose($h);
echo '  string:       '.var_export($resourceAdapter->supports('/tmp/file.csv'), true)."\n";

echo "\n\033[1;33m— Interface polymorphism —\033[0m\n";

$adapters = [
    CsvFileAdapter::from(new CsvOptions()),
    CsvStringAdapter::from(new CsvOptions()),
    CsvResourceAdapter::from(new CsvOptions()),
];

foreach ($adapters as $adapter) {
    $class = (new ReflectionClass($adapter))->getShortName();
    $opts = $adapter->getOptions();
    printf(
        "  %-22s  delimiter=%s  coerce=%s\n",
        $class,
        $opts->delimiter ?? 'auto',
        $opts->coerceTypes ? 'true' : 'false',
    );
}

echo "\n\033[1;32mDone.\033[0m\n\n";
