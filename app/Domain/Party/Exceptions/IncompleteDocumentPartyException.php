<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use DomainException;

final class IncompleteDocumentPartyException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The selected contact has no complete document address');
    }
}
