<?php

namespace App\Services;

use App\Models\PhoneModel;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockBackfillService
{
    public function run(): int
    {
        return DB::transaction(function (): int {
            $processed = 0;

            Sale::query()
                ->whereNull('stock_id')
                ->orderBy('id')
                ->chunkById(500, function ($sales) use (&$processed): void {
                    foreach ($sales as $sale) {
                        $model = PhoneModel::query()->firstOrCreate(['name' => $sale->model]);

                        $stock = Stock::withTrashed()->where('imei', $sale->imei)->first();

                        if ($stock !== null) {
                            throw new RuntimeException(
                                "Backfill dihentikan: IMEI {$sale->imei} sudah digunakan oleh unit stok #{$stock->id}. Periksa datanya sebelum melanjutkan.",
                            );
                        }

                        $stock = Stock::query()->create([
                            'phone_model_id' => $model->id,
                            'storage' => $sale->storage,
                            'color' => $sale->color,
                            'condition' => $sale->condition,
                            'imei' => $sale->imei,
                            'purchase_date' => $sale->sale_date,
                            'cost_price' => $sale->cost_price,
                            'source_name' => 'backfill',
                            'status' => Stock::STATUS_SOLD,
                        ]);

                        $sale->update(['stock_id' => $stock->id]);

                        StockMovement::query()->create([
                            'stock_id' => $stock->id,
                            'type' => StockMovement::TYPE_IN,
                            'moved_at' => $sale->sale_date->startOfDay(),
                            'note' => 'Stok dibuat dari transaksi historis.',
                        ]);
                        StockMovement::query()->create([
                            'stock_id' => $stock->id,
                            'type' => StockMovement::TYPE_SOLD,
                            'sale_id' => $sale->id,
                            'moved_at' => $sale->sale_date->startOfDay(),
                            'note' => 'Transaksi historis ditautkan ke stok.',
                        ]);

                        $processed++;
                    }
                });

            return $processed;
        }, 3);
    }
}
