# Flows of events

Working notes for the appendix of the report, one entry per use case of
`use-cases.puml`. Each entry names the requirement it realises, the entry
condition, the flow of events, and the exceptional flows. The actor of every
use case is the developer. These are notes, not report text.

## ManageProjects (FR1)

Entry condition: the developer is signed in.

1. The developer opens the project list.
2. The developer creates a project by entering a name, renames a project, or
   archives a project.
3. The system validates the name, stores the change and shows the updated list.

Exceptional flows: an empty name is rejected and nothing is stored. Archiving
a project keeps its tasks and work sessions, so that recorded time stays
reportable.

## ManageTasks (FR2)

Entry condition: the developer is signed in and owns the target project.

1. The developer chooses a project and creates, edits or deletes a task.
2. For creation and editing, the developer enters a title and optionally a
   description, an estimate in minutes and a Git branch name.
3. The system validates the input, stores the change and shows the task list.

Exceptional flows: a missing title is rejected. A project of another developer
is refused by the policy. Deleting a task also deletes its work sessions and
resume points.

## StartTask (FR3)

Entry condition: the task has the status todo and the developer has no open
work session.

1. The developer starts the task.
2. The system opens a work session with the current time, sets the status of
   the task to active and shows the task.

Exceptional flows: if the developer has an open work session, the system
starts no session and the developer is directed to SwitchTask. A double
submission is rejected by the database, so that at most one open session
exists (NFR3). A task of another developer is refused by the policy.

## PauseTask (FR4), includes RecordResumePoint

Entry condition: the task has the status active.

1. The developer chooses to pause the active task.
2. The system asks for the resume point: where the work stopped and what the
   next step is.
3. The developer enters both texts.
4. The system stores the resume point, closes the open work session with the
   current time and sets the status of the task to paused, in one transaction.

Exceptional flows: if either text is empty, the pause is rejected and the task
stays active with its session open. If storing fails, nothing is changed
(NFR4).

## SwitchTask (FR5), includes PauseTask and StartTask

Entry condition: one task is active and the developer chooses another task
that is todo or paused.

1. The developer chooses the target task while another task is active.
2. The system asks for the resume point of the active task.
3. The developer enters the resume point.
4. The system pauses the active task, then starts the target task, in one
   transaction. If the target task is paused, ResumeTask shows its latest
   resume point before the new session begins.

Exceptional flows: an empty resume point cancels the whole switch and leaves
the active task unchanged. If starting the target fails, the pause is rolled
back as well, so that no resume point is stored for a switch that did not
happen.

## ResumeTask (FR6)

Entry condition: the task has the status paused and the developer has no open
work session.

1. The developer chooses to resume the task.
2. The system shows the most recent resume point of the task, with the earlier
   resume points available as a history.
3. The developer confirms.
4. The system opens a new work session and sets the status to active.

Exceptional flows: if the developer has an open work session, the resume is
refused and the developer is directed to SwitchTask.

## CompleteTask (FR7)

Entry condition: the task has the status active or paused.

1. The developer completes the task.
2. The system closes any open work session, sets the status to completed and
   stores the completion time.

Exceptional flows: a task with the status todo cannot be completed, because
FR7 permits completion only from the active or the paused state. A completed
task cannot be started again in the prototype.

## ViewOverview (FR8)

Entry condition: the developer is signed in.

1. The developer opens the overview.
2. The system lists the active task first and then the paused tasks, ordered
   by last activity, each with its latest resume point.

Exceptional flows: without an active or paused task the overview states that
there is nothing to resume.

## CopyBranchCommand (FR10), extends ViewOverview

Entry condition: the task shown in the overview has a branch name.

1. The developer chooses to copy the command of the task.
2. The system places `git switch <branch name>` on the clipboard.

Exceptional flows: a task without a branch name offers no command.

## ViewTimeReport (FR9)

Entry condition: the developer is signed in.

1. The developer opens the time report.
2. The system sums the closed work sessions per task and per day and shows the
   result.

Exceptional flows: a session that crosses midnight is split at the day
boundary. A session that is still open is counted up to the current time.

## Open points for the analysis

The behaviour of an archived project with tasks that are still active is not
fixed by the requirements. The flows above assume that archiving does not
touch tasks. Whether a completed task may be reopened is likewise not
required by FR7 and is excluded from the statechart.
