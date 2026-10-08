<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IsoTemplateDownloadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function authenticated_employee_can_download_the_iso_repair_template(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('templates/iso-repair-template.xlsx', 'template');

        $this->actingAs(User::factory()->create())
            ->get('/api/tickets/iso-template')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=iso-repair-template.xlsx');
    }

    #[Test]
    public function unauthenticated_users_cannot_download_the_iso_repair_template(): void
    {
        $this->getJson('/api/tickets/iso-template')->assertUnauthorized();
    }
}
