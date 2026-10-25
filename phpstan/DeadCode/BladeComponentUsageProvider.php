<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use Illuminate\View\Component;
use ReflectionMethod;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * Blade builds a class component from the attributes of its tag (<x-select :options="...">) and hands
 * its public properties and methods to the component's template.
 */
final class BladeComponentUsageProvider extends ReflectionBasedMemberUsageProvider
{
    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        return $method->isPublic() && $method->getDeclaringClass()->isSubclassOf(Component::class)
            ? VirtualUsageData::withNote('Blade component constructor or template method')
            : null;
    }

    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        return $property->isPublic() && $property->getDeclaringClass()->isSubclassOf(Component::class)
            ? VirtualUsageData::withNote('Blade component template variable')
            : null;
    }
}
