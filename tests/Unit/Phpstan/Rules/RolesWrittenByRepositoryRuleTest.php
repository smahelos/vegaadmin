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
        // The fixtures' app/ stands for application code next to the real app/, whose writers are checked too
        return new RolesWrittenByRepositoryRule(
            [
                __DIR__ . '/data/app/',
                dirname(__DIR__, 4) . '/app/',
            ],
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
    public function a_role_written_outside_its_writer_is_reported(): void
    {
        $this->analyse(
            [__DIR__ . '/data/app/roles-written-outside-the-repository.php'],
            [
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 19],
                ['Only ' . EloquentUserRepository::class . ' calls roles() on roles and permissions.', 20],
                ['Only ' . PermissionCatalogSync::class . ' calls givePermissionTo() on roles and permissions.', 27],
                ['Only ' . EloquentUserRepository::class . ' calls users() on roles and permissions.', 28],
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 34],
                ['Only ' . EloquentUserRepository::class . ' calls syncRoles() on roles and permissions.', 35],
                ['Only ' . EloquentUserRepository::class . ' calls assignRole() on roles and permissions.', 44],
                ['Only ' . EloquentUserRepository::class . ' calls roles() on roles and permissions.', 45],
                ['Only ' . EloquentUserRepository::class . ' calls syncRoles() on roles and permissions.', 46],
                ['Only ' . EloquentUserRepository::class . ' calls removeRole() on roles and permissions.', 47],
            ],
        );
    }

    #[Test]
    public function code_outside_the_application_directories_writes_roles(): void
    {
        $this->analyse(
            [__DIR__ . '/data/database/roles-written-by-a-seeder.php'],
            [],
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
