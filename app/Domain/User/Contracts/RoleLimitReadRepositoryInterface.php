<?php

declare(strict_types=1);

namespace App\Domain\User\Contracts;

use App\Domain\User\DTO\RoleLimitDTO;
use App\Domain\User\ValueObjects\UserId;

interface RoleLimitReadRepositoryInterface
{
    /**
     * The limit rows of the user's web roles; a correctly configured user has exactly one.
     *
     * @return list<RoleLimitDTO>
     */
    public function listForUser(UserId $user): array;
}
