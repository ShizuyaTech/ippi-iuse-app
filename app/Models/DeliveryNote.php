<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryNote extends Model
{
    protected $fillable = [
        'dn_number', 'purchase_order_id', 'vendor_id',
        'estimated_delivery_date', 'vehicle_number', 'driver_name',
        'notes', 'status', 'source_type', 'source_id', 'created_by',
    ];

    protected $casts = [
        'estimated_delivery_date' => 'date',
    ];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor()        { return $this->belongsTo(Vendor::class); }
    public function items()         { return $this->hasMany(DeliveryNoteItem::class); }
    public function goodsReceipt()  { return $this->hasOne(GoodsReceipt::class); }
    public function createdBy()     { return $this->belongsTo(User::class, 'created_by'); }
    public function sourceVendorProductionOrder() { return $this->belongsTo(VendorProductionOrder::class, 'source_id')->where('source_type', 'vendor_production_order'); }

    public static function generateNumber(): string
    {
        $prefix = 'SJ-' . date('Ym') . '-';
        $last = static::where('dn_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->dn_number, -4) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'received'  => 'Sudah Diterima',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'pending'   => 'bg-yellow-100 text-yellow-700',
            'confirmed' => 'bg-blue-100 text-blue-700',
            'received'  => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-600',
            default     => 'bg-gray-100 text-gray-600',
        };
    }
}
