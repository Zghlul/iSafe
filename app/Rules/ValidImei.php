<?php

namespace App\Rules;

use App\Models\Sale;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidImei implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreId = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $imei = (string) $value;

        if (! preg_match('/^\d{15}$/', $imei)) {
            $fail('IMEI harus tepat 15 digit angka.');

            return;
        }

        $existing = Sale::withTrashed()->where('imei', $imei)->first();

        if ($existing && $existing->id !== $this->ignoreId) {
            $fail($existing->trashed()
                ? 'IMEI ini dimiliki transaksi terhapus. Pulihkan transaksi tersebut terlebih dahulu.'
                : 'IMEI sudah tercatat pada transaksi lain.');

            return;
        }

        if (config('sales.imei_luhn_check', true) && ! self::passesLuhn($imei)) {
            $fail('Checksum IMEI tidak valid.');
        }
    }

    public static function passesLuhn(string $imei): bool
    {
        $sum = 0;

        foreach (str_split(strrev($imei)) as $index => $digit) {
            $number = (int) $digit;

            if ($index % 2 === 1) {
                $number *= 2;
                $number = $number > 9 ? $number - 9 : $number;
            }

            $sum += $number;
        }

        return $sum % 10 === 0;
    }
}
