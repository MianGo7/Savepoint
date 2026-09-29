<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Livewire\Livewire;

test('the overview shows the active task and the paused tasks with their resume point', function () {
    $project = Project::factory()->create(['name' => 'Savepoint']);
    $active = Task::factory()->for($project)->active()->create(['title' => 'Current work']);
    WorkSession::factory()->open()->for($active)->create();
    $paused = Task::factory()->for($project)->paused()->create(['title' => 'Parked work']);
    ResumePoint::factory()->for($paused)->create(['where_stopped' => 'Parser handles quotes', 'next_step' => 'Cover escapes']);

    Livewire::actingAs($project->user)
        ->test('pages::dashboard')
        ->assertSee('Current work')
        ->assertSee('Savepoint')
        ->assertSee('Parked work')
        ->assertSee('Parser handles quotes')
        ->assertSee('Cover escapes');
});

test('the empty overview links to the projects', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::dashboard')
        ->assertSee('Nothing is active.')
        ->assertSeeHtml(route('projects.index'));
});

test('the overview of one developer does not show the tasks of another', function () {
    Task::factory()->paused()->create(['title' => 'Foreign work']);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::dashboard')
        ->assertDontSee('Foreign work')
        ->assertSee('No paused tasks.');
});

test('the git switch command is offered only for tasks with a branch', function () {
    $project = Project::factory()->create();
    Task::factory()->for($project)->paused()->create(['branch_name' => 'feature/parser']);
    Task::factory()->for($project)->paused()->create(['title' => 'No branch here']);

    $component = Livewire::actingAs($project->user)
        ->test('pages::dashboard')
        ->assertSee('git switch feature/parser')
        ->assertSee('No branch here');

    expect(substr_count($component->html(), 'git switch '))->toBe(1);
});

test('the switch command is null for a missing or unsafe branch name', function (?string $branch, ?string $expected) {
    $task = Task::factory()->make(['branch_name' => $branch]);

    expect($task->switchCommand())->toBe($expected);
})->with([
    'none' => [null, null],
    'plain' => ['main', 'git switch main'],
    'with slash' => ['feature/x-1', 'git switch feature/x-1'],
    'option like' => ['--detach', null],
    'with space' => ['a b', null],
    'shell characters' => ['a;rm', null],
]);

test('the active task is paused from the overview with a resume point', function () {
    $task = Task::factory()->active()->create();
    WorkSession::factory()->open()->for($task)->create();

    Livewire::actingAs($task->user)
        ->test('pages::dashboard')
        ->set('where_stopped', 'Parsing works')
        ->set('next_step', 'Write the validator')
        ->call('pause')
        ->assertHasNoErrors()
        ->assertSet('where_stopped', '')
        ->assertSee('Nothing is active.');

    expect($task->refresh()->status)->toBe(TaskStatus::Paused)
        ->and($task->latestResumePoint->next_step)->toBe('Write the validator');
});

test('pausing from the overview without a resume point is refused', function (string $where, string $next) {
    $task = Task::factory()->active()->create();
    WorkSession::factory()->open()->for($task)->create();

    Livewire::actingAs($task->user)
        ->test('pages::dashboard')
        ->set('where_stopped', $where)
        ->set('next_step', $next)
        ->call('pause')
        ->assertHasErrors();

    expect($task->refresh()->status)->toBe(TaskStatus::Active);
})->with([
    'no where' => ['', 'Next'],
    'no next' => ['Where', ''],
]);

test('pausing without an active task is reported', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::dashboard')
        ->call('pause')
        ->assertHasErrors('form');
});

test('an active and a paused task are completed from the overview', function () {
    $project = Project::factory()->create();
    $active = Task::factory()->for($project)->active()->create();
    WorkSession::factory()->open()->for($active)->create();
    $paused = Task::factory()->for($project)->paused()->create();

    Livewire::actingAs($project->user)
        ->test('pages::dashboard')
        ->call('complete', $active->id)
        ->call('complete', $paused->id);

    expect($active->refresh()->status)->toBe(TaskStatus::Completed)
        ->and($paused->refresh()->status)->toBe(TaskStatus::Completed)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(0);
});

test('a foreign user cannot complete a task from the overview', function () {
    $task = Task::factory()->paused()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::dashboard')
        ->call('complete', $task->id)
        ->assertForbidden();

    expect($task->refresh()->status)->toBe(TaskStatus::Paused);
});
