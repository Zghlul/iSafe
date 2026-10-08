<?php

namespace App\Http\Controllers;

use App\Services\StockReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockReportController extends Controller
{
    public function show(Request $request, string $report, StockReportService $reports): View
    {
        abort_unless(array_key_exists($report, StockReportService::REPORTS), 404);
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return view('stock-reports.show', [
            ...$reports->build($report, $filters),
            'filters' => $filters,
        ]);
    }
}
