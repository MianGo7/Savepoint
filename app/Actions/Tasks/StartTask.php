<?php

namespace App\Actions\Tasks;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\WorkSession;

/**
 * Starts a task that has not been worked on yet. Implements FR3.
 */
final class StartTask
{
    public function __construct(private readonly OpenWorkSession $openWorkSession) {}

    public function handle(Task $task): WorkSession
    {
        return $this->openWorkSession->handle($task, TaskStatus::Todo, 'started');
    }
}
