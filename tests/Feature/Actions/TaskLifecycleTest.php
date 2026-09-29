<?php

use App\Actions\Tasks\CompleteTask;
use App\Actions\Tasks\PauseTask;
use App\Actions\Tasks\ResumeTask;
use App\Actions\Tasks\StartTask;
use App\Enums\TaskStatus;
use App\Exceptions\ProjectArchived;
use App\Exceptions\ResumePointRequired;
use App\Exceptions\SessionAlreadyOpen;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\WorkSession;

/**
 * An active task with the open work session that the lifecycle expects.
 */
function activeTask(?Project $project = null): Task
{
    $task = $project
        ? Task::factory()->for($project)->active()->create()
        : Task::factory()->active()->create();
    WorkSession::factory()->open()->for($task)->create();

    return $task;
}

test('starting a todo task opens a work session and activates it', function () {
    $this->travelTo('2026-09-29 09:00:00');
    $task = Task::factory()->create();

    $session = app(StartTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Active)
        ->and($session->task_id)->toBe($task->id)
        ->and($session->user_id)->toBe($task->user_id)
        ->and($session->started_at->toDateTimeString())->toBe('2026-09-29 09:00:00')
        ->and($session->ended_at)->toBeNull();
});

test('starting a task that is not todo is rejected', function (TaskStatus $status) {
    $task = Task::factory()->create(['status' => $status]);

    expect(fn () => app(StartTask::class)->handle($task))->toThrow(TaskTransitionNotAllowed::class);
    expect(WorkSession::query()->count())->toBe(0);
})->with([TaskStatus::Active, TaskStatus::Paused, TaskStatus::Completed]);

test('starting a task while another one is active is rejected', function () {
    $active = activeTask();
    $other = Task::factory()->for($active->project)->create();

    expect(fn () => app(StartTask::class)->handle($other))->toThrow(SessionAlreadyOpen::class);

    expect($other->refresh()->status)->toBe(TaskStatus::Todo)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1);
});

test('a double submission of a start does not open a second session', function () {
    $task = Task::factory()->create();
    $stale = Task::query()->findOrFail($task->id);

    app(StartTask::class)->handle($task);

    expect(fn () => app(StartTask::class)->handle($stale))->toThrow(TaskTransitionNotAllowed::class);
    expect(WorkSession::query()->count())->toBe(1);
});

test('open sessions of different developers do not block each other', function () {
    activeTask();
    $task = Task::factory()->create();

    app(StartTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Active);
});

test('pausing stores the resume point, closes the session and pauses the task', function () {
    $task = activeTask();
    $this->travelTo('2026-09-29 11:30:00');

    $resumePoint = app(PauseTask::class)->handle($task, '  Parsing works  ', 'Write the validator');

    $session = WorkSession::query()->where('task_id', $task->id)->sole();
    expect($task->status)->toBe(TaskStatus::Paused)
        ->and($session->ended_at?->toDateTimeString())->toBe('2026-09-29 11:30:00')
        ->and($resumePoint->where_stopped)->toBe('Parsing works')
        ->and($resumePoint->next_step)->toBe('Write the validator')
        ->and($resumePoint->work_session_id)->toBe($session->id)
        ->and($task->latestResumePoint->is($resumePoint))->toBeTrue();
});

test('pausing without a resume point is rejected', function (string $where, string $next) {
    $task = activeTask();

    expect(fn () => app(PauseTask::class)->handle($task, $where, $next))->toThrow(ResumePointRequired::class);

    expect($task->refresh()->status)->toBe(TaskStatus::Active)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and(ResumePoint::query()->count())->toBe(0);
})->with([
    'empty where' => ['', 'Next'],
    'empty next' => ['Where', ''],
    'blank both' => ['  ', "\n"],
]);

test('pausing a task that is not active is rejected', function (TaskStatus $status) {
    $task = Task::factory()->create(['status' => $status]);

    expect(fn () => app(PauseTask::class)->handle($task, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);
    expect(ResumePoint::query()->count())->toBe(0);
})->with([TaskStatus::Todo, TaskStatus::Paused, TaskStatus::Completed]);

test('pausing an active task without an open session is rejected', function () {
    $task = Task::factory()->active()->create();

    expect(fn () => app(PauseTask::class)->handle($task, 'Where', 'Next'))->toThrow(TaskTransitionNotAllowed::class);

    expect($task->refresh()->status)->toBe(TaskStatus::Active);
});

test('a failed pause leaves the data unchanged', function () {
    $task = activeTask();
    ResumePoint::creating(fn () => throw new RuntimeException('storage failure'));

    expect(fn () => app(PauseTask::class)->handle($task, 'Where', 'Next'))->toThrow(RuntimeException::class);

    expect($task->refresh()->status)->toBe(TaskStatus::Active)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1)
        ->and(ResumePoint::query()->count())->toBe(0);
});

test('resuming a paused task opens a new session and keeps the earlier resume points', function () {
    $task = Task::factory()->paused()->create();
    ResumePoint::factory()->for($task)->create();

    $session = app(ResumeTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Active)
        ->and($session->ended_at)->toBeNull()
        ->and($task->resumePoints()->count())->toBe(1);
});

test('resuming a task that is not paused is rejected', function (TaskStatus $status) {
    $task = Task::factory()->create(['status' => $status]);

    expect(fn () => app(ResumeTask::class)->handle($task))->toThrow(TaskTransitionNotAllowed::class);
})->with([TaskStatus::Todo, TaskStatus::Active, TaskStatus::Completed]);

test('resuming while another task is active is rejected', function () {
    $active = activeTask();
    $paused = Task::factory()->for($active->project)->paused()->create();

    expect(fn () => app(ResumeTask::class)->handle($paused))->toThrow(SessionAlreadyOpen::class);

    expect($paused->refresh()->status)->toBe(TaskStatus::Paused);
});

test('completing an active task closes its session', function () {
    $task = activeTask();
    $this->travelTo('2026-09-29 17:00:00');

    app(CompleteTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Completed)
        ->and($task->completed_at?->toDateTimeString())->toBe('2026-09-29 17:00:00')
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(0);
});

test('completing a paused task keeps its closed sessions', function () {
    $task = Task::factory()->paused()->create();
    WorkSession::factory()->for($task)->create();

    app(CompleteTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Completed)
        ->and($task->workSessions()->count())->toBe(1);
});

test('completing a todo or completed task is rejected', function (TaskStatus $status) {
    $task = Task::factory()->create(['status' => $status]);

    expect(fn () => app(CompleteTask::class)->handle($task))->toThrow(TaskTransitionNotAllowed::class);
})->with([TaskStatus::Todo, TaskStatus::Completed]);

test('a completed task frees the developer to start another one', function () {
    $active = activeTask();
    $next = Task::factory()->for($active->project)->create();

    app(CompleteTask::class)->handle($active);
    app(StartTask::class)->handle($next);

    expect($next->status)->toBe(TaskStatus::Active);
});

test('a concurrent open session that passes the check is rejected by the index', function () {
    $task = Task::factory()->create();
    $other = Task::factory()->for($task->project)->create();
    // Simulates a second request that opens its session between the check and the insert.
    WorkSession::creating(function () use ($other) {
        WorkSession::withoutEvents(fn () => WorkSession::factory()->open()->for($other)->create());
    });

    expect(fn () => app(StartTask::class)->handle($task))->toThrow(SessionAlreadyOpen::class);

    expect($task->refresh()->status)->toBe(TaskStatus::Todo)
        ->and(WorkSession::query()->count())->toBe(0);
});

test('starting or resuming a task in an archived project is rejected', function () {
    $project = Project::factory()->archived()->create();
    $todo = Task::factory()->for($project)->create();
    $paused = Task::factory()->for($project)->paused()->create();

    expect(fn () => app(StartTask::class)->handle($todo))->toThrow(ProjectArchived::class);
    expect(fn () => app(ResumeTask::class)->handle($paused))->toThrow(ProjectArchived::class);

    expect(WorkSession::query()->count())->toBe(0);
});

test('a task that is active when its project is archived can still be paused and completed', function () {
    $task = activeTask();
    $task->project->forceFill(['archived_at' => now()])->save();

    app(PauseTask::class)->handle($task, 'Where', 'Next');
    app(CompleteTask::class)->handle($task);

    expect($task->status)->toBe(TaskStatus::Completed);
});
