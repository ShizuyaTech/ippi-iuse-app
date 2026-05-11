<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProductionOrder extends Model
{
    protected $fillable = [
        'order_number',
        'vendor_id',
        'material_id',
        'bom_id',
        'purchase_order_item_id',
        'delivery_note_id',
        'quantity_planned',
        'quantity_ok',
        'quantity_ng',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity_planned' => 'decimal:3',
        'quantity_ok' => 'decimal:3',
        'quantity_ng' => 'decimal:3',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function material() { return $this->belongsTo(Material::class); }
    public function bom() { return $this->belongsTo(Bom::class); }
    public function purchaseOrderItem() { return $this->belongsTo(PurchaseOrderItem::class); }
    public function deliveryNote() { return $this->belongsTo(DeliveryNote::class); }
    public function reports() { return $this->hasMany(VendorProductionOrderReport::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public static function generateNumber(): string
    {
        $prefix = 'VPO-' . date('Y') . '-';
        $last = static::where('order_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->order_number, -5) + 1 : 1;

        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function remainingQty(): float
    {
        // Hanya qty OK yang dihitung sebagai pemenuhan plan (NG tidak dikirim ke IPPI)
        return max(0, (float) $this->quantity_planned - (float) $this->quantity_ok);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'released' => 'Released',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'bg-gray-100 text-gray-700',
            'released' => 'bg-blue-100 text-blue-700',
            'in_progress' => 'bg-yellow-100 text-yellow-700',
            'completed' => 'bg-green-100 text-green-700',
            'cancelled' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
