<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\PhoneModel;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_business_pages_and_all_report_types_render(): void
    {
        foreach ([
            '/dashboard',
            '/transactions',
            '/stock',
            '/stock/reports/summary',
            '/stock/reports/low-stock',
            '/stock/reports/aging',
            '/stock/reports/movements',
            '/stock/reports/sold',
            '/models',
            '/settings',
            '/export',
            '/reports/daily',
            '/reports/monthly',
            '/reports/yearly',
            '/reports/seller',
            '/reports/model',
            '/reports/profit',
            '/reports/custom?from=2026-01-01&to=2026-12-31',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_transaction_crud_recalculates_profit_and_rejects_a_reused_imei(): void
    {
        $model = PhoneModel::query()->create(['name' => 'iPhone Test']);
        $stock = app(StockService::class)->add([
            'phone_model_id' => $model->id,
            'storage' => '256GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $this->validImei('12345678901234'),
            'purchase_date' => now()->format('Y-m-d'),
            'cost_price' => 8_000_000,
        ]);
        $data = [
            'unit_mode' => 'stock',
            'stock_id' => $stock->id,
            'sale_date' => now()->format('Y-m-d'),
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'buyer_phone' => '081234567890',
            'selling_price' => 9_000_000,
            'payment_method' => Sale::PAYMENT_TRANSFER,
            'notes' => null,
        ];
        $this->post('/transactions', $data)->assertRedirect(route('sales.index'));

        $sale = Sale::query()->firstOrFail();
        $this->assertSame(1_000_000, $sale->profit);

        $data['selling_price'] = 9_500_000;
        $this->put(route('sales.update', $sale), $data)->assertRedirect(route('sales.index'));
        $this->assertSame(1_500_000, $sale->fresh()->profit);

        $this->post('/transactions', $data)
            ->assertSessionHasErrors('stock_id');

        $this->delete(route('sales.destroy', $sale))->assertRedirect();
        $this->assertSame(Stock::STATUS_AVAILABLE, $stock->fresh()->status);

        $this->post(route('sales.restore', $sale->id))
            ->assertRedirect(route('sales.index'));
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'deleted_at' => null]);
        $this->assertSame(Stock::STATUS_SOLD, $stock->fresh()->status);
        $this->post('/transactions', $data)->assertSessionHasErrors('stock_id');
    }

    public function test_settings_model_management_and_password_update_work(): void
    {
        $this->post('/models', ['name' => 'iPhone Test'])->assertRedirect();
        $model = PhoneModel::query()->where('name', 'iPhone Test')->firstOrFail();
        $this->put(route('models.update', $model), ['name' => 'iPhone Test Pro'])->assertRedirect();
        $this->assertDatabaseHas('phone_models', ['id' => $model->id, 'name' => 'iPhone Test Pro']);

        $this->put('/settings', [
            'store_name' => 'Bangaldi Test',
            'store_address' => 'Bandung',
            'monthly_target' => 50_000_000,
            'default_min_stock' => 2,
            'old_stock_days' => 30,
            'allow_manual_sale' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('settings', ['key' => 'store_name', 'value' => 'Bangaldi Test']);

        $user = auth()->user();
        $this->put('/settings/password', [
            'current_password' => 'password',
            'password' => 'a-strong-new-password',
            'password_confirmation' => 'a-strong-new-password',
        ])->assertRedirect();
        $this->assertTrue(Hash::check('a-strong-new-password', $user->fresh()->password));

        $this->delete(route('models.destroy', $model))->assertRedirect();
        $this->assertDatabaseMissing('phone_models', ['id' => $model->id]);
    }

    public function test_manual_sale_creates_stock_and_updating_it_moves_the_sale_to_selected_stock(): void
    {
        $model = PhoneModel::query()->create(['name' => 'iPhone Manual']);
        $manualImei = $this->validImei('23456789012345');
        $manualData = [
            'unit_mode' => 'manual',
            'phone_model_id' => $model->id,
            'storage' => '128GB',
            'color' => 'Blue',
            'condition' => Sale::CONDITION_USED,
            'imei' => $manualImei,
            'cost_price' => 6_000_000,
            'sale_date' => now()->format('Y-m-d'),
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'buyer_phone' => '',
            'selling_price' => 7_000_000,
            'payment_method' => Sale::PAYMENT_CASH,
            'notes' => null,
        ];
        $this->post(route('sales.store'), $manualData)->assertRedirect(route('sales.index'));

        $sale = Sale::query()->firstOrFail();
        $manualStock = Stock::query()->findOrFail($sale->stock_id);
        $this->assertSame($manualImei, $sale->imei);
        $this->assertSame(Stock::STATUS_SOLD, $manualStock->status);
        $this->get(route('stock-reports.show', 'sold'))->assertOk()->assertSee($manualImei);
        $this->get(route('stock-reports.show', 'movements'))->assertOk()->assertSee($manualImei);

        $replacement = app(StockService::class)->add([
            'phone_model_id' => $model->id,
            'storage' => '128GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_USED,
            'imei' => $this->validImei('34567890123456'),
            'purchase_date' => now()->format('Y-m-d'),
            'cost_price' => 6_500_000,
        ]);
        $this->put(route('sales.update', $sale), [
            'stock_id' => $replacement->id,
            'sale_date' => now()->format('Y-m-d'),
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'buyer_phone' => '',
            'selling_price' => 7_500_000,
            'payment_method' => Sale::PAYMENT_CASH,
            'notes' => null,
        ])->assertRedirect(route('sales.index'));

        $this->assertSame(Stock::STATUS_AVAILABLE, $manualStock->fresh()->status);
        $this->assertSame(Stock::STATUS_SOLD, $replacement->fresh()->status);
        $this->assertSame($replacement->id, $sale->fresh()->stock_id);

        Setting::query()->updateOrCreate(['key' => Setting::ALLOW_MANUAL_SALE], ['value' => '0']);
        $manualData['imei'] = $this->validImei('45678901234567');
        $this->post(route('sales.store'), $manualData)->assertSessionHasErrors('unit_mode');
    }

    public function test_stock_imei_endpoint_and_deleted_unit_detail_work(): void
    {
        $model = PhoneModel::query()->create(['name' => 'iPhone Stok']);
        $stock = app(StockService::class)->add([
            'phone_model_id' => $model->id,
            'storage' => '256GB',
            'color' => 'White',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $this->validImei('56789012345678'),
            'purchase_date' => now()->format('Y-m-d'),
            'cost_price' => 8_000_000,
        ]);

        $this->getJson(route('stocks.imei-check', ['imei' => $stock->imei]))
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');
        $this->get(route('stocks.show', $stock))->assertOk()->assertSee($stock->imei);
        $this->delete(route('stocks.destroy', $stock))->assertRedirect();
        $this->get(route('stocks.show', $stock->id))->assertOk()->assertSee($stock->imei);
    }

    public function test_exports_return_excel_downloads_and_report_totals(): void
    {
        $sale = $this->saleData();
        Sale::query()->create($sale);

        $this->get('/export/transactions?scope=all')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->get('/stock/export?tab=all')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $this->get('/reports/monthly?year='.now()->year.'&month='.now()->month)
            ->assertOk()
            ->assertSee('Rp 9.000.000');

        $this->get('/reports/monthly/export?year='.now()->year.'&month='.now()->month)
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_dashboard_aggregates_sales_by_condition(): void
    {
        Sale::query()->create($this->saleData());

        $response = $this->get('/dashboard')->assertOk();
        $this->assertSame(1, $response->viewData('money')['new_units']);
        $this->assertSame(0, $response->viewData('money')['used_units']);
        $this->assertSame(1_000_000, $response->viewData('current')->profit);
        $this->assertCount(12, $response->viewData('profitChart')['new']);
    }

    private function saleData(): array
    {
        return [
            'sale_date' => now()->format('Y-m-d'),
            'seller_name' => 'Ayu',
            'buyer_name' => 'Budi',
            'buyer_phone' => '081234567890',
            'model' => 'iPhone 17',
            'storage' => '256GB',
            'color' => 'Black',
            'condition' => Sale::CONDITION_NEW,
            'imei' => $this->validImei('12345678901234'),
            'selling_price' => 9_000_000,
            'cost_price' => 8_000_000,
            'payment_method' => Sale::PAYMENT_TRANSFER,
            'notes' => null,
        ];
    }

    private function validImei(string $fourteenDigits): string
    {
        $sum = 0;
        foreach (str_split($fourteenDigits) as $index => $digit) {
            $value = (int) $digit;
            if ($index % 2 === 1) {
                $value *= 2;
                $value = $value > 9 ? $value - 9 : $value;
            }
            $sum += $value;
        }

        return $fourteenDigits.(10 - ($sum % 10)) % 10;
    }
}
