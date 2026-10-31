<?php

declare(strict_types=1);

namespace App\Application\Party\Services;

use App\Domain\Party\Contracts\BusinessReadRepositoryInterface;
use App\Domain\Party\Contracts\ContactReadRepositoryInterface;
use App\Domain\Party\DTO\BusinessDTO;
use App\Domain\Party\DTO\ContactDTO;
use App\Domain\User\Enums\EntityType;
use App\Domain\User\ValueObjects\UserId;

final class LatestPartiesApplicationService
{
    private const LIMIT = 5;

    public function __construct(
        private readonly BusinessReadRepositoryInterface $businesses,
        private readonly ContactReadRepositoryInterface $contacts,
    ) {}

    /** @return list<BusinessDTO|ContactDTO> */
    public function listLatest(UserId $userId, EntityType $kind): array
    {
        return match ($kind) {
            EntityType::BUSINESS => $this->businesses->listLatestActiveForUser($userId, self::LIMIT),
            EntityType::CONTACT => $this->contacts->listLatestActiveForUser($userId, self::LIMIT),
        };
    }
}
