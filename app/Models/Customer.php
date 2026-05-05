<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['code', 'name', 'contact_person', 'email', 'phone', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
