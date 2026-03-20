<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Contract;

/**
 * Contract for CSV delimiter detection strategies.
 *
 * Implementations receive a raw string sample of the CSV content and
 * must return the single-character delimiter that best fits the data.
 *
 * ```php
 * $detector = new DelimiterDetector();
 * $delimiter = $detector->detect("id,name,amount\n1,Alice,100\n");
 * // ","
 * ```
 */
interface DelimiterDetectorInterface
{
    /**
     * Detect the most likely field delimiter from a CSV string sample.
     *
     * @param string $sample First few kilobytes of the CSV content
     *
     * @return string Single-character delimiter (e.g. ',', ';', "\t", '|')
     */
    public function detect(string $sample): string;
}
