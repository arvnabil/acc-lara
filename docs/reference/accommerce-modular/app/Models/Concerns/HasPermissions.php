<?php

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pengganti Spatie Permission: role tunggal dengan daftar permission (JSON). */
trait HasPermissions
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role?->name === 'administrator';
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->role?->permissions ?? [], true);
    }
}
