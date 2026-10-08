<?php

namespace Database\Seeders;

use App\Models\PhoneModel;
use App\Models\Sale;
use Illuminate\Database\Seeder;
use RuntimeException;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->environment() !== 'local' || Sale::withTrashed()->exists()) {
            return;
        }

        $models = PhoneModel::query()->pluck('name')->all();

        if ($models === []) {
            throw new RuntimeException('Seed master model iPhone sebelum membuat data transaksi contoh.');
        }

        foreach (range(1, 80) as $index) {
            $condition = fake()->randomElement(Sale::CONDITIONS);
            $costPrice = random_int(
                $condition === Sale::CONDITION_NEW ? 8_000_000 : 2_000_000,
                $condition === Sale::CONDITION_NEW ? 22_000_000 : 14_000_000,
            );

            Sale::query()->create([
                'sale_date' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
                'seller_name' => fake()->firstName(),
                'buyer_name' => fake()->name(),
                'buyer_phone' => $index % 5 === 0 ? null : fake()->numerify('08##########'),
                'model' => fake()->randomElement($models),
                'storage' => fake()->randomElement(Sale::STORAGES),
                'color' => fake()->randomElement(['Black', 'White', 'Blue', 'Pink', 'Natural Titanium']),
                'condition' => $condition,
                'imei' => $this->generateImei(),
                'selling_price' => max(0, $costPrice + random_int(-500_000, 1_800_000)),
                'cost_price' => $costPrice,
                'payment_method' => fake()->randomElement(Sale::PAYMENT_METHODS),
                'notes' => $index % 4 === 0 ? 'Termasuk aksesori.' : null,
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
