<?php

namespace App\Http\Controllers;

use App\Exports\ReportXlsxExport;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function show(Request $request, string $report, ReportService $reports): View
    {
        abort_unless(array_key_exists($report, ReportService::REPORTS), 404);

        $filters = $this->validatedFilters($request, $report);

        return view('reports.show', [
            ...$reports->build($report, $filters),
            'reportOptions' => ReportService::REPORTS,
        ]);
    }

    public function export(Request $request, string $report, ReportService $reports): BinaryFileResponse
    {
        abort_unless(array_key_exists($report, ReportService::REPORTS), 404);

        $filters = $this->validatedFilters($request, $report);
        $data = $reports->build($report, $filters);
        $rows = $data['rows']->map(fn ($row) => [
            (string) $row->period_label,
            (int) $row->units,
            (int) $row->revenue,
            (int) $row->cost,
            (int) $row->profit,
            round((float) $row->margin, 1),
        ])->all();
        $rows[] = [
            'TOTAL',
            (int) $data['totals']->units,
            (int) $data['totals']->revenue,
            (int) $data['totals']->cost,
            (int) $data['totals']->profit,
            round((float) $data['totals']->margin, 1),
        ];

        return Excel::download(
            new ReportXlsxExport($rows, ['Periode', 'Unit', 'Omzet', 'Modal', 'Keuntungan', 'Margin %']),
            'laporan-'.$report.'-'.$data['from']->format('Y-m-d').'.xlsx',
        );
    }

    private function validatedFilters(Request $request, string $report): array
    {
        $rules = [
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
        ];

        if ($report === 'custom') {
            $rules['from'] = ['required', 'date'];
            $rules['to'] = ['required', 'date', 'after_or_equal:from'];
        }

        return $request->validate($rules);
    }
}
