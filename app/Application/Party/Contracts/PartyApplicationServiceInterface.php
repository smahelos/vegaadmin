<?php

namespace App\Application\Party\Contracts;

use App\Models\User;
use App\Models\Client;
use App\Models\Supplier;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;

/**
 * Application layer facade for party-related operations (clients & suppliers).
 * Controllers should depend on this interface, not on Domain services.
 */
interface PartyApplicationServiceInterface
{
    /**
     * Find a client for given user.
     */
    public function findClient(int $userId, int $id): Client;

    /**
     * Find any client by ID (admin access - no user scoping).
     */
    public function findClientById(int $id): Client;

    /**
     * List clients based on user role.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listClients(User $user): array;

    /**
     * Get default client or first for the user.
     */
    public function defaultClient(int $userId): ?ClientDTO;

    /**
     * Get default client or first for the user.
     */
    public function defaultClientOrFirst(int $userId): ?ClientDTO;

    /**
     * Find a supplier for given user.
     */
    public function findSupplier(int $userId, int $id): Supplier;

    /**
     * List suppliers based on user role.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listSuppliers(User $user): array;

    /**
     * Get default supplier or first for the user.
     */
    public function defaultSupplier(int $userId): ?SupplierDTO;

    /**
     * Get default supplier or first for the user.
     */
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO;

    /**
     * Create or resolve client and indicate if created.
     * @return array{client: Client, created: bool}
     */
    public function resolveOrCreateClientWithFlag(int $userId, array $data): array;

    /**
     * Create or resolve client and indicate if created.
     * @return array{client: Client, created: bool}
     */
    public function resolveOrCreateClient(int $userId, array $data): Client;

    /** Update existing client */
    public function updateClient(int $clientId, array $data): Client;

    /** Delete client if allowed (returns false if blocked) */
    public function deleteClient(int $userId, int $clientId): bool;

    /** Set supplied client as default */
    public function setClientDefault(int $userId, int $clientId): void;

    /**
     * Create or resolve supplier and indicate if created.
     * @return array{supplier: Supplier, created: bool}
     */
    public function resolveOrCreateSupplierWithFlag(int $userId, array $data): array;

    /**
     * Create or resolve supplier and indicate if created.
     * @return array{supplier: Supplier, created: bool}
     */
    public function resolveOrCreateSupplier(int $userId, array $data): Supplier;

    /** Update existing supplier */
    public function updateSupplier(int $supplierId, array $data): Supplier;

    /** Delete supplier if allowed (returns false if blocked) */
    public function deleteSupplier(int $userId, int $supplierId): bool;

    /** Set supplied supplier as default */
    public function setSupplierDefault(int $userId, int $supplierId): void;

    /**
     * Get list of clients for dropdown (id => name)
     * @param int $userId
     * @return array<int,string>
     */
    public function clientOptions(int $userId): array;

    /**
     * Get list of suppliers for dropdown (id => name)
     * @param int $userId
     * @return array<int,string>
     */
    public function supplierOptions(int $userId): array;
}
