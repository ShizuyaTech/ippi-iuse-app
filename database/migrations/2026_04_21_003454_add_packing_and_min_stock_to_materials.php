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
        Schema::table('materials', function (Blueprint $table) {
            $table->decimal('qty_per_case', 15, 3)->default(0)->after('standard_price')->comment('Qty per case/karton');
            $table->decimal('min_stock', 15, 3)->default(0)->after('qty_per_case')->comment('Minimal stok sebelum alert');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['qty_per_case', 'min_stock']);
        });
    }
};
