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
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'is_combo')) {
                $table->boolean('is_combo')->default(false)->after('quality')->index();
            }
        });

        Schema::create('combo_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('combo_product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('item_product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->unique(['combo_product_id', 'item_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combo_items');

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'is_combo')) {
                $table->dropColumn('is_combo');
            }
        });
    }
};
