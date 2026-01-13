<?php

namespace App\Application\Content\Contracts;

use App\Models\Page;

/**
 * Write repository for Page and PageCategory queries (MODEL-BASED).
 *
 * Layering guideline:
 * - Application/Backpack layers should use this interface and work with Eloquent models (CRUD, filters, pagination).
 * - Domain layer MUST NOT depend on models; it should use PageDtoWriteRepositoryInterface instead.
 * - Because of Domain layer interfaces using DTO/VOs, this interface is in Application layer.
 *   This interface can use Eloquent Models and Collections and is used by Application services
 *   for rendering UI (data for forms, blade templates, etc.) and NON-business logic with DB operations.
 *
 * @see \App\Domain\Content\Contracts\PageDtoWriteRepositoryInterface
 *
 */
interface PageWriteRepositoryInterface
{
    /** Create a new page. */
    public function create(array $attributes): Page;

    /** Update page attributes by id. */
    public function updateById(int $id, array $attributes): ?Page;

    /** Delete page by id. */
    public function deleteById(int $id): bool;

    
}
