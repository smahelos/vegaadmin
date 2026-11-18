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
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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
 * A call counts on every receiver that may be a user, role or permission (mixed, Authenticatable, a union), unless
 * its type declares the method on another class. Only application code is checked, the directories given in
 * phpstan.neon; tests, seeders and migrations set roles directly.
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

    private const MODELS = [
        User::class,
        Role::class,
        Permission::class,
    ];

    /**
     * @param  list<string>  $paths  directories of application code, absolute and ending with a slash
     */
    public function __construct(
        private readonly array $paths,
    ) {}

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
        $listed = in_array(
            $method,
            self::METHODS,
            true,
        );
        if (!$listed || !$this->checks($scope->getFile())) {
            return [];
        }
        // A nullable receiver, a nullsafe call's included, writes the roles whenever it is not null
        $type = $scope->getType($node->var);
        $receiver = TypeCombinator::removeNull($type);
        $writer = $this->writer(
            $receiver,
            $method,
            $scope,
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

    private function checks(string $file): bool
    {
        foreach ($this->paths as $path) {
            if (str_starts_with($file, $path)) {
                return true;
            }
        }

        return false;
    }

    private function writer(
        Type $receiver,
        string $method,
        Scope $scope,
    ): ?string {
        // The repository port's own assignRole() is declared on the port, not on a role model; mixed declares any
        $declared = $receiver->hasMethod($method);
        $object = $receiver->isObject();
        if ($declared->yes() && $object->yes()) {
            $reflection = $receiver->getMethod($method, $scope);
            $declaring = $reflection->getDeclaringClass();
            if (!$this->isModel($declaring)) {
                return null;
            }
        }
        // A certain model decides before a possible one: a role typed by its contract may also be some user subclass
        $possible = null;
        foreach (self::MODELS as $model) {
            $type = new ObjectType($model);
            $isModel = $type->isSuperTypeOf($receiver);
            if ($isModel->yes()) {
                return $this->writerOf($model, $method);
            }
            if ($isModel->maybe()) {
                $possible ??= $model;
            }
        }
        if ($possible === null) {
            return null;
        }

        return $this->writerOf($possible, $method);
    }

    private function isModel(ClassReflection $class): bool
    {
        foreach (self::MODELS as $model) {
            if ($class->is($model)) {
                return true;
            }
        }

        return false;
    }

    private function writerOf(string $model, string $method): string
    {
        // users() links users to a role or permission: the user's own grants
        if ($model === User::class || $method === 'users') {
            return EloquentUserRepository::class;
        }

        return PermissionCatalogSync::class;
    }
}
