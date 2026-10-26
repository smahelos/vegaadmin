<?php

namespace App\Application\Party\Services;

use App\Application\User\Contracts\UserAuthorizationAdapterInterface;
use App\Models\User;

/**
 * Centralized authorization service for Party operations.
 *
 * Handles role and permission checking across different guards
 * to eliminate duplicated logic in PartyApplicationService.
 */
class PartyAuthorizationService
{
    public function __construct(
        private readonly UserAuthorizationAdapterInterface $userAuthAdapter,
    ) {}

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
}
