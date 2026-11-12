<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use App\Domain\Shared\Authorization\ValueObjects\AllOwnersScope;
use App\Domain\Shared\Authorization\ValueObjects\ReadScope;
use App\Domain\Shared\Authorization\ValueObjects\WriteScope;
use App\Infrastructure\Authorization\Services\AuthorizingUseCaseRunner;
use Billex\PHPStan\Rules\ScopeIssuedByGateRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<ScopeIssuedByGateRule>
 */
final class ScopeIssuedByGateRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ScopeIssuedByGateRule;
    }

    #[Test]
    public function a_scope_issued_outside_the_gate_is_reported(): void
    {
        $this->analyse(
            [__DIR__ . '/data/scope-issued-outside-the-gate.php'],
            [
                ['Only the gate (' . AuthorizingUseCaseRunner::class . ') issues ' . ReadScope::class . '.', 16],
                ['Only the gate (' . AuthorizingUseCaseRunner::class . ') issues ' . WriteScope::class . '.', 18],
                ['Only the gate (' . AuthorizingUseCaseRunner::class . ') issues ' . AllOwnersScope::class . '.', 23],
            ],
        );
    }

    #[Test]
    public function the_gate_issues_scopes(): void
    {
        $this->analyse(
            [__DIR__ . '/../../../../app/Infrastructure/Authorization/Services/AuthorizingUseCaseRunner.php'],
            [],
        );
    }
}
