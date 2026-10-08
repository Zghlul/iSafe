<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function summary(array $filters): array
    {
        [$from, $to] = $this->period($filters);
        $duration = max(1, $from->diffInDays($to) + 1);
        $previousTo = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($duration - 1);

        $current = $this->totals($from, $to);
        $previous = $this->totals($previousFrom, $previousTo);
        $paymentMethods = Sale::query()
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->select('payment_method')
            ->selectRaw('COUNT(*) AS units')
            ->groupBy('payment_method')
            ->pluck('units', 'payment_method');

        return [
            'from' => $from,
            'to' => $to,
            'current' => $current,
            'previous' => $previous,
            'changes' => [
                'revenue' => $this->change((int) $previous->revenue, (int) $current->revenue),
                'profit' => $this->change((int) $previous->profit, (int) $current->profit),
                'units' => $this->change((int) $previous->units, (int) $current->units),
                'margin' => $this->change((float) $previous->margin, (float) $current->margin),
            ],
            'money' => [
                'cost' => (int) $current->cost,
                'profit' => (int) $current->profit,
                'new_units' => (int) ($current->new_units ?? 0),
                'used_units' => (int) ($current->used_units ?? 0),
            ],
            'payments' => collect(Sale::PAYMENT_METHODS)->mapWithKeys(
                fn (string $method) => [$method => (int) ($paymentMethods[$method] ?? 0)],
            ),
            'rankings' => [
                'models' => $this->rank('model', $from, $to),
                'sellers' => $this->rank('seller_name', $from, $to),
                'conditions' => $this->rank('condition', $from, $to),
            ],
            'revenueChart' => $this->monthly(),
            'profitChart' => $this->monthlyProfitByCondition(),
            'recentSales' => Sale::query()->latest('sale_date')->latest('id')->limit(8)->get(),
            'lossSales' => Sale::query()
                ->whereBetween('sale_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->where('profit', '<', 0)
                ->latest('sale_date')
                ->limit(8)
                ->get(),
            'monthRevenue' => (int) Sale::query()
                ->whereBetween('sale_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('selling_price'),
        ];
    }

    private function totals(Carbon $from, Carbon $to): object
    {
        $condition = DB::connection()->getQueryGrammar()->wrap('condition');

        return Sale::query()
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw("COUNT(*) AS units, COALESCE(SUM(selling_price), 0) AS revenue, COALESCE(SUM(cost_price), 0) AS cost, COALESCE(SUM(profit), 0) AS profit, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin, SUM(CASE WHEN {$condition} = ? THEN 1 ELSE 0 END) AS new_units, SUM(CASE WHEN {$condition} = ? THEN 1 ELSE 0 END) AS used_units", [Sale::CONDITION_NEW, Sale::CONDITION_USED])
            ->first();
    }

    private function period(array $filters): array
    {
        $today = now()->startOfDay();

        return match ($filters['period'] ?? 'month') {
            'today' => [$today->copy(), $today->copy()],
            '7d' => [$today->copy()->subDays(6), $today->copy()],
            'year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear()],
            'custom' => [
                Carbon::parse($filters['from'] ?? $today->copy()->startOfMonth())->startOfDay(),
                Carbon::parse($filters['to'] ?? $today)->startOfDay(),
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        };
    }

    private function change(float|int $previous, float|int $current): ?float
    {
        return $previous == 0 ? null : (($current - $previous) / abs($previous)) * 100;
    }

    private function rank(string $column, Carbon $from, Carbon $to)
    {
        return Sale::query()
            ->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->select($column)
            ->selectRaw('COUNT(*) AS units, COALESCE(SUM(selling_price), 0) AS revenue, COALESCE(SUM(profit), 0) AS profit')
            ->groupBy($column)
            ->orderByDesc('units')
            ->limit(5)
            ->get();
    }

    private function monthly(): array
    {
        [$keyExpression, $monthExpression] = $this->monthExpressions();
        $from = now()->startOfMonth()->subMonths(11);
        $rows = Sale::query()
            ->whereBetween('sale_date', [$from->toDateString(), now()->toDateString()])
            ->selectRaw("{$keyExpression} AS month_key, {$monthExpression} AS month_label")
            ->selectRaw('SUM(selling_price) AS revenue, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin')
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $months = collect(range(0, 11))->map(fn (int $offset) => now()->startOfMonth()->subMonths(11 - $offset));

        return [
            'labels' => $months->map(fn (Carbon $date) => $date->locale('id')->translatedFormat('M'))->values(),
            'revenue' => $months->map(fn (Carbon $date) => (int) ($rows[$date->format('Y-m')]->revenue ?? 0))->values(),
            'margin' => $months->map(fn (Carbon $date) => round((float) ($rows[$date->format('Y-m')]->margin ?? 0), 1))->values(),
        ];
    }

    private function monthlyProfitByCondition(): array
    {
        [$keyExpression, $monthExpression] = $this->monthExpressions();
        $condition = DB::connection()->getQueryGrammar()->wrap('condition');
        $from = now()->startOfMonth()->subMonths(11);
        $rows = Sale::query()
            ->whereBetween('sale_date', [$from->toDateString(), now()->toDateString()])
            ->selectRaw("{$keyExpression} AS month_key, {$monthExpression} AS month_label")
            ->selectRaw("SUM(CASE WHEN {$condition} = ? THEN profit ELSE 0 END) AS new_profit, SUM(CASE WHEN {$condition} = ? THEN profit ELSE 0 END) AS used_profit", [Sale::CONDITION_NEW, Sale::CONDITION_USED])
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get()
            ->keyBy('month_key');

        $months = collect(range(0, 11))->map(fn (int $offset) => now()->startOfMonth()->subMonths(11 - $offset));

        return [
            'labels' => $months->map(fn (Carbon $date) => $date->locale('id')->translatedFormat('M'))->values(),
            'new' => $months->map(fn (Carbon $date) => (int) ($rows[$date->format('Y-m')]->new_profit ?? 0))->values(),
            'used' => $months->map(fn (Carbon $date) => (int) ($rows[$date->format('Y-m')]->used_profit ?? 0))->values(),
        ];
    }

    private function monthExpressions(): array
    {
        return match (DB::getDriverName()) {
            'sqlite' => ["strftime('%Y-%m', sale_date)", "strftime('%m', sale_date)"],
            'pgsql' => ["to_char(sale_date, 'YYYY-MM')", "to_char(sale_date, 'MM')"],
            default => ["DATE_FORMAT(sale_date, '%Y-%m')", "DATE_FORMAT(sale_date, '%m')"],
        };
    }
}
