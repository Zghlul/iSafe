<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DashboardService;
use App\Services\StockMetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, StockMetricsService $stockMetrics): View
    {
        $filters = $request->validate([
            'period' => ['nullable', 'in:today,7d,month,year,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return view('dashboard', [
            ...$dashboard->summary($filters, $stockMetrics),
            'period' => $filters['period'] ?? 'month',
            'monthlyTarget' => (int) (Setting::query()->where('key', 'monthly_target')->value('value') ?? 0),
            'storeName' => Setting::query()->where('key', 'store_name')->value('value') ?? config('app.name'),
        ]);
    }
}
