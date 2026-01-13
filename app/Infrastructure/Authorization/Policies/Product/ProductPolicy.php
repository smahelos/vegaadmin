<?php

namespace App\Infrastructure\Authorization\Policies\Product;

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
        return $user->hasPermissionTo('frontend.can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('frontend_user_plus') ||
               $user->hasRole('backend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // Admin can view any product
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Users can only view their own products
        return $user->id === $product->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('frontend.can_create_edit_product') ||
               $user->hasRole('frontend_user') ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Admin can update any product
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Users can only update their own products
        return $user->id === $product->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // Admin can delete any product
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // Frontend users can delete their own products
        if ($user->hasRole('frontend_user') && $user->id === $product->user_id) {
            return true;
        }
        
        // Users can only delete their own products
        return $user->id === $product->user_id;
    }
}
