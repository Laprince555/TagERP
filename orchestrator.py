"""Background orchestrator for a Claude Code -> Codex -> Claude Code workflow."""

from __future__ import annotations

import json
import os
import re
import shutil
import subprocess
import sys
import threading
from pathlib import Path
from typing import Any


# ---------------------------------------------------------------------------
# Project and model configuration
# ---------------------------------------------------------------------------
PROJECT_NAME = "TagERP"
WORKING_DIRECTORY = Path(__file__).resolve().parent
CLAUDE_MODEL = "sonnet"  # Medium Claude model alias (or use a full model ID).
CODEX_MODEL = "codex-mini-latest"  # Fast Codex model.

TASKS_FILE = Path(__file__).resolve().parent / "tasks.json"
POLL_INTERVAL_SECONDS = 3600
COMMAND_TIMEOUT_SECONDS = 45 * 60

# These may be changed if the executables are not on PATH.
CLAUDE_COMMAND = "claude"
CODEX_COMMAND = "codex"

VALID_STATUSES = {
    "pending_planning",
    "pending_execution",
    "pending_review",
    "completed",
}


class Colors:
    """ANSI colors; disabled automatically when output is not a terminal."""

    enabled = sys.stdout.isatty() and os.getenv("NO_COLOR") is None
    GREEN = "\033[32m" if enabled else ""
    YELLOW = "\033[33m" if enabled else ""
    RED = "\033[31m" if enabled else ""
    CYAN = "\033[36m" if enabled else ""
    RESET = "\033[0m" if enabled else ""


def log(message: str, color: str = "") -> None:
    print(f"{color}{message}{Colors.RESET}", flush=True)


def load_tasks() -> list[dict[str, Any]]:
    """Load and validate the task queue."""
    try:
        raw = json.loads(TASKS_FILE.read_text(encoding="utf-8"))
    except FileNotFoundError as exc:
        raise RuntimeError(f"Task file does not exist: {TASKS_FILE}") from exc
    except json.JSONDecodeError as exc:
        raise RuntimeError(f"Invalid JSON in {TASKS_FILE}: {exc}") from exc

    if not isinstance(raw, list):
        raise RuntimeError("tasks.json must contain a JSON array.")

    seen_ids: set[str] = set()
    for index, task in enumerate(raw):
        if not isinstance(task, dict):
            raise RuntimeError(f"Task #{index + 1} must be an object.")
        missing = {"id", "idea_description", "status"} - task.keys()
        if missing:
            raise RuntimeError(f"Task #{index + 1} is missing: {', '.join(sorted(missing))}")
        task_id = str(task["id"])
        if task_id in seen_ids:
            raise RuntimeError(f"Duplicate task id: {task_id}")
        seen_ids.add(task_id)
        if not isinstance(task["idea_description"], str) or not task["idea_description"].strip():
            raise RuntimeError(f"Task {task_id} has an empty idea_description.")
        if task["status"] not in VALID_STATUSES:
            raise RuntimeError(f"Task {task_id} has invalid status: {task['status']}")
    return raw


def atomic_write_tasks(tasks: list[dict[str, Any]]) -> None:
    """Replace tasks.json atomically so readers never see a partial file."""
    temporary = TASKS_FILE.with_suffix(".json.tmp")
    temporary.write_text(
        json.dumps(tasks, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    os.replace(temporary, TASKS_FILE)


def update_task_status(task_id: str, expected: str, new_status: str) -> None:
    """Reload before writing, preserving tasks added externally during a model run."""
    tasks = load_tasks()
    for task in tasks:
        if str(task["id"]) == task_id:
            if task["status"] != expected:
                raise RuntimeError(
                    f"Task {task_id} changed externally: expected {expected}, "
                    f"found {task['status']}."
                )
            task["status"] = new_status
            atomic_write_tasks(tasks)
            return
    raise RuntimeError(f"Task {task_id} was removed externally.")


def safe_task_id(task_id: str) -> str:
    """Create a filesystem-safe ID without allowing directory traversal."""
    cleaned = re.sub(r"[^A-Za-z0-9_.-]+", "_", task_id).strip("._")
    return cleaned[:80] or "task"


def plan_path_for(task_id: str) -> Path:
    # Each task owns a plan.md, so one failed task cannot consume another plan.
    return WORKING_DIRECTORY / ".orchestrator" / safe_task_id(task_id) / "plan.md"


def run_command(command: list[str], prompt: str, label: str) -> bool:
    """Run an agent non-interactively and report failures without crashing."""
    executable = shutil.which(command[0])
    if executable is None:
        log(f"[ERROR] {command[0]!r} was not found on PATH.", Colors.RED)
        return False

    command[0] = executable
    log(f"[RUNNING] {label}", Colors.CYAN)
    try:
        result = subprocess.run(
            command,
            input=prompt,
            cwd=WORKING_DIRECTORY,
            text=True,
            encoding="utf-8",
            errors="replace",
            timeout=COMMAND_TIMEOUT_SECONDS,
            check=False,
        )
    except subprocess.TimeoutExpired:
        log(f"[ERROR] {label} timed out after {COMMAND_TIMEOUT_SECONDS}s.", Colors.RED)
        return False
    except OSError as exc:
        log(f"[ERROR] Could not start {label}: {exc}", Colors.RED)
        return False

    if result.returncode != 0:
        log(f"[ERROR] {label} exited with code {result.returncode}.", Colors.RED)
        return False

    log(f"[OK] {label} finished successfully.", Colors.GREEN)
    return True


def plan_task(task: dict[str, Any]) -> bool:
    task_id = str(task["id"])
    plan_path = plan_path_for(task_id)
    plan_path.parent.mkdir(parents=True, exist_ok=True)
    prompt = f"""
Project: {PROJECT_NAME}
Task ID: {task_id}
Idea: {task['idea_description']}

Analyze the existing repository deeply and write a concrete implementation plan to:
{plan_path}

The file must be named plan.md. Include scope, affected files, ordered implementation
steps, validation, risks, and acceptance criteria. Do not implement the task yet.
""".strip()
    command = [
        CLAUDE_COMMAND,
        "--print",
        "--model",
        CLAUDE_MODEL,
        "--permission-mode",
        "acceptEdits",
        "--allowedTools",
        "Read,Glob,Grep,Write,Edit",
    ]
    return run_command(command, prompt, f"Claude planning task {task_id}") and plan_path.is_file()


def execute_task(task: dict[str, Any]) -> bool:
    task_id = str(task["id"])
    plan_path = plan_path_for(task_id)
    plan_instruction = (
        f"Read and follow the additional implementation plan at {plan_path}."
        if plan_path.is_file()
        else "No separate plan.md exists. Treat the detailed idea below as the complete implementation specification."
    )
    prompt = f"""
Project: {PROJECT_NAME}
Task ID: {task_id}
Detailed task specification:
{task['idea_description']}

{plan_instruction}
Implement only this task in this repository. Respect the current project architecture and
do not perform later queued tasks unless they are strictly required for this task.
Run suitable non-destructive checks/tests. Do not edit tasks.json and do not commit.
""".strip()
    command = [
        CODEX_COMMAND,
        "exec",
        "--model",
        CODEX_MODEL,
        "--approve-for-me",
        "-",
    ]
    return run_command(command, prompt, f"Codex execution for task {task_id}")


def review_task(task: dict[str, Any]) -> bool:
    task_id = str(task["id"])
    plan_path = plan_path_for(task_id)
    plan_context = (
        f"Additional implementation plan: {plan_path}"
        if plan_path.is_file()
        else "No separate plan.md was created; review against the detailed task specification."
    )
    prompt = f"""
Project: {PROJECT_NAME}
Task ID: {task_id}
Detailed task specification: {task['idea_description']}
{plan_context}

Review the implementation using git diff and relevant repository context. Fix defects,
regressions, incomplete requirements, and maintainability issues you find. Run suitable
non-destructive checks/tests. Do not edit tasks.json and do not commit. If everything is
correct, leave the code unchanged and report that conclusion.
""".strip()
    command = [
        CLAUDE_COMMAND,
        "--print",
        "--model",
        CLAUDE_MODEL,
        "--permission-mode",
        "acceptEdits",
        "--allowedTools",
        "Read,Glob,Grep,Write,Edit,Bash(git diff *),Bash(git status *),Bash(git log *),Bash(git show *),Bash(* test *),Bash(pytest *),Bash(npm test *)",
    ]
    return run_command(command, prompt, f"Claude review for task {task_id}")


def process_tasks() -> None:
    """Run every queued task through its remaining workflow stages."""
    try:
        tasks = load_tasks()
    except RuntimeError as exc:
        log(f"[ERROR] {exc}", Colors.RED)
        return

    actionable = [task for task in tasks if task["status"] != "completed"]
    if not actionable:
        log("[INFO] No pending tasks.", Colors.YELLOW)
        return

    for task in actionable:
        task_id = str(task["id"])
        while task["status"] != "completed":
            old_status = task["status"]
            try:
                if old_status == "pending_planning":
                    succeeded = plan_task(task)
                    new_status = "pending_execution"
                elif old_status == "pending_execution":
                    succeeded = execute_task(task)
                    new_status = "pending_review"
                else:  # pending_review
                    succeeded = review_task(task)
                    new_status = "completed"

                if not succeeded:
                    log(f"[RETRY] Task {task_id} remains {old_status}.", Colors.YELLOW)
                    log("[STOP] Queue paused at the failed task to preserve task order.", Colors.YELLOW)
                    return

                update_task_status(task_id, old_status, new_status)
                task["status"] = new_status
                log(f"[STATUS] Task {task_id}: {old_status} -> {new_status}", Colors.GREEN)
            except Exception as exc:  # Keep one bad task from stopping the queue.
                log(f"[ERROR] Task {task_id}: {exc}", Colors.RED)
                log("[STOP] Queue paused at the failed task to preserve task order.", Colors.YELLOW)
                return


def orchestrator_loop(wake_event: threading.Event, stop_event: threading.Event) -> None:
    """Wait efficiently, then scan hourly or whenever the CLI wakes us."""
    while not stop_event.is_set():
        # Event.wait avoids CPU polling. A CLI signal breaks the one-hour wait.
        wake_event.wait(POLL_INTERVAL_SECONDS)
        wake_event.clear()
        if stop_event.is_set():
            break
        process_tasks()


def main() -> None:
    WORKING_DIRECTORY.mkdir(parents=True, exist_ok=True)
    wake_event = threading.Event()
    stop_event = threading.Event()
    worker = threading.Thread(
        target=orchestrator_loop,
        args=(wake_event, stop_event),
        name="task-orchestrator",
        daemon=True,
    )
    worker.start()

    log(f"Orchestrator started for {PROJECT_NAME}.", Colors.GREEN)
    log("Press Enter or type 'run' to process now; type 'quit' to stop.", Colors.CYAN)
    try:
        while True:
            command = input("> ").strip().lower()
            if command in {"", "run"}:
                wake_event.set()
            elif command in {"quit", "exit", "q"}:
                break
            else:
                log("Unknown command. Use run, Enter, or quit.", Colors.YELLOW)
    except (EOFError, KeyboardInterrupt):
        print()
    finally:
        stop_event.set()
        wake_event.set()
        worker.join(timeout=5)
        log("Orchestrator stopped.", Colors.YELLOW)


if __name__ == "__main__":
    main()
