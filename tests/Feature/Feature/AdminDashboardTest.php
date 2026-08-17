<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function dashboard_requires_admin_role_and_returns_summary(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/api/admin/dashboard')->assertForbidden();

        $response = $this->actingAs(User::factory()->role(Role::Admin)->create())->getJson('/api/admin/dashboard');

        $response->assertOk()->assertJsonStructure([
            'data' => ['summary', 'tickets_by_category', 'recent_tickets', 'recent_articles'],
        ]);
    }
}
