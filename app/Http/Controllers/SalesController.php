<?php

namespace App\Http\Controllers;

use App\Exports\SalesXlsxExport;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\PhoneModel;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesController extends Controller
{
    public function index(Request $request): View
    {
        $deleted = $request->boolean('deleted');
        $query = $deleted ? Sale::onlyTrashed() : Sale::query();
        $query->applyFilters($request->only([
            'q', 'from', 'to', 'model', 'condition', 'payment_method', 'seller',
        ]));

        $totals = (clone $query)
            ->selectRaw('COUNT(*) AS units, COALESCE(SUM(selling_price), 0) AS revenue, COALESCE(SUM(cost_price), 0) AS cost, COALESCE(SUM(profit), 0) AS profit')
            ->first();

        $sort = in_array($request->query('sort'), [
            'sale_date', 'buyer_name', 'seller_name', 'model', 'selling_price', 'profit', 'condition',
        ], true) ? $request->query('sort') : 'sale_date';
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        $sales = $query->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $editing = $request->filled('edit')
            ? Sale::query()->find($request->integer('edit'))
            : null;

        return view('sales.index', [
            'sales' => $sales,
            'totals' => $totals,
            'models' => PhoneModel::query()->orderBy('name')->pluck('name'),
            'sellers' => Sale::query()->distinct()->orderBy('seller_name')->pluck('seller_name'),
            'filters' => $request->only([
                'q', 'from', 'to', 'model', 'condition', 'payment_method', 'seller', 'sort', 'direction',
            ]),
            'deleted' => $deleted,
            'editing' => $editing,
        ]);
    }

    public function checkImei(Request $request)
    {
        $imei = $request->query('imei');

        if (! is_string($imei) || ! preg_match('/^\d{15}$/', $imei)) {
            return response()->json(['status' => 'invalid']);
        }

        $sale = Sale::withTrashed()->where('imei', $imei)->first();

        if ($sale !== null
            && ! $sale->trashed()
            && $request->integer('except') === $sale->id) {
            return response()->json(['status' => 'valid']);
        }

        $passesLuhn = ! config('sales.imei_luhn_check', true)
            || \App\Rules\ValidImei::passesLuhn($imei);

        if ($sale !== null) {
            return response()->json([
                'status' => $sale->trashed() ? 'deleted' : 'duplicate',
                'id' => $sale->id,
            ]);
        }

        return response()->json(['status' => $passesLuhn ? 'valid' : 'checksum']);
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        Sale::query()->create($request->validated());

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil ditambahkan.');
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        $sale->update($request->validated());

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $sale->delete();

        return back()->with('success', 'Transaksi dipindahkan ke data terhapus.');
    }

    public function restore(int $id): RedirectResponse
    {
        Sale::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->route('sales.index')->with('success', 'Transaksi berhasil dipulihkan.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:sales,id'],
        ])['ids'];

        $deleted = Sale::query()->whereIn('id', $ids)->delete();

        return back()->with('success', "{$deleted} transaksi dipindahkan ke data terhapus.");
    }

    public function bulkRestore(Request $request): RedirectResponse
    {
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:sales,id'],
        ])['ids'];

        $restored = Sale::onlyTrashed()->whereIn('id', $ids)->restore();

        return redirect()->route('sales.index', ['deleted' => 1])
            ->with('success', "{$restored} transaksi berhasil dipulihkan.");
    }

    public function export(Request $request): BinaryFileResponse
    {
        $query = $request->boolean('deleted') ? Sale::onlyTrashed() : Sale::query();
        $query->applyFilters($request->only([
            'q', 'from', 'to', 'model', 'condition', 'payment_method', 'seller',
        ]));

        if ($request->filled('ids')) {
            $ids = array_filter((array) $request->query('ids'), 'ctype_digit');
            abort_if($ids === [], 422, 'Pilih minimal satu transaksi untuk diekspor.');
            $query->whereIn('id', $ids);
        }

        return Excel::download(
            new SalesXlsxExport($query),
            'laporan-penjualan-'.now()->format('Y-m-d').'.xlsx',
        );
    }
}
