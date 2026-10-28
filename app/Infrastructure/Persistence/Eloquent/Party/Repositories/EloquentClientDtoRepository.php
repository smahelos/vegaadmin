<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\ClientDtoReadRepositoryInterface;
use App\Domain\Party\DTO\ClientDTO;
use App\Domain\Party\ValueObjects\PartyId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentClientMapper as Mapper;

/**
 * DTO read repository implemented over Eloquent models.
 *
 * Responsibilities:
 * - Delegates queries to EloquentPageRepository (model-based repo)
 * - Maps models to domain DTOs via EloquentPageMapper
 * - Intended for Domain services to avoid Eloquent dependencies
 */
class EloquentClientDtoRepository implements ClientDtoReadRepositoryInterface
{
    public function __construct(private readonly EloquentClientRepository $repo) {}

    public function findById(PartyId $id): ?ClientDTO
    {
        $m = $this->repo->findById($id->toInt());

        return $m ? Mapper::toClientDTO($m) : null;
    }
}
