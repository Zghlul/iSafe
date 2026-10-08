<?php

namespace Database\Seeders;

use App\Models\PhoneModel;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\StockBackfillService;
use Illuminate\Database\Seeder;
use RuntimeException;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment() !== 'local') {
            return;
        }

        app(StockBackfillService::class)->run();

        $models = PhoneModel::query()->pluck('id')->all();

        if ($models === []) {
            throw new RuntimeException('Seed master model iPhone sebelum membuat stok contoh.');
        }

        $existing = Stock::query()->where('source_name', 'Seed lokal')->count();

        if ($existing >= 40) {
            return;
        }

        foreach (range($existing + 1, 40) as $index) {
            $condition = fake()->randomElement(Sale::CONDITIONS);
            Stock::query()->create([
                'phone_model_id' => fake()->randomElement($models),
                'storage' => fake()->randomElement(Sale::STORAGES),
                'color' => fake()->randomElement(['Black', 'White', 'Blue', 'Pink', 'Natural Titanium']),
                'condition' => $condition,
                'imei' => $this->generateImei(),
                'battery_health' => $condition === Sale::CONDITION_USED ? random_int(75, 100) : null,
                'variant' => fake()->randomElement(Stock::VARIANTS),
                'physical_grade' => $condition === Sale::CONDITION_USED ? fake()->randomElement(Stock::GRADES) : null,
                'accessories' => fake()->randomElements(array_keys(Stock::ACCESSORIES), random_int(1, 3)),
                'purchase_date' => fake()->dateTimeBetween('-90 days', 'now')->format('Y-m-d'),
                'cost_price' => random_int(
                    $condition === Sale::CONDITION_NEW ? 8_000_000 : 2_000_000,
                    $condition === Sale::CONDITION_NEW ? 22_000_000 : 14_000_000,
                ),
                'source_name' => 'Seed lokal',
                'status' => Stock::STATUS_AVAILABLE,
                'notes' => $index % 5 === 0 ? 'Unit contoh untuk pengembangan lokal.' : null,
            ]);
        }
    }

    private function generateImei(): string
    {
        $digits = (string) random_int(10_000_000_000_000, 99_999_999_999_999);
        $sum = 0;

        foreach (str_split($digits) as $index => $digit) {
            $value = (int) $digit;

            if ($index % 2 === 1) {
                $value *= 2;
                $value = $value > 9 ? $value - 9 : $value;
            }

            $sum += $value;
        }

        return $digits.(10 - ($sum % 10)) % 10;
    }
}
