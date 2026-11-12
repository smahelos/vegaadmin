<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements QueryInterface<list<ContactDTO>> */
final readonly class ListLatestContacts extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::CONTACT_VIEW);
    }
}
