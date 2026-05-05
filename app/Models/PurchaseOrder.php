<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = ['skm_order_id', 'po_number', 'vendor_id', 'storage_location_id', 'order_date', 'expected_delivery_date', 'status', 'total_amount', 'notes', 'created_by', 'approved_at', 'approved_by'];

    protected $casts = ['order_date' => 'date', 'expected_delivery_date' => 'date', 'approved_at' => 'datetime', 'total_amount' => 'decimal:2'];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function skmOrder() { return $this->belongsTo(\App\Models\SkmOrder::class); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    public function goodsReceipts() { return $this->hasMany(GoodsReceipt::class); }
    public function deliveryNotes() { return $this->hasMany(DeliveryNote::class); }
    public function createdBy()     { return $this->belongsTo(User::class, 'created_by'); }

    public static function generateNumber(): string
    {
        $prefix = 'PO-' . date('Y') . '-';
        $last = static::where('po_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->po_number, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
