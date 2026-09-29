# Diagrams

The UML models of the report, kept as PlantUML sources so that they are
versioned together with the code they describe and every change to a model is
visible in the history. Each diagram is listed with the report section it
belongs to, the backlog item that produces it, and its status. The analysis
models are drafted before the lifecycle is implemented and revised afterwards;
the design models are drawn from the implemented code.

| File | Diagram | Report section | Item | Status |
| --- | --- | --- | --- | --- |
| `use-cases.puml` | Use case diagram | 2.4 | B7 | done |
| `analysis-object-model.puml` | Analysis class diagram with entity, boundary and control objects | 2.4 | B7 | done |
| `task-statechart.puml` | Statechart of a task | 2.4 | B7 | done |
| `switch-sequence.puml` | Sequence diagram of switching tasks with a resume point | 2.4 | B8 | done |
| `subsystems.puml` | Package and component diagram | 3.2 | B8 | done |
| `deployment.puml` | Deployment diagram | 3.3 | B8 | done |
| `global-control.puml` | Request handling from browser to action | 3.6 | B8 | done |
| `object-diagram.puml` | Object diagram of a developer with two tasks, their sessions and a resume point | 2.4 | B10 | done |
| `database-schema.puml` | Entity relationship diagram of the tables, from the migrations | 3.5 | B10 | done |
| `object-definitions.md` | Definitions of the entity objects, notes for Section 2.4 | 2.4 | B10 | done |
| `use-case-flows.md` | Flows of events of the use cases, notes for the appendix | 2.4 | B7 | done |
| `package-models.puml` | Class diagram of the entity objects and the task status | 4 | B8 | done |
| `package-task-actions.puml` | Class diagram of the task actions | 4 | B8 | done |
| `package-project-actions.puml` | Class diagram of the project actions | 4 | B8 | done |
| `package-queries.puml` | Class diagram of the read models | 4 | B8 | done |
| `package-policies.puml` | Class diagram of the ownership policies | 4 | B8 | done |
| `package-exceptions.puml` | Class diagram of the rule exceptions | 4 | B8 | done |
| `package-pages.puml` | Class diagram of the Livewire pages | 4 | B8 | done |

## Conventions

- One diagram per file, opened with `@startuml` followed by the file name
  without extension, so that the rendered file carries the same name.
- UML 2.5 notation. Stereotypes `<<entity>>`, `<<boundary>>` and
  `<<control>>` mark the object types of the analysis model.
- Black on white with the shared style in `_style.puml`, included by every
  diagram, because the report is printed and the figures must stay legible
  without colour.
- Class, attribute and method names match the code exactly. A diagram that
  shows how the code should look rather than how it does is a defect.

## Rendering

    plantuml -tsvg -o out docs/diagrams/*.puml
    plantuml -tpng -o out docs/diagrams/*.puml

The rendered files are written to `docs/diagrams/out/`, which is not
committed, since they are generated from the sources. PlantUML needs Java and,
for class and use case diagrams, Graphviz; `brew install plantuml` installs
all three.
