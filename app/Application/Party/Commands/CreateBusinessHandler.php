<?php

declare(strict_types=1);

namespace App\Application\Party\Commands;

use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\Services\BusinessCreator;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Services\EntityLimit;

final class CreateBusinessHandler
{
    public function __construct(
        private readonly BusinessCreator $creator,
        private readonly EntityLimit $limits,
    ) {}

    public function handle(CreateBusiness $command): BusinessDTO
    {
        $this->limits->assertCanCreate($command->owner, LimitedEntity::BUSINESS);

        return $this->creator->create(
            $command->owner,
            $command->data,
            $command->firstAccount,
        );
    }
}
