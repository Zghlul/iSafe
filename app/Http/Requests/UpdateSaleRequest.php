<?php

namespace App\Http\Requests;

use App\Models\Sale;
use App\Rules\ValidImei;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $sale = $this->route('sale');

        return [
            'sale_date' => ['required', 'date_format:Y-m-d'],
            'seller_name' => ['required', 'string', 'max:100'],
            'buyer_name' => ['required', 'string', 'max:120'],
            'buyer_phone' => ['nullable', 'string', 'max:20'],
            'model' => ['required', 'string', 'max:80'],
            'storage' => ['required', Rule::in(Sale::STORAGES)],
            'color' => ['required', 'string', 'max:50'],
            'condition' => ['required', Rule::in(Sale::CONDITIONS)],
            'imei' => [
                'required',
                'string',
                new ValidImei($sale instanceof Sale ? $sale->id : null),
            ],
            'selling_price' => ['required', 'integer', 'min:0'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', Rule::in(Sale::PAYMENT_METHODS)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_date.required' => 'Tanggal transaksi wajib diisi.',
            'sale_date.date_format' => 'Tanggal transaksi harus valid.',
            'seller_name.required' => 'Nama penjual wajib diisi.',
            'buyer_name.required' => 'Nama pembeli wajib diisi.',
            'model.required' => 'Model iPhone wajib diisi.',
            'storage.required' => 'Kapasitas wajib dipilih.',
            'storage.in' => 'Kapasitas tidak tersedia.',
            'color.required' => 'Warna wajib diisi.',
            'condition.required' => 'Kondisi wajib dipilih.',
            'imei.required' => 'IMEI wajib diisi.',
            'selling_price.required' => 'Harga jual wajib diisi.',
            'selling_price.integer' => 'Harga jual harus berupa angka bulat.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'cost_price.required' => 'Modal wajib diisi.',
            'cost_price.integer' => 'Modal harus berupa angka bulat.',
            'cost_price.min' => 'Modal tidak boleh negatif.',
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
