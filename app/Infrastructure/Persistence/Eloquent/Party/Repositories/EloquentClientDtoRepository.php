<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\ClientDtoWriteRepositoryInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\DTO\ClientWriteData;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentClientMapper as Mapper;
use App\Models\Client;

/**
 * DTO read repository implemented over Eloquent models.
 *
 * Responsibilities:
 * - Delegates queries to EloquentPageRepository (model-based repo)
 * - Maps models to domain DTOs via EloquentPageMapper
 * - Intended for Domain services to avoid Eloquent dependencies
 */
class EloquentClientDtoRepository implements ClientDtoReadRepositoryInterface, ClientDtoWriteRepositoryInterface
{
    public function __construct(private readonly EloquentClientRepository $repo) {}

    public function getDefaultClient(int $userId): ?ClientDTO
    {
        $m = $this->repo->getDefaultClient($userId);

        return $m ? Mapper::toClientDTO($m) : null;
    }

    public function findById(PartyId $id): ?ClientDTO
    {
        $m = $this->repo->findById($id->toInt());

        return $m ? Mapper::toClientDTO($m) : null;
    }

    public function findByIdForUser(PartyId $id, UserId $userId): ?ClientDTO
    {
        $m = $this->repo->findByIdForUser($id->toInt(), $userId->toInt());

        return $m ? Mapper::toClientDTO($m) : null;
    }

    public function firstForUser(int $userId): ?ClientDTO
    {
        $m = $this->repo->firstForUser($userId);

        return $m ? Mapper::toClientDTO($m) : null;
    }

    public function create(ClientWriteData $data): ClientDTO
    {
        $model = Client::create($data->toArray());

        // Load relations to keep consistency with read DTOs (e.g., category)
        // $model->load(['category', 'children']);
        return Mapper::toClientDTO($model);
    }

    public function updateById(PartyId $id, ClientWriteData $data): bool
    {
        return $this->repo->updateById($id->toInt(), $data->toArray());
    }

    public function deleteById(PartyId $id): bool
    {
        return $this->repo->deleteById($id->toInt());
    }

    public function hasLinkedInvoices(PartyId $id): bool
    {
        return $this->repo->hasLinkedInvoices($id->toInt());
    }
}
