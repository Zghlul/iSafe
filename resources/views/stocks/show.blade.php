@extends('layouts.app')

@section('title', 'Detail unit stok')

@section('content')
@php($age = $stock->purchase_date->diffInDays(today()))
<div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold text-text-muted"><a href="{{ route('stocks.index') }}" class="text-primary hover:underline">Stok</a> / Detail unit</p>
            <h1 class="page-title">{{ $stock->phoneModel?->name }} · {{ $stock->storage }}</h1>
            <p class="mt-2 font-mono text-sm text-text-muted">{{ $stock->imei }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-app.badge tone="{{ $stock->status === 'available' ? 'success' : 'warning' }}">{{ $stock->status === 'available' ? 'Tersedia' : 'Terjual' }}</x-app.badge>
            @if ($stock->status === 'available')
                <a href="{{ route('stocks.index', ['edit' => $stock->id]) }}" class="inline-flex min-h-9 items-center rounded-md border border-border-strong px-3 text-xs font-semibold">Edit</a>
                <form action="{{ route('stocks.destroy', $stock) }}" method="POST" onsubmit="return confirm('Hapus unit ini dari stok?')">@csrf @method('DELETE')<button class="min-h-9 rounded-md border border-danger px-3 text-xs font-semibold text-danger">Hapus</button></form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <x-app.card>
            <h2 class="card-title">Spesifikasi unit</h2>
            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                <div><dt class="text-xs text-text-muted">Model</dt><dd class="mt-1 font-semibold">{{ $stock->phoneModel?->name }}</dd></div>
                <div><dt class="text-xs text-text-muted">Warna / kapasitas</dt><dd class="mt-1 font-semibold">{{ $stock->color }} · {{ $stock->storage }}</dd></div>
                <div><dt class="text-xs text-text-muted">Kondisi</dt><dd class="mt-1">{{ $stock->condition === 'new' ? 'Baru' : 'Second' }}</dd></div>
                <div><dt class="text-xs text-text-muted">IMEI</dt><dd class="mt-1 font-mono">{{ $stock->imei }}</dd></div>
                <div><dt class="text-xs text-text-muted">Kesehatan baterai</dt><dd class="mt-1">{{ $stock->battery_health === null ? '—' : $stock->battery_health.'%' }}</dd></div>
                <div><dt class="text-xs text-text-muted">Varian / grade</dt><dd class="mt-1">{{ ['ibox' => 'iBox', 'inter' => 'Inter', 'other' => 'Lainnya'][$stock->variant] ?? '—' }} / {{ $stock->physical_grade ?? '—' }}</dd></div>
                <div><dt class="text-xs text-text-muted">Tanggal masuk</dt><dd class="mt-1">{{ $stock->purchase_date->locale('id')->translatedFormat('d M Y') }} · {{ number_format($age) }} hari @if ($stock->status === 'available' && $age > $oldStockDays)<x-app.badge tone="warning">Lama</x-app.badge>@endif</dd></div>
                <div><dt class="text-xs text-text-muted">Modal</dt><dd class="mt-1 font-semibold tabular-nums">Rp {{ number_format($stock->cost_price, 0, ',', '.') }}</dd></div>
                <div><dt class="text-xs text-text-muted">Garansi sampai</dt><dd class="mt-1">{{ $stock->warranty_until?->locale('id')->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-text-muted">Asal barang</dt><dd class="mt-1">{{ $stock->source_name ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-xs text-text-muted">Kelengkapan</dt><dd class="mt-1">{{ collect($stock->accessories ?? [])->map(fn ($key) => \App\Models\Stock::ACCESSORIES[$key] ?? $key)->join(', ') ?: '—' }}</dd></div>
                <div class="col-span-2"><dt class="text-xs text-text-muted">Catatan</dt><dd class="mt-1 whitespace-pre-line">{{ $stock->notes ?: '—' }}</dd></div>
            </dl>
            @if ($stock->status === 'sold')
                @php($linkedSale = $stock->sales->first())
                <div class="mt-5 rounded-md bg-surface-muted p-3 text-sm">
                    <p class="font-semibold">Transaksi penjualan</p>
                    @if ($linkedSale)
                        <a href="{{ route('sales.index', ['q' => $stock->imei]) }}" class="mt-1 inline-block text-primary underline">{{ $linkedSale->sale_date->locale('id')->translatedFormat('d M Y') }} · {{ $linkedSale->buyer_name }} · Rp {{ number_format($linkedSale->selling_price, 0, ',', '.') }}</a>
                    @else
                        <p class="mt-1 text-text-muted">Unit berstatus terjual, namun transaksi historisnya belum tersedia.</p>
                    @endif
                </div>
            @endif
        </x-app.card>

        <x-app.card class="p-0">
            <div class="border-b border-border px-5 py-4"><h2 class="card-title">Riwayat pergerakan</h2></div>
            <div class="divide-y divide-border">
                @forelse ($movements as $movement)
                    <div class="flex justify-between gap-4 px-5 py-4">
                        <div><p class="text-sm font-semibold">{{ ['in' => 'Stok masuk', 'sold' => 'Terjual', 'returned' => 'Kembali ke stok', 'removed' => 'Unit dihapus'][$movement->type] ?? $movement->type }}</p><p class="mt-1 text-xs text-text-muted">{{ $movement->note ?: '—' }}</p></div>
                        <time class="shrink-0 text-right text-xs text-text-muted">{{ $movement->moved_at->locale('id')->translatedFormat('d M Y H:i') }}</time>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-text-muted">Belum ada riwayat pergerakan.</p>
                @endforelse
            </div>
            <div class="px-5 py-4">{{ $movements->links() }}</div>
        </x-app.card>
    </div>
</div>
@endsection
