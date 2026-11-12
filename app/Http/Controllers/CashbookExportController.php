<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CashType;
use App\Models\CashEntry;
use App\Services\Exports\TabularExport;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The cash book as CSV or Excel, with the same filters the page had.
 */
final class CashbookExportController extends Controller
{
    public function __invoke(Request $request, TabularExport $export, string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx'], true), 404);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'jenis' => ['nullable', 'in:in,out'],
            'koleksi' => ['nullable', 'integer'],
            'kategori' => ['nullable', 'integer'],
        ]);

        $business = $request->user()->business;
        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);

        $query = CashEntry::query()
            ->where('business_id', $business->id)
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()])
            ->when($validated['jenis'] ?? null, fn (Builder $q, string $type) => $q->where('type', $type))
            ->when($validated['koleksi'] ?? null, fn (Builder $q, $id) => $q->where('collection_id', $id))
            ->when($validated['kategori'] ?? null, fn (Builder $q, $id) => $q->where('cash_category_id', $id))
            ->with(['category', 'collection', 'vendor'])
            ->orderBy('occurred_on')
            ->orderBy('id');

        $headings = [
            __('cashbook.columns.date'),
            __('cashbook.columns.type'),
            __('cashbook.columns.category'),
            __('cashbook.columns.description'),
            __('cashbook.columns.collection'),
            __('cashbook.columns.vendor'),
            __('cashbook.columns.notes'),
            __('cashbook.columns.amount'),
        ];

        $rows = function () use ($query): Generator {
            $limit = (int) config('cashbook.export.max_rows');
            $emitted = 0;

            foreach ($query->lazy((int) config('cashbook.export.chunk_size')) as $entry) {
                if (++$emitted > $limit) {
                    return;
                }

                yield [
                    $entry->occurred_on->toDateString(),
                    $entry->type->label(),
                    $entry->category?->name ?? __('cashbook.uncategorised'),
                    $entry->description,
                    $entry->collection?->label() ?? '',
                    $entry->vendor?->name ?? '',
                    (string) $entry->notes,
                    // Signed number, so a spreadsheet can add the column up.
                    ($entry->type === CashType::Out ? -1 : 1) * (float) $entry->amount,
                ];
            }
        };

        $filename = 'buku-kas-'.$from->format('Ymd').'-'.$to->format('Ymd');

        return $format === 'csv'
            ? $export->csv($filename, $headings, $rows)
            : $export->xlsx($filename, $headings, $rows);
    }
}
