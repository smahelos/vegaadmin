<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * The gate calls a use case's handler by name and returns what handle() returns as the result the use case
 * declares (@implements CommandInterface<ContactDTO>), so the handler must exist and return that type
 * (rules/application.md).
 *
 * @implements Rule<InClassNode>
 */
final class UseCaseResultRule implements Rule
{
    private const INTERFACES = [
        CommandInterface::class,
        QueryInterface::class,
    ];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $useCase = $node->getClassReflection();
        if ($useCase->isInterface() || $useCase->isAbstract()) {
            return [];
        }

        foreach (self::INTERFACES as $interface) {
            if ($useCase->implementsInterface($interface)) {
                return $this->check($useCase, $interface);
            }
        }

        return [];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function check(ClassReflection $useCase, string $interface): array
    {
        $useCaseName = $useCase->getName();
        $implementsTags = $useCase->getImplementsTags();
        if (!isset($implementsTags[$interface])) {
            return [$this->error(
                $useCaseName . ' declares no result: add @implements ' . $interface . '<…>, <null> for none.',
            )];
        }
        $ancestor = $useCase->getAncestorWithClassName($interface);
        $templateTypes = $ancestor?->getActiveTemplateTypeMap();
        $declared = $templateTypes?->getType('TResult');
        if ($declared === null) {
            return [$this->error($useCaseName . ' declares no result type.')];
        }

        $handlerName = $useCaseName . 'Handler';
        if (!$this->reflectionProvider->hasClass($handlerName)) {
            return [$this->error($useCaseName . ' has no handler ' . $handlerName . '.')];
        }
        $handler = $this->reflectionProvider->getClass($handlerName);
        if (!$handler->hasNativeMethod('handle')) {
            return [$this->error($handlerName . ' has no handle method.')];
        }
        $handle = $handler->getNativeMethod('handle');
        $variant = $handle->getOnlyVariant();
        $returned = $variant->getReturnType();

        $declaresNull = $declared->isNull();
        $returnsVoid = $returned->isVoid();
        if ($declaresNull->yes()) {
            if ($returnsVoid->yes()) {
                return [];
            }
        } else {
            $accepts = $declared->isSuperTypeOf($returned);
            if (!$returnsVoid->yes() && $accepts->yes()) {
                return [];
            }
        }

        return [
            $this->error(
                $handlerName . '::handle() returns ' . $returned->describe(VerbosityLevel::typeOnly())
                . ', but ' . $useCaseName . ' declares ' . $declared->describe(VerbosityLevel::typeOnly())
                . ' (void for <null>).',
            ),
        ];
    }

    private function error(string $message): IdentifierRuleError
    {
        $error = RuleErrorBuilder::message($message);
        $error->identifier('billex.useCaseResult');

        return $error->build();
    }
}
