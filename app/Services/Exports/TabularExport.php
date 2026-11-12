<?php

declare(strict_types=1);

namespace App\Services\Exports;

use Generator;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a stream of rows into a downloadable CSV or spreadsheet.
 *
 * Rows arrive from a generator and are written straight to the output stream,
 * so an export of fifty thousand transactions never builds the whole file in
 * memory first.
 */
final class TabularExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  callable(): Generator<int, array<int, string|int|float|null>>  $rows
     */
    public function csv(string $filename, array $headings, callable $rows): StreamedResponse
    {
        return response()->stream(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'wb');

            // A byte-order mark makes Excel open UTF-8 correctly on Windows.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, $headings, escape: '');

            foreach ($rows() as $row) {
                fputcsv($handle, array_map($this->neutralize(...), $row), escape: '');
            }

            fclose($handle);
        }, Response::HTTP_OK, $this->headers("{$filename}.csv", 'text/csv; charset=UTF-8'));
    }

    /**
     * @param  array<int, string>  $headings
     * @param  callable(): Generator<int, array<int, string|int|float|null>>  $rows
     */
    public function xlsx(string $filename, array $headings, callable $rows): StreamedResponse
    {
        return response()->stream(function () use ($headings, $rows): void {
            $writer = new Writer;
            $writer->openToFile('php://output');

            // OpenSpout styles are immutable value objects: bold is set on
            // construction, not with a setter.
            $writer->addRow(Row::fromValuesWithStyle($headings, new Style(fontBold: true)));

            foreach ($rows() as $row) {
                $writer->addRow(Row::fromValues(array_map($this->neutralize(...), $row)));
            }

            $writer->close();
        }, Response::HTTP_OK, $this->headers(
            "{$filename}.xlsx",
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ));
    }

    /**
     * Defuse spreadsheet formula injection.
     *
     * A cell whose text begins with =, +, -, @ or a control character is read
     * as a formula by Excel and Sheets. Since a description or note is free
     * text — and a household member can export a transaction shared with them
     * — a value like `=cmd|'/c calc'!A1` would otherwise run on the machine
     * that opens the file. Prefixing a tab keeps the value readable while
     * stripping its formula meaning; numbers are left untouched so totals
     * still add up.
     */
    private function neutralize(string|int|float|null $value): string|int|float|null
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return str_contains("=+-@\t\r", $value[0]) ? "\t".$value : $value;
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $filename, string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ];
    }
}
