<?php

namespace Tests\Feature;

use App\Models\PhoneModel;
use App\Models\Sale;
use Database\Seeders\PhoneModelSeeder;
use Database\Seeders\SaleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_profit_is_recalculated_and_imei_is_stored_as_text(): void
    {
        $sale = $this->createSale('012345678901234', 8_500_000, 8_750_000);

        $sale->forceFill(['profit' => 999_999_999])->save();
        $sale->refresh();

        $this->assertSame(-250_000, $sale->profit);
        $this->assertSame('012345678901234', $sale->imei);
    }

    public function test_imei_must_be_unique(): void
    {
        $this->createSale('123456789012345');

        $this->expectException(QueryException::class);

        $this->createSale('123456789012345');
    }

    public function test_sales_support_soft_delete_and_restore(): void
    {
        $sale = $this->createSale('123456789012345');

        $sale->delete();

        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
        $this->assertDatabaseCount('sales', 1);
        $this->assertSame(0, Sale::query()->count());

        $sale->restore();

        $this->assertSame(1, Sale::query()->count());
    }

    public function test_phone_model_seeder_is_idempotent(): void
    {
        $this->seed(PhoneModelSeeder::class);
        $this->seed(PhoneModelSeeder::class);

        $this->assertSame(15, PhoneModel::query()->count());
        $this->assertSame(
            PhoneModel::query()->count(),
            PhoneModel::query()->distinct()->count('name'),
        );
    }

    public function test_sample_sales_are_seeded_only_in_local_environment_with_valid_imeis(): void
    {
        $this->seed(PhoneModelSeeder::class);

        $this->seed(SaleSeeder::class);

        $this->assertSame(0, Sale::query()->count());

        $this->app->detectEnvironment(fn (): string => 'local');

        $this->seed(SaleSeeder::class);

        $this->assertSame(80, Sale::query()->count());

        foreach (Sale::query()->pluck('imei') as $imei) {
            $this->assertSame(15, strlen($imei));
            $this->assertTrue($this->hasValidLuhnChecksum($imei));
        }
    }

    private function createSale(
        string $imei,
        int $sellingPrice = 9_000_000,
        int $costPrice = 8_000_000,
    ): Sale {
        return Sale::query()->create([
            'sale_date' => '2026-10-08',
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'model' => 'iPhone 17',
            'storage' => '256GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $imei,
            'selling_price' => $sellingPrice,
            'cost_price' => $costPrice,
            'payment_method' => Sale::PAYMENT_TRANSFER,
        ]);
    }

    private function hasValidLuhnChecksum(string $value): bool
    {
        $sum = 0;
        $digits = str_split($value);
        $checkDigit = (int) array_pop($digits);

        foreach (array_reverse($digits) as $index => $digit) {
            $number = (int) $digit;

            if ($index % 2 === 0) {
                $number *= 2;
                $number = $number > 9 ? $number - 9 : $number;
            }

            $sum += $number;
        }

        return (10 - ($sum % 10)) % 10 === $checkDigit;
    }
}
