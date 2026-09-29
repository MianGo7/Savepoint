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
 * Pauses the active task with a resume point and starts or resumes another
 * task in one transaction. Implements FR5 and NFR3.
 */
final class SwitchTask
{
    public function __construct(
        private readonly PauseTask $pauseTask,
        private readonly StartTask $startTask,
        private readonly ResumeTask $resumeTask,
    ) {}

    /**
     * @return array{resumePoint: ResumePoint, session: WorkSession}
     *
     * @throws ResumePointRequired
     * @throws TaskTransitionNotAllowed
     */
    public function handle(Task $target, string $whereStopped, string $nextStep): array
    {
        return DB::transaction(function () use ($target, $whereStopped, $nextStep): array {
            // Checked before anything is paused, so that a target that cannot be
            // started is reported as such and not as a failed pause.
            $targetStatus = Task::query()->findOrFail($target->id)->status;

            if (! in_array($targetStatus, [TaskStatus::Todo, TaskStatus::Paused], true)) {
                throw TaskTransitionNotAllowed::from($targetStatus, 'switched to');
            }

            $active = Task::query()
                ->where('user_id', $target->user_id)
                ->where('status', TaskStatus::Active)
                ->first() ?? throw TaskTransitionNotAllowed::withoutActiveTask();

            $resumePoint = $this->pauseTask->handle($active, $whereStopped, $nextStep);

            // A failure here rolls the pause back as well, so that no resume point
            // is stored for a switch that did not happen (NFR4).
            $session = $targetStatus === TaskStatus::Paused
                ? $this->resumeTask->handle($target)
                : $this->startTask->handle($target);

            return ['resumePoint' => $resumePoint, 'session' => $session];
        });
    }
}
