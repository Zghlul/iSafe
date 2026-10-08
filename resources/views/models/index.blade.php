@extends('layouts.app')

@section('title', 'Model iPhone')

@section('content')
<div>
    <div class="mb-5">
        <p class="mb-2 text-xs font-semibold text-text-muted">Kelola / Model iPhone</p>
        <h1 class="page-title">Model iPhone</h1>
        <p class="mt-2 text-sm text-text-muted">Kelola pilihan model pada formulir transaksi.</p>
    </div>

    @if (session('success'))<div class="mb-4 rounded-md border border-success bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mb-4 rounded-md border border-danger bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">{{ session('error') }}</div>@endif

    <x-app.card class="mb-4">
        <h2 class="card-title">Tambah model</h2>
        <form action="{{ route('models.store') }}" method="POST" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="model-name" class="mb-2 block text-[13px] font-semibold">Nama model iPhone</label>
                <input id="model-name" name="name" value="{{ old('name') }}" required maxlength="80" placeholder="Contoh: iPhone 17 Pro" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                @error('name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="min-h-11 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Tambah model</button>
        </form>
    </x-app.card>

    <x-app.card class="p-0">
        <div class="border-b border-border px-5 py-4"><h2 class="card-title">Daftar model ({{ $models->total() }})</h2></div>
        <x-app.table class="rounded-none border-0">
            <thead class="bg-surface-muted text-[13px] font-semibold text-text-muted"><tr><th class="px-4 py-3">Nama model</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($models as $model)
                    <tr class="border-t border-border">
                        <td class="px-4 py-3">
                            <form id="update-model-{{ $model->id }}" action="{{ route('models.update', $model) }}" method="POST" class="flex flex-wrap items-center gap-3">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $model->name }}" required maxlength="80" aria-label="Nama model iPhone" class="h-10 min-w-0 flex-1 rounded-md border border-border-strong bg-surface px-3 text-sm">
                                            <label class="flex items-center gap-2 text-xs font-semibold text-text-muted">Stok min.
                                                <input name="min_stock" type="number" min="0" max="65535" value="{{ $model->min_stock }}" aria-label="Stok minimum {{ $model->name }}" class="h-10 w-24 rounded-md border border-border-strong bg-surface px-2 text-sm">
                                            </label>
                                            <button type="submit" class="min-h-9 rounded-md border border-border-strong px-3 text-xs font-semibold">Simpan</button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form action="{{ route('models.destroy', $model) }}" method="POST" onsubmit="return confirm('Hapus {{ $model->name }} dari pilihan?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="min-h-9 rounded-md border border-danger px-3 text-xs font-semibold text-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-text-muted">Belum ada model iPhone.</td></tr>
                @endforelse
            </tbody>
        </x-app.table>
        <div class="px-5 py-4">{{ $models->links() }}</div>
    </x-app.card>
</div>
@endsection
