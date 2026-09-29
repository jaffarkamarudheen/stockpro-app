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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('email'); // admin, operator
            $table->boolean('can_access_admin')->default(false)->after('role');
            $table->boolean('can_access_app')->default(true)->after('can_access_admin');
            $table->boolean('is_active')->default(true)->after('can_access_app');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'can_access_admin', 'can_access_app', 'is_active']);
        });
    }
};
