<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabelCheck extends Model
{
    protected $fillable = [
        'part_label',
        'customer_label',
        'result',
        'reference_doc',
        'notes',
        'checked_by',
    ];

    public function checkedBy() { return $this->belongsTo(User::class, 'checked_by'); }

    public function isOk(): bool { return $this->result === 'ok'; }
}
