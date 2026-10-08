@extends('layouts.app')

@section('title', 'Ringkasan')

@php
    $compactMoney = static function ($value): string {
        $value = (float) $value;
        if (abs($value) >= 1_000_000_000) {
            return 'Rp '.number_format($value / 1_000_000_000, 2, ',', '.').' miliar';
        }
        if (abs($value) >= 1_000_000) {
            return 'Rp '.number_format($value / 1_000_000, 1, ',', '.').' jt';
        }
        return 'Rp '.number_format($value, 0, ',', '.');
    };
    $changeBadge = static function (?float $change): string {
        if ($change === null) {
            return '—';
        }
        return ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.').'%';
    };
@endphp

@section('content')
<div
    x-data="dashboardCharts(@js($revenueChart), @js($profitChart), @js($payments->all()))"
    x-init="init()"
>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold text-text-muted">{{ $storeName }} / Ruang kerja</p>
            <h1 class="page-title">Ringkasan</h1>
            <p class="mt-2 text-sm text-text-muted">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <div class="flex flex-wrap gap-2" role="group" aria-label="Periode ringkasan">
            @foreach (['today' => 'Hari ini', '7d' => '7 hari', 'month' => 'Bulan ini', 'year' => 'Tahun ini'] as $key => $label)
                <a href="{{ route('dashboard', ['period' => $key]) }}" @class([
                    'inline-flex h-9 items-center rounded-md border px-3 text-[13px] font-semibold',
                    'border-primary bg-primary-soft text-primary' => $period === $key,
                    'border-border bg-surface text-text-muted' => $period !== $key,
                ])>{{ $label }}</a>
            @endforeach
            <button type="button" class="inline-flex h-9 items-center rounded-md border border-border bg-surface px-3 text-[13px] font-semibold text-text-muted" @click="$refs.customPeriod.showModal()">Custom</button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md border border-success bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Omzet', 'value' => $current->revenue, 'change' => $changes['revenue'], 'icon' => 'Rp'],
            ['label' => 'Keuntungan', 'value' => $current->profit, 'change' => $changes['profit'], 'icon' => '+'],
            ['label' => 'Unit terjual', 'value' => $current->units, 'change' => $changes['units'], 'icon' => '#'],
            ['label' => 'Margin rata-rata', 'value' => number_format((float) $current->margin, 1, ',', '.').'%', 'change' => $changes['margin'], 'icon' => '%'],
        ] as $kpi)
            @php($change = $kpi['change'])
            <x-app.card>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex size-9 items-center justify-center rounded-md border border-border bg-surface-muted text-xs font-bold text-primary" aria-hidden="true">{{ $kpi['icon'] }}</div>
                    <span class="rounded-full px-2 py-1 text-xs font-bold {{ $change === null ? 'bg-surface-muted text-text-muted' : ($change >= 0 ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger') }}">
                        {{ $changeBadge($change) }}
                    </span>
                </div>
                <p class="mt-4 text-[13px] font-semibold text-text-muted">{{ $kpi['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold tracking-tight tabular-nums">
                    @if ($kpi['label'] === 'Unit terjual' || $kpi['label'] === 'Margin rata-rata')
                        {{ $kpi['value'] }}
                    @else
                        {{ $compactMoney($kpi['value']) }}
                    @endif
                </p>
                <p class="mt-1 text-xs text-text-muted">dibanding periode sebelumnya</p>
            </x-app.card>
        @endforeach
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('stocks.index', ['tab' => 'available']) }}" class="block">
            <x-app.card>
                <p class="text-xs font-semibold text-text-muted">Stok tersedia</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($stock['available_units']) }} unit</p>
                <p class="mt-1 text-xs text-text-muted">Modal tertahan {{ $compactMoney($stock['stock_value']) }}</p>
            </x-app.card>
        </a>
        <a href="{{ route('stocks.index', ['tab' => 'available', 'view' => 'summary']) }}" class="block">
            <x-app.card>
                <p class="text-xs font-semibold text-text-muted">Model stok menipis</p>
                <p class="mt-2 text-xl font-semibold tabular-nums {{ $stock['low_stock_models'] > 0 ? 'text-warning' : '' }}">{{ number_format($stock['low_stock_models']) }}</p>
                <p class="mt-1 text-xs text-text-muted">Di bawah batas minimum</p>
            </x-app.card>
        </a>
        <a href="{{ route('stocks.index', ['tab' => 'available', 'old' => 1]) }}" class="block">
            <x-app.card>
                <p class="text-xs font-semibold text-text-muted">Stok berumur lebih dari {{ $stock['old_stock_days'] }} hari</p>
                <p class="mt-2 text-xl font-semibold tabular-nums">{{ number_format($stock['old_units']) }} unit</p>
                <p class="mt-1 text-xs text-text-muted">Perlu ditinjau</p>
            </x-app.card>
        </a>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <x-app.card>
            <h2 class="card-title">Alur uang</h2>
            <p class="mt-1 text-xs text-text-muted">Ringkasan periode terpilih</p>
            <div class="mt-5 space-y-4">
                @foreach ([['label' => 'Omzet', 'value' => (int) $current->revenue, 'color' => 'bg-primary'], ['label' => 'Modal', 'value' => $money['cost'], 'color' => 'bg-chart-slate'], ['label' => 'Keuntungan', 'value' => $money['profit'], 'color' => 'bg-accent']] as $flow)
                    @php($width = (int) $current->revenue > 0 ? min(100, (int) round(max(0, $flow['value']) * 100 / (int) $current->revenue)) : 0)
                    <div>
                        <div class="mb-1 flex justify-between gap-3 text-xs"><span>{{ $flow['label'] }}</span><span class="font-semibold tabular-nums">{{ $compactMoney($flow['value']) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-surface-muted"><div class="h-full rounded-full {{ $flow['color'] }}" style="width: {{ $width }}%"></div></div>
                    </div>
                @endforeach
                <div class="flex flex-wrap justify-between gap-2 border-t border-border pt-4 text-xs">
                    <span><x-app.badge tone="success">Baru</x-app.badge> {{ number_format($money['new_units']) }} unit</span>
                    <span><x-app.badge tone="warning">Second</x-app.badge> {{ number_format($money['used_units']) }} unit</span>
                </div>
            </div>
        </x-app.card>

        <x-app.card>
            <h2 class="card-title">Metode pembayaran</h2>
            <p class="mt-1 text-xs text-text-muted">Berdasarkan unit pada periode terpilih</p>
            <div class="relative mx-auto mt-4 h-44 max-w-[220px]">
                <canvas id="payment-chart" aria-label="Donat persentase metode pembayaran"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-xl font-semibold tabular-nums">{{ number_format((int) $current->units) }}</span>
                    <span class="text-xs text-text-muted">transaksi</span>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                @foreach (['transfer' => 'Transfer', 'cash' => 'Tunai', 'installment' => 'Cicilan', 'other' => 'Lainnya'] as $key => $label)
                    <div class="flex items-center justify-between gap-2"><span class="text-text-muted">{{ $label }}</span><span class="font-semibold tabular-nums">{{ number_format((int) $payments[$key]) }}</span></div>
                @endforeach
            </div>
        </x-app.card>

        <x-app.card x-data="{ rankTab: 'models' }">
            <div class="flex items-start justify-between gap-3">
                <div><h2 class="card-title">Peringkat</h2><p class="mt-1 text-xs text-text-muted">5 teratas berdasarkan unit</p></div>
            </div>
            <div class="mt-3 flex gap-4 border-b border-border text-[13px] font-semibold">
                <button type="button" @click="rankTab='models'" :class="rankTab==='models' ? 'border-b-2 border-primary text-primary' : 'text-text-muted'" class="pb-2">Model</button>
                <button type="button" @click="rankTab='sellers'" :class="rankTab==='sellers' ? 'border-b-2 border-primary text-primary' : 'text-text-muted'" class="pb-2">Penjual</button>
                <button type="button" @click="rankTab='conditions'" :class="rankTab==='conditions' ? 'border-b-2 border-primary text-primary' : 'text-text-muted'" class="pb-2">Kondisi</button>
            </div>
            <?php foreach ($rankings as $key => $rows): ?>
                <div x-show="rankTab === '{{ $key }}'" class="mt-4 space-y-3">
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $name = match ($key) {
                                'models' => $row->model,
                                'sellers' => $row->seller_name,
                                default => $row->condition === 'new' ? 'Baru' : 'Second',
                            };
                            $share = (int) $current->units > 0 ? (int) round($row->units * 100 / (int) $current->units) : 0;
                        ?>
                        <div>
                            <div class="mb-1 flex justify-between gap-3 text-xs"><span class="truncate">{{ $name }}</span><span class="shrink-0 font-semibold">{{ number_format($row->units) }} unit · {{ $share }}%</span></div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-surface-muted"><div class="h-full rounded-full bg-accent-dark" style="width: {{ $share }}%"></div></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($rows->isEmpty()): ?>
                        <p class="py-5 text-center text-xs text-text-muted">Belum ada data pada periode ini.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </x-app.card>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <x-app.card>
            <div class="flex flex-wrap justify-between gap-2">
                <div><h2 class="card-title">Omzet 12 bulan terakhir</h2><p class="mt-1 text-xs text-text-muted">Omzet bulanan dan persentase margin</p></div>
                <span class="text-xs font-semibold text-text-muted">Tidak mengikuti filter periode</span>
            </div>
            <div class="mt-4 h-64"><canvas id="revenue-chart" aria-label="Grafik omzet dan margin 12 bulan terakhir"></canvas></div>
            <div class="mt-3 flex flex-wrap gap-4 text-xs text-text-muted"><span><i class="mr-2 inline-block size-2 rounded-full bg-accent"></i>Omzet</span><span><i class="mr-2 inline-block size-2 rounded-full bg-primary"></i>Margin %</span></div>
        </x-app.card>
        <x-app.card>
            <div class="flex flex-wrap justify-between gap-2">
                <div><h2 class="card-title">Keuntungan per bulan</h2><p class="mt-1 text-xs text-text-muted">Baru dibanding Second</p></div>
                <span class="text-xs font-semibold text-text-muted">12 bulan terakhir</span>
            </div>
            <div class="mt-4 h-64"><canvas id="profit-chart" aria-label="Grafik keuntungan bulanan Baru dan Second"></canvas></div>
            <div class="mt-3 flex flex-wrap gap-4 text-xs text-text-muted"><span><i class="mr-2 inline-block size-2 rounded-full bg-accent-dark"></i>iPhone Baru</span><span><i class="mr-2 inline-block size-2 rounded-full bg-chart-amber"></i>iPhone Second</span></div>
        </x-app.card>
    </div>

    @if ($lossSales->isNotEmpty())
        <div class="mt-4 rounded-lg border border-danger bg-danger-soft p-4">
            <h2 class="text-sm font-semibold text-danger">Transaksi rugi bulan ini ({{ $lossSales->count() }})</h2>
            <ul class="mt-2 space-y-1 text-xs text-danger">
                @foreach ($lossSales as $loss)
                    <li><a class="underline" href="{{ route('sales.index', ['q' => $loss->imei]) }}">{{ $loss->model }} · {{ $loss->buyer_name }} · Rugi Rp {{ number_format(abs($loss->profit), 0, ',', '.') }}</a></li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-app.card class="mt-4 p-0">
        <div class="flex items-center justify-between gap-3 border-b border-border px-5 py-4">
            <div><h2 class="card-title">Transaksi terbaru</h2><p class="mt-1 text-xs text-text-muted">8 transaksi terakhir</p></div>
            <a href="{{ route('sales.index') }}" class="text-sm font-semibold text-primary">Lihat semua</a>
        </div>
        <x-app.table class="rounded-none border-0">
            <thead class="bg-surface-muted text-[13px] font-semibold text-text-muted"><tr><th class="px-3 py-3">Model</th><th class="px-3 py-3">Pembeli</th><th class="px-3 py-3">Tanggal</th><th class="px-3 py-3 text-right">Omzet</th><th class="px-3 py-3 text-right">Untung</th></tr></thead>
            <tbody>
                @forelse ($recentSales as $sale)
                    <tr class="border-t border-border"><td class="whitespace-nowrap px-3 py-3 font-semibold">{{ $sale->model }} · {{ $sale->storage }}</td><td class="whitespace-nowrap px-3 py-3">{{ $sale->buyer_name }}</td><td class="whitespace-nowrap px-3 py-3">{{ $sale->sale_date->locale('id')->translatedFormat('d M Y') }}</td><td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">Rp {{ number_format($sale->selling_price, 0, ',', '.') }}</td><td class="whitespace-nowrap px-3 py-3 text-right font-semibold tabular-nums {{ $sale->profit < 0 ? 'text-danger' : 'text-success' }}">{{ $sale->profit < 0 ? '−' : '+' }}Rp {{ number_format(abs($sale->profit), 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-text-muted">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </x-app.table>
    </x-app.card>

    <dialog x-ref="customPeriod" class="w-[min(440px,calc(100vw-2rem))] rounded-lg border border-border bg-surface p-0 text-text backdrop:bg-text/40">
        <form method="GET" action="{{ route('dashboard') }}" class="space-y-4 p-5">
            <input type="hidden" name="period" value="custom">
            <h2 class="text-lg font-semibold">Periode khusus</h2>
            <label class="block text-[13px] font-semibold">Dari<input type="date" name="from" required class="mt-2 h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            <label class="block text-[13px] font-semibold">Sampai<input type="date" name="to" required class="mt-2 h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            <div class="flex justify-end gap-2"><button type="button" class="min-h-10 rounded-md border border-border-strong px-4 text-sm font-semibold" @click="$refs.customPeriod.close()">Batal</button><button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Terapkan</button></div>
        </form>
    </dialog>
</div>
@endsection
