<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase;

use App\Domain\User\ValueObjects\UserId;

/**
 * A use case over the data of one owner: the gate checks the owner and issues a scope of that owner.
 */
abstract readonly class OwnedUseCase
{
    public function __construct(
        public UserId $owner,
    ) {}
}
