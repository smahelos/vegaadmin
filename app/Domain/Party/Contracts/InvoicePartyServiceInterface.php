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

    /**
     * Resolve or create supplier returning created flag.
     * @return array{supplier: SupplierDTO, created: bool}
     */
    public function resolveOrCreateSupplierWithFlag(UserId $userId, array $data): array; // ['supplier'=>Supplier,'created'=>bool]
    
    /**
     * Resolve or create client but also return creation flag.
     *
     * @return array{client: ClientDTO, created: bool}
     */
    public function resolveOrCreateClientWithFlag(UserId $userId, array $data): array; // ['client'=>Client,'created'=>bool]

    // Role/permission-aware find/list methods have been moved to Application layer

    /** Update existing client */
    public function updateClient(int $clientId, array $data): ClientDTO;
    
    /** Update existing supplier (handles default logic) */
    public function updateSupplier(int $supplierId, array $data): SupplierDTO;

    /** Set supplied supplier as default (unsets others) */
    public function setSupplierDefault(int $userId, int $supplierId): void;
    
    /** Set supplied client as default (unsets others) */
    public function setClientDefault(int $userId, int $clientId): void;

    /** Delete supplier if allowed (returns false if blocked by invoices) */
    public function deleteSupplier(int $userId, int $supplierId): bool;
    
    /** Delete client if allowed (returns false if blocked by invoices) */
    public function deleteClient(int $userId, int $clientId): bool;

    // Role/permission-aware list methods moved to Application layer
    
    /** Default supplier resolution for user (admin unaffected) */
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO;
    
    /** Default client resolution for user (admin unaffected) */
    public function defaultClientOrFirst(int $userId): ?ClientDTO;
}
