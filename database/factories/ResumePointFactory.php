<?php

namespace Database\Factories;

use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResumePoint>
 */
class ResumePointFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            // The session is created for the same task, never for another one.
            'work_session_id' => fn (array $attributes): mixed => WorkSession::factory()
                ->create(['task_id' => $attributes['task_id']])
                ->id,
            'where_stopped' => fake()->sentence(),
            'next_step' => fake()->sentence(),
        ];
    }
}
