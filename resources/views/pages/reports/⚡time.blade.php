<?php

use App\Queries\TimeReportQuery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Time report')] class extends Component {
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $today = Date::now()->setTimezone(config('app.display_timezone'));

        $this->to = $today->format('Y-m-d');
        $this->from = $today->subDays(6)->format('Y-m-d');
    }

    /**
     * Null while the chosen range is invalid, so that the page shows the
     * validation message instead of a report.
     *
     * @return array{days: list<array{date: string, seconds: int, tasks: list<array{task: \App\Models\Task, seconds: int}>}>, tasks: list<array{task: \App\Models\Task, seconds: int}>}|null
     */
    #[Computed]
    public function report(): ?array
    {
        $validator = Validator::make(
            ['from' => $this->from, 'to' => $this->to],
            [
                'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            ],
        );

        if ($validator->fails() || CarbonImmutable::parse($this->from)->diffInDays(CarbonImmutable::parse($this->to)) > 365) {
            return null;
        }

        return app(TimeReportQuery::class)->handle(
            Auth::user(),
            CarbonImmutable::parse($this->from),
            CarbonImmutable::parse($this->to),
        );
    }

    public function duration(int $seconds): string
    {
        return sprintf('%d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-8">
    <flux:heading size="xl" level="1">{{ __('Time report') }}</flux:heading>

    <div class="flex items-end gap-3">
        <flux:input wire:model.live="from" type="date" :label="__('From')" data-test="from" />
        <flux:input wire:model.live="to" type="date" :label="__('To')" data-test="to" />
    </div>

    @if ($this->report === null)
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="__('Choose a valid range of at most one year, ending on or after the start date.')" />
    @else
        <div class="space-y-3" data-test="per-task">
            <flux:heading level="2">{{ __('Per task') }}</flux:heading>
            @forelse ($this->report['tasks'] as $row)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="total-{{ $row['task']->id }}">
                    <span class="flex-1">{{ $row['task']->title }}</span>
                    <span class="font-mono">{{ $this->duration($row['seconds']) }}</span>
                </div>
            @empty
                <flux:text>{{ __('No time recorded in this range.') }}</flux:text>
            @endforelse
        </div>

        <div class="space-y-3" data-test="per-day">
            <flux:heading level="2">{{ __('Per day') }}</flux:heading>
            @foreach ($this->report['days'] as $day)
                <div class="space-y-1 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="day-{{ $day['date'] }}">
                    <div class="flex items-center gap-3 font-medium">
                        <span class="flex-1">{{ $day['date'] }}</span>
                        <span class="font-mono">{{ $this->duration($day['seconds']) }}</span>
                    </div>
                    @foreach ($day['tasks'] as $row)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="flex-1">{{ $row['task']->title }}</span>
                            <span class="font-mono">{{ $this->duration($row['seconds']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endif
</section>
