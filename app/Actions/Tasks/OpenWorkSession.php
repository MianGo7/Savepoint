<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\ProjectArchived;
use App\Exceptions\SessionAlreadyOpen;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Opens a work session for a task and marks it active, shared by the start and
 * the resume transition. Implements FR3 and NFR3.
 */
final class OpenWorkSession
{
    /**
     * @param  TaskStatus  $expected  The status the task must have for the transition.
     *
     * @throws TaskTransitionNotAllowed
     * @throws ProjectArchived
     * @throws SessionAlreadyOpen
     */
    public function handle(Task $task, TaskStatus $expected, string $transition): WorkSession
    {
        try {
            $session = DB::transaction(function () use ($task, $expected, $transition): WorkSession {
                // The task is read again inside the transaction, so that a second
                // submission of the same request sees the status the first one set.
                $current = Task::query()->lockForUpdate()->findOrFail($task->id);

                if ($current->status !== $expected) {
                    throw TaskTransitionNotAllowed::from($current->status, $transition);
                }

                // ADR-0007: archiving leaves tasks alone, but no new work starts in it.
                if ($current->project->archived_at !== null) {
                    throw ProjectArchived::make();
                }

                if (WorkSession::query()->where('user_id', $current->user_id)->whereNull('ended_at')->exists()) {
                    throw SessionAlreadyOpen::forUser();
                }

                $current->status = TaskStatus::Active;
                $current->save();

                return $current->workSessions()->forceCreate([
                    'task_id' => $current->id,
                    'user_id' => $current->user_id,
                    'started_at' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            // NFR3: a concurrent request passed the check above, the partial
            // unique index of the work sessions table is the last line of defence.
            throw SessionAlreadyOpen::forUser();
        }

        $task->refresh();

        return $session;
    }
}
