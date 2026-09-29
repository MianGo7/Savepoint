<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkSession;

/**
 * Restricts every work session to its owner. Implements NFR6.
 */
class WorkSessionPolicy
{
    public function view(User $user, WorkSession $workSession): bool
    {
        return $user->id === $workSession->user_id;
    }
}
