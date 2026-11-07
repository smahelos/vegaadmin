<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * How a role limit is set (D-047): a number, unlimited (NULL) or not allowed (0).
 */
enum LimitMode: string
{
    case LIMITED = 'limited';
    case UNLIMITED = 'unlimited';
    case NOT_ALLOWED = 'not_allowed';

    public static function of(?int $limit): self
    {
        return match ($limit) {
            null => self::UNLIMITED,
            0 => self::NOT_ALLOWED,
            default => self::LIMITED,
        };
    }
}
