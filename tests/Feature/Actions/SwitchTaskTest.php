<?php

use App\Actions\Tasks\SwitchTask;
use App\Enums\TaskStatus;
use App\Exceptions\ProjectArchived;
use App\Exceptions\ResumePointRequired;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\WorkSession;

/**
 * An active task with an open session and a second task of the same developer.
 *
 * @return array{Task, Task}
 */
function activeAndTarget(TaskStatus $targetStatus = TaskStatus::Todo, ?Project $project = null): array
{
    $project ??= Project::factory()->create();
    $active = Task::factory()->for($project)->active()->create();
    WorkSession::factory()->open()->for($active)->create();
    $target = Task::factory()->for($project)->create(['status' => $targetStatus]);

    return [$active, $target];
}

test('switching pauses the active task with its resume point and starts the target', function () {
    [$active, $target] = activeAndTarget();
    $this->travelTo('2026-09-29 12:00:00');

    $result = app(SwitchTask::class)->handle($target, 'Parsing works', 'Write the validator');

    expect($active->refresh()->status)->toBe(TaskStatus::Paused)
        ->and($target->refresh()->status)->toBe(TaskStatus::Active)
        ->and($result['resumePoint']->task_id)->toBe($active->id)
        ->and($result['resumePoint']->where_stopped)->toBe('Parsing works')
        ->and($result['session']->task_id)->toBe($target->id)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and($active->workSessions()->sole()->ended_at?->toDateTimeString())->toBe('2026-09-29 12:00:00');
});

test('switching to a paused task resumes it and keeps its resume points', function () {
    [$active, $target] = activeAndTarget(TaskStatus::Paused);
    ResumePoint::factory()->for($target)->create();

    app(SwitchTask::class)->handle($target, 'Where', 'Next');

    expect($target->refresh()->status)->toBe(TaskStatus::Active)
        ->and($target->resumePoints()->count())->toBe(1)
        ->and($active->resumePoints()->count())->toBe(1);
});

test('switching without a resume point changes nothing', function () {
    [$active, $target] = activeAndTarget();

    expect(fn () => app(SwitchTask::class)->handle($target, '', 'Next'))->toThrow(ResumePointRequired::class);

    expect($active->refresh()->status)->toBe(TaskStatus::Active)
        ->and($target->refresh()->status)->toBe(TaskStatus::Todo)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and(ResumePoint::query()->count())->toBe(0);
});

test('a failed start of the target rolls the pause back', function () {
    $project = Project::factory()->archived()->create();
    [$active, $target] = activeAndTarget(project: $project);

    expect(fn () => app(SwitchTask::class)->handle($target, 'Where', 'Next'))->toThrow(ProjectArchived::class);

    expect($active->refresh()->status)->toBe(TaskStatus::Active)
        ->and($target->refresh()->status)->toBe(TaskStatus::Todo)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and(ResumePoint::query()->count())->toBe(0);
});

test('switching to a task that is active or completed is rejected without pausing', function (TaskStatus $status) {
    [$active, $target] = activeAndTarget($status);

    expect(fn () => app(SwitchTask::class)->handle($target, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);

    expect($active->refresh()->status)->toBe(TaskStatus::Active)
        ->and(ResumePoint::query()->count())->toBe(0);
})->with([TaskStatus::Active, TaskStatus::Completed]);

test('switching to the active task itself is rejected', function () {
    [$active] = activeAndTarget();

    expect(fn () => app(SwitchTask::class)->handle($active, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);

    expect(ResumePoint::query()->count())->toBe(0);
});

test('switching without an active task is rejected', function () {
    $target = Task::factory()->create();

    expect(fn () => app(SwitchTask::class)->handle($target, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);

    expect($target->refresh()->status)->toBe(TaskStatus::Todo);
});

test('the active task of another developer is never paused', function () {
    [$foreignActive] = activeAndTarget();
    $target = Task::factory()->create();

    expect(fn () => app(SwitchTask::class)->handle($target, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);

    expect($foreignActive->refresh()->status)->toBe(TaskStatus::Active);
});
