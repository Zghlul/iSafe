<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Models\Setting;
use App\Models\Stock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $manualAllowed = (bool) Setting::value(Setting::ALLOW_MANUAL_SALE, '1');
        $manualRules = [
            'phone_model_id' => ['required', 'integer', 'exists:phone_models,id'],
            'storage' => ['required', Rule::in(Sale::STORAGES)],
            'color' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(Sale::CONDITIONS)],
            'imei' => ['required', 'string', 'regex:/^\d{15}$/'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'battery_health' => ['nullable', 'integer', 'between:0,100'],
            'variant' => ['nullable', Rule::in(Stock::VARIANTS)],
            'physical_grade' => ['nullable', Rule::in(Stock::GRADES)],
            'accessories' => ['nullable', 'array'],
            'accessories.*' => [Rule::in(array_keys(Stock::ACCESSORIES))],
            'warranty_until' => ['nullable', 'date'],
            'source_name' => ['nullable', 'string', 'max:255'],
        ];

        return [
            'unit_mode' => ['required', Rule::in($manualAllowed ? ['stock', 'manual'] : ['stock'])],
            'stock_id' => ['required_if:unit_mode,stock', 'nullable', 'integer', 'exists:stocks,id'],
            ...($this->input('unit_mode') === 'manual' && $manualAllowed ? $manualRules : []),
            'sale_date' => ['required', 'date_format:Y-m-d'],
            'seller_name' => ['required', 'string', 'max:100'],
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_phone' => ['nullable', 'string', 'max:20'],
            'selling_price' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(Sale::PAYMENT_METHODS)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'unit_mode.required' => 'Pilih unit stok atau mode manual.',
            'unit_mode.in' => 'Mode penjualan manual tidak diizinkan pada pengaturan.',
            'stock_id.required_if' => 'Pilih unit yang tersedia dari stok.',
            'stock_id.exists' => 'Unit stok tidak ditemukan.',
            'sale_date.required' => 'Tanggal transaksi wajib diisi.',
            'sale_date.date_format' => 'Tanggal transaksi harus valid.',
            'seller_name.required' => 'Nama penjual wajib diisi.',
            'buyer_name.required' => 'Nama pembeli wajib diisi.',
            'phone_model_id.required' => 'Pilih model iPhone.',
            'storage.required' => 'Kapasitas wajib dipilih.',
            'color.required' => 'Warna wajib diisi.',
            'condition.required' => 'Kondisi wajib dipilih.',
            'imei.required' => 'IMEI wajib diisi.',
            'imei.regex' => 'IMEI harus terdiri dari tepat 15 digit angka.',
            'cost_price.required' => 'Modal unit manual wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.integer' => 'Harga jual harus berupa angka bulat.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak tersedia.',
            'notes.max' => 'Catatan maksimal 5.000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['selling_price', 'cost_price'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => preg_replace('/[^\d]/', '', $this->input($field))]);
            }
        }
    }
}
