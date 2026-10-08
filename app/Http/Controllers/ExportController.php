<?php

namespace App\Http\Controllers;

use App\Exports\SalesXlsxExport;
use App\Models\Sale;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function index(): View
    {
        return view('exports.index');
    }

    public function transactions(Request $request): BinaryFileResponse
    {
        $filters = $request->validate([
            'scope' => ['required', 'in:all,month,range'],
            'year' => ['required_if:scope,month', 'nullable', 'integer', 'between:2000,2100'],
            'month' => ['required_if:scope,month', 'nullable', 'integer', 'between:1,12'],
            'from' => ['required_if:scope,range', 'nullable', 'date'],
            'to' => ['required_if:scope,range', 'nullable', 'date', 'after_or_equal:from'],
        ], [
            'year.required_if' => 'Tahun wajib diisi.',
            'month.required_if' => 'Bulan wajib dipilih.',
            'from.required_if' => 'Tanggal awal wajib diisi.',
            'to.required_if' => 'Tanggal akhir wajib diisi.',
        ]);

        $query = Sale::query();
        $filename = 'laporan-penjualan-'.now()->format('Y-m-d').'.xlsx';

        if ($filters['scope'] === 'month') {
            $query->whereYear('sale_date', $filters['year'])->whereMonth('sale_date', $filters['month']);
            $filename = 'laporan-penjualan-'.$filters['year'].'-'.str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT).'.xlsx';
        } elseif ($filters['scope'] === 'range') {
            $query->whereBetween('sale_date', [$filters['from'], $filters['to']]);
            $filename = 'laporan-penjualan-'.$filters['from'].'-'.$filters['to'].'.xlsx';
        }

        return Excel::download(new SalesXlsxExport($query), $filename);
    }
}
