<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date')->index();
            $table->string('seller_name', 100)->index();
            $table->string('buyer_name', 120);
            $table->string('buyer_phone', 20)->nullable();
            $table->string('model', 80)->index();
            $table->string('storage', 10);
            $table->string('color', 50);
            $table->string('condition', 10)->index();
            $table->char('imei', 15)->unique();
            $table->unsignedBigInteger('selling_price');
            $table->unsignedBigInteger('cost_price');
            $table->bigInteger('profit');
            $table->string('payment_method', 20)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
