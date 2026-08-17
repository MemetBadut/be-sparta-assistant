<?php

namespace App\Services\KnowledgeBase;

use App\Enums\ArticleStatus;
use App\Models\KnowledgeBaseArticle;

class KeywordKnowledgeBaseRetriever implements KnowledgeBaseRetriever
{
    private const STOP_WORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'to', 'of', 'in',
        'on', 'at', 'for', 'with', 'my', 'it', 'this', 'that', 'not', 'no', 'cannot', 'can',
        'does', 'do', 'have', 'has', 'i', 'we', 'when', 'while', 'after', 'before', 'be',
    ];

    private const MATCH_THRESHOLD = 1;

    /** @return list<KnowledgeBaseArticle> */
    public function retrieve(string $category, string $description, int $limit = 3): array
    {
        $tokens = $this->tokenize($description);
        if ($tokens === []) {
            return [];
        }

        $articles = KnowledgeBaseArticle::query()
            ->where('status', ArticleStatus::Published->value)
            ->where('category', $category)
            ->get();

        $scored = $articles
            ->mapWithKeys(function (KnowledgeBaseArticle $article) use ($tokens): array {
                return [$article->id => $this->score($article, $tokens)];
            })
            ->filter(fn (int $score): bool => $score >= self::MATCH_THRESHOLD)
            ->toArray();

        if ($scored === []) {
            return [];
        }

        $ordered = KnowledgeBaseArticle::query()
            ->whereIn('id', array_keys($scored))
            ->get()
            ->sortByDesc(fn (KnowledgeBaseArticle $article): int => $scored[$article->id])
            ->values()
            ->take($limit);

        return $ordered->all();
    }

    /** @return list<string> */
    private function tokenize(string $text): array
    {
        $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text) ?? '');
        $words = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $words = array_diff($words, self::STOP_WORDS);

        return array_values(array_unique($words));
    }

    /** @param list<string> $tokens */
    private function score(KnowledgeBaseArticle $article, array $tokens): int
    {
        $titleTokens = $this->tokenize($article->title);
        $symptomTokens = $this->tokenize($article->symptoms);
        $keywordTokens = $this->tokenize($article->keywords ?? '');
        $descriptionTokens = $this->tokenize($article->problem_description);

        $score = 0;
        foreach ($tokens as $token) {
            $score += 3 * (int) in_array($token, $titleTokens, true);
            $score += 3 * (int) in_array($token, $keywordTokens, true);
            $score += 2 * (int) in_array($token, $symptomTokens, true);
            $score += 1 * (int) in_array($token, $descriptionTokens, true);
        }

        return $score;
    }
}
