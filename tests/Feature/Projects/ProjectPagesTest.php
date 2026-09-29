<?php

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from the project pages', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->get(route('projects.show', $project))->assertRedirect(route('login'));
});

test('the project list shows only the own projects', function () {
    $own = Project::factory()->create(['name' => 'Own project']);
    Project::factory()->create(['name' => 'Foreign project']);

    Livewire::actingAs($own->user)
        ->test('pages::projects.index')
        ->assertSee('Own project')
        ->assertDontSee('Foreign project');
});

test('a project is created from the list page', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.index')
        ->set('name', 'Savepoint')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('name', '');

    expect($user->projects()->pluck('name')->all())->toBe(['Savepoint']);
});

test('creating a project without a name is refused', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::projects.index')
        ->set('name', '')
        ->call('create')
        ->assertHasErrors(['name' => 'required']);

    expect(Project::query()->count())->toBe(0);
});

test('a project is renamed inline', function () {
    $project = Project::factory()->create(['name' => 'Old']);

    Livewire::actingAs($project->user)
        ->test('pages::projects.index')
        ->call('startRename', $project->id)
        ->assertSet('renamingName', 'Old')
        ->set('renamingName', 'New')
        ->call('rename')
        ->assertHasNoErrors()
        ->assertSet('renamingId', null);

    expect($project->refresh()->name)->toBe('New');
});

test('renaming a project to an empty name is refused', function () {
    $project = Project::factory()->create(['name' => 'Old']);

    Livewire::actingAs($project->user)
        ->test('pages::projects.index')
        ->call('startRename', $project->id)
        ->set('renamingName', '')
        ->call('rename')
        ->assertHasErrors(['renamingName' => 'required']);

    expect($project->refresh()->name)->toBe('Old');
});

test('a project is archived and moves to the archived section', function () {
    $project = Project::factory()->create(['name' => 'Legacy']);

    Livewire::actingAs($project->user)
        ->test('pages::projects.index')
        ->call('archive', $project->id)
        ->assertSee('Archived');

    expect($project->refresh()->archived_at)->not->toBeNull();
});

test('a project is deleted from the list', function () {
    $project = Project::factory()->create();

    Livewire::actingAs($project->user)
        ->test('pages::projects.index')
        ->call('delete', $project->id);

    expect(Project::query()->count())->toBe(0);
});

test('a project with an active task is not deleted', function () {
    $task = Task::factory()->active()->create();

    Livewire::actingAs($task->user)
        ->test('pages::projects.index')
        ->call('delete', $task->project_id);

    expect(Project::query()->count())->toBe(1);
});

test('a foreign user cannot rename, archive or delete a project', function () {
    $project = Project::factory()->create(['name' => 'Private']);
    $foreign = User::factory()->create();

    Livewire::actingAs($foreign)->test('pages::projects.index')
        ->call('startRename', $project->id)->assertForbidden();
    Livewire::actingAs($foreign)->test('pages::projects.index')
        ->call('archive', $project->id)->assertForbidden();
    Livewire::actingAs($foreign)->test('pages::projects.index')
        ->call('delete', $project->id)->assertForbidden();

    expect($project->refresh()->name)->toBe('Private')
        ->and($project->archived_at)->toBeNull();
});

test('the project page lists its tasks with their status', function () {
    $project = Project::factory()->create();
    Task::factory()->for($project)->paused()->create(['title' => 'Paused work']);

    $this->actingAs($project->user)->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Paused work')
        ->assertSee(TaskStatus::Paused->label());
});

test('a foreign user cannot open a project page', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('projects.show', $project))->assertForbidden();
});

test('a task is deleted from the project page', function () {
    $task = Task::factory()->create();

    Livewire::actingAs($task->user)
        ->test('pages::projects.show', ['project' => $task->project])
        ->call('delete', $task->id);

    expect(Task::query()->count())->toBe(0);
});

test('an active task is not deleted from the project page', function () {
    $task = Task::factory()->active()->create();

    Livewire::actingAs($task->user)
        ->test('pages::projects.show', ['project' => $task->project])
        ->call('delete', $task->id);

    expect(Task::query()->count())->toBe(1);
});

test('a foreign user cannot delete a task through the page', function () {
    $task = Task::factory()->create();
    $foreign = User::factory()->create();

    Livewire::actingAs($foreign)
        ->test('pages::projects.show', ['project' => $task->project])
        ->assertForbidden();

    expect(Task::query()->count())->toBe(1);
});
