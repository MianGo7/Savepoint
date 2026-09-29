<?php

namespace App\Actions\Projects;

use App\Models\Project;

/**
 * Archives a project and leaves its tasks and work sessions untouched, so that
 * recorded time stays reportable. Implements FR1.
 */
final class ArchiveProject
{
    public function handle(Project $project): Project
    {
        // ADR-0007: archiving never rejects a project with open tasks.
        if ($project->archived_at === null) {
            $project->archived_at = now();
            $project->save();
        }

        return $project;
    }
}
