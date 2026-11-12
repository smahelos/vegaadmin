<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\PartySearch;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<PaginatedResult<BusinessDTO>> */
final readonly class ListBusinesses extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public PartySearch $search,
        public PageRequest $page,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BUSINESS_VIEW);
    }
}
