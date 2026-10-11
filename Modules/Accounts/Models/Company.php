<?php

namespace Modules\Accounts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Orders\Models\Order;
use Modules\Review\Models\Review;
use Modules\Promo\Models\Voucher;

class Company extends Model
{
    use HasFactory;
    protected static function newFactory(): CompanyFactory { return CompanyFactory::new(); }

    protected $fillable = [
        'name',
        'npwp',
        'sppkp',
        'phone',
        'email',
        'address',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->where('company_role', 'admin');
    }

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function loyaltyAccount(): HasOne { return $this->hasOne(LoyaltyAccount::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function vouchers(): HasMany { return $this->hasMany(Voucher::class); }
}
