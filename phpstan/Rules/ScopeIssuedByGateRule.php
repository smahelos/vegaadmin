<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Infrastructure\Authorization\Services\AuthorizingUseCaseRunner;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A scope is the proof that the gate checked the permission and the owner, so only the gate issues one; tests
 * have no exception and run use cases through the real gate (rules/permissions.md).
 *
 * @implements Rule<StaticCall>
 */
final class ScopeIssuedByGateRule implements Rule
{
    private const SCOPES = [
        ReadScope::class,
        WriteScope::class,
    ];

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Name) {
            return [];
        }
        $class = $scope->resolveName($node->class);
        if (
            !in_array(
                $class,
                self::SCOPES,
                true,
            )
        ) {
            return [];
        }
        $caller = $scope->getClassReflection();
        if ($caller !== null && $caller->getName() === AuthorizingUseCaseRunner::class) {
            return [];
        }
        $error = RuleErrorBuilder::message(
            'Only the gate (' . AuthorizingUseCaseRunner::class . ') issues ' . $class . '.',
        );
        $error->identifier('billex.scopeIssuedByGate');

        return [$error->build()];
    }
}
