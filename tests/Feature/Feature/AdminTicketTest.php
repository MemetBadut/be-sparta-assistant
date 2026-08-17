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
            'issue_title' => 'Network', 'description' => 'No internet', 'category' => 'Wi-Fi / Network',
            'priority' => 'High', 'status' => 'Open',
        ]);
    }

    #[Test]
    public function employee_cannot_use_admin_ticket_routes(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/tickets')->assertForbidden();
    }

    #[Test]
    public function technician_can_filter_update_and_view_activity(): void
    {
        $technician = User::factory()->role(Role::Technician)->create();
        $ticket = $this->ticket();

        $this->actingAs($technician)->getJson('/api/admin/tickets?status=Open&priority=High')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($technician)->patchJson('/api/admin/tickets/'.$ticket->ticket_number, [
            'status' => 'In Progress', 'resolution_notes' => 'Investigating.',
        ])->assertOk()->assertJsonPath('data.status', 'In Progress');

        $this->actingAs($technician)->getJson('/api/admin/tickets/'.$ticket->ticket_number.'/activities')
            ->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function invalid_status_transition_is_rejected(): void
    {
        $technician = User::factory()->role(Role::Technician)->create();
        $ticket = $this->ticket();

        $this->actingAs($technician)->patchJson('/api/admin/tickets/'.$ticket->ticket_number, ['status' => 'Resolved'])
            ->assertUnprocessable();
    }
}
