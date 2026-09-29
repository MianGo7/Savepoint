<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\WorkSession;

/**
 * Resumes a paused task with a new work session. Showing the latest resume
 * point beforehand belongs to the page. Implements FR6.
 */
final class ResumeTask
{
    public function __construct(private readonly OpenWorkSession $openWorkSession) {}

    public function handle(Task $task): WorkSession
    {
        return $this->openWorkSession->handle($task, TaskStatus::Paused, 'resumed');
    }
}
