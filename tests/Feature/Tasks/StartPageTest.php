<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $task = Task::factory()->create();

    $this->get(route('tasks.start', $task))->assertRedirect(route('login'));
});

test('a foreign user cannot open the start page of a task', function () {
    $task = Task::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('tasks.start', $task))->assertForbidden();
});

test('the owner can open the start page', function () {
    $task = Task::factory()->create();

    $this->actingAs($task->user)->get(route('tasks.start', $task))->assertOk();
});

test('a task is started directly when nothing is active', function () {
    $task = Task::factory()->create();

    Livewire::actingAs($task->user)
        ->test('pages::tasks.start', ['task' => $task])
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('dashboard');

    expect($task->refresh()->status)->toBe(TaskStatus::Active)
        ->and(WorkSession::query()->whereNull('ended_at')->count())->toBe(1);
});

test('a paused task shows its latest resume point and is resumed', function () {
    $task = Task::factory()->paused()->create();
    ResumePoint::factory()->for($task)->create([
        'where_stopped' => 'Parser handles quotes',
        'next_step' => 'Cover escaped quotes',
    ]);

    Livewire::actingAs($task->user)
        ->test('pages::tasks.start', ['task' => $task])
        ->assertSee('Parser handles quotes')
        ->assertSee('Cover escaped quotes')
        ->call('submit')
        ->assertRedirectToRoute('dashboard');

    expect($task->refresh()->status)->toBe(TaskStatus::Active);
});

test('another active task asks for a resume point and the switch completes', function () {
    $project = Project::factory()->create();
    $active = Task::factory()->for($project)->active()->create();
    WorkSession::factory()->open()->for($active)->create();
    $target = Task::factory()->for($project)->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.start', ['task' => $target])
        ->assertSee($active->title)
        ->set('where_stopped', 'Parsing works')
        ->set('next_step', 'Write the validator')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('dashboard');

    expect($active->refresh()->status)->toBe(TaskStatus::Paused)
        ->and($target->refresh()->status)->toBe(TaskStatus::Active)
        ->and($active->latestResumePoint->next_step)->toBe('Write the validator');
});

test('the switch is refused without both resume point fields', function (string $where, string $next) {
    $project = Project::factory()->create();
    $active = Task::factory()->for($project)->active()->create();
    WorkSession::factory()->open()->for($active)->create();
    $target = Task::factory()->for($project)->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.start', ['task' => $target])
        ->set('where_stopped', $where)
        ->set('next_step', $next)
        ->call('submit')
        ->assertHasErrors()
        ->assertNoRedirect();

    expect($active->refresh()->status)->toBe(TaskStatus::Active)
        ->and($target->refresh()->status)->toBe(TaskStatus::Todo);
})->with([
    'no where' => ['', 'Next'],
    'no next' => ['Where', ''],
]);

test('a rejected action is reported on the page', function () {
    $task = Task::factory()->completed()->create();

    Livewire::actingAs($task->user)
        ->test('pages::tasks.start', ['task' => $task])
        ->call('submit')
        ->assertHasErrors('form')
        ->assertNoRedirect();
});

test('a task in an archived project is reported on the page', function () {
    $task = Task::factory()->for(Project::factory()->archived())->create();

    Livewire::actingAs($task->user)
        ->test('pages::tasks.start', ['task' => $task])
        ->call('submit')
        ->assertHasErrors('form');

    expect($task->refresh()->status)->toBe(TaskStatus::Todo);
});
