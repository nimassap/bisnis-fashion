@php
    $editable = ! $phase->isCompleted();
@endphp

<div class="space-y-6">
    @if ($collection)
        <a href="{{ route('collections.show', $collection) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
            <x-icon name="chevron-left" class="size-4" />
            <span>{{ $collection->label() }}</span>
        </a>
    @endif

    <x-page-header
        :eyebrow="$phase->positionLabel()"
        :title="$phase->name()"
        :subtitle="$phase->isBrandPhase() ? __('phases.brand_subtitle') : $phase->summary()"
    >
        <x-phase-status :status="$status" />
    </x-page-header>

    @if ($collection)
        <x-phase-stepper :board="$board" :current="$phase" />
    @endif

    {{-- Progress and closing --}}
    <section class="panel-padded space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
            <span class="numeric font-semibold text-foreground">{{ __('phases.progress', $phase->progressCounts()) }}</span>
            <span class="numeric text-muted-foreground">{{ $phase->progress() }}%</span>
        </div>
        <x-progress-bar :percentage="$phase->progress()" :tone="$phase->isCompleted() ? 'success' : 'primary'" :label="$phase->name()" />

        @if ($phase->isCompleted())
            <div class="flex flex-col gap-3 rounded-xl border border-success/40 bg-success-soft p-3.5 sm:flex-row sm:items-center sm:justify-between">
                <p class="flex items-start gap-2 text-sm text-success">
                    <x-icon name="check-circle" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ __('phases.completed_on', ['date' => $phase->completed_at->translatedFormat('j F Y')]) }}</span>
                </p>

                @if ($reopenBlocker)
                    <p class="text-xs text-muted-foreground">{{ $reopenBlocker }}</p>
                @else
                    <button type="button" class="btn-secondary btn-sm shrink-0" wire:click="reopenPhase" wire:loading.attr="disabled">
                        <x-icon name="refresh" class="size-4" />
                        <span>{{ __('phases.reopen_phase') }}</span>
                    </button>
                @endif
            </div>
        @else
            <div class="flex flex-col gap-3 rounded-xl border p-3.5 sm:flex-row sm:items-center sm:justify-between {{ $completeBlocker ? 'border-hairline bg-surface' : 'border-success/40 bg-success-soft' }}">
                <p class="flex items-start gap-2 text-sm {{ $completeBlocker ? 'text-muted-foreground' : 'text-success' }}">
                    <x-icon :name="$completeBlocker ? 'lock' : 'check-circle'" class="mt-0.5 size-4 shrink-0" />
                    <span>{{ $completeBlocker ?? __('phases.ready_to_complete') }}</span>
                </p>

                <button
                    type="button"
                    class="btn-primary btn-sm shrink-0"
                    wire:click="confirmComplete"
                    @disabled($completeBlocker !== null)
                >
                    <x-icon name="check" class="size-4" />
                    <span>{{ __('phases.complete_phase') }}</span>
                </button>
            </div>
        @endif
    </section>

    {{-- What this step needs, read from the data below. --}}
    @if ($requirements !== [])
        <section class="panel-padded space-y-3">
            <h2 class="card-title">{{ __('steps.requirements_title') }}</h2>
            <ul class="grid gap-2 sm:grid-cols-2">
                @foreach ($requirements as $requirement)
                    <li class="flex items-start gap-2 text-sm {{ $requirement->met ? 'text-foreground' : 'text-muted-foreground' }}">
                        <x-icon :name="$requirement->met ? 'check-circle' : 'x-circle'" class="mt-0.5 size-4 shrink-0 {{ $requirement->met ? 'text-success' : 'text-muted-foreground' }}" />
                        <span>{{ $requirement->label }}</span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- The step's own forms. --}}
    @if ($phase->key === 'setup')
        <livewire:setup.brand-setup :key="'brand-setup-'.$phase->id" />
    @elseif (view()->exists('livewire.phases.steps.'.$phase->key))
        @include('livewire.phases.steps.'.$phase->key)
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- Checklist --}}
        <section class="panel-padded lg:col-span-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">{{ __('phases.checklist') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('phases.checklist_hint') }}</p>
                </div>

                @if ($editable)
                    <button type="button" class="btn-secondary btn-sm shrink-0" wire:click="openTaskCreate">
                        <x-icon name="plus" class="size-4" />
                        <span>{{ __('phases.add_task') }}</span>
                    </button>
                @endif
            </div>

            @if ($tasks->isEmpty())
                <x-empty-state icon="list" :title="__('phases.no_tasks')" compact />
            @else
                <ul class="mt-4 divide-y divide-hairline">
                    @foreach ($tasks as $task)
                        <li class="flex items-start gap-3 py-3" wire:key="task-{{ $task->id }}">
                            <button
                                type="button"
                                wire:click="toggleTask({{ $task->id }})"
                                @disabled(! $editable)
                                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-md border-2 transition disabled:cursor-not-allowed disabled:opacity-60 {{ $task->isDone() ? 'border-success bg-success text-success-foreground' : 'border-border hover:border-primary' }}"
                                role="checkbox"
                                aria-checked="{{ $task->isDone() ? 'true' : 'false' }}"
                                aria-label="{{ $task->isDone() ? __('phases.mark_undone') : __('phases.mark_done') }}: {{ $task->title }}"
                            >
                                @if ($task->isDone())
                                    <x-icon name="check" class="size-4" />
                                @endif
                            </button>

                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-pretty {{ $task->isDone() ? 'text-muted-foreground line-through' : 'text-foreground' }}">{{ $task->title }}</p>

                                @if ($task->notes)
                                    <p class="mt-0.5 text-xs text-pretty whitespace-pre-line text-muted-foreground">{{ $task->notes }}</p>
                                @endif

                                @if ($task->is_gate || $task->due_on)
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        @if ($task->is_gate)
                                            <x-status-badge tone="warning" icon="lock" :label="__('phases.gate')" />
                                        @endif
                                        @if ($task->isOverdue())
                                            <x-status-badge tone="danger" icon="exclamation-triangle" :label="__('phases.overdue').' · '.$task->due_on->translatedFormat('j M')" />
                                        @elseif ($task->due_on)
                                            <x-status-badge tone="neutral" icon="calendar" :label="__('phases.due_label', ['date' => $task->due_on->translatedFormat('j M Y')])" />
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if ($editable)
                                <x-row-menu>
                                    <x-row-menu-item icon="pencil" wire:click="openTaskEdit({{ $task->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                    @unless ($loop->first)
                                        <x-row-menu-item icon="arrow-up" wire:click="moveTask({{ $task->id }}, 'up')">{{ __('common.move_up') }}</x-row-menu-item>
                                    @endunless
                                    @unless ($loop->last)
                                        <x-row-menu-item icon="arrow-down" wire:click="moveTask({{ $task->id }}, 'down')">{{ __('common.move_down') }}</x-row-menu-item>
                                    @endunless
                                    <x-row-menu-item icon="trash" danger wire:click="confirmDeleteTask({{ $task->id }})">{{ __('common.delete') }}</x-row-menu-item>
                                </x-row-menu>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Decisions --}}
        <section class="panel-padded lg:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="card-title">{{ __('phases.decisions') }}</h2>
                    <p class="card-hint mt-0.5">{{ __('phases.decisions_hint') }}</p>
                </div>

                <button type="button" class="btn-secondary btn-sm shrink-0" wire:click="openDecisionCreate">
                    <x-icon name="plus" class="size-4" />
                    <span class="sr-only sm:not-sr-only">{{ __('phases.add_decision') }}</span>
                </button>
            </div>

            @if ($decisions->isEmpty())
                <x-empty-state icon="history" :title="__('phases.no_decisions')" compact />
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($decisions as $decision)
                        <li class="tile flex items-start gap-3 p-3.5" wire:key="decision-{{ $decision->id }}">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs text-muted-foreground">{{ $decision->decided_on->translatedFormat('j F Y') }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-pretty text-foreground">{{ $decision->title }}</p>
                                @if ($decision->reason)
                                    <p class="mt-1 text-xs text-pretty whitespace-pre-line text-muted-foreground">{{ $decision->reason }}</p>
                                @endif
                            </div>

                            <x-row-menu>
                                <x-row-menu-item icon="pencil" wire:click="openDecisionEdit({{ $decision->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                <x-row-menu-item icon="trash" danger wire:click="confirmDeleteDecision({{ $decision->id }})">{{ __('common.delete') }}</x-row-menu-item>
                            </x-row-menu>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- Task form --}}
    <x-modal :open="$showTaskForm" close="closeTaskForm" :title="$editingTaskId ? __('phases.edit_task') : __('phases.add_task')">
        <form id="task-form" wire:submit="saveTask" class="space-y-5">
            <x-field :label="__('phases.task_title')" for="taskTitle" :error="$errors->first('taskTitle')" required>
                <input type="text" id="taskTitle" wire:model="taskTitle" class="input @error('taskTitle') input-invalid @enderror" autofocus />
            </x-field>

            <x-field :label="__('phases.task_due_on')" for="taskDueOn" :error="$errors->first('taskDueOn')">
                <x-date-input model="taskDueOn" :value="$taskDueOn" :invalid="$errors->has('taskDueOn')" />
            </x-field>

            <x-field :label="__('phases.task_notes')" for="taskNotes" :error="$errors->first('taskNotes')">
                <textarea id="taskNotes" wire:model="taskNotes" rows="3" class="input @error('taskNotes') input-invalid @enderror"></textarea>
            </x-field>

            <label for="taskIsGate" class="flex cursor-pointer items-start gap-2.5 text-sm text-foreground">
                <input type="checkbox" id="taskIsGate" wire:model="taskIsGate" class="checkbox mt-0.5" />
                <span>
                    <span class="font-medium">{{ __('phases.task_is_gate') }}</span>
                    <span class="block text-xs text-muted-foreground">{{ __('phases.gate_hint') }}</span>
                </span>
            </label>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeTaskForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="task-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="saveTask">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    {{-- Decision form --}}
    <x-modal :open="$showDecisionForm" close="closeDecisionForm" :title="$editingDecisionId ? __('phases.edit_decision') : __('phases.add_decision')">
        <form id="decision-form" wire:submit="saveDecision" class="space-y-5">
            <x-field :label="__('phases.decision_title')" for="decisionTitle" :error="$errors->first('decisionTitle')" required>
                <input type="text" id="decisionTitle" wire:model="decisionTitle" placeholder="{{ __('phases.decision_title_placeholder') }}" class="input @error('decisionTitle') input-invalid @enderror" autofocus />
            </x-field>

            <x-field :label="__('phases.decision_reason')" for="decisionReason" :error="$errors->first('decisionReason')">
                <textarea id="decisionReason" wire:model="decisionReason" rows="4" placeholder="{{ __('phases.decision_reason_placeholder') }}" class="input @error('decisionReason') input-invalid @enderror"></textarea>
            </x-field>

            <x-field :label="__('phases.decided_on')" for="decidedOn" :error="$errors->first('decidedOn')" required>
                <x-date-input model="decidedOn" :value="$decidedOn" :invalid="$errors->has('decidedOn')" />
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeDecisionForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="decision-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="saveDecision">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deletingTask !== null"
        close="cancelDeleteTask"
        confirm="deleteTask"
        :title="__('phases.delete_task')"
        :body="$deletingTask ? __('phases.delete_task_body', ['title' => $deletingTask->title]) : null"
    />

    <x-confirm-dialog
        :open="$deletingDecision !== null"
        close="cancelDeleteDecision"
        confirm="deleteDecision"
        :title="__('phases.delete_decision')"
        :body="$deletingDecision ? __('phases.delete_decision_body', ['title' => $deletingDecision->title]) : null"
    />

    <x-confirm-dialog
        :open="$confirmingComplete"
        close="cancelComplete"
        confirm="completePhase"
        tone="primary"
        :title="__('phases.complete_phase')"
        :body="__('phases.complete_phase_body', ['name' => $phase->name()])"
        :confirm-label="__('phases.complete_phase')"
    />
</div>
