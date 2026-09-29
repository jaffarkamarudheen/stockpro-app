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
        Schema::create('checkout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checkout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_purchase_rate', 12, 2)->default(0);
            $table->decimal('unit_sale_rate', 12, 2)->default(0);
            $table->decimal('unit_other_rate', 12, 2)->default(0);
            $table->decimal('unit_profit', 12, 2)->default(0);
            $table->decimal('subtotal_sale', 12, 2)->default(0);
            $table->decimal('subtotal_profit', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_items');
    }
};
