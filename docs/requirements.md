# Requirements

This document lists the functional and nonfunctional requirements of the
system and traces each one to the code and the tests that satisfy it. It is
the working basis for Section 2.3 of the report and for the verification in
Section 5.2. A requirement is only marked as done when it is implemented and
covered by a test or, for user interface behaviour, by a screenshot.

The requirement set is a first draft derived from the chosen concept. It is
revised when the problem scenarios and the visionary scenario are written,
and every change is recorded in `dev-journal.md`.

Status values: open, in progress, done, partly met, deferred. A deferred
requirement is specified on purpose but not implemented in the prototype, and
the reason is stated next to it. A requirement is partly met when it holds for
some cases only, and the limit is stated next to it.

## Functional requirements

| ID | Requirement | Individual feature | Status | Implemented in | Verified by |
| --- | --- | --- | --- | --- | --- |
| FR1 | The developer can create, rename and archive projects that group tasks. | no | done | `CreateProject`, `RenameProject`, `ArchiveProject`, `DeleteProject`, `pages::projects.index` | `tests/Feature/Actions/ProjectAndTaskActionsTest.php`, `tests/Feature/Projects/ProjectPagesTest.php` |
| FR2 | The developer can create, edit and delete tasks within a project, each with a title, an optional description, an optional estimate and an optional Git branch name. | no | done | `CreateTask`, `UpdateTask`, `DeleteTask`, `pages::projects.show`, `pages::tasks.form` | `tests/Feature/Actions/ProjectAndTaskActionsTest.php`, `tests/Feature/Projects/ProjectPagesTest.php`, `tests/Feature/Tasks/TaskFormPageTest.php` |
| FR3 | The developer can start a task, which opens a work session. At most one task per developer is active at any time. | no | done | `StartTask`, `OpenWorkSession`, `pages::tasks.start` | `tests/Feature/Actions/TaskLifecycleTest.php`, `tests/Feature/Tasks/StartPageTest.php` |
| FR4 | The developer can pause the active task only by recording a resume point that states where the work stopped and what the next step is. Pausing closes the open work session. | yes | done | `PauseTask`, `pages::dashboard`, `pages::tasks.start` | `tests/Feature/Actions/TaskLifecycleTest.php`, `tests/Feature/Tasks/StartPageTest.php`, `tests/Feature/DashboardOverviewTest.php` |
| FR5 | Starting a task while another task is active first asks for the resume point of the active task and then starts the new one in a single flow. | yes | done | `SwitchTask`, `resources/views/pages/tasks/⚡start.blade.php`, linked from the project page | `tests/Feature/Actions/SwitchTaskTest.php`, `tests/Feature/Tasks/StartPageTest.php` |
| FR6 | Resuming a paused task shows its most recent resume point before the new work session begins. Earlier resume points remain available as a history. | yes | done | `ResumeTask`, `pages::tasks.start` shows the latest resume point | `tests/Feature/Actions/TaskLifecycleTest.php`, `tests/Feature/Tasks/StartPageTest.php` |
| FR7 | The developer can complete a task from the active or the paused state. Completing closes any open work session. | no | done | `CompleteTask`, `pages::dashboard` | `tests/Feature/Actions/TaskLifecycleTest.php`, `tests/Feature/DashboardOverviewTest.php` |
| FR8 | An overview lists the active task and all paused tasks, ordered by last activity, each with its latest resume point. | yes | done | `OverviewQuery`, `pages::dashboard` | `tests/Feature/Queries/OverviewQueryTest.php`, `tests/Feature/DashboardOverviewTest.php` |
| FR9 | The system reports the time spent per task and per day, derived from the recorded work sessions. | no | done | `TimeReportQuery`, `pages::reports.time` | `tests/Feature/Queries/TimeReportQueryTest.php`, `tests/Feature/Reports/TimeReportPageTest.php` |
| FR10 | For a task with a branch name, the system offers the matching `git switch` command for copying. | yes | done | `Task::switchCommand`, `pages::dashboard` | `tests/Feature/DashboardOverviewTest.php` |
| FR11 | The developer can set a weekly hour budget per project and is warned when the time recorded in a week exceeds it. | yes | deferred | | |
| FR12 | Commits are linked to tasks automatically through the branch name, for instance by a local Git hook that reports to the system. | yes | deferred | | |

FR11 and FR12 are deferred deliberately. FR11 depends on reliable time data
from FR9 and is only meaningful once the lifecycle in FR3 to FR7 has been used
for some weeks. FR12 requires an interface that accepts requests from outside
the browser session, which widens the attack surface discussed in Section 3.4
and is therefore kept out of the prototype.

## Nonfunctional requirements

| ID | Category | Requirement | Status | Verified by |
| --- | --- | --- | --- | --- |
| NFR1 | Usability | Switching from the active task to another task, including the resume point, takes no more than three interactions after the target task has been chosen. | done | `tests/Feature/Tasks/StartPageTest.php` covers the flow; two text entries and one submit were confirmed by hand in the browser on 2026-09-29 |
| NFR2 | Usability | Every interaction of the switch and pause flow can be completed with the keyboard alone. | done | Native form with autofocus and single line fields, so that Enter submits; the flow was completed with the keyboard alone by hand in the browser on 2026-09-29 |
| NFR3 | Reliability | The rule that at most one work session per developer is open holds under every sequence of actions, including double submissions. | done | `tests/Feature/Actions/TaskLifecycleTest.php` for start, resume and double submission, `tests/Feature/Models/DomainModelTest.php` for the index; `tests/Feature/Actions/SwitchTaskTest.php` for the switch |
| NFR4 | Reliability | A resume point is never lost: pausing either stores the resume point and closes the session together, or changes nothing. | done | `tests/Feature/Actions/TaskLifecycleTest.php`, including a pause that fails while the resume point is stored |
| NFR5 | Security | Every page except the welcome page and the pages for login, registration and password reset requires an authenticated session. | done | `tests/Feature/RouteAccessTest.php` requests every page as a guest and expects the login page, apart from the welcome, login, registration and password reset pages |
| NFR6 | Security | A developer can only read and change projects, tasks and sessions that belong to their own account. | done | `tests/Feature/Policies/OwnershipPolicyTest.php` for the policies; foreign user cases in `ProjectPagesTest.php`, `TaskFormPageTest.php` and `StartPageTest.php` |
| NFR7 | Privacy | All data stays in the local database. The system sends no telemetry and embeds no third party tracking. | done | Read from the code and the build output: the application has no outgoing HTTP calls and no analytics package; the font is downloaded once at build time and served from `/build/assets`. The welcome page carries links to third party sites that are followed only by a click |
| NFR8 | Performance | The overview page responds in under 200 milliseconds of server time with 1,000 tasks and 10,000 work sessions in the database. | done | `tests/Feature/Queries/OverviewQueryTest.php`, which seeds 1,000 tasks (300 paused) and 10,000 work sessions; the page took 33 ms of server time in one run on in-memory SQLite |
| NFR9 | Supportability | Every action class is covered by an automated test, static analysis passes at the configured level, and the code style check passes. | done | `composer test` runs the code style check, PHPStan at level 7 without baseline and 169 tests; every action and both queries are named in a test, and `OpenWorkSession` is covered through `StartTask` and `ResumeTask` |
| NFR10 | Supportability | A new rule can be added as a new action class without changing existing user interface components. | partly met | A rule can be added inside an action without touching a page, as ADR-0007 did with the archived project check in `OpenWorkSession`. A rule that throws a new exception class does need a page change, because every page lists the exceptions it catches |
| NFR11 | Implementation | The system is implemented in PHP with Laravel and Livewire, uses SQLite for persistence, and runs locally on macOS with PHP 8.3 or newer. | done | `composer.json` requires PHP 8.3 or newer, Laravel 13 and Livewire 4, `.env.example` selects SQLite; the suite passes on macOS with PHP 8.4.22, and PHP 8.3 itself was not run |
