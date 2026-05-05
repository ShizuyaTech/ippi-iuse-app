<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id', 'material_id', 'quantity', 'unit_price', 'expected_delivery_date', 'total_price', 'quantity_received'];

    protected $casts = ['quantity' => 'decimal:3', 'unit_price' => 'decimal:2', 'total_price' => 'decimal:2', 'quantity_received' => 'decimal:3', 'expected_delivery_date' => 'date'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function material() { return $this->belongsTo(Material::class); }
    public function goodsReceiptItems() { return $this->hasMany(GoodsReceiptItem::class); }
}
