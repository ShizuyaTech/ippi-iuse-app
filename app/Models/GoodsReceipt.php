<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends Model
{
    protected $fillable = ['gr_number', 'purchase_order_id', 'vendor_id', 'delivery_note_id', 'receipt_date', 'storage_location_id', 'status', 'notes', 'created_by'];

    protected $casts = ['receipt_date' => 'date'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor()        { return $this->belongsTo(\App\Models\Vendor::class); }
    public function deliveryNote() { return $this->belongsTo(DeliveryNote::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function items() { return $this->hasMany(GoodsReceiptItem::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public static function generateNumber(): string
    {
        $prefix = 'GR-' . date('Y') . '-';
        $last = static::where('gr_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->gr_number, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
