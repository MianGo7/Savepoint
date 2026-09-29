# Decision log

Architecture and tooling decisions in the form of architecture decision
records (ADR). Each record states the context, the decision, the alternatives
that were rejected and the consequences. A decision that changes later is not
edited in place; a new record supersedes it and says so.

---

## ADR-0001: Web application with Laravel and Livewire

Date: 2026-09-29. Status: accepted.

**Context.** The system is a personal task management tool for software
development work. It is used at a desktop, next to the editor and the
terminal, and has a single primary user. The project report requires a
prototypical implementation, a subsystem decomposition and an object design
per package, so the chosen technology should make the architecture easy to
show rather than hide it behind generated code.

**Decision.** The system is built as a server rendered web application with
Laravel 13 and Livewire 4, starting from the official Livewire starter kit.
The starter kit provides authentication through Laravel Fortify, the Flux user
interface component library and Tailwind CSS.

**Alternatives.** An Android application would have reused the tooling of the
course DLBCSEMSE02, but a developer manages tasks at the desk rather than on a
phone, so the platform would have worked against the use case. The React
variant of the starter kit was rejected because it splits the logic between
PHP and TypeScript and adds a client state layer that the report would then
have to explain without gaining a requirement in return. Symfony was rejected
because the author has four years of experience with Laravel and none of
comparable depth with Symfony, which would have shifted effort from the
software engineering methods to learning a framework.

**Consequences.** Rules, persistence and rendering stay in one language.
Livewire components make the boundary between the user interface and the
application logic explicit, which is used in ADR-0002. The system depends on a
running PHP process, which is acceptable for a locally hosted tool.

---

## ADR-0002: Action classes as control objects

Date: 2026-09-29. Status: accepted.

**Context.** The analysis model of Bruegge and Dutoit distinguishes entity,
boundary and control objects. The implementation should preserve that
distinction so that the object design in Chapter 4 can be read directly from
the code.

**Decision.** Every use case step that changes state is implemented as a
dedicated action class in `app/Actions/<Package>/` with a single public
`handle` method. Livewire components act as boundary objects and delegate to
actions, Eloquent models act as entity objects, and actions act as control
objects. Actions that write more than one record run inside a database
transaction.

**Alternatives.** Placing the logic inside the Livewire components was
rejected because it couples the rules to the user interface and makes them
testable only through rendered components. Service classes with many methods
were rejected because they tend to collect unrelated operations and blur the
mapping to use cases. The package `lorisleiva/laravel-actions` was rejected
because plain classes achieve the same separation without an additional
dependency and without the implicit behaviour the package introduces.

**Consequences.** The number of classes grows with the number of operations,
which is intended, since each class corresponds to one step of a use case.
Actions can be tested without rendering a page, which serves NFR9.

---

## ADR-0003: SQLite as the database

Date: 2026-09-29. Status: accepted.

**Context.** The system stores projects, tasks, work sessions and resume
points for a single developer on one machine. The expected volume is in the
thousands of rows.

**Decision.** SQLite is used for development, testing and local operation.
The automated tests run against an in memory SQLite database.

**Alternatives.** MySQL and PostgreSQL were rejected because they require a
separate server process, either installed locally or run in a container,
without any requirement that depends on their additional capabilities such as
concurrent writers or replication.

**Consequences.** The database is a single file that can be backed up by
copying it. The schema is defined exclusively through migrations, so a later
move to another database remains possible without changing the application
code.

---

## ADR-0004: Local development without containers

Date: 2026-09-29. Status: accepted.

**Context.** The development machine already provides PHP, Composer and
Node.js, and Docker is available through OrbStack.

**Decision.** The application runs locally through `composer run dev`, which
starts the PHP development server, the queue worker and the Vite development
server together. Laravel Sail is not used.

**Alternatives.** Laravel Sail was rejected because it only pays off when
services such as a database server, a cache or a mail catcher are needed, and
ADR-0003 removes the only candidate among them.

**Consequences.** Setting up the project needs PHP 8.3 or newer, Composer and
Node.js on the machine. The hardware and software mapping in Section 3.3
consists of one node running the PHP process and the SQLite file, and the
browser as the client.

---

## ADR-0005: PlantUML for the UML models

Date: 2026-09-29. Status: accepted.

**Context.** The report requires use case, class, statechart, sequence,
package, component and deployment diagrams, and the models have to stay
consistent with a codebase that changes throughout the project.

**Decision.** All models are written as PlantUML text files in
`docs/diagrams/` and rendered to SVG or PNG for the report. A shared style file
keeps every figure black on white. The analysis models are drafted before the
lifecycle is implemented (B7), and the design models are drawn from the
implemented code (B8).

**Alternatives.** Graphical editors such as draw.io and StarUML were rejected
because their files are hard to compare between versions, so a change to a
model would not be reviewable in the commit that changes the code. Visual
Paradigm was rejected for the same reason and additionally requires a licence
for most diagram types.

**Consequences.** Every model change appears as a readable diff next to the
code change that caused it, which supports the traceability the process
criterion asks for. The automatic layout of PlantUML gives less control over
the placement of elements than a graphical editor, which may require layout
hints in larger diagrams.

---

## ADR-0006: Stored task status and denormalised ownership

Date: 2026-09-29. Status: accepted.

**Context.** The overview (FR8) filters tasks by lifecycle state, the rule that
at most one work session per developer is open (FR3, NFR3) has to hold under
double submissions, and every record has to be restricted to its owner (NFR6).

**Decision.** The lifecycle state is stored in `tasks.status` as the string
backed enum `TaskStatus`, together with `tasks.completed_at`. The column
`user_id` is stored on `tasks` and `work_sessions` in addition to the path
through the project and the task. A partial unique index on
`work_sessions (user_id) where ended_at is null` lets the database reject a
second open session. Resume points carry no `user_id` and are owned through
their task. The models stay in the flat `app/Models` directory, since the
domain has four entities and no package structure is needed yet.

**Alternatives.** Deriving the state from the existence of an open session and
a completion timestamp avoids redundancy, but every overview query would need a
join and a subquery, and the statechart would exist only implicitly. Resolving
ownership through the project for tasks and through the task for sessions was
rejected because every policy check and every ownership filter would load a
second record, and because the partial index needs the owner on the session
row.

**Consequences.** The status column and the sessions can disagree if an action
changes one without the other, so the lifecycle actions of B2 must change both
inside one transaction and are tested for that. The factories copy `user_id`
from the parent so that the two paths to the owner never differ. The partial
index is SQLite syntax, which is acceptable under ADR-0003 and has to be
revisited if the database changes. The PHPStan step of `composer test` now
passes `--memory-limit=512M`, because the default limit of 128 MB crashed the
parallel worker once the domain model was added.
