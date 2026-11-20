<?php

declare(strict_types=1);

namespace Billex\PHPStan\Rules;

use Billex\PHPStan\Collectors\ContainerLookupCollector;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * A dependency is in the signature: only the composition roots in app/Providers work with the container
 * (rules/php.md). The files listed in phpstan.neon still look dependencies up until they are converted; the list only
 * shrinks, so a listed file without a lookup is reported as well.
 *
 * @implements Rule<CollectedDataNode>
 */
final class ContainerLookupRule implements Rule
{
    private const IDENTIFIER = 'billex.containerLookup';

    /**
     * @param  list<string>  $paths  directories of application code, absolute and ending with a slash
     * @param  string  $providers  directory of the composition roots, absolute and ending with a slash
     * @param  list<string>  $files  absolute paths of the files that still look dependencies up
     */
    public function __construct(
        private readonly array $paths,
        private readonly string $providers,
        private readonly array $files,
    ) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        $files = [];
        foreach ($node->get(ContainerLookupCollector::class) as $calls) {
            foreach ($calls as [$call, $file, $line]) {
                $files[$file] = true;
                $listed = in_array(
                    $file,
                    $this->files,
                    true,
                );
                // A trait is analysed in every class that uses it
                $key = $file . ':' . $line . ':' . $call;
                if ($listed || !$this->checks($file) || isset($errors[$key])) {
                    continue;
                }
                $error = RuleErrorBuilder::message(
                    $call . ' looks a dependency up in the container: inject it through the constructor or the method the framework calls, read framework state through its facade.',
                );
                $error->file($file);
                $error->line($line);
                $error->identifier(self::IDENTIFIER);
                $errors[$key] = $error->build();
            }
        }
        $errors = array_values($errors);
        // Analysing a few files collects the lookups of those files only
        if ($node->isOnlyFilesAnalysis()) {
            return $errors;
        }
        foreach ($this->files as $file) {
            if (isset($files[$file])) {
                continue;
            }
            $error = RuleErrorBuilder::message(
                $file . ' looks nothing up in the container: remove it from the files of this rule in phpstan.neon.',
            );
            $error->identifier(self::IDENTIFIER);
            $errors[] = $error->build();
        }

        return $errors;
    }

    private function checks(string $file): bool
    {
        if (str_starts_with($file, $this->providers)) {
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
