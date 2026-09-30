<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResumePoint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Fills the database of the first user with a week of plausible work, so that
 * every page can be photographed for the report. Run it on purpose with
 * `php artisan db:seed --class=DemoDataSeeder`; it is not part of
 * DatabaseSeeder and refuses to touch a user who already has projects.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->orderBy('id')->first() ?? User::factory()->create([
            'name' => 'Demo Developer',
            'email' => 'demo@example.test',
        ]);

        if ($user->projects()->exists()) {
            $this->command->warn('The user already has projects, nothing was seeded.');

            return;
        }

        $savepoint = $this->project($user, 'Savepoint');
        $website = $this->project($user, 'Website Relaunch');
        $thesis = $this->project($user, 'Thesis Notes');
        $legacy = $this->project($user, 'Legacy Import', archived: true);

        $history = $this->task($savepoint, 'Add resume point history to the start page', TaskStatus::Completed);
        $timeReport = $this->task($savepoint, 'Write the time report query', TaskStatus::Paused, 'feature/time-report');
        $switchTest = $this->task($savepoint, 'Cover the switch with a component test', TaskStatus::Active, 'feature/switch-test');
        $screenshots = $this->task($savepoint, 'Take screenshots for the report', TaskStatus::Todo);
        $this->task($savepoint, 'Draft the evaluation chapter', TaskStatus::Todo);

        $blog = $this->task($website, 'Migrate the blog posts', TaskStatus::Paused, 'content/blog-migration');
        $contact = $this->task($website, 'Fix the contact form validation', TaskStatus::Paused);
        $this->task($website, 'Design the footer', TaskStatus::Todo);

        $sources = $this->task($thesis, 'Collect the sources', TaskStatus::Completed);
        $this->task($thesis, 'Summarise the chapter on design goals', TaskStatus::Todo);

        $import = $this->task($legacy, 'Import the old task list', TaskStatus::Completed);

        $this->session($sources, 6, '09:30', 6, '11:00');
        $this->session($history, 6, '14:00', 6, '16:15', 'The latest resume point is shown on the start page', 'List the earlier resume points below it');
        $this->session($timeReport, 5, '09:00', 5, '10:30', 'The query groups sessions per task', 'Split sessions at midnight');
        $this->session($import, 5, '13:00', 5, '14:00');
        $this->session($timeReport, 4, '10:00', 4, '12:15', 'Midnight splitting works for UTC', 'Handle the display time zone');
        $this->session($blog, 4, '14:00', 4, '15:30', 'Twelve of thirty posts are migrated', 'Convert the image links');
        $this->session($contact, 4, '16:00', 4, '17:00', 'The e-mail field is validated', 'Show the error next to the message field');
        $this->session($history, 3, '10:00', 3, '11:00');
        $this->session($timeReport, 3, '23:10', 2, '00:40', 'Days with a summer time change are handled', 'Wire the query into the page');
        $this->session($blog, 2, '09:00', 2, '10:45', 'All posts are migrated, images are not', 'Convert the image links and check the redirects');
        $this->session($timeReport, 1, '14:00', 1, '16:00', 'The page shows both tables', 'Add the tests for a range over a year');
        $this->session($switchTest, 1, '09:00', 1, '10:00', 'The happy path of the switch is covered', 'Test the rollback when the start fails');

        $history->forceFill(['completed_at' => $this->at(3, '11:00')])->save();
        $sources->forceFill(['completed_at' => $this->at(6, '11:00')])->save();
        $import->forceFill(['completed_at' => $this->at(5, '14:00')])->save();

        $open = CarbonImmutable::now()->subMinutes(47);
        WorkSession::factory()->for($switchTest)->create(['started_at' => $open, 'ended_at' => null]);

        $this->command->info('Demo data seeded. Pages for the screenshots:');
        $this->command->line('  /dashboard');
        $this->command->line("  /tasks/{$screenshots->id}/start (switch form, another task is active)");
        $this->command->line("  /tasks/{$timeReport->id}/start (paused task with four resume points)");
        $this->command->line("  /projects/{$savepoint->id} (all four states)");
        $this->command->line('  /projects');
        $this->command->line("  /tasks/{$switchTest->id}/edit (branch name filled in)");
        $this->command->line('  /reports/time');
    }

    private function project(User $user, string $name, bool $archived = false): Project
    {
        return Project::factory()->for($user)->create([
            'name' => $name,
            'archived_at' => $archived ? CarbonImmutable::now()->subDays(2) : null,
            'created_at' => CarbonImmutable::now()->subDays(8),
        ]);
    }

    private function task(Project $project, string $title, TaskStatus $status, ?string $branch = null): Task
    {
        return Task::factory()->for($project)->create([
            'title' => $title,
            'status' => $status,
            'branch_name' => $branch,
            'estimate_minutes' => 120,
            'created_at' => CarbonImmutable::now()->subDays(8),
        ]);
    }

    /**
     * Days are counted back from today in the display time zone, and stored in
     * UTC like every other timestamp.
     */
    private function at(int $daysAgo, string $time): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.display_timezone'))
            ->subDays($daysAgo)
            ->setTimeFromTimeString($time)
            ->utc();
    }

    /**
     * A closed session; with a resume point when the task was paused at its end.
     */
    private function session(
        Task $task,
        int $startDay,
        string $start,
        int $endDay,
        string $end,
        ?string $whereStopped = null,
        ?string $nextStep = null,
    ): WorkSession {
        $session = WorkSession::factory()->for($task)->create([
            'started_at' => $this->at($startDay, $start),
            'ended_at' => $this->at($endDay, $end),
        ]);

        if ($whereStopped !== null && $nextStep !== null) {
            ResumePoint::factory()->for($task)->create([
                'work_session_id' => $session->id,
                'where_stopped' => $whereStopped,
                'next_step' => $nextStep,
                'created_at' => $session->ended_at,
                'updated_at' => $session->ended_at,
            ]);
        }

        return $session;
    }
}
