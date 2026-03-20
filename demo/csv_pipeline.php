<?php

declare(strict_types=1);

/**
 * Demo: CSV Pipeline — core adapter features in one script.
 *
 * Covers file paths, raw strings, resource handles, auto-detection,
 * named constructors, window functions, parallel pipelines, and pivot.
 *
 * Run:  php demo/csv_pipeline.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Algebra;
use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

Algebra::adapters()->register(CsvFileAdapter::from(new CsvOptions(coerceTypes: true)), 100);
Algebra::adapters()->register(CsvStringAdapter::from(new CsvOptions()), 90);
Algebra::adapters()->register(CsvResourceAdapter::from(new CsvOptions()), 80);

$ordersPath = __DIR__.'/../tests/Fixtures/orders.csv';

function section(string $title): void
{
    echo "\n\033[1;36m{$title}\033[0m\n";
}

function row(string $line): void
{
    echo "  {$line}\n";
}

section('1. Paid orders grouped by region');

$byRegion = Algebra::from($ordersPath)
    ->where("item['status'] == 'paid'")
    ->groupBy('region')
    ->aggregate(['revenue' => 'sum(amount)', 'orders' => 'count(*)'])
    ->orderBy('revenue', 'desc')
    ->toArray();

foreach ($byRegion as $r) {
    row(sprintf('%-8s  revenue=%d  orders=%d', $r['_group'], $r['revenue'], $r['orders']));
}

section('2. Running total — paid orders sorted asc');

$running = Algebra::from($ordersPath)
    ->where("item['status'] == 'paid'")
    ->orderBy('amount', 'asc')
    ->window('running_sum', field: 'amount', as: 'cumulative')
    ->toArray();

foreach ($running as $r) {
    row(sprintf('id=%-2s  amount=%d  cumulative=%.0f', $r['id'], $r['amount'], $r['cumulative']));
}

section('3. Partition — high-value vs standard orders');

$partition = Algebra::from($ordersPath)
    ->where("item['status'] == 'paid'")
    ->partition("item['amount'] > 400");

row(sprintf('High-value (>400): %d  (%.0f%%)', $partition->passCount(), $partition->passRate() * 100));
row(sprintf('Standard:          %d', $partition->failCount()));

section('4. Raw CSV string via CsvString::from()');

$csv = CsvString::from(
    "product,category,price\n"
        ."Laptop,Electronics,999\n"
        ."Mouse,Electronics,29\n"
        ."Desk,Furniture,350\n"
        ."Chair,Furniture,250\n"
        ."Monitor,Electronics,349\n",
    new CsvOptions(coerceTypes: true),
);

$byCategory = Algebra::from($csv)
    ->groupBy('category')
    ->aggregate(['avg_price' => 'avg(price)', 'count' => 'count(*)'])
    ->orderBy('avg_price', 'desc')
    ->toArray();

foreach ($byCategory as $r) {
    row(sprintf('%-12s  avg=%.0f  items=%d', $r['_group'], $r['avg_price'], $r['count']));
}

section('5. Resource handle — status tally');

$handle = fopen($ordersPath, 'r');
$tally = Algebra::from($handle)->tally('status');
fclose($handle);

foreach ($tally as $status => $count) {
    row(sprintf('%-12s  %d', $status, $count));
}

section('6. Auto-detect semicolon delimiter');

$semicolonPath = __DIR__.'/../tests/Fixtures/semicolon.csv';

$result = Algebra::from($semicolonPath)
    ->where("item['amount'] > 100")
    ->toArray();

foreach ($result as $r) {
    row(sprintf('id=%-2s  name=%-6s  amount=%s', $r['id'], $r['name'], $r['amount']));
}

section('7. Pivot — region × month revenue');

$pivotCsv = CsvString::from(
    "month,region,revenue\n"
        ."Jan,Nord,1000\nJan,Sud,800\n"
        ."Feb,Nord,1200\nFeb,Sud,600\n"
        ."Mar,Nord,900\nMar,Sud,1100\n",
    new CsvOptions(coerceTypes: true),
);

$pivotResult = Algebra::from($pivotCsv)
    ->pivot(rows: 'month', cols: 'region', value: 'revenue', aggregateFn: 'sum')
    ->toArray();

row(sprintf('%-6s  %-8s  %-8s', 'Month', 'Nord', 'Sud'));
foreach ($pivotResult as $r) {
    row(sprintf('%-6s  %-8d  %-8d', $r['_row'], $r['Nord'] ?? 0, $r['Sud'] ?? 0));
}

section('8. Parallel pipelines');

$results = Algebra::parallel([
    'paid' => Algebra::from($ordersPath)->where("item['status'] == 'paid'"),
    'pending' => Algebra::from($ordersPath)->where("item['status'] == 'pending'"),
    'top3' => Algebra::from($ordersPath)->orderBy('amount', 'desc')->limit(3),
]);

row(sprintf(
    'paid=%d  pending=%d  top3_max=%d',
    count($results['paid']),
    count($results['pending']),
    $results['top3'][0]['amount'],
));

echo "\n\033[1;32mDone.\033[0m\n\n";
