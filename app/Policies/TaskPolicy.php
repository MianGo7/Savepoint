<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * Restricts every task to its owner. Implements NFR6.
 */
class TaskPolicy
{
    /**
     * Creating is checked against the target project, so that a task cannot be
     * attached to the project of another developer.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
