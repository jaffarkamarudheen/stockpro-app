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
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->string('customer_name')->index();
            $table->text('customer_address');
            $table->string('customer_phone')->nullable();
            $table->string('enquiry_from')->index();
            $table->integer('total_quantity')->default(0);
            $table->decimal('total_sale_amount', 12, 2)->default(0);
            $table->decimal('total_purchase_cost', 12, 2)->default(0);
            $table->decimal('total_other_cost', 12, 2)->default(0);
            $table->decimal('total_profit', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkouts');
    }
};
