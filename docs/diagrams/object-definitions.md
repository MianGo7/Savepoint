# Definitions of the objects

Working notes for Section 2.4 of the report, one entry per entity object of
`analysis-object-model.puml`, `package-models.puml` and `object-diagram.puml`.
The persistent representation is in `database-schema.puml`. These are notes,
not report text.

| Object | Responsibility | Owned by | Removed together with |
| --- | --- | --- | --- |
| `User` | The developer who signs in and owns all other records. Comes from the starter kit. | none | not applicable |
| `Project` | Groups the tasks of one developer and can be archived (`archived_at`). | `User` | its user |
| `Task` | A unit of work with a title, an optional description, estimate and Git branch name, and a stored `status` of type `TaskStatus`. | `Project` and `User` | its project or user |
| `TaskStatus` | The lifecycle state of a task: `Todo`, `Active`, `Paused` or `Completed`. | none | not applicable |
| `WorkSession` | A span of time in which the developer worked on one task; open while `ended_at` is null, and at most one is open per developer. | `Task` and `User` | its task or user |
| `ResumePoint` | The note recorded when a session is paused: `where_stopped` and `next_step`. Owned through its task. | `Task` and `WorkSession` | its task or session |
