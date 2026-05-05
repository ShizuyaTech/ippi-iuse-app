<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Storage Locations (Gudang)
        Schema::create('storage_locations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Material Master
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['RM', 'WIP', 'FP'])->default('RM'); // Raw, Semi, Finished
            $table->string('unit_of_measure', 10)->default('PCS');
            $table->decimal('standard_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Vendor Master
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 20)->unique();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->enum('status', ['draft', 'approved', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Purchase Order Items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->decimal('quantity_received', 15, 3)->default(0);
            $table->timestamps();
        });

        // Goods Receipts
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('gr_number', 20)->unique();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->date('receipt_date');
            $table->foreignId('storage_location_id')->constrained('storage_locations');
            $table->enum('status', ['posted', 'reversed'])->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Goods Receipt Items
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity_received', 15, 3);
            $table->timestamps();
        });

        // Goods Issues
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->id();
            $table->string('gi_number', 20)->unique();
            $table->enum('reference_type', ['production_order', 'manual'])->default('manual');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('issue_date');
            $table->foreignId('storage_location_id')->constrained('storage_locations');
            $table->enum('status', ['posted', 'reversed'])->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Goods Issue Items
        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_issue_id')->constrained('goods_issues')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materials');
            $table->decimal('quantity_issued', 15, 3);
            $table->timestamps();
        });

        // Stock (current stock per material per storage location)
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('storage_location_id')->constrained('storage_locations');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();
            $table->unique(['material_id', 'storage_location_id']);
        });

        // Stock Movements (audit trail)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materials');
            $table->foreignId('storage_location_id')->constrained('storage_locations');
            $table->enum('movement_type', ['GR', 'GI', 'GR_REV', 'GI_REV']);
            $table->decimal('quantity', 15, 3);
            $table->decimal('quantity_after', 15, 3);
            $table->string('reference_document', 30)->nullable();
            $table->date('movement_date');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('storage_locations');
    }
};
