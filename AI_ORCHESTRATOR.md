# TagERP AI Orchestrator

## Recommended workflow

1. Open a terminal in `D:\projects\TagERP` and start Claude Code with `claude`.
2. Ask Claude to inspect the project, plan the requested feature, and split it into small,
   independently verifiable tasks in `tasks.json`.
3. Because planning was already done interactively, each new task should use the
   `pending_execution` status. The orchestrator will send it to Codex, then to Claude for
   review and corrections.
4. In another terminal, run `py orchestrator.py`, then press Enter or type `run`.

Use this prompt with Claude Code:

> Analyze this TagERP project and the feature I describe. Break the work into small,
> ordered, independently testable implementation tasks. Update tasks.json without
> deleting unfinished existing tasks. Each object must contain exactly: id,
> idea_description, and status. Give every new task a unique stable id, make
> idea_description detailed enough for Codex to implement without guessing, and set
> status to pending_execution. Include dependencies, affected areas, acceptance criteria,
> and required tests in idea_description. Do not implement the tasks yet.

Allowed statuses are `pending_planning`, `pending_execution`, `pending_review`, and
`completed`. Use `pending_planning` only when you want the background orchestrator to ask
Claude to create a separate plan automatically.

Agent output is inherited by the terminal, so Codex and Claude activity appears live.
For tasks already planned interactively and marked `pending_execution`, a separate
`plan.md` is optional; Codex executes directly from the detailed `idea_description`.
