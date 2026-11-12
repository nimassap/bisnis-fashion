<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PeriodType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * An inclusive range of calendar dates plus the intent behind it.
 *
 * Knowing whether a range came from "a month", "a year" or a hand-picked span
 * is what lets the app pick a sensible period to compare against.
 */
final readonly class DateRange
{
    private function __construct(
        private CarbonImmutable $start,
        private CarbonImmutable $end,
        private PeriodType $type,
    ) {
        if ($this->start->greaterThan($this->end)) {
            throw new InvalidArgumentException('A date range cannot end before it starts.');
        }
    }

    public static function month(int $year, int $month): self
    {
        $start = CarbonImmutable::create($year, $month, 1)->startOfDay();

        return new self($start, $start->endOfMonth()->startOfDay(), PeriodType::Month);
    }

    public static function year(int $year): self
    {
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();

        return new self($start, $start->endOfYear()->startOfDay(), PeriodType::Year);
    }

    public static function custom(CarbonInterface $start, CarbonInterface $end): self
    {
        return new self(
            CarbonImmutable::parse($start)->startOfDay(),
            CarbonImmutable::parse($end)->startOfDay(),
            PeriodType::Custom,
        );
    }

    public static function monthOf(CarbonInterface $date): self
    {
        return self::month((int) $date->year, (int) $date->month);
    }

    public function start(): CarbonImmutable
    {
        return $this->start;
    }

    public function end(): CarbonImmutable
    {
        return $this->end;
    }

    public function type(): PeriodType
    {
        return $this->type;
    }

    /**
     * The period this one is naturally compared against: the month before, the
     * year before, or an equally long span ending the day before this one.
     */
    public function previous(): self
    {
        return match ($this->type) {
            PeriodType::Month => self::month(
                (int) $this->start->subMonthNoOverflow()->year,
                (int) $this->start->subMonthNoOverflow()->month,
            ),
            PeriodType::Year => self::year((int) $this->start->year - 1),
            PeriodType::Custom => self::custom(
                $this->start->subDays($this->lengthInDays()),
                $this->start->subDay(),
            ),
        };
    }

    public function lengthInDays(): int
    {
        return (int) $this->start->diffInDays($this->end) + 1;
    }

    public function contains(CarbonInterface $date): bool
    {
        return CarbonImmutable::parse($date)->startOfDay()->betweenIncluded($this->start, $this->end);
    }

    /**
     * Every month the range touches, as the first day of that month. Used to
     * build the month-by-month series behind the trend charts.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function eachMonth(): Collection
    {
        $months = collect();
        $cursor = $this->start->startOfMonth();
        $last = $this->end->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last)) {
            $months->push($cursor);
            $cursor = $cursor->addMonthNoOverflow();
        }

        return $months;
    }

    /**
     * A range covering the given number of months back from this range's end,
     * used for rolling trend charts.
     */
    public function trailingMonths(int $months): self
    {
        return self::custom(
            $this->end->startOfMonth()->subMonthsNoOverflow(max(0, $months - 1)),
            $this->end->endOfMonth()->startOfDay(),
        );
    }

    /**
     * @return array{start: string, end: string, type: string}
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'type' => $this->type->value,
        ];
    }
}
