<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    protected $fillable = ['delivery_note_id', 'purchase_order_item_id', 'quantity', 'notes'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function deliveryNote()      { return $this->belongsTo(DeliveryNote::class); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class); }
}
