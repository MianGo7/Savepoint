<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;

/**
 * Completes an active or paused task and closes its open work session, if any.
 * Implements FR7.
 */
final class CompleteTask
{
    /**
     * @throws TaskTransitionNotAllowed
     */
    public function handle(Task $task): Task
    {
        DB::transaction(function () use ($task): void {
            $current = Task::query()->lockForUpdate()->findOrFail($task->id);

            if (! in_array($current->status, [TaskStatus::Active, TaskStatus::Paused], true)) {
                throw TaskTransitionNotAllowed::from($current->status, 'completed');
            }

            WorkSession::query()
                ->where('task_id', $current->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $current->status = TaskStatus::Completed;
            $current->completed_at = now();
            $current->save();
        });

        return $task->refresh();
    }
}
