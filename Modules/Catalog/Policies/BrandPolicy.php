<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Brand;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_brand');
    }

    public function view(User $user, Brand $brand): bool
    {
        return $user->can('view_brand');
    }

    public function create(User $user): bool
    {
        return $user->can('create_brand');
    }

    public function update(User $user, Brand $brand): bool
    {
        return $user->can('update_brand');
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $user->can('delete_brand');
    }

    public function restore(User $user, Brand $brand): bool
    {
        return $user->can('delete_brand');
    }

    public function forceDelete(User $user, Brand $brand): bool
    {
        return $user->can('delete_brand');
    }
}
