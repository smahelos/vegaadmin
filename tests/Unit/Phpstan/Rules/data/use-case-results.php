<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules\data\UseCaseResults;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Domain\User\Enums\Permission;

/** @implements QueryInterface<string> */
final readonly class Matching implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_VIEW);
    }
}

final class MatchingHandler
{
    public function handle(Matching $query): string
    {
        return 'found';
    }
}

/** @implements CommandInterface<null> */
final readonly class WithoutResult implements CommandInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_DELETE);
    }
}

final class WithoutResultHandler
{
    public function handle(WithoutResult $command): void {}
}

final readonly class Undeclared implements CommandInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_CREATE);
    }
}

final class UndeclaredHandler
{
    public function handle(Undeclared $command): void {}
}

/** @implements CommandInterface<int> */
final readonly class Mismatched implements CommandInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_UPDATE);
    }
}

final class MismatchedHandler
{
    public function handle(Mismatched $command): string
    {
        return 'not an int';
    }
}

/** @implements QueryInterface<string> */
final readonly class Unhandled implements QueryInterface
{
    public static function access(): Access
    {
        return Access::permission(Permission::PRODUCT_VIEW);
    }
}
