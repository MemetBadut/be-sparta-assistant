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
            'title' => 'Printer queue stuck', 'category' => 'Printer', 'symptoms' => 'Jobs remain queued.',
            'keywords' => 'printer, queue', 'problem_description' => 'The queue does not clear.',
            'solution_steps' => ['Open the printer queue.', 'Cancel the stuck job.'],
            'expected_result' => 'The queue prints normally.', 'status' => 'Published',
        ];
    }

    #[Test]
    public function employee_cannot_manage_articles(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/articles')->assertForbidden();
    }

    #[Test]
    public function technician_can_create_filter_update_and_confirm_delete(): void
    {
        $technician = User::factory()->role(Role::Technician)->create();
        $response = $this->actingAs($technician)->postJson('/api/admin/articles', $this->data());
        $response->assertCreated()->assertJsonPath('data.status', 'Published');
        $id = $response->json('data.id');

        $this->actingAs($technician)->getJson('/api/admin/articles?category=Printer&status=Published')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($technician)->patchJson('/api/admin/articles/'.$id, ['status' => 'Draft'])
            ->assertOk()->assertJsonPath('data.status', 'Draft');
        $this->actingAs($technician)->deleteJson('/api/admin/articles/'.$id)->assertUnprocessable();
        $this->actingAs($technician)->deleteJson('/api/admin/articles/'.$id.'?confirm=1')->assertOk();
        $this->assertDatabaseMissing('knowledge_base_articles', ['id' => $id]);
    }
}
