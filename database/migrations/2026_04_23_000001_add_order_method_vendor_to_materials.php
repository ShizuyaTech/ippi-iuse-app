<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->enum('order_method', ['mrp', 'skm'])->default('mrp')->after('is_active');
            $table->foreignId('vendor_id')->nullable()->after('order_method')->constrained('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn(['order_method', 'vendor_id']);
        });
    }
};
