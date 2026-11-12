<div class="space-y-6">
    <x-page-header :title="__('collections.title')" :subtitle="__('collections.subtitle')">
        <button type="button" class="btn-primary" wire:click="openCreate">
            <x-icon name="plus" class="size-4" />
            <span>{{ __('collections.add') }}</span>
        </button>
    </x-page-header>

    @if ($rows->isEmpty())
        <div class="panel">
            <x-empty-state icon="shirt" :title="__('collections.empty')" :body="__('collections.empty_hint')">
                <button type="button" class="btn-primary btn-sm" wire:click="openCreate">{{ __('collections.add') }}</button>
            </x-empty-state>
        </div>
    @else
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach ($rows as $row)
                @php
                    $collection = $row->collection;
                    $board = $row->board;
                    $active = $board->current();
                @endphp

                <li wire:key="collection-{{ $collection->id }}">
                    <a href="{{ route('collections.show', $collection) }}" wire:navigate class="panel flex h-full flex-col gap-4 p-4 transition hover:-translate-y-0.5 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-muted-foreground">{{ __('collections.number', ['number' => $collection->number]) }}</p>
                                <p class="truncate text-base font-bold text-foreground">{{ $collection->name ?? __('collections.unnamed') }}</p>
                            </div>

                            @if ($board->isFinished())
                                <x-status-badge tone="success" icon="check-circle" :label="__('collections.launched')" />
                            @else
                                <x-status-badge tone="primary" :label="$active->name()" />
                            @endif
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span>{{ __('dashboard.phases_done', ['done' => $board->completedCount(), 'total' => $board->phases()->count()]) }}</span>
                                <span class="numeric">{{ $board->overallProgress() }}%</span>
                            </div>
                            <x-progress-bar :percentage="$board->overallProgress()" :tone="$board->isFinished() ? 'success' : 'primary'" size="sm" :label="$collection->label()" />
                        </div>

                        <div class="mt-auto flex flex-wrap items-center justify-between gap-2">
                            <x-launch-countdown :collection="$collection" :finished="$board->isFinished()" />
                            @if ($board->overdueCount())
                                <span class="text-xs font-semibold text-danger">{{ trans_choice('phases.overdue_count', $board->overdueCount(), ['count' => $board->overdueCount()]) }}</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    <x-modal :open="$showForm" close="closeForm" :title="__('collections.add')" :description="__('collections.add_hint')">
        @include('livewire.collections.collection-form')

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="collection-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('collections.add') }}</button>
        </x-slot:footer>
    </x-modal>
</div>
