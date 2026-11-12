<?php

declare(strict_types=1);

namespace App\Application\Party\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Businesses the profile may duplicate; a blocking match is refused on save as well.
 *
 * @implements QueryInterface<list<DuplicatePartyDTO>>
 */
final readonly class FindBusinessDuplicates extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public PartyProfile $profile,
        public ?BusinessId $except,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::BUSINESS_VIEW);
    }
}
