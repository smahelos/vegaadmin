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
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Spatie\Permission\Contracts\Permission;
use Spatie\Permission\Contracts\Role;

/**
 * The request's grants are forgotten by the one writer of a user's roles and permissions, so a write elsewhere
 * would leave the rest of the request on the old roles (D-049). PermissionCatalogSync may link roles to
 * permissions: those changes apply from the next request. Only application code is checked; tests, seeders and
 * migrations set roles directly.
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
        $writer = $this->writer($receiver);
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

    private function writer(Type $receiver): ?string
    {
        $user = new ObjectType(User::class);
        $isUser = $user->isSuperTypeOf($receiver);
        if ($isUser->yes()) {
            return EloquentUserRepository::class;
        }
        foreach ([Role::class, Permission::class] as $class) {
            $type = new ObjectType($class);
            $isModel = $type->isSuperTypeOf($receiver);
            if ($isModel->yes()) {
                return PermissionCatalogSync::class;
            }
        }

        return null;
    }
}
