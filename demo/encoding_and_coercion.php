<?php

declare(strict_types=1);

/**
 * Demo: Encoding conversion and type coercion.
 *
 * Shows how algebra-csv transparently converts any source encoding
 * to UTF-8, and how coerceTypes casts numeric strings to int/float.
 *
 * Run:  php demo/encoding_and_coercion.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\CsvStringAdapter;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;
use Nalabdou\Algebra\Csv\ValueObject\CsvString;

$adapter = CsvStringAdapter::from(new CsvOptions());

echo "\n\033[1;36mType coercion — numeric strings → int / float\033[0m\n";

$content = "id,amount,rate,code,note\n1,100,3.14,007,\n2,-50,0.5,042,hello\n";

$noCoerce = $adapter->toArray(CsvString::from($content, new CsvOptions()));
$coerced = $adapter->toArray(CsvString::from($content, new CsvOptions(coerceTypes: true)));

echo "\n  Without coercion:\n";
foreach ($noCoerce as $row) {
    printf(
        "    id=%-2s  amount=%-5s(%s)  rate=%-5s(%s)  code=%-4s(%s)  note=%s\n",
        $row['id'],
        $row['amount'],
        gettype($row['amount']),
        $row['rate'],
        gettype($row['rate']),
        $row['code'],
        gettype($row['code']),
        var_export($row['note'], true),
    );
}

echo "\n  With coercion (coerceTypes: true):\n";
foreach ($coerced as $row) {
    printf(
        "    id=%-2s  amount=%-5s(%s)  rate=%-5s(%s)  code=%-4s(%s)  note=%s\n",
        $row['id'],
        $row['amount'],
        gettype($row['amount']),
        $row['rate'],
        gettype($row['rate']),
        $row['code'],
        gettype($row['code']),
        var_export($row['note'], true),
    );
}

echo "\n  Rules:\n";
echo "    '1'      → int(1)      (pure integer string)\n";
echo "    '-50'    → int(-50)    (negative integer)\n";
echo "    '3.14'   → float(3.14) (is_numeric float)\n";
echo "    '007'    → string      (leading zero preserved)\n";
echo "    ''       → string('')  (empty cell never coerced)\n";
echo "    'hello'  → string      (non-numeric never coerced)\n";

echo "\n\033[1;36mEncoding conversion — Windows-1252 → UTF-8\033[0m\n";

$utf8Bytes = mb_convert_encoding(
    "id,name,city\n1,François,Montréal\n2,Héloïse,Genève\n",
    'Windows-1252',
    'UTF-8',
);

echo "\n  Source bytes are Windows-1252 (non-printable in terminal)\n";
echo "  Parsing without encoding hint:\n";
$noEnc = $adapter->toArray(CsvString::from($utf8Bytes, new CsvOptions()));
foreach ($noEnc as $row) {
    printf("    id=%s  name=%s  city=%s\n", $row['id'], $row['name'], $row['city']);
}

echo "\n  Parsing with encoding: 'Windows-1252':\n";
$withEnc = $adapter->toArray(CsvString::from($utf8Bytes, new CsvOptions(encoding: 'Windows-1252')));
foreach ($withEnc as $row) {
    printf("    id=%s  name=%s  city=%s\n", $row['id'], $row['name'], $row['city']);
}
echo "  Names are now correct UTF-8.\n";

echo "\n\033[1;36mCombined: Windows-1252 + coercion\033[0m\n";

$mixed = mb_convert_encoding(
    "id,product,price\n1,Clé USB,19.99\n2,Écran,399.00\n",
    'Windows-1252',
    'UTF-8',
);

$opts = (new CsvOptions())->withEncoding('Windows-1252')->withTypeCoercion();
$result = $adapter->toArray(CsvString::from($mixed, $opts));

foreach ($result as $row) {
    printf(
        "    id=%-2s  product=%-12s  price=%s(%s)\n",
        $row['id'],
        $row['product'],
        $row['price'],
        gettype($row['price']),
    );
}

echo "\n\033[1;32mDone.\033[0m\n\n";
