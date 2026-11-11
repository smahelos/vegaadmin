<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * The gate calls handle() of the handler named after the use case (AuthorizingUseCaseRunner::handler()).
 */
final class UseCaseHandlerUsageProvider extends ReflectionBasedMemberUsageProvider
{
    private const SUFFIX = 'Handler';

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        $handler = $method->getDeclaringClass();
        if ($method->getName() !== 'handle' || !str_ends_with($handler->getName(), self::SUFFIX)) {
            return null;
        }
        $useCase = substr(
            $handler->getName(),
            0,
            -strlen(self::SUFFIX),
        );
        if (is_subclass_of($useCase, CommandInterface::class) || is_subclass_of($useCase, QueryInterface::class)) {
            return VirtualUsageData::withNote('Use case handler called by the gate');
        }

        return null;
    }
}
