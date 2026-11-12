<?php

declare(strict_types=1);

namespace App\Livewire\Payments;

use App\Actions\Cashbook\SettleVendorPayment;
use App\Enums\CashType;
use App\Livewire\Concerns\SendsToasts;
use App\Models\VendorPayment;
use App\Rules\MoneyAmount;
use App\Support\Money;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Payments owed to vendors on a schedule, soonest first. Paying one writes
 * it into the cash book.
 */
#[Layout('components.layouts.app')]
final class PaymentsIndex extends Component
{
    use SendsToasts;

    #[Url(as: 'status')]
    public string $tab = 'unpaid';

    public bool $showForm = false;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $deletingId = null;

    #[Locked]
    public ?int $payingId = null;

    public string $title = '';

    public string $amount = '';

    public string $dueOn = '';

    public string $vendorId = '';

    public string $collectionId = '';

    public string $categoryId = '';

    public string $notes = '';

    public string $paidOn = '';

    public function openCreate(): void
    {
        $this->resetForm();
        $this->dueOn = today()->addWeek()->toDateString();
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $payment = $this->find($id);
        $this->resetForm();

        $this->editingId = $payment->id;
        $this->title = $payment->title;
        $this->amount = Money::grouped($payment->amount);
        $this->dueOn = $payment->due_on->toDateString();
        $this->vendorId = (string) $payment->vendor_id;
        $this->collectionId = (string) $payment->collection_id;
        $this->categoryId = (string) $payment->cash_category_id;
        $this->notes = (string) $payment->notes;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function save(): void
    {
        $business = auth()->user()->business;

        $this->validate([
            'title' => ['required', 'string', 'max:120'],
            'amount' => ['required', new MoneyAmount],
            'dueOn' => ['required', 'date'],
            'vendorId' => ['nullable', Rule::exists('vendors', 'id')->where('business_id', $business->id)],
            'collectionId' => ['nullable', Rule::exists('collections', 'id')->where('business_id', $business->id)],
            'categoryId' => ['nullable', Rule::exists('cash_categories', 'id')->where('business_id', $business->id)->where('type', CashType::Out->value)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'title' => __('payments.title_field'),
            'amount' => __('payments.amount'),
            'dueOn' => __('payments.due_on'),
            'vendorId' => __('payments.vendor'),
            'collectionId' => __('payments.collection'),
            'categoryId' => __('payments.category'),
        ]);

        $attributes = [
            'title' => trim($this->title),
            'amount' => Money::parse($this->amount),
            'due_on' => $this->dueOn,
            'vendor_id' => $this->blankToNull($this->vendorId),
            'collection_id' => $this->blankToNull($this->collectionId),
            'cash_category_id' => $this->blankToNull($this->categoryId),
            'notes' => $this->blankToNull($this->notes),
        ];

        if ($this->editingId !== null) {
            $payment = $this->find($this->editingId);
            $payment->update($attributes);

            // Keep the cash book entry of a paid payment in step.
            $payment->entry?->update([
                'amount' => $payment->amount,
                'description' => $payment->title,
                'cash_category_id' => $payment->cash_category_id,
                'collection_id' => $payment->collection_id,
                'vendor_id' => $payment->vendor_id,
            ]);
        } else {
            $business->vendorPayments()->create($attributes);
        }

        $this->showForm = false;
        $this->toastSuccess(__('payments.saved'));
    }

    public function openPay(int $id): void
    {
        $this->payingId = $this->find($id)->id;
        $this->paidOn = today()->toDateString();
        $this->resetValidation();
    }

    public function closePay(): void
    {
        $this->payingId = null;
    }

    public function pay(SettleVendorPayment $settle): void
    {
        $this->validate(['paidOn' => ['required', 'date']], attributes: ['paidOn' => __('payments.paid_on')]);

        if ($this->payingId !== null) {
            $settle->pay($this->find($this->payingId), $this->paidOn);
            $this->toastSuccess(__('payments.paid'));
        }

        $this->payingId = null;
    }

    public function undoPay(int $id, SettleVendorPayment $settle): void
    {
        $settle->undo($this->find($id));
        $this->toastInfo(__('payments.unpaid'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $this->find($id)->id;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId !== null) {
            // A paid payment's cash book entry stays: that money did leave.
            $this->find($this->deletingId)->delete();
            $this->toastSuccess(__('payments.deleted'));
        }

        $this->deletingId = null;
    }

    public function render(): View
    {
        $business = auth()->user()->business;
        $paid = $this->tab === 'paid';

        $payments = $business->vendorPayments()
            ->with(['vendor', 'collection', 'category'])
            ->when($paid, fn ($q) => $q->whereNotNull('paid_on')->orderByDesc('paid_on'))
            ->when(! $paid, fn ($q) => $q->whereNull('paid_on')->orderBy('due_on'))
            ->orderBy('id')
            ->get();

        $unpaid = $business->vendorPayments()->unpaid();

        return view('livewire.payments.payments-index', [
            'payments' => $payments,
            'unpaidTotal' => bcadd((string) (clone $unpaid)->sum('amount'), '0', 2),
            'overdueTotal' => bcadd((string) (clone $unpaid)->whereDate('due_on', '<', today())->sum('amount'), '0', 2),
            'counts' => [
                'unpaid' => (clone $unpaid)->count(),
                'paid' => $business->vendorPayments()->whereNotNull('paid_on')->count(),
            ],
            'vendors' => $business->vendors()->orderBy('name')->get(),
            'collections' => $business->collections()->get(),
            'categories' => $business->cashCategories()->where('type', CashType::Out)->get(),
            'paying' => $this->payingId ? VendorPayment::find($this->payingId) : null,
            'deleting' => $this->deletingId ? VendorPayment::find($this->deletingId) : null,
        ])->title(__('payments.title'));
    }

    private function find(int $id): VendorPayment
    {
        return auth()->user()->business->vendorPayments()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'title', 'amount', 'dueOn', 'vendorId', 'collectionId', 'categoryId', 'notes');
        $this->resetValidation();
    }

    private function blankToNull(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }
}
