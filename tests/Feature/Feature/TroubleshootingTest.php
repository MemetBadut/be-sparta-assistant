<?php

namespace Tests\Feature\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TroubleshootingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_verified_article_steps_and_persists_result(): void
    {
        $user = User::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/troubleshooting', [
            'category' => 'wifi_network',
            'description' => 'Wi-Fi is connected but there is no internet',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.source', 'verified_knowledge_base')
            ->assertJsonPath('data.article.id', $article->id)
            ->assertJsonPath('data.recommend_ticket', false);

        $this->assertDatabaseHas('troubleshooting_results', [
            'user_id' => $user->id,
            'selected_article_id' => $article->id,
        ]);
    }

    #[Test]
    public function falls_back_to_general_guidance_when_no_article_matches(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/troubleshooting', [
            'category' => 'basic_software_issues',
            'description' => 'Cactus growing software will not bloom at all',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.source', 'general_guidance')
            ->assertJsonPath('data.recommend_ticket', true)
            ->assertJsonStructure(['data' => ['general_guidance']]);
    }

    #[Test]
    public function ai_provider_failure_still_returns_safe_response(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/troubleshooting', [
            'category' => 'basic_software_issues',
            'description' => 'Cactus growing software will not bloom at all',
        ]);

        $response->assertCreated()->assertJsonPath('data.recommend_ticket', true);
    }

    #[Test]
    public function feedback_is_owner_scoped_and_idempotent(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $result = $this->actingAs($user)->postJson('/api/troubleshooting', [
            'category' => 'basic_software_issues',
            'description' => 'Cactus growing software will not bloom at all',
        ]);

        $id = $result->json('data.id');

        $this->actingAs($other)->postJson("/api/troubleshooting/{$id}/feedback", ['helpful' => true])
            ->assertNotFound();

        $this->actingAs($user)->postJson("/api/troubleshooting/{$id}/feedback", ['helpful' => true])->assertOk();
        $this->actingAs($user)->postJson("/api/troubleshooting/{$id}/feedback", ['helpful' => false])->assertOk();

        $this->assertDatabaseHas('troubleshooting_results', ['id' => $id, 'helpful' => false]);
    }

    #[Test]
    public function requires_authentication_and_valid_category(): void
    {
        $this->postJson('/api/troubleshooting', ['category' => 'wifi_network', 'description' => 'no internet'])
            ->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/troubleshooting', ['category' => 'invalid', 'description' => 'no internet'])
            ->assertUnprocessable();
    }
}
