<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Enums;

enum ActorKind: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    /**
     * The guard that holds the actor's permissions.
     */
    public function guard(): string
    {
        return match ($this) {
            self::USER => 'web',
            self::ADMIN => 'backpack',
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
