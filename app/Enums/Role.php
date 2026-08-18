<?php

namespace App\Enums;

enum Role: string
{
    case Employee = 'employee';
    case Admin = 'admin';

    public function canManage(): bool
    {
        return $this === self::Admin;
    }
}
