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
        Schema::create('vendor_production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity_planned', 15, 3);
            $table->decimal('quantity_ok', 15, 3)->default(0);
            $table->decimal('quantity_ng', 15, 3)->default(0);
            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('vendor_production_order_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_production_order_id')
                ->constrained('vendor_production_orders', 'id', 'vpo_reports_order_fk')
                ->cascadeOnDelete();
            $table->date('report_date');
            $table->decimal('quantity_ok', 15, 3)->default(0);
            $table->decimal('quantity_ng', 15, 3)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_production_order_reports');
        Schema::dropIfExists('vendor_production_orders');
    }
};
