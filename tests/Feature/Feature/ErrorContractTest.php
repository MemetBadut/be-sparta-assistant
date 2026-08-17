<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorContractTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function validation_errors_have_json_shape(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/troubleshooting', ['category' => 'Wi-Fi / Network'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['description']]);
    }

    #[Test]
    public function unauthenticated_requests_reject_with_401_json(): void
    {
        $this->getJson('/api/profile')->assertUnauthorized()->assertJsonStructure(['message']);
    }

    #[Test]
    public function unknown_resource_returns_404_json(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/tickets/IT-9999-99999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
