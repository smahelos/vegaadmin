<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase;

use App\Application\Shared\UseCase\Enums\ActorKind;
use App\Domain\User\ValueObjects\UserId;

/**
 * Who runs a use case. A closed set: a user reaches only their own data, an admin anyone's
 * (rules/permissions.md).
 */
final readonly class Actor
{
    private function __construct(
        public ActorKind $kind,
        public UserId $user,
    ) {}

    public static function user(UserId $id): self
    {
        return new self(ActorKind::USER, $id);
    }

    public static function admin(UserId $id): self
    {
        return new self(ActorKind::ADMIN, $id);
    }
}
