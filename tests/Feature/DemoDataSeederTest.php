<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use App\Queries\OverviewQuery;
use App\Queries\TimeReportQuery;
use Carbon\CarbonImmutable;
use Database\Seeders\DemoDataSeeder;

test('the demo data covers every state and page of the screenshots', function () {
    $user = User::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    $savepoint = Project::query()->where('name', 'Savepoint')->sole();
    $overview = app(OverviewQuery::class);

    expect(Project::query()->count())->toBe(4)
        ->and(Project::query()->whereNotNull('archived_at')->count())->toBe(1)
        ->and($savepoint->tasks->pluck('status')->unique()->map->value->sort()->values()->all())
        ->toBe(['active', 'completed', 'paused', 'todo'])
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and($overview->active($user))->not->toBeNull()
        ->and($overview->paused($user))->toHaveCount(3)
        ->and($overview->paused($user)->filter(fn (Task $task) => $task->switchCommand() !== null))->toHaveCount(2)
        ->and($overview->paused($user)->every(fn (Task $task) => $task->latestResumePoint !== null))->toBeTrue()
        ->and(Task::query()->where('title', 'Write the time report query')->sole()->resumePoints)->toHaveCount(4);
});

test('the demo sessions cover a week with several tasks and days', function () {
    $user = User::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    $report = app(TimeReportQuery::class)->handle($user, CarbonImmutable::now()->subDays(6), CarbonImmutable::now());

    expect(count($report['days']))->toBeGreaterThanOrEqual(6)
        ->and(count($report['tasks']))->toBeGreaterThanOrEqual(6);
});

test('every resume point belongs to a session of its own task', function () {
    User::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    expect(ResumePoint::query()->with('workSession')->get()->every(
        fn (ResumePoint $point) => $point->workSession->task_id === $point->task_id
    ))->toBeTrue();
});

test('every earlier session of a task ends with a resume point', function () {
    User::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    $withoutResumePoint = WorkSession::query()
        ->whereNotNull('ended_at')
        ->whereDoesntHave('resumePoint')
        ->get()
        ->filter(fn (WorkSession $session) => WorkSession::query()
            ->where('task_id', $session->task_id)
            ->where('started_at', '>', $session->started_at)
            ->exists());

    expect($withoutResumePoint)->toBeEmpty();
});

test('the seeder leaves a user with projects untouched', function () {
    $project = Project::factory()->create();

    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    expect(Project::query()->count())->toBe(1)
        ->and(Task::query()->count())->toBe(0)
        ->and($project->user->projects()->count())->toBe(1);
});

test('a database without a user gets a demo user', function () {
    $this->artisan('db:seed', ['--class' => DemoDataSeeder::class])->assertSuccessful();

    expect(User::query()->count())->toBe(1)
        ->and(Task::query()->where('status', TaskStatus::Active)->count())->toBe(1);
});
