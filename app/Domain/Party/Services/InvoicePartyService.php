<?php

namespace App\Domain\Party\Services;

use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoWriteRepositoryInterface;
use App\Domain\Party\Contracts\InvoicePartyServiceInterface;
use App\Domain\Party\Contracts\PartyCreationValidatorInterface;
use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\SupplierDtoWriteRepositoryInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\ClientWriteData;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\SupplierWriteData;
use App\Domain\Party\Events\ClientCreated;
use App\Domain\Party\Events\SupplierCreated;
use App\Domain\Party\Factories\PartyDtoFactory;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\Shared\Events\Contracts\EventPublisherInterface;
use App\Domain\User\ValueObjects\UserId;

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
     */
    public function defaultClient(int $userId): ?ClientDTO
    {
        return $this->readClients->getDefaultClient($userId);
    }

    /**
     * Summary of defaultSupplier
     */
    public function defaultSupplier(int $userId): ?SupplierDTO
    {
        return $this->readSuppliers->getDefaultSupplier($userId);
    }

    /**
     * Resolve or create a client
     */
    public function resolveOrCreateClient(UserId $userId, array $data): ClientDTO
    {
        if (! empty($data['client_id'])) {
            $partyId = PartyId::fromInt((int) $data['client_id']);
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
     * Resolve or create supplier
     */
    public function resolveOrCreateSupplier(UserId $userId, array $data): SupplierDTO
    {
        if (! empty($data['supplier_id'])) {
            // Enforce ownership when resolving by provided ID
            $partyId = PartyId::fromInt((int) $data['supplier_id']);
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
     * Get default supplier or first if none marked default
     */
    public function defaultSupplierOrFirst(int $userId): ?SupplierDTO
    {
        $default = $this->readSuppliers->getDefaultSupplier($userId);
        if ($default) {
            return $default;
        }

        return $this->readSuppliers->firstForUser($userId);
    }

    /**
     * Get default client or first if none marked default
     */
    public function defaultClientOrFirst(int $userId): ?ClientDTO
    {
        $default = $this->readClients->getDefaultClient($userId);
        if ($default) {
            return $default;
        }

        return $this->readClients->firstForUser($userId);
    }
}
