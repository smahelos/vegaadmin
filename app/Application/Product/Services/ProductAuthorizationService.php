<?php

namespace App\Application\Product\Services;

use App\Models\User;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;

/**
 * Centralized authorization service for Product operations.
 * 
 * Handles role and permission checking across different guards
 * to eliminate duplicated logic in ProductApplicationService.
 */
class ProductAuthorizationService
{
    public function __construct(
        private readonly UserAuthorizationAdapterInterface $userAuthAdapter
    ) {
    }

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

    /**
     * Check if user can create products.
     */
    public function canCreateProduct(int $userId): bool
    {
        return $this->userAuthAdapter->hasCreateEditPermission($userId) ||
               $this->userAuthAdapter->hasFrontendUserRole($userId) ||
               $this->userAuthAdapter->isWebAdmin($userId);
    }

    

    /**
     * Check if user has web admin role.
     */
    public function isWebAdmin(int $userId): bool
    {
        return $this->userAuthAdapter->isWebAdmin($userId);
    }

    /**
     * Check if user has backpack admin role.
     * Uses safe try/catch to handle guard compatibility issues.
     */
    public function isBackpackAdmin(int $userId): bool
    {
        return $this->userAuthAdapter->isBackpackAdmin($userId);
    }

    /**
     * Check if user has specific view permission in backpack guard.
     * Uses safe try/catch to handle guard compatibility issues.
     */
    private function hasBackpackViewPermission(int $userId, string $entityType): bool
    {
        return $this->userAuthAdapter->hasBackpackPermission($userId, 'can_view_' . $entityType);
    }

    /**
     * Check if user has frontend create/edit permission.
     */
    private function hasCreateEditPermission(int $userId): bool
    {
        return $this->userAuthAdapter->hasCreateEditPermission($userId);
    }

    /**
     * Check if user has frontend user role.
     */
    private function hasFrontendUserRole(int $userId): bool
    {
        return $this->userAuthAdapter->hasFrontendUserRole($userId);
    }
}
