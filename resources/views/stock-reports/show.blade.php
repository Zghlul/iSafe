@extends('layouts.app')

@section('title', $title)

@section('content')
<div>
    <div class="mb-5">
        <p class="mb-2 text-xs font-semibold text-text-muted">Ruang kerja / Stok / Laporan</p>
        <h1 class="page-title">{{ $title }}</h1>
        <p class="mt-2 text-sm text-text-muted">Analisis stok dan riwayat unit iPhone.</p>
    </div>

    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Jenis laporan stok">
        @foreach ($reportOptions as $key => $label)
            <a href="{{ route('stock-reports.show', [$key, ...request()->except('page')]) }}" @class([
                'inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-semibold',
                'border-primary bg-primary-soft text-primary' => $report === $key,
                'border-border bg-surface text-text-muted' => $report !== $key,
            ])>{{ $label }}</a>
        @endforeach
    </nav>

    @if (in_array($report, ['movements', 'sold'], true))
        <x-app.card class="mb-4">
            <form action="{{ route('stock-reports.show', $report) }}" method="GET" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-3">
                <label class="text-[13px] font-semibold">Dari tanggal<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="text-[13px] font-semibold">Sampai tanggal<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <div class="flex gap-2"><button class="min-h-10 flex-1 rounded-md bg-primary px-3 text-sm font-semibold text-on-primary">Terapkan</button><a href="{{ route('stock-reports.show', $report) }}" class="inline-flex min-h-10 items-center rounded-md border border-border-strong px-3 text-sm font-semibold">Reset</a></div>
            </form>
        </x-app.card>
    @endif

    <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ($totals as $label => $value)
            <x-app.card>
                <p class="text-xs font-semibold text-text-muted">{{ $label }}</p>
                <p class="mt-2 text-lg font-semibold tabular-nums">
                    @if (is_numeric($value) && in_array(strtolower($label), ['nilai stok tersedia', 'modal tertahan', 'omzet', 'modal', 'keuntungan'], true))
                        Rp {{ number_format((float) $value, 0, ',', '.') }}
                    @else
                        {{ is_numeric($value) ? number_format((float) $value, 0, ',', '.') : $value }}
                    @endif
                </p>
            </x-app.card>
        @endforeach
    </div>

    <x-app.card class="overflow-hidden p-0">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-[13px]">
                <thead class="bg-surface-muted text-xs font-semibold text-text-muted">
                    <tr>@foreach ($columns as $column)<th class="px-3 py-3">{{ $column }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-border hover:bg-surface-muted/70">
                            @foreach ($row['values'] as $index => $value)
                                <td class="px-3 py-3 {{ in_array($columns[$index], ['IMEI'], true) ? 'font-mono text-xs' : '' }} {{ in_array($columns[$index], ['Nilai stok', 'Modal', 'Harga jual', 'Keuntungan'], true) ? 'text-right tabular-nums' : '' }}">
                                    @if (in_array($columns[$index], ['Nilai stok', 'Modal', 'Harga jual', 'Keuntungan'], true) && is_numeric($value))
                                        Rp {{ number_format((float) $value, 0, ',', '.') }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}" class="px-4 py-10 text-center text-sm text-text-muted">Tidak ada data untuk laporan ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <div class="px-5 py-4">{{ $rows->onEachSide(1)->links() }}</div>
        @endif
    </x-app.card>
</div>
@endsection
