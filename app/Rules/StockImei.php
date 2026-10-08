<?php

namespace App\Rules;

use App\Models\Stock;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StockImei implements ValidationRule
{
    public function __construct(private readonly ?int $exceptId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\d{15}$/', $value)) {
            $fail('IMEI harus terdiri dari tepat 15 digit angka.');

            return;
        }

        if (config('sales.imei_luhn_check', true) && ! ValidImei::passesLuhn($value)) {
            $fail('Nomor IMEI tidak lolos pemeriksaan checksum Luhn.');

            return;
        }

        $existing = Stock::withTrashed()
            ->where('imei', $value)
            ->when($this->exceptId !== null, fn ($query) => $query->where('id', '!=', $this->exceptId))
            ->first();

        if ($existing !== null) {
            $fail($existing->trashed()
                ? 'IMEI ada di data terhapus. Pulihkan unit stok tersebut.'
                : 'IMEI ini sudah terdaftar pada stok.');
        }
    }
}
