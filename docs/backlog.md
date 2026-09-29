# Backlog

The work queue. Items are taken one at a time and in order unless a
dependency says otherwise. B7 is taken directly after B1, because the
analysis models are meant to guide the lifecycle in B2 and B3 rather than
document it afterwards; its number reflects when it was added, not when it
is worked on. Each item names its scope, the condition under
which it counts as done and the documents that have to be updated in the same
change.

Status values: open, in progress, done.

---

## B0: Repository and project set up

Status: done.

Scope: scaffold the application with the official installer and the Livewire
starter kit, configure continuous integration, and create the documents in
`docs/`.

Done when: the application starts locally, `composer test` passes, and the
documents exist.

Documents to update: `dev-journal.md`, `decision-log.md`.

---

## B1: Domain model

Status: done.

Scope: migrations, Eloquent models, enums and factories for projects, tasks,
work sessions and resume points, including the relationships between them and
to the user. Policies that restrict every record to its owner. No user
interface.

Done when: the models and relationships exist, factories produce valid
records, the policies are covered by tests, and static analysis passes.

Documents to update: `requirements.md` (NFR6), `dev-journal.md`,
`decision-log.md` if the package structure is decided here.

---

## B2: Task lifecycle actions

Status: done.

Scope: the actions to start, pause, resume and complete a task, including the
rule that at most one work session per user is open and the rule that pausing
requires a resume point. The state transitions follow the statechart of the
task. No user interface.

Done when: every action has tests for the permitted and the rejected
transitions, a double start cannot open a second session, and a failed pause
leaves the data unchanged.

Documents to update: `requirements.md` (FR3, FR4, FR6, FR7, NFR3, NFR4),
`dev-journal.md`.

---

## B3: Switch with resume point

Status: done.

Scope: the action that pauses the active task with a resume point and starts
another task in one transaction, and the Livewire flow that asks for the
resume point when another task is started.

Done when: the switch is covered by an action test and a component test, and
the flow can be completed with the keyboard alone.

Documents to update: `requirements.md` (FR5, NFR1, NFR2), `dev-journal.md`.

---

## B4: Project and task management pages

Status: done. The screenshots for the report are taken by the author at the end of the project.

Scope: Livewire pages to create, edit, archive and delete projects and tasks,
using Flux components.

Done when: the pages call actions rather than writing models directly, the
component tests pass, and screenshots of the pages exist for the report.

Documents to update: `requirements.md` (FR1, FR2), `dev-journal.md`.

---

## B5: Where was I overview

Status: done.

Scope: the dashboard showing the active task and the paused tasks ordered by
last activity with their latest resume point, and the `git switch` command for
tasks with a branch name.

Done when: ordering and content are covered by tests and the page meets the
response time in NFR8 with seeded data.

Documents to update: `requirements.md` (FR8, FR10, NFR8), `dev-journal.md`.

---

## B6: Time report

Status: open.

Scope: time spent per task and per day, derived from the work sessions.

Done when: the aggregation is covered by tests, including sessions that cross
midnight.

Documents to update: `requirements.md` (FR9), `dev-journal.md`.

---

## B7: Analysis models

Status: done. Taken after B1, before B2.

Scope: the analysis models of Section 2.4 as PlantUML sources in
`docs/diagrams/`: the use case diagram with the flow of events of every use
case, the analysis class diagram with entity, boundary and control objects,
and the statechart of a task. They describe the intended behaviour, and B2 and
B3 are implemented against them.

Done when: the three diagrams render without errors, the flows of events are
written down for the appendix, and the statechart names every transition that
B2 has to implement.

Documents to update: `docs/diagrams/README.md`, `dev-journal.md`.

---

## B8: Design models

Status: open. Taken after B6.

Scope: the design models of Chapters 3 and 4 as PlantUML sources, drawn from
the implemented code: the sequence diagram of the switch, the package and
component diagram, the deployment diagram, the diagram of global control, and
one class diagram per package. The analysis models from B7 are revised where
the implementation deviated from them, and each deviation is recorded.

Done when: every diagram listed in `docs/diagrams/README.md` exists, renders
without errors and matches the code, and the deviations from the analysis
models are listed in `dev-journal.md`.

Documents to update: `docs/diagrams/README.md`, `dev-journal.md`.

---

## B9: Evaluation material

Status: open.

Scope: source lines of code per package for Section 5.1, the verification of
every requirement against the prototype for Section 5.2, and screenshots of
the main pages.

Done when: the figures are measured rather than estimated, and every row in
`requirements.md` has a final status.

Documents to update: `requirements.md`, `dev-journal.md`.
