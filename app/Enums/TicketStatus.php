<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'Open';
    case InProgress = 'In Progress';
    case Resolved = 'Resolved';
    case Closed = 'Closed';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Open => $next === self::InProgress,
            self::InProgress => $next === self::Resolved,
            self::Resolved => $next === self::Closed,
            self::Closed => false,
        };
    }
}
