<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\Collection;
use App\Services\PhaseBoard;
use App\Services\Sales\SalesReport;
use App\Services\Sales\StockReport;
use App\Services\TaskAgenda;
use App\Support\DateRange;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
final class DashboardPage extends Component
{
    public function render(): View
    {
        $business = auth()->user()->business;
        $agenda = new TaskAgenda($business);
        $brandPhase = PhaseBoard::forBrand($business)->phases()->first();

        $collections = $business->collections()->get()->map(fn (Collection $collection) => (object) [
            'collection' => $collection,
            'board' => PhaseBoard::forCollection($collection),
        ]);

        // The soonest launch still ahead among collections not yet launched.
        $nextLaunch = $collections
            ->reject(fn (object $row) => $row->board->isFinished() || $row->collection->target_launch_on === null)
            ->sortBy(fn (object $row) => $row->collection->target_launch_on)
            ->first();

        return view('livewire.dashboard.dashboard-page', [
            'business' => $business,
            'brandPhase' => $brandPhase,
            'inProgress' => $collections->reject(fn (object $row) => $row->board->isFinished())->values(),
            'launchedCount' => $collections->filter(fn (object $row) => $row->board->isFinished())->count(),
            'nextLaunch' => $nextLaunch,
            'overdueCount' => $agenda->overdueCount(),
            'overdueTasks' => $agenda->overdue(),
            'upcomingTasks' => $agenda->upcoming(),
            'cashBalance' => $business->cashBalance(),
            'monthSales' => new SalesReport($business, DateRange::monthOf(today())),
            'lowStockCount' => (new StockReport($business))->lowStock()->count(),
            // Overdue, plus whatever falls due within the lookahead.
            'duePayments' => $business->vendorPayments()
                ->unpaid()
                ->whereDate('due_on', '<=', today()->addDays((int) config('cashbook.payment_lookahead_days')))
                ->with('vendor')
                ->orderBy('due_on')
                ->limit(5)
                ->get(),
        ])->title(__('navigation.dashboard'));
    }
}
