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
        // Add vendor_id to goods_issues so GI to_vendor knows which vendor
        Schema::table('goods_issues', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete()
                  ->after('destination_name');
        });

        // Add goods_issue_id to vendor_material_deliveries so VMD knows it was created from a GI
        Schema::table('vendor_material_deliveries', function (Blueprint $table) {
            $table->foreignId('goods_issue_id')->nullable()->constrained('goods_issues')->nullOnDelete()
                  ->after('vmd_number');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_material_deliveries', function (Blueprint $table) {
            $table->dropForeign(['goods_issue_id']);
            $table->dropColumn('goods_issue_id');
        });

        Schema::table('goods_issues', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });
    }
};
