<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_model_id')->constrained()->restrictOnDelete();
            $table->string('storage', 10);
            $table->string('color', 50);
            $table->string('condition', 10);
            $table->char('imei', 15)->unique();
            $table->unsignedTinyInteger('battery_health')->nullable();
            $table->string('variant', 10)->nullable();
            $table->char('physical_grade', 1)->nullable();
            $table->json('accessories')->nullable();
            $table->date('warranty_until')->nullable();
            $table->date('purchase_date')->index();
            $table->unsignedBigInteger('cost_price');
            $table->string('source_name')->nullable();
            $table->string('status', 10)->default('available')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['phone_model_id', 'status']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_id')->constrained()->restrictOnDelete();
            $table->string('type', 10);
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('moved_at');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['stock_id', 'moved_at']);
            $table->index(['type', 'moved_at']);
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique('sales_imei_unique');
            $table->foreignId('stock_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('sales')->select('imei')->groupBy('imei')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new \RuntimeException(
                'Tidak dapat mengembalikan indeks IMEI lama: ada transaksi dengan IMEI yang dipakai ulang.',
            );
        }

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('stock_id');
            $table->unique('imei');
        });

        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
    }
};
