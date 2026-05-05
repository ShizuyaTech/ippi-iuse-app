<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkCenter extends Model
{
    protected $fillable = ['code', 'name', 'description', 'capacity_per_hour', 'cost_per_hour', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'capacity_per_hour' => 'decimal:2', 'cost_per_hour' => 'decimal:2'];

    public function routingOperations() { return $this->hasMany(RoutingOperation::class); }
}
