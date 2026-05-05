<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials'); // FP/WIP item
            $table->decimal('order_quantity', 15, 3);                   // qty order customer
            $table->string('customer_name')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);                // bisa dinonaktifkan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_demands');
    }
};
