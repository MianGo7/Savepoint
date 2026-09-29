# Development journal

The chronological record of the work. It is the main source for the process
criterion and for the critical evaluation in Chapter 5, so problems are
recorded as they happened, including the ones that turned out to be the
author's own mistakes.

Template for every entry:

    ## YYYY-MM-DD

    **Worked on.** What was done, with backlog item ids.

    **Decisions.** What was decided and why, with ADR numbers where one exists.

    **Problems.** What went wrong, how it was noticed and how it was solved.

    **Next.** What comes next.

---

## 2026-09-29

**Worked on.** Selection of the examination task and set up of the
repository, backlog item B0. Of the three tasks offered, Task 1 was chosen,
which asks for a task management system for the author's own development work
with individual features that existing systems do not provide. The concept
centres on the cost of switching between tasks: pausing a task requires a
short resume point that records where the work stopped and what the next step
is, and resuming a task shows that resume point first. Time per task is
derived from the work sessions between starting and pausing.

**Decisions.** The stack is Laravel 13 with Livewire 4, based on the official
starter kit (ADR-0001), with action classes as control objects (ADR-0002),
SQLite as the database (ADR-0003) and local development without containers
(ADR-0004). A weekly hour budget per project was considered as a second
individual feature. It is specified as FR11 but deferred, because the task
explicitly welcomes requirements that are specified without being implemented,
and because the page limit of the report favours a small system described in
depth over a broader one described superficially. The UML models are kept as
PlantUML sources (ADR-0005) and split into two backlog items: the analysis
models in B7 are drafted directly after the domain model and guide the
implementation of the lifecycle, whereas the design models in B8 are drawn
from the finished code, so that the difference between the two becomes
material for the evaluation.

**Problems.** Two problems occurred during the set up. First, the Laravel
installer rejected `.` as the target directory with the message that the
application already exists, although the directory was empty. The cause is a
comparison in the installer between the literal string `.` and the absolute
path of the working directory, which never matches; passing the absolute path
instead of `.` avoided the check. Second, the freshly installed application
failed static analysis. When optional authentication features are deselected,
the starter kit removes the marked code sections but left an empty
`withTwoFactor` method in the user factory, whose missing return statement
PHPStan reported at level 7. The same removal left a skipped two factor test,
an empty test that Pest reported as risky, unused imports and an empty `mount`
method in the security settings page, and a rate limiter for the two factor
challenge. The leftovers were removed in a separate commit, after which the
code style check, static analysis and all 25 tests passed. The incident
confirms that generated code is not exempt from the quality checks and that
the checks belong in the definition of done from the first commit onwards.

**Next.** B1, the domain model with migrations, models, factories and
policies, followed by B7, the analysis models.

---

## 2026-09-29 (B1)

**Worked on.** Backlog item B1, the domain model. Migrations, models,
factories and policies for projects, tasks, work sessions and resume points,
the `TaskStatus` enum, the relationships to the user, and tests for the
relationships, the factory states and the ownership policies. The rename of the
working title to Savepoint was committed beforehand as a separate change.

**Decisions.** The task status is stored rather than derived, and `user_id` is
stored on tasks and work sessions, recorded as ADR-0006 with the rejected
alternatives. The rule of one open work session per user is enforced by a
partial unique index, so that it also holds under double submissions (NFR3),
and a test shows that the database rejects the second session. Task creation
is authorised against the target project, so that a task cannot be attached to
the project of another developer.

**Problems.** After the models were added, `composer test` failed because the
PHPStan worker exceeded the PHP memory limit of 128 MB, although the analysis
itself was clean. The limit was raised in the composer script and recorded in
ADR-0006. The first version of the factories also failed level 7, because
`findOrFail` on an array argument is typed as a possible collection; querying
the owner column directly fixed the type without a suppression. The
`two_factor_*` annotations were removed from the `User` model as planned.

**Next.** B7, the analysis models, which guide B2 and B3.

---

## 2026-09-29 (B7)

**Worked on.** Backlog item B7, the analysis models: the use case diagram
(`use-cases.puml`), the analysis class diagram with entity, boundary and
control objects (`analysis-object-model.puml`), the statechart of a task
(`task-statechart.puml`), and the flows of events of the ten use cases in
`use-case-flows.md`. All three diagrams were rendered with PlantUML without
errors.

**Decisions.** The use cases follow FR1 to FR10 one to one, with the two
deferred requirements FR11 and FR12 left out. SwitchTask is modelled as an
include of PauseTask and StartTask rather than as a transition of its own, so
that the statechart has exactly five transitions for B2 to implement: start,
pause, resume, complete from active and complete from paused. The atomicity of
the switch is stated on the statechart as a note and is the subject of B3.
Entity names and attributes in the class diagram are those of the models
from B1, whereas the boundary and control names are provisional and are
corrected in B8 where the code differs.

**Problems.** Two points are not settled by the requirements. FR7 permits
completion only from the active and the paused state, so a task with the status
todo cannot be completed and a completed task cannot be reopened; both were
kept out of the statechart. The behaviour of tasks in an archived project is
likewise unspecified, and the flows assume that archiving does not touch them.
Both are recorded as open points at the end of `use-case-flows.md` and need a
decision from the author before B4.

**Next.** B2, the task lifecycle actions, implemented against the statechart.

---

## 2026-09-29 (B2)

**Worked on.** Backlog item B2, the lifecycle actions `StartTask`,
`PauseTask`, `ResumeTask` and `CompleteTask` in `app/Actions/Tasks/`, the
three exceptions `TaskTransitionNotAllowed`, `SessionAlreadyOpen` and
`ResumePointRequired`, and 26 tests in `TaskLifecycleTest`. The five
transitions of the statechart are covered, each with the permitted path and
every rejected path.

Afterwards the open points of B2 were decided and recorded as ADR-0007: a test
now covers the translation of an index violation, tasks in an archived project
cannot be started or resumed but can be paused and completed, and completed
tasks are not reopened.

**Decisions.** Starting and resuming differ only in the status they expect, so
both delegate to an internal action `OpenWorkSession`. The analysis model
of B7 draws `ResumeTask` depending on `StartTask`; the code deviates from it,
which is recorded here for the comparison with the design model in B8. Every
action reads the task again inside its transaction, so that a second
submission of the same request meets the status the first one set. The
partial unique index from B1 remains the last line of defence against two open
sessions, and a violation is translated into `SessionAlreadyOpen`. Resume point
texts are trimmed, and a text of blanks counts as missing. Ownership is not
checked in the actions, since the pages authorise through the policies.

**Problems.** SQLite has no row locks, so `lockForUpdate` has no effect in the
local database and the protection against concurrent requests rests on the
re-read of the status and on the index. The translation of a unique violation
in `OpenWorkSession` is not exercised by a test, because a single process
cannot pass the application check and the index at the same moment; the index
itself is covered in `DomainModelTest`. The requirements FR3, FR4, FR6, FR7 and
NFR3 stay in progress until the pages and the switch exist, and only NFR4 is
marked done.

**Next.** B3, the switch with a resume point.

---

## 2026-09-29 (B3)

**Worked on.** Backlog item B3, the switch with a resume point. The action
`SwitchTask` pauses the active task with the resume point and starts or resumes
the target inside one transaction. The Livewire page `tasks/{task}/start`
starts or resumes a task directly when nothing is active, shows the latest
resume point of a paused task first (FR6), and asks for the resume point of the
active task when another one is running. Tests cover the action (eight cases)
and the page (nine cases).

**Decisions.** The flow lives on a routable page of its own, because the task
list (B4) and the overview (B5) do not exist yet, and the pages of those items
only have to link to it. A page that changes state on a plain visit was
avoided: the page always shows a button, so that opening the address never
starts a session. The active task is looked up again on submit, because it may
have changed since the page was rendered. The analysis model names a
`ResumePointDialog` as the boundary object; the code has a page instead, which
is material for the comparison in B8.

**Problems.** The keyboard requirement (NFR2) and the count of interactions
(NFR1) cannot be shown by a Livewire component test, which asserts on state and
not on focus order. The form is a native form with autofocus on the first field
and single line inputs, so that Enter submits, but this has not been tried in a
browser yet, and both requirements stayed in progress until the flow was tried by hand. The
author completed it with the keyboard alone in the browser, after which NFR1 and
NFR2 were marked done. An automated browser test was not possible, because the
browser used for it could not reach local addresses, and a
first test server started with `php artisan serve` read the development
database instead of the throwaway one, because that command does not forward
`DB_DATABASE`; the second server was started with the PHP built-in server. A test for a target in an
archived project shows that the pause is rolled back when the start fails.

**Next.** Commit B3; B4 follows.

---

## 2026-09-29 (B4)

**Worked on.** Backlog item B4, the project and task management pages. Seven
new actions (`CreateProject`, `RenameProject`, `ArchiveProject`,
`DeleteProject`, `CreateTask`, `UpdateTask`, `DeleteTask`), the pages
`projects` (list, create, rename, archive, delete), `projects/{project}`
(tasks with status and links to start, edit and delete) and the task form for
creating and editing, and the navigation. The starter kit links to its own
repository and documentation were replaced by a link to the projects, as
planned in the leftovers list.

**Decisions.** Recorded as ADR-0008: active tasks cannot be deleted, tasks
cannot be created in archived projects, archived projects cannot be restored,
and branch names are restricted to a safe character set. Archiving does not
reject projects with open tasks, as ADR-0007 requires. The analysis model
draws one control object `ManageProjects` and one `ManageTasks`; the code has
one action per operation, in line with ADR-0002, which is material for B8.
Authorisation happens on every mutating method of the pages, since a Livewire
method can be called with any id.

**Problems.** A new task instance did not carry the database default of its
status, which a test caught; the action now reloads it. The wording of the
`ProjectArchived` message promised a restore that does not exist and was
corrected. The item asks for screenshots of the pages for the report, which
have not been taken yet; they are taken at the end, together with the others
for Section 5.1.

**Next.** B5, the overview. The screenshots of the pages are taken at the end
of the project, together with the others for Section 5.1.

