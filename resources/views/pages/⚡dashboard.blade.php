<?php

use App\Actions\Tasks\CompleteTask;
use App\Actions\Tasks\PauseTask;
use App\Exceptions\ResumePointRequired;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\Task;
use App\Queries\OverviewQuery;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Where was I')] class extends Component {
    public string $where_stopped = '';
    public string $next_step = '';

    #[Computed]
    public function activeTask(): ?Task
    {
        return app(OverviewQuery::class)->active(Auth::user());
    }

    /**
     * @return Collection<int, Task>
     */
    #[Computed]
    public function pausedTasks(): Collection
    {
        return app(OverviewQuery::class)->paused(Auth::user());
    }

    /**
     * Pauses the active task. The task is looked up again, because the page may
     * be stale, and it is authorised like every other mutation.
     */
    public function pause(PauseTask $action): void
    {
        $task = $this->activeTask;

        if ($task === null) {
            $this->addError('form', __('There is no active task to pause.'));

            return;
        }

        $this->authorize('update', $task);

        $validated = $this->validate([
            'where_stopped' => ['required', 'string', 'max:1000'],
            'next_step' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $action->handle($task, $validated['where_stopped'], $validated['next_step']);
        } catch (TaskTransitionNotAllowed|ResumePointRequired $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->reset('where_stopped', 'next_step');
        $this->resetErrorBag();
        unset($this->activeTask, $this->pausedTasks);
        Flux::toast(variant: 'success', text: __('Task paused.'));
    }

    public function complete(int $id, CompleteTask $action): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        try {
            $action->handle($task);
        } catch (TaskTransitionNotAllowed $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->activeTask, $this->pausedTasks);
        Flux::toast(variant: 'success', text: __('Task completed.'));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-8">
    <flux:heading size="xl" level="1">{{ __('Where was I') }}</flux:heading>

    @error('form')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    <div class="space-y-3" data-test="active-task">
        <flux:heading level="2">{{ __('Active') }}</flux:heading>

        @if ($this->activeTask)
            @php($active = $this->activeTask)
            <div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <div class="font-medium">{{ $active->title }}</div>
                        <flux:text size="sm">
                            {{ $active->project->name }}
                            @if ($active->openWorkSession)
                                &middot; {{ __('since :time', ['time' => $active->openWorkSession->started_at->diffForHumans()]) }}
                            @endif
                        </flux:text>
                    </div>
                    <flux:button size="sm" icon="check" wire:click="complete({{ $active->id }})" data-test="complete-active">
                        {{ __('Complete') }}
                    </flux:button>
                </div>

                @if ($active->switchCommand())
                    <flux:input :value="$active->switchCommand()" readonly copyable :label="__('Branch')" />
                @endif

                <form wire:submit="pause" class="space-y-3">
                    <flux:input wire:model="where_stopped" :label="__('Where did the work stop?')" required data-test="where-stopped" />
                    <flux:input wire:model="next_step" :label="__('What is the next step?')" required data-test="next-step" />
                    <flux:button type="submit" icon="pause" data-test="pause-button">{{ __('Pause') }}</flux:button>
                </form>
            </div>
        @else
            <flux:text>
                {{ __('Nothing is active.') }}
                <flux:link :href="route('projects.index')" wire:navigate data-test="projects-link">{{ __('Start a task from a project.') }}</flux:link>
            </flux:text>
        @endif
    </div>

    <div class="space-y-3" data-test="paused-tasks">
        <flux:heading level="2">{{ __('Paused') }}</flux:heading>

        @forelse ($this->pausedTasks as $task)
            <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="paused-{{ $task->id }}">
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <div class="font-medium">{{ $task->title }}</div>
                        <flux:text size="sm">{{ $task->project->name }}</flux:text>
                    </div>
                    <flux:button size="sm" icon="play" :href="route('tasks.start', $task)" wire:navigate>{{ __('Resume') }}</flux:button>
                    <flux:button size="sm" icon="check" wire:click="complete({{ $task->id }})" :aria-label="__('Complete')" />
                </div>

                @if ($task->latestResumePoint)
                    <div class="text-sm">
                        <div>{{ $task->latestResumePoint->where_stopped }}</div>
                        <flux:text size="sm">{{ __('Next step: :step', ['step' => $task->latestResumePoint->next_step]) }}</flux:text>
                        <flux:text size="sm">{{ $task->latestResumePoint->created_at->diffForHumans() }}</flux:text>
                    </div>
                @endif

                @if ($task->switchCommand())
                    <flux:input :value="$task->switchCommand()" readonly copyable :aria-label="__('Branch')" />
                @endif
            </div>
        @empty
            <flux:text>{{ __('No paused tasks.') }}</flux:text>
        @endforelse
    </div>
</section>
