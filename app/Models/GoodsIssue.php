<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsIssue extends Model
{
    protected $fillable = ['gi_number', 'reference_type', 'reference_id', 'issue_date', 'issue_type', 'destination_name', 'vendor_id', 'destination_storage_location_id', 'storage_location_id', 'status', 'notes', 'created_by'];

    protected $casts = ['issue_date' => 'date'];

    public function storageLocation() { return $this->belongsTo(StorageLocation::class); }
    public function destinationStorageLocation() { return $this->belongsTo(StorageLocation::class, 'destination_storage_location_id'); }
    public function items() { return $this->hasMany(GoodsIssueItem::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function vendorMaterialDelivery() { return $this->hasOne(VendorMaterialDelivery::class, 'goods_issue_id'); }

    public static function generateNumber(): string
    {
        $prefix = 'GI-' . date('Y') . '-';
        $last = static::where('gi_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        $next = $last ? (int) substr($last->gi_number, -5) + 1 : 1;
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
