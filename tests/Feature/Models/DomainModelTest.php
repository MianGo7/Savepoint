<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Database\QueryException;

test('a task takes its owner from its project', function () {
    $project = Project::factory()->create();

    $task = Task::factory()->for($project)->create();

    expect($task->user_id)->toBe($project->user_id);
});

test('the relationships connect the user, project, task, session and resume point', function () {
    $resumePoint = ResumePoint::factory()->create();
    $task = $resumePoint->task;

    expect($task->project->tasks->contains($task))->toBeTrue()
        ->and($task->user->projects->contains($task->project))->toBeTrue()
        ->and($task->user->tasks->contains($task))->toBeTrue()
        ->and($task->workSessions->contains($resumePoint->workSession))->toBeTrue()
        ->and($resumePoint->workSession->task->is($task))->toBeTrue()
        ->and($task->user->workSessions->contains($resumePoint->workSession))->toBeTrue()
        ->and($resumePoint->workSession->resumePoint->is($resumePoint))->toBeTrue();
});

test('the latest resume point of a task is the most recently created one', function () {
    $task = Task::factory()->paused()->create();
    ResumePoint::factory()->for($task)->create(['created_at' => now()->subDay()]);
    $latest = ResumePoint::factory()->for($task)->create(['created_at' => now()]);

    expect($task->latestResumePoint->is($latest))->toBeTrue();
});

test('factory states produce the matching lifecycle values', function () {
    expect(Task::factory()->create()->status)->toBe(TaskStatus::Todo)
        ->and(Task::factory()->active()->create()->status)->toBe(TaskStatus::Active)
        ->and(Task::factory()->paused()->create()->status)->toBe(TaskStatus::Paused)
        ->and(Task::factory()->withBranch()->create()->branch_name)->not->toBeNull();

    $completed = Task::factory()->completed()->create();

    expect($completed->status)->toBe(TaskStatus::Completed)
        ->and($completed->completed_at)->not->toBeNull()
        ->and(Project::factory()->archived()->create()->archived_at)->not->toBeNull()
        ->and(WorkSession::factory()->open()->create()->ended_at)->toBeNull();
});

test('every task status has a label', function () {
    foreach (TaskStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

test('a second open work session of the same user is rejected by the database', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    WorkSession::factory()->open()->for(Task::factory()->for($project)->create())->create();

    $second = fn () => WorkSession::factory()->open()->for(Task::factory()->for($project)->create())->create();

    expect($second)->toThrow(QueryException::class);
});

test('open work sessions of different users do not collide', function () {
    WorkSession::factory()->open()->create();
    WorkSession::factory()->open()->create();

    expect(WorkSession::query()->whereNull('ended_at')->count())->toBe(2);
});

test('closed work sessions do not count as open', function () {
    $task = Task::factory()->create();
    WorkSession::factory()->count(2)->for($task)->create();
    WorkSession::factory()->open()->for($task)->create();

    expect(WorkSession::query()->count())->toBe(3);
});

test('deleting a project removes its tasks, sessions and resume points', function () {
    $resumePoint = ResumePoint::factory()->create();

    $resumePoint->task->project->delete();

    expect(Task::query()->count())->toBe(0)
        ->and(WorkSession::query()->count())->toBe(0)
        ->and(ResumePoint::query()->count())->toBe(0);
});
