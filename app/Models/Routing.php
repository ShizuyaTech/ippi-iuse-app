<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Routing extends Model
{
    protected $fillable = ['routing_number', 'material_id', 'description', 'status'];

    public function material() { return $this->belongsTo(Material::class); }
    public function operations() { return $this->hasMany(RoutingOperation::class)->orderBy('operation_number'); }
    public function productionOrders() { return $this->hasMany(ProductionOrder::class); }

    public static function generateNumber(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->routing_number, 4) + 1 : 1;
        return 'RTG-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
