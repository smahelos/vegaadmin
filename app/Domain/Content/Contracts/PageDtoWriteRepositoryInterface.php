<?php

namespace App\Domain\Content\Contracts;

use App\Domain\Content\DTO\PageDTO;
use App\Domain\Content\DTO\PageWriteData;

/**
 * DTO-based write repository for Page mutations.
 *
 * Layering guideline:
 * - Domain/Application services that chtějí pracovat s čistými daty bez Eloquent modelů
 *   by měly používat tento kontrakt.
 * - Backpack a UI admin CRUD by měly nadále používat modelový PageWriteRepositoryInterface.
 */
interface PageDtoWriteRepositoryInterface
{
    /** Create a new page and return its DTO. */
    public function create(PageWriteData $data): PageDTO;

    /** Update page by id using provided data (partial updates allowed); returns updated DTO or null if not found. */
    public function updateById(int $id, PageWriteData $data): ?PageDTO;

    /** Delete page by id; returns true if deleted. */
    public function deleteById(int $id): bool;

    /** Set published=true; returns true if updated. */
    public function publish(int $id): bool;

    /** Set published=false; returns true if updated. */
    public function unpublish(int $id): bool;

    /** Update sort order; returns true if updated. */
    public function reorder(int $id, int $sortOrder): bool;
}
