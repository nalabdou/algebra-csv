<?php

declare(strict_types=1);

/**
 * Demo: CsvOptions Builder — every option and every with*() method.
 *
 * Shows how to configure parsing from scratch or by chaining the
 * immutable builder methods, and how each option changes output.
 *
 * Run:  php demo/options_builder.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

function show(string $label, array $rows): void
{
    printf("\n  \033[0;33m%s\033[0m\n", $label);
    foreach ($rows as $row) {
        echo '    '.json_encode($row)."\n";
    }
}

$adapter = CsvStringAdapter::from(new CsvOptions());

echo "\n\033[1;36mDefault options\033[0m\n";
$defaults = new CsvOptions();
echo '  delimiter:   '.var_export($defaults->delimiter, true)." (auto-detect)\n";
echo '  enclosure:   '.var_export($defaults->enclosure, true)."\n";
echo '  escape:      '.var_export($defaults->escape, true)."\n";
echo '  hasHeader:   '.var_export($defaults->hasHeader, true)."\n";
echo '  encoding:    '.var_export($defaults->encoding, true)." (assume UTF-8)\n";
echo '  skipEmpty:   '.var_export($defaults->skipEmpty, true)."\n";
echo '  coerceTypes: '.var_export($defaults->coerceTypes, true)."\n";

echo "\n\033[1;36mwithDelimiter()\033[0m\n";

$commaOpts = (new CsvOptions())->withDelimiter(',');
$semicolonOpts = (new CsvOptions())->withDelimiter(';');
$tabOpts = (new CsvOptions())->withDelimiter("\t");
$pipeOpts = (new CsvOptions())->withDelimiter('|');

show('comma  → id,name', $adapter->toArray(CsvString::from("id,name\n1,Alice\n", $commaOpts)));
show('semi   → id;name', $adapter->toArray(CsvString::from("id;name\n1;Alice\n", $semicolonOpts)));
show('tab    → id\\tname', $adapter->toArray(CsvString::from("id\tname\n1\tAlice\n", $tabOpts)));
show('pipe   → id|name', $adapter->toArray(CsvString::from("id|name\n1|Alice\n", $pipeOpts)));

echo "\n\033[1;36mwithTypeCoercion()\033[0m\n";

$raw = CsvString::from("id,amount,rate\n1,100,3.14\n");
$noCoerce = (new CsvOptions())->withDelimiter(',');
$coerce = $noCoerce->withTypeCoercion();

show('coerceTypes: false', $adapter->toArray(CsvString::from("id,amount,rate\n1,100,3.14\n", $noCoerce)));
show('coerceTypes: true', $adapter->toArray(CsvString::from("id,amount,rate\n1,100,3.14\n", $coerce)));

echo "\n\033[1;36mwithSkipEmpty()\033[0m\n";

$content = "id,name\n1,Alice\n,\n2,Bob\n,\n3,Carol\n";
show('skipEmpty: false (default)', $adapter->toArray(CsvString::from($content)));
show('skipEmpty: true', $adapter->toArray(CsvString::from($content, (new CsvOptions())->withSkipEmpty())));

echo "\n\033[1;36mwithoutHeader()\033[0m\n";

$noHeaderContent = "1,Alice,100\n2,Bob,200\n3,Carol,300\n";
show('hasHeader: true (default) — first row treated as header', $adapter->toArray(CsvString::from($noHeaderContent)));
show('hasHeader: false — zero-indexed keys', $adapter->toArray(CsvString::from($noHeaderContent, (new CsvOptions())->withoutHeader())));

echo "\n\033[1;36mwithEncoding()\033[0m\n";

echo "  Encoding set to 'Windows-1252' converts bytes to UTF-8 automatically.\n";
echo "  Example: CsvFileAdapter::from((new CsvOptions())->withEncoding('Windows-1252'))\n";

echo "\n\033[1;36mFluent chain — all options together\033[0m\n";

$opts = (new CsvOptions())
    ->withDelimiter(';')
    ->withEncoding('UTF-8')
    ->withTypeCoercion()
    ->withSkipEmpty();

echo '  '.json_encode([
    'delimiter' => $opts->delimiter,
    'encoding' => $opts->encoding,
    'coerceTypes' => $opts->coerceTypes,
    'skipEmpty' => $opts->skipEmpty,
    'hasHeader' => $opts->hasHeader,
])."\n";

show(
    'Result',
    $adapter->toArray(CsvString::from(
        "id;name;score\n1;Alice;95\n;\n2;Bob;82\n;\n3;Carol;91\n",
        $opts,
    ))
);

echo "\n\033[1;36mImmutability proof\033[0m\n";

$base = new CsvOptions();
$base->withDelimiter(';');
$base->withTypeCoercion();

echo '  base.delimiter after with*() calls: '.var_export($base->delimiter, true)." (unchanged)\n";
echo '  base.coerceTypes after with*() calls: '.var_export($base->coerceTypes, true)." (unchanged)\n";

echo "\n\033[1;36mCsvString::from() — named constructor\033[0m\n";

$a = CsvString::from("id,name\n1,Alice\n");
$b = CsvString::from("id;name\n1;Alice\n", new CsvOptions(delimiter: ';'));

echo '  a.options.delimiter: '.var_export($a->options->delimiter, true)."\n";
echo '  b.options.delimiter: '.var_export($b->options->delimiter, true)."\n";

echo "\n\033[1;32mDone.\033[0m\n\n";
