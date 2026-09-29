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
