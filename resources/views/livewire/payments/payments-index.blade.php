<div class="space-y-6">
    <x-page-header :title="__('payments.title')" :subtitle="__('payments.subtitle')">
        <button type="button" class="btn-primary" wire:click="openCreate">
            <x-icon name="plus" class="size-4" />
            <span>{{ __('payments.add') }}</span>
        </button>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('payments.unpaid_total') }}</p>
            <x-amount :value="$unpaidTotal" class="text-2xl font-bold" />
            <p class="text-xs text-muted-foreground">{{ trans_choice('payments.count', $counts['unpaid'], ['count' => $counts['unpaid']]) }}</p>
        </div>
        <div class="panel flex flex-col gap-1.5 p-4 sm:p-5">
            <p class="text-sm font-semibold text-foreground">{{ __('payments.overdue_total') }}</p>
            <x-amount :value="$overdueTotal" @class(['text-2xl font-bold', 'text-danger' => (float) $overdueTotal > 0]) />
            <p class="text-xs text-muted-foreground">{{ __('payments.overdue_hint') }}</p>
        </div>
    </div>

    <x-filter-tabs model="tab" :current="$tab" :options="['unpaid' => __('payments.tab_unpaid'), 'paid' => __('payments.tab_paid')]" :counts="$counts" />

    @if ($payments->isEmpty())
        <div class="panel">
            <x-empty-state icon="receipt" :title="$tab === 'paid' ? __('payments.empty_paid') : __('payments.empty')" :body="$tab === 'paid' ? null : __('payments.empty_hint')" />
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($payments as $payment)
                @php($days = $payment->daysUntilDue())
                <li class="panel flex flex-col gap-3 p-4 sm:flex-row sm:items-center" wire:key="payment-{{ $payment->id }}">
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-foreground">{{ $payment->title }}</p>
                            @if ($payment->isPaid())
                                <x-status-badge tone="success" icon="check-circle" :label="__('payments.paid_on_label', ['date' => $payment->paid_on->translatedFormat('j M Y')])" />
                            @elseif ($payment->isOverdue())
                                <x-status-badge tone="danger" icon="exclamation-triangle" :label="trans_choice('payments.overdue_by', abs($days), ['count' => abs($days)])" />
                            @elseif ($days === 0)
                                <x-status-badge tone="warning" icon="calendar" :label="__('payments.due_today')" />
                            @elseif ($days <= config('cashbook.payment_lookahead_days'))
                                <x-status-badge tone="warning" icon="calendar" :label="trans_choice('payments.due_in', $days, ['count' => $days])" />
                            @endif
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ __('payments.due_label', ['date' => $payment->due_on->translatedFormat('j F Y')]) }}
                            @if ($payment->vendor) · {{ $payment->vendor->name }} @endif
                            @if ($payment->collection) · {{ $payment->collection->label() }} @endif
                            @if ($payment->category) · {{ $payment->category->name }} @endif
                        </p>
                        @if ($payment->notes)
                            <p class="text-xs text-pretty whitespace-pre-line text-foreground">{{ $payment->notes }}</p>
                        @endif
                    </div>

                    <div class="flex items-center justify-between gap-3 sm:justify-end">
                        <x-amount :value="$payment->amount" class="text-lg font-bold" />

                        <div class="flex items-center gap-1">
                            @if ($payment->isPaid())
                                <button type="button" class="btn-ghost btn-sm" wire:click="undoPay({{ $payment->id }})">{{ __('payments.undo_pay') }}</button>
                            @else
                                <button type="button" class="btn-primary btn-sm" wire:click="openPay({{ $payment->id }})">
                                    <x-icon name="check" class="size-4" />
                                    <span>{{ __('payments.pay') }}</span>
                                </button>
                            @endif
                            <x-row-menu>
                                <x-row-menu-item icon="pencil" wire:click="openEdit({{ $payment->id }})">{{ __('common.edit') }}</x-row-menu-item>
                                @if ($payment->vendor?->whatsappUrl())
                                    <a href="{{ $payment->vendor->whatsappUrl() }}" target="_blank" rel="noopener" class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-foreground transition hover:bg-secondary" role="menuitem">
                                        <x-icon name="phone" class="size-4 shrink-0" />
                                        <span>WhatsApp</span>
                                    </a>
                                @endif
                                <x-row-menu-item icon="trash" danger wire:click="confirmDelete({{ $payment->id }})">{{ __('common.delete') }}</x-row-menu-item>
                            </x-row-menu>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <x-modal :open="$showForm" close="closeForm" :title="$editingId ? __('payments.edit') : __('payments.add')" max-width="xl">
        <form id="payment-form" wire:submit="save" class="space-y-5">
            <x-field :label="__('payments.title_field')" for="title" :error="$errors->first('title')" required>
                <input type="text" id="title" wire:model="title" class="input @error('title') input-invalid @enderror" placeholder="{{ __('payments.title_placeholder') }}" autofocus />
            </x-field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('payments.amount')" for="amount" :error="$errors->first('amount')" required>
                    <x-money-input model="amount" :invalid="$errors->has('amount')" />
                </x-field>
                <x-field :label="__('payments.due_on')" for="dueOn" :error="$errors->first('dueOn')" required>
                    <x-date-input model="dueOn" :value="$dueOn" :invalid="$errors->has('dueOn')" />
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field :label="__('payments.vendor')" for="vendorId" :error="$errors->first('vendorId')">
                    <select id="vendorId" wire:model="vendorId" class="select">
                        <option value="">–</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field :label="__('payments.category')" for="categoryId" :error="$errors->first('categoryId')">
                    <select id="categoryId" wire:model="categoryId" class="select">
                        <option value="">{{ __('cashbook.uncategorised') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </x-field>
            </div>

            <x-field :label="__('payments.collection')" for="collectionId" :error="$errors->first('collectionId')">
                <select id="collectionId" wire:model="collectionId" class="select">
                    <option value="">{{ __('cashbook.no_collection') }}</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection->id }}">{{ $collection->label() }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field :label="__('payments.notes')" for="notes" :error="$errors->first('notes')">
                <textarea id="notes" wire:model="notes" rows="2" class="input"></textarea>
            </x-field>
        </form>

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closeForm">{{ __('common.cancel') }}</button>
            <button type="submit" form="payment-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="save">{{ __('common.save') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-modal :open="$paying !== null" close="closePay" :title="__('payments.pay')" :description="__('payments.pay_hint')" max-width="sm">
        @if ($paying)
            <form id="pay-form" wire:submit="pay" class="space-y-4">
                <div class="tile flex items-center justify-between gap-3 p-3.5">
                    <span class="min-w-0 truncate text-sm font-medium">{{ $paying->title }}</span>
                    <x-amount :value="$paying->amount" class="font-bold" />
                </div>
                <x-field :label="__('payments.paid_on')" for="paidOn" :error="$errors->first('paidOn')" required>
                    <x-date-input model="paidOn" :value="$paidOn" :invalid="$errors->has('paidOn')" />
                </x-field>
            </form>
        @endif

        <x-slot:footer>
            <button type="button" class="btn-secondary sm:w-auto" wire:click="closePay">{{ __('common.cancel') }}</button>
            <button type="submit" form="pay-form" class="btn-primary sm:w-auto" wire:loading.attr="disabled" wire:target="pay">{{ __('payments.confirm_pay') }}</button>
        </x-slot:footer>
    </x-modal>

    <x-confirm-dialog
        :open="$deleting !== null"
        close="cancelDelete"
        confirm="delete"
        :title="__('payments.delete')"
        :body="$deleting ? ($deleting->isPaid() ? __('payments.delete_paid_body', ['title' => $deleting->title]) : __('payments.delete_body', ['title' => $deleting->title])) : null"
    />
</div>
