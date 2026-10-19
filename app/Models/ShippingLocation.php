<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingLocation extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'external_id', 'parent_external_id', 'name', 'postal_code'];

    protected $casts = ['external_id' => 'integer', 'parent_external_id' => 'integer'];
}
