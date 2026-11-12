<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Party\DTO\BankAccountWriteData;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\BusinessWriteData;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The first account entered with the business is part of creating it (PRD_PERMISSIONS 4.8).
 *
 * @implements CommandInterface<BusinessDTO>
 */
final readonly class CreateBusiness extends OwnedUseCase implements CommandInterface
{
    public function __construct(
        UserId $owner,
        public BusinessWriteData $data,
        public ?BankAccountWriteData $firstAccount,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permissionWithOwner(Permission::BUSINESS_CREATE);
    }
}
