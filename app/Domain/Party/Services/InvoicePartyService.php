<?php

namespace App\Domain\Party\Services;

use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoWriteRepositoryInterface;
use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\SupplierDtoWriteRepositoryInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\ClientWriteData;
use App\Domain\Party\DTO\SupplierWriteData;
use App\Domain\Party\Factories\PartyDtoFactory;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\Party\Events\ClientCreated;
use App\Domain\Party\Events\SupplierCreated;
use App\Domain\Party\Contracts\PartyCreationValidatorInterface;

class InvoicePartyService implements InvoicePartyServiceInterface
{
    public function __construct(
        private ClientDtoReadRepositoryInterface $readClients,
        private ClientDtoWriteRepositoryInterface $writeClients,
        private SupplierDtoReadRepositoryInterface $readSuppliers,
        private SupplierDtoWriteRepositoryInterface $writeSuppliers,
        private PartyCreationValidatorInterface $creationValidator,
        private readonly EventPublisherInterface $events,
    ) {}

    /**
     * Summary of defaultClient
     * @param int $userId
     * @return ClientDTO|null
     */
    public function defaultClient(int $userId): ?ClientDTO
    {
        return $this->readClients->getDefaultClient($userId);
    }

    /**
     * Summary of defaultSupplier
     * @param int $userId
     * @return SupplierDTO|null
     */
    public function defaultSupplier(int $userId): ?SupplierDTO
    {
        return $this->readSuppliers->getDefaultSupplier($userId);
    }

    /**
     * Resolve or create a client
     * @param UserId $userId
     * @param array $data
     * @return ClientDTO
     */
    public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO
    {
        if (!empty($data['client_id'])) {
            $partyId = PartyId::fromInt((int)$data['client_id']);
            // Enforce ownership when resolving by provided ID
            $existing = $this->readClients->findByIdForUser($partyId, $userId);
            if ($existing) {
                return $existing;
            }
        }
        $payload = $this->creationValidator->validateClient($userId, $data);
        $writeData = $payload instanceof ClientWriteData ? $payload : PartyDtoFactory::createClientWriteData($payload);
        $created = $this->writeClients->create($writeData);
        
        // Dispatch Domain Event for new client creation
        $this->events->publish(new ClientCreated($created, $userId->toInt()));
        
        return $created;
    }

    /**
     * Resolve or create a client returning both the instance and a created flag.
     * @param UserId $userId
     * @param array $data
     * @return array{client: ClientDTO, created: bool}
     */
    public function resolveOrCreateClientWithFlag(UserId $userId, array $data): array
    {
        if (!empty($data['client_id'])) {
            $partyId = PartyId::fromInt((int)$data['client_id']);
            // Enforce ownership when resolving by provided ID
            $existing = $this->readClients->findByIdForUser($partyId, $userId);
            if ($existing) {
                return ['client' => $existing, 'created' => false];
            }
        }
        $payload = $this->creationValidator->validateClient($userId, $data);
        $writeData = $payload instanceof ClientWriteData ? $payload : PartyDtoFactory::createClientWriteData($payload);
        $created = $this->writeClients->create($writeData);
        
        if ($created) {
            // Dispatch Domain Event for usage recording (replaces old Observer pattern)
            $this->events->publish(new ClientCreated($created, $userId->toInt()));
        }
        
        return ['client' => $created, 'created' => true];
    }

    /**
     * Resolve or create supplier
     * @param UserId $userId
     * @param array $data
     * @return SupplierDTO
     */
    public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO
    {
        if (!empty($data['supplier_id'])) {
            // Enforce ownership when resolving by provided ID
            $partyId = PartyId::fromInt((int)$data['supplier_id']);
            $existing = $this->readSuppliers->findByIdForUser($partyId, $userId);
            if ($existing) {
                return $existing;
            }
        }
        $payload = $this->creationValidator->validateSupplier($userId, $data);
        $writeData = $payload instanceof SupplierWriteData ? $payload : PartyDtoFactory::createSupplierWriteData($payload);
        $created = $this->writeSuppliers->create($writeData);
        
        // Dispatch Domain Event for new supplier creation
        $this->events->publish(new SupplierCreated($created, $userId->toInt()));
        
        return $created;
    }

    /**
     * Resolve or create supplier returning both the instance and a created flag.
     * @param UserId $userId
     * @param array $data
     * @return array{supplier: SupplierDTO, created: bool}
     */
    public function resolveOrCreateSupplierWithFlag(UserId $userId, array $data): array
    {
        if (!empty($data['supplier_id'])) {
            // Enforce ownership when resolving by provided ID
            $partyId = PartyId::fromInt((int)$data['supplier_id']);
            $existing = $this->readSuppliers->findByIdForUser($partyId, $userId);
            if ($existing) {
                return ['supplier' => $existing, 'created' => false];
            }
        }
        $payload = $this->creationValidator->validateSupplier($userId, $data);
        $writeData = $payload instanceof SupplierWriteData ? $payload : PartyDtoFactory::createSupplierWriteData($payload);
        $created = $this->writeSuppliers->create($writeData);
        
        // Dispatch Domain Event for new supplier creation
        $this->events->publish(new SupplierCreated($created, $userId->toInt()));
        
        return ['supplier' => $created, 'created' => true];
    }

    /**
     * Update a client with new data
     * @param int $clientId
     * @param array $data
     * @return ClientDTO
     */
    public function updateClient(int $clientId, array $data): ClientDTO
    {
        $partyId = PartyId::fromInt($clientId);
        $writeData = PartyDtoFactory::createClientWriteData($data);
        $this->writeClients->updateById($partyId, $writeData);
        return $this->readClients->findById($partyId);
    }

    /**
     * Update a supplier with new data
     * @param int $supplierId
     * @param array $data
     * @return SupplierDTO
     */
    public function updateSupplier(int $supplierId, array $data): SupplierDTO
    {
        $partyId = PartyId::fromInt($supplierId);
        $writeData = PartyDtoFactory::createSupplierWriteData($data);
        $this->writeSuppliers->updateById($partyId, $writeData);
        return $this->readSuppliers->findById($partyId);
    }

    /**
     * Set a supplier as default for the user, unsetting others
     * @param int $userId
     * @param int $supplierId
     * @return void
     */
    public function setSupplierDefault(int $userId, int $supplierId): void
    {
        $partyId = PartyId::fromInt($supplierId);
        
        // Unset default flag on other suppliers
        $this->writeSuppliers->unsetOthersDefault($partyId, $userId);

        // Get current supplier DTO, clone its data and set is_default = true
        $supplierDto = $this->readSuppliers->findById($partyId);
        $writeData = $this->cloneWriteDto($supplierDto, SupplierWriteData::class, ['is_default' => true]);

        $this->writeSuppliers->updateById($partyId, $writeData);
    }

    /**
     * Summary of setClientDefault
     * @param int $userId
     * @param int $clientId
     * @return void
     */
    public function setClientDefault(int $userId, int $clientId): void
    {
        $partyId = PartyId::fromInt($clientId);
        
        // Unset default flag on other clients
        $this->writeClients->unsetOthersDefault($partyId, $userId);

        // Get current client DTO, clone its data and set is_default = true
        $clientDto = $this->readClients->findById($partyId);
        $writeData = $this->cloneWriteDto($clientDto, ClientWriteData::class, ['is_default' => true]);

        $this->writeClients->updateById($partyId, $writeData);
    }

    /**
     * Delete a supplier if no invoices are linked
     * @param int $userId
     * @param int $supplierId
     * @return bool True if deleted, false if not (due to linked invoices)
     */
    public function deleteSupplier(int $userId, int $supplierId): bool
    {
        $partyId = PartyId::fromInt($supplierId);
        
        // First check ownership
        $supplier = $this->readSuppliers->findByIdForUser($partyId, UserId::fromInt($userId));
        if (!$supplier) {
            return false;
        }

        // Check if supplier has linked invoices  
        if ($this->writeSuppliers->hasLinkedInvoices($partyId)) {
            return false;
        }

        return $this->writeSuppliers->deleteById($partyId);
    }

    /**
     * Delete a client if no invoices are linked
     * @param int $userId
     * @param int $clientId
     * @return bool True if deleted, false if not (due to linked invoices)
     */
    public function deleteClient(int $userId, int $clientId): bool
    {
        $partyId = PartyId::fromInt($clientId);
        
        // First check ownership
        $client = $this->readClients->findByIdForUser($partyId, UserId::fromInt($userId));
        if (!$client) {
            return false;
        }
        
        // Check if client has linked invoices
        if ($this->writeClients->hasLinkedInvoices($partyId)) {
            return false;
        }
        
        return $this->writeClients->deleteById($partyId);
    }

    /**
     * Get default supplier or first if none marked default
     * @param int $userId
     * @return SupplierDTO|null
     */
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO
    {
        $default = $this->readSuppliers->getDefaultSupplier($userId);
        if ($default) return $default;
        return $this->readSuppliers->firstForUser($userId);
    }

    /**
     * Get default client or first if none marked default
     * @param int $userId
     * @return ClientDTO|null
     */
    public function defaultClientOrFirst(int $userId): ?ClientDTO
    {
        $default = $this->readClients->getDefaultClient($userId);
        if ($default) return $default;
        return $this->readClients->firstForUser($userId);
    }

    /**
     * Clone a DTO with some field overrides.
     * Used to maintain immutability while updating specific fields.
     * @param object $dto
     * @param string $writeClass
     * @param array $overrides
     * @return object
     */
    private function cloneWriteDto(object $dto, string $writeClass, array $overrides): object
    {
        $data = get_object_vars($dto);
        foreach ($overrides as $k => $v) {
            $data[$k] = $v;
        }
        // Use Factory instead of buildDto
        return match($writeClass) {
            ClientWriteData::class => PartyDtoFactory::createClientWriteData($data),
            SupplierWriteData::class => PartyDtoFactory::createSupplierWriteData($data),
            default => throw new \InvalidArgumentException("Unsupported DTO class: $writeClass")
        };
    }
}
