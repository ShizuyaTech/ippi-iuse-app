<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vendor_stock_movements MODIFY COLUMN movement_type ENUM('VMD_IN','VRD_OUT','GI_OUT') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE vendor_stock_movements MODIFY COLUMN movement_type ENUM('VMD_IN','VRD_OUT') NOT NULL");
    }
};
