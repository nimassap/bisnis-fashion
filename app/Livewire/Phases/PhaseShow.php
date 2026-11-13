<?php

declare(strict_types=1);

namespace App\Livewire\Phases;

use App\Actions\Phases\CompletePhase;
use App\Actions\Phases\ReopenPhase;
use App\Exceptions\PhaseException;
use App\Livewire\Concerns\SendsToasts;
use App\Models\Decision;
use App\Models\Phase;
use App\Models\PhaseTask;
use App\Services\PhaseBoard;
use App\Services\StepRequirements;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * One phase: its checklist, its decisions, and closing it.
 *
 * Every lookup goes through the signed-in owner's business, so an id from
 * another business resolves to a 404 rather than to somebody else's data.
 * A closed phase is read-only until it is reopened, which keeps "done"
 * meaning that every gate really was checked when it closed.
 */
#[Layout('components.layouts.app')]
final class PhaseShow extends Component
{
    use SendsToasts;

    #[Locked]
    public int $phaseId;

    // Task form
    public bool $showTaskForm = false;

    #[Locked]
    public ?int $editingTaskId = null;

    public string $taskTitle = '';

    public string $taskNotes = '';

    public string $taskDueOn = '';

    public bool $taskIsGate = false;

    // Decision form
    public bool $showDecisionForm = false;

    #[Locked]
    public ?int $editingDecisionId = null;

    public string $decisionTitle = '';

    public string $decisionReason = '';

    public string $decidedOn = '';

    // Confirmations
    #[Locked]
    public ?int $deletingTaskId = null;

    #[Locked]
    public ?int $deletingDecisionId = null;

    public bool $confirmingComplete = false;

    /**
     * The brand page passes nothing; a collection's phase page passes the
     * collection id and the phase key.
     */
    public function mount(?int $collection = null, ?string $key = null): void
    {
        $business = auth()->user()->business;

        $phase = $collection === null
            ? $business->brandPhase()->firstOrFail()
            : $business->collections()->findOrFail($collection)->phases()->where('key', $key)->firstOrFail();

        $this->phaseId = $phase->id;
    }

    // ── Tasks ──────────────────────────────────────────────────────────

    public function toggleTask(int $taskId): void
    {
        $task = $this->findTask($taskId);
        $this->ensureEditable();

        $task->forceFill(['completed_at' => $task->isDone() ? null : now()])->save();
    }

    public function openTaskCreate(): void
    {
        $this->ensureEditable();
        $this->resetTaskForm();
        $this->showTaskForm = true;
    }

    public function openTaskEdit(int $taskId): void
    {
        $this->ensureEditable();
        $task = $this->findTask($taskId);

        $this->editingTaskId = $task->id;
        $this->taskTitle = $task->title;
        $this->taskNotes = (string) $task->notes;
        $this->taskDueOn = (string) $task->due_on?->toDateString();
        $this->taskIsGate = $task->is_gate;
        $this->resetValidation();
        $this->showTaskForm = true;
    }

    public function closeTaskForm(): void
    {
        $this->showTaskForm = false;
        $this->resetTaskForm();
    }

    public function saveTask(): void
    {
        $this->ensureEditable();

        $this->validate([
            'taskTitle' => ['required', 'string', 'max:160'],
            'taskNotes' => ['nullable', 'string', 'max:2000'],
            'taskDueOn' => ['nullable', 'date'],
            'taskIsGate' => ['boolean'],
        ], attributes: [
            'taskTitle' => __('phases.task_title'),
            'taskNotes' => __('phases.task_notes'),
            'taskDueOn' => __('phases.task_due_on'),
        ]);

        $attributes = [
            'title' => $this->taskTitle,
            'notes' => $this->taskNotes !== '' ? $this->taskNotes : null,
            'due_on' => $this->taskDueOn !== '' ? $this->taskDueOn : null,
            'is_gate' => $this->taskIsGate,
        ];

        if ($this->editingTaskId !== null) {
            $this->findTask($this->editingTaskId)->update($attributes);
        } else {
            $phase = $this->phase();
            $phase->tasks()->create($attributes + [
                'position' => (int) $phase->tasks()->max('position') + 1,
            ]);
        }

        $this->closeTaskForm();
        $this->toastSuccess(__('phases.task_saved'));
    }

    public function confirmDeleteTask(int $taskId): void
    {
        $this->ensureEditable();
        $this->deletingTaskId = $this->findTask($taskId)->id;
    }

    public function cancelDeleteTask(): void
    {
        $this->deletingTaskId = null;
    }

    public function deleteTask(): void
    {
        $this->ensureEditable();

        if ($this->deletingTaskId !== null) {
            $this->findTask($this->deletingTaskId)->delete();
            $this->toastSuccess(__('phases.task_deleted'));
        }

        $this->deletingTaskId = null;
    }

    /**
     * Moves a task one place up or down within the phase.
     */
    public function moveTask(int $taskId, string $direction): void
    {
        $this->ensureEditable();

        $tasks = $this->phase()->tasks()->get()->values();
        $index = $tasks->search(fn (PhaseTask $t) => $t->id === $taskId);

        if ($index === false) {
            abort(404);
        }

        $swapWith = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($tasks[$swapWith])) {
            return;
        }

        $ordered = $tasks->all();
        [$ordered[$index], $ordered[$swapWith]] = [$ordered[$swapWith], $ordered[$index]];

        DB::transaction(function () use ($ordered): void {
            foreach ($ordered as $position => $task) {
                $task->forceFill(['position' => $position + 1])->save();
            }
        });
    }

    // ── Decisions ──────────────────────────────────────────────────────

    public function openDecisionCreate(): void
    {
        $this->resetDecisionForm();
        $this->decidedOn = today()->toDateString();
        $this->showDecisionForm = true;
    }

    public function openDecisionEdit(int $decisionId): void
    {
        $decision = $this->findDecision($decisionId);

        $this->editingDecisionId = $decision->id;
        $this->decisionTitle = $decision->title;
        $this->decisionReason = (string) $decision->reason;
        $this->decidedOn = $decision->decided_on->toDateString();
        $this->resetValidation();
        $this->showDecisionForm = true;
    }

    public function closeDecisionForm(): void
    {
        $this->showDecisionForm = false;
        $this->resetDecisionForm();
    }

    public function saveDecision(): void
    {
        $this->validate([
            'decisionTitle' => ['required', 'string', 'max:160'],
            'decisionReason' => ['nullable', 'string', 'max:2000'],
            'decidedOn' => ['required', 'date'],
        ], attributes: [
            'decisionTitle' => __('phases.decision_title'),
            'decisionReason' => __('phases.decision_reason'),
            'decidedOn' => __('phases.decided_on'),
        ]);

        $attributes = [
            'title' => $this->decisionTitle,
            'reason' => $this->decisionReason !== '' ? $this->decisionReason : null,
            'decided_on' => $this->decidedOn,
        ];

        if ($this->editingDecisionId !== null) {
            $this->findDecision($this->editingDecisionId)->update($attributes);
        } else {
            auth()->user()->business->decisions()->create($attributes + ['phase_id' => $this->phaseId]);
        }

        $this->closeDecisionForm();
        $this->toastSuccess(__('phases.decision_saved'));
    }

    public function confirmDeleteDecision(int $decisionId): void
    {
        $this->deletingDecisionId = $this->findDecision($decisionId)->id;
    }

    public function cancelDeleteDecision(): void
    {
        $this->deletingDecisionId = null;
    }

    public function deleteDecision(): void
    {
        if ($this->deletingDecisionId !== null) {
            $this->findDecision($this->deletingDecisionId)->delete();
            $this->toastSuccess(__('phases.decision_deleted'));
        }

        $this->deletingDecisionId = null;
    }

    // ── Closing and reopening ──────────────────────────────────────────

    public function confirmComplete(): void
    {
        $this->confirmingComplete = true;
    }

    public function cancelComplete(): void
    {
        $this->confirmingComplete = false;
    }

    public function completePhase(CompletePhase $complete): void
    {
        $this->confirmingComplete = false;
        $phase = $this->phase();

        try {
            $complete->handle($phase);
        } catch (PhaseException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastSuccess(__('phases.phase_completed', ['name' => $phase->name()]));
    }

    public function reopenPhase(ReopenPhase $reopen): void
    {
        $phase = $this->phase();

        try {
            $reopen->handle($phase);
        } catch (PhaseException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastInfo(__('phases.phase_reopened', ['name' => $phase->name()]));
    }

    /**
     * A form inside the step saved something: redraw so the requirements
     * and progress follow.
     */
    #[On('step-updated')]
    public function refreshStep(): void {}

    // ── Rendering ──────────────────────────────────────────────────────

    public function render(CompletePhase $complete, ReopenPhase $reopen): View
    {
        $phase = $this->phase();
        $board = PhaseBoard::containing($phase);
        $phase = $board->phases()->firstWhere('id', $this->phaseId);

        return view('livewire.phases.phase-show', [
            'board' => $board,
            'collection' => $phase->collection,
            'phase' => $phase,
            'status' => $board->status($phase),
            'tasks' => $phase->tasks()->get(),
            'decisions' => $phase->decisions()->get(),
            'requirements' => StepRequirements::for($phase),
            'completeBlocker' => $complete->blocker($phase),
            'reopenBlocker' => $phase->isCompleted() ? $reopen->blocker($phase) : null,
            'deletingTask' => $this->deletingTaskId ? PhaseTask::find($this->deletingTaskId) : null,
            'deletingDecision' => $this->deletingDecisionId ? Decision::find($this->deletingDecisionId) : null,
        ])->title($phase->name());
    }

    private function phase(): Phase
    {
        return auth()->user()->business->allPhases()->findOrFail($this->phaseId);
    }

    private function findTask(int $taskId): PhaseTask
    {
        return PhaseTask::query()->where('phase_id', $this->phaseId)->findOrFail($taskId);
    }

    private function findDecision(int $decisionId): Decision
    {
        return auth()->user()->business->decisions()
            ->where('phase_id', $this->phaseId)
            ->findOrFail($decisionId);
    }

    private function ensureEditable(): void
    {
        abort_if($this->phase()->isCompleted(), 403);
    }

    private function resetTaskForm(): void
    {
        $this->reset('editingTaskId', 'taskTitle', 'taskNotes', 'taskDueOn', 'taskIsGate');
        $this->resetValidation();
    }

    private function resetDecisionForm(): void
    {
        $this->reset('editingDecisionId', 'decisionTitle', 'decisionReason', 'decidedOn');
        $this->resetValidation();
    }
}
