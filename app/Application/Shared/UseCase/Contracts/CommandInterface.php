<?php

declare(strict_types=1);

namespace App\Application\Shared\UseCase\Contracts;

use App\Application\Shared\UseCase\Access;

/**
 * A use case that changes data. Its handler is the class of the same name with the suffix Handler, which the
 * gate calls inside a transaction (rules/application.md).
 *
 * @template TResult
 */
interface CommandInterface
{
    public static function access(): Access;
}
