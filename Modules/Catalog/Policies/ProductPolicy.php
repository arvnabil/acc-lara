<?php

namespace Modules\Catalog\Policies;

use App\Models\User;
use Modules\Catalog\Models\Product;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_product');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->can('view_product');
    }

    public function create(User $user): bool
    {
        return $user->can('create_product');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('update_product');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('delete_product');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->can('delete_product');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->can('delete_product');
    }
}
