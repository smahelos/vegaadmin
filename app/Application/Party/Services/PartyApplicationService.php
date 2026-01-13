<?php

namespace App\Application\Party\Services;

use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\Party\Services\PartyAuthorizationService;
use App\Application\Party\Mappers\ClientArrayMapper;
use App\Application\Party\Mappers\SupplierArrayMapper;
use App\Application\Party\Services\PartyInputValidationService;
use App\Application\Party\Contracts\ClientReadRepositoryInterface;
use App\Application\Party\Contracts\SupplierReadRepositoryInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
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
        private readonly ClientArrayMapper $clientMapper,
        private readonly SupplierArrayMapper $supplierMapper,
        private readonly PartyInputValidationService $validationService,
        private readonly UserReadRepositoryInterface $userReadRepository
    )
    {
    }

    public function findClient(int $userId, int $id): Client
    {
        try {
            $client = $this->clientsRead->findByIdForUser($id, $userId);
            if (!$client) {
                throw new ModelNotFoundException('Client not found for userId ' . $userId . ' and clientId ' . $id);
            }
            return $client;
        } catch (ModelNotFoundException $e) {
            Log::error('Client not found', ['userId' => $userId, 'clientId' => $id, 'error' => $e->getMessage()]);
            throw new ModelNotFoundException('Client not found for userId ' . $userId . ' and clientId ' . $id);
        }
    }

    public function findClientById(int $id): Client
    {
        try {
            $client = $this->clientsRead->findByIdAny($id);
            if (!$client) {
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

        if (!$supplier) {
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

    public function resolveOrCreateClient(int $userId, array $data): Client
    {
        $this->validationService->validateClientBusinessRules($data, $userId);
        
        return DB::transaction(fn() => 
            $this->partyService->resolveOrCreateClient(UserId::fromInt($userId), $data)
        );
    }

    public function resolveOrCreateClientWithFlag(int $userId, array $data): array
    {
        $this->validationService->validateClientBusinessRules($data, $userId);
        
        return DB::transaction(fn() => 
            $this->partyService->resolveOrCreateClientWithFlag(UserId::fromInt($userId), $data)
        );
    }

    public function updateClient(int $clientId, array $data): Client
    {
        // For updates, we need to get user_id and exclude current client from uniqueness checks
        $client = $this->findClient(auth()->user()->id, $clientId);
        $this->validationService->validateClientBusinessRules($data, $client->user_id, $clientId);
        
        DB::transaction(fn() => 
            $this->partyService->updateClient($clientId, $data)
        );

        // Return the updated Client model
        return $this->findClient(auth()->user()->id, $clientId);
    }

    public function deleteClient(int $userId, int $clientId): bool
    {
        return DB::transaction(fn() => 
            $this->partyService->deleteClient($userId, $clientId)
        );
    }

    public function setClientDefault(int $userId, int $clientId): void
    {
        DB::transaction(fn() => 
            $this->partyService->setClientDefault($userId, $clientId)
        );
    }

    public function resolveOrCreateSupplier(int $userId, array $data): Supplier
    {
        $this->validationService->validateSupplierBusinessRules($data, $userId);
        
        return DB::transaction(fn() => 
            $this->partyService->resolveOrCreateSupplier(UserId::fromInt($userId), $data)
        );
    }

    public function resolveOrCreateSupplierWithFlag(int $userId, array $data): array
    {
        $this->validationService->validateSupplierBusinessRules($data, $userId);
        
        return DB::transaction(fn() => 
            $this->partyService->resolveOrCreateSupplierWithFlag(UserId::fromInt($userId), $data)
        );
    }

    public function updateSupplier(int $supplierId, array $data): Supplier
    {
        // For updates, we need to get user_id and exclude current supplier from uniqueness checks
        $supplier = $this->findSupplier(auth()->user()->id, $supplierId);
        $this->validationService->validateSupplierBusinessRules($data, $supplier->user_id, $supplierId);
        
        DB::transaction(fn() => 
            $this->partyService->updateSupplier($supplierId, $data)
        );
        
        // Return the updated Supplier model
        return $this->findSupplier(auth()->user()->id, $supplierId);
    }

    public function deleteSupplier(int $userId, int $supplierId): bool
    {
        return DB::transaction(fn() => 
            $this->partyService->deleteSupplier($userId, $supplierId)
        );
    }

    public function setSupplierDefault(int $userId, int $supplierId): void
    {
        DB::transaction(fn() => 
            $this->partyService->setSupplierDefault($userId, $supplierId)
        );
    }

    /**
     * Get list of clients for dropdown (id => name)
     * @param int $userId
     * @return array<int,string>
     */
    public function clientOptions(int $userId): array
    {
        return $this->clientsRead->getClientsForDropdown($userId);
    }

    /**
     * Get list of suppliers for dropdown (id => name)
     * @param int $userId
     * @return array<int,string>
     */
    public function supplierOptions(int $userId): array
    {
        return $this->suppliersRead->getSuppliersForDropdown($userId);
    }
}
