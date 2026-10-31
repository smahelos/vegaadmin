<?php

declare(strict_types=1);

namespace App\Domain\User\Enums;

/**
 * An entity with a capacity limit; the value is the entity_type of its entity_limits rows.
 */
enum EntityType: string
{
    case BUSINESS = 'business';
    case CONTACT = 'contact';
}
