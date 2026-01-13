<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Domain\Shared\Status\Contracts\StatusCategoryDtoRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Shared\Status\Mappers\EloquentStatusCategoryMapper;
use App\Domain\Shared\Status\DTO\StatusCategoryDTO;
use App\Domain\Shared\Status\ValueObjects\StatusCategoryId;
use App\Models\Statuscategory;

class EloquentStatusCategoryDtoRepository implements StatusCategoryDtoRepositoryInterface
{
    public function __construct(
        private readonly EloquentStatusCategoryRepository $statusCategoryRepository,
        private readonly EloquentStatusCategoryMapper $mapper
    ) {
    }

    public function findIdBySlug(string $slug): ?int
    {
        return $this->statusCategoryRepository->findIdBySlug($slug);
    }

    public function getAllForDropdown(): array
    {
        return $this->statusCategoryRepository->getAllForDropdown();
    }

    public function findById(StatusCategoryId $id): ?StatusCategoryDTO
    {
        $status = $this->statusCategoryRepository->findById($id->getValue());

        return $status ? $this->mapper->toDto($status) : null;
    }

    public function findBySlug(string $slug): ?StatusCategoryDTO
    {
        $status = $this->statusCategoryRepository->findBySlug($slug);

        return $status ? $this->mapper->toDto($status) : null;
    }

    public function getStatusCategoriesSlugIdMap(): array
    {
        return $this->statusCategoryRepository->getStatusCategoriesSlugIdMap();
    }

    public function getStatusCategoriesIdSlugMap(): array
    {
        return $this->statusCategoryRepository->getStatusCategoriesIdSlugMap();
    }
}
