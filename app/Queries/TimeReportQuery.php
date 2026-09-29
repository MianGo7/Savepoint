<?php

namespace App\Queries;

use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;

/**
 * Read model of the time report: the recorded time per task and per day,
 * derived from the work sessions. Implements FR9.
 *
 * @phpstan-type TaskTime array{task: Task, seconds: int}
 * @phpstan-type DayTime array{date: string, seconds: int, tasks: list<TaskTime>}
 * @phpstan-type Report array{days: list<DayTime>, tasks: list<TaskTime>}
 */
final class TimeReportQuery
{
    /**
     * Both dates are inclusive and read as days of the display time zone. A
     * session that crosses midnight of that zone is split at the boundary, and
     * a session that is still open counts up to the current time.
     *
     * @return Report
     */
    public function handle(User $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $zone = config('app.display_timezone');
        $rangeStart = CarbonImmutable::parse($from->format('Y-m-d'), $zone)->startOfDay();
        // ADR-0009: a calendar day is added, so that days with a daylight saving change stay whole.
        $rangeEnd = CarbonImmutable::parse($to->format('Y-m-d'), $zone)->startOfDay()->addDay();
        $now = Date::now();

        $sessions = WorkSession::query()
            ->where('user_id', $user->id)
            ->where('started_at', '<', $rangeEnd->utc())
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhere('ended_at', '>', $rangeStart->utc()))
            ->get(['task_id', 'started_at', 'ended_at']);

        /** @var array<string, array<int, int>> $seconds date => task id => seconds */
        $seconds = [];

        foreach ($sessions as $session) {
            $cursor = $session->started_at->max($rangeStart)->setTimezone($zone);
            $end = ($session->ended_at ?? $now)->min($rangeEnd);

            while ($cursor < $end) {
                $dayEnd = $cursor->startOfDay()->addDay();
                $segmentEnd = $dayEnd < $end ? $dayEnd : $end;
                $date = $cursor->format('Y-m-d');

                $seconds[$date][$session->task_id] = ($seconds[$date][$session->task_id] ?? 0)
                    + $segmentEnd->getTimestamp() - $cursor->getTimestamp();
                $cursor = $segmentEnd;
            }
        }

        $taskIds = collect($seconds)->flatMap(fn (array $day): array => array_keys($day))->unique()->all();
        $tasks = Task::query()->whereIn('id', $taskIds)->get()->keyBy('id');

        return [
            'days' => $this->days($seconds, $tasks),
            'tasks' => $this->totals($seconds, $tasks),
        ];
    }

    /**
     * @param  array<string, array<int, int>>  $seconds
     * @param  Collection<int, Task>  $tasks
     * @return list<DayTime>
     */
    private function days(array $seconds, $tasks): array
    {
        krsort($seconds);

        $days = [];
        foreach ($seconds as $date => $perTask) {
            arsort($perTask);
            $days[] = [
                'date' => (string) $date,
                'seconds' => array_sum($perTask),
                'tasks' => array_map(
                    fn (int $taskId, int $taskSeconds): array => ['task' => $tasks[$taskId], 'seconds' => $taskSeconds],
                    array_keys($perTask),
                    $perTask,
                ),
            ];
        }

        return $days;
    }

    /**
     * @param  array<string, array<int, int>>  $seconds
     * @param  Collection<int, Task>  $tasks
     * @return list<TaskTime>
     */
    private function totals(array $seconds, $tasks): array
    {
        $totals = [];
        foreach ($seconds as $perTask) {
            foreach ($perTask as $taskId => $taskSeconds) {
                $totals[$taskId] = ($totals[$taskId] ?? 0) + $taskSeconds;
            }
        }
        arsort($totals);

        return array_map(
            fn (int $taskId, int $taskSeconds): array => ['task' => $tasks[$taskId], 'seconds' => $taskSeconds],
            array_keys($totals),
            $totals,
        );
    }
}
