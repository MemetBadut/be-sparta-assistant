<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'employee';
    case Technician = 'technician';
    case Admin = 'admin';

    public function canManage(): bool
    {
        return $this !== self::Employee;
    }
}
