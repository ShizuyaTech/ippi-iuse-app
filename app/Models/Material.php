<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = ['code', 'name', 'description', 'type', 'unit_of_measure', 'standard_price', 'qty_per_case', 'min_stock', 'is_active', 'order_method', 'vendor_id', 'process_vendor_id'];

    protected $casts = ['is_active' => 'boolean', 'standard_price' => 'decimal:2', 'qty_per_case' => 'decimal:3', 'min_stock' => 'decimal:3'];

    public function stocks() { return $this->hasMany(Stock::class); }
    public function stockMovements() { return $this->hasMany(StockMovement::class); }
    public function boms() { return $this->hasMany(Bom::class); }
    public function bomItems() { return $this->hasMany(BomItem::class); }
    public function productionOrders() { return $this->hasMany(ProductionOrder::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function processVendor() { return $this->belongsTo(Vendor::class, 'process_vendor_id'); }

    public function getStockQuantity(int $storageLocationId = null): float
    {
        $query = $this->stocks();
        if ($storageLocationId) {
            $query->where('storage_location_id', $storageLocationId);
        }
        return (float) $query->sum('quantity');
    }
}
