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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_number')->unique()->index();
            $table->string('name')->index();
            $table->string('photo_path')->nullable();
            $table->string('quality')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('purchase_rate', 12, 2)->default(0);
            $table->decimal('sale_rate', 12, 2)->default(0);
            $table->decimal('other_rate', 12, 2)->default(0);
            $table->decimal('profit_per_unit', 12, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
