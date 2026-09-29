<?php

use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use App\Queries\OverviewQuery;

test('the active task is the one with the active status of the developer', function () {
    $active = Task::factory()->active()->create();
    Task::factory()->for($active->project)->create();
    Task::factory()->active()->create();

    expect(app(OverviewQuery::class)->active($active->user)?->is($active))->toBeTrue();
});

test('without an active task the overview has none', function () {
    $user = User::factory()->create();

    expect(app(OverviewQuery::class)->active($user))->toBeNull();
});

test('paused tasks are ordered by the end of their latest work session', function () {
    $project = Project::factory()->create();
    $oldest = Task::factory()->for($project)->paused()->create();
    $newest = Task::factory()->for($project)->paused()->create();
    $middle = Task::factory()->for($project)->paused()->create();
    // The task created first was paused last, so creation order must not decide.
    foreach ([[$newest, '2026-09-29 15:00:00'], [$middle, '2026-09-29 12:00:00'], [$oldest, '2026-09-28 09:00:00']] as [$task, $end]) {
        WorkSession::factory()->for($task)->create(['started_at' => '2026-09-01 08:00:00', 'ended_at' => $end]);
    }
    WorkSession::factory()->for($middle)->create(['started_at' => '2026-09-01 08:00:00', 'ended_at' => '2026-09-10 08:30:00']);

    $ids = app(OverviewQuery::class)->paused($project->user)->pluck('id')->all();

    expect($ids)->toBe([$newest->id, $middle->id, $oldest->id]);
});

test('paused tasks carry only their latest resume point', function () {
    $task = Task::factory()->paused()->create();
    ResumePoint::factory()->for($task)->create(['next_step' => 'Old step', 'created_at' => now()->subDay()]);
    ResumePoint::factory()->for($task)->create(['next_step' => 'Latest step', 'created_at' => now()]);

    $paused = app(OverviewQuery::class)->paused($task->user);

    expect($paused)->toHaveCount(1)
        ->and($paused->first()->latestResumePoint->next_step)->toBe('Latest step');
});

test('the overview never contains tasks of another developer or other statuses', function () {
    $own = Task::factory()->paused()->create();
    Task::factory()->for($own->project)->create();
    Task::factory()->for($own->project)->completed()->create();
    Task::factory()->paused()->create();

    $paused = app(OverviewQuery::class)->paused($own->user);

    expect($paused->pluck('id')->all())->toBe([$own->id]);
});

test('the overview stays within the response time with 1,000 tasks and 10,000 sessions', function () {
    $user = User::factory()->create();
    $projectId = Project::factory()->for($user)->create()->id;
    $now = now()->toDateTimeString();

    $tasks = [];
    for ($i = 0; $i < 1000; $i++) {
        $tasks[] = [
            'project_id' => $projectId, 'user_id' => $user->id, 'title' => "Task $i",
            'status' => $i < 300 ? 'paused' : 'completed', 'created_at' => $now, 'updated_at' => $now,
        ];
    }
    foreach (array_chunk($tasks, 200) as $chunk) {
        Task::query()->insert($chunk);
    }

    $sessions = [];
    $taskIds = Task::query()->pluck('id')->all();
    foreach ($taskIds as $taskId) {
        for ($j = 0; $j < 10; $j++) {
            $start = now()->subDays($j + 1)->subHours(2);
            $sessions[] = [
                'task_id' => $taskId, 'user_id' => $user->id, 'started_at' => $start->toDateTimeString(),
                'ended_at' => $start->addHour()->toDateTimeString(), 'created_at' => $now, 'updated_at' => $now,
            ];
        }
    }
    foreach (array_chunk($sessions, 500) as $chunk) {
        WorkSession::query()->insert($chunk);
    }

    $response = $this->actingAs($user)->withoutVite();
    $started = hrtime(true);
    $response->get(route('dashboard'))->assertOk();
    $elapsedMs = (hrtime(true) - $started) / 1e6;

    expect(WorkSession::query()->count())->toBe(10000)
        ->and($elapsedMs)->toBeLessThan(200);
});
