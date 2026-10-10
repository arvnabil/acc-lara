<?php

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class PointLedger extends Model
{
    protected $table = 'point_ledger';
    public const UPDATED_AT = null;
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['counts_for_tier' => 'boolean', 'earned_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
