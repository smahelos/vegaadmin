<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use Billex\PHPStan\Collectors\EloquentCallCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A controller loads and writes no data itself: it runs a use case, and Backpack loads the records of its CRUD
 * (rules/architecture.md, rules/admin.md). The controllers listed in phpstan.neon still call Eloquent until they are
 * converted; the list only shrinks, so a listed controller that calls no Eloquent any more is reported as well.
 *
 * @implements Rule<CollectedDataNode>
 */
final class EloquentOutsideControllersRule implements Rule
{
    private const IDENTIFIER = 'billex.eloquentOutsideControllers';

    /**
     * @param  list<class-string>  $controllers  controllers that still call Eloquent
     */
    public function __construct(
        private readonly array $controllers,
    ) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        $callers = [];
        foreach ($node->get(EloquentCallCollector::class) as $calls) {
            foreach ($calls as [$controller, $call, $file, $line]) {
                $callers[$controller] = true;
                if (
                    in_array(
                        $controller,
                        $this->controllers,
                        true,
                    )
                ) {
                    continue;
                }
                $error = RuleErrorBuilder::message(
                    $controller . ' calls ' . $call . ': a controller runs a use case, which loads and writes the data.',
                );
                $error->file($file);
                $error->line($line);
                $error->identifier(self::IDENTIFIER);
                $errors[] = $error->build();
            }
        }
        // Analysing a few files collects the calls of those files only
        if ($node->isOnlyFilesAnalysis()) {
            return $errors;
        }
        foreach ($this->controllers as $controller) {
            if (isset($callers[$controller])) {
                continue;
            }
            $error = RuleErrorBuilder::message(
                $controller . ' calls no Eloquent: remove it from the controllers of this rule in phpstan.neon.',
            );
            $error->identifier(self::IDENTIFIER);
            $errors[] = $error->build();
        }

        return $errors;
    }
}
