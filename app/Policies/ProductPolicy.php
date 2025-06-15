<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || 
               $user->hasPermissionTo('can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || 
               $user->hasPermissionTo('can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->id === $product->user_id || 
               $user->hasPermissionTo('can_create_edit_product') ||
               $user->hasRole('admin');
    }
}
