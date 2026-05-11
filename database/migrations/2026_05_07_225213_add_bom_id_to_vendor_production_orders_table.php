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
        Schema::table('vendor_production_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('bom_id')->nullable()->after('material_id');
            $table->foreign('bom_id')->references('id')->on('boms')->nullOnDelete();
        });

        // Extend movement_type enum to include production movements
        DB::statement("ALTER TABLE vendor_stock_movements MODIFY COLUMN movement_type ENUM('VMD_IN','VRD_OUT','GI_OUT','PROD_CONSUME','PROD_OUTPUT') NOT NULL");
    }

    public function down(): void
    {
        Schema::table('vendor_production_orders', function (Blueprint $table) {
            $table->dropForeign(['bom_id']);
            $table->dropColumn('bom_id');
        });

        DB::statement("ALTER TABLE vendor_stock_movements MODIFY COLUMN movement_type ENUM('VMD_IN','VRD_OUT','GI_OUT') NOT NULL");
    }
};
