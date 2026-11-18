<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use Billex\PHPStan\Rules\PermissionsReadByGateRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<PermissionsReadByGateRule>
 */
final class PermissionsReadByGateRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $root = dirname(__DIR__, 4);

        // The fixtures' app/ stands for application code next to the real app/, whose gate reads permissions
        return new PermissionsReadByGateRule(
            [
                __DIR__ . '/data/app/',
                $root . '/app/',
            ],
            $root . '/app/Infrastructure/Authorization/',
        );
    }

    /**
     * The rule decides by the receiver's type, which the project's stubs give, as in the Larastan run.
     *
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../../phpstan/stubs.neon'];
    }

    #[Test]
    public function a_permission_read_outside_the_gate_is_reported(): void
    {
        $message = 'Only the gate reads permissions and roles: use holds() or allowed() instead of %s().';

        $this->analyse(
            [__DIR__ . '/data/app/permissions-read-outside-the-gate.php'],
            [
                [sprintf($message, 'can'), 19],
                [sprintf($message, 'hasPermissionTo'), 20],
                [sprintf($message, 'hasRole'), 21],
                [sprintf($message, 'getAllPermissions'), 22],
                [sprintf($message, 'hasAnyRole'), 30],
                [sprintf($message, 'can'), 31],
                [sprintf($message, 'getRoleNames'), 32],
            ],
        );
    }

    #[Test]
    public function the_gate_reads_permissions(): void
    {
        $this->analyse(
            [__DIR__ . '/../../../../app/Infrastructure/Authorization/Services/GrantStore.php'],
            [],
        );
    }
}
