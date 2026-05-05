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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->decimal('quantity_ok', 15, 3)->default(0)->after('quantity_produced');
            $table->decimal('quantity_ng', 15, 3)->default(0)->after('quantity_ok');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn(['quantity_ok', 'quantity_ng']);
        });
    }
};
