<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Shared\Status\Mappers\EloquentStatusMapper;
use App\Domain\Shared\Status\DTO\StatusDTO;
use App\Domain\Shared\Status\ValueObjects\StatusId;
use App\Models\Status;

class EloquentStatusDtoRepository implements StatusDtoRepositoryInterface
{
    public function __construct(
        private readonly EloquentStatusRepository $statusRepository,
        private readonly EloquentStatusMapper $mapper
    ) {
    }

    public function findIdBySlug(string $slug): ?int
    {
        return $this->statusRepository->findIdBySlug($slug);
    }

    public function getAllForDropdown(): array
    {
        return $this->statusRepository->getAllForDropdown();
    }

    public function findById(StatusId $id): ?StatusDTO
    {
        $status = $this->statusRepository->findById($id->getValue());

        return $status ? $this->mapper->toDto($status) : null;
    }

    public function findBySlug(string $slug): ?StatusDTO
    {
        $status = $this->statusRepository->findBySlug($slug);

        return $status ? $this->mapper->toDto($status) : null;
    }

    public function getStatusSlugIdMap(): array
    {
        return $this->statusRepository->getStatusSlugIdMap();
    }

    public function getStatusIdSlugMap(): array
    {
        return $this->statusRepository->getStatusIdSlugMap();
    }
}
