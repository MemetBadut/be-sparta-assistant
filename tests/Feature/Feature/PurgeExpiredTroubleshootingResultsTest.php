<?php

namespace Tests\Feature\Feature;

use App\Models\TroubleshootingResult;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PurgeExpiredTroubleshootingResultsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function command_deletes_only_unlinked_results_older_than_24_hours(): void
    {
        Carbon::setTestNow('2026-09-29 12:00:00');
        $user = User::factory()->create();

        $expired = TroubleshootingResult::create([
            'user_id' => $user->id, 'category' => 'wifi_network', 'description' => 'no internet',
            'source' => 'general_guidance', 'result_payload' => [],
        ]);
        $expired->forceFill(['created_at' => now()->subHours(25)])->saveQuietly();
        $recent = TroubleshootingResult::create([
            'user_id' => $user->id, 'category' => 'wifi_network', 'description' => 'no internet',
            'source' => 'general_guidance', 'result_payload' => [],
        ]);
        $recent->forceFill(['created_at' => now()->subHours(23)])->saveQuietly();
        $linked = TroubleshootingResult::create([
            'user_id' => $user->id, 'category' => 'wifi_network', 'description' => 'no internet',
            'source' => 'general_guidance', 'result_payload' => [],
        ]);
        $linked->forceFill(['created_at' => now()->subHours(25)])->saveQuietly();
        $user->tickets()->create([
            'ticket_number' => 'IT-2026-30001', 'name' => $user->name, 'division' => $user->division,
            'issue_title' => 'x', 'description' => 'x', 'category' => 'wifi_network', 'priority' => 'Low',
            'status' => 'Open', 'troubleshooting_result_id' => $linked->id,
        ]);

        $this->artisan('troubleshooting:purge-expired')
            ->expectsOutput('Deleted 1 expired unlinked troubleshooting result(s).')
            ->assertSuccessful();

        $this->assertDatabaseMissing('troubleshooting_results', ['id' => $expired->id]);
        $this->assertDatabaseHas('troubleshooting_results', ['id' => $recent->id]);
        $this->assertDatabaseHas('troubleshooting_results', ['id' => $linked->id]);
        Carbon::setTestNow();
    }
}
