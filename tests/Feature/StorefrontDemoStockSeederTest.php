<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use Database\Seeders\StorefrontDemoStockSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDemoStockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_stock_seeder_is_idempotent_and_does_not_create_sales(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $seeder = new StorefrontDemoStockSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(15, Stock::query()->where('source_name', 'Demo katalog')->count());
        $this->assertSame(15, StockMovement::query()->count());
        $this->assertSame(0, Sale::query()->count());
        $this->assertSame(
            15,
            Stock::query()->where('source_name', 'Demo katalog')->where('cost_price', 0)->count(),
        );
    }
}
