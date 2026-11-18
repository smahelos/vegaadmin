<?php

declare(strict_types=1);

namespace Tests\Unit\Phpstan\Rules;

use App\Infrastructure\Authorization\Services\PermissionCatalogSync;
use App\Infrastructure\Persistence\Eloquent\User\Repositories\EloquentUserRepository;
use Billex\PHPStan\Rules\RolesWrittenByRepositoryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @extends RuleTestCase<RolesWrittenByRepositoryRule>
 */
final class RolesWrittenByRepositoryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RolesWrittenByRepositoryRule;
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
    public function a_role_written_outside_its_writer_is_reported(): void
    {
        $this->analyse(
            [__DIR__ . '/data/roles-written-outside-the-repository.php'],
            [
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 17],
                ['Only ' . EloquentUserRepository::class . ' calls roles() on roles and permissions.', 18],
                ['Only ' . PermissionCatalogSync::class . ' calls givePermissionTo() on roles and permissions.', 25],
                ['Only ' . EloquentUserRepository::class . ' calls users() on roles and permissions.', 26],
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 32],
                ['Only ' . EloquentUserRepository::class . ' calls syncRoles() on roles and permissions.', 33],
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 38],
            ],
        );
    }

    #[Test]
    public function the_writers_write_roles(): void
    {
        $this->analyse(
            [
                __DIR__ . '/../../../../app/Infrastructure/Persistence/Eloquent/User/Repositories/EloquentUserRepository.php',
                __DIR__ . '/../../../../app/Infrastructure/Authorization/Services/PermissionCatalogSync.php',
            ],
            [],
        );
    }
}
