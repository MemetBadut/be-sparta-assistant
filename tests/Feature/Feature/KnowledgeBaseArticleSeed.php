<?php

namespace Tests\Feature\Feature;

use App\Models\KnowledgeBaseArticle;

final class KnowledgeBaseArticleSeed
{
    public static function run(): void
    {
        KnowledgeBaseArticle::factory()->create();
        KnowledgeBaseArticle::factory()->draft()->create([
            'title' => 'Unpublished printer draft',
            'category' => 'printer',
        ]);
    }
}
