<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorMaterialDeliveryItem extends Model
{
    protected $fillable = [
        'vendor_material_delivery_id', 'material_id',
        'storage_location_id', 'quantity', 'notes',
    ];

    protected $casts = ['quantity' => 'decimal:3'];

    public function delivery()         { return $this->belongsTo(VendorMaterialDelivery::class, 'vendor_material_delivery_id'); }
    public function material()         { return $this->belongsTo(Material::class); }
    public function storageLocation()  { return $this->belongsTo(StorageLocation::class); }
}
