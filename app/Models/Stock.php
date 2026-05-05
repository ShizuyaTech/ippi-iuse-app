<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['material_id', 'storage_location_id', 'quantity'];

    protected $casts = ['quantity' => 'decimal:3'];

    public function material() { return $this->belongsTo(Material::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
}
