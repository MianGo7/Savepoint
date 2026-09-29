<?php

use App\Actions\Tasks\CreateTask;
use App\Actions\Tasks\UpdateTask;
use App\Exceptions\ProjectArchived;
use App\Models\Project;
use App\Models\Task;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Task')] class extends Component {
    public ?Project $project = null;
    public ?Task $task = null;

    public string $title = '';
    public string $description = '';
    public ?int $estimate_minutes = null;
    public string $branch_name = '';

    /**
     * Serves both routes: creating in a project, and editing an existing task.
     */
    public function mount(?Project $project = null, ?Task $task = null): void
    {
        if ($task?->exists) {
            $this->authorize('update', $task);

            $this->task = $task;
            $this->project = $task->project;
            $this->title = $task->title;
            $this->description = $task->description ?? '';
            $this->estimate_minutes = $task->estimate_minutes;
            $this->branch_name = $task->branch_name ?? '';

            return;
        }

        $this->authorize('create', [Task::class, $project]);

        $this->project = $project;
    }

    public function save(CreateTask $create, UpdateTask $update): void
    {
        // A branch name ends up in a copyable `git switch` command (FR10), so
        // only characters that are valid in a ref and harmless in a shell pass.
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'estimate_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'branch_name' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9._\/-]+$/'],
        ]);

        try {
            if ($this->task !== null) {
                $this->authorize('update', $this->task);
                $update->handle($this->task, $validated);
            } else {
                $this->authorize('create', [Task::class, $this->project]);
                $create->handle($this->project, $validated);
            }
        } catch (ProjectArchived $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->redirectRoute('projects.show', $this->project, navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <flux:heading size="xl" level="1">
        {{ $task ? __('Edit task') : __('New task in :project', ['project' => $project->name]) }}
    </flux:heading>

    @error('form')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="title" :label="__('Title')" required autofocus data-test="task-title" />
        <flux:textarea wire:model="description" :label="__('Description')" rows="4" />
        <flux:input wire:model="estimate_minutes" :label="__('Estimate in minutes')" type="number" min="1" />
        <flux:input wire:model="branch_name" :label="__('Git branch name')" placeholder="feature/example" />

        <div class="flex items-center gap-3">
            <flux:button variant="primary" type="submit" data-test="save-task-button">{{ __('Save') }}</flux:button>
            <flux:button :href="route('projects.show', $project)" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
