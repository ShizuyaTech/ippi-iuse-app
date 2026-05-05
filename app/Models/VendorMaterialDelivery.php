<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorMaterialDelivery extends Model
{
    protected $fillable = [
        'vmd_number', 'vendor_id', 'purchase_order_id',
        'delivery_date', 'vehicle_number', 'driver_name',
        'notes', 'status', 'confirmed_at', 'confirmed_by', 'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'confirmed_at'  => 'datetime',
    ];

    public function vendor()        { return $this->belongsTo(Vendor::class); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function items()         { return $this->hasMany(VendorMaterialDeliveryItem::class); }
    public function createdBy()     { return $this->belongsTo(User::class, 'created_by'); }
    public function confirmedBy()   { return $this->belongsTo(User::class, 'confirmed_by'); }

    public static function generateNumber(): string
    {
        $prefix = 'VMD-' . date('Ym') . '-';
        $last   = static::where('vmd_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next   = $last ? (int) substr($last->vmd_number, -4) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'sent'      => 'Dikirim',
            'confirmed' => 'Dikonfirmasi Vendor',
            default     => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'sent'      => 'bg-yellow-100 text-yellow-800',
            'confirmed' => 'bg-green-100 text-green-800',
            default     => 'bg-gray-100 text-gray-600',
        };
    }
}
