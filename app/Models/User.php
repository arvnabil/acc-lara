<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
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
use Modules\Clients\Models\Address;
use Modules\Clients\Models\Client;
use Spatie\Permission\Traits\HasRoles;
use Modules\Email\Models\EmailSetting;
use Modules\Email\Notifications\PasswordResetNotification;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, InteractsWithAppAuthentication, InteractsWithAppAuthenticationRecovery;

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
        'client_id',
        'company_role',
        'referral_code', 'referred_by_user_id',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_user_id');
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

    public function sendPasswordResetNotification($token): void
    {
        if (EmailSetting::getInstance()->password_reset_enabled) {
            $this->notify(new PasswordResetNotification($token));
        }
    }
}



