# Diagrams

The UML models of the report, kept as PlantUML sources so that they are
versioned together with the code they describe. Each diagram is listed with
the report section it belongs to and its status.

| File | Diagram | Report section | Status |
| --- | --- | --- | --- |
| `use-cases.puml` | Use case diagram | 2.4 | open |
| `analysis-object-model.puml` | Analysis class diagram with entity, boundary and control objects | 2.4 | open |
| `task-statechart.puml` | Statechart of a task | 2.4 | open |
| `switch-sequence.puml` | Sequence diagram of switching tasks with a resume point | 2.4 | open |
| `subsystems.puml` | Package and component diagram | 3.2 | open |
| `deployment.puml` | Deployment diagram | 3.3 | open |
| `global-control.puml` | Request handling from browser to action | 3.6 | open |
| `package-*.puml` | One class diagram per package | 4.x | open |

Rendering: `plantuml -tsvg docs/diagrams/*.puml`, or the PlantUML plugin of
the editor.
