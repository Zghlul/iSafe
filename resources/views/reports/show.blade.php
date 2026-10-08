@extends('layouts.app')

@section('title', $title)

@section('content')
<div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold text-text-muted">Ruang kerja / Laporan</p>
            <h1 class="page-title">{{ $title }}</h1>
            <p class="mt-2 text-sm text-text-muted">{{ $from->locale('id')->translatedFormat('d M Y') }} – {{ $to->locale('id')->translatedFormat('d M Y') }}</p>
        </div>
        <a href="{{ route('reports.export', [$report, ...request()->query()]) }}" class="inline-flex min-h-10 items-center rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Export Excel</a>
    </div>

    <div class="mb-4 flex flex-wrap gap-2" aria-label="Jenis laporan">
        @foreach ($reportOptions as $key => $label)
            <a href="{{ route('reports.show', [$key, ...request()->except('page')]) }}" @class([
                'inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-semibold',
                'border-primary bg-primary-soft text-primary' => $report === $key,
                'border-border bg-surface text-text-muted' => $report !== $key,
            ])>{{ str_replace('Laporan ', '', $label) }}</a>
        @endforeach
    </div>

    <x-app.card class="mb-4">
        <form action="{{ route('reports.show', $report) }}" method="GET" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @if ($report === 'daily')
                <label class="text-[13px] font-semibold">Tanggal<input type="date" name="date" value="{{ $filters['date'] ?? now()->format('Y-m-d') }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            @elseif ($report === 'monthly')
                <label class="text-[13px] font-semibold">Bulan<select name="month" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">@foreach (range(1, 12) as $month)<option value="{{ $month }}" @selected((int) ($filters['month'] ?? now()->month) === $month)>{{ \Illuminate\Support\Carbon::create(null, $month)->locale('id')->translatedFormat('F') }}</option>@endforeach</select></label>
                <label class="text-[13px] font-semibold">Tahun<input type="number" name="year" value="{{ $filters['year'] ?? now()->year }}" min="2000" max="2100" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            @elseif ($report === 'yearly')
                <label class="text-[13px] font-semibold">Tahun<input type="number" name="year" value="{{ $filters['year'] ?? now()->year }}" min="2000" max="2100" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            @elseif ($report === 'custom' || in_array($report, ['seller', 'model', 'profit'], true))
                <label class="text-[13px] font-semibold">Dari tanggal<input type="date" name="from" value="{{ $filters['from'] ?? ($report === 'custom' ? '' : now()->startOfYear()->format('Y-m-d')) }}" @required($report === 'custom') class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="text-[13px] font-semibold">Sampai tanggal<input type="date" name="to" value="{{ $filters['to'] ?? ($report === 'custom' ? '' : now()->format('Y-m-d')) }}" @required($report === 'custom') class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            @endif
            <button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Terapkan filter</button>
        </form>
    </x-app.card>

    <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([['Unit', $totals->units], ['Omzet', 'Rp '.number_format($totals->revenue, 0, ',', '.')], ['Modal', 'Rp '.number_format($totals->cost, 0, ',', '.')], ['Keuntungan', 'Rp '.number_format($totals->profit, 0, ',', '.')]] as [$label, $value])
            <x-app.card><p class="text-xs font-semibold text-text-muted">{{ $label }}</p><p class="mt-2 text-lg font-semibold tabular-nums {{ $label === 'Keuntungan' && $totals->profit < 0 ? 'text-danger' : '' }}">{{ $value }}</p></x-app.card>
        @endforeach
    </div>

    @if ($rows->isNotEmpty())
        <x-app.card class="mb-4 p-0">
            <div class="border-b border-border px-5 py-4"><h2 class="card-title">Ringkasan</h2></div>
            <x-app.table class="rounded-none border-0">
                <thead class="bg-surface-muted text-[13px] font-semibold text-text-muted"><tr><th class="px-3 py-3">Periode / Kelompok</th><th class="px-3 py-3 text-right">Unit</th><th class="px-3 py-3 text-right">Omzet</th><th class="px-3 py-3 text-right">Modal</th><th class="px-3 py-3 text-right">Keuntungan</th><th class="px-3 py-3 text-right">Margin</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t border-border"><td class="whitespace-nowrap px-3 py-3 font-semibold">{{ $row->period_label }}</td><td class="px-3 py-3 text-right tabular-nums">{{ number_format($row->units) }}</td><td class="px-3 py-3 text-right tabular-nums">Rp {{ number_format($row->revenue, 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">Rp {{ number_format($row->cost, 0, ',', '.') }}</td><td class="px-3 py-3 text-right font-semibold tabular-nums {{ $row->profit < 0 ? 'text-danger' : 'text-success' }}">{{ $row->profit < 0 ? '−' : '' }}Rp {{ number_format(abs($row->profit), 0, ',', '.') }}</td><td class="px-3 py-3 text-right tabular-nums">{{ number_format((float) $row->margin, 1, ',', '.') }}%</td></tr>
                    @endforeach
                    <tr class="border-t border-border bg-surface-muted font-semibold"><td class="px-3 py-3">TOTAL</td><td class="px-3 py-3 text-right">{{ number_format($totals->units) }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($totals->revenue, 0, ',', '.') }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($totals->cost, 0, ',', '.') }}</td><td class="px-3 py-3 text-right">Rp {{ number_format($totals->profit, 0, ',', '.') }}</td><td class="px-3 py-3 text-right">{{ number_format((float) $totals->margin, 1, ',', '.') }}%</td></tr>
                </tbody>
            </x-app.table>
        </x-app.card>
    @endif

    <x-app.card class="p-0">
        <div class="border-b border-border px-5 py-4"><h2 class="card-title">Rincian transaksi</h2></div>
        <x-app.table class="rounded-none border-0">
            <thead class="bg-surface-muted text-[13px] font-semibold text-text-muted"><tr><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3">Model / IMEI</th><th class="px-3 py-3">Penjual</th><th class="px-3 py-3">Pembeli</th><th class="px-3 py-3 text-right">Omzet</th><th class="px-3 py-3 text-right">Untung</th></tr></thead>
            <tbody>
                @forelse ($transactions as $sale)
                    <tr class="border-t border-border"><td class="whitespace-nowrap px-3 py-3">{{ $sale->sale_date->locale('id')->translatedFormat('d M Y') }}</td><td class="whitespace-nowrap px-3 py-3"><span class="font-semibold">{{ $sale->model }}</span><span class="block font-mono text-xs text-text-muted">{{ $sale->imei }}</span></td><td class="px-3 py-3">{{ $sale->seller_name }}</td><td class="px-3 py-3">{{ $sale->buyer_name }}</td><td class="whitespace-nowrap px-3 py-3 text-right">Rp {{ number_format($sale->selling_price, 0, ',', '.') }}</td><td class="whitespace-nowrap px-3 py-3 text-right font-semibold {{ $sale->profit < 0 ? 'text-danger' : 'text-success' }}">{{ $sale->profit < 0 ? '−' : '+' }}Rp {{ number_format(abs($sale->profit), 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-text-muted">Tidak ada transaksi untuk laporan ini.</td></tr>
                @endforelse
            </tbody>
        </x-app.table>
        <div class="px-5 py-4">{{ $transactions->onEachSide(1)->links() }}</div>
    </x-app.card>
</div>
@endsection
