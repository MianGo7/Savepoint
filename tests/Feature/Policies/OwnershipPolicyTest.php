<?php

use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;

test('the owner may view, update and delete a project', function () {
    $project = Project::factory()->create();

    expect($project->user->can('view', $project))->toBeTrue()
        ->and($project->user->can('update', $project))->toBeTrue()
        ->and($project->user->can('delete', $project))->toBeTrue();
});

test('a foreign user may not touch a project', function () {
    $project = Project::factory()->create();
    $foreign = User::factory()->create();

    expect($foreign->can('view', $project))->toBeFalse()
        ->and($foreign->can('update', $project))->toBeFalse()
        ->and($foreign->can('delete', $project))->toBeFalse();
});

test('the owner may view, update and delete a task', function () {
    $task = Task::factory()->create();

    expect($task->user->can('view', $task))->toBeTrue()
        ->and($task->user->can('update', $task))->toBeTrue()
        ->and($task->user->can('delete', $task))->toBeTrue();
});

test('a foreign user may not touch a task', function () {
    $task = Task::factory()->create();
    $foreign = User::factory()->create();

    expect($foreign->can('view', $task))->toBeFalse()
        ->and($foreign->can('update', $task))->toBeFalse()
        ->and($foreign->can('delete', $task))->toBeFalse();
});

test('a task may only be created in an own project', function () {
    $project = Project::factory()->create();
    $foreign = User::factory()->create();

    expect($project->user->can('create', [Task::class, $project]))->toBeTrue()
        ->and($foreign->can('create', [Task::class, $project]))->toBeFalse();
});

test('only the owner may view a work session', function () {
    $session = WorkSession::factory()->create();
    $foreign = User::factory()->create();

    expect($session->user->can('view', $session))->toBeTrue()
        ->and($foreign->can('view', $session))->toBeFalse();
});

test('only the owner of the task may view a resume point', function () {
    $resumePoint = ResumePoint::factory()->create();
    $foreign = User::factory()->create();

    expect($resumePoint->task->user->can('view', $resumePoint))->toBeTrue()
        ->and($foreign->can('view', $resumePoint))->toBeFalse();
});
