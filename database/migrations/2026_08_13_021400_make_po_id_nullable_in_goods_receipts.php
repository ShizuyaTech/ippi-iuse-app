<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // goods_receipts.purchase_order_id → nullable (GR Non-PO)
        DB::statement('ALTER TABLE goods_receipts DROP FOREIGN KEY goods_receipts_purchase_order_id_foreign');
        DB::statement('ALTER TABLE goods_receipts MODIFY COLUMN purchase_order_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE goods_receipts ADD CONSTRAINT goods_receipts_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id)');

        // goods_receipt_items.purchase_order_item_id → nullable (GR Non-PO items)
        DB::statement('ALTER TABLE goods_receipt_items DROP FOREIGN KEY goods_receipt_items_purchase_order_item_id_foreign');
        DB::statement('ALTER TABLE goods_receipt_items MODIFY COLUMN purchase_order_item_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE goods_receipt_items ADD CONSTRAINT goods_receipt_items_purchase_order_item_id_foreign FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items(id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE goods_receipts DROP FOREIGN KEY goods_receipts_purchase_order_id_foreign');
        DB::statement('ALTER TABLE goods_receipts MODIFY COLUMN purchase_order_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE goods_receipts ADD CONSTRAINT goods_receipts_purchase_order_id_foreign FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id)');

        DB::statement('ALTER TABLE goods_receipt_items DROP FOREIGN KEY goods_receipt_items_purchase_order_item_id_foreign');
        DB::statement('ALTER TABLE goods_receipt_items MODIFY COLUMN purchase_order_item_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE goods_receipt_items ADD CONSTRAINT goods_receipt_items_purchase_order_item_id_foreign FOREIGN KEY (purchase_order_item_id) REFERENCES purchase_order_items(id)');
    }
};
