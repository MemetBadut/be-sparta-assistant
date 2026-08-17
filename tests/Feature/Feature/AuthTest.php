<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_register_and_is_always_employee(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'employee_id' => 'EMP-9000',
            'division' => 'Operations',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertCreated()->assertJsonPath('data.role', Role::Employee->value);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['employee_id' => 'EMP-9000', 'role' => Role::Employee->value]);
    }

    public function test_duplicate_email_and_employee_id_are_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'employee_id' => 'EMP-0002']);

        $this->postJson('/api/auth/register', [
            'name' => 'Duplicate', 'email' => 'taken@example.com', 'employee_id' => 'EMP-0002',
            'division' => 'Operations', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email', 'employee_id']);
    }

    public function test_user_can_login_with_email_or_employee_id(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com', 'employee_id' => 'EMP-7000', 'password' => 'password123']);

        $this->postJson('/api/auth/login', ['login' => $user->email, 'password' => 'password123'])->assertOk();
        $this->assertAuthenticated();
        $this->postJson('/api/auth/login', ['login' => $user->employee_id, 'password' => 'password123'])->assertOk();
    }

    public function test_failed_login_is_rejected(): void
    {
        User::factory()->create(['email' => 'login@example.com', 'password' => 'password123']);

        $this->postJson('/api/auth/login', ['login' => 'login@example.com', 'password' => 'wrong'])->assertUnprocessable();
    }

    public function test_profile_requires_authentication_and_returns_safe_fields(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/profile')->assertOk()
            ->assertJsonPath('employee_id', $user->employee_id)
            ->assertJsonMissing(['password']);
    }

    public function test_categories_are_fixed(): void
    {
        $this->getJson('/api/categories')->assertOk()->assertJsonCount(5, 'data');
    }
}
