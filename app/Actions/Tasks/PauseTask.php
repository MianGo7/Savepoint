<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Exceptions\ResumePointRequired;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Support\Facades\DB;

/**
 * Pauses the active task, storing the resume point and closing the work
 * session together. Implements FR4 and NFR4.
 */
final class PauseTask
{
    /**
     * @throws ResumePointRequired
     * @throws TaskTransitionNotAllowed
     */
    public function handle(Task $task, string $whereStopped, string $nextStep): ResumePoint
    {
        $whereStopped = trim($whereStopped);
        $nextStep = trim($nextStep);

        if ($whereStopped === '' || $nextStep === '') {
            throw ResumePointRequired::make();
        }

        $resumePoint = DB::transaction(function () use ($task, $whereStopped, $nextStep): ResumePoint {
            $current = Task::query()->lockForUpdate()->findOrFail($task->id);

            if ($current->status !== TaskStatus::Active) {
                throw TaskTransitionNotAllowed::from($current->status, 'paused');
            }

            $session = WorkSession::query()
                ->where('task_id', $current->id)
                ->whereNull('ended_at')
                ->first() ?? throw TaskTransitionNotAllowed::withoutOpenSession();

            $session->ended_at = now();
            $session->save();

            $current->status = TaskStatus::Paused;
            $current->save();

            $resumePoint = new ResumePoint([
                'where_stopped' => $whereStopped,
                'next_step' => $nextStep,
            ]);
            $resumePoint->task_id = $current->id;
            $resumePoint->work_session_id = $session->id;
            $resumePoint->save();

            return $resumePoint;
        });

        $task->refresh();

        return $resumePoint;
    }
}
