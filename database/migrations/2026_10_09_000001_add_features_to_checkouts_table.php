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
        Schema::table('checkouts', function (Blueprint $table) {
            $table->boolean('is_promotion')->default(false)->after('enquiry_from');
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('is_promotion');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->string('status', 50)->default('ordered')->index()->after('discount_amount');
            $table->timestamp('ordered_at')->nullable()->after('status');
            $table->date('expected_delivery_date')->nullable()->after('ordered_at');
            $table->timestamp('delivered_at')->nullable()->after('expected_delivery_date');
            $table->timestamp('received_at')->nullable()->after('delivered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('checkouts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn([
                'is_promotion',
                'subtotal_amount',
                'discount_amount',
                'status',
                'ordered_at',
                'expected_delivery_date',
                'delivered_at',
                'received_at',
            ]);
        });
    }
};
