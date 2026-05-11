<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_result_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('vrd_number', 50)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->date('delivery_date');
            $table->string('vehicle_number', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['sent', 'confirmed'])->default('sent');
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_result_delivery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_result_delivery_id')
                  ->constrained('vendor_result_deliveries')
                  ->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
            $table->decimal('quantity_sent', 15, 3);
            $table->decimal('quantity_confirmed', 15, 3)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_result_delivery_items');
        Schema::dropIfExists('vendor_result_deliveries');
    }
};
