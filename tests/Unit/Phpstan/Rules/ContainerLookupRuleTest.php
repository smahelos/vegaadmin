<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use Billex\PHPStan\Collectors\ContainerLookupCollector;
use Billex\PHPStan\Rules\ContainerLookupRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<ContainerLookupRule>
 */
final class ContainerLookupRuleTest extends RuleTestCase
{
    private const ADVICE = ' looks a dependency up in the container: inject it through the constructor or the method the framework calls, read framework state through its facade.';

    protected function getRule(): Rule
    {
        // The fixtures' app/ stands for application code, its Providers/ for the composition roots
        return new ContainerLookupRule(
            [__DIR__ . '/data/app/'],
            __DIR__ . '/data/app/Providers/',
            [
                __DIR__ . '/data/app/ContainerLookups/StillLooksUp.php',
                __DIR__ . '/data/app/ContainerLookups/LooksNothingUp.php',
            ],
        );
    }

    protected function getCollectors(): array
    {
        return [new ContainerLookupCollector];
    }

    #[Test]
    public function container_lookups_outside_the_providers_are_reported_and_the_list_only_shrinks(): void
    {
        $this->analyse(
            [
                __DIR__ . '/data/app/ContainerLookups/LooksUp.php',
                __DIR__ . '/data/app/ContainerLookups/StillLooksUp.php',
                __DIR__ . '/data/app/ContainerLookups/LooksNothingUp.php',
                __DIR__ . '/data/app/ContainerLookups/LooksUpInATrait.php',
                __DIR__ . '/data/app/ContainerLookups/UsesTheTrait.php',
                __DIR__ . '/data/app/Providers/ContainerLookupServiceProvider.php',
                __DIR__ . '/data/database/container-lookup-in-a-migration.php',
            ],
            [
                ['app()' . self::ADVICE, 20],
                ['app()' . self::ADVICE, 21],
                ['resolve()' . self::ADVICE, 23],
                ['App::make()' . self::ADVICE, 24],
                ['App::makeWith()' . self::ADVICE, 25],
                ['app()' . self::ADVICE, 13],
                [__DIR__ . '/data/app/ContainerLookups/LooksNothingUp.php looks nothing up in the container: remove it from the files of this rule in phpstan.neon.', -1],
            ],
        );
    }
}
