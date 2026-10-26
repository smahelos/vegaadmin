<?php

namespace App\Application\Party\Services;

use App\Application\Party\Contracts\ClientReadRepositoryInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\Party\Contracts\SupplierReadRepositoryInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Thin Application-layer adapter over Domain party service.
 *
 * Keeps controllers free from Domain dependencies while delegating
 * business logic and repository access to the Domain service via interfaces.
 */
class PartyApplicationService implements PartyApplicationServiceInterface
{
    public function __construct(
        private readonly InvoicePartyServiceInterface $partyService,
        private readonly ClientReadRepositoryInterface $clientsRead,
        private readonly SupplierReadRepositoryInterface $suppliersRead,
        private readonly PartyAuthorizationService $authService,
    ) {}

    public function findClient(int $userId, int $id): Client
    {
        try {
            $client = $this->clientsRead->findByIdForUser($id, $userId);
            if (! $client) {
                throw new ModelNotFoundException('Client not found for userId ' . $userId . ' and clientId ' . $id);
            }

            return $client;
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found', [
                'userId' => $userId,
                'clientId' => $id,
                'error' => $e->getMessage(),
            ]);
            throw new ModelNotFoundException('Client not found for userId ' . $userId . ' and clientId ' . $id);
        }
    }

    public function findClientById(int $id): Client
    {
        try {
            $client = $this->clientsRead->findByIdAny($id);
            if (! $client) {
                throw new ModelNotFoundException('Client not found for clientId ' . $id);
            }

            return $client;
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found', ['clientId' => $id, 'error' => $e->getMessage()]);
            throw new ModelNotFoundException('Client not found for clientId ' . $id);
        }
    }

    public function listClients(User $user): array
    {
        return $this->authService->canAccessAnyClients($user->id)
            ? $this->clientsRead->allForAdmin()
            : $this->clientsRead->allForUser($user->id);
    }

    public function defaultClientOrFirst(int $userId): ?ClientDTO
    {
        return $this->partyService->defaultClientOrFirst($userId);
    }

    public function defaultClient(int $userId): ?ClientDTO
    {
        return $this->partyService->defaultClient($userId);
    }

    public function findSupplier(int $userId, int $id): Supplier
    {
        $supplier = $this->authService->canAccessAnySuppliers($userId)
            ? $this->suppliersRead->findByIdAny($id)
            : $this->suppliersRead->findByIdForUser($id, $userId);

        if (! $supplier) {
            throw new ModelNotFoundException('Supplier not found');
        }

        return $supplier;
    }

    public function listSuppliers(User $user): array
    {
        return $this->authService->canAccessAnySuppliers($user->id)
            ? $this->suppliersRead->allForAdmin()
            : $this->suppliersRead->allForUser($user->id);
    }

    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO
    {
        return $this->partyService->defaultSupplierOrFirst($userId);
    }

    public function defaultSupplier(int $userId): ?SupplierDTO
    {
        return $this->partyService->defaultSupplier($userId);
    }

    /**
     * Get list of clients for dropdown (id => name)
     *
     * @return array<int,string>
     */
    public function clientOptions(int $userId): array
    {
        return $this->clientsRead->getClientsForDropdown($userId);
    }

    /**
     * Get list of suppliers for dropdown (id => name)
     *
     * @return array<int,string>
     */
    public function supplierOptions(int $userId): array
    {
        return $this->suppliersRead->getSuppliersForDropdown($userId);
    }
}
