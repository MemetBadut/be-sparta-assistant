<?php

namespace Tests\Feature\Feature;

use App\Enums\Role;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function employee_can_upload_valid_attachment_without_exposing_path(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $ticket = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'Screenshot', 'description' => 'See screenshot.', 'category' => 'windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);

        $response = $this->actingAs($user)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->image('screen.png'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonMissing(['path']);
        $this->assertDatabaseHas('ticket_attachments', ['ticket_id' => $ticket->id, 'original_name' => 'screen.png']);
    }

    #[Test]
    public function invalid_attachment_is_rejected(): void
    {
        $user = User::factory()->create();
        $ticket = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'Bad file', 'description' => 'Bad file.', 'category' => 'windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);

        $this->actingAs($user)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    #[Test]
    public function owner_can_download_their_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $ticket = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'Screenshot', 'description' => 'See screenshot.', 'category' => 'windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);
        $upload = $this->actingAs($user)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->image('screen.png'),
        ], ['Accept' => 'application/json']);
        $attachmentId = $upload->json('data.id');

        $this->actingAs($user)
            ->get('/api/tickets/'.$ticket->ticket_number.'/attachments/'.$attachmentId)
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=screen.png');
    }

    #[Test]
    public function other_employee_cannot_download_someone_elses_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $ticket = $owner->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $owner->name, 'division' => $owner->division,
            'issue_title' => 'Screenshot', 'description' => 'See screenshot.', 'category' => 'windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);
        $upload = $this->actingAs($owner)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->image('screen.png'),
        ], ['Accept' => 'application/json']);
        $attachmentId = $upload->json('data.id');

        $this->actingAs($stranger)
            ->get('/api/tickets/'.$ticket->ticket_number.'/attachments/'.$attachmentId)
            ->assertNotFound();
    }

    #[Test]
    public function admin_can_download_any_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $ticket = $owner->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $owner->name, 'division' => $owner->division,
            'issue_title' => 'Screenshot', 'description' => 'See screenshot.', 'category' => 'windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);
        $upload = $this->actingAs($owner)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->image('screen.png'),
        ], ['Accept' => 'application/json']);
        $attachmentId = $upload->json('data.id');

        $this->actingAs($admin)
            ->get('/api/tickets/'.$ticket->ticket_number.'/attachments/'.$attachmentId)
            ->assertOk();
    }

    #[Test]
    public function attachment_belonging_to_a_different_ticket_returns_not_found(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $ticketA = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'A', 'description' => 'A.', 'category' => 'windows', 'priority' => 'Low', 'status' => 'Open',
        ]);
        $ticketB = $user->tickets()->create([
            'ticket_number' => 'IT-2026-10002', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'B', 'description' => 'B.', 'category' => 'windows', 'priority' => 'Low', 'status' => 'Open',
        ]);
        $upload = $this->actingAs($user)->post('/api/tickets/'.$ticketA->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->image('screen.png'),
        ], ['Accept' => 'application/json']);
        $attachmentId = $upload->json('data.id');

        $this->actingAs($user)
            ->get('/api/tickets/'.$ticketB->ticket_number.'/attachments/'.$attachmentId)
            ->assertNotFound();
    }
}
