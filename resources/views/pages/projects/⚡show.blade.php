<?php

use App\Actions\Tasks\DeleteTask;
use App\Enums\TaskStatus;
use App\Exceptions\ActiveTaskCannotBeDeleted;
use App\Models\Project;
use App\Models\Task;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Project')] class extends Component {
    public Project $project;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);

        $this->project = $project;
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function tasks(): Collection
    {
        return $this->project->tasks()->orderBy('created_at')->orderBy('id')->get();
    }

    public function delete(int $id, DeleteTask $action): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);

        try {
            $action->handle($task);
        } catch (ActiveTaskCannotBeDeleted $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->tasks);
        Flux::toast(variant: 'success', text: __('Task deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div class="flex items-center gap-3">
        <flux:heading size="xl" level="1" class="flex-1">{{ $project->name }}</flux:heading>
        @if ($project->archived_at)
            <flux:badge>{{ __('Archived') }}</flux:badge>
        @else
            <flux:button variant="primary" icon="plus" :href="route('tasks.create', $project)" wire:navigate data-test="new-task-link">
                {{ __('New task') }}
            </flux:button>
        @endif
    </div>

    <div class="space-y-2">
        @forelse ($this->tasks as $task)
            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="task-{{ $task->id }}">
                <div class="flex-1">
                    <div class="font-medium">{{ $task->title }}</div>
                    @if ($task->branch_name)
                        <flux:text size="sm">{{ $task->branch_name }}</flux:text>
                    @endif
                </div>
                <flux:badge>{{ $task->status->label() }}</flux:badge>
                @if (in_array($task->status, [TaskStatus::Todo, TaskStatus::Paused], true))
                    <flux:button size="sm" icon="play" :href="route('tasks.start', $task)" wire:navigate>
                        {{ $task->status === TaskStatus::Paused ? __('Resume') : __('Start') }}
                    </flux:button>
                @endif
                <flux:button size="sm" icon="pencil" :href="route('tasks.edit', $task)" wire:navigate :aria-label="__('Edit')" />
                <flux:button size="sm" icon="trash" variant="danger" wire:click="delete({{ $task->id }})" wire:confirm="{{ __('Delete this task?') }}" :aria-label="__('Delete')" />
            </div>
        @empty
            <flux:text>{{ __('No tasks yet.') }}</flux:text>
        @endforelse
    </div>

    <flux:link :href="route('projects.index')" wire:navigate>{{ __('Back to projects') }}</flux:link>
</section>
