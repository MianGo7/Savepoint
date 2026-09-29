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
| `switch-sequence.puml` | Sequence diagram of switching tasks with a resume point | 2.4 | B8 | open |
| `subsystems.puml` | Package and component diagram | 3.2 | B8 | open |
| `deployment.puml` | Deployment diagram | 3.3 | B8 | open |
| `global-control.puml` | Request handling from browser to action | 3.6 | B8 | open |
| `use-case-flows.md` | Flows of events of the use cases, notes for the appendix | 2.4 | B7 | done |
| `package-*.puml` | One class diagram per package | 4.x | B8 | open |

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
