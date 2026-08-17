<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $user->canManage() || $ticket->user_id === $user->id;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->canManage();
    }

    public function viewActivities(User $user, Ticket $ticket): bool
    {
        return $user->canManage();
    }
}
