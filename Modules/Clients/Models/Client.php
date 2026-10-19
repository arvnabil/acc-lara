<?php

namespace Modules\Clients\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Clients\Models\Address;
use Modules\Loyalty\Models\LoyaltyAccount;
use Modules\Orders\Models\Order;
use Modules\Review\Models\Review;
use Modules\Promo\Models\Voucher;
use App\Models\TaxProfile;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';
    protected static function newFactory(): ClientFactory { return ClientFactory::new(); }

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

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->where('company_role', 'admin');
    }

    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function loyaltyAccount(): HasOne { return $this->hasOne(LoyaltyAccount::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function vouchers(): HasMany { return $this->hasMany(Voucher::class); }
    public function taxProfiles(): HasMany { return $this->hasMany(TaxProfile::class); }
}




