<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from the task form', function () {
    $project = Project::factory()->create();

    $this->get(route('tasks.create', $project))->assertRedirect(route('login'));
});

test('a task is created with all its fields', function () {
    $project = Project::factory()->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.form', ['project' => $project])
        ->set('title', 'Write parser')
        ->set('description', 'Handle quotes')
        ->set('estimate_minutes', 90)
        ->set('branch_name', 'feature/parser')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('projects.show', $project);

    $task = Task::query()->sole();
    expect($task->title)->toBe('Write parser')
        ->and($task->estimate_minutes)->toBe(90)
        ->and($task->branch_name)->toBe('feature/parser')
        ->and($task->project_id)->toBe($project->id);
});

test('the optional fields may stay empty', function () {
    $project = Project::factory()->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.form', ['project' => $project])
        ->set('title', 'Only a title')
        ->call('save')
        ->assertHasNoErrors();

    expect(Task::query()->sole()->branch_name)->toBeNull();
});

test('invalid input is refused', function (string $field, mixed $value, string $rule) {
    $project = Project::factory()->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.form', ['project' => $project])
        ->set('title', 'Valid title')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field => $rule]);

    expect(Task::query()->count())->toBe(0);
})->with([
    'missing title' => ['title', '', 'required'],
    'estimate below one' => ['estimate_minutes', 0, 'min'],
    'branch with a space' => ['branch_name', 'feature x', 'regex'],
    'branch with shell characters' => ['branch_name', 'a;rm -rf', 'regex'],
]);

test('a foreign user cannot create a task in the project of another developer', function () {
    $project = Project::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::tasks.form', ['project' => $project])
        ->assertForbidden();
});

test('a task cannot be created in an archived project', function () {
    $project = Project::factory()->archived()->create();

    Livewire::actingAs($project->user)
        ->test('pages::tasks.form', ['project' => $project])
        ->set('title', 'Too late')
        ->call('save')
        ->assertHasErrors('form');

    expect(Task::query()->count())->toBe(0);
});

test('the edit form is filled with the task and saves changes', function () {
    $task = Task::factory()->create(['title' => 'Old', 'branch_name' => 'old-branch']);

    Livewire::actingAs($task->user)
        ->test('pages::tasks.form', ['task' => $task])
        ->assertSet('title', 'Old')
        ->assertSet('branch_name', 'old-branch')
        ->set('title', 'New')
        ->set('branch_name', '')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('projects.show', $task->project);

    expect($task->refresh()->title)->toBe('New')
        ->and($task->branch_name)->toBeNull();
});

test('a foreign user cannot edit a task', function () {
    $task = Task::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test('pages::tasks.form', ['task' => $task])
        ->assertForbidden();
});

test('the routes of the form render for the owner', function () {
    $task = Task::factory()->create();

    $this->actingAs($task->user)->get(route('tasks.create', $task->project))->assertOk();
    $this->actingAs($task->user)->get(route('tasks.edit', $task))->assertOk();
});
