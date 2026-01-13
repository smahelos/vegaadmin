<?php

namespace App\Application\Auth\Contracts;

/**
 * Authorization service contract for application layer.
 *
 * Provides guard-agnostic methods to check roles and permissions
 * without exposing Eloquent or Spatie to callers.
 */
interface AuthorizationServiceInterface
{
    /** Check if the given user has admin role on the default web guard. */
    public function isWebAdmin(int $userId): bool;

    /** Check if the given user has admin role on the backpack guard. */
    public function isBackpackAdmin(int $userId): bool;

    /** Check if the given user has a specific permission on the backpack guard. */
    public function hasBackpackPermission(int $userId, string $permission): bool;
}
