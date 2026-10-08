<?php

namespace App\Services\Troubleshooting;

use App\Enums\Category;

class CategoryClassifier
{
    /** @var array<string, list<string>> */
    private const KEYWORDS = [
        'wifi_network' => ['wifi', 'wi-fi', 'internet', 'network', 'jaringan', 'connection', 'koneksi', 'hotspot', 'router'],
        'windows' => ['windows', 'system', 'sistem', 'update', 'boot', 'login', 'blue'],
        'laptop_pc' => ['laptop', 'pc', 'komputer', 'computer', 'hardware', 'keyboard', 'screen', 'layar', 'battery', 'baterai'],
        'printer' => ['printer', 'print', 'cetak', 'ink', 'tinta', 'paper', 'kertas', 'scanner'],
        'basic_software_issues' => ['app', 'aplikasi', 'software', 'program', 'teams', 'office', 'browser', 'crash', 'install'],
    ];

    /** @return array{category:?string,candidates:list<string>} */
    public function classify(string $description): array
    {
        $tokens = $this->tokens($description);
        $scores = array_fill_keys(Category::values(), 0);

        foreach (self::KEYWORDS as $category => $keywords) {
            foreach ($keywords as $keyword) {
                $scores[$category] += (int) \in_array($keyword, $tokens, true);
            }
        }

        $highest = \max($scores);
        if ($highest === 0) {
            return ['category' => null, 'candidates' => array_keys(self::KEYWORDS)];
        }

        $candidates = array_keys(array_filter($scores, static fn (int $score): bool => $score === $highest));
        return \count($candidates) === 1
            ? ['category' => $candidates[0], 'candidates' => []]
            : ['category' => null, 'candidates' => $candidates];
    }

    /** @return list<string> */
    private function tokens(string $text): array
    {
        $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text) ?? '');
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_map(
            static fn (string $word): string => str_replace('-', '', $word),
            $words,
        )));
    }
}
