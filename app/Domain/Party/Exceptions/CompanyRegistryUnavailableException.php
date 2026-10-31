<?php

declare(strict_types=1);

namespace App\Domain\Party\Exceptions;

use RuntimeException;
use Throwable;

final class CompanyRegistryUnavailableException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct(
            'The company register did not answer',
            0,
            $previous,
        );
    }
}
