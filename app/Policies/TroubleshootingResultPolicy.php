<?php

namespace App\Policies;

use App\Models\TroubleshootingResult;
use App\Models\User;

class TroubleshootingResultPolicy
{
    public function update(User $user, TroubleshootingResult $result): bool
    {
        return $user->id === $result->user_id;
    }
}
