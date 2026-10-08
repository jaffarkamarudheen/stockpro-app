<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No-op: preserved so existing or new products are never wiped on deployment
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
