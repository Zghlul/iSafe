@extends('layouts.app')

@section('title', 'Export Excel')

@section('content')
<div>
    <div class="mb-5">
        <p class="mb-2 text-xs font-semibold text-text-muted">Kelola / Export Excel</p>
        <h1 class="page-title">Export Excel</h1>
        <p class="mt-2 text-sm text-text-muted">Unduh data transaksi dalam format .xlsx.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <x-app.card>
            <h2 class="card-title">Semua transaksi</h2>
            <p class="mt-2 text-sm text-text-muted">Ekspor seluruh transaksi aktif beserta baris total.</p>
            <form action="{{ route('exports.transactions') }}" method="GET" class="mt-5">
                <input type="hidden" name="scope" value="all">
                <button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Unduh semua data</button>
            </form>
        </x-app.card>
        <x-app.card>
            <h2 class="card-title">Per bulan</h2>
            <p class="mt-2 text-sm text-text-muted">Pilih periode bulanan untuk ekspor transaksi.</p>
            <form action="{{ route('exports.transactions') }}" method="GET" class="mt-5 space-y-3">
                <input type="hidden" name="scope" value="month">
                <label class="block text-[13px] font-semibold">Bulan<input name="month" type="number" min="1" max="12" value="{{ now()->month }}" required class="mt-1 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Tahun<input name="year" type="number" min="2000" max="2100" value="{{ now()->year }}" required class="mt-1 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Unduh periode</button>
            </form>
        </x-app.card>
        <x-app.card>
            <h2 class="card-title">Rentang tanggal</h2>
            <p class="mt-2 text-sm text-text-muted">Batasi ekspor pada tanggal awal dan akhir.</p>
            <form action="{{ route('exports.transactions') }}" method="GET" class="mt-5 space-y-3">
                <input type="hidden" name="scope" value="range">
                <label class="block text-[13px] font-semibold">Dari<input name="from" type="date" required class="mt-1 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Sampai<input name="to" type="date" required class="mt-1 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <button type="submit" class="min-h-10 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Unduh rentang</button>
            </form>
        </x-app.card>
    </div>
</div>
@endsection
