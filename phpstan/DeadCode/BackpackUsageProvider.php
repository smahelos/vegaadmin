<?php

declare(strict_types=1);

namespace Billex\PHPStan\DeadCode;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use ReflectionClass;
use ReflectionMethod;
use ShipMonk\PHPStan\DeadCode\Provider\ReflectionBasedMemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

/**
 * Backpack calls a CRUD controller's setup<Name>Routes, setup<Name>Defaults and setup<Name>Operation
 * methods by name (CrudController, CrudRouter), and routes each operation to the action its trait
 * declares, so a controller or operation trait overriding that action is reached through the route.
 */
final class BackpackUsageProvider extends ReflectionBasedMemberUsageProvider
{
    private const OPERATIONS_NAMESPACE = 'Backpack\\CRUD\\app\\Http\\Controllers\\Operations\\';

    protected function shouldMarkMethodAsUsed(ReflectionMethod $method): ?VirtualUsageData
    {
        $class = $method->getDeclaringClass();

        if (! $class->isSubclassOf(CrudController::class)) {
            return null;
        }

        if (preg_match('/^setup\w+(Routes|Defaults|Operation)$/', $method->getName()) === 1) {
            return VirtualUsageData::withNote('Backpack calls setup methods by name');
        }

        if ($method->isPublic() && $this->isOperationAction($class, $method->getName())) {
            return VirtualUsageData::withNote('Backpack routes the operation to this action');
        }

        return null;
    }

    /**
     * @param  ReflectionClass<object>  $class
     */
    private function isOperationAction(ReflectionClass $class, string $methodName): bool
    {
        foreach (class_uses_recursive($class->getName()) as $trait) {
            if (str_starts_with($trait, self::OPERATIONS_NAMESPACE) && method_exists($trait, $methodName)) {
                return true;
            }
        }

        return false;
    }
}
