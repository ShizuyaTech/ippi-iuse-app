<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Work Centers (mesin/stasiun kerja)
        Schema::create('work_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('capacity_per_hour', 10, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // BOMs (Bill of Materials header)
        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('bom_number', 20)->unique();
            $table->foreignId('material_id')->constrained('materials'); // Finished product
            $table->decimal('base_quantity', 15, 3)->default(1);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // BOM Items (components)
        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials'); // Component
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 10)->default('PCS');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Routings (urutan proses produksi)
        Schema::create('routings', function (Blueprint $table) {
            $table->id();
            $table->string('routing_number', 20)->unique();
            $table->foreignId('material_id')->constrained('materials'); // Finished product
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Routing Operations
        Schema::create('routing_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routing_id')->constrained('routings')->cascadeOnDelete();
            $table->unsignedSmallInteger('operation_number');
            $table->foreignId('work_center_id')->constrained('work_centers');
            $table->string('description');
            $table->decimal('setup_time', 8, 2)->default(0); // minutes
            $table->decimal('standard_time', 8, 2)->default(0); // minutes per unit
            $table->timestamps();
        });

        // Production Orders
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('material_id')->constrained('materials'); // Product to produce
            $table->foreignId('bom_id')->nullable()->constrained('boms');
            $table->foreignId('routing_id')->nullable()->constrained('routings');
            $table->decimal('quantity_planned', 15, 3);
            $table->decimal('quantity_produced', 15, 3)->default(0);
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->enum('status', ['created', 'released', 'in_progress', 'confirmed', 'completed', 'cancelled'])->default('created');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Production Order Components (bahan yang akan digunakan)
        Schema::create('production_order_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained('production_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity_required', 15, 3);
            $table->decimal('quantity_issued', 15, 3)->default(0);
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations');
            $table->timestamps();
        });

        // MRP Runs
        Schema::create('mrp_runs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('run_date');
            $table->foreignId('run_by')->constrained('users');
            $table->enum('status', ['completed', 'failed'])->default('completed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // MRP Results (rekomendasi MRP)
        Schema::create('mrp_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mrp_run_id')->constrained('mrp_runs')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('required_quantity', 15, 3)->default(0);
            $table->decimal('shortage_quantity', 15, 3)->default(0);
            $table->enum('recommendation_type', ['purchase', 'production'])->default('purchase');
            $table->decimal('recommended_quantity', 15, 3)->default(0);
            $table->date('recommended_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_results');
        Schema::dropIfExists('mrp_runs');
        Schema::dropIfExists('production_order_components');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('routing_operations');
        Schema::dropIfExists('routings');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
        Schema::dropIfExists('work_centers');
    }
};
