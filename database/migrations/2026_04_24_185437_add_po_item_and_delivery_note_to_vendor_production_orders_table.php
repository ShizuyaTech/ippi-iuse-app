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
        Schema::table('vendor_production_orders', function (Blueprint $table) {
            $table->foreignId('purchase_order_item_id')->nullable()->after('material_id')
                ->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('delivery_note_id')->nullable()->after('purchase_order_item_id')
                ->constrained('delivery_notes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_production_orders', function (Blueprint $table) {
            $table->dropForeign(['delivery_note_id']);
            $table->dropColumn('delivery_note_id');
            $table->dropForeign(['purchase_order_item_id']);
            $table->dropColumn('purchase_order_item_id');
        });
    }
};
