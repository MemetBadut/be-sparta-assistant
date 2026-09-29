<?php

namespace App\Console\Commands;

use App\Models\TroubleshootingResult;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurgeExpiredTroubleshootingResults extends Command
{
    protected $signature = 'troubleshooting:purge-expired';

    protected $description = 'Delete unlinked troubleshooting results older than 24 hours';

    public function handle(): int
    {
        $cutoff = Carbon::now()->subHours(24);
        $deleted = TroubleshootingResult::query()
            ->where('created_at', '<', $cutoff)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('tickets')
                    ->whereColumn('tickets.troubleshooting_result_id', 'troubleshooting_results.id');
            })
            ->delete();

        $this->info("Deleted {$deleted} expired unlinked troubleshooting result(s).");

        return self::SUCCESS;
    }
}
