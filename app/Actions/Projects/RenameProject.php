<?php

namespace App\Actions\Projects;

use App\Models\Project;

/**
 * Renames a project. Implements FR1.
 */
final class RenameProject
{
    public function handle(Project $project, string $name): Project
    {
        $project->update(['name' => trim($name)]);

        return $project;
    }
}
