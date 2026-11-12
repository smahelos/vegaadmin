<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\Party\DTO\ContactWriteData;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<ContactDTO> */
final readonly class UpdateContact extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public ContactId $id,
        public ContactWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::CONTACT_UPDATE);
    }
}
