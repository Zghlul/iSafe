@extends('layouts.app')

@section('title', 'Stok')

@section('content')
<div x-data="stockPage(@js(route('stocks.imei-check')), {{ (bool) ($editing || request()->boolean('create') || $errors->any()) ? 'true' : 'false' }}, @js(old('imei', $editing?->imei ?? '')))">
    <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-xs font-semibold text-text-muted">Ruang kerja / Stok</p>
            <h1 class="page-title">Stok iPhone</h1>
            <p class="mt-2 text-sm text-text-muted">Pantau unit, modal tertahan, dan pergerakan stok.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('stock-reports.show', 'summary') }}" class="inline-flex min-h-10 items-center rounded-md border border-border-strong px-4 text-sm font-semibold">Laporan stok</a>
            <button type="button" @click="openDrawer()" class="inline-flex min-h-10 items-center rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">+ Tambah stok</button>
        </div>
    </div>

    @if (session('success'))<div class="mb-4 rounded-md border border-success bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="mb-4 rounded-md border border-danger bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">{{ session('error') }}</div>@endif

    <div class="mb-4 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([['Unit tersedia', number_format($availableCount)], ['Nilai stok', 'Rp '.number_format($stockValue, 0, ',', '.')], ['Rata-rata umur', number_format($averageAge).' hari'], ['Model menipis', number_format($lowStockCount)]] as [$label, $value])
            <x-app.card><p class="text-xs font-semibold text-text-muted">{{ $label }}</p><p class="mt-2 text-xl font-semibold tabular-nums">{{ $value }}</p></x-app.card>
        @endforeach
    </div>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex flex-wrap gap-2" aria-label="Status stok">
            @foreach (['available' => 'Tersedia', 'sold' => 'Terjual', 'all' => 'Semua', 'deleted' => 'Terhapus'] as $key => $label)
                <a href="{{ route('stocks.index', [...request()->query(), 'tab' => $key, 'page' => null]) }}" @class([
                    'inline-flex min-h-9 items-center rounded-md border px-3 text-xs font-semibold',
                    'border-primary bg-primary-soft text-primary' => $tab === $key,
                    'border-border bg-surface text-text-muted' => $tab !== $key,
                ])>{{ $label }}</a>
            @endforeach
        </nav>
        @if ($tab !== 'deleted')
            <div class="flex gap-2 text-xs font-semibold">
                <a href="{{ route('stocks.index', [...request()->query(), 'view' => 'units']) }}" @class(['rounded-md px-3 py-2', 'bg-primary-soft text-primary' => $viewMode === 'units', 'text-text-muted' => $viewMode !== 'units'])>Daftar unit</a>
                <a href="{{ route('stocks.index', [...request()->query(), 'view' => 'summary']) }}" @class(['rounded-md px-3 py-2', 'bg-primary-soft text-primary' => $viewMode === 'summary', 'text-text-muted' => $viewMode !== 'summary'])>Rekap model</a>
            </div>
        @endif
    </div>

    <x-app.card class="mb-4">
        <form action="{{ route('stocks.index') }}" method="GET" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="hidden" name="view" value="{{ $viewMode }}">
            <label class="text-[13px] font-semibold">Cari<input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="IMEI, model, warna, asal" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            <label class="text-[13px] font-semibold">Model<select name="model" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Semua model</option>@foreach ($models as $model)<option value="{{ $model->id }}" @selected(($filters['model'] ?? '') == $model->id)>{{ $model->name }}</option>@endforeach</select></label>
            <label class="text-[13px] font-semibold">Kapasitas<select name="storage" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Semua kapasitas</option>@foreach (\App\Models\Sale::STORAGES as $storage)<option value="{{ $storage }}" @selected(($filters['storage'] ?? '') === $storage)>{{ $storage }}</option>@endforeach</select></label>
            <label class="text-[13px] font-semibold">Kondisi<select name="condition" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Semua kondisi</option><option value="new" @selected(($filters['condition'] ?? '') === 'new')>Baru</option><option value="used" @selected(($filters['condition'] ?? '') === 'used')>Second</option></select></label>
            <label class="text-[13px] font-semibold">Varian<select name="variant" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Semua varian</option><option value="ibox" @selected(($filters['variant'] ?? '') === 'ibox')>iBox</option><option value="inter" @selected(($filters['variant'] ?? '') === 'inter')>Inter</option><option value="other" @selected(($filters['variant'] ?? '') === 'other')>Lainnya</option></select></label>
            <label class="text-[13px] font-semibold">Masuk dari<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            <label class="text-[13px] font-semibold">Masuk sampai<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
            <div class="flex gap-2"><button class="min-h-10 flex-1 rounded-md bg-primary px-3 text-sm font-semibold text-on-primary">Terapkan</button><a href="{{ route('stocks.index', ['tab' => $tab, 'view' => $viewMode]) }}" class="inline-flex min-h-10 items-center rounded-md border border-border-strong px-3 text-sm font-semibold">Reset</a></div>
        </form>
    </x-app.card>

    @if ($viewMode === 'units' || $tab === 'deleted')
        <form action="{{ route('stocks.bulk-delete') }}" method="POST" onsubmit="return confirm('Hapus unit tersedia yang dipilih? Unit terjual akan dilewati.')">
            @csrf
            <x-app.card class="overflow-hidden p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div><h2 class="card-title">{{ $tab === 'deleted' ? 'Unit terhapus' : 'Daftar unit' }}</h2><p class="mt-1 text-xs text-text-muted">{{ number_format($rows->total()) }} unit cocok dengan filter</p></div>
                    <div class="flex gap-2">
                        @unless ($tab === 'deleted')
                            <a href="{{ route('stock-reports.export', request()->query()) }}" class="inline-flex min-h-9 items-center rounded-md border border-border-strong px-3 text-xs font-semibold">Export Excel</a>
                            <button type="submit" class="min-h-9 rounded-md border border-danger px-3 text-xs font-semibold text-danger">Hapus terpilih</button>
                        @endunless
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] text-left text-[13px]">
                        <thead class="bg-surface-muted text-xs font-semibold text-text-muted"><tr>
                            @if ($tab !== 'deleted')<th class="px-3 py-3"><input type="checkbox" aria-label="Pilih semua" @change="selectAll($event)"></th>@endif
                            <th class="px-3 py-3">Model / Spek</th><th class="px-3 py-3">Kondisi / IMEI</th><th class="px-3 py-3">Baterai</th><th class="px-3 py-3">Tanggal masuk</th><th class="px-3 py-3 text-right">Umur</th><th class="px-3 py-3 text-right">Modal</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Aksi</th>
                        </tr></thead>
                        <tbody>
                            @forelse ($rows as $stock)
                                @php($age = $stock->purchase_date->diffInDays(today()))
                                <tr class="border-t border-border">
                                    @if ($tab !== 'deleted')<td class="px-3 py-3"><input type="checkbox" name="ids[]" value="{{ $stock->id }}" class="stock-selection" @disabled($stock->status !== 'available') aria-label="Pilih unit {{ $stock->imei }}"></td>@endif
                                    <td class="px-3 py-3"><a href="{{ route('stocks.show', $stock) }}" class="font-semibold text-primary hover:underline">{{ $stock->phoneModel?->name ?? $stock->model }}</a><span class="block text-xs text-text-muted">{{ $stock->storage }} · {{ $stock->color }}</span></td>
                                    <td class="px-3 py-3"><span>{{ $stock->condition === 'new' ? 'Baru' : 'Second' }}</span><span class="block font-mono text-xs text-text-muted">{{ $stock->imei }}</span></td>
                                    <td class="px-3 py-3">{{ $stock->battery_health === null ? '—' : $stock->battery_health.'%' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $stock->purchase_date->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="px-3 py-3 text-right"><span class="tabular-nums">{{ number_format($age) }} hari</span>@if ($stock->status === 'available' && $age > $oldStockDays)<span class="ml-1 rounded-full bg-warning-soft px-2 py-1 text-[10px] font-semibold text-warning">Lama</span>@endif</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">Rp {{ number_format($stock->cost_price, 0, ',', '.') }}</td>
                                    <td class="px-3 py-3"><x-app.badge tone="{{ $stock->status === 'available' ? 'success' : ($stock->trashed() ? 'neutral' : 'warning') }}">{{ $stock->trashed() ? 'Terhapus' : ($stock->status === 'available' ? 'Tersedia' : 'Terjual') }}</x-app.badge></td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('stocks.show', $stock) }}" class="font-semibold text-primary">Detail</a>
                                        @if ($tab === 'deleted')
                                            <form action="{{ route('stocks.restore', $stock->id) }}" method="POST" class="ml-2 inline">@csrf<button class="font-semibold text-success">Pulihkan</button></form>
                                        @elseif ($stock->status === 'available')
                                            <a href="{{ route('stocks.index', ['edit' => $stock->id]) }}" class="ml-2 font-semibold">Edit</a>
                                            <form action="{{ route('stocks.destroy', $stock) }}" method="POST" class="ml-2 inline" onsubmit="return confirm('Hapus unit {{ $stock->imei }}?')">@csrf @method('DELETE')<button class="font-semibold text-danger">Hapus</button></form>
                                        @else
                                            <span class="ml-2 text-text-muted">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $tab === 'deleted' ? 8 : 9 }}" class="px-4 py-10 text-center text-sm text-text-muted">Tidak ada unit yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"><span class="text-xs text-text-muted">Menampilkan {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} dari {{ $rows->total() }}</span>{{ $rows->onEachSide(1)->links() }}</div>
            </x-app.card>
        </form>
    @else
        <x-app.card class="overflow-hidden p-0">
            <div class="border-b border-border px-5 py-4"><h2 class="card-title">Rekap per model, kapasitas, warna, dan kondisi</h2></div>
            <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-[13px]">
                <thead class="bg-surface-muted text-xs font-semibold text-text-muted"><tr><th class="px-3 py-3">Model / Spesifikasi</th><th class="px-3 py-3 text-right">Tersedia</th><th class="px-3 py-3 text-right">Terjual</th><th class="px-3 py-3 text-right">Total masuk</th><th class="px-3 py-3 text-right">Nilai stok</th><th class="px-3 py-3">Status</th></tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($minimum = $models->firstWhere('id', $row->phone_model_id)?->min_stock ?? \App\Models\Setting::value(\App\Models\Setting::DEFAULT_MIN_STOCK))
                        @php($low = $minimum !== null && $minimum !== '' && (int) $row->available < (int) $minimum)
                        <tr class="border-t border-border hover:bg-surface-muted"><td class="px-3 py-3"><a href="{{ route('stocks.index', ['model' => $row->phone_model_id, 'storage' => $row->storage, 'condition' => $row->condition, 'tab' => 'all']) }}" class="font-semibold text-primary hover:underline">{{ $row->model }}</a><span class="block text-xs text-text-muted">{{ $row->storage }} · {{ $row->color }} · {{ $row->condition === 'new' ? 'Baru' : 'Second' }}</span></td><td class="px-3 py-3 text-right font-semibold">{{ number_format($row->available) }}</td><td class="px-3 py-3 text-right">{{ number_format($row->sold) }}</td><td class="px-3 py-3 text-right">{{ number_format($row->total) }}</td><td class="px-3 py-3 text-right tabular-nums">Rp {{ number_format($row->stock_value, 0, ',', '.') }}</td><td class="px-3 py-3">@if ($low)<x-app.badge tone="warning">Menipis</x-app.badge>@else<span class="text-text-muted">—</span>@endif</td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-text-muted">Belum ada stok yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
            <div class="px-5 py-4">{{ $rows->links() }}</div>
        </x-app.card>
    @endif

    <x-app.drawer title="{{ $editing ? 'Edit unit stok' : 'Tambah stok' }}" open="drawerOpen" id="stock-drawer">
        @unless ($editing)
            <div class="mb-4 flex gap-2">
                <button type="button" @click="mode='single'" :class="mode === 'single' ? 'bg-primary-soft text-primary' : 'text-text-muted'" class="rounded-md px-3 py-2 text-xs font-semibold">Satu unit</button>
                <button type="button" @click="mode='bulk'" :class="mode === 'bulk' ? 'bg-primary-soft text-primary' : 'text-text-muted'" class="rounded-md px-3 py-2 text-xs font-semibold">Banyak IMEI</button>
            </div>
        @endunless
        <form action="{{ $editing ? route('stocks.update', $editing) : route('stocks.store') }}" method="POST" class="space-y-4" @submit="prepareSubmit($event)">
            @csrf
            @if ($editing) @method('PUT') @else <input type="hidden" name="bulk" :value="mode === 'bulk' ? 1 : 0">@endif
            @if ($editing && $editing->status === 'sold')
                <div class="rounded-md border border-warning bg-warning-soft p-3 text-sm text-warning">Unit terjual: hanya baterai, grade, kelengkapan, garansi, dan catatan yang dapat diubah.</div>
                @foreach (['battery_health' => 'Kesehatan baterai (%)', 'physical_grade' => 'Grade fisik', 'warranty_until' => 'Garansi sampai', 'notes' => 'Catatan'] as $field => $label)
                    <label class="block text-[13px] font-semibold">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $editing->$field) }}" @if ($field === 'battery_health') type="number" min="0" max="100" @elseif ($field === 'warranty_until') type="date" value="{{ old($field, $editing->warranty_until?->format('Y-m-d')) }}" @endif class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                @endforeach
                <label class="block text-[13px] font-semibold">Kelengkapan</label>
                @foreach (\App\Models\Stock::ACCESSORIES as $key => $label)<label class="mr-3 inline-flex items-center gap-2 text-xs"><input type="checkbox" name="accessories[]" value="{{ $key }}" @checked(in_array($key, old('accessories', $editing->accessories ?? [])))>{{ $label }}</label>@endforeach
            @else
                <label class="block text-[13px] font-semibold">Model iPhone<select name="phone_model_id" required class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Pilih model</option>@foreach ($models as $model)<option value="{{ $model->id }}" @selected(old('phone_model_id', $editing?->phone_model_id) == $model->id)>{{ $model->name }}</option>@endforeach</select></label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-[13px] font-semibold">Kapasitas<select name="storage" required class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">Pilih</option>@foreach (\App\Models\Sale::STORAGES as $storage)<option value="{{ $storage }}" @selected(old('storage', $editing?->storage) === $storage)>{{ $storage }}</option>@endforeach</select></label>
                    <label class="block text-[13px] font-semibold">Kondisi<select name="condition" required @change="condition=$event.target.value" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="new" @selected(old('condition', $editing?->condition ?? 'new') === 'new')>Baru</option><option value="used" @selected(old('condition', $editing?->condition) === 'used')>Second</option></select></label>
                </div>
                <label class="block text-[13px] font-semibold">Warna<input name="color" value="{{ old('color', $editing?->color) }}" list="stock-colors" required maxlength="50" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><datalist id="stock-colors">@foreach ($colors as $color)<option value="{{ $color }}">@endforeach</datalist></label>
                <div x-show="mode === 'single'">
                    <label class="block text-[13px] font-semibold">IMEI<input name="imei" x-model="imei" @input.debounce.300ms="checkImei()" inputmode="numeric" maxlength="15" pattern="[0-9]{15}" :required="mode === 'single'" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 font-mono text-sm" :class="imeiStatus === 'valid' ? 'border-success' : (['invalid','checksum','duplicate','deleted','error'].includes(imeiStatus) ? 'border-danger' : 'border-border-strong')">
                    </label><p class="mt-1 text-xs" x-text="imeiMessage"></p>
                </div>
                @unless ($editing)
                    <div x-show="mode === 'bulk'"><label class="block text-[13px] font-semibold">Daftar IMEI, satu per baris<textarea name="imeis" rows="6" :required="mode === 'bulk'" placeholder="IMEI satu&#10;IMEI dua" class="mt-2 w-full rounded-md border border-border-strong bg-surface px-3 py-2 font-mono text-sm"></textarea></label><p class="mt-1 text-xs text-text-muted">Semua IMEI diperiksa sebelum satu unit pun disimpan.</p></div>
                @endunless
                @unless($editing)
                    <label class="block text-[13px] font-semibold">Kesehatan baterai (%)<input name="battery_health" type="number" min="0" max="100" x-show="condition === 'used'" value="{{ old('battery_health') }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                @endunless
                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-[13px] font-semibold">Varian<select name="variant" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">—</option><option value="ibox" @selected(old('variant', $editing?->variant) === 'ibox')>iBox</option><option value="inter" @selected(old('variant', $editing?->variant) === 'inter')>Inter</option><option value="other" @selected(old('variant', $editing?->variant) === 'other')>Lainnya</option></select></label>
                    <label class="block text-[13px] font-semibold">Grade<select name="physical_grade" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"><option value="">—</option>@foreach (\App\Models\Stock::GRADES as $grade)<option value="{{ $grade }}" @selected(old('physical_grade', $editing?->physical_grade) === $grade)>Grade {{ $grade }}</option>@endforeach</select></label>
                </div>
                @unless ($editing)
                    <fieldset><legend class="text-[13px] font-semibold">Kelengkapan</legend><div class="mt-2 flex flex-wrap gap-x-3 gap-y-2">@foreach (\App\Models\Stock::ACCESSORIES as $key => $label)<label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="accessories[]" value="{{ $key }}" @checked(in_array($key, old('accessories', [])))>{{ $label }}</label>@endforeach</div></fieldset>
                @endunless
                <label class="block text-[13px] font-semibold">Garansi sampai<input name="warranty_until" type="date" value="{{ old('warranty_until', $editing?->warranty_until?->format('Y-m-d')) }}" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Tanggal masuk<input name="purchase_date" type="date" value="{{ old('purchase_date', $editing?->purchase_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Modal (Rp)<input name="cost_price" type="number" min="0" step="1" value="{{ old('cost_price', $editing?->cost_price) }}" required class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Asal barang<input name="source_name" value="{{ old('source_name', $editing?->source_name) }}" maxlength="255" class="mt-2 h-10 w-full rounded-md border border-border-strong bg-surface px-3 text-sm"></label>
                <label class="block text-[13px] font-semibold">Catatan<textarea name="notes" rows="3" maxlength="5000" class="mt-2 w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm">{{ old('notes', $editing?->notes) }}</textarea></label>
            @endif
            @foreach ($errors->all() as $message)<p class="text-xs text-danger">{{ $message }}</p>@endforeach
            <button class="min-h-11 w-full rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">{{ $editing ? 'Simpan perubahan' : 'Simpan stok' }}</button>
        </form>
    </x-app.drawer>
</div>
@endsection
