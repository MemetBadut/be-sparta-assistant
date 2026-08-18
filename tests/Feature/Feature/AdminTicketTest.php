<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminTicketTest extends TestCase
{
    use RefreshDatabase;

    private function ticket(): Ticket
    {
        $user = User::factory()->create();
        return $user->tickets()->create([
            'ticket_number' => 'IT-2026-30001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'Network', 'description' => 'No internet', 'category' => 'wifi_network',
            'priority' => 'High', 'status' => 'Open',
        ]);
    }

    #[Test]
    public function employee_cannot_use_admin_ticket_routes(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/tickets')->assertForbidden();
    }

    #[Test]
    public function admin_can_filter_update_and_view_activity(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        $ticket = $this->ticket();

        $this->actingAs($admin)->getJson('/api/admin/tickets?status=Open&priority=High')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($admin)->patchJson('/api/admin/tickets/'.$ticket->ticket_number, [
            'status' => 'In Progress', 'resolution_notes' => 'Investigating.',
        ])->assertOk()->assertJsonPath('data.status', 'In Progress');

        $this->actingAs($admin)->getJson('/api/admin/tickets/'.$ticket->ticket_number.'/activities')
            ->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function invalid_status_transition_is_rejected(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();
        $ticket = $this->ticket();

        $this->actingAs($admin)->patchJson('/api/admin/tickets/'.$ticket->ticket_number, ['status' => 'Resolved'])
            ->assertUnprocessable()
            ->assertJson(['error_code' => 'INVALID_STATUS_TRANSITION']);
    }
}
