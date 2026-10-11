<?php

namespace Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Accounts\Models\Company;
use Modules\Loyalty\Enums\LoyaltyTier;
use Database\Factories\LoyaltyAccountFactory;

class LoyaltyAccount extends Model
{
    use HasFactory;
    protected static function newFactory(): LoyaltyAccountFactory { return LoyaltyAccountFactory::new(); }
    protected $fillable = ['company_id', 'tier_points', 'points_balance', 'current_tier', 'tier_valid_until'];
    protected $casts = ['current_tier' => LoyaltyTier::class, 'tier_valid_until' => 'date'];
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function pointLedger(): HasMany { return $this->hasMany(PointLedger::class); }
    public function redemptions(): HasMany { return $this->hasMany(Redemption::class); }
}
