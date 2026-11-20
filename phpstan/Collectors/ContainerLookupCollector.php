<?php

declare(strict_types=1);

namespace Billex\PHPStan\Collectors;

use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
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
 * Every lookup in the Laravel container: app() with or without an argument, resolve(), the App facade's make(),
 * makeWith(), get() and call() (also through the global alias \App) and Container::getInstance().
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
        'get',
        'call',
    ];

    /** The class names App::make() resolves through: the facade and its global alias */
    private const FACADES = [
        App::class,
        'App',
    ];

    private const CONTAINERS = [
        Container::class,
        Application::class,
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
        $class = $scope->resolveName($node->class);
        $method = $node->name->toLowerString();
        $container = $method === 'getinstance' && in_array(
            $class,
            self::CONTAINERS,
            true,
        );
        $facade = in_array(
            $method,
            self::FACADE_METHODS,
            true,
        ) && in_array(
            $class,
            self::FACADES,
            true,
        );
        if (!$container && !$facade) {
            return null;
        }

        return $node->class->getLast() . '::' . $node->name->toString() . '()';
    }
}
