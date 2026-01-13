<?php

namespace App\Domain\Shared\Status\Contracts;

use App\Domain\Shared\Status\DTO\StatusCategoryDTO;
use App\Domain\Shared\Status\ValueObjects\StatusCategoryId;

/**
 * Minimal status lookup contract to decouple Domain services from Eloquent models.
 * Wave 2 scope: only lookup by slug returning ID or null.
 */
interface StatusCategoryDtoRepositoryInterface
{
    /** Return status ID for given slug or null if not found */
    public function findIdBySlug(string $slug): ?int;

    public function getAllForDropdown(): array;

    public function findById(StatusCategoryId $id): ?StatusCategoryDTO;

    public function findBySlug(string $slug): ?StatusCategoryDTO;

    public function getStatusCategoriesSlugIdMap(): array;

    public function getStatusCategoriesIdSlugMap(): array;
}
