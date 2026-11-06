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

        $declaringClass = $property->getDeclaringClass();
        $traits = class_uses_recursive($declaringClass->getName());

        if (
            in_array(
                RedirectsUsers::class,
                $traits,
                true,
            )
        ) {
            return VirtualUsageData::withNote('Read by RedirectsUsers::redirectPath()');
        }

        return null;
    }
}
