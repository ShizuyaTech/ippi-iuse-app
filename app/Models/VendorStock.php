<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorStock extends Model
{
    protected $fillable = ['vendor_id', 'material_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function vendor()   { return $this->belongsTo(Vendor::class); }
    public function material() { return $this->belongsTo(Material::class); }
}
