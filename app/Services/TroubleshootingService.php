<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Models\KnowledgeBaseArticle;
use App\Models\TroubleshootingResult;
use App\Models\User;
use App\Services\AI\AiGuidanceGenerator;
use App\Services\KnowledgeBase\KnowledgeBaseRetriever;

class TroubleshootingService
{
    public function __construct(
        private readonly KnowledgeBaseRetriever $retriever,
        private readonly AiGuidanceGenerator $ai,
    ) {}

    public function create(User $user, string $category, string $description): TroubleshootingResult
    {
        $articles = $this->retriever->retrieve($category, $description);
        $article = $articles[0] ?? null;
        $payload = [
            'category' => $category,
            'issue_summary' => $description,
            'source' => $article ? 'verified_knowledge_base' : 'general_guidance',
            'article' => $article ? [
                'id' => $article->id,
                'title' => $article->title,
                'steps' => $article->solution_steps,
                'expected_result' => $article->expected_result,
            ] : null,
            'general_guidance' => null,
            'recommend_ticket' => ! $article,
        ];

        if (! $article) {
            $guidance = $this->ai->generate($category, $description);
            $payload['general_guidance'] = $guidance['guidance'];
            $payload['recommend_ticket'] = $guidance['recommend_ticket'];
        }

        return TroubleshootingResult::create([
            'user_id' => $user->id,
            'category' => $category,
            'description' => $description,
            'source' => $payload['source'],
            'selected_article_id' => $article?->id,
            'result_payload' => $payload,
        ]);
    }
}
