<?php

namespace Tests\Unit;

use App\Enums\ArticleStatus;
use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBase\KeywordKnowledgeBaseRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KeywordKnowledgeBaseRetrieverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function finds_published_articles_in_category(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Laptop connected to Wi-Fi but no internet',
            'keywords' => 'wifi, internet, connected, network',
        ]);

        $retriever = new KeywordKnowledgeBaseRetriever();

        $results = $retriever->retrieve('wifi_network', 'wifi connected but no internet');

        $this->assertCount(1, $results);
    }

    #[Test]
    public function never_returns_draft_articles(): void
    {
        KnowledgeBaseArticle::factory()->draft()->create([
            'title' => 'Laptop connected to Wi-Fi but no internet',
        ]);

        $retriever = new KeywordKnowledgeBaseRetriever();

        $this->assertSame([], $retriever->retrieve('wifi_network', 'wifi no internet'));
    }

    #[Test]
    public function filters_by_category(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Wi-Fi printer offline',
            'category' => 'printer',
        ]);

        $retriever = new KeywordKnowledgeBaseRetriever();

        $this->assertSame([], $retriever->retrieve('wifi_network', 'wifi printer offline'));
    }

    #[Test]
    public function returns_empty_when_no_keyword_overlap(): void
    {
        KnowledgeBaseArticle::factory()->create([
            'title' => 'Reboot the domain controller',
        ]);

        $retriever = new KeywordKnowledgeBaseRetriever();

        $this->assertSame([], $retriever->retrieve('wifi_network', 'cactus blooming in the desert'));
    }

    #[Test]
    public function ranks_relevant_article_first_and_respects_limit(): void
    {
        $relevant = KnowledgeBaseArticle::factory()->create(['title' => 'Wi-Fi connected but no internet']);
        KnowledgeBaseArticle::factory()->create(['title' => 'Slow network drive mapping']);
        KnowledgeBaseArticle::factory()->create(['title' => 'Weak Wi-Fi signal in meeting rooms']);
        KnowledgeBaseArticle::factory()->create(['title' => 'Wi-Fi guest network access']);

        $retriever = new KeywordKnowledgeBaseRetriever();

        $results = $retriever->retrieve('wifi_network', 'wifi connected but no internet', 3);

        $this->assertSame($relevant->id, $results[0]->id);
        $this->assertLessThanOrEqual(3, count($results));
    }
}
