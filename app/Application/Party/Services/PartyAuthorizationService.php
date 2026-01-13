<?php

namespace App\Application\Party\Services;

use App\Models\User;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;

/**
 * Centralized authorization service for Party operations.
 * 
 * Handles role and permission checking across different guards
 * to eliminate duplicated logic in PartyApplicationService.
 */
class PartyAuthorizationService
{
    public function __construct(
        private readonly UserAuthorizationAdapterInterface $userAuthAdapter
    ) {
    }

    /**
     * Check if user can access any clients (admin or permission-based).
     */
    public function canAccessAnyClients(int $userId): bool
    {
        return $this->userAuthAdapter->isWebAdmin($userId) || 
               $this->userAuthAdapter->isBackpackAdmin($userId) || 
               $this->userAuthAdapter->hasBackpackPermission($userId, 'client');
    }

    /**
     * Check if user can access any suppliers (admin or permission-based).
     */
    public function canAccessAnySuppliers(int $userId): bool
    {
        return $this->userAuthAdapter->isWebAdmin($userId) || 
               $this->userAuthAdapter->isBackpackAdmin($userId) || 
               $this->userAuthAdapter->hasBackpackPermission($userId, 'supplier');
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
}
