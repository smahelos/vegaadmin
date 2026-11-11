<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Contracts;

use App\Application\Shared\UseCase\Access;

/**
 * A use case that only reads. Its handler is the class of the same name with the suffix Handler, which the
 * gate calls without a transaction (rules/application.md).
 *
 * @template TResult
 */
interface QueryInterface
{
    public static function access(): Access;
}
