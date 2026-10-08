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
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'user_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
            });
        }

        if (Schema::hasTable('checkouts') && ! Schema::hasColumn('checkouts', 'user_id')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('user_name')->nullable();
            });
        }

        if (Schema::hasTable('stock_movements') && ! Schema::hasColumn('stock_movements', 'user_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'user_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'user_name']);
            });
        }

        if (Schema::hasTable('checkouts') && Schema::hasColumn('checkouts', 'user_id')) {
            Schema::table('checkouts', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id', 'user_name']);
            });
        }

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'user_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn(['user_id']);
            });
        }
    }
};
