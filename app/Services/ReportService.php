<?php

namespace App\Services;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public const REPORTS = [
        'daily' => 'Laporan harian',
        'monthly' => 'Laporan bulanan',
        'yearly' => 'Laporan tahunan',
        'seller' => 'Laporan per penjual',
        'model' => 'Laporan per model iPhone',
        'profit' => 'Laporan keuntungan',
        'custom' => 'Laporan rentang tanggal',
    ];

    public function build(string $report, array $filters): array
    {
        abort_unless(array_key_exists($report, self::REPORTS), 404);

        [$from, $to, $filters] = $this->dates($report, $filters);
        $query = Sale::query()->whereBetween('sale_date', [$from->toDateString(), $to->toDateString()]);
        $transactions = (clone $query)->latest('sale_date')->latest('id')->paginate(20)->withQueryString();
        $totals = (clone $query)
            ->selectRaw('COUNT(*) AS units, COALESCE(SUM(selling_price), 0) AS revenue, COALESCE(SUM(cost_price), 0) AS cost, COALESCE(SUM(profit), 0) AS profit, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin')
            ->first();

        $rows = match ($report) {
            'daily', 'monthly' => $this->groupByDate($query, 'day'),
            'yearly' => $this->groupByDate($query, 'month'),
            'seller' => $this->groupByField($query, 'seller_name'),
            'model' => $this->groupByField($query, 'model'),
            'profit' => $this->groupByProfit($query),
            default => collect(),
        };

        return [
            'report' => $report,
            'title' => self::REPORTS[$report],
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'rows' => $rows,
            'transactions' => $transactions,
            'totals' => $totals,
        ];
    }

    private function dates(string $report, array $filters): array
    {
        $today = now()->startOfDay();

        return match ($report) {
            'daily' => [
                Carbon::parse($filters['date'] ?? $today)->startOfDay(),
                Carbon::parse($filters['date'] ?? $today)->startOfDay(),
                [...$filters, 'date' => $filters['date'] ?? $today->toDateString()],
            ],
            'monthly' => $this->monthDates($filters),
            'yearly' => $this->yearDates($filters),
            'seller', 'model' => [
                isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $today->copy()->startOfYear(),
                isset($filters['to']) ? Carbon::parse($filters['to'])->startOfDay() : $today->copy(),
                $filters,
            ],
            'custom' => [
                Carbon::parse($filters['from'])->startOfDay(),
                Carbon::parse($filters['to'])->startOfDay(),
                $filters,
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), $filters],
        };
    }

    private function monthDates(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);
        $from = Carbon::create($year, $month, 1)->startOfDay();

        return [$from, $from->copy()->endOfMonth(), [...$filters, 'year' => $year, 'month' => $month]];
    }

    private function yearDates(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);

        return [
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay(),
            [...$filters, 'year' => $year],
        ];
    }

    private function groupByDate($query, string $period)
    {
        [$key, $label] = $this->dateExpressions($period);

        $rows = (clone $query)
            ->selectRaw("{$key} AS period_key, {$label} AS period_label")
            ->selectRaw('COUNT(*) AS units, SUM(selling_price) AS revenue, SUM(cost_price) AS cost, SUM(profit) AS profit, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin')
            ->groupBy('period_key', 'period_label')
            ->orderBy('period_key')
            ->get();

        if ($period === 'month') {
            $rows->each(function ($row): void {
                $row->period_label = Carbon::create(2000, (int) $row->period_label, 1)
                    ->locale('id')
                    ->translatedFormat('F');
            });
        }

        return $rows;
    }

    private function groupByField($query, string $field)
    {
        return (clone $query)
            ->select($field.' AS period_label')
            ->selectRaw('COUNT(*) AS units, SUM(selling_price) AS revenue, SUM(cost_price) AS cost, SUM(profit) AS profit, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin')
            ->groupBy($field)
            ->orderByDesc('revenue')
            ->get();
    }

    private function groupByProfit($query)
    {
        return (clone $query)
            ->select('condition AS period_label')
            ->selectRaw('COUNT(*) AS units, SUM(selling_price) AS revenue, SUM(cost_price) AS cost, SUM(profit) AS profit, CASE WHEN SUM(selling_price) = 0 THEN 0 ELSE SUM(profit) * 100.0 / SUM(selling_price) END AS margin')
            ->groupBy('condition')
            ->orderBy('condition')
            ->get()
            ->map(fn ($row) => tap($row, function ($row): void {
                $row->period_label = $row->period_label === Sale::CONDITION_NEW ? 'Baru' : 'Second';
            }));
    }

    private function dateExpressions(string $period): array
    {
        $driver = DB::getDriverName();

        if ($period === 'month') {
            return match ($driver) {
                'sqlite' => ["strftime('%Y-%m', sale_date)", "strftime('%m', sale_date)"],
                'pgsql' => ["to_char(sale_date, 'YYYY-MM')", "to_char(sale_date, 'MM')"],
                default => ["DATE_FORMAT(sale_date, '%Y-%m')", "DATE_FORMAT(sale_date, '%m')"],
            };
        }

        return match ($driver) {
            'sqlite' => ["strftime('%Y-%m-%d', sale_date)", "strftime('%d', sale_date)"],
            'pgsql' => ["to_char(sale_date, 'YYYY-MM-DD')", "to_char(sale_date, 'DD')"],
            default => ["DATE_FORMAT(sale_date, '%Y-%m-%d')", "DATE_FORMAT(sale_date, '%d')"],
        };
    }
}
