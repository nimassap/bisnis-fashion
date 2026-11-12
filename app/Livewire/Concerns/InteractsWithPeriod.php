<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\PeriodType;
use App\Support\DateRange;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;

/**
 * The month / year / custom-range picker shared by the dashboard, the reports
 * and the household screens.
 *
 * The selection lives in the query string, so a period someone is looking at
 * can be bookmarked and shared, and the back button behaves as expected.
 */
trait InteractsWithPeriod
{
    #[Url(as: 'period', keep: true)]
    public string $periodType = 'month';

    /** Format: YYYY-MM */
    #[Url(as: 'month', keep: true)]
    public string $month = '';

    #[Url(as: 'year', keep: true)]
    public int $year = 0;

    #[Url(as: 'from')]
    public string $startDate = '';

    #[Url(as: 'to')]
    public string $endDate = '';

    public function mountInteractsWithPeriod(): void
    {
        $today = $this->today();

        if ($this->month === '') {
            $this->month = $today->format('Y-m');
        }

        if ($this->year === 0) {
            $this->year = (int) $today->year;
        }

        if ($this->startDate === '') {
            $this->startDate = $today->startOfMonth()->toDateString();
        }

        if ($this->endDate === '') {
            $this->endDate = $today->toDateString();
        }
    }

    /**
     * The range currently selected, falling back to the current month when the
     * query string holds something unusable.
     */
    public function period(): DateRange
    {
        return match ($this->resolvedPeriodType()) {
            PeriodType::Year => DateRange::year($this->year),
            PeriodType::Custom => $this->customRange(),
            PeriodType::Month => $this->monthRange(),
        };
    }

    public function periodLabel(): string
    {
        $period = $this->period();

        return match ($period->type()) {
            PeriodType::Month => $period->start()->translatedFormat('F Y'),
            PeriodType::Year => (string) $period->start()->year,
            PeriodType::Custom => $period->start()->translatedFormat('j M Y').' – '.$period->end()->translatedFormat('j M Y'),
        };
    }

    public function comparisonLabel(): string
    {
        return $this->period()->type()->comparisonLabel();
    }

    /**
     * Reset paging whenever the period changes: page 7 of the previous month
     * rarely exists in the new one.
     */
    public function updatedPeriodType(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatedMonth(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatedYear(): void
    {
        $this->resetPageIfPaginated();
    }

    public function updatedStartDate(): void
    {
        $this->validateCustomRangeOrder();
        $this->resetPageIfPaginated();
    }

    public function updatedEndDate(): void
    {
        $this->validateCustomRangeOrder();
        $this->resetPageIfPaginated();
    }

    /**
     * Whether the custom range reads start-then-end, checked fresh on every
     * change to either field.
     *
     * A `#[Validate]` attribute on each property used to do this, but it only
     * ever revalidates the one property that just changed — fixing a reversed
     * range by moving the *other* date left its error on screen, since
     * nothing had told Livewire the first field's error was stale. Both keys
     * are reset every time either date moves, so a stale message never
     * survives a fix regardless of which field made it — but only endDate
     * gets the error back when the range is still reversed: the end is what
     * reads wrong against a start the person just as easily meant to keep,
     * and one message once is the actual complaint, not the same sentence
     * under both fields.
     */
    private function validateCustomRangeOrder(): void
    {
        $this->resetErrorBag(['startDate', 'endDate']);

        $start = $this->parseDate($this->startDate);
        $end = $this->parseDate($this->endDate);

        if ($start === null || $end === null || ! $start->greaterThan($end)) {
            return;
        }

        $this->addError('endDate', __('common.custom_range_reversed'));
    }

    /**
     * Step to the previous or next month while in month mode.
     */
    public function shiftMonth(int $months): void
    {
        $this->periodType = PeriodType::Month->value;
        $this->month = CarbonImmutable::parse($this->month.'-01')->addMonthsNoOverflow($months)->format('Y-m');

        $this->resetPageIfPaginated();
    }

    public function goToCurrentMonth(): void
    {
        $this->periodType = PeriodType::Month->value;
        $this->month = $this->today()->format('Y-m');

        $this->resetPageIfPaginated();
    }

    /**
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $currentYear = (int) $this->today()->year;

        return range($currentYear, $currentYear - 5);
    }

    /**
     * Today as the signed-in user experiences it, in their own timezone.
     */
    protected function today(): CarbonImmutable
    {
        return CarbonImmutable::now(auth()->user()?->timezone ?? config('app.timezone'))->startOfDay();
    }

    private function resolvedPeriodType(): PeriodType
    {
        return PeriodType::tryFrom($this->periodType) ?? PeriodType::Month;
    }

    private function monthRange(): DateRange
    {
        try {
            $month = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01');
        } catch (\Throwable) {
            return DateRange::monthOf($this->today());
        }

        return DateRange::month((int) $month->year, (int) $month->month);
    }

    private function customRange(): DateRange
    {
        $start = $this->parseDate($this->startDate);
        $end = $this->parseDate($this->endDate);

        // A reversed or unparseable range never gets applied: the live
        // validation on startDate/endDate tells the user why, and this falls
        // back to the current month rather than guessing what they meant.
        if ($start === null || $end === null || $start->greaterThan($end)) {
            return DateRange::monthOf($this->today());
        }

        return DateRange::custom($start, $end);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resetPageIfPaginated(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
