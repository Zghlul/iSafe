<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Rules\ValidImei;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    private const SOLD_EDITABLE_FIELDS = [
        'battery_health',
        'physical_grade',
        'accessories',
        'warranty_until',
        'notes',
    ];

    public function add(array $attributes): Stock
    {
        return DB::transaction(function () use ($attributes): Stock {
            $this->ensureImeiAvailable((string) $attributes['imei']);

            $stock = Stock::query()->create([
                ...$attributes,
                'status' => Stock::STATUS_AVAILABLE,
            ]);
            $this->movement($stock, StockMovement::TYPE_IN);

            return $stock;
        }, 3);
    }

    /**
     * Add one unit for each IMEI atomically.
     *
     * @param  list<string>  $imeis
     * @return list<Stock>
     */
    public function addMany(array $attributes, array $imeis): array
    {
        return DB::transaction(function () use ($attributes, $imeis): array {
            $normalizedImeis = array_map(
                static fn (string $imei): string => trim($imei),
                $imeis,
            );
            $duplicates = array_keys(array_filter(
                array_count_values($normalizedImeis),
                static fn (int $count): bool => $count > 1,
            ));

            if ($duplicates !== []) {
                throw ValidationException::withMessages([
                    'imeis' => 'Daftar IMEI mengandung duplikat: '.implode(', ', $duplicates),
                ]);
            }

            foreach ($normalizedImeis as $imei) {
                $this->ensureImeiAvailable($imei);
            }

            $stocks = [];

            foreach ($normalizedImeis as $imei) {
                $stock = Stock::query()->create([
                    ...$attributes,
                    'imei' => $imei,
                    'status' => Stock::STATUS_AVAILABLE,
                ]);
                $this->movement($stock, StockMovement::TYPE_IN);
                $stocks[] = $stock;
            }

            return $stocks;
        }, 3);
    }

    public function update(Stock $stock, array $attributes): Stock
    {
        return DB::transaction(function () use ($stock, $attributes): Stock {
            $locked = $this->lockActiveStock($stock->id);
            $allowedFields = $locked->status === Stock::STATUS_SOLD
                ? self::SOLD_EDITABLE_FIELDS
                : array_diff($locked->getFillable(), ['status']);
            $lockedFields = array_diff_key($attributes, array_flip($allowedFields));

            if ($lockedFields !== []) {
                throw ValidationException::withMessages([
                    'stock' => $locked->status === Stock::STATUS_SOLD
                        ? 'Unit terjual hanya dapat mengubah baterai, grade, kelengkapan, garansi, dan catatan.'
                        : 'Ada field stok yang tidak dapat diubah.',
                ]);
            }

            $changes = array_intersect_key($attributes, array_flip($allowedFields));

            if (array_key_exists('imei', $changes)) {
                $this->ensureImeiAvailable((string) $changes['imei'], $locked->id);
            }

            $locked->fill($changes)->save();

            return $locked->refresh();
        }, 3);
    }

    public function restoreStock(int $stockId): Stock
    {
        return DB::transaction(function () use ($stockId): Stock {
            $stock = Stock::withTrashed()->whereKey($stockId)->lockForUpdate()->firstOrFail();

            if (! $stock->trashed()) {
                throw ValidationException::withMessages([
                    'stock' => 'Unit ini tidak berada di data terhapus.',
                ]);
            }

            $stock->restore();
            $this->movement($stock, StockMovement::TYPE_IN, 'Unit stok dipulihkan.');

            return $stock->refresh();
        }, 3);
    }

    public function remove(Stock $stock): void
    {
        DB::transaction(function () use ($stock): void {
            $locked = $this->lockActiveStock($stock->id);

            if ($locked->status !== Stock::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'stock' => 'Unit terjual tidak dapat dihapus.',
                ]);
            }

            $this->movement($locked, StockMovement::TYPE_REMOVED);
            $locked->delete();
        }, 3);
    }

    public function sell(int $stockId, array $saleAttributes): Sale
    {
        return DB::transaction(function () use ($stockId, $saleAttributes): Sale {
            $stock = $this->lockAvailableStock($stockId);
            $sale = Sale::query()->create($this->saleSnapshot($stock, $saleAttributes));

            $stock->update(['status' => Stock::STATUS_SOLD]);
            $this->movement($stock, StockMovement::TYPE_SOLD, null, $sale);

            return $sale;
        }, 3);
    }

    public function manualSale(array $stockAttributes, array $saleAttributes): Sale
    {
        return DB::transaction(function () use ($stockAttributes, $saleAttributes): Sale {
            $this->ensureImeiAvailable((string) $stockAttributes['imei']);

            $stock = Stock::query()->create([
                ...$stockAttributes,
                'status' => Stock::STATUS_SOLD,
                'source_name' => $stockAttributes['source_name'] ?? 'manual',
            ]);
            $sale = Sale::query()->create($this->saleSnapshot($stock, $saleAttributes));
            $this->movement($stock, StockMovement::TYPE_IN, 'Unit dicatat langsung saat penjualan.');
            $this->movement($stock, StockMovement::TYPE_SOLD, 'Unit manual langsung terjual.', $sale);

            return $sale;
        }, 3);
    }

    public function replaceSaleUnit(Sale $sale, int $newStockId, array $saleAttributes): Sale
    {
        return DB::transaction(function () use ($sale, $newStockId, $saleAttributes): Sale {
            $lockedSale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($lockedSale->stock_id === $newStockId) {
                $stock = $this->lockActiveStock($newStockId);
                $this->ensureSaleOwnsStock($stock, $lockedSale);

                $lockedSale->fill($this->saleSnapshot($stock, $saleAttributes))->save();

                return $lockedSale->refresh();
            }

            $stockIds = array_values(array_unique(array_filter([
                $lockedSale->stock_id,
                $newStockId,
            ])));
            sort($stockIds);

            $lockedStocks = Stock::query()
                ->whereIn('id', $stockIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $newStock = $lockedStocks->get($newStockId);

            if ($newStock === null || $newStock->status !== Stock::STATUS_AVAILABLE) {
                throw ValidationException::withMessages([
                    'stock_id' => 'Unit ini sudah terjual atau tidak tersedia.',
                ]);
            }

            if ($lockedSale->stock_id !== null) {
                $oldStock = $lockedStocks->get($lockedSale->stock_id);

                if ($oldStock === null || $oldStock->status !== Stock::STATUS_SOLD) {
                    throw ValidationException::withMessages([
                        'stock_id' => 'Status unit sebelumnya tidak konsisten. Periksa riwayat stok.',
                    ]);
                }

                $oldStock->update(['status' => Stock::STATUS_AVAILABLE]);
                $this->movement($oldStock, StockMovement::TYPE_RETURNED, 'Unit diganti pada transaksi.', $lockedSale);
            }

            $newStock->update(['status' => Stock::STATUS_SOLD]);
            $lockedSale->fill($this->saleSnapshot($newStock, $saleAttributes))->save();
            $this->movement($newStock, StockMovement::TYPE_SOLD, 'Unit ditetapkan pada transaksi.', $lockedSale);

            return $lockedSale->refresh();
        }, 3);
    }

    public function cancelSale(Sale $sale): void
    {
        DB::transaction(function () use ($sale): void {
            $lockedSale = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            if ($lockedSale->stock_id === null) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi ini belum terhubung ke stok. Jalankan stock:backfill terlebih dahulu.',
                ]);
            }

            $stock = $this->lockActiveStock($lockedSale->stock_id);
            $this->ensureSaleOwnsStock($stock, $lockedSale);
            $stock->update(['status' => Stock::STATUS_AVAILABLE]);
            $lockedSale->delete();

            $this->movement($stock, StockMovement::TYPE_RETURNED, 'Penjualan dibatalkan.', $lockedSale);
        }, 3);
    }

    public function restoreSale(int $saleId): Sale
    {
        return DB::transaction(function () use ($saleId): Sale {
            $sale = Sale::withTrashed()->whereKey($saleId)->lockForUpdate()->firstOrFail();

            if (! $sale->trashed()) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi ini tidak berada di data terhapus.',
                ]);
            }

            if ($sale->stock_id === null) {
                throw ValidationException::withMessages([
                    'sale' => 'Transaksi lama ini belum terhubung ke unit stok. Jalankan stock:backfill terlebih dahulu.',
                ]);
            }

            $stock = $this->lockAvailableStock($sale->stock_id);
            $stock->update(['status' => Stock::STATUS_SOLD]);
            $sale->restore();
            $this->movement($stock, StockMovement::TYPE_SOLD, 'Transaksi dipulihkan.', $sale);

            return $sale->refresh();
        }, 3);
    }

    private function lockActiveStock(int $stockId): Stock
    {
        return Stock::query()->whereKey($stockId)->lockForUpdate()->firstOrFail();
    }

    private function lockAvailableStock(int $stockId): Stock
    {
        $stock = $this->lockActiveStock($stockId);

        if ($stock->status !== Stock::STATUS_AVAILABLE) {
            throw ValidationException::withMessages([
                'stock_id' => 'Unit ini sudah terjual.',
            ]);
        }

        return $stock;
    }

    private function ensureImeiAvailable(string $imei, ?int $exceptId = null): void
    {
        if (! preg_match('/^\d{15}$/', $imei)
            || (config('sales.imei_luhn_check', true) && ! ValidImei::passesLuhn($imei))) {
            throw ValidationException::withMessages([
                'imei' => 'IMEI harus terdiri dari 15 digit dan lolos pemeriksaan Luhn.',
            ]);
        }

        $existing = Stock::withTrashed()
            ->where('imei', $imei)
            ->when($exceptId !== null, fn (Builder $query) => $query->where('id', '!=', $exceptId))
            ->first();

        if ($existing === null) {
            return;
        }

        throw ValidationException::withMessages([
            'imei' => $existing->trashed()
                ? 'IMEI ada di data terhapus. Pulihkan unit stok tersebut.'
                : 'IMEI ini sudah terdaftar pada stok.',
        ]);
    }

    private function ensureSaleOwnsStock(Stock $stock, Sale $sale): void
    {
        if ($stock->status !== Stock::STATUS_SOLD
            || ! Sale::withTrashed()->whereKey($sale->id)->where('stock_id', $stock->id)->exists()) {
            throw ValidationException::withMessages([
                'stock_id' => 'Status unit tidak cocok dengan transaksi ini.',
            ]);
        }
    }

    private function saleSnapshot(Stock $stock, array $saleAttributes): array
    {
        return [
            ...$saleAttributes,
            'stock_id' => $stock->id,
            'model' => $stock->phoneModel()->value('name'),
            'storage' => $stock->storage,
            'color' => $stock->color,
            'condition' => $stock->condition,
            'imei' => $stock->imei,
            'cost_price' => $stock->cost_price,
        ];
    }

    private function movement(
        Stock $stock,
        string $type,
        ?string $note = null,
        ?Sale $sale = null,
    ): void {
        StockMovement::query()->create([
            'stock_id' => $stock->id,
            'type' => $type,
            'sale_id' => $sale?->id,
            'moved_at' => now(),
            'note' => $note,
        ]);
    }
}
