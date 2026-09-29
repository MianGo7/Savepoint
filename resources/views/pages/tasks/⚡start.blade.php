<?php

use App\Actions\Tasks\ResumeTask;
use App\Actions\Tasks\StartTask;
use App\Actions\Tasks\SwitchTask;
use App\Enums\TaskStatus;
use App\Exceptions\ProjectArchived;
use App\Exceptions\ResumePointRequired;
use App\Exceptions\SessionAlreadyOpen;
use App\Exceptions\TaskTransitionNotAllowed;
use App\Models\ResumePoint;
use App\Models\Task;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Start task')] class extends Component {
    public Task $task;

    public string $where_stopped = '';
    public string $next_step = '';

    public function mount(Task $task): void
    {
        $this->authorize('update', $task);

        $this->task = $task;
    }

    /**
     * The task the developer is working on now, which has to be paused first.
     */
    #[Computed]
    public function activeTask(): ?Task
    {
        return Task::query()
            ->where('user_id', Auth::id())
            ->where('status', TaskStatus::Active)
            ->first();
    }

    /**
     * Shown before a paused task is resumed (FR6).
     */
    #[Computed]
    public function latestResumePoint(): ?ResumePoint
    {
        return $this->task->latestResumePoint;
    }

    /**
     * The resume points before the latest one, newest first (FR6). The order is
     * by id, like the one that defines the latest resume point of a task.
     *
     * @return Collection<int, ResumePoint>
     */
    #[Computed]
    public function earlierResumePoints(): Collection
    {
        return $this->task->resumePoints()->orderByDesc('id')->get()->slice(1)->values();
    }

    /**
     * Starts the task directly, or switches to it when another task is active.
     * The active task is looked up again here, because it may have changed
     * since the page was rendered.
     */
    public function submit(StartTask $start, ResumeTask $resume, SwitchTask $switch): void
    {
        $this->authorize('update', $this->task);
        $this->resetErrorBag();

        try {
            if ($this->activeTask !== null) {
                $validated = $this->validate([
                    'where_stopped' => ['required', 'string', 'max:1000'],
                    'next_step' => ['required', 'string', 'max:1000'],
                ]);

                $switch->handle($this->task, $validated['where_stopped'], $validated['next_step']);
            } elseif ($this->task->status === TaskStatus::Paused) {
                $resume->handle($this->task);
            } else {
                $start->handle($this->task);
            }
        } catch (TaskTransitionNotAllowed|SessionAlreadyOpen|ProjectArchived|ResumePointRequired $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: __('Working on :title.', ['title' => $this->task->title]));

        $this->redirectRoute('dashboard', navigate: true);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <flux:heading size="xl" level="1">{{ $task->title }}</flux:heading>

    @error('form')
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
    @enderror

    @if ($this->latestResumePoint)
        <flux:callout icon="bookmark" :heading="__('Where you left off')" data-test="latest-resume-point">
            <flux:callout.text>{{ $this->latestResumePoint->where_stopped }}</flux:callout.text>
            <flux:callout.text>{{ __('Next step: :step', ['step' => $this->latestResumePoint->next_step]) }}</flux:callout.text>
        </flux:callout>
    @endif

    @if ($this->earlierResumePoints->isNotEmpty())
        <details class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" data-test="resume-point-history">
            <summary class="cursor-pointer font-medium">
                {{ trans_choice('Earlier resume point (:count)|Earlier resume points (:count)', $this->earlierResumePoints->count()) }}
            </summary>
            <ul class="mt-3 space-y-3">
                @foreach ($this->earlierResumePoints as $point)
                    <li class="text-sm" wire:key="history-{{ $point->id }}">
                        <div>{{ $point->where_stopped }}</div>
                        <flux:text size="sm">{{ __('Next step: :step', ['step' => $point->next_step]) }}</flux:text>
                        <flux:text size="sm">{{ $point->created_at->diffForHumans() }}</flux:text>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif

    <form wire:submit="submit" class="space-y-6">
        @if ($this->activeTask)
            <flux:heading level="2">
                {{ __('Pause ":title" first', ['title' => $this->activeTask->title]) }}
            </flux:heading>

            <flux:input
                wire:model="where_stopped"
                :label="__('Where did the work stop?')"
                required
                autofocus
                data-test="where-stopped"
            />
            <flux:input
                wire:model="next_step"
                :label="__('What is the next step?')"
                required
                data-test="next-step"
            />
        @endif

        <flux:button variant="primary" type="submit" data-test="start-task-button" :autofocus="! $this->activeTask">
            {{ $this->activeTask ? __('Pause and switch') : ($task->status === \App\Enums\TaskStatus::Paused ? __('Resume') : __('Start')) }}
        </flux:button>
    </form>
</section>
