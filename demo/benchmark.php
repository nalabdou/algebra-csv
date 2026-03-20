<?php

declare(strict_types=1);

/**
 * Benchmark: algebra-csv adapter performance.
 *
 * Measures parsing time and throughput (rows/sec, MB/sec) across:
 *   - Row counts: 1 000 / 10 000 / 50 000
 *   - Adapters:   CsvFileAdapter, CsvStringAdapter, CsvResourceAdapter
 *   - Options:    no coercion vs coerceTypes:true, auto-detect vs explicit delimiter
 *   - Delimiters: comma, semicolon, tab
 *
 * Run:  php demo/benchmark.php
 *       php demo/benchmark.php --rows=100000
 *       php demo/benchmark.php --rows=50000 --runs=10
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\CsvResourceAdapter;
use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

$opts = getopt('', ['rows:', 'runs:']);
$defaultRows = (int) ($opts['rows'] ?? 0);
$runs = max(1, (int) ($opts['runs'] ?? 5));
$rowCounts = $defaultRows > 0 ? [$defaultRows] : [1_000, 10_000, 50_000];

function generateCsvString(int $rows, string $delimiter = ','): string
{
    $d = $delimiter;
    $buf = "id{$d}name{$d}status{$d}amount{$d}region{$d}created_at\n";
    $statuses = ['paid', 'pending', 'cancelled'];
    $regions = ['Nord', 'Sud', 'Est', 'Ouest'];

    for ($i = 1; $i <= $rows; ++$i) {
        $buf .= "{$i}{$d}User_{$i}{$d}{$statuses[$i % 3]}{$d}"
            .(100 + ($i % 900))."{$d}{$regions[$i % 4]}{$d}2024-0"
            .(($i % 9) + 1)."-01\n";
    }

    return $buf;
}

function bench(callable $fn, int $runs): array
{
    $times = [];

    for ($r = 0; $r < $runs; ++$r) {
        $start = hrtime(true);
        $rowCount = $fn();
        $elapsed = (hrtime(true) - $start) / 1e9;
        $times[] = ['elapsed' => $elapsed, 'rows' => $rowCount];
    }

    $elapsed = array_column($times, 'elapsed');
    $rows = $times[0]['rows'];

    return [
        'rows' => $rows,
        'min' => min($elapsed),
        'max' => max($elapsed),
        'avg' => array_sum($elapsed) / count($elapsed),
        'median' => (static function (array $a) {
            sort($a);
            $c = count($a);

            return 0 === $c % 2 ? ($a[$c / 2 - 1] + $a[$c / 2]) / 2 : $a[(int) ($c / 2)];
        })($elapsed),
    ];
}

function printResult(string $label, array $r, int $bytes): void
{
    $mbps = $bytes / $r['avg'] / 1_048_576;
    $krps = $r['rows'] / $r['avg'] / 1_000;
    printf(
        "  %-44s  avg=%6.1fms  med=%6.1fms  min=%5.1fms  max=%5.1fms  %6.0f k-rows/s  %5.1f MB/s\n",
        $label,
        $r['avg'] * 1000,
        $r['median'] * 1000,
        $r['min'] * 1000,
        $r['max'] * 1000,
        $krps,
        $mbps,
    );
}

echo "\n\033[1;36malgbra-csv Benchmark\033[0m\n";
printf("  PHP %s   runs=%d per scenario\n", \PHP_VERSION, $runs);
echo "\n";

foreach ($rowCounts as $rowCount) {
    printf("\033[1;33m%s rows\033[0m\n", number_format($rowCount));

    $commaCsv = generateCsvString($rowCount, ',');
    $semiCsv = generateCsvString($rowCount, ';');
    $tabCsv = generateCsvString($rowCount, "\t");
    $bytes = strlen($commaCsv);

    $tmpFile = sys_get_temp_dir().'/algebra_bench_'.$rowCount.'.csv';
    file_put_contents($tmpFile, $commaCsv);

    $tmpSemi = sys_get_temp_dir().'/algebra_bench_semi_'.$rowCount.'.csv';
    file_put_contents($tmpSemi, $semiCsv);

    $tmpTab = sys_get_temp_dir().'/algebra_bench_tab_'.$rowCount.'.csv';
    file_put_contents($tmpTab, $tabCsv);

    printf("  \033[0;36mCsvFileAdapter\033[0m\n");

    $r = bench(static function () use ($tmpFile) {
        return count(CsvFileAdapter::from(new CsvOptions())->toArray($tmpFile));
    }, $runs);
    printResult('file / comma / auto-detect', $r, $bytes);

    $r = bench(static function () use ($tmpFile) {
        return count(CsvFileAdapter::from(new CsvOptions(delimiter: ','))->toArray($tmpFile));
    }, $runs);
    printResult('file / comma / explicit delimiter', $r, $bytes);

    $r = bench(static function () use ($tmpFile) {
        return count(CsvFileAdapter::from(new CsvOptions(coerceTypes: true))->toArray($tmpFile));
    }, $runs);
    printResult('file / comma / coerceTypes:true', $r, $bytes);

    $r = bench(static function () use ($tmpSemi) {
        return count(CsvFileAdapter::from(new CsvOptions())->toArray($tmpSemi));
    }, $runs);
    printResult('file / semicolon / auto-detect', $r, strlen($semiCsv));

    $r = bench(static function () use ($tmpTab) {
        return count(CsvFileAdapter::from(new CsvOptions())->toArray($tmpTab));
    }, $runs);
    printResult('file / tab / auto-detect', $r, strlen($tabCsv));

    printf("\n  \033[0;36mCsvStringAdapter\033[0m\n");

    $csvStr = CsvString::from($commaCsv);

    $r = bench(static function () use ($csvStr) {
        return count(CsvStringAdapter::from(new CsvOptions())->toArray($csvStr));
    }, $runs);
    printResult('string / comma / auto-detect', $r, $bytes);

    $r = bench(static function () use ($csvStr) {
        return count(CsvStringAdapter::from(new CsvOptions(delimiter: ','))->toArray($csvStr));
    }, $runs);
    printResult('string / comma / explicit delimiter', $r, $bytes);

    $r = bench(static function () use ($csvStr) {
        return count(CsvStringAdapter::from(new CsvOptions(coerceTypes: true))->toArray($csvStr));
    }, $runs);
    printResult('string / comma / coerceTypes:true', $r, $bytes);

    $semiStr = CsvString::from($semiCsv, new CsvOptions(delimiter: ';'));

    $r = bench(static function () use ($semiStr) {
        return count(CsvStringAdapter::from(new CsvOptions())->toArray($semiStr));
    }, $runs);
    printResult('string / semicolon / explicit delimiter', $r, strlen($semiCsv));

    printf("\n  \033[0;36mCsvResourceAdapter\033[0m\n");

    $r = bench(static function () use ($tmpFile) {
        $h = fopen($tmpFile, 'r');
        $res = count(CsvResourceAdapter::from(new CsvOptions())->toArray($h));
        fclose($h);

        return $res;
    }, $runs);
    printResult('resource / comma / auto-detect', $r, $bytes);

    $r = bench(static function () use ($tmpFile) {
        $h = fopen($tmpFile, 'r');
        $res = count(CsvResourceAdapter::from(new CsvOptions(delimiter: ','))->toArray($h));
        fclose($h);

        return $res;
    }, $runs);
    printResult('resource / comma / explicit delimiter', $r, $bytes);

    $r = bench(static function () use ($tmpFile) {
        $h = fopen($tmpFile, 'r');
        $res = count(CsvResourceAdapter::from(new CsvOptions(coerceTypes: true))->toArray($h));
        fclose($h);

        return $res;
    }, $runs);
    printResult('resource / comma / coerceTypes:true', $r, $bytes);

    printf("\n  \033[0;36mDelimiter auto-detect overhead\033[0m\n");

    $r1 = bench(static function () use ($tmpFile) {
        return count(CsvFileAdapter::from(new CsvOptions(delimiter: ','))->toArray($tmpFile));
    }, $runs);
    $r2 = bench(static function () use ($tmpFile) {
        return count(CsvFileAdapter::from(new CsvOptions())->toArray($tmpFile));
    }, $runs);

    $overhead = ($r2['avg'] - $r1['avg']) * 1000;
    printf(
        "  explicit vs auto-detect overhead: %+.2fms avg (%s)\n",
        $overhead,
        abs($overhead) < 1 ? 'negligible' : sprintf('%.1f%%', abs($overhead) / $r1['avg'] / 10),
    );

    unlink($tmpFile);
    unlink($tmpSemi);
    unlink($tmpTab);

    echo "\n";
}

echo "\033[1;32mBenchmark complete.\033[0m\n\n";
