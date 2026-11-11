<?php

declare(strict_types=1);

namespace App\Domain\Shared\Authorization\ValueObjects;

use App\Domain\User\ValueObjects\UserId;

/**
 * Proof that the gate allowed reading the data of one owner. Only the gate issues it
 * (phpstan/Rules/ScopeIssuedByGateRule.php), so a port taking it cannot be reached around the gate.
 */
final readonly class ReadScope
{
    private function __construct(
        public UserId $owner,
    ) {}

    public static function issue(UserId $owner): self
    {
        return new self($owner);
    }
}
