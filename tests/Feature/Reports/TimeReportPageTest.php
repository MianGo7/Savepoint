<?php

use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Livewire\Livewire;

test('guests are redirected from the time report', function () {
    $this->get(route('reports.time'))->assertRedirect(route('login'));
});

test('the report defaults to the last seven days', function () {
    $this->travelTo('2026-09-29 12:00:00');

    Livewire::actingAs(User::factory()->create())
        ->test('pages::reports.time')
        ->assertSet('from', '2026-09-23')
        ->assertSet('to', '2026-09-29')
        ->assertSee('No time recorded in this range.');
});

test('the report shows time per task and per day', function () {
    $this->travelTo('2026-09-29 12:00:00');
    $task = Task::factory()->create(['title' => 'Write parser']);
    WorkSession::factory()->for($task)->create(['started_at' => '2026-09-28 09:00:00', 'ended_at' => '2026-09-28 10:30:00']);

    Livewire::actingAs($task->user)
        ->test('pages::reports.time')
        ->assertSee('Write parser')
        ->assertSee('2026-09-28')
        ->assertSee('1:30');
});

test('the time of another developer is not shown', function () {
    $this->travelTo('2026-09-29 12:00:00');
    $task = Task::factory()->create(['title' => 'Foreign work']);
    WorkSession::factory()->for($task)->create(['started_at' => '2026-09-28 09:00:00', 'ended_at' => '2026-09-28 10:00:00']);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::reports.time')
        ->assertDontSee('Foreign work');
});

test('the range can be changed', function () {
    $this->travelTo('2026-09-29 12:00:00');
    $task = Task::factory()->create(['title' => 'Old work']);
    WorkSession::factory()->for($task)->create(['started_at' => '2026-08-10 09:00:00', 'ended_at' => '2026-08-10 10:00:00']);

    Livewire::actingAs($task->user)
        ->test('pages::reports.time')
        ->assertDontSee('Old work')
        ->set('from', '2026-08-01')
        ->set('to', '2026-08-31')
        ->assertSee('Old work');
});

test('an invalid range shows a message instead of a report', function (string $from, string $to) {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::reports.time')
        ->set('from', $from)
        ->set('to', $to)
        ->assertSee('Choose a valid range');
})->with([
    'end before start' => ['2026-09-29', '2026-09-01'],
    'not a date' => ['yesterday', '2026-09-01'],
    'longer than a year' => ['2024-01-01', '2026-09-01'],
]);
