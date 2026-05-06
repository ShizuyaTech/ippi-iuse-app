<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = ['code', 'name', 'contact_person', 'email', 'phone', 'address', 'is_active', 'vendor_type'];

    protected $casts = ['is_active' => 'boolean'];

    /** vendor_type labels */
    public const TYPES = [
        'coil_center' => 'Coil Center (Supplier Bahan Baku)',
        'process'     => 'Process / Makloon',
        'general'     => 'Umum',
    ];

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->vendor_type ?? 'general'] ?? 'Umum';
    }

    public function isCoilCenter(): bool
    {
        return $this->vendor_type === 'coil_center';
    }

    public function isProcessVendor(): bool
    {
        return $this->vendor_type === 'process';
    }

    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
}
