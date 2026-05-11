<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorStockMovement extends Model
{
    protected $fillable = [
        'vendor_id', 'material_id', 'movement_type',
        'quantity', 'quantity_after', 'reference_document',
        'movement_date', 'created_by',
    ];

    protected $casts = [
        'quantity'       => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'movement_date'  => 'date',
    ];

    public function vendor()    { return $this->belongsTo(Vendor::class); }
    public function material()  { return $this->belongsTo(Material::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
