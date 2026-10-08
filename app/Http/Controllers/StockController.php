<?php

namespace App\Http\Controllers;

use App\Exports\StockXlsxExport;
use App\Models\PhoneModel;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Rules\StockImei;
use App\Rules\ValidImei;
use App\Services\StockMetricsService;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockController extends Controller
{
    public function index(Request $request, StockMetricsService $metrics): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['available', 'sold', 'all', 'deleted'])],
            'view' => ['nullable', Rule::in(['units', 'summary'])],
            'q' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'integer', 'exists:phone_models,id'],
            'storage' => ['nullable', Rule::in(Sale::STORAGES)],
            'condition' => ['nullable', Rule::in(Sale::CONDITIONS)],
            'variant' => ['nullable', Rule::in(Stock::VARIANTS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'old' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['purchase_date', 'model', 'cost_price', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $tab = $filters['tab'] ?? 'available';
        $query = $tab === 'deleted' ? Stock::onlyTrashed() : Stock::query();
        if ($tab === 'available' || $tab === 'sold') {
            $query->where('status', $tab);
        }
        $this->applyFilters($query, $filters);

        $available = Stock::available()->count();
        $ageExpression = match (DB::getDriverName()) {
            'sqlite' => "AVG(julianday('now') - purchase_date)",
            'pgsql' => 'AVG(CURRENT_DATE - purchase_date)',
            default => 'AVG(DATEDIFF(CURRENT_DATE, purchase_date))',
        };
        $summary = Stock::available()->selectRaw("COALESCE(SUM(cost_price), 0) AS value, {$ageExpression} AS average_age")->first();
        $lowModels = $metrics->lowStockModels()->count();

        if (($filters['view'] ?? 'units') === 'summary' && $tab !== 'deleted') {
            $summaryQuery = Stock::query();
            $this->applyFilters($summaryQuery, $filters);
            $rows = $summaryQuery
                ->join('phone_models', 'stocks.phone_model_id', '=', 'phone_models.id')
                ->selectRaw('phone_models.id AS phone_model_id, phone_models.name AS model, stocks.storage, stocks.color, stocks.condition, SUM(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) AS available, SUM(CASE WHEN stocks.status = ? THEN 1 ELSE 0 END) AS sold, COUNT(*) AS total, SUM(CASE WHEN stocks.status = ? THEN stocks.cost_price ELSE 0 END) AS stock_value', [
                    Stock::STATUS_AVAILABLE,
                    Stock::STATUS_SOLD,
                    Stock::STATUS_AVAILABLE,
                ])
                ->groupBy('phone_models.id', 'phone_models.name', 'stocks.storage', 'stocks.color', 'stocks.condition')
                ->orderBy('phone_models.name')
                ->paginate(20)
                ->withQueryString();
        } else {
            $sort = $filters['sort'] ?? 'purchase_date';
            $direction = $filters['direction'] ?? 'desc';
            $rows = $query->with('phoneModel')
                ->orderBy($sort === 'model' ? 'phone_model_id' : $sort, $direction)
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }

        $editing = $request->filled('edit')
            ? Stock::query()->with('phoneModel')->findOrFail($request->integer('edit'))
            : null;

        return view('stocks.index', [
            'rows' => $rows,
            'viewMode' => $filters['view'] ?? 'units',
            'tab' => $tab,
            'filters' => $filters,
            'models' => PhoneModel::query()->orderBy('name')->get(),
            'colors' => Stock::query()->distinct()->orderBy('color')->pluck('color'),
            'availableCount' => $available,
            'stockValue' => (int) $summary->value,
            'averageAge' => (int) round((float) ($summary->average_age ?? 0)),
            'lowStockCount' => $lowModels,
            'oldStockDays' => (int) Setting::value(Setting::OLD_STOCK_DAYS, 30),
            'minStockModels' => $metrics->lowStockModels()->pluck('id')->all(),
            'editing' => $editing,
            'deletedStock' => $tab === 'deleted',
        ]);
    }

    public function store(Request $request, StockService $stocks): RedirectResponse
    {
        $bulk = $request->boolean('bulk');
        $validated = $this->validatedStock($request, null, $bulk);
        $imeis = preg_split('/\R/', trim((string) ($validated['imeis'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        unset($validated['imeis'], $validated['bulk']);

        if ($imeis !== false && $imeis !== []) {
            $stocks->addMany($validated, array_values($imeis));
            $count = count($imeis);
        } else {
            $stocks->add($validated);
            $count = 1;
        }

        return redirect()->route('stocks.index')->with('success', "{$count} unit stok berhasil ditambahkan.");
    }

    public function update(Request $request, Stock $stock, StockService $stocks): RedirectResponse
    {
        $stocks->update($stock, $this->validatedStock($request, $stock));

        return redirect()->route('stocks.index')->with('success', 'Unit stok berhasil diperbarui.');
    }

    public function show(int $stock): View
    {
        $stock = Stock::withTrashed()->findOrFail($stock);
        $stock->load(['phoneModel', 'sales' => fn ($query) => $query->withTrashed()->latest()]);

        return view('stocks.show', [
            'stock' => $stock,
            'movements' => $stock->movements()->with('sale')->latest('moved_at')->paginate(20),
            'oldStockDays' => (int) Setting::value(Setting::OLD_STOCK_DAYS, 30),
        ]);
    }

    public function destroy(Stock $stock, StockService $stocks): RedirectResponse
    {
        $stocks->remove($stock);

        return back()->with('success', 'Unit stok dihapus.');
    }

    public function restore(int $stock, StockService $stocks): RedirectResponse
    {
        $stocks->restoreStock($stock);

        return redirect()->route('stocks.index', ['tab' => 'available'])->with('success', 'Unit stok berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request, StockService $stocks): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:stocks,id'],
        ])['ids'];
        $deleted = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $stock = Stock::query()->findOrFail($id);

            if ($stock->status !== Stock::STATUS_AVAILABLE) {
                $skipped++;
                continue;
            }

            $stocks->remove($stock);
            $deleted++;
        }

        return back()->with('success', "{$deleted} unit dihapus; {$skipped} unit terjual dilewati.");
    }

    public function checkImei(Request $request): JsonResponse
    {
        $imei = $request->query('imei');

        if (! is_string($imei) || ! preg_match('/^\d{15}$/', $imei)) {
            return response()->json(['status' => 'invalid']);
        }

        $stock = Stock::withTrashed()->where('imei', $imei)->first();

        if ($stock !== null && $request->integer('except') === $stock->id && ! $stock->trashed()) {
            return response()->json(['status' => 'valid']);
        }

        if (config('sales.imei_luhn_check', true) && ! ValidImei::passesLuhn($imei)) {
            return response()->json(['status' => 'checksum']);
        }

        if ($stock !== null) {
            return response()->json([
                'status' => $stock->trashed() ? 'deleted' : 'duplicate',
                'id' => $stock->id,
            ]);
        }

        return response()->json(['status' => 'valid']);
    }

    public function searchAvailable(Request $request): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'min:2', 'max:100']]);
        $query = trim($data['q']);

        return response()->json(
            Stock::available()
                ->with('phoneModel:id,name')
                ->where(function (Builder $builder) use ($query): void {
                    $builder->where('imei', 'like', '%'.$query.'%')
                        ->orWhereHas('phoneModel', fn (Builder $modelQuery) => $modelQuery->where('name', 'like', '%'.$query.'%'));
                })
                ->orderBy('purchase_date')
                ->limit(20)
                ->get()
                ->map(fn (Stock $stock) => [
                    'id' => $stock->id,
                    'imei' => $stock->imei,
                    'model' => $stock->phoneModel->name,
                    'storage' => $stock->storage,
                    'color' => $stock->color,
                    'condition' => $stock->condition,
                    'cost_price' => $stock->cost_price,
                    'battery_health' => $stock->battery_health,
                    'variant' => $stock->variant,
                ]),
        );
    }

    public function export(Request $request): BinaryFileResponse
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['available', 'sold', 'all'])],
            'q' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'integer', 'exists:phone_models,id'],
            'storage' => ['nullable', Rule::in(Sale::STORAGES)],
            'condition' => ['nullable', Rule::in(Sale::CONDITIONS)],
            'variant' => ['nullable', Rule::in(Stock::VARIANTS)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'old' => ['nullable', 'boolean'],
        ]);
        $query = Stock::query()->with('phoneModel');
        $tab = $filters['tab'] ?? 'available';

        if (in_array($tab, ['available', 'sold'], true)) {
            $query->where('status', $tab);
        }

        $this->applyFilters($query, $filters);

        return Excel::download(new StockXlsxExport($query), 'laporan-stok-'.now()->format('Y-m-d').'.xlsx');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query->when(filled($filters['q'] ?? null), function (Builder $builder) use ($filters): void {
            $term = '%'.trim($filters['q']).'%';
            $builder->where(function (Builder $nested) use ($term): void {
                $nested->where('imei', 'like', $term)
                    ->orWhere('color', 'like', $term)
                    ->orWhere('source_name', 'like', $term)
                    ->orWhereHas('phoneModel', fn (Builder $modelQuery) => $modelQuery->where('name', 'like', $term));
            });
        });

        foreach (['phone_model_id' => 'model', 'storage' => 'storage', 'condition' => 'condition', 'variant' => 'variant'] as $column => $filter) {
            $query->when(filled($filters[$filter] ?? null), fn (Builder $builder) => $builder->where($column, $filters[$filter]));
        }

        $query->when(filled($filters['from'] ?? null), fn (Builder $builder) => $builder->whereDate('purchase_date', '>=', $filters['from']))
            ->when(filled($filters['to'] ?? null), fn (Builder $builder) => $builder->whereDate('purchase_date', '<=', $filters['to']))
            ->when(($filters['old'] ?? false) === true || ($filters['old'] ?? null) === '1', function (Builder $builder): void {
                $days = (int) Setting::value(Setting::OLD_STOCK_DAYS, 30);
                $builder->whereDate('purchase_date', '<', now()->subDays($days)->toDateString());
            });
    }

    private function validatedStock(Request $request, ?Stock $stock = null, bool $bulk = false): array
    {
        $isSold = $stock?->status === Stock::STATUS_SOLD;
        $rules = [
            'phone_model_id' => ['required', 'integer', 'exists:phone_models,id'],
            'storage' => ['required', Rule::in(Sale::STORAGES)],
            'color' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(Sale::CONDITIONS)],
            'imei' => ['required', 'string', new StockImei($stock?->id)],
            'battery_health' => ['nullable', 'integer', 'between:0,100'],
            'variant' => ['nullable', Rule::in(Stock::VARIANTS)],
            'physical_grade' => ['nullable', Rule::in(Stock::GRADES)],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => [Rule::in(array_keys(Stock::ACCESSORIES))],
            'warranty_until' => ['nullable', 'date'],
            'purchase_date' => ['required', 'date'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'imeis' => ['nullable', 'string', 'max:50000'],
            'bulk' => ['nullable', 'boolean'],
        ];

        if ($isSold) {
            $rules = array_intersect_key($rules, array_flip([
                'battery_health', 'physical_grade', 'accessories', 'warranty_until', 'notes',
            ]));
        } elseif ($bulk) {
            unset($rules['imei']);
            $rules['imeis'] = ['required', 'string', 'max:50000'];
        }

        $data = $request->validate($rules);

        if ($isSold && array_diff(array_keys($request->all()), [
            '_token', '_method', 'battery_health', 'physical_grade', 'accessories', 'warranty_until', 'notes',
        ]) !== []) {
            abort(422, 'Unit terjual hanya dapat mengubah field non-finansial.');
        }

        return $data;
    }

}
