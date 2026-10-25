<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;

class EloquentStatusDtoRepository implements StatusDtoRepositoryInterface
{
    public function __construct(
        private readonly EloquentStatusRepository $statusRepository,
    ) {}

    public function findIdBySlug(string $slug): ?int
    {
        return $this->statusRepository->findIdBySlug($slug);
    }

    public function getAllForDropdown(): array
    {
        return $this->statusRepository->getAllForDropdown();
    }
}
