<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_cannot_access_management_namespace(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Employee]))
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }
}
