<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use App\Models\User;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

/**
 * Access is decided only by the gate, which reads a user's permissions and roles once per request through
 * GrantStore (rules/permissions.md, D-049); a check elsewhere would bypass the guard of the actor and the grants
 * already loaded. A call counts on every receiver that may be a user (mixed, Authenticatable, a union), unless its
 * type declares the method on another class. Application code is checked, the directories given in phpstan.neon,
 * except the gate's own directory.
 *
 * @implements Rule<MethodCall>
 */
final class PermissionsReadByGateRule implements Rule
{
    private const METHODS = [
        'can',
        'canAny',
        'cannot',
        'cant',
        'checkPermissionTo',
        'getAllPermissions',
        'getDirectPermissions',
        'getPermissionNames',
        'getPermissionsViaRoles',
        'getRoleNames',
        'hasAllDirectPermissions',
        'hasAllPermissions',
        'hasAllRoles',
        'hasAnyDirectPermission',
        'hasAnyPermission',
        'hasAnyRole',
        'hasDirectPermission',
        'hasExactRoles',
        'hasPermissionTo',
        'hasRole',
    ];

    /**
     * @param  list<string>  $paths  directories of application code, absolute and ending with a slash
     * @param  string  $gate  directory of the gate, absolute and ending with a slash
     */
    public function __construct(
        private readonly array $paths,
        private readonly string $gate,
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
        $type = $scope->getType($node->var);
        $receiver = TypeCombinator::removeNull($type);
        // A method of the same name on another class (a port, a policy object) is not a permission read
        $declared = $receiver->hasMethod($method);
        $object = $receiver->isObject();
        if ($declared->yes() && $object->yes()) {
            $reflection = $receiver->getMethod($method, $scope);
            $declaring = $reflection->getDeclaringClass();
            // can() comes from a parent of User, so the method counts when User inherits it
            $declaringType = new ObjectType($declaring->getName());
            $inherited = $declaringType->isSuperTypeOf(
                new ObjectType(User::class),
            );
            if (!$inherited->yes()) {
                return [];
            }
        }
        $user = new ObjectType(User::class);
        $isUser = $user->isSuperTypeOf($receiver);
        if ($isUser->no()) {
            return [];
        }
        $error = RuleErrorBuilder::message(
            'Only the gate reads permissions and roles: use holds() or allowed() instead of ' . $method . '().',
        );
        $error->identifier('billex.permissionsReadByGate');

        return [$error->build()];
    }

    private function checks(string $file): bool
    {
        if (str_starts_with($file, $this->gate)) {
            return false;
        }
        foreach ($this->paths as $path) {
            if (str_starts_with($file, $path)) {
                return true;
            }
        }

        return false;
    }
}
