<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use App\Queries\TimeReportQuery;
use Carbon\CarbonImmutable;

/**
 * @return array{days: list<array<string, mixed>>, tasks: list<array<string, mixed>>}
 */
function timeReport(User $user, string $from, string $to): array
{
    return app(TimeReportQuery::class)->handle($user, CarbonImmutable::parse($from), CarbonImmutable::parse($to));
}

function recordSession(Task $task, string $start, ?string $end): WorkSession
{
    return WorkSession::factory()->for($task)->create(['started_at' => $start, 'ended_at' => $end]);
}

test('a session within one day counts for that day and task', function () {
    $task = Task::factory()->create();
    recordSession($task, '2026-09-28 09:00:00', '2026-09-28 10:30:00');

    $report = timeReport($task->user, '2026-09-28', '2026-09-28');

    expect($report['days'])->toHaveCount(1)
        ->and($report['days'][0]['date'])->toBe('2026-09-28')
        ->and($report['days'][0]['seconds'])->toBe(5400)
        ->and($report['tasks'][0]['task']->is($task))->toBeTrue()
        ->and($report['tasks'][0]['seconds'])->toBe(5400);
});

test('a session that crosses midnight is split at the boundary', function () {
    $task = Task::factory()->create();
    recordSession($task, '2026-09-28 23:00:00', '2026-09-29 01:30:00');

    $report = timeReport($task->user, '2026-09-28', '2026-09-29');

    expect(array_column($report['days'], 'seconds', 'date'))->toBe([
        '2026-09-29' => 5400,
        '2026-09-28' => 3600,
    ])->and($report['tasks'][0]['seconds'])->toBe(9000);
});

test('a session over several days is split into whole days', function () {
    $task = Task::factory()->create();
    recordSession($task, '2026-09-27 22:00:00', '2026-09-30 02:00:00');

    $report = timeReport($task->user, '2026-09-27', '2026-09-30');

    expect(array_column($report['days'], 'seconds', 'date'))->toBe([
        '2026-09-30' => 7200,
        '2026-09-29' => 86400,
        '2026-09-28' => 86400,
        '2026-09-27' => 7200,
    ]);
});

test('midnight is the midnight of the display time zone', function () {
    config(['app.display_timezone' => 'Europe/Berlin']);
    $task = Task::factory()->create();
    // 21:30 UTC is 23:30 in Berlin in summer, so one hour falls on the next local day.
    recordSession($task, '2026-09-28 21:30:00', '2026-09-28 23:30:00');

    $report = timeReport($task->user, '2026-09-28', '2026-09-29');

    expect(array_column($report['days'], 'seconds', 'date'))->toBe([
        '2026-09-29' => 5400,
        '2026-09-28' => 1800,
    ]);
});

test('a day with a daylight saving change counts its actual length', function () {
    config(['app.display_timezone' => 'Europe/Berlin']);
    $task = Task::factory()->create();
    // 29 March 2026 has 23 hours in Berlin: local midnight is 28 March 23:00 UTC.
    recordSession($task, '2026-03-28 23:00:00', '2026-03-29 22:00:00');

    $report = timeReport($task->user, '2026-03-29', '2026-03-29');

    expect($report['days'][0]['seconds'])->toBe(23 * 3600);
});

test('an open session counts up to the current time', function () {
    $this->travelTo('2026-09-29 12:00:00');
    $task = Task::factory()->create();
    recordSession($task, '2026-09-29 10:00:00', null);

    $report = timeReport($task->user, '2026-09-29', '2026-09-29');

    expect($report['days'][0]['seconds'])->toBe(7200);
});

test('sessions are clipped to the requested range', function () {
    $task = Task::factory()->create();
    recordSession($task, '2026-09-27 23:00:00', '2026-09-28 01:00:00');
    recordSession($task, '2026-09-28 23:00:00', '2026-09-29 01:00:00');

    $report = timeReport($task->user, '2026-09-28', '2026-09-28');

    expect($report['days'])->toHaveCount(1)
        ->and($report['days'][0]['seconds'])->toBe(3600 + 3600);
});

test('sessions outside the range and of other developers are ignored', function () {
    $task = Task::factory()->create();
    recordSession($task, '2026-09-01 09:00:00', '2026-09-01 10:00:00');
    recordSession(Task::factory()->create(), '2026-09-28 09:00:00', '2026-09-28 10:00:00');

    $report = timeReport($task->user, '2026-09-28', '2026-09-28');

    expect($report['days'])->toBe([])->and($report['tasks'])->toBe([]);
});

test('time is grouped per task within a day and ordered by duration', function () {
    $project = Project::factory()->create();
    $short = Task::factory()->for($project)->create();
    $long = Task::factory()->for($project)->create();
    recordSession($short, '2026-09-28 09:00:00', '2026-09-28 09:30:00');
    recordSession($long, '2026-09-28 10:00:00', '2026-09-28 12:00:00');
    recordSession($short, '2026-09-28 13:00:00', '2026-09-28 13:15:00');

    $report = timeReport($project->user, '2026-09-28', '2026-09-28');

    expect($report['days'][0]['seconds'])->toBe(2700 + 7200)
        ->and($report['days'][0]['tasks'][0]['task']->is($long))->toBeTrue()
        ->and($report['days'][0]['tasks'][1]['seconds'])->toBe(2700)
        ->and($report['tasks'][0]['task']->is($long))->toBeTrue();
});
