<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\ActiveTaskCannotBeDeleted;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a task together with its work sessions and resume points. Implements
 * FR2.
 */
final class DeleteTask
{
    /**
     * @throws ActiveTaskCannotBeDeleted
     */
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $current = Task::query()->lockForUpdate()->findOrFail($task->id);

            if ($current->status === TaskStatus::Active) {
                throw ActiveTaskCannotBeDeleted::make();
            }

            $current->delete();
        });
    }
}
