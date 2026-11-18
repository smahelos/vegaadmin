<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserRepository;
use App\Models\User;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

/**
 * The request's grants are forgotten by the one writer of a user's roles and permissions, so a write elsewhere
 * would leave the rest of the request on the old roles (D-049). The writer follows the table written, not the side
 * of the relation: a user's roles and direct permissions belong to EloquentUserRepository, also through a role's or
 * permission's users(); PermissionCatalogSync may link roles to permissions, which applies from the next request.
 * Only application code is checked; tests, seeders and migrations set roles directly.
 *
 * @implements Rule<MethodCall>
 */
final class RolesWrittenByRepositoryRule implements Rule
{
    private const METHODS = [
        'assignRole',
        'givePermissionTo',
        'permissions',
        'removeRole',
        'revokePermissionTo',
        'roles',
        'syncPermissions',
        'syncRoles',
        'users',
    ];

    private const RELATIONS = [
        'permissions',
        'roles',
        'users',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }
        $method = $node->name->toString();
        $namespace = $scope->getNamespace() ?? '';
        if (
            !in_array(
                $method,
                self::METHODS,
                true,
            )
            || !str_starts_with($namespace, 'App\\')
        ) {
            return [];
        }
        // A nullable receiver, a nullsafe call's included, writes the roles whenever it is not null
        $type = $scope->getType($node->var);
        $receiver = TypeCombinator::removeNull($type);
        $writer = $this->writer(
            $receiver,
            $method,
        );
        if ($writer === null) {
            return [];
        }
        $caller = $scope->getClassReflection();
        if ($caller !== null && $caller->getName() === $writer) {
            return [];
        }
        $error = RuleErrorBuilder::message(
            'Only ' . $writer . ' calls ' . $method . '() on roles and permissions.',
        );
        $error->identifier('billex.rolesWrittenByRepository');

        return [$error->build()];
    }

    private function writer(Type $receiver, string $method): ?string
    {
        $user = new ObjectType(User::class);
        $isUser = $user->isSuperTypeOf($receiver);
        if ($isUser->yes() || $this->hidesWrite($receiver, $method)) {
            return EloquentUserRepository::class;
        }
        foreach ([Role::class, Permission::class] as $class) {
            $type = new ObjectType($class);
            $isModel = $type->isSuperTypeOf($receiver);
            if (!$isModel->yes()) {
                continue;
            }
            // Links users to the role or permission: the user's own grants
            if ($method === 'users') {
                return EloquentUserRepository::class;
            }

            return PermissionCatalogSync::class;
        }

        return null;
    }

    /**
     * A receiver of unknown type (backpack_user(), an untyped vendor return) may be a user, so a Spatie write method
     * on it counts; relation names are common to many models, so they count only on a certain receiver.
     */
    private function hidesWrite(Type $receiver, string $method): bool
    {
        $relation = in_array(
            $method,
            self::RELATIONS,
            true,
        );

        return $receiver instanceof MixedType && !$relation;
    }
}
