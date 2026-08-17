<?php

namespace Tests\Feature\Feature;

use App\Models\KnowledgeBaseArticle;
use App\Models\TroubleshootingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeTicketTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function employee_can_create_ticket_with_carried_forward_history(): void
    {
        $user = User::factory()->create();
        $article = KnowledgeBaseArticle::factory()->create();
        $result = TroubleshootingResult::create([
            'user_id' => $user->id,
            'category' => 'Wi-Fi / Network',
            'description' => 'wifi no internet',
            'source' => 'verified_knowledge_base',
            'selected_article_id' => $article->id,
            'result_payload' => ['article' => ['steps' => $article->solution_steps]],
        ]);

        $response = $this->actingAs($user)->postJson('/api/tickets', [
            'name' => $user->name,
            'division' => $user->division,
            'issue_title' => 'Wi-Fi connected but no internet',
            'description' => 'The laptop has no internet.',
            'category' => 'Wi-Fi / Network',
            'priority' => 'Medium',
            'troubleshooting_result_id' => $result->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'Open');
        $this->assertStringStartsWith('IT-'.now()->year.'-', $response->json('data.ticket_number'));
        $this->assertDatabaseHas('tickets', [
            'ticket_number' => $response->json('data.ticket_number'),
            'troubleshooting_result_id' => $result->id,
        ]);
    }

    #[Test]
    public function device_categories_require_device_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/tickets', [
            'name' => $user->name, 'division' => $user->division, 'issue_title' => 'Broken laptop',
            'description' => 'Laptop will not start.', 'category' => 'Laptop / PC', 'priority' => 'High',
        ])->assertUnprocessable()->assertJsonValidationErrors(['device_code']);
    }

    #[Test]
    public function employee_sees_only_own_tickets_and_public_number_lookup(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'Own', 'description' => 'Own', 'category' => 'Windows', 'priority' => 'Low', 'status' => 'Open',
        ]);
        $foreign = $other->tickets()->create([
            'ticket_number' => 'IT-2026-10002', 'name' => $other->name, 'division' => $other->division,
            'issue_title' => 'Foreign', 'description' => 'Foreign', 'category' => 'Windows', 'priority' => 'Low', 'status' => 'Open',
        ]);

        $this->actingAs($user)->getJson('/api/tickets')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson('/api/tickets/'.$own->ticket_number)->assertOk();
        $this->actingAs($user)->getJson('/api/tickets/'.$foreign->ticket_number)->assertNotFound();
    }
}
