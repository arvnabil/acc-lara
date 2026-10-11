<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RfqRequestFactory;

class RfqRequest extends Model
{
    use HasFactory;
    protected static function newFactory(): RfqRequestFactory { return RfqRequestFactory::new(); }
    protected $fillable = ['name', 'email', 'phone', 'company_name', 'message', 'status'];
}
