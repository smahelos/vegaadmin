<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/** @implements CommandInterface<BusinessDTO> */
final readonly class UpdateBusiness extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public BusinessId $id,
        public BusinessWriteData $data,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::BUSINESS_UPDATE);
    }
}
