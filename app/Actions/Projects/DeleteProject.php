<?php

namespace App\Actions\Projects;

use App\Enums\TaskStatus;
use App\Exceptions\ActiveTaskCannotBeDeleted;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a project together with its tasks, work sessions and resume points.
 * Implements FR1.
 */
final class DeleteProject
{
    /**
     * @throws ActiveTaskCannotBeDeleted
     */
    public function handle(Project $project): void
    {
        DB::transaction(function () use ($project): void {
            if ($project->tasks()->where('status', TaskStatus::Active)->exists()) {
                throw ActiveTaskCannotBeDeleted::make();
            }

            $project->delete();
        });
    }
}
