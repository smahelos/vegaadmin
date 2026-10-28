<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\ValueObjects\PartyId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentSupplierMapper as Mapper;

/**
 * DTO read repository implemented over Eloquent models.
 *
 * Responsibilities:
 * - Delegates queries to EloquentSuppllierRepository (model-based repo)
 * - Maps models to domain DTOs via EloquentSupplierMapper
 * - Intended for Domain services to avoid Eloquent dependencies
 */
class EloquentSupplierDtoRepository implements SupplierDtoReadRepositoryInterface
{
    public function __construct(private readonly EloquentSupplierRepository $repo) {}

    public function findById(PartyId $id): ?SupplierDTO
    {
        $m = $this->repo->findById($id->toInt());

        return $m ? Mapper::toSupplierDTO($m) : null;
    }
}
