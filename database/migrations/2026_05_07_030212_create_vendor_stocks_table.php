<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();
            $table->unique(['vendor_id', 'material_id']);
        });

        Schema::create('vendor_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->enum('movement_type', ['VMD_IN', 'VRD_OUT']);
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->string('reference_document', 50);
            $table->date('movement_date');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_stock_movements');
        Schema::dropIfExists('vendor_stocks');
    }
};
