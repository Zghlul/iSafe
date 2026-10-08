<?php

namespace Database\Seeders;

use App\Models\PhoneModel;
use Illuminate\Database\Seeder;

class PhoneModelSeeder extends Seeder
{
    private const MODELS = [
        'iPhone 13',
        'iPhone 13 Pro',
        'iPhone 13 Pro Max',
        'iPhone 14',
        'iPhone 14 Pro',
        'iPhone 14 Pro Max',
        'iPhone 15',
        'iPhone 15 Pro',
        'iPhone 15 Pro Max',
        'iPhone 16',
        'iPhone 16 Plus',
        'iPhone 16 Pro',
        'iPhone 16 Pro Max',
        'iPhone 17',
        'iPhone 17 Pro Max',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MODELS as $name) {
            PhoneModel::query()->firstOrCreate(['name' => $name]);
        }
    }
}
