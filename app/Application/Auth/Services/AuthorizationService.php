<?php

namespace App\Application\Auth\Services;

use App\Application\Auth\Contracts\AuthorizationServiceInterface;
use App\Application\User\Contracts\UserAuthorizationAdapterInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    public function __construct(
        private readonly UserAuthorizationAdapterInterface $adapter
    ) {}

    public function isWebAdmin(int $userId): bool
    {
        return $this->adapter->isWebAdmin($userId);
    }

    public function isBackpackAdmin(int $userId): bool
    {
        return $this->adapter->isBackpackAdmin($userId);
    }

    public function hasBackpackPermission(int $userId, string $permission): bool
    {
        return $this->adapter->hasBackpackPermission($userId, $permission);
    }
}
