<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contacts the profile may duplicate; a blocking match is refused on save as well.
 *
 * @implements QueryInterface<list<DuplicatePartyDTO>>
 */
final readonly class FindContactDuplicates extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public PartyProfile $profile,
        public ?ContactId $except,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::CONTACT_VIEW);
    }
}
