<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\Wishlist;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\PaymentProof;
use Modules\Review\Models\Review;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Accounts\Models\Address;
use Modules\Accounts\Models\Company;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company_id',
        'company_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Filament access panel check.
     * User must have the 'access_panel' permission (or pass via Gate::before for administrator role).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->can('access_panel');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function carts(): HasMany { return $this->hasMany(Cart::class); }
    public function wishlists(): HasMany { return $this->hasMany(Wishlist::class); }
    public function orders(): HasMany { return $this->hasMany(Order::class); }
    public function paymentProofs(): HasMany { return $this->hasMany(PaymentProof::class, 'verified_by'); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
}
