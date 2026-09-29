<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            // The owner is copied from the project so that the two never differ.
            'user_id' => fn (array $attributes): mixed => Project::query()
                ->whereKey($attributes['project_id'])
                ->value('user_id'),
            'title' => fake()->sentence(4),
            'description' => null,
            'estimate_minutes' => null,
            'branch_name' => null,
            'status' => TaskStatus::Todo,
            'completed_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Active,
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Paused,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function withBranch(): static
    {
        return $this->state(fn (): array => [
            'branch_name' => 'feature/'.fake()->slug(2),
        ]);
    }
}
