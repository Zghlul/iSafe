<?php

namespace Tests\Feature;

use App\Models\PhoneModel;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_shows_only_available_product_details(): void
    {
        $phoneModel = PhoneModel::query()->create(['name' => 'iPhone 15 Pro']);
        Stock::query()->create([
            'phone_model_id' => $phoneModel->id,
            'storage' => '128GB',
            'color' => 'Natural Titanium',
            'condition' => 'used',
            'imei' => '111111111111111',
            'battery_health' => 89,
            'variant' => 'ibox',
            'physical_grade' => 'A',
            'purchase_date' => now()->toDateString(),
            'cost_price' => 12345678,
            'status' => Stock::STATUS_AVAILABLE,
        ]);
        Stock::query()->create([
            'phone_model_id' => $phoneModel->id,
            'storage' => '256GB',
            'color' => 'Black Titanium',
            'condition' => 'new',
            'imei' => '222222222222222',
            'purchase_date' => now()->toDateString(),
            'cost_price' => 23456789,
            'status' => Stock::STATUS_SOLD,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Katalog')
            ->assertSee('iPhone 15 Pro')
            ->assertSee('128GB')
            ->assertSee('Natural Titanium')
            ->assertSee('Tanya harga')
            ->assertSee('https://wa.me/6285183660490')
            ->assertDontSee('111111111111111')
            ->assertDontSee('12345678')
            ->assertDontSee('Black Titanium');
    }

    public function test_public_homepage_has_a_contact_path_when_no_units_are_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Belum ada unit yang ditampilkan.')
            ->assertSee('0851 8366 0490');
    }
}
