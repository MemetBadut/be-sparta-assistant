<?php

namespace App\Services\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TicketActivityService
{
    public function update(Ticket $ticket, User $actor, array $changes): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor, $changes): Ticket {
            $ticket->refresh();
            $activities = [];

            if (array_key_exists('status', $changes)) {
                $next = TicketStatus::from($changes['status']);
                if ($ticket->status !== $next && ! $ticket->status->canTransitionTo($next)) {
                    throw new InvalidArgumentException('Invalid ticket status transition.');
                }
                if ($ticket->status !== $next) {
                    $activities[] = ['type' => 'status_changed', 'old_status' => $ticket->status->value, 'new_status' => $next->value];
                }
            }

            if (array_key_exists('assigned_technician', $changes) && $ticket->assigned_technician !== $changes['assigned_technician']) {
                $activities[] = ['type' => 'assigned', 'note' => $changes['assigned_technician'] === null ? 'Assignment cleared.' : 'Assigned to '.$changes['assigned_technician'].'.'];
            }

            if (array_key_exists('resolution_notes', $changes) && $ticket->resolution_notes !== $changes['resolution_notes']) {
                $activities[] = ['type' => 'resolution_note_added', 'note' => $changes['resolution_notes']];
            }

            $ticket->fill($changes)->save();
            foreach ($activities as $activity) {
                $ticket->activities()->create([...$activity, 'user_id' => $actor->id]);
            }

            return $ticket->fresh('attachments');
        });
    }
}
