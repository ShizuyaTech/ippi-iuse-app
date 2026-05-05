<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skm_orders', function (Blueprint $table) {
            $table->id();
            $table->string('skm_number', 20)->unique();
            $table->date('order_date');
            $table->enum('status', ['draft', 'sent', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('skm_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skm_order_id')->constrained('skm_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->decimal('kanban_qty', 15, 3);      // snapshot of qty_per_case at time of creation
            $table->integer('num_cards');               // number of kanban cards triggered
            $table->decimal('order_qty', 15, 3);       // kanban_qty × num_cards
            $table->decimal('current_stock', 15, 3)->default(0); // snapshot
            $table->decimal('min_stock', 15, 3)->default(0);     // snapshot
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skm_order_items');
        Schema::dropIfExists('skm_orders');
    }
};
