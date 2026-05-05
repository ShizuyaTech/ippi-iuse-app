<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorProductionOrderReport extends Model
{
    protected $fillable = [
        'vendor_production_order_id',
        'report_date',
        'quantity_ok',
        'quantity_ng',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'quantity_ok' => 'decimal:3',
        'quantity_ng' => 'decimal:3',
    ];

    public function order() { return $this->belongsTo(VendorProductionOrder::class, 'vendor_production_order_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
