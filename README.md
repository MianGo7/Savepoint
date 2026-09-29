# Task Manager for Developers

A personal task management system for software development work, built
around the cost of switching between tasks. Pausing a task requires a short
resume point that records where the work stopped and what the next step is.
Resuming a task shows that resume point before anything else, and the time
spent on each task is derived from the work sessions between starting and
pausing it.

This repository is the practical part of a project report for the IU course
DLBCSPSE01, Project: Software Engineering.

- Course: DLBCSPSE01, Project: Software Engineering
- Task: Task 1, task management system for software developers
- Author: Mian Gohar Ehsan
- Matriculation number: IU14147184

## Planned functionality

- Projects that group tasks, and tasks with an optional Git branch name.
- Starting a task opens a work session. Only one task is active at a time.
- Pausing a task requires a resume point. Starting another task while one is
  active asks for the resume point first and then switches in one step.
- An overview of the active task and the paused tasks with their latest
  resume points.
- Time spent per task and per day.

The full list of requirements, including the ones deliberately deferred, is in
`docs/requirements.md`.

## Requirements

- PHP 8.3 or newer with the SQLite extension
- Composer 2
- Node.js 22 or newer

## Set up and run

    git clone <repository url>
    cd task-manager-for-devs
    composer setup
    composer run dev

The application is then available at `http://localhost:8000`.

## Checks

    composer test      # code style check, static analysis and the test suite
    composer lint      # fixes the code style

## Project structure

    app/Actions/        control objects, one class per state changing operation
    app/Models/         entity objects, Eloquent models
    app/Policies/       ownership rules
    resources/views/pages/   Livewire pages, the boundary objects
    database/migrations/     the schema
    tests/              feature and unit tests

    docs/
      requirements.md   functional and nonfunctional requirements with status
      decision-log.md   architecture decisions with rejected alternatives
      dev-journal.md    chronological development record
      backlog.md        scoped work items, taken one at a time
      diagrams/         UML models as PlantUML sources

One further file, `docs/course-context.local.md`, holds a paraphrase of the
examination requirements. It is intentionally not committed, because IU holds
the copyright on its examination tasks and objects to them being published on
third party platforms.

## Architecture

The code follows the distinction between entity, boundary and control objects.
Livewire pages render the user interface and delegate every change to an
action class, action classes enforce the rules and run multi record changes in
a transaction, and Eloquent models hold the persistent state. The reasoning is
recorded in `docs/decision-log.md`, ADR-0002.
