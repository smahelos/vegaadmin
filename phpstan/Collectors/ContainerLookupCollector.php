<?php

declare(strict_types=1);

namespace Billex\PHPStan\Collectors;

use Illuminate\Support\Facades\App;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

/**
 * Every lookup in the Laravel container: app() with or without an argument, resolve() and App::make().
 *
 * @implements Collector<CallLike, array{string, string, int}>
 */
final class ContainerLookupCollector implements Collector
{
    private const FUNCTIONS = [
        'app',
        'resolve',
    ];

    private const FACADE_METHODS = [
        'make',
        'makewith',
    ];

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): ?array
    {
        $call = $this->lookup($node, $scope);
        if ($call === null) {
            return null;
        }

        // A trait counts where it is written, not in each class that uses it
        $trait = $scope->getTraitReflection();

        return [
            $call,
            $trait?->getFileName() ?? $scope->getFile(),
            $node->getStartLine(),
        ];
    }

    private function lookup(Node $node, Scope $scope): ?string
    {
        if ($node instanceof FuncCall && $node->name instanceof Name) {
            $function = $node->name->toLowerString();
            $listed = in_array(
                $function,
                self::FUNCTIONS,
                true,
            );

            return $listed ? $function . '()' : null;
        }
        if (!$node instanceof StaticCall || !$node->class instanceof Name || !$node->name instanceof Identifier) {
            return null;
        }
        $method = $node->name->toLowerString();
        $listed = in_array(
            $method,
            self::FACADE_METHODS,
            true,
        );
        if (!$listed || $scope->resolveName($node->class) !== App::class) {
            return null;
        }

        return 'App::' . $node->name->toString() . '()';
    }
}
