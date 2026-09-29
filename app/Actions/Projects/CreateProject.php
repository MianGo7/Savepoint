<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

/**
 * Creates a project for a developer. Implements FR1.
 */
final class CreateProject
{
    public function handle(User $user, string $name): Project
    {
        return $user->projects()->create(['name' => trim($name)]);
    }
}
