<?php

declare(strict_types=1);

namespace App\Domain\Shared\Authorization\ValueObjects;

use App\Domain\User\ValueObjects\UserId;

/**
 * Proof that the gate allowed changing the data of one owner. It holds the ReadScope of the same owner, so a
 * Command reads through the read ports without a second proof.
 */
final readonly class WriteScope
{
    private function __construct(
        public UserId $owner,
        public ReadScope $read,
    ) {}

    public static function issue(UserId $owner, ReadScope $read): self
    {
        return new self($owner, $read);
    }
}
