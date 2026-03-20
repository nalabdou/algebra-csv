<?php

declare(strict_types=1);

namespace Nalabdou\Algebra\Csv\Parser;

use Nalabdou\Algebra\Csv\Contract\DelimiterDetectorInterface;

/**
 * Detects the most likely CSV field delimiter from a sample of lines.
 *
 * Counts occurrences of each candidate delimiter in the first N lines and
 * picks the one with the most consistent frequency (lowest variance across
 * lines). Ties favour the order: comma → semicolon → tab → pipe.
 */
final class DelimiterDetector implements DelimiterDetectorInterface
{
    private const CANDIDATES = [',', ';', "\t", '|'];
    private const SAMPLE_LINES = 5;

    /**
     * Detect the delimiter from a raw CSV string sample.
     *
     * @param string $sample First few KB of the CSV content
     *
     * @return string Best-matching delimiter character
     */
    public function detect(string $sample): string
    {
        $lines = \array_filter(
            \array_slice(\explode("\n", $sample), 0, self::SAMPLE_LINES),
            static fn (string $l): bool => '' !== \trim($l)
        );

        if (empty($lines)) {
            return ',';
        }

        $scores = [];

        foreach (self::CANDIDATES as $delimiter) {
            $counts = \array_map(
                static fn (string $line): int => \substr_count($line, $delimiter),
                $lines
            );

            $nonZero = \array_filter($counts);

            if (empty($nonZero)) {
                continue;
            }

            $mean = \array_sum($counts) / \count($counts);
            $variance = \array_sum(
                \array_map(static fn (int $c): float => ($c - $mean) ** 2, $counts)
            ) / \count($counts);

            $scores[$delimiter] = [
                'mean' => $mean,
                'variance' => $variance,
            ];
        }

        if (empty($scores)) {
            return ',';
        }

        // Pick highest mean; break ties by lowest variance; then by candidate order
        \uasort($scores, static function (array $a, array $b): int {
            if ($a['mean'] !== $b['mean']) {
                return $b['mean'] <=> $a['mean'];
            }

            return $a['variance'] <=> $b['variance'];
        });

        return \array_key_first($scores);
    }
}
