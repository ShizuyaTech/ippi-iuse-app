<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('materials', 'process_vendor_id')) {
            return;
        }

        Schema::table('materials', function (Blueprint $table) {
            $table->unsignedBigInteger('process_vendor_id')->nullable()->after('vendor_id');
            $table->index('process_vendor_id', 'materials_process_vendor_id_idx');
            $table->foreign('process_vendor_id', 'materials_process_vendor_id_fk')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('materials', 'process_vendor_id')) {
            return;
        }

        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign('materials_process_vendor_id_fk');
            $table->dropIndex('materials_process_vendor_id_idx');
            $table->dropColumn('process_vendor_id');
        });
    }
};
