<?php

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Loyalty\Enums\Tier;

class LoyaltyAccount extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['current_tier' => Tier::class, 'tier_valid_until' => 'date'];
    }

    public function ledger(): HasMany { return $this->hasMany(PointLedger::class); }
}
