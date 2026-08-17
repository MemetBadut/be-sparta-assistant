<?php

namespace App\Services\KnowledgeBase;

use App\Models\KnowledgeBaseArticle;

interface KnowledgeBaseRetriever
{
    /** @return list<KnowledgeBaseArticle> */
    public function retrieve(string $category, string $description, int $limit = 3): array;
}
