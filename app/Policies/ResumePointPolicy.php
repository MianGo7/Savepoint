<?php

namespace App\Policies;

use App\Models\ResumePoint;
use App\Models\User;

/**
 * Restricts every resume point to the owner of its task. Implements NFR6.
 */
class ResumePointPolicy
{
    public function view(User $user, ResumePoint $resumePoint): bool
    {
        return $user->id === $resumePoint->task->user_id;
    }
}
