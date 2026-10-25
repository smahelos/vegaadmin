<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use Illuminate\Foundation\Auth\RedirectsUsers;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * laravel/ui's RedirectsUsers::redirectPath() reads the controller's $redirectTo after login,
 * registration and password reset.
 */
final class RedirectsUsersUsageProvider extends ReflectionBasedMemberUsageProvider
{
    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        if ($property->getName() !== 'redirectTo') {
            return null;
        }

        $traits = class_uses_recursive($property->getDeclaringClass()->getName());

        return in_array(
            RedirectsUsers::class,
            $traits,
            true,
        )
            ? VirtualUsageData::withNote('Read by RedirectsUsers::redirectPath()')
            : null;
    }
}
