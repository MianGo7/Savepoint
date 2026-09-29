<?php

namespace App\Actions\Tasks;

use App\Models\Task;

/**
 * Changes the descriptive fields of a task. The status is never changed here,
 * only by the lifecycle actions. Implements FR2.
 */
final class UpdateTask
{
    /**
     * @param  array{title: string, description: string|null, estimate_minutes: int|null, branch_name: string|null}  $data
     */
    public function handle(Task $task, array $data): Task
    {
        $task->update([
            'title' => trim($data['title']),
            'description' => filled($data['description']) ? trim($data['description']) : null,
            'estimate_minutes' => $data['estimate_minutes'],
            'branch_name' => filled($data['branch_name']) ? trim($data['branch_name']) : null,
        ]);

        return $task;
    }
}
