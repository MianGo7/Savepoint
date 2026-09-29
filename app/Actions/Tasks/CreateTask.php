<?php

namespace App\Actions\Tasks;

use App\Exceptions\ProjectArchived;
use App\Models\Project;
use App\Models\Task;

/**
 * Creates a task in a project, owned by the owner of the project. Implements
 * FR2.
 */
final class CreateTask
{
    /**
     * @param  array{title: string, description: string|null, estimate_minutes: int|null, branch_name: string|null}  $data
     *
     * @throws ProjectArchived
     */
    public function handle(Project $project, array $data): Task
    {
        if ($project->archived_at !== null) {
            throw ProjectArchived::make();
        }

        $task = new Task($this->normalise($data));
        $task->project_id = $project->id;
        $task->user_id = $project->user_id;
        $task->save();

        // The database default of the status is not on the new instance yet.
        return $task->refresh();
    }

    /**
     * @param  array{title: string, description: string|null, estimate_minutes: int|null, branch_name: string|null}  $data
     * @return array{title: string, description: string|null, estimate_minutes: int|null, branch_name: string|null}
     */
    private function normalise(array $data): array
    {
        return [
            'title' => trim($data['title']),
            'description' => filled($data['description']) ? trim($data['description']) : null,
            'estimate_minutes' => $data['estimate_minutes'],
            'branch_name' => filled($data['branch_name']) ? trim($data['branch_name']) : null,
        ];
    }
}
