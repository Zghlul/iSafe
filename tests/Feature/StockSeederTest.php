<?php

namespace Tests\Feature;

use App\Models\PhoneModel;
use App\Models\Stock;
use Database\Seeders\PhoneModelSeeder;
use Database\Seeders\StockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_stock_is_seeded_only_locally_and_can_be_reseeded_safely(): void
    {
        $this->seed(PhoneModelSeeder::class);

        $this->seed(StockSeeder::class);

        $this->assertSame(0, Stock::query()->count());

        $this->app->detectEnvironment(fn (): string => 'local');

        $this->seed(StockSeeder::class);

        $this->assertSame(60, Stock::query()
            ->where('source_name', 'Seed lokal')
            ->where('status', Stock::STATUS_AVAILABLE)
            ->count());

        $this->seed(StockSeeder::class);

        $this->assertSame(60, Stock::query()
            ->where('source_name', 'Seed lokal')
            ->where('status', Stock::STATUS_AVAILABLE)
            ->count());
    }
}
