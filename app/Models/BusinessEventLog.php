<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessEventLog extends Model
{
    protected $fillable = [
        'event_type',
        'entity_type',
        'entity_id',
        'user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
