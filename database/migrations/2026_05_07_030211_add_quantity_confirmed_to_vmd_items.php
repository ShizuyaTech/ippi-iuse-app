<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_material_delivery_items', function (Blueprint $table) {
            $table->decimal('quantity_confirmed', 15, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_material_delivery_items', function (Blueprint $table) {
            $table->dropColumn('quantity_confirmed');
        });
    }
};
