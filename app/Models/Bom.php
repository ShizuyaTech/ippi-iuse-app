<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    protected $fillable = ['bom_number', 'material_id', 'base_quantity', 'valid_from', 'valid_to', 'status', 'description'];

    protected $casts = ['valid_from' => 'date', 'valid_to' => 'date', 'base_quantity' => 'decimal:3'];

    public function material() { return $this->belongsTo(Material::class); }
    public function items() { return $this->hasMany(BomItem::class); }
    public function productionOrders() { return $this->hasMany(ProductionOrder::class); }

    public static function generateNumber(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->bom_number, 4) + 1 : 1;
        return 'BOM-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
