<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULTS = [
        'default_min_stock' => null,
        'old_stock_days' => '30',
        'allow_manual_sale' => '1',
    ];

    public function up(): void
    {
        Schema::table('phone_models', function (Blueprint $table): void {
            $table->unsignedSmallInteger('min_stock')->nullable();
        });

        foreach (self::DEFAULTS as $key => $value) {
            DB::table('settings')->insertOrIgnore([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', array_keys(self::DEFAULTS))->delete();

        Schema::table('phone_models', function (Blueprint $table): void {
            $table->dropColumn('min_stock');
        });
    }
};
