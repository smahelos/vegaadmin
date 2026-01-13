<?php

namespace App\Domain\Party\Contracts;

use App\Domain\User\ValueObjects\UserId;

/**
 * Validates and normalizes input data for ad-hoc Party (Client/Supplier) creation.
 */
interface PartyCreationValidatorInterface
{
    /**
     * Validate & normalize raw input for client creation (ad-hoc resolveOrCreate flow).
     * Should throw domain exception on invalid data.
     *
     * @param UserId $userId
     * @param array<string,mixed> $data
     * @return array<string,mixed> Normalized payload ready for repository create
     */
    public function validateClient(UserId $userId, array $data): array;

    /**
     * Validate & normalize raw input for supplier creation (ad-hoc resolveOrCreate flow).
     * Should throw domain exception on invalid data.
     *
     * @param UserId $userId
     * @param array<string,mixed> $data
     * @return array<string,mixed> Normalized payload ready for repository create
     */
    public function validateSupplier(UserId $userId, array $data): array;
}
