<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use Livewire\Component;
use Livewire\Form;
use ReflectionMethod;
use ReflectionProperty;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * The browser calls a Livewire component's public methods as actions and reads and writes its public
 * properties, also those of its form objects (wire:model="form.name"); Livewire calls the lifecycle hooks by
 * name (SupportLifecycleHooks), for traits with the trait's name appended (mountPaginates), and reads the
 * validation rules, messages and attribute names of components and form objects by name (HandlesValidation).
 */
final class LivewireUsageProvider extends ReflectionBasedMemberUsageProvider
{
    private const LIFECYCLE_HOOK = '/^(boot|booted|initialize|mount|hydrate|dehydrate|updating|updated|rendering|rendered|exception)/';

    private const VALIDATION_METHODS = [
        'rules',
        'messages',
        'validationAttributes',
    ];

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        $class = $method->getDeclaringClass();
        $validates = $class->isSubclassOf(Component::class) || $class->isSubclassOf(Form::class);
        if ($validates && in_array(
            $method->getName(),
            self::VALIDATION_METHODS,
            true,
        )) {
            return VirtualUsageData::withNote('Livewire validation read by name');
        }

        if (! $class->isSubclassOf(Component::class)) {
            return null;
        }

        if ($method->isPublic()) {
            return VirtualUsageData::withNote('Livewire action or hook');
        }

        if (preg_match(self::LIFECYCLE_HOOK, $method->getName()) === 1) {
            return VirtualUsageData::withNote('Livewire lifecycle hook');
        }

        return null;
    }

    protected function shouldMarkPropertyAsRead(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->isComponentState($property)
            ? VirtualUsageData::withNote('Livewire state read by the view and the browser')
            : null;
    }

    protected function shouldMarkPropertyAsWritten(ReflectionProperty $property): ?VirtualUsageData
    {
        return $this->isComponentState($property)
            ? VirtualUsageData::withNote('Livewire state written by mount parameters and the browser')
            : null;
    }

    private function isComponentState(ReflectionProperty $property): bool
    {
        $class = $property->getDeclaringClass();

        return $property->isPublic() && ($class->isSubclassOf(Component::class) || $class->isSubclassOf(Form::class));
    }
}
