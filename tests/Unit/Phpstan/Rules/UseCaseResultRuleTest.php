<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use App\Application\Shared\UseCase\Contracts\CommandInterface;
use Billex\PHPStan\Rules\UseCaseResultRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<UseCaseResultRule>
 */
final class UseCaseResultRuleTest extends RuleTestCase
{
    private const FIXTURES = 'Tests\Unit\Phpstan\Rules\data\UseCaseResults\\';

    protected function getRule(): Rule
    {
        return new UseCaseResultRule(
            $this->createReflectionProvider(),
        );
    }

    #[Test]
    public function a_handler_must_exist_and_return_the_declared_result(): void
    {
        // PHPStan reflects the fixture classes only once they are loaded
        require_once __DIR__ . '/data/use-case-results.php';

        $this->analyse(
            [__DIR__ . '/data/use-case-results.php'],
            [
                [self::FIXTURES . 'Undeclared declares no result: add @implements ' . CommandInterface::class . '<…>, <null> for none.', 43],
                [self::FIXTURES . 'MismatchedHandler::handle() returns string, but ' . self::FIXTURES . 'Mismatched declares int (void for <null>).', 57],
                [self::FIXTURES . 'Unhandled has no handler ' . self::FIXTURES . 'UnhandledHandler.', 74],
            ],
        );
    }
}
