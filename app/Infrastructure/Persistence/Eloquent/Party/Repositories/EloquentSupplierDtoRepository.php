<?php

namespace App\Infrastructure\Persistence\Eloquent\Party\Repositories;

use App\Domain\Party\Contracts\SupplierDtoReadRepositoryInterface;
use App\Domain\Party\Contracts\SupplierDtoWriteRepositoryInterface;
use App\Domain\Party\DTO\SupplierDTO;
use App\Domain\Party\DTO\SupplierWriteData;
use App\Domain\Party\ValueObjects\PartyId;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Party\Mappers\EloquentSupplierMapper as Mapper;
use App\Models\Supplier;

/**
 * DTO read repository implemented over Eloquent models.
 *
 * Responsibilities:
 * - Delegates queries to EloquentSuppllierRepository (model-based repo)
 * - Maps models to domain DTOs via EloquentSupplierMapper
 * - Intended for Domain services to avoid Eloquent dependencies
 */
class EloquentSupplierDtoRepository implements SupplierDtoReadRepositoryInterface, SupplierDtoWriteRepositoryInterface
{
    public function __construct(private readonly EloquentSupplierRepository $repo) {}

    public function getDefaultSupplier(int $userId): ?SupplierDTO
    {
        $m = $this->repo->getDefaultSupplier($userId);

        return $m ? Mapper::toSupplierDTO($m) : null;
    }

    public function findById(PartyId $id): ?SupplierDTO
    {
        $m = $this->repo->findById($id->toInt());

        return $m ? Mapper::toSupplierDTO($m) : null;
    }

    public function findByIdForUser(PartyId $id, UserId $userId): SupplierDTO
    {
        $m = $this->repo->findByIdForUser($id->toInt(), $userId->toInt());

        return Mapper::toSupplierDTO($m);
    }

    public function firstForUser(int $userId): ?SupplierDTO
    {
        $m = $this->repo->firstForUser($userId);

        return $m ? Mapper::toSupplierDTO($m) : null;
    }

    public function create(SupplierWriteData $data): SupplierDTO
    {
        $model = Supplier::create($data->toArray());

        // Load relations to keep consistency with read DTOs (e.g., category)
        // $model->load(['category', 'children']);
        return Mapper::toSupplierDTO($model);
    }
}
