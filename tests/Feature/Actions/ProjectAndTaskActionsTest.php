<?php

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\RenameProject;
use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\DeleteTask;
use App\Actions\Tasks\UpdateTask;
use App\Enums\TaskStatus;
use App\Exceptions\ActiveTaskCannotBeDeleted;
use App\Exceptions\ProjectArchived;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;

test('creating a project stores it for the developer with a trimmed name', function () {
    $user = User::factory()->create();

    $project = app(CreateProject::class)->handle($user, '  Savepoint  ');

    expect($project->name)->toBe('Savepoint')
        ->and($project->user_id)->toBe($user->id)
        ->and($project->archived_at)->toBeNull();
});

test('renaming a project changes only its name', function () {
    $project = Project::factory()->create();

    app(RenameProject::class)->handle($project, ' Renamed ');

    expect($project->refresh()->name)->toBe('Renamed');
});

test('archiving keeps the tasks, their sessions and their status', function () {
    $this->travelTo('2026-09-29 10:00:00');
    $task = Task::factory()->active()->create();
    WorkSession::factory()->open()->for($task)->create();

    app(ArchiveProject::class)->handle($task->project);

    expect($task->project->refresh()->archived_at?->toDateTimeString())->toBe('2026-09-29 10:00:00')
        ->and($task->refresh()->status)->toBe(TaskStatus::Active)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1);
});

test('archiving an archived project keeps the first archive time', function () {
    $project = Project::factory()->archived()->create();
    $archivedAt = $project->archived_at;
    $this->travelTo(now()->addDay());

    app(ArchiveProject::class)->handle($project);

    expect($project->refresh()->archived_at?->equalTo($archivedAt))->toBeTrue();
});

test('deleting a project removes its tasks, sessions and resume points', function () {
    $resumePoint = ResumePoint::factory()->create();

    app(DeleteProject::class)->handle($resumePoint->task->project);

    expect(Project::query()->count())->toBe(0)
        ->and(Task::query()->count())->toBe(0)
        ->and(WorkSession::query()->count())->toBe(0)
        ->and(ResumePoint::query()->count())->toBe(0);
});

test('deleting a project with an active task is rejected', function () {
    $task = Task::factory()->active()->create();

    expect(fn () => app(DeleteProject::class)->handle($task->project))->toThrow(ActiveTaskCannotBeDeleted::class);

    expect(Project::query()->count())->toBe(1);
});

test('creating a task normalises the input and copies the owner from the project', function () {
    $project = Project::factory()->create();

    $task = app(CreateTask::class)->handle($project, [
        'title' => '  Write parser ',
        'description' => '  ',
        'estimate_minutes' => 90,
        'branch_name' => ' feature/parser ',
    ]);

    expect($task->title)->toBe('Write parser')
        ->and($task->description)->toBeNull()
        ->and($task->estimate_minutes)->toBe(90)
        ->and($task->branch_name)->toBe('feature/parser')
        ->and($task->user_id)->toBe($project->user_id)
        ->and($task->status)->toBe(TaskStatus::Todo);
});

test('creating a task in an archived project is rejected', function () {
    $project = Project::factory()->archived()->create();

    expect(fn () => app(CreateTask::class)->handle($project, [
        'title' => 'Task',
        'description' => null,
        'estimate_minutes' => null,
        'branch_name' => null,
    ]))->toThrow(ProjectArchived::class);

    expect(Task::query()->count())->toBe(0);
});

test('updating a task changes the descriptive fields and never the status', function () {
    $task = Task::factory()->paused()->create();

    app(UpdateTask::class)->handle($task, [
        'title' => 'New title',
        'description' => 'Details',
        'estimate_minutes' => null,
        'branch_name' => '',
    ]);

    expect($task->refresh()->title)->toBe('New title')
        ->and($task->description)->toBe('Details')
        ->and($task->branch_name)->toBeNull()
        ->and($task->status)->toBe(TaskStatus::Paused);
});

test('deleting a task removes its sessions and resume points', function () {
    $resumePoint = ResumePoint::factory()->create();

    app(DeleteTask::class)->handle($resumePoint->task);

    expect(Task::query()->count())->toBe(0)
        ->and(WorkSession::query()->count())->toBe(0)
        ->and(ResumePoint::query()->count())->toBe(0);
});

test('deleting an active task is rejected', function () {
    $task = Task::factory()->active()->create();

    expect(fn () => app(DeleteTask::class)->handle($task))->toThrow(ActiveTaskCannotBeDeleted::class);

    expect(Task::query()->count())->toBe(1);
});
