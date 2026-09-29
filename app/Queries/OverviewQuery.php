<?php

namespace App\Queries;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\Eloquent\Collection;

/**
 * Read model of the overview: the active task and the paused tasks by last
 * activity, each with its latest resume point. Implements FR8.
 */
final class OverviewQuery
{
    public function active(User $user): ?Task
    {
        return Task::query()
            ->where('user_id', $user->id)
            ->where('status', TaskStatus::Active)
            ->with(['project', 'openWorkSession', 'latestResumePoint'])
            ->first();
    }

    /**
     * Ordered by the end of the latest work session, because that is the last
     * moment the developer worked on the task; no column stores it (ADR-0006).
     *
     * @return Collection<int, Task>
     */
    public function paused(User $user): Collection
    {
        return Task::query()
            ->select('tasks.*')
            ->where('user_id', $user->id)
            ->where('status', TaskStatus::Paused)
            ->with(['project', 'latestResumePoint'])
            ->orderByDesc(
                WorkSession::query()
                    ->select('ended_at')
                    ->whereColumn('work_sessions.task_id', 'tasks.id')
                    ->orderByDesc('ended_at')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->get();
    }
}
