<?php

namespace App\Domain\Shared\Status\Contracts;

use App\Domain\Shared\Status\DTO\StatusDTO;
use App\Domain\Shared\Status\ValueObjects\StatusId;

/**
 * Minimal status lookup contract to decouple Domain services from Eloquent models.
 * Wave 2 scope: only lookup by slug returning ID or null.
 */
interface StatusDtoRepositoryInterface
{
    /** Return status ID for given slug or null if not found */
    public function findIdBySlug(string $slug): ?int;

    public function getAllForDropdown(): array;

    public function findById(StatusId $id): ?StatusDTO;

    public function findBySlug(string $slug): ?StatusDTO;

    public function getStatusSlugIdMap(): array;

    public function getStatusIdSlugMap(): array;
}
