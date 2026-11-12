<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\DuplicatePartyDTO;
use App\Domain\Party\DTO\PartyProfile;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * Contacts the document's customer may duplicate when it is saved to the contacts.
 *
 * @implements QueryInterface<list<DuplicatePartyDTO>>
 */
final readonly class FindDocumentContactDuplicates extends OwnedUseCase implements QueryInterface
{
    public function __construct(
        UserId $owner,
        public PartyProfile $profile,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
            Permission::EXPENSE_CREATE,
            Permission::EXPENSE_UPDATE,
        );
    }
}
