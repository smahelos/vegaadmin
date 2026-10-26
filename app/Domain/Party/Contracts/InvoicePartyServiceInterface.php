<?php

namespace App\Domain\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\User\ValueObjects\UserId;

interface InvoicePartyServiceInterface
{
    public function defaultClient(int $userId): ?ClientDTO;

    public function defaultSupplier(int $userId): ?SupplierDTO;

    public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO; // ensures user ownership

    public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO; // ensures user ownership

    // Role/permission-aware list methods moved to Application layer

    /** Default supplier resolution for user (admin unaffected) */
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO;

    /** Default client resolution for user (admin unaffected) */
    public function defaultClientOrFirst(int $userId): ?ClientDTO;
}
