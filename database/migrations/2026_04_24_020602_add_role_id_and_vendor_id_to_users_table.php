<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role_id FK to roles table (replaces old string 'role' column)
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete()->after('email');
            // vendor_id: for vendor_admin / vendor_user only
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()->after('role_id');
            // Keep old role column for now, will drop after data migration
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['role_id', 'vendor_id']);
        });
    }
};
