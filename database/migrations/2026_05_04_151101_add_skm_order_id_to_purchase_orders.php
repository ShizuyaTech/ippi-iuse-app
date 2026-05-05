<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add skm_order_id FK to purchase_orders
        if (!Schema::hasColumn('purchase_orders', 'skm_order_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->foreignId('skm_order_id')->nullable()->after('id')
                      ->constrained('skm_orders')->nullOnDelete();
            });
        }

        // Add partial_received to skm_orders.status ENUM (MySQL only)
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE skm_orders MODIFY COLUMN status ENUM('draft','sent','partial_received','completed','cancelled') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE skm_orders MODIFY COLUMN status ENUM('draft','sent','completed','cancelled') DEFAULT 'draft'");
        }

        if (Schema::hasColumn('purchase_orders', 'skm_order_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['skm_order_id']);
                $table->dropColumn('skm_order_id');
            });
        }
    }
};
