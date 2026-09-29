<?php

use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\RenameProject;
use App\Exceptions\ActiveTaskCannotBeDeleted;
use App\Models\Project;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component {
    public string $name = '';

    public ?int $renamingId = null;
    public string $renamingName = '';

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function projects(): Collection
    {
        return Auth::user()->projects()->whereNull('archived_at')->withCount('tasks')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Project>
     */
    #[Computed]
    public function archivedProjects(): Collection
    {
        return Auth::user()->projects()->whereNotNull('archived_at')->withCount('tasks')->orderBy('name')->get();
    }

    public function create(CreateProject $action): void
    {
        $validated = $this->validate(['name' => ['required', 'string', 'max:255']]);

        $action->handle(Auth::user(), $validated['name']);

        $this->reset('name');
        Flux::toast(variant: 'success', text: __('Project created.'));
    }

    public function startRename(int $id): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $this->renamingId = $project->id;
        $this->renamingName = $project->name;
    }

    public function rename(RenameProject $action): void
    {
        $project = Project::findOrFail($this->renamingId);
        $this->authorize('update', $project);

        $validated = $this->validate(['renamingName' => ['required', 'string', 'max:255']]);

        $action->handle($project, $validated['renamingName']);

        $this->reset('renamingId', 'renamingName');
        Flux::toast(variant: 'success', text: __('Project renamed.'));
    }

    public function cancelRename(): void
    {
        $this->reset('renamingId', 'renamingName');
        $this->resetErrorBag();
    }

    public function archive(int $id, ArchiveProject $action): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('update', $project);

        $action->handle($project);

        Flux::toast(variant: 'success', text: __('Project archived.'));
    }

    public function delete(int $id, DeleteProject $action): void
    {
        $project = Project::findOrFail($id);
        $this->authorize('delete', $project);

        try {
            $action->handle($project);
        } catch (ActiveTaskCannotBeDeleted $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Project deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-8">
    <flux:heading size="xl" level="1">{{ __('Projects') }}</flux:heading>

    <form wire:submit="create" class="flex items-end gap-3">
        <div class="flex-1">
            <flux:input wire:model="name" :label="__('New project')" required data-test="project-name" />
        </div>
        <flux:button type="submit" variant="primary" icon="plus" data-test="create-project-button">{{ __('Create') }}</flux:button>
    </form>

    <div class="space-y-2">
        @forelse ($this->projects as $project)
            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="project-{{ $project->id }}">
                @if ($renamingId === $project->id)
                    <form wire:submit="rename" class="flex flex-1 items-start gap-2">
                        <div class="flex-1">
                            <flux:input wire:model="renamingName" :aria-label="__('Project name')" required autofocus data-test="rename-input" />
                        </div>
                        <flux:button type="submit" size="sm" variant="primary" data-test="rename-save">{{ __('Save') }}</flux:button>
                        <flux:button type="button" size="sm" wire:click="cancelRename">{{ __('Cancel') }}</flux:button>
                    </form>
                @else
                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex-1 font-medium hover:underline">
                        {{ $project->name }}
                    </a>
                    <flux:badge>{{ trans_choice(':count task|:count tasks', $project->tasks_count) }}</flux:badge>
                    <flux:button size="sm" icon="pencil" wire:click="startRename({{ $project->id }})" :aria-label="__('Rename')" />
                    <flux:button size="sm" icon="archive-box" wire:click="archive({{ $project->id }})" :aria-label="__('Archive')" />
                    <flux:button size="sm" icon="trash" variant="danger" wire:click="delete({{ $project->id }})" wire:confirm="{{ __('Delete this project with all its tasks?') }}" :aria-label="__('Delete')" />
                @endif
            </div>
        @empty
            <flux:text>{{ __('No projects yet.') }}</flux:text>
        @endforelse
    </div>

    @if ($this->archivedProjects->isNotEmpty())
        <div class="space-y-2">
            <flux:heading level="2">{{ __('Archived') }}</flux:heading>
            @foreach ($this->archivedProjects as $project)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="archived-{{ $project->id }}">
                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="flex-1 hover:underline">{{ $project->name }}</a>
                    <flux:badge>{{ trans_choice(':count task|:count tasks', $project->tasks_count) }}</flux:badge>
                    <flux:button size="sm" icon="trash" variant="danger" wire:click="delete({{ $project->id }})" wire:confirm="{{ __('Delete this project with all its tasks?') }}" :aria-label="__('Delete')" />
                </div>
            @endforeach
        </div>
    @endif
</section>
