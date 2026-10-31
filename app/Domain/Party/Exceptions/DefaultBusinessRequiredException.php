<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use App\Domain\Party\ValueObjects\BusinessId;
use DomainException;

/**
 * Thrown when the default business is archived while other businesses stay active and no new default was chosen.
 */
final class DefaultBusinessRequiredException extends DomainException
{
    public function __construct(BusinessId $id)
    {
        parent::__construct("Archiving the default business {$id->value} needs another active business as the default");
    }
}
