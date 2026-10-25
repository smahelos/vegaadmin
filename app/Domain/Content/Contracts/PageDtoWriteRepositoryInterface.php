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
}
