<?php

declare(strict_types=1);

namespace App\Application\User\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use App\Domain\User\DTO\UserDTO;
use App\Domain\User\Enums\Permission;

/**
 * The admin's owner lookup in forms that select an owner.
 *
 * @implements QueryInterface<PaginatedResult<UserDTO>>
 */
final readonly class SearchUsers implements QueryInterface
{
    public function __construct(
        public ?string $term,
        public PageRequest $page,
    ) {}

    public static function access(): Access
    {
        return Access::allOwners(Permission::USER_VIEW);
    }
}
