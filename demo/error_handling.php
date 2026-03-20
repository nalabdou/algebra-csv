<?php

declare(strict_types=1);

/**
 * Demo: Error handling — every typed exception algebra-csv can throw.
 *
 * All exceptions extend CsvException which extends RuntimeException,
 * so you can catch them individually or as a group.
 *
 * Run:  php demo/error_handling.php
 */

require_once __DIR__.'/../vendor/autoload.php';

use Nalabdou\Algebra\Csv\CsvFileAdapter;
use Nalabdou\Algebra\Csv\Exception\CsvException;
use Nalabdou\Algebra\Csv\Exception\FileNotFoundException;
use Nalabdou\Algebra\Csv\Exception\InvalidResourceException;
use Nalabdou\Algebra\Csv\Parser\CsvParser;
use Nalabdou\Algebra\Csv\ValueObject\CsvOptions;

function attempt(string $label, callable $fn): void
{
    echo "\n  \033[0;33m{$label}\033[0m\n";
    try {
        $fn();
        echo "    (no exception thrown)\n";
    } catch (CsvException $e) {
        printf("    \033[0;31m%s\033[0m: %s\n", (new ReflectionClass($e))->getShortName(), $e->getMessage());
    } catch (Throwable $e) {
        printf("    \033[0;31m%s\033[0m: %s\n", (new ReflectionClass($e))->getShortName(), $e->getMessage());
    }
}

echo "\n\033[1;36mFileNotFoundException\033[0m\n";

attempt('toArray() with a path that does not exist', static function () {
    CsvFileAdapter::from(new CsvOptions())->toArray('/tmp/no_such_file_ever.csv');
});

attempt('fromPath() named constructor', static function () {
    throw FileNotFoundException::fromPath('/data/missing.csv');
});

echo "\n\033[1;36mInvalidResourceException\033[0m\n";

attempt('CsvParser::parse() with a string instead of a resource', static function () {
    (new CsvParser())->parse('not-a-resource', new CsvOptions());
});

attempt('CsvParser::parse() with null', static function () {
    (new CsvParser())->parse(null, new CsvOptions());
});

attempt('CsvParser::parse() with an integer', static function () {
    (new CsvParser())->parse(42, new CsvOptions());
});

echo "\n\033[1;36mCatching as base CsvException\033[0m\n";

attempt('All exceptions extend CsvException', static function () {
    try {
        CsvFileAdapter::from(new CsvOptions())->toArray('/tmp/nope.csv');
    } catch (CsvException $e) {
        echo '    Caught as CsvException: '.(new ReflectionClass($e))->getShortName()."\n";

        return;
    }
});

echo "\n\033[1;36mDirect exception constructors for custom use\033[0m\n";

$exceptions = [
    FileNotFoundException::fromPath('/x.csv'),
    Nalabdou\Algebra\Csv\Exception\FileNotReadableException::fromPath('/x.csv'),
    InvalidResourceException::create(),
    Nalabdou\Algebra\Csv\Exception\ColumnCountMismatchException::create(3, 2, 7),
    Nalabdou\Algebra\Csv\Exception\EmptySourceException::create(),
    Nalabdou\Algebra\Csv\Exception\HeaderMissingException::create(),
];

foreach ($exceptions as $e) {
    printf(
        "  %-38s  \"%s\"\n",
        (new ReflectionClass($e))->getShortName(),
        $e->getMessage(),
    );
}

echo "\n\033[1;36msupports() prevents exceptions at the adapter layer\033[0m\n";

$fileAdapter = CsvFileAdapter::from(new CsvOptions());
$inputs = [
    '/tmp/valid_path_if_exists.csv',
    '/tmp/no_such_file.csv',
    new SplFileInfo('/tmp/no_such_file.csv'),
    'id,name',
    [],
    null,
    42,
];

foreach ($inputs as $input) {
    $label = is_object($input)
        ? $input::class
        : (is_array($input) ? '[]' : var_export($input, true));
    printf(
        "  supports(%-40s)  %s\n",
        $label,
        $fileAdapter->supports($input) ? "\033[0;32mtrue\033[0m" : "\033[0;31mfalse\033[0m",
    );
}

echo "\n\033[1;32mDone.\033[0m\n\n";
