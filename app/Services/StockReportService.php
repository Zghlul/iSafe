<?php

namespace App\Services;

use App\Models\PhoneModel;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StockReportService
{
    public const REPORTS = [
        'summary' => 'Ringkasan stok',
        'low-stock' => 'Stok menipis',
        'aging' => 'Stok lama',
        'movements' => 'Pergerakan stok',
        'sold' => 'Unit terjual',
    ];

    public function build(string $report, array $filters): array
    {
        abort_unless(array_key_exists($report, self::REPORTS), 404);

        return match ($report) {
            'summary' => $this->summary(),
            'low-stock' => $this->lowStock(),
            'aging' => $this->aging(),
            'movements' => $this->movements($filters),
            'sold' => $this->sold($filters),
        };
    }

    private function summary(): array
    {
        $rows = Stock::query()
            ->join('phone_models', 'stocks.phone_model_id', '=', 'phone_models.id')
            ->selectRaw('phone_models.name AS model, COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) AS available', [Stock::STATUS_AVAILABLE])
            ->selectRaw('SUM(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) AS sold', [Stock::STATUS_SOLD])
            ->selectRaw('SUM(CASE WHEN stocks.status = ? THEN stocks.cost_price ELSE 0 END) AS value', [Stock::STATUS_AVAILABLE])
            ->groupBy('phone_models.name')
            ->orderBy('phone_models.name')
            ->get()
            ->map(fn ($row) => [
                'values' => [$row->model, (int) $row->available, (int) $row->sold, (int) $row->total, (int) $row->value],
            ]);

        return $this->reportData('summary', ['Model', 'Tersedia', 'Terjual', 'Total unit', 'Nilai stok'], $rows, [
            'Model' => PhoneModel::query()->count(),
            'Unit tersedia' => Stock::available()->count(),
            'Nilai stok tersedia' => (int) Stock::available()->sum('cost_price'),
        ]);
    }

    private function lowStock(): array
    {
        $defaultMinimum = Setting::value(Setting::DEFAULT_MIN_STOCK);
        $rows = app(StockMetricsService::class)->lowStockModels()
            ->select(['phone_models.id', 'phone_models.name', 'phone_models.min_stock'])
            ->selectSub(
                Stock::query()->selectRaw('COUNT(*)')
                    ->whereColumn('stocks.phone_model_id', 'phone_models.id')
                    ->where('stocks.status', Stock::STATUS_AVAILABLE),
                'available_units',
            )
            ->orderBy('phone_models.name')
            ->get()
            ->map(function (PhoneModel $model) use ($defaultMinimum): array {
                $minimum = $model->min_stock ?? $defaultMinimum;

                return [
                    'values' => [
                        $model->name,
                        (int) $model->available_units,
                        $minimum === null ? '—' : (int) $minimum,
                        $minimum === null ? '—' : max(0, (int) $minimum - (int) $model->available_units),
                    ],
                ];
            });

        return $this->reportData('low-stock', ['Model', 'Tersedia', 'Batas minimum', 'Kekurangan'], $rows, [
            'Model menipis' => $rows->count(),
        ]);
    }

    private function aging(): array
    {
        $days = (int) Setting::value(Setting::OLD_STOCK_DAYS, 30);
        $query = Stock::available()
            ->with('phoneModel:id,name')
            ->whereDate('purchase_date', '<', now()->subDays($days)->toDateString())
            ->orderBy('purchase_date');
        $rows = $query->paginate(50)->withQueryString()->through(fn (Stock $stock) => [
                'values' => [
                    $stock->phoneModel?->name ?? '—',
                    $stock->storage,
                    $stock->color,
                    (string) $stock->imei,
                    $stock->purchase_date->format('d/m/Y'),
                    $stock->purchase_date->diffInDays(now()->startOfDay()),
                    (int) $stock->cost_price,
                ],
            ]);
        $oldStockQuery = Stock::available()->whereDate('purchase_date', '<', now()->subDays($days)->toDateString());

        return $this->reportData('aging', ['Model', 'Kapasitas', 'Warna', 'IMEI', 'Tanggal masuk', 'Umur (hari)', 'Modal'], $rows, [
            'Batas stok lama' => "{$days} hari",
            'Unit stok lama' => (clone $oldStockQuery)->count(),
            'Modal tertahan' => (int) (clone $oldStockQuery)->sum('cost_price'),
        ]);
    }

    private function movements(array $filters): array
    {
        $query = StockMovement::query()->with([
            'stock' => fn ($stockQuery) => $stockQuery->withTrashed()->with('phoneModel'),
            'sale',
        ])->latest('moved_at');
        $this->dateFilter($query, 'moved_at', $filters);

        $rows = $query->paginate(50)->withQueryString()->through(fn (StockMovement $movement) => [
            'values' => [
                $movement->moved_at->format('d/m/Y H:i'),
                $movement->stock?->phoneModel?->name ?? '—',
                (string) ($movement->stock?->imei ?? '—'),
                $movement->type,
                $movement->sale_id ? '#'.$movement->sale_id : '—',
                $movement->note ?? '—',
            ],
        ]);

        return $this->reportData('movements', ['Waktu', 'Model', 'IMEI', 'Jenis', 'Transaksi', 'Catatan'], $rows, [
            'Total pergerakan' => (clone $query)->count(),
        ]);
    }

    private function sold(array $filters): array
    {
        $query = Sale::query()->whereNotNull('stock_id')->latest('sale_date')->latest('id');
        $this->dateFilter($query, 'sale_date', $filters);
        $totals = (clone $query)
            ->selectRaw('COUNT(*) AS units, COALESCE(SUM(selling_price), 0) AS revenue, COALESCE(SUM(cost_price), 0) AS cost, COALESCE(SUM(profit), 0) AS profit')
            ->first();
        $rows = $query->paginate(50)->withQueryString()->through(fn (Sale $sale) => [
            'values' => [
                $sale->sale_date->format('d/m/Y'),
                $sale->model,
                $sale->storage,
                $sale->color,
                (string) $sale->imei,
                (int) $sale->selling_price,
                (int) $sale->cost_price,
                (int) $sale->profit,
            ],
        ]);

        return $this->reportData('sold', ['Tanggal', 'Model', 'Kapasitas', 'Warna', 'IMEI', 'Harga jual', 'Modal', 'Keuntungan'], $rows, [
            'Unit terjual' => (int) $totals->units,
            'Omzet' => (int) $totals->revenue,
            'Modal' => (int) $totals->cost,
            'Keuntungan' => (int) $totals->profit,
        ]);
    }

    private function dateFilter(Builder $query, string $column, array $filters): void
    {
        $query->when(isset($filters['from']), fn (Builder $builder) => $builder->whereDate($column, '>=', $filters['from']))
            ->when(isset($filters['to']), fn (Builder $builder) => $builder->whereDate($column, '<=', $filters['to']));
    }

    private function reportData(string $report, array $columns, Collection|LengthAwarePaginator $rows, array $totals): array
    {
        return [
            'report' => $report,
            'title' => self::REPORTS[$report],
            'reportOptions' => self::REPORTS,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
