<?php

namespace App\Http\Requests;

use App\Models\Sale;
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
        return [
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
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
            'stock_id.required' => 'Pilih unit stok untuk transaksi ini.',
            'stock_id.exists' => 'Unit stok tidak ditemukan.',
            'sale_date.required' => 'Tanggal transaksi wajib diisi.',
            'sale_date.date_format' => 'Tanggal transaksi harus valid.',
            'seller_name.required' => 'Nama penjual wajib diisi.',
            'buyer_name.required' => 'Nama pembeli wajib diisi.',
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
        if (is_string($this->input('selling_price'))) {
            $this->merge(['selling_price' => preg_replace('/[^\d]/', '', $this->input('selling_price'))]);
        }
    }
}
