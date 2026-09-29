<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkSession>
 */
class WorkSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subHour();

        return [
            'task_id' => Task::factory(),
            'user_id' => fn (array $attributes): mixed => Task::query()
                ->whereKey($attributes['task_id'])
                ->value('user_id'),
            'started_at' => $startedAt,
            'ended_at' => $startedAt->addMinutes(45),
        ];
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'ended_at' => null,
        ]);
    }
}
