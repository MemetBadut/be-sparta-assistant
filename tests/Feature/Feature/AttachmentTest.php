<?php

namespace Tests\Feature\Feature;

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
            'issue_title' => 'Screenshot', 'description' => 'See screenshot.', 'category' => 'Windows',
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
            'issue_title' => 'Bad file', 'description' => 'Bad file.', 'category' => 'Windows',
            'priority' => 'Low', 'status' => 'Open',
        ]);

        $this->actingAs($user)->post('/api/tickets/'.$ticket->ticket_number.'/attachments', [
            'file' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ], ['Accept' => 'application/json'])->assertUnprocessable();
    }
}
