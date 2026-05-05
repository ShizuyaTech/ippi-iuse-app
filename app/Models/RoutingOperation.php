<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingOperation extends Model
{
    protected $fillable = ['routing_id', 'operation_number', 'work_center_id', 'description', 'setup_time', 'standard_time'];

    protected $casts = ['setup_time' => 'decimal:2', 'standard_time' => 'decimal:2'];

    public function routing() { return $this->belongsTo(Routing::class); }
    public function workCenter() { return $this->belongsTo(WorkCenter::class); }
}
