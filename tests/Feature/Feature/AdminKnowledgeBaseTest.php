<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\KnowledgeBaseArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminKnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private function article(): KnowledgeBaseArticle
    {
        return KnowledgeBaseArticle::factory()->create();
    }

    private function data(): array
    {
        return [
            'title' => 'Printer queue stuck', 'category' => 'printer', 'symptoms' => 'Jobs remain queued.',
            'keywords' => 'printer, queue', 'problem_description' => 'The queue does not clear.',
            'expected_result' => 'The queue prints normally.', 'status' => 'Published',
        ];
    }

    #[Test]
    public function employee_cannot_manage_articles(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/articles')->assertForbidden();
    }

    #[Test]
    public function admin_can_create_filter_update_and_confirm_delete(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        $response = $this->actingAs($admin)->postJson('/api/admin/articles', $this->data());
        $response->assertCreated()->assertJsonPath('data.status', 'Published');
        $id = $response->json('data.id');

        $this->actingAs($admin)->getJson('/api/admin/articles?category=printer&status=Published')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($admin)->patchJson('/api/admin/articles/'.$id, ['status' => 'Draft'])
            ->assertOk()->assertJsonPath('data.status', 'Draft');
        $this->actingAs($admin)->deleteJson('/api/admin/articles/'.$id)
            ->assertUnprocessable()
            ->assertJson(['error_code' => 'CONFIRMATION_REQUIRED']);
        $this->actingAs($admin)->deleteJson('/api/admin/articles/'.$id.'?confirm=1')->assertOk();
        $this->assertDatabaseMissing('knowledge_base_articles', ['id' => $id]);
    }
}
