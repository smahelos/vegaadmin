<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Enums;

enum ActorKind: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case ANONYMOUS = 'anonymous';
    case SYSTEM = 'system';

    /**
     * The guard that holds the actor's permissions; null for an actor without a user.
     */
    public function guard(): ?string
    {
        return match ($this) {
            self::USER => 'web',
            self::ADMIN => 'backpack',
            self::ANONYMOUS, self::SYSTEM => null,
        };
    }

    public static function fromGuard(string $guard): ?self
    {
        foreach (self::cases() as $kind) {
            if ($kind->guard() === $guard) {
                return $kind;
            }
        }

        return null;
    }
}
