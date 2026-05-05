<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_issues', function (Blueprint $table) {
            $table->foreignId('destination_storage_location_id')
                  ->nullable()
                  ->after('destination_name')
                  ->constrained('storage_locations')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goods_issues', function (Blueprint $table) {
            $table->dropForeign(['destination_storage_location_id']);
            $table->dropColumn('destination_storage_location_id');
        });
    }
};
