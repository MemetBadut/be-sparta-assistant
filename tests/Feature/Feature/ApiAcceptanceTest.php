<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function full_employee_to_admin_flow_works_end_to_end(): void
    {
        Storage::fake('local');
        KnowledgeBaseArticleSeed::run();

        // Register employee.
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Acceptance Employee', 'email' => 'acceptance@example.com', 'employee_id' => 'EMP-8001',
            'division' => 'Operations', 'password' => 'password123', 'password_confirmation' => 'password123',
        ]);
        $register->assertCreated();

        // Profile.
        $this->getJson('/api/profile')->assertOk()->assertJsonPath('data.employee_id', 'EMP-8001');

        // Troubleshooting from a published article.
        $result = $this->postJson('/api/troubleshooting', [
            'category' => 'wifi_network', 'description' => 'Wi-Fi is connected but there is no internet',
        ]);
        $result->assertCreated()->assertJsonPath('data.source', 'verified_knowledge_base');
        $resultId = $result->json('data.id');

        // Feedback.
        $this->postJson("/api/troubleshooting/{$resultId}/feedback", ['helpful' => false])->assertOk();

        // Ticket with carried-forward history.
        $ticket = $this->postJson('/api/tickets', [
            'name' => 'Acceptance Employee', 'division' => 'Operations',
            'issue_title' => 'Wi-Fi connected but no internet',
            'description' => 'The laptop associates but cannot browse.',
            'category' => 'wifi_network', 'priority' => 'Medium',
            'troubleshooting_result_id' => $resultId,
        ]);
        $ticket->assertCreated();
        $ticketNumber = $ticket->json('data.ticket_number');

        // Upload attachment.
        $this->post("/api/tickets/{$ticketNumber}/attachments", [
            'file' => UploadedFile::fake()->image('screenshot.png'),
        ], ['Accept' => 'application/json'])->assertCreated();

        // Admin logs in and updates ticket.
        $admin = User::factory()->role(Role::Admin)->create(['password' => 'password123']);
        $this->postJson('/api/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards(); // test app reuses one guard across requests; real requests get a fresh one
        $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'password123'])->assertOk();
        $this->patchJson("/api/admin/tickets/{$ticketNumber}", ['status' => 'In Progress'])
            ->assertOk()->assertJsonPath('data.status', 'In Progress');

        // Employee logs back in and still reads own ticket.
        $this->postJson('/api/auth/logout')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/login', ['email' => 'acceptance@example.com', 'password' => 'password123'])->assertOk();
        $this->getJson("/api/tickets/{$ticketNumber}")->assertOk();

        // Draft articles never surface.
        $draftResult = $this->postJson('/api/troubleshooting', [
            'category' => 'printer', 'description' => 'Printer prints blank pages only',
        ]);
        $draftResult->assertCreated()->assertJsonMissing(['source' => 'verified_knowledge_base']);
    }
}
