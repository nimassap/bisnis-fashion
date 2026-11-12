<div class="space-y-6">
    <a href="{{ route('collections.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground">
        <x-icon name="chevron-left" class="size-4" />
        <span>{{ __('collections.title') }}</span>
    </a>

    <x-page-header
        :eyebrow="__('collections.number', ['number' => $collection->number])"
        :title="$collection->name ?? __('collections.unnamed')"
        :subtitle="__('collections.couple_hint')"
    >
        <button type="button" class="btn-secondary btn-sm" wire:click="openEdit">
            <x-icon name="pencil" class="size-4" />
            <span>{{ __('common.edit') }}</span>
        </button>
        <button type="button" class="btn-ghost btn-sm text-danger" wire:click="confirmDelete">
            <x-icon name="trash" class="size-4" />
            <span class="sr-only">{{ __('collections.delete') }}</span>
        </button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('dashboard.current_phase') }}</p>
            @if ($board->isFinished())
                <p class="text-xl font-bold text-success">{{ __('collections.launched') }}</p>
            @else
                <a href="{{ $board->current()->url() }}" wire:navigate class="text-xl font-bold text-foreground hover:text-primary">{{ $board->current()->name() }}</a>
            @endif
            <p class="text-xs text-muted-foreground">{{ __('dashboard.phases_done', ['done' => $board->completedCount(), 'total' => $board->phases()->count()]) }}</p>
        </div>

        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('dashboard.overall_progress') }}</p>
            <p class="numeric text-3xl font-bold text-foreground">{{ $board->overallProgress() }}%</p>
            <x-progress-bar :percentage="$board->overallProgress()" size="sm" :label="__('dashboard.overall_progress')" />
        </div>

        <div class="panel flex flex-col gap-2 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('collections.target_launch_on') }}</p>
            <x-launch-countdown :collection="$collection" :finished="$board->isFinished()" class="text-sm" />
            @if ($board->overdueCount())
                <p class="text-xs font-semibold text-danger">{{ trans_choice('phases.overdue_count', $board->overdueCount(), ['count' => $board->overdueCount()]) }}</p>
            @endif
        </div>
    </div>

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="card-title">{{ __('collections.couple_set') }}</h2>
            <div class="flex flex-wrap gap-2">
                @if ($collection->products->isNotEmpty())
                    <a href="{{ route('collections.pricing', $collection) }}" wire:navigate class="btn-secondary btn-sm">
                        <x-icon name="hand-coins" class="size-4" />
                        <span>{{ __('navigation.pricing') }}</span>
                    </a>
                @endif
                <a href="{{ route('collections.budget', $collection) }}" wire:navigate class="btn-secondary btn-sm">
                    <x-icon name="wallet" class="size-4" />
                    <span>{{ __('budgets.title') }}</span>
                </a>
            </div>
        </div>
        <x-couple-set :collection="$collection" />
    </section>

    <section class="space-y-3">
        <h2 class="card-title">{{ __('collections.phases') }}</h2>
        <x-phase-stepper :board="$board" />
    </section>

    @if ($collection->notes)
        <section class="panel-padded">
            <h2 class="card-title">{{ __('collections.notes') }}</h2>
            <p class="mt-2 text-sm text-pretty whitespace-pre-line text-muted-foreground">{{ $collection->notes }}</p>
        </section>
    @endif

    <x-modal :open="$showForm" close="closeForm" :title="__('collections.edit')">
        @include('livewire.collections.collection-form', ['withNotes' => true])

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="collection-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$confirmingDelete"
        close="cancelDelete"
        confirm="delete"
        :title="__('collections.delete')"
        :body="__('collections.delete_body', ['label' => $collection->label()])"
    />
</div>
