<?php

namespace App\Services\Tickets;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketNumberGenerator
{
    public function next(): string
    {
        return DB::transaction(function (): string {
            $year = (int) now()->format('Y');
            $prefix = 'IT-'.$year.'-';

            $last = Ticket::where('ticket_number', 'like', $prefix.'%')
                ->orderByDesc('ticket_number')
                ->lockForUpdate()
                ->value('ticket_number');

            $sequence = $last === null ? 1 : ((int) substr($last, strlen($prefix)) + 1);

            return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
