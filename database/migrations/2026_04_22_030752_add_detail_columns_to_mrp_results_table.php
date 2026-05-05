<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mrp_results', function (Blueprint $table) {
            $table->decimal('gross_requirement', 15, 3)->default(0)->after('required_quantity'); // from BOM explosion
            $table->decimal('open_po_qty', 15, 3)->default(0)->after('gross_requirement');       // sisa PO partial_received
            $table->decimal('net_requirement', 15, 3)->default(0)->after('open_po_qty');         // gross - open_po
            $table->decimal('safety_stock_qty', 15, 3)->default(0)->after('net_requirement');    // net * 20%
            $table->decimal('qty_per_case', 15, 3)->default(0)->after('safety_stock_qty');       // MOQ from material
        });
    }

    public function down(): void
    {
        Schema::table('mrp_results', function (Blueprint $table) {
            $table->dropColumn(['gross_requirement','open_po_qty','net_requirement','safety_stock_qty','qty_per_case']);
        });
    }
};
