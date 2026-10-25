<?php

namespace App\Application\Product\Services;

use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Models\User;

/**
 * Centralized authorization service for Product operations.
 *
 * Handles role and permission checking across different guards
 * to eliminate duplicated logic in ProductApplicationService.
 */
class ProductAuthorizationService
{
    public function __construct(
        private readonly UserAuthorizationAdapterInterface $userAuthAdapter,
    ) {}

    /**
     * Check if user can access any products (admin or permission-based).
     */
    public function canAccessAnyProducts(int $userId): bool
    {
        return $this->userAuthAdapter->isWebAdmin($userId) ||
               $this->userAuthAdapter->isBackpackAdmin($userId) ||
               $this->userAuthAdapter->hasBackpackViewPermission($userId, 'product');
    }

    /**
     * Check if user can view specific product.
     */
    public function canViewProduct(int $userId, int $productUserId): bool
    {
        // Admin can view any product
        if ($this->canAccessAnyProducts($userId)) {
            return true;
        }

        // Users can only view their own products
        return $userId === $productUserId;
    }

    /**
     * Check if user can update specific product.
     */
    public function canUpdateProduct(int $userId, int $productUserId): bool
    {
        // Admin can update any product
        if ($this->canAccessAnyProducts($userId)) {
            return true;
        }

        // Users can only update their own products
        return $userId === $productUserId;
    }

    /**
     * Check if user can delete specific product.
     */
    public function canDeleteProduct(int $userId, int $productUserId): bool
    {
        // Admin can delete any product
        if ($this->canAccessAnyProducts($userId)) {
            return true;
        }

        // Frontend users can delete their own products
        if ($this->userAuthAdapter->hasFrontendUserRole($userId) && $userId === $productUserId) {
            return true;
        }

        // Users can only delete their own products
        return $userId === $productUserId;
    }
}
