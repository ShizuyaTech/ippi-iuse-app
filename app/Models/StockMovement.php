<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = ['material_id', 'storage_location_id', 'movement_type', 'quantity', 'quantity_after', 'reference_document', 'movement_date', 'created_by'];

    protected $casts = ['quantity' => 'decimal:3', 'quantity_after' => 'decimal:3', 'movement_date' => 'date'];

    public function material() { return $this->belongsTo(Material::class); }
    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
}
