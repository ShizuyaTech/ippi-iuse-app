<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add delivery date + destination storage location to skm_order_items
        if (Schema::hasTable('skm_order_items')) {
            Schema::table('skm_order_items', function (Blueprint $table) {
                $table->date('expected_delivery_date')->nullable()->after('order_qty');
                $table->foreignId('storage_location_id')->nullable()->after('expected_delivery_date')
                      ->constrained('storage_locations')->nullOnDelete();
            });
        }

        // 2. Add partial_received status to skm_orders (MySQL ENUM modify)
        if (Schema::hasTable('skm_orders') && DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE skm_orders MODIFY COLUMN status ENUM('draft','sent','partial_received','completed','cancelled') DEFAULT 'draft'");
        }

        // 3. Add skm_order_id (nullable) to purchase_orders to track which SKM generated this PO
        if (Schema::hasTable('purchase_orders') && Schema::hasTable('skm_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('skm_order_id')->nullable()->after('id')
                      ->constrained('skm_orders')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'skm_order_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['skm_order_id']);
                $table->dropColumn('skm_order_id');
            });
        }

        if (Schema::hasTable('skm_orders') && DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE skm_orders MODIFY COLUMN status ENUM('draft','sent','completed','cancelled') DEFAULT 'draft'");
        }

        if (Schema::hasTable('skm_order_items') && Schema::hasColumn('skm_order_items', 'storage_location_id')) {
            Schema::table('skm_order_items', function (Blueprint $table) {
                $table->dropForeign(['storage_location_id']);
                $table->dropColumn(['expected_delivery_date', 'storage_location_id']);
            });
        }
    }
};
