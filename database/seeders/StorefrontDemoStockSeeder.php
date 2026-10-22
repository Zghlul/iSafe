<?php

namespace Database\Seeders;

use App\Models\PhoneModel;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StorefrontDemoStockSeeder extends Seeder
{
    private const DEMO_STOCK = [
        ['iPhone 13', '128GB', 'Midnight', 'used', 88, 'B', 'ibox'],
        ['iPhone 13 Pro', '256GB', 'Sierra Blue', 'used', 86, 'B', 'ibox'],
        ['iPhone 13 Pro Max', '256GB', 'Graphite', 'used', 90, 'A', 'inter'],
        ['iPhone 14', '128GB', 'Blue', 'used', 91, 'A', 'ibox'],
        ['iPhone 14 Pro', '256GB', 'Deep Purple', 'used', 87, 'B', 'ibox'],
        ['iPhone 14 Pro Max', '256GB', 'Space Black', 'used', 89, 'A', 'inter'],
        ['iPhone 15', '128GB', 'Pink', 'used', 95, 'A', 'ibox'],
        ['iPhone 15 Pro', '256GB', 'Natural Titanium', 'used', 92, 'A', 'ibox'],
        ['iPhone 15 Pro Max', '256GB', 'Blue Titanium', 'used', 90, 'A', 'inter'],
        ['iPhone 16', '128GB', 'Ultramarine', 'used', 97, 'A', 'ibox'],
        ['iPhone 16 Plus', '128GB', 'Teal', 'used', 96, 'A', 'ibox'],
        ['iPhone 16 Pro', '256GB', 'Desert Titanium', 'used', 98, 'A', 'inter'],
        ['iPhone 16 Pro Max', '512GB', 'Black Titanium', 'used', 99, 'A', 'ibox'],
        ['iPhone 17', '256GB', 'Sage', 'new', null, null, 'ibox'],
        ['iPhone 17 Pro Max', '512GB', 'Cosmic Orange', 'new', null, null, 'ibox'],
    ];

    public function run(): void
    {
        $stocks = app(StockService::class);

        DB::transaction(function () use ($stocks): void {
            foreach (self::DEMO_STOCK as $index => [$modelName, $storage, $color, $condition, $battery, $grade, $variant]) {
                $imei = $this->generateImei($index + 1);
                $existing = Stock::withTrashed()->where('imei', $imei)->first();

                if ($existing !== null) {
                    if ($existing->source_name === 'Demo katalog') {
                        continue;
                    }

                    throw new RuntimeException(
                        "IMEI demo {$imei} sudah digunakan stok lain (#{$existing->id}); stok demo tidak dibuat.",
                    );
                }

                $model = PhoneModel::query()->firstOrCreate(['name' => $modelName]);

                $stocks->add([
                    'phone_model_id' => $model->id,
                    'storage' => $storage,
                    'color' => $color,
                    'condition' => $condition,
                    'imei' => $imei,
                    'battery_health' => $battery,
                    'variant' => $variant,
                    'physical_grade' => $grade,
                    'accessories' => ['box', 'cable'],
                    'purchase_date' => today()->subDays($index + 1)->toDateString(),
                    'cost_price' => 0,
                    'source_name' => 'Demo katalog',
                    'notes' => 'DATA DUMMY katalog. Bukan unit atau penawaran nyata.',
                ]);
            }
        });
    }

    private function generateImei(int $sequence): string
    {
        $prefix = sprintf('9900000000%04d', $sequence);

        for ($checkDigit = 0; $checkDigit <= 9; $checkDigit++) {
            $imei = $prefix.$checkDigit;

            if (\App\Rules\ValidImei::passesLuhn($imei)) {
                return $imei;
            }
        }

        throw new RuntimeException("Tidak dapat membuat IMEI demo untuk unit {$sequence}.");
    }
}
