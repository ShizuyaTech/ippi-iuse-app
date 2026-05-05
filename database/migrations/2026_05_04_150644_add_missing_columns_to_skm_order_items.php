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
        Schema::table('skm_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('skm_order_items', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('order_qty');
            }
            if (!Schema::hasColumn('skm_order_items', 'storage_location_id')) {
                $table->foreignId('storage_location_id')->nullable()->after('expected_delivery_date')
                      ->constrained('storage_locations')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('skm_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('skm_order_items', 'storage_location_id')) {
                $table->dropForeign(['storage_location_id']);
                $table->dropColumn('storage_location_id');
            }
            if (Schema::hasColumn('skm_order_items', 'expected_delivery_date')) {
                $table->dropColumn('expected_delivery_date');
            }
        });
    }
};
