<?php

declare(strict_types=1);

namespace Billex\PHPStan\Collectors;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

/**
 * Every call a controller makes to an Eloquent model, its query builder or relation, or DB. A call on a parameter of a
 * closure is not collected: Backpack passes its columns, fields and filters the record or query it loaded itself.
 *
 * @implements Collector<CallLike, array{string, string, string, int}>
 */
final class EloquentCallCollector implements Collector
{
    private const CONTROLLERS = 'App\\Http\\Controllers\\';

    private const TYPES = [
        Model::class,
        Builder::class,
        Relation::class,
        DB::class,
    ];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        if (!$node instanceof StaticCall && !$node instanceof MethodCall) {
            return null;
        }
        $controller = $scope->getClassReflection();
        if ($controller === null || !str_starts_with($controller->getName(), self::CONTROLLERS)) {
            return null;
        }
        // A trait counts where it is written: form fields of app/Infrastructure/Forms may load their options
        $trait = $scope->getTraitReflection();
        if ($trait !== null && !str_starts_with($trait->getName(), self::CONTROLLERS)) {
            return null;
        }
        $receiver = $this->receiver($node, $scope);
        if ($receiver === null || !$this->isEloquent($receiver)) {
            return null;
        }
        $method = $node->name instanceof Identifier ? $node->name->toString() : '{expression}';

        return [
            $controller->getName(),
            $receiver->describe(VerbosityLevel::typeOnly()) . '::' . $method . '()',
            $trait?->getFileName() ?? $scope->getFile(),
            $node->getStartLine(),
        ];
    }

    private function receiver(StaticCall|MethodCall $node, Scope $scope): ?Type
    {
        if ($node instanceof StaticCall) {
            if ($node->class instanceof Name) {
                return new ObjectType(
                    $scope->resolveName($node->class),
                );
            }
            $class = $scope->getType($node->class);

            return $class->getObjectTypeOrClassStringObjectType();
        }
        // PHPStan visits a nullsafe call as a MethodCall too
        if ($this->isClosureParameter($node->var, $scope)) {
            return null;
        }
        $type = $scope->getType($node->var);

        return TypeCombinator::removeNull($type);
    }

    private function isClosureParameter(Expr $receiver, Scope $scope): bool
    {
        $closure = $scope->getAnonymousFunctionReflection();
        if ($closure === null || !$receiver instanceof Variable || !is_string($receiver->name)) {
            return false;
        }
        foreach ($closure->getParameters() as $parameter) {
            if ($parameter->getName() === $receiver->name) {
                return true;
            }
        }

        return false;
    }

    private function isEloquent(Type $receiver): bool
    {
        foreach (self::TYPES as $class) {
            $type = new ObjectType($class);
            $isEloquent = $type->isSuperTypeOf($receiver);
            if ($isEloquent->yes()) {
                return true;
            }
        }

        return false;
    }
}
