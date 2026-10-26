<?php

namespace App\Application\Party\Contracts;

use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;

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
     * Get list of clients for dropdown (id => name)
     *
     * @return array<int,string>
     */
    public function clientOptions(int $userId): array;

    /**
     * Get list of suppliers for dropdown (id => name)
     *
     * @return array<int,string>
     */
    public function supplierOptions(int $userId): array;
}
