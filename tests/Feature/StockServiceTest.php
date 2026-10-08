<?php

namespace Tests\Feature;

use App\Models\PhoneModel;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Setting;
use App\Rules\ValidImei;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private PhoneModel $phoneModel;

    private int $imeiCounter = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->phoneModel = PhoneModel::query()->create(['name' => 'iPhone Test']);
    }

    public function test_adding_stock_creates_an_in_movement_and_rejects_trashed_imeis(): void
    {
        $service = app(StockService::class);
        $stock = $service->add($this->stockAttributes());

        $this->assertSame(Stock::STATUS_AVAILABLE, $stock->status);
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'type' => StockMovement::TYPE_IN,
        ]);

        try {
            $service->add($this->stockAttributes($stock->imei));
            $this->fail('Expected active IMEI duplicates to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('sudah terdaftar', $exception->errors()['imei'][0]);
        }

        $service->remove($stock);

        try {
            $service->add($this->stockAttributes($stock->imei));
            $this->fail('Expected the deleted IMEI to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Pulihkan unit stok', $exception->errors()['imei'][0]);
        }

        $service->restoreStock($stock->id);
        $this->assertNotNull($stock->fresh());
        $this->assertSame(2, $stock->movements()->where('type', StockMovement::TYPE_IN)->count());
    }

    public function test_stock_settings_have_safe_defaults_and_status_is_service_managed(): void
    {
        $this->assertDatabaseHas('settings', ['key' => Setting::OLD_STOCK_DAYS, 'value' => '30']);
        $this->assertDatabaseHas('settings', ['key' => Setting::ALLOW_MANUAL_SALE, 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => Setting::DEFAULT_MIN_STOCK, 'value' => null]);

        $stock = app(StockService::class)->add($this->stockAttributes());

        try {
            app(StockService::class)->update($stock, ['status' => Stock::STATUS_SOLD]);
            $this->fail('Expected stock status to be managed only by stock transitions.');
        } catch (ValidationException $exception) {
            $this->assertSame(Stock::STATUS_AVAILABLE, $stock->fresh()->status);
            $this->assertArrayHasKey('stock', $exception->errors());
        }
    }

    public function test_bulk_stock_addition_is_atomic_and_checks_all_imeis(): void
    {
        $service = app(StockService::class);
        $imei = $this->imei();

        try {
            $service->addMany($this->stockAttributes(), [$imei, $imei]);
            $this->fail('Expected duplicate IMEIs in one batch to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('imeis', $exception->errors());
        }

        $this->assertSame(0, Stock::query()->count());

        $stocks = $service->addMany($this->stockAttributes(), [$this->imei(), $this->imei()]);
        $this->assertCount(2, $stocks);
        $this->assertSame(2, StockMovement::query()->where('type', StockMovement::TYPE_IN)->count());
    }

    public function test_selling_a_unit_copies_its_snapshot_and_prevents_double_sale(): void
    {
        $stock = app(StockService::class)->add($this->stockAttributes());
        $sale = app(StockService::class)->sell($stock->id, $this->saleAttributes());

        $this->assertSame(Stock::STATUS_SOLD, $stock->fresh()->status);
        $this->assertSame($stock->id, $sale->stock_id);
        $this->assertSame($stock->imei, $sale->imei);
        $this->assertSame('iPhone Test', $sale->model);
        $this->assertSame(7_000_000, $sale->cost_price);
        $this->assertSame(2_000_000, $sale->profit);
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'sale_id' => $sale->id,
            'type' => StockMovement::TYPE_SOLD,
        ]);

        try {
            app(StockService::class)->sell($stock->id, $this->saleAttributes());
            $this->fail('Expected sold stock to be unavailable.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('sudah terjual', $exception->errors()['stock_id'][0]);
        }

        $this->assertSame(1, Sale::query()->count());
    }

    public function test_replacing_a_sale_unit_returns_the_old_unit_and_sells_the_new_one(): void
    {
        $service = app(StockService::class);
        $oldStock = $service->add($this->stockAttributes());
        $newStock = $service->add($this->stockAttributes(costPrice: 8_000_000));
        $sale = $service->sell($oldStock->id, $this->saleAttributes());

        $updated = $service->replaceSaleUnit($sale, $newStock->id, $this->saleAttributes());

        $this->assertSame(Stock::STATUS_AVAILABLE, $oldStock->fresh()->status);
        $this->assertSame(Stock::STATUS_SOLD, $newStock->fresh()->status);
        $this->assertSame($newStock->id, $updated->stock_id);
        $this->assertSame(8_000_000, $updated->cost_price);
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $oldStock->id,
            'sale_id' => $sale->id,
            'type' => StockMovement::TYPE_RETURNED,
        ]);
    }

    public function test_cancelling_and_restoring_sales_preserves_single_active_sale_per_unit(): void
    {
        $service = app(StockService::class);
        $stock = $service->add($this->stockAttributes());
        $firstSale = $service->sell($stock->id, $this->saleAttributes());

        $service->cancelSale($firstSale);
        $this->assertSoftDeleted('sales', ['id' => $firstSale->id]);
        $this->assertSame(Stock::STATUS_AVAILABLE, $stock->fresh()->status);
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'sale_id' => $firstSale->id,
            'type' => StockMovement::TYPE_RETURNED,
        ]);

        $secondSale = $service->sell($stock->id, $this->saleAttributes());

        try {
            $service->restoreSale($firstSale->id);
            $this->fail('Expected restoring a sale to fail when its unit has been resold.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('sudah terjual', $exception->errors()['stock_id'][0]);
        }

        $service->cancelSale($secondSale);
        $restored = $service->restoreSale($firstSale->id);
        $this->assertNull($restored->deleted_at);
        $this->assertSame(Stock::STATUS_SOLD, $stock->fresh()->status);
    }

    public function test_sale_without_backfilled_stock_cannot_be_cancelled_silently(): void
    {
        $sale = Sale::query()->create([
            ...$this->saleAttributes(),
            'model' => 'iPhone Legacy',
            'storage' => '128GB',
            'color' => 'White',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $this->imei(),
            'cost_price' => 7_000_000,
        ]);

        try {
            app(StockService::class)->cancelSale($sale);
            $this->fail('Expected an unlinked legacy sale to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('sale', $exception->errors());
        }

        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'deleted_at' => null]);
    }

    public function test_sold_stock_locks_financial_fields_and_cannot_be_removed(): void
    {
        $service = app(StockService::class);
        $stock = $service->add($this->stockAttributes());
        $service->sell($stock->id, $this->saleAttributes());

        try {
            $service->update($stock, ['cost_price' => 1]);
            $this->fail('Expected financial fields to be locked while sold.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('stock', $exception->errors());
        }

        $service->update($stock, ['notes' => 'Catatan unit terjual.']);
        $this->assertSame('Catatan unit terjual.', $stock->fresh()->notes);
        $this->assertSame(7_000_000, $stock->fresh()->cost_price);

        try {
            $service->remove($stock);
            $this->fail('Expected sold stock to be protected from deletion.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Unit terjual', $exception->errors()['stock'][0]);
        }
    }

    public function test_backfill_is_idempotent_and_ignores_deleted_sales(): void
    {
        $activeSale = Sale::query()->create([
            ...$this->saleAttributes(),
            'model' => 'iPhone Backfill',
            'storage' => '128GB',
            'color' => 'Blue',
            'condition' => Sale::CONDITION_USED,
            'cost_price' => 6_000_000,
            'imei' => $this->imei(),
        ]);
        $deletedSale = Sale::query()->create([
            ...$this->saleAttributes(),
            'model' => 'iPhone Test',
            'storage' => '256GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_NEW,
            'cost_price' => 7_000_000,
            'imei' => $this->imei(),
        ]);
        $deletedSale->delete();

        $this->artisan('stock:backfill')
            ->expectsOutput('Unit stok dibuat dan ditautkan: 1.')
            ->assertExitCode(0);
        $this->artisan('stock:backfill')
            ->expectsOutput('Unit stok dibuat dan ditautkan: 0.')
            ->assertExitCode(0);

        $activeSale->refresh();
        $this->assertNotNull($activeSale->stock_id);
        $this->assertSame(Stock::STATUS_SOLD, $activeSale->stock->status);
        $this->assertSame('backfill', $activeSale->stock->source_name);
        $this->assertSame(2, $activeSale->stock->movements()->count());
        $this->assertNull($deletedSale->fresh()->stock_id);
    }

    private function stockAttributes(?string $imei = null, int $costPrice = 7_000_000): array
    {
        return [
            'phone_model_id' => $this->phoneModel->id,
            'storage' => '256GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $imei ?? $this->imei(),
            'purchase_date' => now()->toDateString(),
            'cost_price' => $costPrice,
            'source_name' => 'Pemasok',
        ];
    }

    private function saleAttributes(): array
    {
        return [
            'sale_date' => now()->toDateString(),
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'buyer_phone' => null,
            'selling_price' => 9_000_000,
            'payment_method' => Sale::PAYMENT_TRANSFER,
            'notes' => null,
        ];
    }

    private function imei(): string
    {
        $digits = str_pad((string) $this->imeiCounter++, 14, '1', STR_PAD_LEFT);
        $sum = 0;

        foreach (str_split($digits) as $index => $digit) {
            $value = (int) $digit;
            if ($index % 2 === 1) {
                $value *= 2;
                $value = $value > 9 ? $value - 9 : $value;
            }
            $sum += $value;
        }

        $imei = $digits.(10 - ($sum % 10)) % 10;
        $this->assertTrue(ValidImei::passesLuhn($imei));

        return $imei;
    }
}
