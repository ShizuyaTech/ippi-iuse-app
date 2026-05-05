<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('goods_receipts', 'delivery_note_id')) {
            return;
        }

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_note_id')->nullable()->after('purchase_order_id');
            $table->index('delivery_note_id', 'gr_delivery_note_id_idx');
            $table->unique('delivery_note_id', 'gr_delivery_note_id_unq');
            $table->foreign('delivery_note_id', 'gr_delivery_note_id_fk')->references('id')->on('delivery_notes');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('goods_receipts', 'delivery_note_id')) {
            return;
        }

        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropForeign('gr_delivery_note_id_fk');
            $table->dropUnique('gr_delivery_note_id_unq');
            $table->dropIndex('gr_delivery_note_id_idx');
            $table->dropColumn('delivery_note_id');
        });
    }
};
