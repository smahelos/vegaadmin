<?php

declare(strict_types=1);

namespace App\Domain\Shared\Authorization\ValueObjects;

/**
 * Proof that the gate allowed reading the data of every owner, issued for Access::allOwners and Access::system.
 * A separate type from ReadScope, so a port over one owner's data can never receive it; only the ports that serve
 * an admin-wide read accept it. Only the gate issues it (phpstan/Rules/ScopeIssuedByGateRule.php).
 */
final readonly class AllOwnersScope
{
    private function __construct() {}

    public static function issue(): self
    {
        return new self;
    }
}
