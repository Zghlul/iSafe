@extends('layouts.app')

@section('title', 'Transaksi')

@php
    $formStock = $editing?->stock ? [
        'id' => $editing->stock->id,
        'imei' => $editing->stock->imei,
        'model' => $editing->stock->phoneModel->name,
        'storage' => $editing->stock->storage,
        'color' => $editing->stock->color,
        'condition' => $editing->stock->condition,
        'cost_price' => $editing->stock->cost_price,
        'battery_health' => $editing->stock->battery_health,
        'variant' => $editing->stock->variant,
    ] : $availableStocks->firstWhere('id', (int) old('stock_id'));
    $formSale = [
        'id' => $editing?->id,
        'unit_mode' => old('unit_mode', 'stock'),
        'sale_date' => old('sale_date', $editing?->sale_date->format('Y-m-d') ?? now()->format('Y-m-d')),
        'seller_name' => old('seller_name', $editing?->seller_name ?? ''),
        'buyer_name' => old('buyer_name', $editing?->buyer_name ?? ''),
        'buyer_phone' => old('buyer_phone', $editing?->buyer_phone ?? ''),
        'stock_id' => old('stock_id', $editing?->stock_id ?? ''),
        'selling_price' => old('selling_price', $editing?->selling_price ?? ''),
        'payment_method' => old('payment_method', $editing?->payment_method ?? 'transfer'),
        'notes' => old('notes', $editing?->notes ?? ''),
        'stock' => $formStock,
    ];
@endphp

@section('content')
    <div x-data="salesPage(@js($formSale), @js(route('stocks.available-search')), {{ $errors->any() || request()->boolean('create') || $editing ? 'true' : 'false' }}, @js($allowManualSale), @js($availableStocks))">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="mb-2 text-xs font-semibold text-text-muted">Ruang kerja / Transaksi</p>
                <h1 class="page-title">Transaksi</h1>
                <p class="mt-2 text-sm text-text-muted">Kelola penjualan iPhone dan pantau keuntungan.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('sales.index', ['deleted' => $deleted ? null : 1]) }}" class="inline-flex min-h-10 items-center rounded-md border border-border-strong bg-surface px-4 text-sm font-semibold text-text">
                    {{ $deleted ? 'Transaksi aktif' : 'Terhapus' }}
                </a>
                @unless ($deleted)
                    <a href="{{ route('sales.export', request()->query()) }}" class="inline-flex min-h-10 items-center rounded-md border border-border-strong bg-surface px-4 text-sm font-semibold text-text">Export Excel</a>
                    <button type="button" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-primary px-4 text-sm font-semibold text-on-primary hover:bg-primary-hover" @click="openDrawer()">
                        <span aria-hidden="true">+</span>
                        Tambah transaksi
                    </button>
                @endunless
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md border border-success bg-success-soft px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-md border border-danger bg-danger-soft px-4 py-3 text-sm text-danger" role="alert">{{ session('error') }}</div>
        @endif

        <x-app.card class="mb-4">
            <form action="{{ route('sales.index') }}" method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if ($deleted)
                    <input type="hidden" name="deleted" value="1">
                @endif
                <div class="sm:col-span-2">
                    <label for="filter-q" class="mb-1.5 block text-[13px] font-semibold">Cari transaksi</label>
                    <input id="filter-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="IMEI, pembeli, penjual, model" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="filter-from" class="mb-1.5 block text-[13px] font-semibold">Dari tanggal</label>
                    <input id="filter-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="filter-to" class="mb-1.5 block text-[13px] font-semibold">Sampai tanggal</label>
                    <input id="filter-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label for="filter-model" class="mb-1.5 block text-[13px] font-semibold">Model</label>
                    <select id="filter-model" name="model" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                        <option value="">Semua model</option>
                        @foreach ($models as $model)
                            <option value="{{ $model }}" @selected(($filters['model'] ?? '') === $model)>{{ $model }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter-condition" class="mb-1.5 block text-[13px] font-semibold">Kondisi</label>
                    <select id="filter-condition" name="condition" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                        <option value="">Semua kondisi</option>
                        <option value="new" @selected(($filters['condition'] ?? '') === 'new')>Baru</option>
                        <option value="used" @selected(($filters['condition'] ?? '') === 'used')>Second</option>
                    </select>
                </div>
                <div>
                    <label for="filter-payment" class="mb-1.5 block text-[13px] font-semibold">Metode bayar</label>
                    <select id="filter-payment" name="payment_method" class="h-9 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                        <option value="">Semua metode</option>
                        <option value="transfer" @selected(($filters['payment_method'] ?? '') === 'transfer')>Transfer</option>
                        <option value="cash" @selected(($filters['payment_method'] ?? '') === 'cash')>Tunai</option>
                        <option value="installment" @selected(($filters['payment_method'] ?? '') === 'installment')>Cicilan</option>
                        <option value="other" @selected(($filters['payment_method'] ?? '') === 'other')>Lainnya</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="inline-flex h-9 flex-1 items-center justify-center rounded-md bg-primary px-4 text-sm font-semibold text-on-primary">Terapkan</button>
                    <a href="{{ route('sales.index', $deleted ? ['deleted' => 1] : []) }}" class="inline-flex h-9 items-center justify-center rounded-md border border-border-strong px-4 text-sm font-semibold">Reset</a>
                </div>
            </form>
        </x-app.card>

        <form method="POST" action="{{ route($deleted ? 'sales.bulk-restore' : 'sales.bulk-delete') }}" id="bulk-sales-form" onsubmit="return confirm('{{ $deleted ? 'Pulihkan transaksi terpilih?' : 'Hapus transaksi terpilih?' }}')">
            @csrf
            <x-app.card class="p-0">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4">
                    <div>
                        <h2 class="card-title">{{ $deleted ? 'Transaksi terhapus' : 'Daftar transaksi' }}</h2>
                        <p class="mt-1 text-xs text-text-muted">{{ number_format($totals->units) }} unit · Omzet Rp {{ number_format($totals->revenue, 0, ',', '.') }} · Modal Rp {{ number_format($totals->cost, 0, ',', '.') }} · Untung <span class="{{ $totals->profit < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($totals->profit, 0, ',', '.') }}</span></p>
                    </div>
                    <button type="submit" class="inline-flex min-h-9 items-center rounded-md border border-border-strong px-3 text-xs font-semibold" @if ($deleted) formaction="{{ route('sales.bulk-restore') }}" @endif>
                        {{ $deleted ? 'Pulihkan terpilih' : 'Hapus terpilih' }}
                    </button>
                </div>
                <x-app.table class="rounded-none border-0">
                    <thead class="bg-surface-muted text-[13px] font-semibold text-text-muted">
                        <tr>
                            <th scope="col" class="px-3 py-3"><input type="checkbox" aria-label="Pilih semua transaksi di halaman ini" @change="selectAll($event)"></th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Model</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Pembeli</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Penjual</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Kondisi</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">IMEI</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Tanggal</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Bayar</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Harga jual</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Untung</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="border-t border-border hover:bg-surface-muted/70">
                                <td class="px-3 py-3"><input type="checkbox" name="ids[]" value="{{ $sale->id }}" class="sale-selection" aria-label="Pilih transaksi {{ $sale->imei }}"></td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <span class="font-semibold">{{ $sale->model }}</span>
                                    <span class="block text-xs text-text-muted">{{ $sale->storage }} · {{ $sale->color }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $sale->buyer_name }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $sale->seller_name }}</td>
                                <td class="px-3 py-3"><x-app.badge :tone="$sale->condition === 'new' ? 'success' : 'warning'">{{ $sale->condition === 'new' ? 'Baru' : 'Second' }}</x-app.badge></td>
                                <td class="whitespace-nowrap px-3 py-3 font-mono text-xs">{{ $sale->imei }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ $sale->sale_date->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="whitespace-nowrap px-3 py-3">{{ ['transfer' => 'Transfer', 'cash' => 'Tunai', 'installment' => 'Cicilan', 'other' => 'Lainnya'][$sale->payment_method] }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">Rp {{ number_format($sale->selling_price, 0, ',', '.') }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-right font-bold tabular-nums {{ $sale->profit < 0 ? 'text-danger' : 'text-success' }}">{{ $sale->profit < 0 ? '−' : '+' }}Rp {{ number_format(abs($sale->profit), 0, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if ($deleted)
                                        <button type="button" class="min-h-9 rounded-md border border-border-strong px-2 text-xs font-semibold" onclick="document.getElementById('restore-{{ $sale->id }}').submit()">Pulihkan</button>
                                    @else
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button" class="min-h-9 rounded-md border border-border-strong px-2 text-xs font-semibold" @click="openDrawer(@js($sale->only(['id', 'sale_date', 'seller_name', 'buyer_name', 'buyer_phone', 'selling_price', 'payment_method', 'notes', 'stock_id']) + ['sale_date' => $sale->sale_date->format('Y-m-d'), 'stock' => $sale->stock ? ['id' => $sale->stock->id, 'imei' => $sale->stock->imei, 'model' => $sale->stock->phoneModel->name, 'storage' => $sale->stock->storage, 'color' => $sale->stock->color, 'condition' => $sale->stock->condition, 'cost_price' => $sale->stock->cost_price, 'battery_health' => $sale->stock->battery_health, 'variant' => $sale->stock->variant] : null]))">Edit</button>
                                            <button type="button" class="min-h-9 rounded-md border border-danger px-2 text-xs font-semibold text-danger" onclick="document.getElementById('delete-{{ $sale->id }}').submit()">Hapus</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-4 py-12 text-center text-sm text-text-muted">{{ $deleted ? 'Tidak ada transaksi terhapus untuk filter ini.' : 'Belum ada transaksi untuk filter ini.' }}</td></tr>
                        @endforelse
                    </tbody>
                </x-app.table>
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <p class="text-xs text-text-muted">Menampilkan {{ $sales->firstItem() ?? 0 }}–{{ $sales->lastItem() ?? 0 }} dari {{ $sales->total() }} transaksi</p>
                    {{ $sales->onEachSide(1)->links() }}
                </div>
            </x-app.card>
        </form>

        @foreach ($sales as $sale)
            @if ($deleted)
                <form id="restore-{{ $sale->id }}" action="{{ route('sales.restore', $sale->id) }}" method="POST" class="hidden">@csrf</form>
            @else
                <form id="delete-{{ $sale->id }}" action="{{ route('sales.destroy', $sale) }}" method="POST" class="hidden" onsubmit="return confirm('Hapus transaksi {{ $sale->imei }}?')">@csrf @method('DELETE')</form>
            @endif
        @endforeach

        @unless ($deleted)
            <x-app.drawer title="Transaksi" open="drawerOpen" id="sale-drawer">
                <form :action="sale.id ? `/transactions/${sale.id}` : '{{ route('sales.store') }}'" method="POST" class="space-y-4" @submit="prepareSubmit($event)">
                    @csrf
                    <input type="hidden" name="_method" value="PUT" :disabled="!sale.id">
                    <input type="hidden" name="unit_mode" x-model="unitMode" :disabled="sale.id">
                    <input type="hidden" name="stock_id" :value="selectedStock?.id ?? ''" :disabled="unitMode !== 'stock'">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-app.input name="sale_date" label="Tanggal transaksi" type="date" required x-model="sale.sale_date" />
                        <div>
                            <label for="sale-seller" class="mb-2 block text-[13px] font-semibold">Nama penjual</label>
                            <input id="sale-seller" name="seller_name" list="seller-names" x-model="sale.seller_name" required maxlength="100" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                            <datalist id="seller-names">@foreach ($sellers as $seller)<option value="{{ $seller }}">@endforeach</datalist>
                            @error('seller_name')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                        <x-app.input name="buyer_name" label="Nama pembeli" required x-model="sale.buyer_name" maxlength="120" />
                        <x-app.input name="buyer_phone" label="No. HP pembeli" type="tel" x-model="sale.buyer_phone" maxlength="20" />
                    </div>

                    <div x-show="!sale.id && allowManual" class="flex gap-2 rounded-md bg-surface-muted p-1">
                        <button type="button" @click="unitMode = 'stock'" class="min-h-10 flex-1 rounded px-3 text-sm font-semibold" :class="unitMode === 'stock' ? 'bg-surface shadow-sm' : 'text-text-muted'">Ambil dari stok</button>
                        <button type="button" @click="unitMode = 'manual'" class="min-h-10 flex-1 rounded px-3 text-sm font-semibold" :class="unitMode === 'manual' ? 'bg-surface shadow-sm' : 'text-text-muted'">Penjualan manual</button>
                    </div>
                    <section x-show="unitMode === 'stock'" class="space-y-3">
                        <div>
                            <label for="stock-search" class="mb-2 block text-[13px] font-semibold">Cari unit stok (IMEI atau model)</label>
                            <input id="stock-search" x-model="stockQuery" @input.debounce.250ms="searchStocks()" type="search" placeholder="Ketik minimal 2 karakter..." class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                        </div>
                        <div x-show="stockResults.length" class="max-h-48 overflow-y-auto rounded-md border border-border">
                            <template x-for="stock in stockResults" :key="stock.id">
                                <button type="button" @click="chooseStock(stock)" class="block w-full border-b border-border px-3 py-2 text-left last:border-0 hover:bg-surface-muted">
                                    <span class="block font-semibold" x-text="`${stock.model} ${stock.storage} · ${stock.color}`"></span>
                                    <span class="block font-mono text-xs text-text-muted" x-text="stock.imei"></span>
                                </button>
                            </template>
                        </div>
                        <template x-if="selectedStock">
                            <div class="rounded-md border border-success bg-success-soft p-3 text-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold" x-text="`${selectedStock.model} ${selectedStock.storage} · ${selectedStock.color}`"></p>
                                        <p class="mt-1 font-mono text-xs" x-text="selectedStock.imei"></p>
                                        <p class="mt-1 text-xs text-text-muted" x-text="`Modal ${formatRupiah(selectedStock.cost_price)} · ${selectedStock.condition === 'new' ? 'Baru' : 'Second'}`"></p>
                                    </div>
                                    <button type="button" x-show="!sale.id" @click="selectedStock = null" class="text-xs font-semibold underline">Ganti unit</button>
                                </div>
                            </div>
                        </template>
                        @error('stock_id')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                    </section>

                    <fieldset x-show="unitMode === 'manual'" :disabled="unitMode !== 'manual' || sale.id" class="space-y-4 rounded-md border border-border p-4">
                        <legend class="px-1 text-sm font-semibold">Detail unit manual</legend>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label for="sale-model" class="mb-2 block text-[13px] font-semibold">Model iPhone</label>
                                <select id="sale-model" name="phone_model_id" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                                    <option value="">Pilih model</option>
                                    @foreach ($phoneModels as $phoneModel)<option value="{{ $phoneModel->id }}" @selected(old('phone_model_id') == $phoneModel->id)>{{ $phoneModel->name }}</option>@endforeach
                                </select>
                                @error('phone_model_id')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="sale-storage" class="mb-2 block text-[13px] font-semibold">Kapasitas</label>
                                <select id="sale-storage" name="storage" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                                    <option value="">Pilih kapasitas</option>
                                    @foreach (\App\Models\Sale::STORAGES as $storage)<option value="{{ $storage }}" @selected(old('storage') === $storage)>{{ $storage }}</option>@endforeach
                                </select>
                                @error('storage')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            </div>
                            <x-app.input name="color" label="Warna" maxlength="50" />
                            <div>
                                <label for="sale-condition" class="mb-2 block text-[13px] font-semibold">Kondisi</label>
                                <select id="sale-condition" name="condition" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                                    <option value="new">Baru</option><option value="used">Second</option>
                                </select>
                                @error('condition')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="sale-imei" class="mb-2 block text-[13px] font-semibold">IMEI</label>
                                <input id="sale-imei" name="imei" inputmode="numeric" pattern="[0-9]{15}" maxlength="15" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 font-mono text-sm">
                                @error('imei')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="sale-cost" class="mb-2 block text-[13px] font-semibold">Modal (Rp)</label>
                                <input id="sale-cost" name="cost_price" @input="formatPrice($event, 'cost_price')" required inputmode="numeric" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm tabular-nums">
                                @error('cost_price')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </fieldset>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="sale-price" class="mb-2 block text-[13px] font-semibold">Harga jual (Rp)</label>
                            <input id="sale-price" name="selling_price" x-model="sale.selling_price" @input="formatPrice($event, 'selling_price')" required inputmode="numeric" class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm tabular-nums">
                            @error('selling_price')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                        </div>
                        <div x-show="unitMode === 'stock'" class="rounded-md border border-border bg-surface-muted px-3 py-2">
                            <span class="block text-xs font-semibold text-text-muted">Modal unit stok</span>
                            <span class="mt-1 block font-semibold tabular-nums" x-text="selectedStock ? formatRupiah(selectedStock.cost_price) : 'Pilih unit terlebih dahulu'"></span>
                        </div>
                        <div x-show="unitMode === 'manual'" class="rounded-md border border-border bg-surface-muted px-3 py-2">
                            <span class="block text-xs font-semibold text-text-muted">Modal manual</span>
                            <span class="mt-1 block font-semibold tabular-nums" x-text="formatRupiah(document.getElementById('sale-cost')?.value.replace(/\D/g, '') || 0)"></span>
                        </div>
                    </div>
                    <div class="rounded-md bg-success-soft p-4">
                        <span class="block text-xs font-semibold text-success">Keuntungan terhitung otomatis</span>
                        <span class="mt-1 block text-xl font-semibold tabular-nums" :class="profit < 0 ? 'text-danger' : 'text-success'" x-text="formatRupiah(profit)"></span>
                        <span x-show="profit < 0" class="mt-1 block text-xs text-danger">Harga jual lebih rendah daripada modal.</span>
                    </div>
                    <div>
                        <label for="sale-payment" class="mb-2 block text-[13px] font-semibold">Metode pembayaran</label>
                        <select id="sale-payment" name="payment_method" x-model="sale.payment_method" required class="h-11 w-full rounded-md border border-border-strong bg-surface px-3 text-sm">
                            <option value="transfer">Transfer</option><option value="cash">Tunai</option><option value="installment">Cicilan</option><option value="other">Lainnya</option>
                        </select>
                        @error('payment_method')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="sale-notes" class="mb-2 block text-[13px] font-semibold">Catatan</label>
                        <textarea id="sale-notes" name="notes" x-model="sale.notes" rows="3" maxlength="5000" class="w-full rounded-md border border-border-strong bg-surface px-3 py-2 text-sm"></textarea>
                        @error('notes')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-border bg-surface py-4">
                        <button type="button" class="inline-flex min-h-11 items-center rounded-md border border-border-strong px-4 text-sm font-semibold" @click="drawerOpen = false">Batal</button>
                        <button type="submit" class="inline-flex min-h-11 items-center rounded-md bg-primary px-5 text-sm font-semibold text-on-primary">Simpan transaksi</button>
                    </div>
                </form>
            </x-app.drawer>
        @endunless
    </div>
@endsection
