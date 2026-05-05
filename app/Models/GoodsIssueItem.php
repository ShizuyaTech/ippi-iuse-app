<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsIssueItem extends Model
{
    protected $fillable = ['goods_issue_id', 'material_id', 'quantity_issued', 'note'];

    protected $casts = ['quantity_issued' => 'decimal:3'];

    public function goodsIssue() { return $this->belongsTo(GoodsIssue::class); }
    public function material() { return $this->belongsTo(Material::class); }
}
